<?php
/**
 * Plugin Name: UV People
 * Description: Extends WordPress Users with public fields, media-library avatars, per-location assignments, and a Team grid shortcode.
 * Version: 0.8.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: Unge Vil
 * Author URI: https://www.ungevil.no/
 * Text Domain: uv-people
 * Update URI: https://github.com/Unge-Vil/Unge-Vil-Website/plugins/uv-people
 */
if (!defined('ABSPATH')) exit;

if (!defined('UV_PEOPLE_VERSION')) {
    define('UV_PEOPLE_VERSION', '0.8.0');
}

$update_checker_path = __DIR__ . '/plugin-update-checker/plugin-update-checker.php';
if (file_exists($update_checker_path)) {
    require $update_checker_path;
    $uvPeopleUpdateChecker = \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
        'https://github.com/Unge-Vil/Unge-Vil-Website/',
        __FILE__,
        'uv-people'
    );
    $uvPeopleUpdateChecker->setBranch('main');
    if (method_exists($uvPeopleUpdateChecker, 'setPathInsideRepository')) {
        $uvPeopleUpdateChecker->setPathInsideRepository('plugins/uv-people');
    }
}

function uv_people_cache_ttl(){
    return (int) apply_filters('uv_people_cache_ttl', HOUR_IN_SECONDS);
}

function uv_people_get_team_grid_cache_key($location_id){
    return 'uv_people_team_grid_' . md5((string) $location_id);
}

function uv_people_get_all_team_grid_cache_key($location_ids){
    $location_ids = array_filter(array_map('intval', (array) $location_ids));
    sort($location_ids);

    return 'uv_people_all_team_grid_' . md5(implode(',', $location_ids));
}

function uv_people_get_team_cache_prefixes(){
    return [
        'uv_people_team_grid_',
        'uv_people_all_team_grid_',
    ];
}

function uv_people_delete_transients_with_prefix($prefix){
    global $wpdb;

    $transient_like         = $wpdb->esc_like('_transient_' . $prefix) . '%';
    $transient_timeout_like = $wpdb->esc_like('_transient_timeout_' . $prefix) . '%';

    $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $transient_like));
    $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $transient_timeout_like));
}

function uv_people_clear_team_caches($prefixes = null){
    $prefixes = $prefixes ?? uv_people_get_team_cache_prefixes();

    foreach ((array) $prefixes as $prefix) {
        uv_people_delete_transients_with_prefix($prefix);
    }
}

// Load textdomain
add_action('plugins_loaded', function(){
    load_plugin_textdomain('uv-people', false, dirname(plugin_basename(__FILE__)) . '/languages');
});

function uv_register_select2_assets(){
    if (!wp_script_is('select2', 'registered')) {
        wp_register_script(
            'select2',
            plugin_dir_url(__FILE__) . 'assets/select2/select2.min.js',
            ['jquery'],
            '4.0.13',
            true
        );
    }
    if (!wp_style_is('select2', 'registered')) {
        wp_register_style(
            'select2',
            plugin_dir_url(__FILE__) . 'assets/select2/select2.min.css',
            [],
            '4.0.13'
        );
    }
}

// Admin assets and localizations
add_action('admin_enqueue_scripts', function($hook){
    $screen = get_current_screen();
    $is_user_page = in_array($hook, ['profile.php', 'user-edit.php'], true);
    $is_control_panel = ('toplevel_page_uv-control-panel' === $hook);
    $is_location_term = $screen && 'uv_location' === $screen->taxonomy && 'term' === $screen->base;

    if ($is_user_page || $is_control_panel || $is_location_term) {
        if ($is_user_page) {
            wp_enqueue_media();
        }
        uv_register_select2_assets();
        wp_enqueue_style('select2');
        wp_enqueue_script('select2');
        $deps = ['jquery', 'select2'];
        if ($is_location_term) {
            $deps[] = 'jquery-ui-sortable';
        }
        wp_enqueue_script(
            'uv-people-admin',
            plugin_dir_url(__FILE__) . 'assets/admin.js',
            $deps,
            UV_PEOPLE_VERSION,
            true
        );
        wp_localize_script('uv-people-admin', 'UVPeople', [
            'selectAvatar' => __('Velg avatar', 'uv-people'),
            'useImage' => __('Bruk bilde', 'uv-people'),
        ]);
    }
});

function uv_people_remove_default_bio_field(){
    ?>
    <style>.user-description-wrap{display:none!important;}</style>
    <script>
    document.addEventListener('DOMContentLoaded', function(){
        var row = document.querySelector('.user-description-wrap');
        if (row) row.remove();
    });
    </script>
    <?php
}
add_action('admin_head-user-edit.php', 'uv_people_remove_default_bio_field');
add_action('admin_head-profile.php', 'uv_people_remove_default_bio_field');

// Taxonomy: uv_position
add_action('init', function(){
    register_taxonomy('uv_position', null, [
        'label'        => __('Stillinger', 'uv-people'),
        'public'       => false,
        'show_ui'      => true,
        'hierarchical' => false,
        'show_in_rest' => true,
        'meta_box_cb'  => false,
        // Place the taxonomy under the UV Control Panel so non-admins can reach it
        'show_in_menu' => 'uv-control-panel',
        // Ensure editors can manage and assign terms
        'capabilities' => [
            'manage_terms' => 'manage_categories',
            'edit_terms'   => 'manage_categories',
            'delete_terms' => 'manage_categories',
            'assign_terms' => 'edit_posts',
        ],
    ]);
});

// Term meta: uv_rank_weight
add_action('init', function(){
    register_term_meta('uv_position', 'uv_rank_weight', [
        'type'              => 'number',
        'single'            => true,
        'sanitize_callback' => 'intval',
        'show_in_rest'      => true,
    ]);
});

add_action('uv_position_add_form_fields', function(){
    ?>
    <div class="form-field term-rank-weight-wrap">
        <label for="uv_rank_weight"><?php esc_html_e('Rank Weight', 'uv-people'); ?></label>
        <input type="number" name="uv_rank_weight" id="uv_rank_weight" value="" class="small-text">
        <p class="description"><?php esc_html_e('Sorting weight; lower numbers appear first.', 'uv-people'); ?></p>
    </div>
    <?php
});

add_action('uv_position_edit_form_fields', function($term){
    $value = get_term_meta($term->term_id, 'uv_rank_weight', true);
    ?>
    <tr class="form-field term-rank-weight-wrap">
        <th scope="row"><label for="uv_rank_weight"><?php esc_html_e('Rank Weight', 'uv-people'); ?></label></th>
        <td>
            <input type="number" name="uv_rank_weight" id="uv_rank_weight" value="<?php echo esc_attr($value); ?>" class="small-text">
            <p class="description"><?php esc_html_e('Sorting weight; lower numbers appear first.', 'uv-people'); ?></p>
        </td>
    </tr>
    <?php
});

$uv_people_save_rank_weight = function($term_id){
    if(isset($_POST['uv_rank_weight'])){
        $val = $_POST['uv_rank_weight'] === '' ? '' : intval($_POST['uv_rank_weight']);
        update_term_meta($term_id, 'uv_rank_weight', $val);
    }
};
add_action('created_uv_position', $uv_people_save_rank_weight);
add_action('edited_uv_position', $uv_people_save_rank_weight);

