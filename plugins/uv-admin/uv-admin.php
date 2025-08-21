<?php
/**
 * Plugin Name: UV Admin
 * Description: Branded admin UI, Control Panel, and editor-friendly dashboard for Unge Vil.
 * Version: 0.2.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Text Domain: uv-admin
 */
if (!defined('ABSPATH')) exit;

// i18n (kept in English by default)
add_action('plugins_loaded', function(){
    load_plugin_textdomain('uv-admin', false, dirname(plugin_basename(__FILE__)) . '/languages');
});

// Option: docs URL
function uv_admin_get_docs_url(){
    $url = get_option('uv_admin_docs_url', '');
    return $url ? esc_url($url) : '';
}

// Settings page
add_action('admin_menu', function(){
    add_options_page(__('Unge Vil Admin','uv-admin'), __('Unge Vil Admin','uv-admin'), 'manage_options', 'uv-admin', 'uv_admin_settings_page');
});

function uv_admin_settings_page(){
    if(isset($_POST['uv_admin_docs_url']) && check_admin_referer('uv_admin_save','uv_admin_nonce')){
        update_option('uv_admin_docs_url', esc_url_raw($_POST['uv_admin_docs_url']));
        echo '<div class="updated"><p>' . esc_html( __('Saved.', 'uv-admin') ) . '</p></div>';
    }
    $docs = uv_admin_get_docs_url();
    echo '<div class="wrap"><h1>' . esc_html( __('Unge Vil Admin', 'uv-admin') ) . '</h1>';
    echo '<form method="post">';
    wp_nonce_field('uv_admin_save','uv_admin_nonce');
    echo '<table class="form-table">
            <tr><th scope="row"><label for="uv_admin_docs_url">' . esc_html( __('Docs URL', 'uv-admin') ) . '</label></th>
                <td><input type="url" name="uv_admin_docs_url" id="uv_admin_docs_url" value="' . esc_attr($docs) . '" class="regular-text" placeholder="' . esc_attr( __('https://sites.google.com/...', 'uv-admin') ) . '"></td></tr>
          </table>';
    submit_button(__('Save','uv-admin'));
    echo '</form></div>';
}

// Custom admin color scheme
add_action('admin_init', function(){
    $css = esc_url( plugins_url('assets/admin-colors.css', __FILE__) );
    wp_admin_css_color('uv', __('Unge Vil','uv-admin'), $css, ['#1f1b24','#9900ff','#ffffff','#f5f5f7']);
});

// Enqueue small admin CSS (cards, control panel visuals)
add_action('admin_enqueue_scripts', function($hook){
    wp_enqueue_style('uv-admin', esc_url( plugins_url('assets/admin.css', __FILE__) ), [], '1.0');
});

// Login branding
add_action('login_enqueue_scripts', function(){
    $logo = get_stylesheet_directory_uri() . '/assets/img/logo.svg';
    echo '<style>
    .login h1 a{background-image:url('.esc_url($logo).') !important; width:200px; height:60px; background-size:contain;}
    .login #backtoblog, .login #nav{display:none}
    </style>';
});

// Dashboard cleanup for non-admins
add_action('wp_dashboard_setup', function(){
    if(current_user_can('manage_options')) return;
    remove_meta_box('dashboard_primary','dashboard','side');
    remove_meta_box('dashboard_quick_press','dashboard','side');
    remove_meta_box('dashboard_site_health','dashboard','normal');
    remove_meta_box('rank_math_dashboard_widget','dashboard','normal');
    remove_meta_box('seopress_overview','dashboard','normal');
    remove_meta_box('w3tc-dashboard-widget','dashboard','normal');
    remove_meta_box('wordfence_activity_report_widget','dashboard','normal');
    remove_meta_box('google_site_kit_dashboard_widget','dashboard','normal');
});

// Hide some menus for non-admins (keeps content-focused UI)
add_action('admin_menu', function(){
    if(current_user_can('manage_options')) return;
    remove_menu_page('tools.php');
    remove_menu_page('plugins.php');
    remove_menu_page('options-general.php');
    remove_menu_page('themes.php');
    remove_menu_page('wordfence'); // Wordfence
    remove_menu_page('googlesitekit-dashboard'); // Site Kit
    remove_menu_page('complianz'); // Complianz
}, 999);

// Control Panel (top-level menu)
add_action('admin_menu', function(){
    add_menu_page(__('Control Panel','uv-admin'), __('Control Panel','uv-admin'),'edit_posts','uv-control-panel','uv_admin_control_panel','dashicons-layout',2);
});

function uv_admin_control_panel(){
    $page_id = intval( get_option('uv_admin_control_panel_page_id', 0) );
    if ( $page_id ) {
        $page = get_post( $page_id );
        if ( $page instanceof \WP_Post ) {
            echo '<div class="wrap uv-control-panel">';
            echo apply_filters('the_content', $page->post_content);
            echo '</div>';
            return;
        }
    }

    $docs = uv_admin_get_docs_url();
    $cards = [
        ['News','edit.php','dashicons-megaphone'],
        ['Media','upload.php','dashicons-format-image'],
        ['Pages','edit.php?post_type=page','dashicons-admin-page'],
        ['Locations','edit-tags.php?taxonomy=uv_location','dashicons-location'],
        ['Activities','edit.php?post_type=uv_activity','dashicons-forms'],
        ['Partners','edit.php?post_type=uv_partner','dashicons-heart'],
        ['Team Assignments','edit.php?post_type=uv_team_assignment','dashicons-groups'],
    ];
    if(post_type_exists('tribe_events')) $cards[] = ['Events','edit.php?post_type=tribe_events','dashicons-calendar-alt'];
    echo '<div class="wrap uv-control-panel">';
    echo '<div class="uvcp-header"><img alt="Unge Vil" src="' . esc_url(get_stylesheet_directory_uri().'/assets/img/logo.svg') . '\"><h1>Unge Vil — ' . esc_html( __('Control Panel','uv-admin') ) . '</h1></div>';
    echo '<div class="uvcp-grid">';
    foreach($cards as $c){
        echo '<a class="uvcp-card" href="' . esc_url( admin_url($c[1]) ) . '\"><span class="dashicons ' . esc_attr($c[2]) . '\"></span><strong>' . esc_html( __($c[0], 'uv-admin') ) . '</strong></a>';
    }
    echo '</div>';
    if($docs){
        echo '<p class="uvcp-docs"><a class="button button-primary" href="' . esc_url($docs) . '" target="_blank" rel="noopener">' . esc_html( __('Open Team Docs','uv-admin') ) . '</a></p>';
    } else {
        echo '<p class="uvcp-docs">' . sprintf( __('Tip: set your Docs URL in %sSettings → Unge Vil Admin%s.', 'uv-admin' ), '<a href="' . esc_url( admin_url('options-general.php?page=uv-admin') ) . '">', '</a>' ) . '</p>';
    }
    echo '</div>';
}

add_shortcode('uv_display_name', function(){
    $user = wp_get_current_user();
    return $user && $user->exists() ? esc_html( $user->display_name ) : '';
});


// Admin bar shortcut
add_action('admin_bar_menu', function($bar){
    if(!is_admin() || !current_user_can('edit_posts')) return;
    $bar->add_node([
        'id'=>'uvcp',
        'title'=>__('Control Panel','uv-admin'),
        'href'=>esc_url( admin_url('admin.php?page=uv-control-panel') ),
        'meta'=>['class'=>'uvcp-bar']
    ]);
}, 80);
