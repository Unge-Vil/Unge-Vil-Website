<?php
/**
 * Plugin Name: UV People
 * Description: Extends WordPress Users with public fields, media-library avatars, per-location assignments, and a Team grid shortcode.
 * Version: 0.6.2
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: Unge Vil
 * Author URI: https://www.ungevil.no/
 * Text Domain: uv-people
 * Update URI: https://github.com/Unge-Vil/Unge-Vil-Website/plugins/uv-people
 */
if (!defined('ABSPATH')) exit;

if (!defined('UV_PEOPLE_VERSION')) {
    define('UV_PEOPLE_VERSION', '0.6.2');
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

// Load textdomain
add_action('plugins_loaded', function(){
    load_plugin_textdomain('uv-people', false, dirname(plugin_basename(__FILE__)) . '/languages');
});

// Admin assets and localizations
add_action('admin_enqueue_scripts', function($hook){
    $screen = get_current_screen();
    $is_user_page = in_array($hook, ['profile.php', 'user-edit.php'], true);
    $is_control_panel = ('toplevel_page_uv-control-panel' === $hook);
    $is_assignment_cpt = $screen && 'uv_team_assignment' === $screen->post_type && in_array($hook, ['post.php', 'post-new.php'], true);
    $is_location_term = $screen && 'uv_location' === $screen->taxonomy && 'term' === $screen->base;

    if ($is_user_page || $is_control_panel || $is_assignment_cpt || $is_location_term) {
        if ($is_user_page) {
            wp_enqueue_media();
        }
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
            'selectAvatar' => __('Select Avatar', 'uv-people'),
        ]);
    }
});

// CPT: uv_team_assignment (user ↔ location w/ role & order)
add_action('init', function(){
    register_post_type('uv_team_assignment', [
        'label' => __('Team Assignments', 'uv-people'),
        'public' => false,
        'show_ui' => true,
        'supports' => ['title','author'],
        'menu_icon' => 'dashicons-groups'
    ]);
});

// Taxonomy: uv_position
add_action('init', function(){
    register_taxonomy('uv_position', ['uv_team_assignment'], [
        'label'        => __('Positions', 'uv-people'),
        'public'       => false,
        'show_ui'      => true,
        'hierarchical' => false,
        'show_in_rest' => true,
        'meta_box_cb'  => false,
    ]);
});

// Meta boxes for uv_team_assignment
add_action('add_meta_boxes_uv_team_assignment', function(){
    add_meta_box('uv_ta_fields', __('Assignment', 'uv-people'), function($post){
        $user_id = get_post_meta($post->ID, 'uv_user_id', true);
        if (!$user_id && $post->post_author) {
            $user_id = $post->post_author;
        }
        $user_ids = $user_id ? [$user_id] : [];
        $loc_id   = get_post_meta($post->ID, 'uv_location_id', true);
        $role_term = get_post_meta($post->ID, 'uv_role_term', true);
        $primary = get_post_meta($post->ID, 'uv_is_primary', true);
        $order   = get_post_meta($post->ID, 'uv_order_weight', true);
        // Guard against missing uv_location taxonomy when uv-core is inactive or removed
        $locations = [];
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
        ?>
        <?php wp_nonce_field('uv_ta_save', 'uv_ta_nonce'); ?>
        <p><label><?php esc_html_e('Users','uv-people'); ?></label>
        <?php
        $dropdown = wp_dropdown_users([
            'name'             => 'uv_user_ids[]',
            'id'               => 'uv_user_ids',
            'selected'         => $user_ids,
            'include_selected' => true,
            'multi'            => true,
            'show'             => 'display_name',
            'number'           => 50,
            'class'            => 'uv-user-select',
            'echo'             => false,
        ]);
        echo str_replace('<select', '<select style="width:100%;height:8em;"', $dropdown);
        ?>
        </p>
        <p><label><?php esc_html_e('Location','uv-people'); ?></label>
        <select name="uv_location_id" class="uv-location-select" style="width:100%">
            <option value=""><?php esc_html_e('Select','uv-people'); ?></option>
            <?php foreach($locations as $t): ?>
            <option value="<?php echo esc_attr($t->term_id); ?>" <?php selected($loc_id, $t->term_id); ?>><?php echo esc_html($t->name); ?></option>
            <?php endforeach; ?>
        </select></p>
        <p><label><?php esc_html_e('Position','uv-people'); ?></label>
        <select name="uv_role_term" class="uv-position-select" style="width:100%">
            <option value=""><?php esc_html_e('Select','uv-people'); ?></option>
            <?php foreach($positions as $p): ?>
            <option value="<?php echo esc_attr($p->term_id); ?>" <?php selected($role_term, $p->term_id); ?>><?php echo esc_html($p->name); ?></option>
            <?php endforeach; ?>
        </select></p>
        <p><label><input type="checkbox" name="uv_is_primary" value="1" <?php checked($primary, '1'); ?>> <?php esc_html_e('Primary contact','uv-people'); ?></label></p>
        <p><label><?php esc_html_e('Order weight (lower = earlier)','uv-people'); ?></label>
        <input type="number" name="uv_order_weight" value="<?php echo esc_attr($order?:'10'); ?>" style="width:100%"></p>
        <?php
    }, 'uv_team_assignment', 'normal');
});