// User profile fields (phone, public email, quote, socials, avatar attachment)
function uv_people_profile_fields($user){
    $phone       = get_user_meta($user->ID, 'uv_phone', true);
    $position    = get_user_meta($user->ID, 'uv_position_term', true);
    $quote_nb    = get_user_meta($user->ID, 'uv_quote_nb', true);
    $quote_en    = get_user_meta($user->ID, 'uv_quote_en', true);
    $bio_nb      = get_user_meta($user->ID, 'uv_bio_nb', true);
    $bio_en      = get_user_meta($user->ID, 'uv_bio_en', true);
    $show_phone  = get_user_meta($user->ID, 'uv_show_phone', true) === '1';
    $avatar_id   = get_user_meta($user->ID, 'uv_avatar_id', true);
    $birthdate   = get_user_meta($user->ID, 'uv_birthdate', true);
    // Guard against missing uv_location taxonomy when uv-core is inactive or removed
    $locations  = [];
    if (taxonomy_exists('uv_location')) {
        $locations = get_terms(['taxonomy' => 'uv_location', 'hide_empty' => false]);
        if (is_wp_error($locations)) {
            $locations = [];
        }
    }
    $positions = get_terms(['taxonomy' => 'uv_position', 'hide_empty' => false]);
    if (is_wp_error($positions)) {
        $positions = [];
    }
    $assigned   = get_user_meta($user->ID, 'uv_location_terms', true);
    if(!is_array($assigned)) $assigned = [];
    $primary_locations = get_user_meta($user->ID, 'uv_primary_locations', true);
    if(!is_array($primary_locations)) $primary_locations = [];
    ?>
    <h2><?php esc_html_e('Public Profile (Unge Vil)','uv-people'); ?></h2>
    <table class="form-table">
        <tr><th><label for="uv_locations"><?php esc_html_e('Locations','uv-people'); ?></label></th>
        <td>
            <input type="hidden" name="uv_locations[]" value="">
            <select name="uv_locations[]" id="uv_locations" class="uv-location-select" multiple style="width:100%">
                <?php foreach($locations as $loc): ?>
                <option value="<?php echo esc_attr($loc->term_id); ?>" <?php selected(in_array($loc->term_id, $assigned)); ?>><?php echo esc_html($loc->name); ?></option>
                <?php endforeach; ?>
            </select>
        </td></tr>
        <tr><th><label for="uv_primary_locations"><?php esc_html_e('Primary Locations','uv-people'); ?></label></th>
        <td>
            <input type="hidden" name="uv_primary_locations[]" value="">
            <select name="uv_primary_locations[]" id="uv_primary_locations" class="uv-location-select" multiple style="width:100%">
                <?php foreach($locations as $loc): ?>
                <option value="<?php echo esc_attr($loc->term_id); ?>" <?php selected(in_array($loc->term_id, $primary_locations)); ?>><?php echo esc_html($loc->name); ?></option>
                <?php endforeach; ?>
            </select>
            <p class="description"><?php esc_html_e('Locations where this user is a primary contact.','uv-people'); ?></p>
        </td></tr>
      <tr><th><label for="uv_phone"><?php esc_html_e('Phone (public optional)','uv-people'); ?></label></th>
        <td>
            <input type="tel" name="uv_phone" id="uv_phone" value="<?php echo esc_attr($phone); ?>" class="regular-text">
            <br><label><input type="checkbox" name="uv_show_phone" value="1" <?php checked($show_phone); ?>> <?php esc_html_e('Show on profile','uv-people'); ?></label>
        </td></tr>
      <tr><th><label for="uv_birthdate"><?php esc_html_e('Birthdate','uv-people'); ?></label></th>
        <td>
            <input type="date" name="uv_birthdate" id="uv_birthdate" value="<?php echo esc_attr($birthdate); ?>" class="regular-text">
        </td></tr>
      <tr><th><label for="uv_position_term"><?php esc_html_e('Position','uv-people'); ?></label></th>
        <td>
            <select name="uv_position_term" id="uv_position_term" class="uv-position-select" style="width:100%">
                <option value=""><?php esc_html_e('Velg','uv-people'); ?></option>
                <?php foreach($positions as $pos): ?>
                <option value="<?php echo esc_attr($pos->term_id); ?>" <?php selected($position, $pos->term_id); ?>><?php echo esc_html($pos->name); ?></option>
                <?php endforeach; ?>
            </select>
        </td></tr>
      <tr><th><label for="uv_bio_nb"><?php esc_html_e('Biography (Norwegian)','uv-people'); ?></label></th>
        <td><?php
            wp_editor(
                $bio_nb,
                'uv_bio_nb',
                [
                    'textarea_name' => 'uv_bio_nb',
                    'textarea_rows' => 5,
                    'media_buttons' => false,
                    'teeny'         => true,
                ]
            );
        ?></td></tr>
      <tr><th><label for="uv_bio_en"><?php esc_html_e('Biography (English)','uv-people'); ?></label></th>
        <td><?php
            wp_editor(
                $bio_en,
                'uv_bio_en',
                [
                    'textarea_name' => 'uv_bio_en',
                    'textarea_rows' => 5,
                    'media_buttons' => false,
                    'teeny'         => true,
                ]
            );
        ?></td></tr>
      <tr><th><label for="uv_quote_nb"><?php esc_html_e('Volunteer Quote (Norwegian)','uv-people'); ?></label></th>
        <td><textarea name="uv_quote_nb" id="uv_quote_nb" rows="4" class="large-text"><?php echo esc_textarea($quote_nb); ?></textarea></td></tr>
      <tr><th><label for="uv_quote_en"><?php esc_html_e('Volunteer Quote (English)','uv-people'); ?></label></th>
        <td><textarea name="uv_quote_en" id="uv_quote_en" rows="4" class="large-text"><?php echo esc_textarea($quote_en); ?></textarea></td></tr>
      <tr><th><?php esc_html_e('Avatar (Media Library)','uv-people'); ?></th>
        <td>
          <input type="hidden" id="uv_avatar_id" name="uv_avatar_id" value="<?php echo esc_attr($avatar_id); ?>">
          <button type="button" class="button" id="uv-avatar-upload"><?php esc_html_e('Velg bilde','uv-people'); ?></button>
          <button type="button" class="button" id="uv-avatar-remove"<?php echo $avatar_id ? '' : ' style="display:none;"'; ?>><?php esc_html_e('Remove','uv-people'); ?></button>
          <div id="uv-avatar-preview"><?php echo $avatar_id ? wp_get_attachment_image($avatar_id,'uv_avatar') : ''; ?></div>
          <p class="description"><?php esc_html_e('This replaces Gravatar and uses a local image.','uv-people'); ?></p>
        </td></tr>
    </table>
    <?php
}
add_action('show_user_profile','uv_people_profile_fields',5);
add_action('edit_user_profile','uv_people_profile_fields',5);

