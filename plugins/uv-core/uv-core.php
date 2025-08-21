<?php
/**
 * Plugin Name: UV Core
 * Description: CPTs, taxonomies, term images, and lightweight shortcodes.
 * Version: 0.2.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Text Domain: uv-core
 */

if (!defined('ABSPATH')) exit;

add_action('plugins_loaded', function(){
    load_plugin_textdomain('uv-core', false, dirname(plugin_basename(__FILE__)) . '/languages');
});

add_action('init', function(){
    // Taxonomies
    register_taxonomy('uv_location', ['post','uv_activity','uv_partner','uv_experience'], [
        'label' => __('Locations', 'uv-core'),
        'public' => true,
        'hierarchical' => true,
        'show_in_rest' => true,
    ]);
    register_taxonomy('uv_activity_type', ['uv_activity'], [
        'label' => __('Activity Types', 'uv-core'),
        'public' => true,
        'hierarchical' => true,
        'show_in_rest' => true,
    ]);
    register_taxonomy('uv_partner_type', ['uv_partner'], [
        'label' => __('Partner Types', 'uv-core'),
        'public' => true,
        'hierarchical' => true,
        'show_in_rest' => true,
    ]);

    // CPTs
    register_post_type('uv_activity', [
        'label' => __('Activities', 'uv-core'),
        'public' => true,
        'show_in_rest' => true,
        'has_archive' => true,
        'menu_icon' => 'dashicons-forms',
        'supports' => ['title','editor','thumbnail','excerpt'],
        'taxonomies' => ['uv_location','uv_activity_type'],
    ]);
    register_post_type('uv_partner', [
        'label' => __('Partners', 'uv-core'),
        'public' => true,
        'show_in_rest' => true,
        'has_archive' => true,
        'menu_icon' => 'dashicons-heart',
        'supports' => ['title','editor','thumbnail','excerpt'],
        'taxonomies' => ['uv_location','uv_partner_type'],
    ]);
    register_post_type('uv_experience', [
        'label' => __('Experiences', 'uv-core'),
        'public' => true,
        'show_in_rest' => true,
        'has_archive' => true,
        'menu_icon' => 'dashicons-awards',
        'supports' => ['title','editor','thumbnail','excerpt','custom-fields'],
        'taxonomies' => ['uv_location'],
    ]);
});

add_action('admin_enqueue_scripts', function($hook){
    if(!in_array($hook, ['edit-tags.php','term.php'])) return;
    $screen = get_current_screen();
    if($screen && $screen->taxonomy === 'uv_location'){
        wp_enqueue_media();
        wp_enqueue_script('uv-term-image', plugins_url('assets/term-image.js', __FILE__), ['jquery'], '0.2.0', true);
        wp_localize_script('uv-term-image', 'uvTermImage', [
            'selectImage' => __('Select Image', 'uv-core'),
        ]);
    }
});

// Term image: uv_location
add_action('uv_location_add_form_fields', function(){
    ?>
    <div class="form-field">
      <?php wp_nonce_field('uv_location_image_action', 'uv_location_image_nonce'); ?>
      <label for="uv_location_image"><?php _e('Location Image', 'uv-core'); ?></label>
      <input type="hidden" id="uv_location_image" name="uv_location_image" value="">
      <button class="button uv-upload"><?php _e('Select Image', 'uv-core'); ?></button>
      <p class="description"><?php _e('Used on location cards.', 'uv-core'); ?></p>
    </div>
    <?php
});

add_action('uv_location_edit_form_fields', function($term){
    $val = get_term_meta($term->term_id, 'uv_location_image', true);
    $img = $val ? wp_get_attachment_image($val, 'thumbnail') : '';
    ?>
    <tr class="form-field">
      <th scope="row"><label for="uv_location_image"><?php _e('Location Image', 'uv-core'); ?></label></th>
      <td>
        <?php wp_nonce_field('uv_location_image_action', 'uv_location_image_nonce'); ?>
        <input type="hidden" id="uv_location_image" name="uv_location_image" value="<?php echo esc_attr($val); ?>">
        <button class="button uv-upload"><?php _e('Select Image', 'uv-core'); ?></button>
        <div><?php echo $img; ?></div>
      </td>
    </tr>
    <?php
}, 10, 1);

