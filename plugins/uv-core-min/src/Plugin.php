<?php

declare(strict_types=1);

namespace UV\CoreMin;

use UV\CoreMin\Admin\Notices;
use UV\CoreMin\Admin\TermFields;

final class Plugin
{
    private static string $pluginFile;

    public static function boot(string $pluginFile): void
    {
        self::$pluginFile = $pluginFile;

        register_activation_hook(self::$pluginFile, [self::class, 'activate']);
        register_deactivation_hook(self::$pluginFile, [self::class, 'deactivate']);

        add_action('init', [PostTypes::class, 'register']);
        add_action('init', [Taxonomies::class, 'register']);
        add_action('init', [Meta::class, 'register']);
        add_action('admin_notices', [Notices::class, 'render']);

        TermFields::register();
    }

    public static function activate(): void
    {
        Taxonomies::register();
        PostTypes::register();
        flush_rewrite_rules();
    }

    public static function deactivate(): void
    {
        flush_rewrite_rules();
    }
}
