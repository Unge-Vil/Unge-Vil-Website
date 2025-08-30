<?php

add_action('init', function(){
    // Taxonomies
    register_taxonomy('uv_location', ['post','uv_activity','uv_partner'], [
        'label' => esc_html__('Steder', 'uv-core'),
        'public' => true,
        'hierarchical' => true,
        'show_in_rest' => true,
    ]);
    register_taxonomy('uv_activity_type', ['uv_activity'], [
        'label' => esc_html__('Aktivitetstyper', 'uv-core'),
        'public' => true,
        'hierarchical' => true,
        'show_in_rest' => true,
    ]);
    register_taxonomy('uv_partner_type', ['uv_partner'], [
        'label' => esc_html__('Partnertyper', 'uv-core'),
        'public' => true,
        'hierarchical' => true,
        'show_in_rest' => true,
    ]);

    // CPTs
    register_post_type('uv_activity', [
        'label' => esc_html__('Aktiviteter', 'uv-core'),
        'public' => true,
        'show_in_rest' => true,
        'has_archive' => true,
        'menu_icon' => 'dashicons-forms',
        'supports' => ['title','editor','thumbnail','excerpt'],
        'taxonomies' => ['uv_location','uv_activity_type'],
    ]);
    register_post_type('uv_partner', [
        'label' => esc_html__('Partnere', 'uv-core'),
        'public' => true,
        'show_in_rest' => true,
        'has_archive' => true,
        'menu_icon' => 'dashicons-heart',
        'supports' => ['title','thumbnail','excerpt'],
        'taxonomies' => ['uv_location','uv_partner_type'],
    ]);
    register_post_type('uv_experience', [
        'label' => esc_html__('Erfaringer', 'uv-core'),
        'public' => true,
        'show_in_rest' => true,
        'has_archive' => true,
        'menu_icon' => 'dashicons-awards',
        'supports' => ['title','editor','thumbnail','excerpt','custom-fields'],
    ]);
});

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