add_action('personal_options_update','uv_people_profile_save');
add_action('edit_user_profile_update','uv_people_profile_save');
function uv_people_profile_save($user_id){
    if(!current_user_can('edit_user', $user_id)) return;
    check_admin_referer('update-user_' . $user_id);
    if(isset($_POST['uv_bio_nb'])) update_user_meta($user_id, 'uv_bio_nb', wp_kses_post(wp_unslash($_POST['uv_bio_nb'])));
    if(isset($_POST['uv_bio_en'])) update_user_meta($user_id, 'uv_bio_en', wp_kses_post(wp_unslash($_POST['uv_bio_en'])));
    if(isset($_POST['uv_phone'])) update_user_meta($user_id, 'uv_phone', sanitize_text_field($_POST['uv_phone']));
    if(isset($_POST['uv_position_term'])) update_user_meta($user_id, 'uv_position_term', absint($_POST['uv_position_term']));
    if(isset($_POST['uv_quote_nb'])) update_user_meta($user_id, 'uv_quote_nb', sanitize_textarea_field($_POST['uv_quote_nb']));
    if(isset($_POST['uv_quote_en'])) update_user_meta($user_id, 'uv_quote_en', sanitize_textarea_field($_POST['uv_quote_en']));
    if(isset($_POST['uv_avatar_id'])) {
        $avatar_id = absint($_POST['uv_avatar_id']);
        if($avatar_id){
            update_user_meta($user_id, 'uv_avatar_id', $avatar_id);
        } else {
            delete_user_meta($user_id, 'uv_avatar_id');
        }
    }
    if(isset($_POST['uv_birthdate'])){
        $bd = sanitize_text_field($_POST['uv_birthdate']);
        if($bd){
            $dt = date_create($bd);
            if($dt){
                update_user_meta($user_id, 'uv_birthdate', $dt->format('Y-m-d'));
            }
        } else {
            delete_user_meta($user_id, 'uv_birthdate');
        }
    }
    update_user_meta($user_id, 'uv_show_phone', isset($_POST['uv_show_phone']) ? '1' : '0');
    $loc_ids = [];
    if(isset($_POST['uv_locations'])){
        $loc_ids = array_filter(array_map('intval', (array)$_POST['uv_locations']));
        update_user_meta($user_id, 'uv_location_terms', $loc_ids);
    } else {
        delete_user_meta($user_id, 'uv_location_terms');
    }
    if(isset($_POST['uv_primary_locations'])){
        $primary_raw = array_filter(array_map('intval', (array)$_POST['uv_primary_locations']));
        $primary_ids = array_values(array_intersect($primary_raw, $loc_ids));
        if(!empty($primary_ids)){
            update_user_meta($user_id, 'uv_primary_locations', $primary_ids);
        } else {
            delete_user_meta($user_id, 'uv_primary_locations');
        }
    } else {
        delete_user_meta($user_id, 'uv_primary_locations');
    }
}

// Helper: get user avatar URL by our field
function uv_people_get_avatar($user_id){
    $id   = get_user_meta($user_id,'uv_avatar_id',true);
    $name = get_the_author_meta('display_name', $user_id);
    $alt  = $name ? esc_attr($name) : '';
    if($id){
        $img = wp_get_attachment_image($id, 'uv_avatar', false, ['alt' => $alt, 'loading' => 'lazy']);
        return $img;
    }
    return get_avatar($user_id, 96, '', $alt, ['loading' => 'lazy']); // fallback
}

// Shortcode: front-end profile edit form
function uv_people_edit_profile_shortcode(){
    if(!is_user_logged_in()){
        return '<p>'.esc_html__('Du må være innlogget for å redigere profilen din.', 'uv-people').'</p>';
    }

    $user_id = get_current_user_id();
    $message = '';

    if('POST' === $_SERVER['REQUEST_METHOD'] && isset($_POST['uv_edit_profile_submit'])){
        if(isset($_POST['_wpnonce']) && wp_verify_nonce($_POST['_wpnonce'], 'update-user_' . $user_id)){
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/media.php';
            require_once ABSPATH . 'wp-admin/includes/image.php';

            if(!empty($_FILES['uv_avatar']['name'])){
                $attachment_id = media_handle_upload('uv_avatar', 0);
                if(!is_wp_error($attachment_id)){
                    $_POST['uv_avatar_id'] = $attachment_id;
                } else {
                    $message = '<div class="uv-edit-profile-message uv-error">'.esc_html__('Opplasting av avatar mislyktes.', 'uv-people').'</div>';
                }
            }

            uv_people_profile_save($user_id);
            if(!$message){
                $message = '<div class="uv-edit-profile-message uv-success">'.esc_html__('Profil oppdatert.', 'uv-people').'</div>';
            }
        } else {
            $message = '<div class="uv-edit-profile-message uv-error">'.esc_html__('Sikkerhetssjekk mislyktes.', 'uv-people').'</div>';
        }
    }

    $phone       = get_user_meta($user_id, 'uv_phone', true);
    $quote_nb    = get_user_meta($user_id, 'uv_quote_nb', true);
    $quote_en    = get_user_meta($user_id, 'uv_quote_en', true);
    $bio_nb      = get_user_meta($user_id, 'uv_bio_nb', true);
    $bio_en      = get_user_meta($user_id, 'uv_bio_en', true);
    $avatar_id   = get_user_meta($user_id, 'uv_avatar_id', true);
    $position    = get_user_meta($user_id, 'uv_position_term', true);
    $birthdate   = get_user_meta($user_id, 'uv_birthdate', true);
    $positions   = get_terms(['taxonomy' => 'uv_position', 'hide_empty' => false]);
    if(is_wp_error($positions)){
        $positions = [];
    }
    // Guard against missing uv_location taxonomy when uv-core is inactive or removed
    $locations = [];
    if (taxonomy_exists('uv_location')) {
        $locations = get_terms(['taxonomy' => 'uv_location', 'hide_empty' => false]);
        if (is_wp_error($locations)) {
            $locations = [];
        }
    }
    $assigned = get_user_meta($user_id, 'uv_location_terms', true);
    if(!is_array($assigned)) $assigned = [];
    $primary_locations = get_user_meta($user_id, 'uv_primary_locations', true);
    if(!is_array($primary_locations)) $primary_locations = [];

    ob_start();
    wp_enqueue_style('uv-people-edit-profile', plugin_dir_url(__FILE__) . 'assets/edit-profile.css', [], UV_PEOPLE_VERSION);
    wp_enqueue_editor();
    wp_enqueue_script('jquery');
    wp_add_inline_script('jquery', 'jQuery(function($){
        var $loc = $("#uv_locations");
        var $primary = $("#uv_primary_locations");
        if($loc.length && $primary.length){
            function sync(){
                var selected = $loc.val() || [];
                $primary.find("option").each(function(){
                    var val = $(this).val();
                    var allowed = selected.indexOf(val) !== -1;
                    $(this).prop("disabled", !allowed);
                    if(!allowed){
                        $(this).prop("selected", false);
                    }
                });
            }
            $loc.on("change", sync);
            sync();
        }
    });');

    if($message){
        echo $message;
    }
    ?>
    <form class="uv-edit-profile-form" method="post" enctype="multipart/form-data">
        <?php wp_nonce_field('update-user_' . $user_id); ?>
        <p class="uv-field">
            <label for="uv_phone"><?php esc_html_e('Phone', 'uv-people'); ?></label>
            <input type="tel" name="uv_phone" id="uv_phone" value="<?php echo esc_attr($phone); ?>">
        </p>
        <p class="uv-field">
            <label for="uv_birthdate"><?php esc_html_e('Birthdate', 'uv-people'); ?></label>
            <input type="date" name="uv_birthdate" id="uv_birthdate" value="<?php echo esc_attr($birthdate); ?>">
        </p>
        <p class="uv-field">
            <label for="uv_position_term"><?php esc_html_e('Position', 'uv-people'); ?></label>
            <select name="uv_position_term" id="uv_position_term">
                <option value=""><?php esc_html_e('Velg', 'uv-people'); ?></option>
                <?php foreach($positions as $pos): ?>
                    <option value="<?php echo esc_attr($pos->term_id); ?>" <?php selected($position, $pos->term_id); ?>><?php echo esc_html($pos->name); ?></option>
                <?php endforeach; ?>
            </select>
        </p>
        <?php if(!empty($locations)): ?>
        <p class="uv-field">
            <label for="uv_locations"><?php esc_html_e('Locations', 'uv-people'); ?></label>
            <input type="hidden" name="uv_locations[]" value="">
            <select name="uv_locations[]" id="uv_locations" multiple>
                <?php foreach($locations as $loc): ?>
                    <option value="<?php echo esc_attr($loc->term_id); ?>" <?php selected(in_array($loc->term_id, $assigned)); ?>><?php echo esc_html($loc->name); ?></option>
                <?php endforeach; ?>
            </select>
        </p>
        <p class="uv-field">
            <label for="uv_primary_locations"><?php esc_html_e('Primary Locations', 'uv-people'); ?></label>
            <input type="hidden" name="uv_primary_locations[]" value="">
            <select name="uv_primary_locations[]" id="uv_primary_locations" multiple>
                <?php foreach($locations as $loc): ?>
                    <option value="<?php echo esc_attr($loc->term_id); ?>" <?php selected(in_array($loc->term_id, $primary_locations)); ?>><?php echo esc_html($loc->name); ?></option>
                <?php endforeach; ?>
            </select>
        </p>
        <?php endif; ?>
        <p class="uv-field">
            <label for="uv_bio_nb"><?php esc_html_e('Biography (Norwegian)', 'uv-people'); ?></label>
            <?php
            wp_editor(
                $bio_nb,
                'uv_bio_nb',
                [
                    'textarea_name' => 'uv_bio_nb',
                    'textarea_rows' => 5,
                    'media_buttons' => false,
                    'teeny'         => true,
                ]
            );
            ?>
        </p>
        <p class="uv-field">
            <label for="uv_bio_en"><?php esc_html_e('Biography (English)', 'uv-people'); ?></label>
            <?php
            wp_editor(
                $bio_en,
                'uv_bio_en',
                [
                    'textarea_name' => 'uv_bio_en',
                    'textarea_rows' => 5,
                    'media_buttons' => false,
                    'teeny'         => true,
                ]
            );
            ?>
        </p>
        <p class="uv-field">
            <label for="uv_quote_nb"><?php esc_html_e('Quote (Norwegian)', 'uv-people'); ?></label>
            <textarea name="uv_quote_nb" id="uv_quote_nb" rows="4"><?php echo esc_textarea($quote_nb); ?></textarea>
        </p>
        <p class="uv-field">
            <label for="uv_quote_en"><?php esc_html_e('Quote (English)', 'uv-people'); ?></label>
            <textarea name="uv_quote_en" id="uv_quote_en" rows="4"><?php echo esc_textarea($quote_en); ?></textarea>
        </p>
        <p class="uv-field">
            <label for="uv_avatar"><?php esc_html_e('Avatar', 'uv-people'); ?></label>
            <?php if($avatar_id) echo wp_get_attachment_image($avatar_id, 'uv_avatar', false, ['class' => 'uv-current-avatar']); ?>
            <input type="file" name="uv_avatar" id="uv_avatar" accept="image/*">
        </p>
        <p>
            <button type="submit" name="uv_edit_profile_submit" class="uv-button"><?php esc_html_e('Save Profile', 'uv-people'); ?></button>
        </p>
    </form>
    <?php
    return ob_get_clean();
}
add_shortcode('uv_edit_profile', 'uv_people_edit_profile_shortcode');