function uv_save_team_assignment($post_id){
    if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if(!isset($_POST['uv_ta_nonce']) || !wp_verify_nonce($_POST['uv_ta_nonce'], 'uv_ta_save')) return;
    if(!current_user_can('edit_post', $post_id)) return;

    remove_action('save_post_uv_team_assignment', 'uv_save_team_assignment');

    $user_ids  = isset($_POST['uv_user_ids']) ? array_filter(array_map('absint', (array)$_POST['uv_user_ids'])) : [];
    $loc_id    = isset($_POST['uv_location_id']) ? absint($_POST['uv_location_id']) : 0;
    $role_term = isset($_POST['uv_role_term']) ? absint($_POST['uv_role_term']) : 0;
    $order     = isset($_POST['uv_order_weight']) ? absint($_POST['uv_order_weight']) : '';
    $primary   = isset($_POST['uv_is_primary']) ? '1' : '0';

    if($loc_id) update_post_meta($post_id, 'uv_location_id', $loc_id);
    if($role_term) update_post_meta($post_id, 'uv_role_term', $role_term);
    if($order !== '') update_post_meta($post_id, 'uv_order_weight', (int) $order);
    update_post_meta($post_id, 'uv_is_primary', $primary);

    if($user_ids){
        $first = array_shift($user_ids);
        update_post_meta($post_id, 'uv_user_id', $first);

        $term = get_term($loc_id, 'uv_location');

        wp_update_post([
            'ID'         => $post_id,
            'post_author'=> $first,
            'post_title' => get_the_author_meta('display_name', $first) . ' - ' . ( $term ? $term->name : '' ),
        ]);

        foreach($user_ids as $uid){
            $title = get_the_author_meta('display_name', $uid) . ' - ' . ($term ? $term->name : '');
            $new_id = wp_insert_post([
                'post_type'   => 'uv_team_assignment',
                'post_status' => 'publish',
                'post_title'  => $title,
                'post_author' => $uid,
            ]);
            if($new_id){
                update_post_meta($new_id, 'uv_user_id', $uid);
                if($loc_id) update_post_meta($new_id, 'uv_location_id', $loc_id);
                if($role_term) update_post_meta($new_id, 'uv_role_term', $role_term);
                update_post_meta($new_id, 'uv_is_primary', $primary);
                if($order !== '') update_post_meta($new_id, 'uv_order_weight', (int) $order);
            }
        }
    }

    add_action('save_post_uv_team_assignment', 'uv_save_team_assignment');
}
add_action('save_post_uv_team_assignment', 'uv_save_team_assignment');

