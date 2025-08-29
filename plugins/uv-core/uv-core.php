<?php
/**
 * Plugin Name: UV Core
 * Description: CPTs, taxonomies, term images, and lightweight shortcodes.
 * Version: 0.6.1
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: Unge Vil
 * Author URI: https://www.ungevil.no/
 * Text Domain: uv-core
 * Update URI: https://github.com/Unge-Vil/Unge-Vil-Website/plugins/uv-core
 */

if (!defined('ABSPATH')) exit;

$uv_core_min_php = '7.4';
$uv_core_min_wp  = '6.0';
$uv_core_php_ok  = version_compare(PHP_VERSION, $uv_core_min_php, '>=');
$uv_core_wp_ok   = version_compare(get_bloginfo('version'), $uv_core_min_wp, '>=');

if (!$uv_core_php_ok || !$uv_core_wp_ok) {
    add_action('admin_notices', function () use ($uv_core_php_ok, $uv_core_wp_ok, $uv_core_min_php, $uv_core_min_wp) {
        echo '<div class="notice notice-error"><p>';
        if (!$uv_core_php_ok) {
            printf(esc_html__('UV Core requires PHP %s or higher.', 'uv-core'), esc_html($uv_core_min_php));
            echo '<br>';
        }
        if (!$uv_core_wp_ok) {
            printf(esc_html__('UV Core requires WordPress %s or higher.', 'uv-core'), esc_html($uv_core_min_wp));
        }
        echo '</p></div>';
    });

    if (!function_exists('deactivate_plugins')) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }
    deactivate_plugins(plugin_basename(__FILE__));
    return;
}

if (!defined('UV_CORE_VERSION')) {
    define('UV_CORE_VERSION', '0.6.1');
}

$update_checker_path = dirname(__DIR__, 2) . '/plugin-update-checker/plugin-update-checker.php';
if (file_exists($update_checker_path)) {
    if (!class_exists('\\YahnisElsts\\PluginUpdateChecker\\v5\\PucFactory')) {
        require $update_checker_path;
    }
    $uvCoreUpdateChecker = \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
        'https://github.com/Unge-Vil/Unge-Vil-Website/',
        __FILE__,
        'uv-core'
    );
    $uvCoreUpdateChecker->setBranch('main');
    if (method_exists($uvCoreUpdateChecker, 'setPathInsideRepository')) {
        $uvCoreUpdateChecker->setPathInsideRepository('plugins/uv-core');
    }
}

add_filter('block_categories_all', function($categories) {
    array_unshift($categories, [
        'slug'  => 'unge-vil',
        'title' => __('Unge Vil blocks', 'uv-core'),
    ]);
    return $categories;
}, 10, 2);

add_action('plugins_loaded', function(){
    load_plugin_textdomain('uv-core', false, dirname(plugin_basename(__FILE__)) . '/languages');
});

add_action('init', function(){
    // Taxonomies
    register_taxonomy('uv_location', ['post','uv_activity','uv_partner'], [
        'label' => esc_html__('Locations', 'uv-core'),
        'public' => true,
        'hierarchical' => true,
        'show_in_rest' => true,
    ]);
    register_taxonomy('uv_activity_type', ['uv_activity'], [
        'label' => esc_html__('Activity Types', 'uv-core'),
        'public' => true,
        'hierarchical' => true,
        'show_in_rest' => true,
    ]);
    register_taxonomy('uv_partner_type', ['uv_partner'], [
        'label' => esc_html__('Partner Types', 'uv-core'),
        'public' => true,
        'hierarchical' => true,
        'show_in_rest' => true,
    ]);

    // CPTs
    register_post_type('uv_activity', [
        'label' => esc_html__('Activities', 'uv-core'),
        'public' => true,
        'show_in_rest' => true,
        'has_archive' => true,
        'menu_icon' => 'dashicons-forms',
        'supports' => ['title','editor','thumbnail','excerpt'],
        'taxonomies' => ['uv_location','uv_activity_type'],
    ]);
    register_post_type('uv_partner', [
        'label' => esc_html__('Partners', 'uv-core'),
        'public' => true,
        'show_in_rest' => true,
        'has_archive' => true,
        'menu_icon' => 'dashicons-heart',
        'supports' => ['title','thumbnail','excerpt'],
        'taxonomies' => ['uv_location','uv_partner_type'],
    ]);
    register_post_type('uv_experience', [
        'label' => esc_html__('Experiences', 'uv-core'),
        'public' => true,
        'show_in_rest' => true,
        'has_archive' => true,
        'menu_icon' => 'dashicons-awards',
        'supports' => ['title','editor','thumbnail','excerpt','custom-fields'],
    ]);
});