function uv_people_migrate_birthdates_to_iso(){
    if(get_option('uv_people_birthdate_migrated')){
        return;
    }
    $users = get_users(['fields' => 'ID', 'meta_key' => 'uv_birthdate']);
    foreach($users as $uid){
        $raw = get_user_meta($uid, 'uv_birthdate', true);
        if($raw){
            $ts = strtotime($raw);
            if($ts){
                update_user_meta($uid, 'uv_birthdate', gmdate('Y-m-d', $ts));
            }
        }
    }
    update_option('uv_people_birthdate_migrated', 1);
}
add_action('init', 'uv_people_migrate_birthdates_to_iso');

// Shortcode: Team grid by location
function uv_people_team_grid($atts){
    wp_enqueue_style('uv-team-grid-style', plugin_dir_url(__FILE__) . 'blocks/team-grid/style.css', [], UV_PEOPLE_VERSION);
    $a = shortcode_atts([
        'location'          => '',
        'columns'           => 4,
        'highlight_primary' => 1,
        'per_page'          => 100,
        'page'              => 1,
        'show_nav'          => false,
        'show_age'          => false,
        'sortBy'            => 'default',
        'sort_by'           => 'default',
    ], $atts);
    $placeholder = function($msg){
        return (is_admin() || (defined('REST_REQUEST') && REST_REQUEST))
            ? '<div class="uv-block-placeholder">'.esc_html($msg).'</div>'
            : '';
    };
    if(!$a['location']) return $placeholder(__('Velg et sted.', 'uv-people'));
    // Guard against missing uv_location taxonomy when uv-core is inactive or removed
    if (!taxonomy_exists('uv_location')) {
        return $placeholder(__('Ingen steder tilgjengelig.', 'uv-people'));
    }
    $loc = sanitize_title($a['location']); // Shortcode expects a location slug
    $term = get_term_by('slug', $loc, 'uv_location'); // Look up the term to obtain its ID
    if(!$term) return $placeholder(__('Location not found.', 'uv-people'));

    $order_meta = get_term_meta($term->term_id, 'uv_member_order', true);
    $order_map = [];
    if (is_array($order_meta)) {
        foreach ($order_meta as $idx => $uid) {
            $order_map[intval($uid)] = $idx;
        }
    }

    $per_page = max(1, intval($a['per_page']));
    $page = isset($_GET['uv_page']) ? max(1, intval($_GET['uv_page'])) : max(1, intval($a['page']));
    $sort = isset($atts['sort_by']) ? $a['sort_by'] : $a['sortBy'];
    $sort = sanitize_key($sort);
    if (!in_array($sort, ['age', 'name'], true)) {
        $sort = 'default';
    }

    $cache_key = uv_people_get_team_grid_cache_key($term->term_id);
    $items = get_transient($cache_key);
    if ($items === false) {
        // Fetch users assigned to this location
        $users = get_users([
            'number'     => -1,
            'fields'     => ['ID'],
            'meta_query' => [
                'relation' => 'OR',
                [
                    'key'     => 'uv_location_terms',
                    'value'   => '"' . $term->term_id . '"',
                    'compare' => 'LIKE',
                ],
                [
                    'key'     => 'uv_primary_locations',
                    'value'   => '"' . $term->term_id . '"',
                    'compare' => 'LIKE',
                ],
            ],
        ]);
        $items = [];
        foreach ($users as $u) {
            $uid = $u->ID;
            $role_term = get_user_meta($uid, 'uv_position_term', true);
            $rank = 999;
            if ($role_term) {
                $rw = get_term_meta($role_term, 'uv_rank_weight', true);
                if ($rw !== '' && $rw !== false) {
                    $rank = intval($rw);
                }
            }
            $primary_ids = get_user_meta($uid, 'uv_primary_locations', true);
            if(!is_array($primary_ids)) $primary_ids = [];
            $items[] = [
                'user_id'   => $uid,
                'role_term' => $role_term,
                'primary'   => in_array($term->term_id, $primary_ids, true),
                'rank'      => $rank,
            ];
        }
        set_transient($cache_key, $items, uv_people_cache_ttl());
    }
    foreach ($items as &$it) {
        $uid = intval($it['user_id']);
        $it['order'] = $order_map[$uid] ?? PHP_INT_MAX;
        if ($sort === 'age') {
            $birthdate = get_user_meta($uid, 'uv_birthdate', true);
            $it['age'] = PHP_INT_MAX;
            if ($birthdate) {
                $bd = DateTime::createFromFormat('Y-m-d', $birthdate);
                if ($bd) {
                    $it['age'] = (new DateTime())->diff($bd)->y;
                }
            }
        }
    }
    unset($it);
    if(!$items){
        return $placeholder(__('Ingen teammedlemmer funnet.', 'uv-people'));
    }
    $cols = max(1, min(6, intval($a['columns'])));
    // sort priority based on selection
    usort($items, function($a, $b) use ($sort) {
        if ($sort === 'age') {
            if ($a['age'] !== $b['age']) {
                return $a['age'] < $b['age'] ? -1 : 1;
            }
            $an = get_the_author_meta('display_name', $a['user_id']);
            $bn = get_the_author_meta('display_name', $b['user_id']);
            return strcasecmp($an, $bn);
        }
        if ($sort === 'name') {
            $an = get_the_author_meta('display_name', $a['user_id']);
            $bn = get_the_author_meta('display_name', $b['user_id']);
            return strcasecmp($an, $bn);
        }
        if ($a['primary'] !== $b['primary']) return $a['primary'] ? -1 : 1;
        if ($a['order'] !== $b['order']) return $a['order'] < $b['order'] ? -1 : 1;
        if ($a['rank'] !== $b['rank']) return $a['rank'] < $b['rank'] ? -1 : 1;
        $an = get_the_author_meta('display_name', $a['user_id']);
        $bn = get_the_author_meta('display_name', $b['user_id']);
        return strcasecmp($an, $bn);
    });
    $lang = substr(get_locale(),0,2);

    $total_pages = (int) ceil(count($items) / $per_page);
    $page = min($page, $total_pages ? $total_pages : 1);
    $offset = ($page - 1) * $per_page;
    $items = array_slice($items, $offset, $per_page);

    ob_start();
    echo '<div class="uv-team-grid columns-'.$cols.'" role="list">';
    foreach($items as $it){
        $uid = intval($it['user_id']);
        $name = get_the_author_meta('display_name', $uid);
        $phone = get_user_meta($uid,'uv_phone',true);
        $email = get_the_author_meta('user_email', $uid);
        $classes = 'uv-person';
        if($a['highlight_primary'] && $it['primary']) $classes .= ' uv-primary-contact';
        // Link each card to custom team template
        $url = add_query_arg(
            [
                'team'        => 1,
                'author_name' => get_the_author_meta('user_nicename', $uid),
            ],
            home_url('/')
        );
        $label = sprintf(__('View profile for %s','uv-people'), $name);
        echo '<article class="'.esc_attr($classes).'" role="listitem">';
        echo '<a href="'.esc_url($url).'" aria-label="'.esc_attr($label).'">';
        echo '<div class="uv-avatar">'.uv_people_get_avatar($uid).'</div>';
        echo '<div class="uv-info">';
        echo '<h3 class="notranslate">'.esc_html($name).'</h3>';
        if($a['show_age']){
            $birthdate = get_user_meta($uid,'uv_birthdate',true);
            if($birthdate){
                $bd = DateTime::createFromFormat('Y-m-d',$birthdate);
                if($bd){
                    $age = (new DateTime())->diff($bd)->y;
                    $label = ($age >= 30) ? esc_html__('Voksen leder','uv-people') : esc_html__('Ung leder','uv-people');
                    echo '<span class="uv-age-pill">'.esc_html($label).'</span>';
                }
            }
        }
        $role = '';
        $role_term = $it['role_term'];
        if(!$role_term){
            $role_term = get_user_meta($uid,'uv_position_term',true);
        }
        if($role_term){
            $t = get_term($role_term, 'uv_position');
            if($t && !is_wp_error($t)){
                $role = $t->name;
            }
        }
        if(!$role){
            $role_nb = $it['role_nb'] ?: get_user_meta($uid,'uv_position_nb',true);
            $role_en = $it['role_en'] ?: get_user_meta($uid,'uv_position_en',true);
            $role = ($lang==='en') ? ($role_en ?: $role_nb) : ($role_nb ?: $role_en);
        }
        if($role) echo '<div class="uv-role notranslate">'.esc_html($role).'</div>';

        // choose quote by language
        $quote_nb = get_user_meta($uid,'uv_quote_nb',true);
        $quote_en = get_user_meta($uid,'uv_quote_en',true);
        $quote = ($lang==='en') ? ($quote_en ?: $quote_nb) : ($quote_nb ?: $quote_en);
        echo '</div>';
        echo '</a>';
        $bio_nb = get_user_meta($uid,'uv_bio_nb',true);
        $bio_en = get_user_meta($uid,'uv_bio_en',true);
        $bio = ($lang==='en') ? ($bio_en ?: $bio_nb) : ($bio_nb ?: $bio_en);
        if($bio) echo '<div class="uv-bio">'.wp_kses_post(wpautop($bio)).'</div>';
        // contact visibility
        $show_phone = get_user_meta($uid,'uv_show_phone',true)==='1';
        if(($phone && $show_phone) || $email){
            $email_label = ($lang==='en') ? __('Email:','uv-people') : __('E-post:','uv-people');
            $phone_label = ($lang==='en') ? __('Mobile:','uv-people') : __('Mobil:','uv-people');
            echo '<div class="uv-contact">';
            if($email) echo '<div class="uv-email"><span class="label">'.esc_html($email_label).'</span><a href="mailto:'.esc_attr($email).'">'.esc_html($email).'</a></div>';
            if($phone && $show_phone) echo '<div class="uv-mobile"><span class="label">'.esc_html($phone_label).'</span><a href="tel:'.esc_attr($phone).'">'.esc_html($phone).'</a></div>';
            echo '</div>';
        }
        if($quote) echo '<div class="uv-quote"><span class="uv-quote-icon">&ldquo;</span>'.esc_html($quote).'</div>';
        echo '</article>';
    }
    echo '</div>';
    if($a['show_nav'] && $total_pages > 1){
        $base_url = remove_query_arg('uv_page');
        $base     = esc_url(add_query_arg('uv_page', '%#%', $base_url));
        echo '<nav class="uv-pagination">'.paginate_links([
            'base'    => $base,
            'format'  => '',
            'current' => $page,
            'total'   => $total_pages,
        ]).'</nav>';
    }
    return ob_get_clean();
}
add_shortcode('uv_team','uv_people_team_grid');

