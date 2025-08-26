<?php
/**
 * Plugin Name: UV People
 * Description: Extends WordPress Users with public fields, media-library avatars, per-location assignments, and a Team grid shortcode.
 * Version: 0.5.5
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: Unge Vil
 * Author URI: https://www.ungevil.no/
 * Text Domain: uv-people
 * Update URI: https://github.com/Unge-Vil/Unge-Vil-Website/plugins/uv-people
 */
if (!defined('ABSPATH')) exit;

if (!defined('UV_PEOPLE_VERSION')) {
    define('UV_PEOPLE_VERSION', '0.5.5');
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
        wp_enqueue_script(
            'uv-people-admin',
            plugin_dir_url(__FILE__) . 'assets/admin.js',
            ['jquery', 'select2'],
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

// Meta boxes for uv_team_assignment
add_action('add_meta_boxes_uv_team_assignment', function(){
    add_meta_box('uv_ta_fields', __('Assignment', 'uv-people'), function($post){
        $user_id = get_post_meta($post->ID, 'uv_user_id', true);
        $user_ids = $user_id ? [$user_id] : [];
        $loc_id  = get_post_meta($post->ID, 'uv_location_id', true);
        $role_nb = get_post_meta($post->ID, 'uv_role_nb', true);
        $role_en = get_post_meta($post->ID, 'uv_role_en', true);
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
        <p><label><?php esc_html_e('Role title (Norwegian)','uv-people'); ?></label>
        <input type="text" name="uv_role_nb" value="<?php echo esc_attr($role_nb); ?>" style="width:100%"></p>
        <p><label><?php esc_html_e('Role title (English)','uv-people'); ?></label>
        <input type="text" name="uv_role_en" value="<?php echo esc_attr($role_en); ?>" style="width:100%"></p>
        <p><label><input type="checkbox" name="uv_is_primary" value="1" <?php checked($primary, '1'); ?>> <?php esc_html_e('Primary contact','uv-people'); ?></label></p>
        <p><label><?php esc_html_e('Order weight (lower = earlier)','uv-people'); ?></label>
        <input type="number" name="uv_order_weight" value="<?php echo esc_attr($order?:'10'); ?>" style="width:100%"></p>
        <?php
    }, 'normal');
});

function uv_save_team_assignment($post_id){
    if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if(!isset($_POST['uv_ta_nonce']) || !wp_verify_nonce($_POST['uv_ta_nonce'], 'uv_ta_save')) return;
    if(!current_user_can('edit_post', $post_id)) return;

    remove_action('save_post_uv_team_assignment', 'uv_save_team_assignment');

    $user_ids = isset($_POST['uv_user_ids']) ? array_filter(array_map('absint', (array)$_POST['uv_user_ids'])) : [];
    $loc_id   = isset($_POST['uv_location_id']) ? absint($_POST['uv_location_id']) : 0;
    $role_nb  = isset($_POST['uv_role_nb']) ? sanitize_text_field($_POST['uv_role_nb']) : '';
    $role_en  = isset($_POST['uv_role_en']) ? sanitize_text_field($_POST['uv_role_en']) : '';
    $order    = isset($_POST['uv_order_weight']) ? absint($_POST['uv_order_weight']) : '';
    $primary  = isset($_POST['uv_is_primary']) ? '1' : '0';

    if($loc_id) update_post_meta($post_id, 'uv_location_id', $loc_id);
    if($role_nb !== '') update_post_meta($post_id, 'uv_role_nb', $role_nb);
    if($role_en !== '') update_post_meta($post_id, 'uv_role_en', $role_en);
    if($order !== '') update_post_meta($post_id, 'uv_order_weight', (int) $order);
    update_post_meta($post_id, 'uv_is_primary', $primary);

    if($user_ids){
        $first = array_shift($user_ids);
        update_post_meta($post_id, 'uv_user_id', $first);

        $term = get_term($loc_id, 'uv_location');
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
                if($role_nb !== '') update_post_meta($new_id, 'uv_role_nb', $role_nb);
                if($role_en !== '') update_post_meta($new_id, 'uv_role_en', $role_en);
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
    $phone      = get_user_meta($user->ID, 'uv_phone', true);
    $position_nb = get_user_meta($user->ID, 'uv_position_nb', true);
    $position_en = get_user_meta($user->ID, 'uv_position_en', true);
    $quote_nb   = get_user_meta($user->ID, 'uv_quote_nb', true);
    $quote_en   = get_user_meta($user->ID, 'uv_quote_en', true);
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
      <tr><th><label for="uv_position_nb"><?php esc_html_e('Position (Norwegian)','uv-people'); ?></label></th>
        <td><input type="text" name="uv_position_nb" id="uv_position_nb" value="<?php echo esc_attr($position_nb); ?>" class="regular-text"></td></tr>
      <tr><th><label for="uv_position_en"><?php esc_html_e('Position (English)','uv-people'); ?></label></th>
        <td><input type="text" name="uv_position_en" id="uv_position_en" value="<?php echo esc_attr($position_en); ?>" class="regular-text"></td></tr>
      <tr><th><label for="uv_quote_nb"><?php esc_html_e('Volunteer Quote (Norwegian)','uv-people'); ?></label></th>
        <td><textarea name="uv_quote_nb" id="uv_quote_nb" rows="4" class="large-text"><?php echo esc_textarea($quote_nb); ?></textarea></td></tr>
      <tr><th><label for="uv_quote_en"><?php esc_html_e('Volunteer Quote (English)','uv-people'); ?></label></th>
        <td><textarea name="uv_quote_en" id="uv_quote_en" rows="4" class="large-text"><?php echo esc_textarea($quote_en); ?></textarea></td></tr>
      <tr><th><?php esc_html_e('Avatar (Media Library)','uv-people'); ?></th>
        <td>
          <input type="hidden" id="uv_avatar_id" name="uv_avatar_id" value="<?php echo esc_attr($avatar_id); ?>">
          <button class="button" id="uv-avatar-upload"><?php esc_html_e('Select Image','uv-people'); ?></button>
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
    if(isset($_POST['uv_position_nb'])) update_user_meta($user_id, 'uv_position_nb', sanitize_text_field($_POST['uv_position_nb']));
    if(isset($_POST['uv_position_en'])) update_user_meta($user_id, 'uv_position_en', sanitize_text_field($_POST['uv_position_en']));
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
            'meta_query'     => [
                ['key' => 'uv_user_id', 'value' => $user_id, 'compare' => '='],
            ],
        ]);
        $existing_map = [];
        foreach($existing as $p){
            $lid = get_post_meta($p->ID, 'uv_location_id', true);
            $existing_map[$lid] = $p->ID;
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
        $img = wp_get_attachment_image($id, 'uv_avatar', false, ['alt'=>$alt]);
        return $img;
    }
    return get_avatar($user_id, 96, '', $alt); // fallback
}

// Shortcode: Team grid by location
function uv_people_team_grid($atts){
    wp_enqueue_style('uv-team-grid-style', plugin_dir_url(__FILE__) . 'blocks/team-grid/style.css', [], UV_PEOPLE_VERSION);
    $a = shortcode_atts(['location'=>'','columns'=>4,'highlight_primary'=>1], $atts);
    if(!$a['location']) return '';
    // Guard against missing uv_location taxonomy when uv-core is inactive or removed
    if (!taxonomy_exists('uv_location')) {
        return '';
    }
    $loc = sanitize_title($a['location']);
    $term = get_term_by('slug', $loc, 'uv_location');
    if(!$term) return '';
    $q = new WP_Query([
        'post_type'=>'uv_team_assignment',
        'posts_per_page'=>-1,
        'meta_query'=>[
            ['key'=>'uv_location_id','value'=>strval($term->term_id),'compare'=>'=']
        ]
    ]);
    if(!$q->have_posts()) return '';
    $cols = max(1, min(6, intval($a['columns'])));
    $items = [];
    while($q->have_posts()){ $q->the_post();
        $uid = get_post_meta(get_the_ID(),'uv_user_id',true);
        $rank = get_user_meta($uid, 'uv_rank_number', true);
        $rank = ($rank === '' ? 999 : intval($rank));
        $items[] = [
            'id'=>get_the_ID(),
            'user_id'=>$uid,
            'role_nb'=>get_post_meta(get_the_ID(),'uv_role_nb',true),
            'role_en'=>get_post_meta(get_the_ID(),'uv_role_en',true),
            'primary'=>get_post_meta(get_the_ID(),'uv_is_primary',true) === '1',
            'rank'=>$rank,
        ];
    }
    wp_reset_postdata();
    // sort priority: primary ➜ rank ➜ name
    usort($items, function($a,$b){
        if($a['primary'] !== $b['primary']) return $a['primary']? -1 : 1;
        if($a['rank'] !== $b['rank']) return $a['rank'] < $b['rank'] ? -1 : 1;
        $an = get_the_author_meta('display_name', $a['user_id']);
        $bn = get_the_author_meta('display_name', $b['user_id']);
        return strcasecmp($an, $bn);
    });
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
        $url = add_query_arg('team', '1', get_author_posts_url($uid));
        $label = sprintf(__('View profile for %s','uv-people'), $name);
        echo '<article class="'.esc_attr($classes).'" role="listitem">';
        echo '<a href="'.esc_url($url).'" aria-label="'.esc_attr($label).'">';
        echo '<div class="uv-avatar">'.uv_people_get_avatar($uid).'</div>';
        echo '<div class="uv-info">';
        echo '<h3>'.esc_html($name).'</h3>';
        $role_nb = $it['role_nb'] ?: get_user_meta($uid,'uv_position_nb',true);
        $role_en = $it['role_en'] ?: get_user_meta($uid,'uv_position_en',true);
        $role = ($lang==='en') ? ($role_en ?: $role_nb) : ($role_nb ?: $role_en);
        if($role) echo '<div class="uv-role">'.esc_html($role).'</div>';

        // choose quote by language
        $quote_nb = get_user_meta($uid,'uv_quote_nb',true);
        $quote_en = get_user_meta($uid,'uv_quote_en',true);
        $quote = ($lang==='en') ? ($quote_en ?: $quote_nb) : ($quote_nb ?: $quote_en);
        echo '</div>';
        echo '</a>';
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
    return ob_get_clean();
}
add_shortcode('uv_team','uv_people_team_grid');

function uv_people_all_team_grid($atts){
    wp_enqueue_style('uv-all-team-grid-style', plugin_dir_url(__FILE__) . 'blocks/all-team-grid/style.css', [], UV_PEOPLE_VERSION);
    $a = shortcode_atts(['columns'=>4], $atts);
    $users = get_users(['meta_key' => 'uv_rank_number', 'meta_compare' => 'EXISTS', 'number' => -1]);
    if(!$users) return '';
    $items = [];
    foreach($users as $u){
        $rank = get_user_meta($u->ID, 'uv_rank_number', true);
        $rank = ($rank === '' ? 999 : intval($rank));
        $items[] = ['user' => $u, 'rank' => $rank];
    }
    usort($items, function($a,$b){
        if($a['rank'] !== $b['rank']) return $a['rank'] < $b['rank'] ? -1 : 1;
        return strcasecmp($a['user']->display_name, $b['user']->display_name);
    });
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
        $url = add_query_arg('team','1',get_author_posts_url($uid));
        $label = sprintf(__('View profile for %s','uv-people'), $name);
        echo '<article class="'.esc_attr($classes).'" role="listitem">';
        echo '<a href="'.esc_url($url).'" aria-label="'.esc_attr($label).'">';
        echo '<div class="uv-avatar">'.uv_people_get_avatar($uid).'</div>';
        echo '<div class="uv-info">';
        echo '<h3>'.esc_html($name).'</h3>';
        $role_nb = get_user_meta($uid,'uv_position_nb',true);
        $role_en = get_user_meta($uid,'uv_position_en',true);
        $role = ($lang==='en') ? ($role_en ?: $role_nb) : ($role_nb ?: $role_en);
        if($role) echo '<div class="uv-role">'.esc_html($role).'</div>';
        echo '</div>';
        echo '</a>';
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
    return ob_get_clean();
}

// Block registration
add_action('init', function(){
    register_block_type(__DIR__ . '/blocks/team-grid', [
        'render_callback' => 'uv_people_team_grid'
    ]);
    register_block_type(__DIR__ . '/blocks/all-team-grid', [
        'render_callback' => 'uv_people_all_team_grid'
    ]);
});

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

// Primary team selection on uv_location term edit screen
add_action('uv_location_edit_form_fields', function($term){
    $term_id = $term->term_id;
    $assignments = get_posts([
        'post_type'      => 'uv_team_assignment',
        'posts_per_page' => -1,
        'post_status'    => 'any',
        'meta_query'     => [
            ['key' => 'uv_location_id', 'value' => strval($term_id), 'compare' => '='],
        ],
    ]);
    $users = [];
    $selected = [];
    foreach ($assignments as $p) {
        $uid = get_post_meta($p->ID, 'uv_user_id', true);
        if ($uid) {
            $uid = intval($uid);
            $users[$uid] = get_the_author_meta('display_name', $uid);
            if ('1' === get_post_meta($p->ID, 'uv_is_primary', true)) {
                $selected[] = $uid;
            }
        }
    }
    wp_nonce_field('uv_location_primary_team', 'uv_location_primary_team_nonce');
    echo '<tr class="form-field"><th scope="row"><label for="uv_primary_team">'.esc_html__('Primary contacts','uv-people').'</label></th><td>';
    echo '<select multiple name="uv_primary_team[]" id="uv_primary_team" class="uv-user-select" style="width:100%;">';
    foreach ($users as $uid => $name) {
        $sel = in_array($uid, $selected, true) ? ' selected' : '';
        echo '<option value="'.esc_attr($uid).'"'.$sel.'>'.esc_html($name).'</option>';
    }
    echo '</select></td></tr>';
});

function uv_people_save_location_primary_team($term_id){
    if (!isset($_POST['uv_location_primary_team_nonce']) || !wp_verify_nonce($_POST['uv_location_primary_team_nonce'], 'uv_location_primary_team')) {
        return;
    }
    $ids = isset($_POST['uv_primary_team']) ? array_filter(array_map('intval', (array)$_POST['uv_primary_team'])) : [];
    update_term_meta($term_id, 'uv_primary_team', $ids);
    $assignments = get_posts([
        'post_type'      => 'uv_team_assignment',
        'posts_per_page' => -1,
        'post_status'    => 'any',
        'meta_query'     => [
            ['key' => 'uv_location_id', 'value' => strval($term_id), 'compare' => '='],
        ],
    ]);
    foreach ($assignments as $p) {
        $uid = intval(get_post_meta($p->ID, 'uv_user_id', true));
        $is_primary = in_array($uid, $ids, true) ? '1' : '0';
        update_post_meta($p->ID, 'uv_is_primary', $is_primary);
    }
}
add_action('edited_uv_location', 'uv_people_save_location_primary_team');
add_action('created_uv_location', 'uv_people_save_location_primary_team');

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
        $users = get_users(['meta_key' => 'uv_quote']);
        foreach ($users as $user) {
            $legacy = get_user_meta($user->ID, 'uv_quote', true);
            if ($legacy) {
                if (!get_user_meta($user->ID, 'uv_quote_nb', true)) {
                    update_user_meta($user->ID, 'uv_quote_nb', $legacy);
                }
                if (!get_user_meta($user->ID, 'uv_quote_en', true)) {
                    update_user_meta($user->ID, 'uv_quote_en', $legacy);
                }
                delete_user_meta($user->ID, 'uv_quote');
                $migrated++;
            }
        }

        // Migrate assignment roles
        $assignments = get_posts([
            'post_type'      => 'uv_team_assignment',
            'posts_per_page' => -1,
            'post_status'    => 'any',
            'meta_key'       => 'uv_role_title',
        ]);
        foreach ($assignments as $post) {
            $legacy = get_post_meta($post->ID, 'uv_role_title', true);
            if ($legacy) {
                if (!get_post_meta($post->ID, 'uv_role_nb', true)) {
                    update_post_meta($post->ID, 'uv_role_nb', $legacy);
                }
                if (!get_post_meta($post->ID, 'uv_role_en', true)) {
                    update_post_meta($post->ID, 'uv_role_en', $legacy);
                }
                delete_post_meta($post->ID, 'uv_role_title');
                $migrated++;
            }
        }

        WP_CLI::success(sprintf('Migrated %d legacy meta entries.', $migrated));
    });
}