add_action('admin_enqueue_scripts', function($hook){
    $screen = get_current_screen();
    if($screen && $screen->taxonomy === 'uv_location' && in_array($hook, ['edit-tags.php','term.php'])){
        wp_enqueue_media();
        wp_enqueue_script('uv-term-image', plugins_url('assets/term-image.js', __FILE__), ['jquery'], UV_CORE_VERSION, true);
        wp_localize_script('uv-term-image', 'uvTermImage', [
            'selectImage' => esc_html__('Select Image', 'uv-core'),
        ]);
    }

    if($screen && $screen->post_type === 'uv_experience' && in_array($hook, ['post.php','post-new.php'])){
        wp_enqueue_script('select2');
        wp_enqueue_style('select2');
        wp_enqueue_script('uv-admin', plugins_url('assets/admin.js', __FILE__), ['jquery','select2'], UV_CORE_VERSION, true);
    }
});

// Term image: uv_location
add_action('uv_location_add_form_fields', function(){
    ?>
    <div class="form-field">
      <?php wp_nonce_field('uv_location_image_action', 'uv_location_image_nonce'); ?>
      <label for="uv_location_image"><?php esc_html_e('Location Image', 'uv-core'); ?></label>
      <input type="hidden" id="uv_location_image" name="uv_location_image" value="">
      <button class="button uv-upload"><?php esc_html_e('Select Image', 'uv-core'); ?></button>
      <p class="description"><?php esc_html_e('Used on location cards.', 'uv-core'); ?></p>
    </div>
    <?php
});

add_action('uv_location_edit_form_fields', function($term){
    $val = get_term_meta($term->term_id, 'uv_location_image', true);
    $img = $val ? wp_get_attachment_image($val, 'thumbnail') : '';
    ?>
    <tr class="form-field">
      <th scope="row"><label for="uv_location_image"><?php esc_html_e('Location Image', 'uv-core'); ?></label></th>
      <td>
        <?php wp_nonce_field('uv_location_image_action', 'uv_location_image_nonce'); ?>
        <input type="hidden" id="uv_location_image" name="uv_location_image" value="<?php echo esc_attr($val); ?>">
        <button class="button uv-upload"><?php esc_html_e('Select Image', 'uv-core'); ?></button>
        <div><?php echo $img; ?></div>
      </td>
    </tr>
    <?php
}, 10, 1);

// Term page: uv_location
add_action('uv_location_add_form_fields', function(){
    ?>
    <div class="form-field">
      <?php wp_nonce_field('uv_location_page_action', 'uv_location_page_nonce'); ?>
      <label for="uv_location_page"><?php esc_html_e('Location Page', 'uv-core'); ?></label>
      <?php wp_dropdown_pages([
          'post_type' => 'page',
          'name' => 'uv_location_page',
          'id' => 'uv_location_page',
          'show_option_none' => esc_html__('— None —', 'uv-core'),
          'option_none_value' => 0,
      ]); ?>
      <p class="description"><?php esc_html_e('Links will use this page if set.', 'uv-core'); ?></p>
    </div>
    <?php
});