function uv_people_all_team_grid($atts){
    wp_enqueue_style('uv-all-team-grid-style', plugin_dir_url(__FILE__) . 'blocks/all-team-grid/style.css', [], UV_PEOPLE_VERSION);
    $a = shortcode_atts([
        'columns'      => 4,
        'locations'    => [],
        'allLocations' => true,
        'per_page'     => 100,
        'page'         => 1,
        'show_nav'     => false,
        'showQuote'    => true,
        'showBio'      => false,
        'showEmail'    => false,
        'showAge'      => false,
        'sortBy'       => 'default',
        'sort_by'      => 'default',
    ], $atts);

    $location_ids = [];
    if( ! empty( $a['locations'] ) && empty( $a['allLocations'] ) ){
        $terms = get_terms([
            'taxonomy'   => 'uv_location',
            'slug'       => (array) $a['locations'],
            'fields'     => 'ids',
            'hide_empty' => false,
        ]);
        if( ! is_wp_error( $terms ) && $terms ){
            $location_ids = array_map( 'intval', $terms );
        }
    }

    $order_map = [];
    $order_locations = $location_ids;
    if (empty($order_locations)) {
        $all_terms = get_terms([
            'taxonomy'   => 'uv_location',
            'fields'     => 'ids',
            'hide_empty' => false,
        ]);
        if (!is_wp_error($all_terms) && $all_terms) {
            $order_locations = array_map('intval', $all_terms);
        }
    }
    foreach ($order_locations as $loc_id) {
        $order_meta = get_term_meta($loc_id, 'uv_member_order', true);
        if (is_array($order_meta)) {
            foreach ($order_meta as $idx => $uid) {
                $uid = intval($uid);
                if (!isset($order_map[$uid]) || $idx < $order_map[$uid]) {
                    $order_map[$uid] = $idx;
                }
            }
        }
    }

    $per_page = max(1, intval($a['per_page']));
    $page     = isset($_GET['uv_page']) ? max(1, intval($_GET['uv_page'])) : max(1, intval($a['page']));
    $sort     = isset($atts['sort_by']) ? $a['sort_by'] : $a['sortBy'];
    $sort     = sanitize_key($sort);
    if (!in_array($sort, ['age', 'name'], true)) {
        $sort = 'default';
    }

    // Build meta query to fetch users belonging to the requested locations
    if ($location_ids) {
        $meta_query = ['relation' => 'OR'];
        foreach ($location_ids as $loc_id) {
            $meta_query[] = [
                'key'     => 'uv_location_terms',
                'value'   => 'i:' . $loc_id . ';',
                'compare' => 'LIKE',
            ];
            $meta_query[] = [
                'key'     => 'uv_primary_locations',
                'value'   => 'i:' . $loc_id . ';',
                'compare' => 'LIKE',
            ];
        }
    } else {
        $meta_query = [
            'relation' => 'OR',
            [ 'key' => 'uv_location_terms',    'compare' => 'EXISTS' ],
            [ 'key' => 'uv_primary_locations', 'compare' => 'EXISTS' ],
        ];
    }

    $cache_key = uv_people_get_all_team_grid_cache_key($location_ids);
    $user_ids = get_transient($cache_key);
    if ($user_ids === false) {
        $users = get_users([
            'number'     => -1,
            'fields'     => ['ID'],
            'meta_query' => $meta_query,
        ]);
        $user_ids = wp_list_pluck($users, 'ID');
        set_transient($cache_key, $user_ids, uv_people_cache_ttl());
    }

    if(!$user_ids){
        return (is_admin() || (defined('REST_REQUEST') && REST_REQUEST))
            ? '<div class="uv-block-placeholder">'.esc_html__('Ingen teammedlemmer funnet.', 'uv-people').'</div>'
            : '';
    }

    $offset   = ($page - 1) * $per_page;

    $sorted = [];
    foreach ($user_ids as $uid) {
        $role_term = get_user_meta($uid, 'uv_position_term', true);
        $rank = 999;
        if ($role_term) {
            $rw = get_term_meta($role_term, 'uv_rank_weight', true);
            if ($rw !== '' && $rw !== false) {
                $rank = intval($rw);
            }
        }
        $primary_ids = get_user_meta($uid, 'uv_primary_locations', true);
        if(!is_array($primary_ids)) $primary_ids = [];
        $primary = $location_ids ? (bool) array_intersect($primary_ids, $location_ids) : !empty($primary_ids);
        $name = get_the_author_meta('display_name', $uid);
        $age = PHP_INT_MAX;
        if ($sort === 'age') {
            $birthdate = get_user_meta($uid, 'uv_birthdate', true);
            if ($birthdate) {
                $bd = DateTime::createFromFormat('Y-m-d', $birthdate);
                if ($bd) {
                    $age = (new DateTime())->diff($bd)->y;
                }
            }
        }
        $sorted[] = [
            'ID'      => $uid,
            'rank'    => $rank,
            'name'    => $name,
            'primary' => $primary,
            'order'   => $order_map[$uid] ?? PHP_INT_MAX,
            'age'     => $age,
        ];
    }
    usort($sorted, function($a, $b) use ($sort) {
        if ($sort === 'age') {
            if ($a['age'] !== $b['age']) return $a['age'] < $b['age'] ? -1 : 1;
            return strcasecmp($a['name'], $b['name']);
        }
        if ($sort === 'name') {
            return strcasecmp($a['name'], $b['name']);
        }
        if($a['primary'] !== $b['primary']) return $a['primary']? -1 : 1;
        if($a['order'] !== $b['order']) return $a['order'] < $b['order'] ? -1 : 1;
        if($a['rank'] !== $b['rank']) return $a['rank'] < $b['rank'] ? -1 : 1;
        return strcasecmp($a['name'], $b['name']);
    });

    $total_users = count($sorted);
    $paged_items = array_slice($sorted, $offset, $per_page);
    $paged_ids   = wp_list_pluck($paged_items, 'ID');

    // Retrieve only the users needed for this page
    $users = get_users([
        'include' => $paged_ids,
        'number'  => $per_page,
        'fields'  => ['ID', 'display_name', 'user_email'],
        'orderby' => 'include',
    ]);
    $user_map = [];
    foreach ($users as $u) {
        $user_map[$u->ID] = $u;
    }

    $items = [];
    foreach ($paged_items as $it) {
        $uid = $it['ID'];
        if (isset($user_map[$uid])) {
            $items[] = [
                'user'    => $user_map[$uid],
                'rank'    => $it['rank'],
                'primary' => !empty($it['primary']),
                'order'   => $it['order'],
            ];
        }
    }
    $total_pages = ceil($total_users / $per_page);

    $cols = max(1, min(6, intval($a['columns'])));
    $lang = substr(get_locale(),0,2);
    ob_start();
    echo '<div class="uv-team-grid columns-'.$cols.'" role="list">';
    foreach($items as $it){
        $user = $it['user'];
        $uid = $user->ID;
        $name = $user->display_name;
        $phone = get_user_meta($uid,'uv_phone',true);
        $email = $user->user_email;
        $classes = 'uv-person';
        if (empty($a['allLocations']) && !empty($it['primary'])) {
            $classes .= ' uv-primary-contact';
        }
        $url = add_query_arg(
            [
                'team'        => 1,
                'author_name' => get_the_author_meta('user_nicename', $uid),
            ],
            home_url('/')
        );
        $label = sprintf(__('View profile for %s','uv-people'), $name);
        echo '<article class="'.esc_attr($classes).'" role="listitem">';
        echo '<a href="'.esc_url($url).'" aria-label="'.esc_attr($label).'">';
        echo '<div class="uv-avatar">'.uv_people_get_avatar($uid).'</div>';
        echo '<div class="uv-info">';
        echo '<h3 class="notranslate">'.esc_html($name).'</h3>';
        $role = '';
        $role_term = get_user_meta($uid,'uv_position_term',true);
        if($role_term){
            $t = get_term($role_term, 'uv_position');
            if($t && !is_wp_error($t)){
                $role = $t->name;
            }
        }
        if(!$role){
            $role_nb = get_user_meta($uid,'uv_position_nb',true);
            $role_en = get_user_meta($uid,'uv_position_en',true);
            $role = ($lang==='en') ? ($role_en ?: $role_nb) : ($role_nb ?: $role_en);
        }
        if($role) echo '<div class="uv-role notranslate">'.esc_html($role).'</div>';
        if($a['showAge']){
            $birthdate = get_user_meta($uid,'uv_birthdate',true);
            if($birthdate){
                $bd = DateTime::createFromFormat('Y-m-d', $birthdate);
                if($bd){
                    $age = (new DateTime())->diff($bd)->y;
                    $label = ($age >= 30) ? esc_html__('Voksen leder','uv-people') : esc_html__('Ung leder','uv-people');
                    echo '<span class="uv-age-pill">'.esc_html($label).'</span>';
                }
            }
        }
        echo '</div>';
        echo '</a>';
        if($a['showBio']){
            $bio_nb = get_user_meta($uid,'uv_bio_nb',true);
            $bio_en = get_user_meta($uid,'uv_bio_en',true);
            $bio = ($lang==='en') ? ($bio_en ?: $bio_nb) : ($bio_nb ?: $bio_en);
            if($bio) echo '<div class="uv-bio">'.wp_kses_post(wpautop($bio)).'</div>';
        }
        $show_phone = get_user_meta($uid,'uv_show_phone',true)==='1';
        if(($phone && $show_phone) || ($a['showEmail'] && $email)){
            $email_label = ($lang==='en') ? __('Email:','uv-people') : __('E-post:','uv-people');
            $phone_label = ($lang==='en') ? __('Mobile:','uv-people') : __('Mobil:','uv-people');
            echo '<div class="uv-contact">';
            if($a['showEmail'] && $email) echo '<div class="uv-email"><span class="label">'.esc_html($email_label).'</span><a href="mailto:'.esc_attr($email).'">'.esc_html($email).'</a></div>';
            if($phone && $show_phone) echo '<div class="uv-mobile"><span class="label">'.esc_html($phone_label).'</span><a href="tel:'.esc_attr($phone).'">'.esc_html($phone).'</a></div>';
            echo '</div>';
        }
        $quote_nb = get_user_meta($uid,'uv_quote_nb',true);
        $quote_en = get_user_meta($uid,'uv_quote_en',true);
        $quote = ($lang==='en') ? ($quote_en ?: $quote_nb) : ($quote_nb ?: $quote_en);
        if ($a['showQuote'] && $quote) echo '<div class="uv-quote"><span class="uv-quote-icon">&ldquo;</span>'.esc_html($quote).'</div>';
        echo '</article>';
    }
    echo '</div>';
    if($a['show_nav'] && $total_pages > 1){
        $base_url = remove_query_arg('uv_page');
        $base     = esc_url(add_query_arg('uv_page', '%#%', $base_url));
        echo '<nav class="uv-pagination">'.paginate_links([
            'base'    => $base,
            'format'  => '',
            'current' => $page,
            'total'   => $total_pages,
        ]).'</nav>';
    }
    return ob_get_clean();
}

