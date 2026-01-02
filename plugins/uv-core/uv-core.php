<?php
/**
 * Plugin Name: UV Core
 * Description: CPTs, taxonomies, term images, and lightweight shortcodes.
 * Version: 0.8.5
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: Unge Vil
 * Author URI: https://www.ungevil.no/
 * Text Domain: uv-core
 * Update URI: https://github.com/Unge-Vil/Unge-Vil-Website/plugins/uv-core
 */

if (!defined('ABSPATH')) exit;

$uv_core_min_php = '7.4';
$uv_core_min_wp  = '6.0';
$uv_core_php_ok  = version_compare(PHP_VERSION, $uv_core_min_php, '>=');
$uv_core_wp_ok   = version_compare(get_bloginfo('version'), $uv_core_min_wp, '>=');

if (!$uv_core_php_ok || !$uv_core_wp_ok) {
    add_action('admin_notices', function () use ($uv_core_php_ok, $uv_core_wp_ok, $uv_core_min_php, $uv_core_min_wp) {
        echo '<div class="notice notice-error"><p>';
        if (!$uv_core_php_ok) {
            printf(esc_html__('UV Core requires PHP %s or higher.', 'uv-core'), esc_html($uv_core_min_php));
            echo '<br>';
        }
        if (!$uv_core_wp_ok) {
            printf(esc_html__('UV Core requires WordPress %s or higher.', 'uv-core'), esc_html($uv_core_min_wp));
        }
        echo '</p></div>';
    });

    if (!function_exists('deactivate_plugins')) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }
    deactivate_plugins(plugin_basename(__FILE__));
    return;
}

if (!defined('UV_CORE_VERSION')) {
define('UV_CORE_VERSION', '0.8.5');
}

$update_checker_path = dirname(__DIR__, 2) . '/plugin-update-checker/plugin-update-checker.php';
if (file_exists($update_checker_path)) {
    if (!class_exists('\\YahnisElsts\\PluginUpdateChecker\\v5\\PucFactory')) {
        require $update_checker_path;
    }
    $uvCoreUpdateChecker = \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
        'https://github.com/Unge-Vil/Unge-Vil-Website/',
        __FILE__,
        'uv-core'
    );
    $uvCoreUpdateChecker->setBranch('main');
    if (method_exists($uvCoreUpdateChecker, 'setPathInsideRepository')) {
        $uvCoreUpdateChecker->setPathInsideRepository('plugins/uv-core');
    }
}

add_action('plugins_loaded', function(){
    load_plugin_textdomain('uv-core', false, dirname(plugin_basename(__FILE__)) . '/languages');
});

require __DIR__ . '/includes/cpt-taxonomies.php';
require __DIR__ . '/includes/meta-boxes.php';
require __DIR__ . '/includes/shortcodes-blocks.php';
