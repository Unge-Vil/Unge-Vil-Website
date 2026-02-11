<?php

declare(strict_types=1);

namespace UV\CoreMin\Admin;

final class Notices
{
    public static function render(): void
    {
        if (!current_user_can('activate_plugins')) {
            return;
        }

        self::renderLegacyActiveNotice();
        self::renderAcfNotice();
    }

    private static function renderLegacyActiveNotice(): void
    {
        include_once ABSPATH . 'wp-admin/includes/plugin.php';

        $legacy = [];
        if (is_plugin_active('uv-core/uv-core.php')) {
            $legacy[] = 'UV Core';
        }
        if (is_plugin_active('uv-people/uv-people.php')) {
            $legacy[] = 'UV People';
        }

        if ($legacy === []) {
            return;
        }

        echo '<div class="notice notice-info"><p>';
        printf(
            esc_html__('UV Core Min is active while legacy plugin(s) are still active: %s. This is supported during migration.', 'uv-core-min'),
            esc_html(implode(', ', $legacy))
        );
        echo '</p></div>';
    }

    private static function renderAcfNotice(): void
    {
        if (function_exists('acf_get_field_groups')) {
            return;
        }

        echo '<div class="notice notice-warning"><p>';
        echo esc_html__('UV Core Min recommends Advanced Custom Fields (ACF) for field UI management. Core data structures still work without ACF.', 'uv-core-min');
        echo '</p></div>';
    }
}
