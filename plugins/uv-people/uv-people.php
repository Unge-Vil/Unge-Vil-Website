<?php
/**
 * Plugin Name: UV People
 * Description: Extends WordPress Users with public fields, media-library avatars, per-location assignments, and a Team grid shortcode.
 * Version: 0.1.0
 * Text Domain: uv-people
 */
if (!defined('ABSPATH')) exit;

// Load textdomain
add_action('plugins_loaded', function(){
    load_plugin_textdomain('uv-people', false, dirname(plugin_basename(__FILE__)) . '/languages');
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
        $loc_id  = get_post_meta($post->ID, 'uv_location_id', true);
        $role    = get_post_meta($post->ID, 'uv_role_title', true);
        $primary = get_post_meta($post->ID, 'uv_is_primary', true);
        $order   = get_post_meta($post->ID, 'uv_order_weight', true);
        $locations = get_terms(['taxonomy'=>'uv_location','hide_empty'=>false]);
        ?>
        <p><label><?php _e('User ID','uv-people'); ?></label>
        <input type="number" name="uv_user_id" value="<?php echo esc_attr($user_id); ?>" style="width:100%"></p>
        <p><label><?php _e('Location','uv-people'); ?></label>
        <select name="uv_location_id" style="width:100%">
            <option value=""><?php _e('Select','uv-people'); ?></option>
            <?php foreach($locations as $t): ?>
            <option value="<?php echo esc_attr($t->term_id); ?>" <?php selected($loc_id, $t->term_id); ?>><?php echo esc_html($t->name); ?></option>
            <?php endforeach; ?>
        </select></p>
        <p><label><?php _e('Role title','uv-people'); ?></label>
        <input type="text" name="uv_role_title" value="<?php echo esc_attr($role); ?>" style="width:100%"></p>
        <p><label><input type="checkbox" name="uv_is_primary" value="1" <?php checked($primary, '1'); ?>> <?php _e('Primary contact','uv-people'); ?></label></p>
        <p><label><?php _e('Order weight (lower = earlier)','uv-people'); ?></label>
        <input type="number" name="uv_order_weight" value="<?php echo esc_attr($order?:'10'); ?>" style="width:100%"></p>
        <?php
    }, 'normal');
});
add_action('save_post_uv_team_assignment', function($post_id){
    foreach(['uv_user_id','uv_location_id','uv_role_title','uv_order_weight'] as $key){
        if(isset($_POST[$key])) update_post_meta($post_id, $key, sanitize_text_field($_POST[$key]));
    }
    update_post_meta($post_id, 'uv_is_primary', isset($_POST['uv_is_primary']) ? '1' : '0');
});

// User profile fields (phone, public email, quote, socials, avatar attachment)
function uv_people_profile_fields($user){
    $phone = get_user_meta($user->ID, 'uv_phone', true);
    $pub_email = get_user_meta($user->ID, 'uv_public_email', true);
    $quote = get_user_meta($user->ID, 'uv_quote', true);
    $avatar_id = get_user_meta($user->ID, 'uv_avatar_id', true);
    ?>
    <h2><?php _e('Public Profile (Unge Vil)','uv-people'); ?></h2>
    <table class="form-table">
      <tr><th><label for="uv_phone"><?php _e('Phone (public optional)','uv-people'); ?></label></th>
        <td><input type="text" name="uv_phone" id="uv_phone" value="<?php echo esc_attr($phone); ?>" class="regular-text"></td></tr>
      <tr><th><label for="uv_public_email"><?php _e('Public Email (optional)','uv-people'); ?></label></th>
        <td><input type="email" name="uv_public_email" id="uv_public_email" value="<?php echo esc_attr($pub_email); ?>" class="regular-text"></td></tr>
      <tr><th><label for="uv_quote"><?php _e('Volunteer Quote','uv-people'); ?></label></th>
        <td><textarea name="uv_quote" id="uv_quote" rows="4" class="large-text"><?php echo esc_textarea($quote); ?></textarea></td></tr>
      <tr><th><?php _e('Avatar (Media Library)','uv-people'); ?></th>
        <td>
          <input type="hidden" id="uv_avatar_id" name="uv_avatar_id" value="<?php echo esc_attr($avatar_id); ?>">
          <button class="button" id="uv-avatar-upload"><?php _e('Select Image','uv-people'); ?></button>
          <div id="uv-avatar-preview"><?php echo $avatar_id ? wp_get_attachment_image($avatar_id,'uv_avatar') : ''; ?></div>
          <p class="description"><?php _e('This replaces Gravatar and uses a local image.','uv-people'); ?></p>
        </td></tr>
    </table>
    <script>
    jQuery(function($){
        var frame;
        $('#uv-avatar-upload').on('click', function(e){
            e.preventDefault();
            frame = wp.media({title: 'Select Avatar', multiple:false});
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
    foreach(['uv_phone','uv_public_email','uv_quote','uv_avatar_id'] as $k){
        if(isset($_POST[$k])) update_user_meta($user_id, $k, sanitize_text_field($_POST[$k]));
    }
}

// Helper: get user avatar URL by our field
function uv_people_get_avatar($user_id){
    $id = get_user_meta($user_id,'uv_avatar_id',true);
    if($id){
        $img = wp_get_attachment_image($id, 'uv_avatar', false, ['alt'=>'']);
        return $img;
    }
    return get_avatar($user_id); // fallback
}

// Shortcode: Team grid by location
function uv_people_team_grid($atts){
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
            'role'=>get_post_meta(get_the_ID(),'uv_role_title',true),
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
    ob_start();
    echo '<div class="uv-team-grid" style="grid-template-columns:repeat('.$cols.',1fr)">';
    foreach($items as $it){
        $uid = intval($it['user_id']);
        $name = get_the_author_meta('display_name', $uid);
        $quote = get_user_meta($uid,'uv_quote',true);
        $phone = get_user_meta($uid,'uv_phone',true);
        $pub_email = get_user_meta($uid,'uv_public_email',true);
        $classes = 'uv-person';
        if($a['highlight_primary'] && $it['primary']) $classes .= ' uv-primary-contact';
        echo '<div class="'.esc_attr($classes).'">';
        echo uv_people_get_avatar($uid);
        echo '<h3>'.esc_html($name).'</h3>';
        if($it['role']) echo '<div class="uv-role">'.esc_html($it['role']).'</div>';
        if($quote) echo '<div class="uv-quote">“'.esc_html($quote).'”</div>';
        if($phone || $pub_email){
            echo '<div class="uv-contact">';
            if($phone) echo '<div><a href="tel:'.esc_attr($phone).'">'.esc_html($phone).'</a></div>';
            if($pub_email) echo '<div><a href="mailto:'.esc_attr($pub_email).'">'.esc_html($pub_email).'</a></div>';
            echo '</div>';
        }
        echo '</div>';
    }
    echo '</div>';
    return ob_get_clean();
}
add_shortcode('uv_team','uv_people_team_grid');

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