// User profile fields (phone, public email, quote, socials, avatar attachment)
function uv_people_profile_fields($user){
    $phone       = get_user_meta($user->ID, 'uv_phone', true);
    $position    = get_user_meta($user->ID, 'uv_position_term', true);
    $quote_nb    = get_user_meta($user->ID, 'uv_quote_nb', true);
    $quote_en    = get_user_meta($user->ID, 'uv_quote_en', true);
    $show_phone = get_user_meta($user->ID, 'uv_show_phone', true) === '1';
    $avatar_id  = get_user_meta($user->ID, 'uv_avatar_id', true);
    $rank_number = get_user_meta($user->ID, 'uv_rank_number', true);
    if($rank_number === '') {
        $rank_number = 999;
    } else {
        $rank_number = intval($rank_number);
    }
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
      <tr><th><label for="uv_phone"><?php esc_html_e('Phone (public optional)','uv-people'); ?></label></th>
        <td>
            <input type="text" name="uv_phone" id="uv_phone" value="<?php echo esc_attr($phone); ?>" class="regular-text">
            <br><label><input type="checkbox" name="uv_show_phone" value="1" <?php checked($show_phone); ?>> <?php esc_html_e('Show on profile','uv-people'); ?></label>
        </td></tr>
      <tr><th><label for="uv_rank_number"><?php esc_html_e('Rank Number','uv-people'); ?></label></th>
        <td><input type="number" name="uv_rank_number" id="uv_rank_number" value="<?php echo esc_attr($rank_number); ?>" class="small-text"></td></tr>
      <tr><th><label for="uv_position_term"><?php esc_html_e('Position','uv-people'); ?></label></th>
        <td>
            <select name="uv_position_term" id="uv_position_term" class="uv-position-select" style="width:100%">
                <option value=""><?php esc_html_e('Select','uv-people'); ?></option>
                <?php foreach($positions as $pos): ?>
                <option value="<?php echo esc_attr($pos->term_id); ?>" <?php selected($position, $pos->term_id); ?>><?php echo esc_html($pos->name); ?></option>
                <?php endforeach; ?>
            </select>
        </td></tr>
      <tr><th><label for="uv_quote_nb"><?php esc_html_e('Volunteer Quote (Norwegian)','uv-people'); ?></label></th>
        <td><textarea name="uv_quote_nb" id="uv_quote_nb" rows="4" class="large-text"><?php echo esc_textarea($quote_nb); ?></textarea></td></tr>
      <tr><th><label for="uv_quote_en"><?php esc_html_e('Volunteer Quote (English)','uv-people'); ?></label></th>
        <td><textarea name="uv_quote_en" id="uv_quote_en" rows="4" class="large-text"><?php echo esc_textarea($quote_en); ?></textarea></td></tr>
      <tr><th><?php esc_html_e('Avatar (Media Library)','uv-people'); ?></th>
        <td>
          <input type="hidden" id="uv_avatar_id" name="uv_avatar_id" value="<?php echo esc_attr($avatar_id); ?>">
          <button type="button" class="button" id="uv-avatar-upload"><?php esc_html_e('Select Image','uv-people'); ?></button>
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
    if(isset($_POST['uv_phone'])) update_user_meta($user_id, 'uv_phone', sanitize_text_field($_POST['uv_phone']));
    if(isset($_POST['uv_position_term'])) update_user_meta($user_id, 'uv_position_term', absint($_POST['uv_position_term']));
    if(isset($_POST['uv_quote_nb'])) update_user_meta($user_id, 'uv_quote_nb', sanitize_textarea_field($_POST['uv_quote_nb']));
    if(isset($_POST['uv_quote_en'])) update_user_meta($user_id, 'uv_quote_en', sanitize_textarea_field($_POST['uv_quote_en']));
    if(isset($_POST['uv_avatar_id'])) update_user_meta($user_id, 'uv_avatar_id', absint($_POST['uv_avatar_id']));
    $rank = isset($_POST['uv_rank_number']) && $_POST['uv_rank_number'] !== '' ? intval($_POST['uv_rank_number']) : 999;
    update_user_meta($user_id, 'uv_rank_number', $rank);
    update_user_meta($user_id, 'uv_show_phone', isset($_POST['uv_show_phone']) ? '1' : '0');
    if(isset($_POST['uv_locations'])){
        $loc_ids = array_filter(array_map('intval', (array)$_POST['uv_locations']));
        update_user_meta($user_id, 'uv_location_terms', $loc_ids);

        $existing = get_posts([
            'post_type'      => 'uv_team_assignment',
            'posts_per_page' => -1,
            'post_status'    => 'any',
            'no_found_rows'  => true,
            'fields'         => 'ids',
            'meta_query'     => [
                ['key' => 'uv_user_id', 'value' => $user_id, 'compare' => '='],
            ],
        ]);
        $existing_map = [];
        foreach($existing as $pid){
            $lid = get_post_meta($pid, 'uv_location_id', true);
            $existing_map[$lid] = $pid;
        }
        foreach($loc_ids as $lid){
            if(isset($existing_map[$lid])){
                unset($existing_map[$lid]);
                continue;
            }
            $term = get_term($lid, 'uv_location');
            $title = get_the_author_meta('display_name', $user_id) . ' - ' . ($term ? $term->name : '');
            $post_id = wp_insert_post([
                'post_type'   => 'uv_team_assignment',
                'post_status' => 'publish',
                'post_title'  => $title,
                'post_author' => $user_id,
            ]);
            if($post_id){
                update_post_meta($post_id, 'uv_user_id', $user_id);
                update_post_meta($post_id, 'uv_location_id', $lid);
                $pos_term = absint(get_user_meta($user_id, 'uv_position_term', true));
                if($pos_term) update_post_meta($post_id, 'uv_role_term', $pos_term);
                update_post_meta($post_id, 'uv_is_primary', '0');
                update_post_meta($post_id, 'uv_order_weight', '10');
            }
        }
        foreach($existing_map as $post_id){
            wp_delete_post($post_id, true);
        }
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
        return '<p>'.esc_html__('You must be logged in to edit your profile.', 'uv-people').'</p>';
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
                    $message = '<div class="uv-edit-profile-message uv-error">'.esc_html__('Avatar upload failed.', 'uv-people').'</div>';
                }
            }

            uv_people_profile_save($user_id);
            if(!$message){
                $message = '<div class="uv-edit-profile-message uv-success">'.esc_html__('Profile updated.', 'uv-people').'</div>';
            }
        } else {
            $message = '<div class="uv-edit-profile-message uv-error">'.esc_html__('Security check failed.', 'uv-people').'</div>';
        }
    }

    $phone       = get_user_meta($user_id, 'uv_phone', true);
    $quote_nb    = get_user_meta($user_id, 'uv_quote_nb', true);
    $quote_en    = get_user_meta($user_id, 'uv_quote_en', true);
    $avatar_id   = get_user_meta($user_id, 'uv_avatar_id', true);
    $position    = get_user_meta($user_id, 'uv_position_term', true);
    $positions   = get_terms(['taxonomy' => 'uv_position', 'hide_empty' => false]);
    if(is_wp_error($positions)){
        $positions = [];
    }

    ob_start();
    wp_enqueue_style('uv-people-edit-profile', plugin_dir_url(__FILE__) . 'assets/edit-profile.css', [], UV_PEOPLE_VERSION);

    if($message){
        echo $message;
    }
    ?>
    <form class="uv-edit-profile-form" method="post" enctype="multipart/form-data">
        <?php wp_nonce_field('update-user_' . $user_id); ?>
        <p class="uv-field">
            <label for="uv_phone"><?php esc_html_e('Phone', 'uv-people'); ?></label>
            <input type="text" name="uv_phone" id="uv_phone" value="<?php echo esc_attr($phone); ?>">
        </p>
        <p class="uv-field">
            <label for="uv_position_term"><?php esc_html_e('Position', 'uv-people'); ?></label>
            <select name="uv_position_term" id="uv_position_term">
                <option value=""><?php esc_html_e('Select', 'uv-people'); ?></option>
                <?php foreach($positions as $pos): ?>
                    <option value="<?php echo esc_attr($pos->term_id); ?>" <?php selected($position, $pos->term_id); ?>><?php echo esc_html($pos->name); ?></option>
                <?php endforeach; ?>
            </select>
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