function uv_people_invalidate_team_cache(){
    uv_people_clear_team_caches();
}

function uv_people_invalidate_all_team_cache(){
    uv_people_clear_team_caches(['uv_people_all_team_grid_']);
}

$uv_people_maybe_invalidate_location_meta = function($meta_id, $object_id, $meta_key){
    $location_meta_keys = ['uv_location_terms', 'uv_primary_locations'];

    if (in_array($meta_key, $location_meta_keys, true)) {
        uv_people_invalidate_all_team_cache();
    }
};

add_action('profile_update', 'uv_people_invalidate_all_team_cache');
add_action('added_user_meta', $uv_people_maybe_invalidate_location_meta, 10, 3);
add_action('updated_user_meta', $uv_people_maybe_invalidate_location_meta, 10, 3);
add_action('deleted_user_meta', $uv_people_maybe_invalidate_location_meta, 10, 3);
add_action('edited_uv_location', 'uv_people_invalidate_all_team_cache');
add_action('added_user_meta', 'uv_people_invalidate_team_cache');
add_action('updated_user_meta', 'uv_people_invalidate_team_cache');
add_action('deleted_user_meta', 'uv_people_invalidate_team_cache');

function uv_people_render_clear_cache_page(){
    if (!current_user_can('manage_options')) {
        return;
    }

    $cleared = isset($_GET['uv_people_cache_cleared']);
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Clear Team Cache', 'uv-people'); ?></h1>
        <?php if ($cleared): ?>
            <div class="notice notice-success is-dismissible">
                <p><?php esc_html_e('Team caches cleared.', 'uv-people'); ?></p>
            </div>
        <?php endif; ?>
        <p><?php esc_html_e('Use this action after bulk updates to ensure the team grids reflect the latest data.', 'uv-people'); ?></p>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <?php wp_nonce_field('uv_people_clear_team_cache'); ?>
            <input type="hidden" name="action" value="uv_people_clear_team_cache">
            <?php submit_button(__('Clear team cache', 'uv-people'), 'primary'); ?>
        </form>
    </div>
    <?php
}

