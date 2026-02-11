<?php

declare(strict_types=1);

namespace UV\CoreMin;

final class Meta
{
    public static function register(): void
    {
        register_term_meta('uv_location', 'uv_location_image', [
            'type' => 'integer',
            'single' => true,
            'sanitize_callback' => 'absint',
            'show_in_rest' => true,
        ]);

        register_term_meta('uv_location', 'uv_location_page', [
            'type' => 'integer',
            'single' => true,
            'sanitize_callback' => 'absint',
            'show_in_rest' => true,
        ]);

        register_term_meta('uv_location', 'uv_member_order', [
            'type' => 'array',
            'single' => true,
            'show_in_rest' => true,
            'auth_callback' => static fn (): bool => current_user_can('manage_categories'),
        ]);

        register_term_meta('uv_location', 'uv_primary_team', [
            'type' => 'array',
            'single' => true,
            'show_in_rest' => true,
            'auth_callback' => static fn (): bool => current_user_can('manage_categories'),
        ]);

        register_term_meta('uv_position', 'uv_rank_weight', [
            'type' => 'number',
            'single' => true,
            'sanitize_callback' => 'intval',
            'show_in_rest' => true,
        ]);

        self::registerPostMeta('uv_partner', 'uv_partner_url', 'string', 'esc_url_raw');
        self::registerPostMeta('uv_partner', 'uv_partner_display', 'string', [self::class, 'sanitizePartnerDisplay']);
        self::registerPostMeta('uv_activity', 'uv_external_url', 'string', 'esc_url_raw');
        self::registerPostMeta('post', 'uv_related_post', 'integer', 'absint');
        self::registerPostMeta('uv_experience', 'uv_experience_org', 'string', 'sanitize_text_field');
        self::registerPostMeta('uv_experience', 'uv_experience_dates', 'string', 'sanitize_text_field');
        self::registerPostMeta('uv_experience', 'uv_experience_users', 'integer', 'absint', false);
        self::registerPostMeta('uv_experience', 'uv_experience_partners', 'integer', 'absint', false);
    }

    private static function registerPostMeta(
        string $postType,
        string $key,
        string $type,
        $sanitizeCallback,
        bool $single = true
    ): void {
        register_post_meta($postType, $key, [
            'single' => $single,
            'type' => $type,
            'sanitize_callback' => $sanitizeCallback,
            'show_in_rest' => true,
            'auth_callback' => static fn (): bool => current_user_can('edit_posts'),
        ]);
    }

    public static function sanitizePartnerDisplay($value): string
    {
        $allowed = ['logo_title', 'logo_only', 'circle_title', 'title_only'];
        $value = sanitize_key((string) $value);

        return in_array($value, $allowed, true) ? $value : 'logo_title';
    }
}