add_action('uv_location_edit_form_fields', function($term){
    $val = get_term_meta($term->term_id, 'uv_location_page', true);
    ?>
    <tr class="form-field">
      <th scope="row"><label for="uv_location_page"><?php esc_html_e('Location Page', 'uv-core'); ?></label></th>
      <td>
        <?php wp_nonce_field('uv_location_page_action', 'uv_location_page_nonce'); ?>
        <?php wp_dropdown_pages([
            'post_type' => 'page',
            'name' => 'uv_location_page',
            'id' => 'uv_location_page',
            'selected' => $val,
            'show_option_none' => esc_html__('— None —', 'uv-core'),
            'option_none_value' => 0,
        ]); ?>
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

add_action('created_uv_location', function($term_id){
    if(!isset($_POST['uv_location_page_nonce'])) return;
    if(!current_user_can('manage_categories')) return;
    check_admin_referer('uv_location_page_action', 'uv_location_page_nonce');
    if(isset($_POST['uv_location_page'])){
        $page_id = absint($_POST['uv_location_page']);
        if($page_id){
            update_term_meta($term_id, 'uv_location_page', $page_id);
        } else {
            delete_term_meta($term_id, 'uv_location_page');
        }
    }
}, 10, 1);
add_action('edited_uv_location', function($term_id){
    if(!isset($_POST['uv_location_page_nonce'])) return;
    if(!current_user_can('manage_categories')) return;
    check_admin_referer('uv_location_page_action', 'uv_location_page_nonce');
    if(isset($_POST['uv_location_page'])){
        $page_id = absint($_POST['uv_location_page']);
        if($page_id){
            update_term_meta($term_id, 'uv_location_page', $page_id);
        } else {
            delete_term_meta($term_id, 'uv_location_page');
        }
    }
}, 10, 1);

// Shortcodes
function uv_core_locations_grid($atts){
    $a = shortcode_atts(['columns'=>3,'show_links'=>1], $atts);
    $terms = get_terms(['taxonomy'=>'uv_location','hide_empty'=>false]);
    if(is_wp_error($terms) || empty($terms)){
        if(is_admin() || (defined('REST_REQUEST') && REST_REQUEST)){
            return '<div class="uv-block-placeholder">'.esc_html__('No locations found.', 'uv-core').'</div>';
        }
        return '';
    }
    $cols = intval($a['columns']);
    $out = '<ul class="uv-card-list" style="grid-template-columns:repeat('.$cols.',1fr)">';
    foreach($terms as $t){
        $img_id = get_term_meta($t->term_id, 'uv_location_image', true);
        $img = $img_id ? wp_get_attachment_image($img_id, 'uv_card', false, ['alt'=>esc_attr($t->name)]) : '';
        $page_id = get_term_meta($t->term_id, 'uv_location_page', true);
        $url = $page_id ? get_permalink($page_id) : get_term_link($t);
        $out .= '<li class="uv-card">';
        if($a['show_links']) $out .= '<a href="'.esc_url($url).'">';
        $out .= $img;
        $out .= '<div class="uv-card-body"><h3>'.esc_html($t->name).'</h3></div>';
        if($a['show_links']) $out .= '</a>';
        $out .= '</li>';
    }
    $out .= '</ul>';
    return $out;
}
add_shortcode('uv_locations_grid','uv_core_locations_grid');

add_action('template_redirect', function(){
    if(is_tax('uv_location')){
        $term = get_queried_object();
        if($term && !is_wp_error($term)){
            $page_id = get_term_meta($term->term_id, 'uv_location_page', true);
            if($page_id){
                $url = get_permalink($page_id);
                if($url){
                    wp_safe_redirect($url, 301);
                    exit;
                }
            }
        }
    }
});

function uv_core_posts_news($atts){
    $a = shortcode_atts(['location'=>'','count'=>3], $atts);
    $args = ['post_type'=>'post','posts_per_page'=>intval($a['count'])];
    if($a['location']){
        $loc = sanitize_title($a['location']);
        $args['tax_query'] = [[
            'taxonomy'=>'uv_location',
            'field'=>'slug',
            'terms'=>$loc
        ]];
    }
    $q = new WP_Query($args);
    ob_start();
    if($q->have_posts()){
        echo '<ul class="uv-card-list" style="grid-template-columns:repeat(3,1fr)">';
        while($q->have_posts()){ $q->the_post();
            echo '<li class="uv-card"><a href="'.esc_url(get_permalink()).'">';
            if(has_post_thumbnail()) the_post_thumbnail('uv_card',['alt'=>esc_attr(get_the_title())]);
            echo '<div class="uv-card-body"><h3>'.esc_html(get_the_title()).'</h3></div></a></li>';
        }
        echo '</ul>';
    } else {
        if(is_admin() || (defined('REST_REQUEST') && REST_REQUEST)){
            echo '<div class="uv-block-placeholder">'.esc_html__('No posts found.', 'uv-core').'</div>';
        }
    }
    wp_reset_postdata();
    return ob_get_clean();
}
add_shortcode('uv_news','uv_core_posts_news');

function uv_core_enqueue_card_grid_style() {
    wp_enqueue_style(
        'uv-card-grid',
        plugins_url('assets/uv-card-grid.css', __FILE__),
        [],
        UV_CORE_VERSION
    );
}

function uv_core_activities($atts){
    uv_core_enqueue_card_grid_style();
    $a = shortcode_atts(['location'=>'','columns'=>4], $atts);
    $cols = max(1, intval($a['columns']));
    $args = ['post_type'=>'uv_activity','posts_per_page'=>-1];
    if($a['location']){
        $loc = sanitize_title($a['location']);
        $args['tax_query'] = [[
            'taxonomy'=>'uv_location','field'=>'slug','terms'=>$loc
        ]];
    }
    $q = new WP_Query($args);
    ob_start();
    if($q->have_posts()){
        echo '<ul class="uv-card-list uv-card-grid" style="--uv-columns:'.esc_attr($cols).'">';
        while($q->have_posts()){ $q->the_post();
            echo '<li class="uv-card"><a href="'.esc_url(get_permalink()).'">';
            if(has_post_thumbnail()) the_post_thumbnail('uv_card',['alt'=>esc_attr(get_the_title())]);
            echo '<div class="uv-card-body"><h3>'.esc_html(get_the_title()).'</h3>';
            if(has_excerpt()) echo '<div>'.esc_html(get_the_excerpt()).'</div>';
            echo '</div></a></li>';
        }
        echo '</ul>';
    } else {
        if(is_admin() || (defined('REST_REQUEST') && REST_REQUEST)){
            echo '<div class="uv-block-placeholder">'.esc_html__('No activities found.', 'uv-core').'</div>';
        }
    }
    wp_reset_postdata();
    return ob_get_clean();
}
add_shortcode('uv_activities','uv_core_activities');

function uv_core_experiences($atts){
    $a = shortcode_atts(['count'=>3], $atts);
    $args = ['post_type'=>'uv_experience','posts_per_page'=>intval($a['count'])];
    $q = new WP_Query($args);
    ob_start();
    if($q->have_posts()){
        echo '<ul class="uv-card-list" style="grid-template-columns:repeat(3,1fr)">';
        while($q->have_posts()){ $q->the_post();
            echo '<li class="uv-card"><a href="'.esc_url(get_permalink()).'">';
            if(has_post_thumbnail()) the_post_thumbnail('uv_card',['alt'=>esc_attr(get_the_title())]);
            echo '<div class="uv-card-body"><h3>'.esc_html(get_the_title()).'</h3>';
            if(has_excerpt()) echo '<div>'.esc_html(get_the_excerpt()).'</div>';
            echo '</div></a></li>';
        }
        echo '</ul>';
    } else {
        if(is_admin() || (defined('REST_REQUEST') && REST_REQUEST)){
            echo '<div class="uv-block-placeholder">'.esc_html__('No experiences found.', 'uv-core').'</div>';
        }
    }
    wp_reset_postdata();
    return ob_get_clean();
}
add_shortcode('uv_experiences','uv_core_experiences');

function uv_core_partners($atts){
    uv_core_enqueue_card_grid_style();
    $a = shortcode_atts(['location'=>'','type'=>'','columns'=>4], $atts);
    $cols = max(1, intval($a['columns']));
    $args = ['post_type'=>'uv_partner','posts_per_page'=>-1];
    $taxq = [];
    $loc = $a['location'] ? sanitize_title($a['location']) : '';
    $type = $a['type'] ? sanitize_title($a['type']) : '';
    if($loc){
        $taxq[] = ['taxonomy'=>'uv_location','field'=>'slug','terms'=>$loc];
    }
    if($type){
        $taxq[] = ['taxonomy'=>'uv_partner_type','field'=>'slug','terms'=>$type];
    }
    if($taxq) $args['tax_query'] = $taxq;
    $q = new WP_Query($args);
    ob_start();
    if($q->have_posts()){
        echo '<ul class="uv-card-list uv-card-grid" style="--uv-columns:'.esc_attr($cols).'">';
        while($q->have_posts()){ $q->the_post();
            $link = get_post_meta(get_the_ID(), 'uv_partner_url', true);
            $display = get_post_meta(get_the_ID(), 'uv_partner_display', true);
            if(!$display) $display = has_post_thumbnail() ? 'circle_title' : 'title_only';
            if(!has_post_thumbnail()) $display = 'title_only';
            $classes = 'uv-card uv-partner uv-partner--'.esc_attr($display);
            echo '<li class="'.$classes.'">';
            echo $link
                ? '<a href="' . esc_url( $link ) . '" target="_blank" rel="noopener nofollow">'
                : '<a href="' . esc_url( get_permalink() ) . '" rel="noopener">';
            $fallback = '<span class="uv-partner-icon"></span>';
            $render_thumb = function($attrs = []) use ($fallback){
                if(has_post_thumbnail()){
                    $attrs = wp_parse_args($attrs, ['alt'=>esc_attr(get_the_title())]);
                    the_post_thumbnail('uv_card', $attrs);
                } else {
                    echo $fallback;
                }
            };
            switch($display){
                case 'logo_only':
                    $render_thumb();
                    break;
                case 'circle_title':
                    $render_thumb(['class'=>'is-circle']);
                    echo '<div class="uv-card-body"><strong>'.esc_html(get_the_title()).'</strong>';
                    $excerpt = get_the_excerpt();
                    if ( $excerpt ) {
                        echo '<div>' . esc_html( $excerpt ) . '</div>';
                    }
                    echo '</div>';
                    break;
                case 'title_only':
                    echo '<div class="uv-card-body"><strong>'.esc_html(get_the_title()).'</strong>';
                    $excerpt = get_the_excerpt();
                    if ( $excerpt ) {
                        echo '<div>' . esc_html( $excerpt ) . '</div>';
                    }
                    echo '</div>';
                    break;
                case 'logo_title':
                default:
                    $render_thumb();
                    echo '<div class="uv-card-body"><strong>' . esc_html( get_the_title() ) . '</strong>';
                    $excerpt = get_the_excerpt();
                    if ( $excerpt ) {
                        echo '<div>' . esc_html( $excerpt ) . '</div>';
                    }
                    echo '</div>';
                    break;
            }
            echo '</a></li>';
        }
        echo '</ul>';
    } else {
        if(is_admin() || (defined('REST_REQUEST') && REST_REQUEST)){
            echo '<div class="uv-block-placeholder">'.esc_html__('No partners found.', 'uv-core').'</div>';
        }
    }
    wp_reset_postdata();
    return ob_get_clean();
}
add_shortcode('uv_partners','uv_core_partners');

// Partner meta boxes
add_action('add_meta_boxes_uv_partner', function(){
    add_meta_box('uv_partner_url', esc_html__('External URL', 'uv-core'), function($post){
        $val = get_post_meta($post->ID, 'uv_partner_url', true);
        wp_nonce_field('uv_partner_url_action', 'uv_partner_url_nonce');
        echo '<p><label>' . esc_html__('Website', 'uv-core') . '</label><input type="url" style="width:100%" name="uv_partner_url" value="' . esc_attr($val) . '"></p>';
    }, 'uv_partner', 'side', 'high');
    add_meta_box('uv_partner_display', esc_html__('Display', 'uv-core'), function($post){
        $val = get_post_meta($post->ID, 'uv_partner_display', true);
        if(!$val) {
            $val = has_post_thumbnail($post->ID) ? 'circle_title' : 'title_only';
        }
        wp_nonce_field('uv_partner_display_action', 'uv_partner_display_nonce');
        echo '<p><label class="screen-reader-text" for="uv_partner_display">' . esc_html__('Display', 'uv-core') . '</label>';
        echo '<select id="uv_partner_display" name="uv_partner_display">';
        $opts = [
            'logo_only'   => esc_html__('Logo only', 'uv-core'),
            'logo_title'  => esc_html__('Logo and title', 'uv-core'),
            'circle_title'=> esc_html__('Circle & title', 'uv-core'),
            'title_only'  => esc_html__('Title only', 'uv-core'),
        ];
        foreach($opts as $k => $label){
            echo '<option value="' . esc_attr($k) . '"' . selected($val, $k, false) . '>' . $label . '</option>';
        }
        echo '</select></p>';
    }, 'uv_partner', 'side', 'high');
});
add_action('save_post_uv_partner', function($post_id){
    if(!current_user_can('edit_post', $post_id)) return;

    if(isset($_POST['uv_partner_url_nonce'])){
        check_admin_referer('uv_partner_url_action', 'uv_partner_url_nonce');
        if(isset($_POST['uv_partner_url'])){
            update_post_meta($post_id, 'uv_partner_url', esc_url_raw($_POST['uv_partner_url']));
        }
    }

    if(isset($_POST['uv_partner_display_nonce'])){
        check_admin_referer('uv_partner_display_action', 'uv_partner_display_nonce');
        if(isset($_POST['uv_partner_display'])){
            $allowed = ['logo_only','logo_title','circle_title','title_only'];
            $val = in_array($_POST['uv_partner_display'],$allowed) ? $_POST['uv_partner_display'] : 'circle_title';
            update_post_meta($post_id, 'uv_partner_display', $val);
        }
    }
});

function uv_core_sanitize_partner_display($val){
    $allowed = ['logo_only','logo_title','circle_title','title_only'];
    return in_array($val, $allowed, true) ? $val : 'circle_title';
}

add_action('init', function(){
    register_post_meta('uv_partner', 'uv_partner_url', [
        'single' => true,
        'type' => 'string',
        'show_in_rest' => true,
        'default' => '',
        'sanitize_callback' => 'esc_url_raw',
        'auth_callback' => function(){ return current_user_can('edit_posts'); },
    ]);
    register_post_meta('uv_partner', 'uv_partner_display', [
        'single' => true,
        'type' => 'string',
        'show_in_rest' => true,
        'default' => 'circle_title',
        'sanitize_callback' => 'uv_core_sanitize_partner_display',
        'auth_callback' => function(){ return current_user_can('edit_posts'); },
    ]);
});

add_action('enqueue_block_editor_assets', function(){
    $screen = get_current_screen();
    if($screen && $screen->base === 'post' && $screen->post_type === 'uv_partner'){
        wp_enqueue_script(
            'uv-partner-meta',
            plugins_url('assets/partner-meta.js', __FILE__),
            ['wp-plugins','wp-edit-post','wp-element','wp-components','wp-data','wp-compose','wp-i18n'],
            UV_CORE_VERSION,
            true
        );
    }
});

// Related post meta box for experiences
add_action('add_meta_boxes_uv_experience', function(){
    add_meta_box('uv_related_post', esc_html__('Related Post','uv-core'), function($post){
        wp_nonce_field('uv_related_post_action', 'uv_related_post_nonce');
        $selected = get_post_meta($post->ID, 'uv_related_post', true);
        $posts    = get_posts([
            'post_type'      => 'post',
            'posts_per_page' => -1,
        ]);

        $dropdown  = '<select name="uv_related_post" class="uv-post-select">';
        $dropdown .= '<option value="0">' . esc_html__('— None —', 'uv-core') . '</option>';
        foreach ($posts as $p) {
            $dropdown .= sprintf(
                '<option value="%1$s"%2$s>%3$s</option>',
                esc_attr($p->ID),
                selected($selected, $p->ID, false),
                esc_html(get_the_title($p))
            );
        }
        $dropdown .= '</select>';
        echo $dropdown;
    }, 'uv_experience', 'side', 'high');
});

add_action('save_post_uv_experience', function($post_id){
    if(!isset($_POST['uv_related_post_nonce'])) return;
    if(!current_user_can('edit_post', $post_id)) return;
    check_admin_referer('uv_related_post_action', 'uv_related_post_nonce');
    $val = isset($_POST['uv_related_post']) ? absint($_POST['uv_related_post']) : 0;
    if($val){
        update_post_meta($post_id, 'uv_related_post', $val);
    }else{
        delete_post_meta($post_id, 'uv_related_post');
    }
});

// Experience users meta box
add_action('add_meta_boxes_uv_experience', function(){
    add_meta_box('uv_experience_users', esc_html__('Team Members','uv-core'), function($post){
        wp_nonce_field('uv_experience_users_action', 'uv_experience_users_nonce');
        $selected = get_post_meta($post->ID, 'uv_experience_users', false);
        $dropdown = wp_dropdown_users([
            'name'             => 'uv_experience_users[]',
            'id'               => 'uv_experience_users',
            'selected'         => $selected,
            'include_selected' => true,
            'multi'            => true,
            'show'             => 'display_name',
            'number'           => 50,
            'class'            => 'uv-user-select',
            'echo'             => false,
        ]);
        echo str_replace('<select', '<select multiple="multiple" style="width:100%;"', $dropdown);
    }, 'uv_experience', 'side', 'high');
});

add_action('save_post_uv_experience', function($post_id){
    if(!isset($_POST['uv_experience_users_nonce'])) return;
    if(!current_user_can('edit_post', $post_id)) return;
    check_admin_referer('uv_experience_users_action', 'uv_experience_users_nonce');
    $user_ids = isset($_POST['uv_experience_users']) ? array_filter(array_map('absint', (array)$_POST['uv_experience_users'])) : [];
    delete_post_meta($post_id, 'uv_experience_users');
    foreach($user_ids as $uid){
        add_post_meta($post_id, 'uv_experience_users', $uid);
    }
});

// Register meta for querying
add_action('init', function(){
    register_post_meta('uv_experience', 'uv_related_post', [
        'single' => true,
        'type' => 'integer',
        'show_in_rest' => true,
        'sanitize_callback' => 'absint',
        'auth_callback' => function(){ return current_user_can('edit_posts'); },
    ]);
    register_post_meta('uv_experience', 'uv_experience_users', [
        'single' => false,
        'type' => 'integer',
        'show_in_rest' => true,
        'sanitize_callback' => 'absint',
        'auth_callback' => function(){ return current_user_can('edit_posts'); },
    ]);
});

function uv_core_get_experiences_for_user($user_id){
    return get_posts([
        'post_type' => 'uv_experience',
        'posts_per_page' => -1,
        'meta_query' => [[
            'key' => 'uv_experience_users',
            'value' => absint($user_id),
            'compare' => '=',
        ]]
    ]);
}

// Block registration
add_action('init', function(){
    register_block_type(__DIR__ . '/blocks/locations-grid', [
        'render_callback' => 'uv_core_locations_grid'
    ]);
    register_block_type(__DIR__ . '/blocks/news', [
        'render_callback' => 'uv_core_posts_news'
    ]);
    register_block_type(__DIR__ . '/blocks/experiences', [
        'render_callback' => 'uv_core_experiences'
    ]);
    if (function_exists('wp_set_script_translations')) {
        wp_set_script_translations('uv-experiences-editor-script', 'uv-core', plugin_dir_path(__FILE__) . 'languages');
    }
    register_block_type(__DIR__ . '/blocks/activities', [
        'render_callback' => 'uv_core_activities'
    ]);
    register_block_type(__DIR__ . '/blocks/partners', [
        'render_callback' => 'uv_core_partners'
    ]);
});