function uv_people_handle_clear_team_cache_action(){
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have permission to clear the cache.', 'uv-people'));
    }

    check_admin_referer('uv_people_clear_team_cache');

    uv_people_invalidate_team_cache();

    $redirect = wp_get_referer();
    if (!$redirect) {
        $redirect = admin_url('tools.php?page=uv-people-clear-cache');
    }

    wp_safe_redirect(add_query_arg('uv_people_cache_cleared', '1', $redirect));
    exit;
}

add_action('admin_menu', function(){
    add_management_page(
        __('Clear Team Cache', 'uv-people'),
        __('Clear Team Cache', 'uv-people'),
        'manage_options',
        'uv-people-clear-cache',
        'uv_people_render_clear_cache_page'
    );
});
add_action('admin_post_uv_people_clear_team_cache', 'uv_people_handle_clear_team_cache_action');

$uv_people_invalidate_term_meta = function($mid, $term_id, $meta_key){
    if ($meta_key === 'uv_rank_weight') {
        uv_people_invalidate_team_cache();
    }
};
add_action('added_term_meta', $uv_people_invalidate_term_meta, 10, 3);
add_action('updated_term_meta', $uv_people_invalidate_term_meta, 10, 3);
add_action('deleted_term_meta', $uv_people_invalidate_term_meta, 10, 3);
add_action('created_uv_position', 'uv_people_invalidate_team_cache');
add_action('edited_uv_position', 'uv_people_invalidate_team_cache');
add_action('delete_uv_position', 'uv_people_invalidate_team_cache');

// Block registration
add_action('init', function(){
    register_block_type(__DIR__ . '/blocks/team-grid', [
        'render_callback' => 'uv_people_team_grid'
    ]);
    wp_set_script_translations( 'uv-team-grid-editor-script', 'uv-people', plugin_dir_path(__FILE__) . 'languages' );
    wp_set_script_translations( 'uv-all-team-grid-editor-script', 'uv-people', plugin_dir_path(__FILE__) . 'languages' );
    register_block_type(__DIR__ . '/blocks/all-team-grid', [
        'render_callback' => 'uv_people_all_team_grid',
        'attributes'      => [
            'columns' => [
                'type'    => 'number',
                'default' => 4,
            ],
            'locations' => [
                'type'    => 'array',
                'items'   => [ 'type' => 'string' ],
                'default' => [],
            ],
            'allLocations' => [
                'type'    => 'boolean',
                'default' => true,
            ],
            'per_page' => [
                'type'    => 'number',
                'default' => 100,
            ],
            'page' => [
                'type'    => 'number',
                'default' => 1,
            ],
            'show_nav' => [
                'type'    => 'boolean',
                'default' => false,
            ],
            'showQuote' => [
                'type'    => 'boolean',
                'default' => true,
            ],
            'showBio' => [
                'type'    => 'boolean',
                'default' => false,
            ],
            'showEmail' => [
                'type'    => 'boolean',
                'default' => false,
            ],
            'showAge' => [
                'type'    => 'boolean',
                'default' => false,
            ],
            'sortBy' => [
                'type'    => 'string',
                'default' => 'default',
            ],
        ],
    ]);
    wp_set_script_translations( 'uv-team-grid-editor-script', 'uv-people', plugin_dir_path(__FILE__) . 'languages' );
    wp_set_script_translations( 'uv-all-team-grid-editor-script', 'uv-people', plugin_dir_path(__FILE__) . 'languages' );
});

