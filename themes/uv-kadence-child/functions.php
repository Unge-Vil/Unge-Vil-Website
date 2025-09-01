<?php
/*
Theme Name: UV Kadence Child
Version: 0.7.5
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

// Accessibility: add skip link at the start of the document
add_action('wp_body_open', function() {
    echo '<a class="skip-link" href="#main-content">' . esc_html__('Hopp til innhold', 'uv-kadence-child') . '</a>';
});

// Register block patterns for shortcode blocks
add_action('init', function() {
    if (!function_exists('register_block_pattern')) {
        return;
    }
    $patterns = [
        'locations-grid' => __('Stedrutenett', 'uv-kadence-child'),
        'news-list'      => __('Nyhetsliste', 'uv-kadence-child'),
        'activities'     => __('Aktiviteter', 'uv-kadence-child'),
        'partners'       => __('Partnere', 'uv-kadence-child'),
        'team-grid'      => __('Teamrutenett', 'uv-kadence-child'),
    ];
    foreach ($patterns as $slug => $title) {
        $pattern_path = get_theme_file_path('shortcode-patterns/' . $slug . '.php');
        if (!file_exists($pattern_path)) {
            error_log('Block pattern file not found: ' . $pattern_path);
            continue;
        }
        register_block_pattern(
            'uv-kadence-child/' . $slug,
            [
                'title'   => $title,
                'content' => include $pattern_path,
            ]
        );
    }
});

// Ensure author links point to the team view
add_filter('author_link', function($link, $author_id, $author_nicename) {
    return add_query_arg('team', 1, $link);
}, 10, 3);

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

// Prevent 404s on team author pages with no posts
add_action('template_redirect', function() {
    if (is_author() && isset($_GET['team'])) {
        global $wp_query;
        if (0 === $wp_query->post_count) {
            status_header(200);
            $wp_query->is_404 = false;
        }
    }
});

// Register Control Panel admin page and redirect users there after login
add_action('admin_menu', function() {
    add_menu_page(
        __('Kontrollpanel', 'uv-kadence-child'),
        __('Kontrollpanel', 'uv-kadence-child'),
        'read',
        'uv-control-panel',
        'uv_render_control_panel',
        'dashicons-admin-home',
        2
    );
});

add_action('admin_menu', function() {
    add_submenu_page(
        null,
        __('Rediger profil', 'uv-kadence-child'),
        __('Rediger profil', 'uv-kadence-child'),
        'read',
        'uv-edit-profile',
        'uv_render_edit_profile_page'
    );
});

// Redirect non-admins to the Control Panel after login.
add_filter('login_redirect', function($redirect_to, $request, $user) {
    if (is_wp_error($user)) {
        return $redirect_to;
    }

    // Allow users with manage options capability (e.g., administrators)
    // to retain their original destination.
    if ($user instanceof WP_User && user_can($user, 'manage_options')) {
        return $redirect_to;
    }

    return admin_url('admin.php?page=uv-control-panel');
}, 10, 3);

// Enqueue styles for the Control Panel and Edit Profile admin pages
add_action('admin_enqueue_scripts', function($hook) {
    if (!in_array($hook, ['toplevel_page_uv-control-panel', 'admin_page_uv-edit-profile'], true)) {
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
    $user = wp_get_current_user();
    $display_name = $user ? $user->display_name : '';
    $uid = $user ? $user->ID : 0;
    $img_base = get_stylesheet_directory_uri() . '/assets/img';
    $knowledge_url = esc_url(get_option('uv_knowledge_url'));

    $avatar = '';
    if ($uid) {
        if (function_exists('uv_people_get_avatar')) {
            $avatar = uv_people_get_avatar($uid);
        } else {
            $avatar = get_avatar($uid, 96, '', '', ['loading' => 'lazy']);
        }
    }

    $age = '';
    $birthdate = $uid ? get_user_meta($uid, 'uv_birthdate', true) : '';
    if ($birthdate) {
        $bd = DateTime::createFromFormat('Y-m-d', $birthdate);
        if ($bd) {
            $age = (new DateTime())->diff($bd)->y;
        }
    }

    $locations = $uid ? get_user_meta($uid, 'uv_location_terms', true) : [];

    $sections = [
        [
            'title' => __('Hva vil du gjøre?', 'uv-kadence-child'),
            'links' => [
                [
                    'url'    => admin_url('admin.php?page=uv-edit-profile'),
                    'img'    => 'profile.png',
                    'label'  => __('Rediger min profil', 'uv-kadence-child'),
                    'target' => '_self',
                ],
                [
                    'url'    => admin_url('edit.php?post_type=uv_activity'),
                    'img'    => 'activities.png',
                    'label'  => __('Aktiviteter', 'uv-kadence-child'),
                    'target' => '_self',
                ],
                [
                    'url'    => admin_url('edit.php'),
                    'img'    => 'news.png',
                    'label'  => __('Nyheter', 'uv-kadence-child'),
                    'target' => '_self',
                ],
                [
                    'url'    => admin_url('edit.php?post_type=uv_partner'),
                    'img'    => 'partners.png',
                    'label'  => __('Partnere', 'uv-kadence-child'),
                    'target' => '_self',
                ],
                [
                    'url'    => admin_url('edit.php?post_type=uv_experience'),
                    'img'    => 'experience.png',
                    'label'  => __('Erfaringer', 'uv-kadence-child'),
                    'target' => '_self',
                ],
            ],
        ],
        [
            'title' => __('Andre verktøy', 'uv-kadence-child'),
            'links' => [
                [
                    'url'    => $knowledge_url ?: '#',
                    'img'    => 'knowledge.png',
                    'label'  => __('Kunnskap', 'uv-kadence-child'),
                    'target' => '_blank',
                ],
                [
                    'url'    => 'https://mail.google.com/',
                    'img'    => 'gmail.svg',
                    'label'  => __('Gmail', 'uv-kadence-child'),
                    'target' => '_blank',
                ],
                [
                    'url'    => 'https://chat.google.com/',
                    'img'    => 'chat.svg',
                    'label'  => __('Chat', 'uv-kadence-child'),
                    'target'  => '_blank',
                ],
                [
                    'url'    => 'https://drive.google.com/',
                    'img'    => 'drive.svg',
                    'label'  => __('Drive', 'uv-kadence-child'),
                    'target' => '_blank',
                ],
                [
                    'url'    => 'https://monday.com/',
                    'img'    => 'monday.svg',
                    'label'  => __('Monday', 'uv-kadence-child'),
                    'target' => '_blank',
                ],
                [
                    'url'    => 'https://www.canva.com/',
                    'img'    => 'canva.svg',
                    'label'  => __('Canva', 'uv-kadence-child'),
                    'target' => '_blank',
                ],
            ],
        ],
    ];

    if (current_user_can('manage_options')) {
        $sections[] = [
            'title' => __('Admin', 'uv-kadence-child'),
            'links' => [
                [
                    'url'    => admin_url('users.php'),
                    'img'    => 'multipleusers.png',
                    'label'  => __('Brukere', 'uv-kadence-child'),
                    'target' => '_self',
                ],
                [
                    'url'    => admin_url('edit-tags.php?taxonomy=uv_location'),
                    'img'    => 'locations.png',
                    'label'  => __('Steder', 'uv-kadence-child'),
                    'target' => '_self',
                ],
                [
                    'url'    => admin_url('edit-tags.php?taxonomy=uv_position'),
                    'img'    => 'positions.png',
                    'label'  => __('Stillinger', 'uv-kadence-child'),
                    'target' => '_self',
                ],
            ],
        ];
    }

    echo '<div class="wrap uv-control-panel">';
    echo '<div class="uv-admin-header">';
    echo '<img class="uv-admin-logo" src="' . esc_url($img_base . '/UngeVil_admin_logo.png') . '" alt="Unge Vil" />';
    echo '<h1>' . sprintf(esc_html__('Velkommen, %s!', 'uv-kadence-child'), esc_html($display_name)) . '</h1>';
    echo '</div>';

    if ($avatar || $age || (is_array($locations) && $locations)) {
        echo '<div class="uv-admin-user">';
        if ($avatar) {
            echo '<div class="uv-avatar">' . $avatar . '</div>';
        }
        if ($age || (is_array($locations) && $locations)) {
            echo '<div class="uv-user-meta">';
            if ($age) {
                echo '<div class="uv-age">' . sprintf(esc_html__('Alder: %d', 'uv-kadence-child'), (int) $age) . '</div>';
            }
            if (is_array($locations) && $locations) {
                echo '<div class="uv-locations">';
                foreach ($locations as $loc_id) {
                    $loc_term = get_term($loc_id, 'uv_location');
                    if (!is_wp_error($loc_term) && $loc_term) {
                        echo '<span class="uv-location-pill">' . esc_html($loc_term->name) . '</span>';
                    }
                }
                echo '</div>';
            }
            echo '</div>';
        }
        echo '</div>';
    }

    echo '<nav>';
    foreach ($sections as $section) {
        echo '<div class="uv-control-section">';
        echo '<h2>' . esc_html($section['title']) . '</h2>';
        echo '<ul class="uv-links">';
        foreach ($section['links'] as $link) {
            $target = $link['target'] === '_blank' ? ' target="_blank" rel="noopener"' : '';
            echo '<li><a class="uv-link-card" href="' . esc_url($link['url']) . '"' . $target . '>';
            echo '<img src="' . esc_url($img_base . '/' . $link['img']) . '" alt="" aria-hidden="true" role="presentation" />';
            echo '<span>' . esc_html($link['label']) . '</span>';
            echo '</a></li>';
        }
        echo '</ul>';
        echo '</div>';
    }
    echo '</nav>';
    echo '</div>';
}

/**
 * Render the Edit Profile admin page.
 */
