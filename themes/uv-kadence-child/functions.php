<?php
/**
 * UV Kadence Child theme functions
 */
add_action('wp_enqueue_scripts', function() {
    wp_enqueue_style('uv-child', get_stylesheet_uri(), [], wp_get_theme()->get('Version'));
    
    // Poppins (Google Fonts) with display swap
    wp_enqueue_style('uv-fonts', 'https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap', [], null);
    wp_enqueue_style('uv-child-extra', get_stylesheet_directory_uri() . '/assets/css/theme.css', ['uv-child'], wp_get_theme()->get('Version'));
});

// Image sizes for cards/avatars
add_action('after_setup_theme', function() {
    add_image_size('uv_card', 800, 600, true);
    add_image_size('uv_avatar', 512, 512, true);
});

// Make theme translation-ready
add_action('after_setup_theme', function() {
    load_child_theme_textdomain('uv-kadence-child', get_stylesheet_directory() . '/languages');
});

// Register block patterns for shortcode blocks
add_action('init', function() {
    if (!function_exists('register_block_pattern')) {
        return;
    }
    $patterns = [
        'locations-grid' => __('Locations Grid', 'uv-kadence-child'),
        'news-list'      => __('News List', 'uv-kadence-child'),
        'activities'     => __('Activities', 'uv-kadence-child'),
        'partners'       => __('Partners', 'uv-kadence-child'),
        'team-grid'      => __('Team Grid', 'uv-kadence-child'),
    ];
    foreach ($patterns as $slug => $title) {
        register_block_pattern(
            'uv-kadence-child/' . $slug,
            [
                'title'   => $title,
                'content' => include get_theme_file_path('patterns/' . $slug . '.php'),
            ]
        );
    }
});

