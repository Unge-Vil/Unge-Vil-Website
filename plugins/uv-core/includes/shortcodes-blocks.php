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
        echo '<ul class="uv-card-list uv-card-grid columns-' . $cols . '">';
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
    if ( ! is_array( $atts ) ) {
        $atts = [];
    }

    $a = shortcode_atts(['count'=>3, 'layout' => 'grid'], $atts);
    $count  = max( 1, intval( $a['count'] ) );
    $layout = in_array( $a['layout'], ['list', 'grid', 'timeline'], true ) ? $a['layout'] : 'grid';
    $args = ['post_type'=>'uv_experience','posts_per_page'=>$count,'no_found_rows'=>true];
    $q = new WP_Query($args);
    ob_start();
    if($q->have_posts()){
        $classes = [ 'uv-experiences', 'uv-experiences--' . $layout ];
        if ( 'grid' === $layout ) {
            $classes[] = 'uv-card-list';
            $classes[] = 'uv-card-grid';
            $classes[] = 'columns-3';
        } elseif ( 'timeline' === $layout ) {
            $classes[] = 'uv-card-list';
        }

        echo '<ul class="' . esc_attr( implode( ' ', $classes ) ) . '">';
        while($q->have_posts()){ $q->the_post();
            $has_thumb = has_post_thumbnail();
            $classes   = $has_thumb ? 'uv-card' : 'uv-card uv-card--experience';
            echo '<li class="' . $classes . '"><a href="' . esc_url( get_permalink() ) . '">';
            if($has_thumb){
                the_post_thumbnail('uv_card',['alt'=>esc_attr(get_the_title())]);
            } else {
                echo '<div class="uv-card-icon" aria-hidden="true"><svg viewBox="0 0 24 24" role="img" focusable="false" xmlns="http://www.w3.org/2000/svg"><path d="M12 3.75a4.5 4.5 0 1 1 0 9 4.5 4.5 0 0 1 0-9Z" fill="none" stroke="currentColor" stroke-width="1.5"/><path d="M5.25 19.5a6.75 6.75 0 0 1 13.5 0" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg></div>';
            }
            echo '<div class="uv-card-body"><h3>'.esc_html(get_the_title()).'</h3>';
            $org   = get_post_meta(get_the_ID(), 'uv_experience_org', true);
            $dates = get_post_meta(get_the_ID(), 'uv_experience_dates', true);
            if($org || $dates){
                echo '<div class="uv-card-meta">';
                if($org)  echo '<div class="uv-card-meta__org">'.esc_html($org).'</div>';
                if($dates) echo '<div class="uv-card-meta__dates">'.esc_html($dates).'</div>';
                echo '</div>';
            }
            if(has_excerpt()) echo '<div class="uv-card-excerpt">'.esc_html(get_the_excerpt()).'</div>';
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
    $a = shortcode_atts(['location'=>'','type'=>'','columns'=>4,'showLocations'=>false], $atts);
    $cols = max(1, intval($a['columns']));
    $show_locations = ! empty( $a['showLocations'] );
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
        echo '<ul class="uv-card-list uv-card-grid columns-' . $cols . '">';
        while($q->have_posts()){ $q->the_post();
            $link     = get_post_meta( get_the_ID(), 'uv_partner_url', true );
            $display  = get_post_meta( get_the_ID(), 'uv_partner_display', true );
            if ( ! $display ) {
                $display = has_post_thumbnail() ? 'circle_title' : 'title_only';
            }
            if ( ! has_post_thumbnail() ) {
                $display = 'title_only';
            }
            $classes  = 'uv-card uv-partner uv-partner--' . esc_attr( $display );
            $external = ! empty( $link );
            echo '<li class="' . $classes . '">';
            if ( $external ) {
                /* translators: %s: Partner name */
                $new_tab_label = sprintf( esc_html__( '%s (opens in a new tab)', 'uv-core' ), get_the_title() );
                echo '<a href="' . esc_url( $link ) . '" target="_blank" rel="noopener nofollow" aria-label="' . esc_attr( $new_tab_label ) . '" title="' . esc_attr( $new_tab_label ) . '">';
            } else {
                echo '<a href="' . esc_url( get_permalink() ) . '" rel="noopener">';
            }
            $fallback = '<span class="uv-partner-icon"></span>';
            $render_thumb = function($attrs = []) use ($fallback){
                if(has_post_thumbnail()){
                    $attrs = wp_parse_args($attrs, ['alt'=>esc_attr(get_the_title())]);
                    the_post_thumbnail('uv_card', $attrs);
                } else {
                    echo $fallback;
                }
            };
            $loc_html = '';
            if ( $show_locations ) {
                $loc_terms = get_the_terms( get_the_ID(), 'uv_location' );
                if ( $loc_terms && ! is_wp_error( $loc_terms ) ) {
                    $loc_html .= '<div class="uv-partner-locations">';
                    foreach ( $loc_terms as $loc_term ) {
                        $loc_html .= '<span class="uv-location-pill">' . esc_html( $loc_term->name ) . '</span>';
                    }
                    $loc_html .= '</div>';
                }
            }
            switch($display){
                case 'logo_only':
                    $render_thumb();
                    if ( $loc_html ) {
                        echo '<div class="uv-card-body">' . $loc_html . '</div>';
                    }
                    break;
                case 'circle_title':
                    $render_thumb(['class'=>'is-circle']);
                    echo '<div class="uv-card-body"><strong>'.esc_html(get_the_title()).'</strong>';
                    $excerpt = get_the_excerpt();
                    if ( $excerpt ) {
                        echo '<div>' . esc_html( $excerpt ) . '</div>';
                    }
                    echo $loc_html;
                    echo '</div>';
                    break;
                case 'title_only':
                    echo '<div class="uv-card-body"><strong>'.esc_html(get_the_title()).'</strong>';
                    $excerpt = get_the_excerpt();
                    if ( $excerpt ) {
                        echo '<div>' . esc_html( $excerpt ) . '</div>';
                    }
                    echo $loc_html;
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
                    echo $loc_html;
                    echo '</div>';
                    break;
            }
            if ( $external ) {
                echo '<span class="uv-new-tab-icon" aria-hidden="true">&#8599;</span>';
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
