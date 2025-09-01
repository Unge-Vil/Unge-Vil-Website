<?php

add_filter('block_categories_all', function($categories) {
    array_unshift($categories, [
        'slug'  => 'unge-vil',
        'title' => __('Unge Vil-blokker', 'uv-core'),
    ]);
    return $categories;
}, 10, 2);

function uv_core_locations_grid($atts){
    $a = shortcode_atts(['columns'=>3,'show_links'=>1], $atts);
    $terms = get_terms(['taxonomy'=>'uv_location','hide_empty'=>false]);
    if(is_wp_error($terms) || empty($terms)){
        if(is_admin() || (defined('REST_REQUEST') && REST_REQUEST)){
            return '<div class="uv-block-placeholder">'.esc_html__('Ingen steder funnet.', 'uv-core').'</div>';
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

function uv_core_posts_news($atts){
    $a = shortcode_atts(['location'=>'','count'=>3], $atts);
    $args = ['post_type'=>'post','posts_per_page'=>intval($a['count']),'no_found_rows'=>true];
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
            echo '<div class="uv-block-placeholder">'.esc_html__('Ingen innlegg funnet.', 'uv-core').'</div>';
        }
    }
    wp_reset_postdata();
    return ob_get_clean();
}
add_shortcode('uv_news','uv_core_posts_news');

function uv_core_activities($atts){
    $a = shortcode_atts(['location'=>'','columns'=>4], $atts);
    $cols = max(1, intval($a['columns']));
    $args = ['post_type'=>'uv_activity','posts_per_page'=>-1,'no_found_rows'=>true];
    if($a['location']){
        $loc = sanitize_title($a['location']);
        $args['tax_query'] = [[
            'taxonomy'=>'uv_location','field'=>'slug','terms'=>$loc
        ]];
    }
    $q = new WP_Query($args);
    ob_start();
    if($q->have_posts()){
        echo '<ul class="uv-card-list uv-card-grid">';
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
            echo '<div class="uv-block-placeholder">'.esc_html__('Ingen aktiviteter funnet.', 'uv-core').'</div>';
        }
    }
    wp_reset_postdata();
    return ob_get_clean();
}
add_shortcode('uv_activities','uv_core_activities');

function uv_core_experiences($atts){
    $a = shortcode_atts(['count'=>3], $atts);
    $args = ['post_type'=>'uv_experience','posts_per_page'=>intval($a['count']),'no_found_rows'=>true];
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
            echo '<div class="uv-block-placeholder">'.esc_html__('Ingen erfaringer funnet.', 'uv-core').'</div>';
        }
    }
    wp_reset_postdata();
    return ob_get_clean();
}
add_shortcode('uv_experiences','uv_core_experiences');

function uv_core_partners($atts){
    $a = shortcode_atts(['location'=>'','type'=>'','columns'=>4], $atts);
    $cols = max(1, intval($a['columns']));
    $args = ['post_type'=>'uv_partner','posts_per_page'=>-1,'no_found_rows'=>true];
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
        echo '<ul class="uv-card-list uv-card-grid">';
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
            echo '<div class="uv-block-placeholder">'.esc_html__('Ingen partnere funnet.', 'uv-core').'</div>';
        }
    }
    wp_reset_postdata();
    return ob_get_clean();
}
add_shortcode('uv_partners','uv_core_partners');

add_action('init', function(){
    register_block_type(__DIR__ . '/../blocks/locations-grid', [
        'render_callback' => 'uv_core_locations_grid'
    ]);
    register_block_type(__DIR__ . '/../blocks/news', [
        'render_callback' => 'uv_core_posts_news'
    ]);
    register_block_type(__DIR__ . '/../blocks/experiences', [
        'render_callback' => 'uv_core_experiences'
    ]);
    register_block_type(__DIR__ . '/../blocks/activities', [
        'render_callback' => 'uv_core_activities'
    ]);
    register_block_type(__DIR__ . '/../blocks/partners', [
        'render_callback' => 'uv_core_partners'
    ]);
    if (function_exists('wp_set_script_translations')) {
        $lang_dir = dirname(__DIR__) . '/languages';
        wp_set_script_translations('uv-locations-grid-editor-script', 'uv-core', $lang_dir);
        wp_set_script_translations('uv-news-editor-script', 'uv-core', $lang_dir);
        wp_set_script_translations('uv-experiences-editor-script', 'uv-core', $lang_dir);
        wp_set_script_translations('uv-activities-editor-script', 'uv-core', $lang_dir);
        wp_set_script_translations('uv-partners-editor-script', 'uv-core', $lang_dir);
    }
});
