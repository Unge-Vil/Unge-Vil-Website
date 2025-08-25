<?php
/*
Theme Name: UV Kadence Child
Version: 0.5.2
*/
$update_checker_path = dirname(__DIR__, 2) . '/plugin-update-checker/plugin-update-checker.php';
if (file_exists($update_checker_path)) {
    if (!class_exists('\\YahnisElsts\\PluginUpdateChecker\\v5\\PucFactory')) {
        require $update_checker_path;
    }
    $uvThemeUpdateChecker = \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
        'https://github.com/Unge-Vil/Unge-Vil-Website/',
        __FILE__,
        'uv-kadence-child',
        'theme'
    );
    $uvThemeUpdateChecker->setBranch('main');
    if (method_exists($uvThemeUpdateChecker, 'setPathInsideRepository')) {
        $uvThemeUpdateChecker->setPathInsideRepository('themes/uv-kadence-child');
    }
}
add_action('wp_enqueue_scripts', function() {
    wp_enqueue_style('uv-child', get_stylesheet_uri(), [], wp_get_theme()->get('Version'));

    wp_enqueue_style(
        'uv-fonts',
        get_stylesheet_directory_uri() . '/assets/css/fonts.css',
        [],
        wp_get_theme()->get('Version')
    );

    wp_enqueue_style(
        'uv-child-extra',
        get_stylesheet_directory_uri() . '/assets/css/theme.css',
        ['uv-child', 'uv-fonts'],
        wp_get_theme()->get('Version')
    );
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
                'content' => include get_theme_file_path('shortcode-patterns/' . $slug . '.php'),
            ]
        );
    }
});

// Use custom team author template when ?team is present on author URLs
add_filter('author_template', function($template) {
    if (isset($_GET['team'])) {
        $team_template = locate_template('author-team.php');
        if ($team_template) {
            return $team_template;
        }
    }
    return $template;
});

// Register Control Panel admin page and redirect users there after login
add_action('admin_menu', function() {
    add_menu_page(
        __('Control Panel', 'uv-kadence-child'),
        __('Control Panel', 'uv-kadence-child'),
        'read',
        'uv-control-panel',
        'uv_render_control_panel',
        'dashicons-admin-home',
        2
    );
});

add_filter('login_redirect', function($redirect_to, $request, $user) {
    if (is_wp_error($user)) {
        return $redirect_to;
    }
    $page_id = absint(get_option('uv_control_panel_page_id'));
    if ($page_id) {
        return admin_url('admin.php?page=uv-control-panel');
    }
    return $redirect_to;
}, 10, 3);

// Enqueue styles for the Control Panel admin page
add_action('admin_enqueue_scripts', function($hook) {
    if ('toplevel_page_uv-control-panel' !== $hook) {
        return;
    }

    $deps = [];

    // Parent Kadence theme stylesheet
    wp_enqueue_style(
        'kadence-theme',
        get_template_directory_uri() . '/style.css',
        [],
        wp_get_theme()->get('Version')
    );
    $deps[] = 'kadence-theme';

    // UV font stylesheet
    wp_enqueue_style(
        'uv-fonts',
        get_stylesheet_directory_uri() . '/assets/css/fonts.css',
        [],
        wp_get_theme()->get('Version')
    );
    $deps[] = 'uv-fonts';

    // Kadence Blocks global stylesheet, if plugin is active
    if (defined('KADENCE_BLOCKS_VERSION') && defined('KADENCE_BLOCKS_MAIN_FILE')) {
        wp_enqueue_style(
            'kadence-blocks',
            plugins_url('dist/blocks.style.build.css', KADENCE_BLOCKS_MAIN_FILE),
            ['kadence-theme'],
            KADENCE_BLOCKS_VERSION
        );
        $deps[] = 'kadence-blocks';
    }

    // Control Panel specific styles
    wp_enqueue_style(
        'uv-control-panel',
        get_stylesheet_directory_uri() . '/assets/css/control-panel.css',
        $deps,
        wp_get_theme()->get('Version')
    );
});

/**
 * Render the Control Panel admin page.
 */
function uv_render_control_panel() {
    echo '<div class="wrap">';
    $page_id = absint(get_option('uv_control_panel_page_id'));
    if ($page_id) {
        $post = get_post($page_id);
        if ($post) {
            echo apply_filters('the_content', $post->post_content);
        } else {
            esc_html_e('Control Panel page not found.', 'uv-kadence-child');
        }
    } else {
        esc_html_e('No Control Panel page has been set.', 'uv-kadence-child');
    }
    echo '</div>';
}

// Register option for Control Panel page ID
add_action('admin_init', function() {
    register_setting('uv_settings', 'uv_control_panel_page_id', [
        'type'              => 'integer',
        'sanitize_callback' => 'absint',
    ]);
});

// Add settings page for Control Panel configuration
add_action('admin_menu', function() {
    add_options_page(
        __('UV Settings', 'uv-kadence-child'),
        __('UV Settings', 'uv-kadence-child'),
        'manage_options',
        'uv-settings',
        'uv_render_settings_page'
    );
});

/**
 * Render the UV settings page.
 */
function uv_render_settings_page() {
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('UV Settings', 'uv-kadence-child'); ?></h1>
        <form action="options.php" method="post">
            <?php settings_fields('uv_settings'); ?>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row">
                        <label for="uv_control_panel_page_id"><?php esc_html_e('Control Panel Page ID', 'uv-kadence-child'); ?></label>
                    </th>
                    <td>
                        <input type="number" id="uv_control_panel_page_id" name="uv_control_panel_page_id" value="<?php echo esc_attr(get_option('uv_control_panel_page_id')); ?>" class="regular-text" />
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