function uv_render_edit_profile_page() {
    if (!is_user_logged_in()) {
        wp_die(__('Du har ikke tilgang til denne siden.', 'uv-kadence-child'));
    }

    $current_user_id = get_current_user_id();
    $requested_id    = isset($_GET['user_id']) ? (int) $_GET['user_id'] : $current_user_id;

    if ($requested_id !== $current_user_id) {
        wp_die(__('Du kan bare få tilgang til din egen profil.', 'uv-kadence-child'));
    }

    $img_base = get_stylesheet_directory_uri() . '/assets/img';

    echo '<div class="wrap uv-edit-profile-admin">';
    echo '<div class="uv-admin-header">';
    echo '<img class="uv-admin-logo" src="' . esc_url($img_base . '/UngeVil_admin_logo.png') . '" alt="Unge Vil" />';
    echo '<h1>' . esc_html__('Rediger profil', 'uv-kadence-child') . '</h1>';
    echo '</div>';
    echo do_shortcode('[uv_edit_profile]');
    echo '</div>';
}

// Register option for Knowledge URL
add_action('admin_init', function() {
    register_setting('uv_settings', 'uv_knowledge_url', [
        'type'              => 'string',
        'sanitize_callback' => 'esc_url_raw',
    ]);
});

// Add settings page for Control Panel configuration
add_action('admin_menu', function() {
    add_options_page(
        __('UV-innstillinger', 'uv-kadence-child'),
        __('UV-innstillinger', 'uv-kadence-child'),
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
                        <label for="uv_knowledge_url"><?php esc_html_e('Knowledge URL', 'uv-kadence-child'); ?></label>
                    </th>
                    <td>
                        <input type="url" id="uv_knowledge_url" name="uv_knowledge_url" value="<?php echo esc_attr(get_option('uv_knowledge_url')); ?>" class="regular-text" />
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

require_once get_stylesheet_directory() . '/uv-team-manager.php';
