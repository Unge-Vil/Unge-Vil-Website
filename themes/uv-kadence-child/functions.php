<?php
/**
 * UV Kadence Child theme functions
 */
add_action('wp_enqueue_scripts', function() {
    wp_enqueue_style('uv-child', get_stylesheet_uri(), [], wp_get_theme()->get('Version'));
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
