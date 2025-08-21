<?php
/**
 * Plugin Name: UV Events Bridge
 * Description: Adds uv_location taxonomy to The Events Calendar events and provides an upcoming events shortcode.
 * Version: 0.1.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Text Domain: uv-events-bridge
 */
if (!defined('ABSPATH')) exit;

add_action('init', function(){
    if(post_type_exists('tribe_events')){
        register_taxonomy_for_object_type('uv_location','tribe_events');
    }
});

function uv_upcoming_events_sc($atts){
    if(!post_type_exists('tribe_events')) return '';
    $a = shortcode_atts(['location'=>'','count'=>5], $atts);
    $args = [
        'post_type'=>'tribe_events',
        'posts_per_page'=>intval($a['count']),
        'post_status'=>'publish',
        'meta_key'=>'_EventStartDate',
        'orderby'=>'meta_value',
        'order'=>'ASC',
        'meta_query'=>[
            ['key'=>'_EventStartDate','value'=>current_time('mysql'),'compare'=>'>=','type'=>'DATETIME']
        ]
    ];
    if($a['location']){
        $args['tax_query'] = [[
            'taxonomy'=>'uv_location','field'=>'slug','terms'=>$a['location']
        ]];
    }
    $q = new WP_Query($args);
    ob_start();
    if($q->have_posts()){
        echo '<ul class="uv-card-list" style="grid-template-columns:repeat(1,1fr)">';
        while($q->have_posts()){ $q->the_post();
            $date = function_exists('tribe_get_start_date') ? tribe_get_start_date(get_the_ID(), false, 'j M Y H:i') : '';
            echo '<li class="uv-card"><a href="'.esc_url(get_permalink()).'">';
            if(has_post_thumbnail()) the_post_thumbnail('uv_card',['alt'=>esc_attr(get_the_title())]);
            echo '<div class="uv-card-body"><strong>'.esc_html(get_the_title()).'</strong>';
            if($date) echo '<div>'.esc_html($date).'</div>';
            echo '</div></a></li>';
        }
        echo '</ul>';
        wp_reset_postdata();
    }
    return ob_get_clean();
}
add_shortcode('uv_upcoming_events','uv_upcoming_events_sc');

// i18n
add_action('plugins_loaded', function(){
    load_plugin_textdomain('uv-events-bridge', false, dirname(plugin_basename(__FILE__)) . '/languages');
});
