<?php
/**
 * Plugin Name: UV Core
 * Description: CPTs, taxonomies, term images, and lightweight shortcodes.
 * Version: 0.8.12
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
define('UV_CORE_VERSION', '0.8.12');
}

/**
 * Get likely domain variants for Mixpanel cookie cleanup.
 */
function uv_core_get_mixpanel_cookie_domains(): array {
    $host = (string) wp_parse_url(home_url(), PHP_URL_HOST);
    if ($host === '') {
        return [];
    }

    $base_host = preg_replace('/^www\./i', '', $host) ?: $host;

    return array_values(array_unique(array_filter([
        $host,
        '.' . ltrim($host, '.'),
        $base_host,
        '.' . ltrim($base_host, '.'),
    ])));
}

/**
 * Workaround for strict WAF setups that false-positive on Mixpanel cookie values.
 * Clears mp_*_mixpanel cookies on wp-admin responses for logged-in users.
 */
function uv_core_expire_mixpanel_cookies(): void {
    $domains = uv_core_get_mixpanel_cookie_domains();

    foreach (array_keys($_COOKIE) as $cookie_name) {
        if (!preg_match('/^mp_[A-Za-z0-9]+_mixpanel$/', (string) $cookie_name)) {
            continue;
        }

        setcookie($cookie_name, '', time() - HOUR_IN_SECONDS, '/');
        setcookie($cookie_name, '', time() - HOUR_IN_SECONDS, '/wp-admin');
        foreach ($domains as $domain) {
            setcookie($cookie_name, '', time() - HOUR_IN_SECONDS, '/', $domain);
            setcookie($cookie_name, '', time() - HOUR_IN_SECONDS, '/wp-admin', $domain);
        }
        unset($_COOKIE[$cookie_name]);
    }
}

add_action('admin_init', function () {
    if (!is_user_logged_in()) {
        return;
    }

    uv_core_expire_mixpanel_cookies();
}, 1);

add_action('admin_head', function () {
    if (!is_user_logged_in()) {
        return;
    }

    $domains = wp_json_encode(uv_core_get_mixpanel_cookie_domains());
    ?>
<script>
(function () {
    try {
        const cookies = document.cookie ? document.cookie.split(';') : [];
        const domains = <?php echo $domains ?: '[]'; ?>;
        const paths = ['/', '/wp-admin'];
        let removed = false;

        cookies.forEach((entry) => {
            const separatorIndex = entry.indexOf('=');
            const name = (separatorIndex === -1 ? entry : entry.slice(0, separatorIndex)).trim();

            if (!/^mp_[A-Za-z0-9]+_mixpanel$/.test(name)) {
                return;
            }

            paths.forEach((path) => {
                document.cookie = `${name}=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=${path}; SameSite=Lax`;
                domains.forEach((domain) => {
                    document.cookie = `${name}=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=${path}; domain=${domain}; SameSite=Lax`;
                });
            });

            removed = true;
        });

        if (removed && window.sessionStorage && !sessionStorage.getItem('uv-mixpanel-cookie-reset')) {
            sessionStorage.setItem('uv-mixpanel-cookie-reset', '1');
            window.location.reload();
        }
    } catch (error) {}
})();
</script>
    <?php
}, 0);

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