// Preserve team query parameter when redirecting to pretty author URLs
add_filter('redirect_canonical', function($redirect_url, $requested_url) {
    if (isset($_GET['team']) && $redirect_url) {
        $redirect_url = add_query_arg('team', absint($_GET['team']), $redirect_url);
    }
    return $redirect_url;
}, 10, 2);

// Dashboard widget for team guide links
add_action('wp_dashboard_setup', function(){
    if (current_user_can('edit_posts')) {
        wp_add_dashboard_widget('uv_team_guide', esc_html__('Teamguide','uv-people'), function(){
            echo '<p>'.esc_html__('Hurtiglenker for redaktører:', 'uv-people').'</p>';
            echo '<ul>
                <li><a href="edit-tags.php?taxonomy=uv_location">'.esc_html__('Administrer steder','uv-people').'</a></li>
                <li><a href="edit.php?post_type=uv_activity">'.esc_html__('Aktiviteter','uv-people').'</a></li>
                <li><a href="edit.php?post_type=uv_partner">'.esc_html__('Partnere','uv-people').'</a></li>
                <li><a href="edit.php">'.esc_html__('Nyhetsinnlegg','uv-people').'</a></li>
            </ul>';
            echo '<p>'.esc_html__('Legg til egne opplæringsvideoer her (rediger uv-people-plugin).','uv-people').'</p>';
        });
    } else {
        remove_meta_box('uv_team_guide', 'dashboard', 'normal');
    }
});

// Team members list with primary toggle and ordering controls on uv_location term edit screen
add_action('uv_location_edit_form_fields', function($term){
    $term_id = $term->term_id;
    $users = get_users([
        'number'     => -1,
        'fields'     => ['ID', 'display_name'],
        'meta_query' => [
            [
                'key'     => 'uv_location_terms',
                'value'   => '"' . $term_id . '"',
                'compare' => 'LIKE',
            ],
        ],
    ]);
    $members = [];
    foreach ($users as $u) {
        $primary = get_user_meta($u->ID, 'uv_primary_locations', true);
        if(!is_array($primary)) $primary = [];
        $members[$u->ID] = [
            'name'    => $u->display_name,
            'primary' => in_array($term_id, $primary, true),
        ];
    }
    $order = get_term_meta($term_id, 'uv_member_order', true);
    $ordered = [];
    if (is_array($order)) {
        foreach ($order as $uid) {
            $uid = intval($uid);
            if (isset($members[$uid])) {
                $ordered[$uid] = $members[$uid];
                unset($members[$uid]);
            }
        }
    }
    foreach ($members as $uid => $data) {
        $ordered[$uid] = $data;
    }
    wp_nonce_field('uv_location_members', 'uv_location_members_nonce');
    echo '<tr class="form-field"><th scope="row"><label>'.esc_html__('Teammedlemmer','uv-people').'</label></th><td>';
    if ($ordered) {
        echo '<ul id="uv-member-sortable">';
        foreach ($ordered as $uid => $data) {
            $checked = $data['primary'] ? ' checked' : '';
            echo '<li class="uv-member-item" data-uid="'.esc_attr($uid).'">';
            echo '<span class="uv-handle dashicons dashicons-move" style="cursor:move;margin-right:8px;"></span>'.esc_html($data['name']);
            echo ' <label style="margin-left:10px;"><input type="checkbox" name="uv_primary_team[]" value="'.esc_attr($uid).'"'.$checked.'> '.esc_html__('Primær','uv-people').'</label>';
            echo '<input type="hidden" name="uv_member_order[]" value="'.esc_attr($uid).'">';
            echo '</li>';
        }
        echo '</ul>';
        echo '<p class="description">'.esc_html__('Dra for å endre rekkefølgen på teammedlemmer.','uv-people').'</p>';
    } else {
        echo '<p>'.esc_html__('Ingen teammedlemmer tildelt.','uv-people').'</p>';
    }
    echo '</td></tr>';
});

function uv_people_save_location_members($term_id){
    if (!isset($_POST['uv_location_members_nonce']) || !wp_verify_nonce($_POST['uv_location_members_nonce'], 'uv_location_members')) {
        return;
    }
    if (!current_user_can('manage_categories')) {
        return;
    }
    $order_ids = isset($_POST['uv_member_order']) ? array_values(array_map('intval', (array)$_POST['uv_member_order'])) : [];
    update_term_meta($term_id, 'uv_member_order', $order_ids);
    $primary_ids = isset($_POST['uv_primary_team']) ? array_filter(array_map('intval', (array)$_POST['uv_primary_team'])) : [];
    update_term_meta($term_id, 'uv_primary_team', $primary_ids);
    $users = get_users([
        'number'     => -1,
        'fields'     => ['ID'],
        'meta_query' => [
            [
                'key'     => 'uv_location_terms',
                'value'   => '"' . $term_id . '"',
                'compare' => 'LIKE',
            ],
        ],
    ]);
    foreach ($users as $u) {
        $uid = $u->ID;
        $primary_locs = get_user_meta($uid, 'uv_primary_locations', true);
        if(!is_array($primary_locs)) $primary_locs = [];
        $has = in_array($term_id, $primary_locs, true);
        $should = in_array($uid, $primary_ids, true);
        if ($should && !$has) {
            $primary_locs[] = $term_id;
            update_user_meta($uid, 'uv_primary_locations', $primary_locs);
        } elseif (!$should && $has) {
            $primary_locs = array_diff($primary_locs, [$term_id]);
            update_user_meta($uid, 'uv_primary_locations', array_values($primary_locs));
        }
    }
}
add_action('edited_uv_location', 'uv_people_save_location_members');
add_action('created_uv_location', 'uv_people_save_location_members');

// Tidy admin for non-admins (optional, minimal)
add_action('admin_menu', function(){
    if(!current_user_can('manage_options')){
        remove_menu_page('tools.php');
        remove_menu_page('plugins.php');
        remove_menu_page('themes.php');
    }
}, 999);

// WP-CLI command to migrate legacy meta fields
if (defined('WP_CLI') && WP_CLI) {
    WP_CLI::add_command('uv-people migrate-legacy-meta', function () {
        $migrated = 0;

        // Migrate user quotes
        $users = get_users([
            'meta_key' => 'uv_quote',
            'number'   => -1,
            'fields'   => 'ID',
        ]);
        foreach ($users as $uid) {
            $legacy = get_user_meta($uid, 'uv_quote', true);
            if ($legacy) {
                if (!get_user_meta($uid, 'uv_quote_nb', true)) {
                    update_user_meta($uid, 'uv_quote_nb', $legacy);
                }
                if (!get_user_meta($uid, 'uv_quote_en', true)) {
                    update_user_meta($uid, 'uv_quote_en', $legacy);
                }
                delete_user_meta($uid, 'uv_quote');
                $migrated++;
            }
        }

        WP_CLI::success(sprintf('Migrated %d legacy meta entries.', $migrated));
    });

    WP_CLI::add_command('uv-people migrate-rank-weight', function(){
        $users = get_users([
            'meta_key' => 'uv_rank_number',
            'number'   => -1,
            'fields'   => 'ID',
        ]);
        $migrated = 0;
        foreach ($users as $uid) {
            $rank = get_user_meta($uid, 'uv_rank_number', true);
            $term_id = get_user_meta($uid, 'uv_position_term', true);
            if ($term_id && $rank !== '') {
                update_term_meta($term_id, 'uv_rank_weight', intval($rank));
                $migrated++;
            }
            delete_user_meta($uid, 'uv_rank_number');
        }
        WP_CLI::success(sprintf('Migrated %d rank numbers to term meta.', $migrated));
    });
}