add_action('created_uv_location', function($term_id){
    if(!isset($_POST['uv_location_image_nonce'])) return;
    if(!current_user_can('manage_categories')) return;
    check_admin_referer('uv_location_image_action', 'uv_location_image_nonce');
    if(isset($_POST['uv_location_image'])){
        update_term_meta($term_id, 'uv_location_image', intval($_POST['uv_location_image']));
    }
}, 10, 1);
add_action('edited_uv_location', function($term_id){
    if(!isset($_POST['uv_location_image_nonce'])) return;
    if(!current_user_can('manage_categories')) return;
    check_admin_referer('uv_location_image_action', 'uv_location_image_nonce');
    if(isset($_POST['uv_location_image'])){
        update_term_meta($term_id, 'uv_location_image', intval($_POST['uv_location_image']));
    }
}, 10, 1);

// Shortcodes
function uv_core_locations_grid($atts){
    $a = shortcode_atts(['columns'=>3,'show_links'=>1], $atts);
    $terms = get_terms(['taxonomy'=>'uv_location','hide_empty'=>false]);
    if(is_wp_error($terms) || empty($terms)) return '';
    $cols = intval($a['columns']);
    $out = '<ul class="uv-card-list" style="grid-template-columns:repeat('.$cols.',1fr)">';
    foreach($terms as $t){
        $img_id = get_term_meta($t->term_id, 'uv_location_image', true);
        $img = $img_id ? wp_get_attachment_image($img_id, 'uv_card', false, ['alt'=>esc_attr($t->name)]) : '';
        $url = get_term_link($t);
        $out .= '<li class="uv-card">';
        if($a['show_links']) $out .= '<a href="'.esc_url($url).'">';
        $out .= $img;
        $out .= '<div class="uv-card-body"><strong>'.esc_html($t->name).'</strong></div>';
        if($a['show_links']) $out .= '</a>';
        $out .= '</li>';
    }
    $out .= '</ul>';
    return $out;
}
add_shortcode('uv_locations_grid','uv_core_locations_grid');

function uv_core_posts_news($atts){
    $a = shortcode_atts(['location'=>'','count'=>3], $atts);
    $args = ['post_type'=>'post','posts_per_page'=>intval($a['count'])];
    if($a['location']){
        $args['tax_query'] = [[
            'taxonomy'=>'uv_location',
            'field'=>'slug',
            'terms'=>$a['location']
        ]];
    }
    $q = new WP_Query($args);
    ob_start();
    if($q->have_posts()){
        echo '<ul class="uv-card-list" style="grid-template-columns:repeat(3,1fr)">';
        while($q->have_posts()){ $q->the_post();
            echo '<li class="uv-card"><a href="'.esc_url(get_permalink()).'">';
            if(has_post_thumbnail()) the_post_thumbnail('uv_card',['alt'=>esc_attr(get_the_title())]);
            echo '<div class="uv-card-body"><strong>'.esc_html(get_the_title()).'</strong></div></a></li>';
        }
        echo '</ul>';
        wp_reset_postdata();
    }
    return ob_get_clean();
}
add_shortcode('uv_news','uv_core_posts_news');

function uv_core_activities($atts){
    $a = shortcode_atts(['location'=>'','columns'=>3], $atts);
    $args = ['post_type'=>'uv_activity','posts_per_page'=>-1];
    if($a['location']){
        $args['tax_query'] = [[
            'taxonomy'=>'uv_location','field'=>'slug','terms'=>$a['location']
        ]];
    }
    $q = new WP_Query($args);
    ob_start();
    if($q->have_posts()){
        $cols = intval($a['columns']);
        echo '<ul class="uv-card-list" style="grid-template-columns:repeat('.$cols.',1fr)">';
        while($q->have_posts()){ $q->the_post();
            echo '<li class="uv-card"><a href="'.esc_url(get_permalink()).'">';
            if(has_post_thumbnail()) the_post_thumbnail('uv_card',['alt'=>esc_attr(get_the_title())]);
            echo '<div class="uv-card-body"><strong>'.esc_html(get_the_title()).'</strong>';
            if(has_excerpt()) echo '<div>'.esc_html(get_the_excerpt()).'</div>';
            echo '</div></a></li>';
        }
        echo '</ul>';
        wp_reset_postdata();
    }
    return ob_get_clean();
}
add_shortcode('uv_activities','uv_core_activities');

