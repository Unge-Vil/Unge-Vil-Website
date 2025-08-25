<?php
/**
 * Plugin Name: UV People
 * Description: Extends WordPress Users with public fields, media-library avatars, per-location assignments, and a Team grid shortcode.
 * Version: 0.2.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Text Domain: uv-people
 */
if (!defined('ABSPATH')) exit;

// Load textdomain
add_action('plugins_loaded', function(){
    load_plugin_textdomain('uv-people', false, dirname(plugin_basename(__FILE__)) . '/languages');
});

// Localize strings for inline scripts
add_action('admin_enqueue_scripts', function($hook){
    if('profile.php' === $hook || 'user-edit.php' === $hook){
        wp_localize_script('jquery', 'UVPeople', [
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
        if(!$role_nb && !$role_en){
            $legacy = get_post_meta($post->ID, 'uv_role_title', true);
            $role_nb = $role_nb ?: $legacy;
            $role_en = $role_en ?: $legacy;
        }
        $primary = get_post_meta($post->ID, 'uv_is_primary', true);
        $order   = get_post_meta($post->ID, 'uv_order_weight', true);
        $locations = get_terms(['taxonomy'=>'uv_location','hide_empty'=>false]);
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
        <select name="uv_location_id" style="width:100%">
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
    $quote_nb   = get_user_meta($user->ID, 'uv_quote_nb', true);
    $quote_en   = get_user_meta($user->ID, 'uv_quote_en', true);
    $show_phone = get_user_meta($user->ID, 'uv_show_phone', true) === '1';
    $avatar_id  = get_user_meta($user->ID, 'uv_avatar_id', true);
    $locations  = get_terms(['taxonomy'=>'uv_location','hide_empty'=>false]);
    $assigned   = get_user_meta($user->ID, 'uv_location_terms', true);
    if(!is_array($assigned)) $assigned = [];
    if(!$quote_nb && !$quote_en){
        $legacy = get_user_meta($user->ID, 'uv_quote', true);
        $quote_nb = $quote_nb ?: $legacy;
        $quote_en = $quote_en ?: $legacy;
    }
    ?>
    <h2><?php esc_html_e('Public Profile (Unge Vil)','uv-people'); ?></h2>
    <table class="form-table">
      <tr><th><label for="uv_locations"><?php esc_html_e('Locations','uv-people'); ?></label></th>
        <td>
            <input type="hidden" name="uv_locations[]" value="">
            <select name="uv_locations[]" id="uv_locations" multiple style="height:8em;width:100%">
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
    <script>
    jQuery(function($){
        var frame;
        $('#uv-avatar-upload').on('click', function(e){
            e.preventDefault();
            frame = wp.media({title: UVPeople.selectAvatar, multiple:false});
            frame.on('select', function(){
                var att = frame.state().get('selection').first().toJSON();
                $('#uv_avatar_id').val(att.id);
                $('#uv-avatar-preview').html('<img src="'+att.url+'" style="max-width:128px;border-radius:12px;">');
            });
            frame.open();
        });
    });
    </script>
    <?php
}
add_action('show_user_profile','uv_people_profile_fields');
add_action('edit_user_profile','uv_people_profile_fields');

add_action('personal_options_update','uv_people_profile_save');
add_action('edit_user_profile_update','uv_people_profile_save');
function uv_people_profile_save($user_id){
    if(!current_user_can('edit_user', $user_id)) return;
    check_admin_referer('update-user_' . $user_id);
    if(isset($_POST['uv_phone'])) update_user_meta($user_id, 'uv_phone', sanitize_text_field($_POST['uv_phone']));
    if(isset($_POST['uv_quote_nb'])) update_user_meta($user_id, 'uv_quote_nb', sanitize_textarea_field($_POST['uv_quote_nb']));
    if(isset($_POST['uv_quote_en'])) update_user_meta($user_id, 'uv_quote_en', sanitize_textarea_field($_POST['uv_quote_en']));
    if(isset($_POST['uv_avatar_id'])) update_user_meta($user_id, 'uv_avatar_id', absint($_POST['uv_avatar_id']));
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
                update_post_meta($post_id, 'uv_role_title', '');
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
    wp_enqueue_style('uv-team-grid-style');
    $a = shortcode_atts(['location'=>'','columns'=>4,'highlight_primary'=>1], $atts);
    if(!$a['location']) return '';
    $term = get_term_by('slug', $a['location'], 'uv_location');
    if(!$term) return '';
    $q = new WP_Query([
        'post_type'=>'uv_team_assignment',
        'posts_per_page'=>-1,
        'meta_query'=>[
            ['key'=>'uv_location_id','value'=>strval($term->term_id),'compare'=>'=']
        ]
    ]);
    if(!$q->have_posts()) return '';
    $cols = intval($a['columns']);
    $items = [];
    while($q->have_posts()){ $q->the_post();
        $items[] = [
            'id'=>get_the_ID(),
            'user_id'=>get_post_meta(get_the_ID(),'uv_user_id',true),
            'role_nb'=>get_post_meta(get_the_ID(),'uv_role_nb',true),
            'role_en'=>get_post_meta(get_the_ID(),'uv_role_en',true),
            'role_legacy'=>get_post_meta(get_the_ID(),'uv_role_title',true),
            'primary'=>get_post_meta(get_the_ID(),'uv_is_primary',true) === '1',
            'order'=>intval(get_post_meta(get_the_ID(),'uv_order_weight',true) ?: 10),
        ];
    }
    wp_reset_postdata();
    // sort: primary desc, order asc, name asc
    usort($items, function($a,$b){
        if($a['primary'] !== $b['primary']) return $a['primary']? -1 : 1;
        if($a['order'] !== $b['order']) return $a['order'] < $b['order'] ? -1 : 1;
        $an = get_the_author_meta('display_name', $a['user_id']);
        $bn = get_the_author_meta('display_name', $b['user_id']);
        return strcasecmp($an, $bn);
    });
    $lang = function_exists('pll_current_language') ? pll_current_language('slug') : substr(get_locale(),0,2);
    ob_start();
    echo '<div class="uv-team-grid" style="grid-template-columns:repeat('.$cols.',1fr)">';
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
        echo '<a class="'.esc_attr($classes).'" href="'.esc_url($url).'" aria-label="'.esc_attr($label).'">';
        echo '<div class="uv-avatar">'.uv_people_get_avatar($uid).'</div>';
        echo '<div class="uv-info">';
        echo '<h3>'.esc_html($name).'</h3>';
        $role_nb = $it['role_nb'];
        $role_en = $it['role_en'];
        $legacy_role = $it['role_legacy'];
        $role = ($lang==='en') ? ($role_en ?: $role_nb ?: $legacy_role) : ($role_nb ?: $role_en ?: $legacy_role);
        if($role) echo '<div class="uv-role">'.esc_html($role).'</div>';

        // choose quote by language
        $quote_nb = get_user_meta($uid,'uv_quote_nb',true);
        $quote_en = get_user_meta($uid,'uv_quote_en',true);
        $legacy_q = get_user_meta($uid,'uv_quote',true);
        $quote = ($lang==='en') ? ($quote_en ?: $quote_nb ?: $legacy_q) : ($quote_nb ?: $quote_en ?: $legacy_q);
        if($quote) echo '<div class="uv-quote">“'.esc_html($quote).'”</div>';
        // contact visibility
        $show_phone = get_user_meta($uid,'uv_show_phone',true)==='1';
        if(($phone && $show_phone) || $email){
            echo '<div class="uv-contact">';
            if($phone && $show_phone) echo '<div><a href="tel:'.esc_attr($phone).'">'.esc_html($phone).'</a></div>';
            if($email) echo '<div><a href="mailto:'.esc_attr($email).'">'.esc_html($email).'</a></div>';
            echo '</div>';
        }
        echo '</div>';
        echo '</a>';
    }
    echo '</div>';
    return ob_get_clean();
}
add_shortcode('uv_team','uv_people_team_grid');

// Block registration
add_action('init', function(){
    register_block_type(__DIR__ . '/blocks/team-grid', [
        'render_callback' => 'uv_people_team_grid'
    ]);
});

// Dashboard widget for team guide links
add_action('wp_dashboard_setup', function(){
    wp_add_dashboard_widget('uv_team_guide', __('Team Guide','uv-people'), function(){
        echo '<p>'.__('Quick links for editors:', 'uv-people').'</p>';
        echo '<ul>
            <li><a href="edit-tags.php?taxonomy=uv_location">'.__('Manage Locations','uv-people').'</a></li>
            <li><a href="edit.php?post_type=uv_activity">'.__('Activities','uv-people').'</a></li>
            <li><a href="edit.php?post_type=uv_partner">'.__('Partners','uv-people').'</a></li>
            <li><a href="edit.php">'.__('News Posts','uv-people').'</a></li>
            <li><a href="edit.php?post_type=uv_team_assignment">'.__('Team Assignments','uv-people').'</a></li>
        </ul>';
        echo '<p>'.__('Add your own how-to video links here (edit uv-people plugin).','uv-people').'</p>';
    });
});

// Tidy admin for non-admins (optional, minimal)
add_action('admin_menu', function(){
    if(!current_user_can('manage_options')){
        remove_menu_page('tools.php');
        remove_menu_page('plugins.php');
        remove_menu_page('themes.php');
    }
}, 999);