// Shortcode: Team grid by location
function uv_people_team_grid($atts){
    wp_enqueue_style('uv-team-grid-style', plugin_dir_url(__FILE__) . 'blocks/team-grid/style.css', [], UV_PEOPLE_VERSION);
    $a = shortcode_atts(['location'=>'','columns'=>4,'highlight_primary'=>1], $atts);
    $placeholder = function($msg){
        return (is_admin() || (defined('REST_REQUEST') && REST_REQUEST))
            ? '<div class="uv-block-placeholder">'.esc_html($msg).'</div>'
            : '';
    };
    if(!$a['location']) return $placeholder(__('Select a location.', 'uv-people'));
    // Guard against missing uv_location taxonomy when uv-core is inactive or removed
    if (!taxonomy_exists('uv_location')) {
        return $placeholder(__('No locations available.', 'uv-people'));
    }
    $loc = sanitize_title($a['location']); // Shortcode expects a location slug
    $term = get_term_by('slug', $loc, 'uv_location'); // Look up the term to obtain its ID
    if(!$term) return $placeholder(__('Location not found.', 'uv-people'));
    $order_ids = get_term_meta($term->term_id, 'uv_member_order', true);
    $order_map = is_array($order_ids) ? array_flip(array_map('intval', $order_ids)) : [];
    // Fetch only assignments tied to this location
    $q = new WP_Query([
        'post_type'     => 'uv_team_assignment',
        'posts_per_page'=> -1,
        'no_found_rows' => true,
        'fields'        => 'ids',
        'meta_query'    => [
            ['key' => 'uv_location_id', 'value' => strval($term->term_id), 'compare' => '=']
        ]
    ]);
    if(!$q->have_posts()){
        wp_reset_postdata();
        return $placeholder(__('No team members found.', 'uv-people'));
    }
    $cols = max(1, min(6, intval($a['columns'])));
    $items = [];
    foreach($q->posts as $pid){
        $uid = get_post_meta($pid,'uv_user_id',true);
        $rank = get_user_meta($uid, 'uv_rank_number', true);
        $rank = ($rank === '' ? 999 : intval($rank));
        $items[] = [
            'id'        => $pid,
            'user_id'   => $uid,
            'role_term' => get_post_meta($pid,'uv_role_term',true),
            'role_nb'   => get_post_meta($pid,'uv_role_nb',true), // fallback legacy
            'role_en'   => get_post_meta($pid,'uv_role_en',true), // fallback legacy
            'primary'   => get_post_meta($pid,'uv_is_primary',true) === '1',
            'rank'      => $rank,
            'order'     => isset($order_map[$uid]) ? $order_map[$uid] : null,
        ];
    }
    wp_reset_postdata();
    if($order_map){
        usort($items, function($a,$b){
            $oa = $a['order'] !== null ? $a['order'] : 999;
            $ob = $b['order'] !== null ? $b['order'] : 999;
            if($oa !== $ob) return $oa < $ob ? -1 : 1;
            return 0;
        });
    } else {
        // sort priority: primary ➜ rank ➜ name
        usort($items, function($a,$b){
            if($a['primary'] !== $b['primary']) return $a['primary']? -1 : 1;
            if($a['rank'] !== $b['rank']) return $a['rank'] < $b['rank'] ? -1 : 1;
            $an = get_the_author_meta('display_name', $a['user_id']);
            $bn = get_the_author_meta('display_name', $b['user_id']);
            return strcasecmp($an, $bn);
        });
    }
    $lang = function_exists('pll_current_language') ? pll_current_language('slug') : substr(get_locale(),0,2);
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
        echo '<h3>'.esc_html($name).'</h3>';
        $role = '';
        $role_term = $it['role_term'];
        if(!$role_term){
            $role_term = get_user_meta($uid,'uv_position_term',true);
        }
        if($role_term){
            $t = get_term($role_term, 'uv_position');
            if(!is_wp_error($t) && $t){
                if(function_exists('pll_get_term') && $lang){
                    $tid = pll_get_term($t->term_id, $lang);
                    if($tid){
                        $t = get_term($tid, 'uv_position');
                    }
                }
                if($t && !is_wp_error($t)){
                    $role = $t->name;
                }
            }
        }
        if(!$role){
            $role_nb = $it['role_nb'] ?: get_user_meta($uid,'uv_position_nb',true);
            $role_en = $it['role_en'] ?: get_user_meta($uid,'uv_position_en',true);
            $role = ($lang==='en') ? ($role_en ?: $role_nb) : ($role_nb ?: $role_en);
        }
        if($role) echo '<div class="uv-role">'.esc_html($role).'</div>';

        // choose quote by language
        $quote_nb = get_user_meta($uid,'uv_quote_nb',true);
        $quote_en = get_user_meta($uid,'uv_quote_en',true);
        $quote = ($lang==='en') ? ($quote_en ?: $quote_nb) : ($quote_nb ?: $quote_en);
        echo '</div>';
        echo '</a>';
        $bio = get_the_author_meta('description', $uid);
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
    ], $atts);

    $meta_query = [
        [
            'key'     => 'uv_location_id',
            'compare' => 'EXISTS',
        ],
    ];

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

    // Fetch all team assignments and group them by user
    $assignments = get_posts([
        'post_type'      => 'uv_team_assignment',
        'numberposts'    => -1,
        'fields'         => 'ids',
        'no_found_rows'  => true,
        'meta_query'     => $meta_query,
    ]);
    $grouped = [];
    foreach ( $assignments as $aid ) {
        $uid = intval( get_post_meta( $aid, 'uv_user_id', true ) );
        if ( ! $uid ) {
            continue;
        }
        if ( ! isset( $grouped[ $uid ] ) ) {
            $grouped[ $uid ] = [
                'matched' => false,
                'primary' => false,
                'rank'    => null,
            ];
        }
        $loc_id  = intval( get_post_meta( $aid, 'uv_location_id', true ) );
        $matches = empty( $location_ids ) || in_array( $loc_id, $location_ids, true );
        if ( $matches ) {
            $grouped[ $uid ]['matched'] = true;
            if ( get_post_meta( $aid, 'uv_is_primary', true ) === '1' ) {
                $grouped[ $uid ]['primary'] = true;
            }
            $arank = get_post_meta( $aid, 'uv_rank_number', true );
            if ( $arank !== '' ) {
                $arank = intval( $arank );
                if ( ! isset( $grouped[ $uid ]['rank'] ) || $arank < $grouped[ $uid ]['rank'] ) {
                    $grouped[ $uid ]['rank'] = $arank;
                }
            }
        }
    }
    $user_ids = array_keys( array_filter( $grouped, function( $u ){ return $u['matched']; } ) );

    if(!$user_ids){
        return (is_admin() || (defined('REST_REQUEST') && REST_REQUEST))
            ? '<div class="uv-block-placeholder">'.esc_html__('No team members found.', 'uv-people').'</div>'
            : '';
    }

    $per_page = max(1, intval($a['per_page']));
    $page     = isset($_GET['uv_page']) ? max(1, intval($_GET['uv_page'])) : max(1, intval($a['page']));
    $offset   = ($page - 1) * $per_page;

    $sorted = [];
    foreach ( $user_ids as $uid ) {
        $rank = isset( $grouped[ $uid ]['rank'] ) ? $grouped[ $uid ]['rank'] : '';
        if ( $rank === '' || $rank === null ) {
            $rank = get_user_meta( $uid, 'uv_rank_number', true );
        }
        $rank = ( $rank === '' ? 999 : intval( $rank ) );
        $name = get_the_author_meta( 'display_name', $uid );
        $sorted[] = [
            'ID'      => $uid,
            'rank'    => $rank,
            'name'    => $name,
            'primary' => ! empty( $grouped[ $uid ]['primary'] ),
        ];
    }
    usort($sorted, function($a,$b){
        if ($a['rank'] !== $b['rank']) return $a['rank'] < $b['rank'] ? -1 : 1;
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
                'primary' => !empty( $it['primary'] ),
            ];
        }
    }
    $total_pages = ceil($total_users / $per_page);

    $cols = max(1, min(6, intval($a['columns'])));
    $lang = function_exists('pll_current_language') ? pll_current_language('slug') : substr(get_locale(),0,2);
    ob_start();
    echo '<div class="uv-team-grid columns-'.$cols.'" role="list">';
    foreach($items as $it){
        $user = $it['user'];
        $uid = $user->ID;
        $name = $user->display_name;
        $phone = get_user_meta($uid,'uv_phone',true);
        $email = $user->user_email;
        $classes = 'uv-person';
        if (!empty($it['primary'])) {
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
        echo '<h3>'.esc_html($name).'</h3>';
        $role = '';
        $role_term = get_user_meta($uid,'uv_position_term',true);
        if($role_term){
            $t = get_term($role_term, 'uv_position');
            if(!is_wp_error($t) && $t){
                if(function_exists('pll_get_term') && $lang){
                    $tid = pll_get_term($t->term_id, $lang);
                    if($tid){
                        $t = get_term($tid, 'uv_position');
                    }
                }
                if($t && !is_wp_error($t)){
                    $role = $t->name;
                }
            }
        }
        if(!$role){
            $role_nb = get_user_meta($uid,'uv_position_nb',true);
            $role_en = get_user_meta($uid,'uv_position_en',true);
            $role = ($lang==='en') ? ($role_en ?: $role_nb) : ($role_nb ?: $role_en);
        }
        if($role) echo '<div class="uv-role">'.esc_html($role).'</div>';
        echo '</div>';
        echo '</a>';
        $bio = get_the_author_meta('description', $uid);
        if($bio) echo '<div class="uv-bio">'.wp_kses_post(wpautop($bio)).'</div>';
        $show_phone = get_user_meta($uid,'uv_show_phone',true)==='1';
        if(($phone && $show_phone) || $email){
            $email_label = ($lang==='en') ? __('Email:','uv-people') : __('E-post:','uv-people');
            $phone_label = ($lang==='en') ? __('Mobile:','uv-people') : __('Mobil:','uv-people');
            echo '<div class="uv-contact">';
            if($email) echo '<div class="uv-email"><span class="label">'.esc_html($email_label).'</span><a href="mailto:'.esc_attr($email).'">'.esc_html($email).'</a></div>';
            if($phone && $show_phone) echo '<div class="uv-mobile"><span class="label">'.esc_html($phone_label).'</span><a href="tel:'.esc_attr($phone).'">'.esc_html($phone).'</a></div>';
            echo '</div>';
        }
        $quote_nb = get_user_meta($uid,'uv_quote_nb',true);
        $quote_en = get_user_meta($uid,'uv_quote_en',true);
        $quote = ($lang==='en') ? ($quote_en ?: $quote_nb) : ($quote_nb ?: $quote_en);
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
    wp_add_dashboard_widget('uv_team_guide', esc_html__('Team Guide','uv-people'), function(){
        echo '<p>'.esc_html__('Quick links for editors:', 'uv-people').'</p>';
        echo '<ul>
            <li><a href="edit-tags.php?taxonomy=uv_location">'.esc_html__('Manage Locations','uv-people').'</a></li>
            <li><a href="edit.php?post_type=uv_activity">'.esc_html__('Activities','uv-people').'</a></li>
            <li><a href="edit.php?post_type=uv_partner">'.esc_html__('Partners','uv-people').'</a></li>
            <li><a href="edit.php">'.esc_html__('News Posts','uv-people').'</a></li>
            <li><a href="edit.php?post_type=uv_team_assignment">'.esc_html__('Team Assignments','uv-people').'</a></li>
        </ul>';
        echo '<p>'.esc_html__('Add your own how-to video links here (edit uv-people plugin).','uv-people').'</p>';
    });
});