function uv_core_partners($atts){
    $a = shortcode_atts(['location'=>'','type'=>'','columns'=>4], $atts);
    $args = ['post_type'=>'uv_partner','posts_per_page'=>-1];
    $taxq = [];
    if($a['location']){
        $taxq[] = ['taxonomy'=>'uv_location','field'=>'slug','terms'=>$a['location']];
    }
    if($a['type']){
        $taxq[] = ['taxonomy'=>'uv_partner_type','field'=>'slug','terms'=>$a['type']];
    }
    if($taxq) $args['tax_query'] = $taxq;
    $q = new WP_Query($args);
    ob_start();
    if($q->have_posts()){
        $cols = intval($a['columns']);
        echo '<ul class="uv-card-list" style="grid-template-columns:repeat('.$cols.',1fr)">';
        while($q->have_posts()){ $q->the_post();
            $link = get_post_meta(get_the_ID(), 'uv_partner_url', true);
            echo '<li class="uv-card">';
            echo $link ? '<a href="'.esc_url($link).'" rel="noopener nofollow">' : '<a href="'.esc_url(get_permalink()).'">';
            if(has_post_thumbnail()) the_post_thumbnail('uv_card',['alt'=>esc_attr(get_the_title())]);
            echo '<div class="uv-card-body"><strong>'.esc_html(get_the_title()).'</strong>';
            if(has_excerpt()) echo '<div>'.esc_html(get_the_excerpt()).'</div>';
            echo '</div></a></li>';
        }
        echo '</ul>';
        wp_reset_postdata();
    }
    return ob_get_clean();
}
add_shortcode('uv_partners','uv_core_partners');

// Partner external URL meta box
add_action('add_meta_boxes_uv_partner', function(){
    add_meta_box('uv_partner_url', __('External URL','uv-core'), function($post){
        $val = get_post_meta($post->ID, 'uv_partner_url', true);
        wp_nonce_field('uv_partner_url_action', 'uv_partner_url_nonce');
        echo '<label>'.__('Website','uv-core').'</label><input type="url" style="width:100%" name="uv_partner_url" value="'.esc_attr($val).'">';
    }, 'side');
});
add_action('save_post_uv_partner', function($post_id){
    if(!isset($_POST['uv_partner_url_nonce'])) return;
    if(!current_user_can('edit_post', $post_id)) return;
    check_admin_referer('uv_partner_url_action', 'uv_partner_url_nonce');
    if(isset($_POST['uv_partner_url'])){
        update_post_meta($post_id, 'uv_partner_url', esc_url_raw($_POST['uv_partner_url']));
    }
});

// Related post meta box for experiences
add_action('add_meta_boxes_uv_experience', function(){
    add_meta_box('uv_related_post', __('Related Post','uv-core'), function($post){
        wp_nonce_field('uv_related_post_action', 'uv_related_post_nonce');
        $selected = get_post_meta($post->ID, 'uv_related_post', true);
        wp_dropdown_pages([
            'post_type' => 'post',
            'name' => 'uv_related_post',
            'selected' => $selected,
            'show_option_none' => __('— None —', 'uv-core'),
        ]);
    }, 'side');
});

add_action('save_post_uv_experience', function($post_id){
    if(!isset($_POST['uv_related_post_nonce'])) return;
    if(!current_user_can('edit_post', $post_id)) return;
    check_admin_referer('uv_related_post_action', 'uv_related_post_nonce');
    $val = isset($_POST['uv_related_post']) ? intval($_POST['uv_related_post']) : 0;
    if($val){
        update_post_meta($post_id, 'uv_related_post', $val);
    }else{
        delete_post_meta($post_id, 'uv_related_post');
    }
});

// Block registration
add_action('init', function(){
    register_block_type(__DIR__ . '/blocks/locations-grid', [
        'render_callback' => 'uv_core_locations_grid'
    ]);
    register_block_type(__DIR__ . '/blocks/news', [
        'render_callback' => 'uv_core_posts_news'
    ]);
    register_block_type(__DIR__ . '/blocks/activities', [
        'render_callback' => 'uv_core_activities'
    ]);
    register_block_type(__DIR__ . '/blocks/partners', [
        'render_callback' => 'uv_core_partners'
    ]);
});
