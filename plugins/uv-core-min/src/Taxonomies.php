<?php

declare(strict_types=1);

namespace UV\CoreMin;

final class Taxonomies
{
    public static function register(): void
    {
        register_taxonomy('uv_location', ['post', 'uv_activity', 'uv_partner'], [
            'label' => esc_html__('Steder', 'uv-core-min'),
            'public' => true,
            'hierarchical' => true,
            'show_in_rest' => true,
        ]);

        register_taxonomy('uv_activity_type', ['uv_activity'], [
            'label' => esc_html__('Aktivitetstyper', 'uv-core-min'),
            'public' => true,
            'hierarchical' => true,
            'show_in_rest' => true,
        ]);

        register_taxonomy('uv_partner_type', ['uv_partner'], [
            'label' => esc_html__('Partnertyper', 'uv-core-min'),
            'public' => true,
            'hierarchical' => true,
            'show_in_rest' => true,
        ]);

        register_taxonomy('uv_position', null, [
            'label' => esc_html__('Stillinger', 'uv-core-min'),
            'public' => false,
            'show_ui' => true,
            'hierarchical' => false,
            'show_in_rest' => true,
            'meta_box_cb' => false,
            'show_in_menu' => 'uv-control-panel',
            'capabilities' => [
                'manage_terms' => 'manage_categories',
                'edit_terms' => 'manage_categories',
                'delete_terms' => 'manage_categories',
                'assign_terms' => 'edit_posts',
            ],
        ]);
    }
}