// Team members list with primary toggle and ordering controls on uv_location term edit screen
add_action('uv_location_edit_form_fields', function($term){
    $term_id = $term->term_id;
    $assignments = get_posts([
        'post_type'      => 'uv_team_assignment',
        'posts_per_page' => -1,
        'post_status'    => 'any',
        'no_found_rows'  => true,
        'fields'         => 'ids',
        'meta_query'     => [
            ['key' => 'uv_location_id', 'value' => strval($term_id), 'compare' => '='],
        ],
    ]);
    $members = [];
    foreach ($assignments as $pid) {
        $uid = intval(get_post_meta($pid, 'uv_user_id', true));
        if (!$uid) continue;
        $members[$uid] = [
            'name'    => get_the_author_meta('display_name', $uid),
            'primary' => (get_post_meta($pid, 'uv_is_primary', true) === '1'),
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
    echo '<tr class="form-field"><th scope="row"><label>'.esc_html__('Team members','uv-people').'</label></th><td>';
    if ($ordered) {
        echo '<ul id="uv-member-sortable">';
        foreach ($ordered as $uid => $data) {
            $checked = $data['primary'] ? ' checked' : '';
            echo '<li class="uv-member-item" data-uid="'.esc_attr($uid).'">';
            echo '<span class="uv-handle dashicons dashicons-move" style="cursor:move;margin-right:8px;"></span>'.esc_html($data['name']);
            echo ' <label style="margin-left:10px;"><input type="checkbox" name="uv_primary_team[]" value="'.esc_attr($uid).'"'.$checked.'> '.esc_html__('Primary','uv-people').'</label>';
            echo '<input type="hidden" name="uv_member_order[]" value="'.esc_attr($uid).'">';
            echo '</li>';
        }
        echo '</ul>';
        echo '<p class="description">'.esc_html__('Drag to reorder team members.','uv-people').'</p>';
    } else {
        echo '<p>'.esc_html__('No team members assigned.','uv-people').'</p>';
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
    $assignments = get_posts([
        'post_type'      => 'uv_team_assignment',
        'posts_per_page' => -1,
        'post_status'    => 'any',
        'no_found_rows'  => true,
        'fields'         => 'ids',
        'meta_query'     => [
            ['key' => 'uv_location_id', 'value' => strval($term_id), 'compare' => '='],
        ],
    ]);
    foreach ($assignments as $pid) {
        $uid = intval(get_post_meta($pid, 'uv_user_id', true));
        $pos = array_search($uid, $order_ids, true);
        $weight = ($pos !== false) ? $pos + 1 : 999;
        update_post_meta($pid, 'uv_order_weight', $weight);
        $is_primary = in_array($uid, $primary_ids, true) ? '1' : '0';
        update_post_meta($pid, 'uv_is_primary', $is_primary);
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

        // Migrate assignment roles
        $assignments = get_posts([
            'post_type'      => 'uv_team_assignment',
            'posts_per_page' => -1,
            'post_status'    => 'any',
            'no_found_rows'  => true,
            'fields'         => 'ids',
            'meta_key'       => 'uv_role_title',
        ]);
        foreach ($assignments as $post_id) {
            $legacy = get_post_meta($post_id, 'uv_role_title', true);
            if ($legacy) {
                if (!get_post_meta($post_id, 'uv_role_nb', true)) {
                    update_post_meta($post_id, 'uv_role_nb', $legacy);
                }
                if (!get_post_meta($post_id, 'uv_role_en', true)) {
                    update_post_meta($post_id, 'uv_role_en', $legacy);
                }
                delete_post_meta($post_id, 'uv_role_title');
                $migrated++;
            }
        }

        WP_CLI::success(sprintf('Migrated %d legacy meta entries.', $migrated));
    });
}
