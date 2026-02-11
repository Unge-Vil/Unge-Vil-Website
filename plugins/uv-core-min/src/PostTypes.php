<?php

declare(strict_types=1);

namespace UV\CoreMin;

final class PostTypes
{
    public static function register(): void
    {
        register_post_type('uv_activity', [
            'label' => esc_html__('Aktiviteter', 'uv-core-min'),
            'public' => true,
            'show_in_rest' => true,
            'has_archive' => true,
            'menu_icon' => 'dashicons-forms',
            'supports' => ['title', 'editor', 'thumbnail', 'excerpt'],
            'taxonomies' => ['uv_location', 'uv_activity_type'],
        ]);

        register_post_type('uv_partner', [
            'label' => esc_html__('Partnere', 'uv-core-min'),
            'public' => true,
            'show_in_rest' => true,
            'has_archive' => true,
            'menu_icon' => 'dashicons-heart',
            'supports' => ['title', 'thumbnail', 'excerpt'],
            'taxonomies' => ['uv_location', 'uv_partner_type'],
        ]);

        register_post_type('uv_experience', [
            'label' => esc_html__('Erfaringer', 'uv-core-min'),
            'public' => true,
            'show_in_rest' => true,
            'has_archive' => true,
            'menu_icon' => 'dashicons-awards',
            'supports' => ['title', 'editor', 'thumbnail', 'excerpt', 'custom-fields'],
        ]);
    }
}
