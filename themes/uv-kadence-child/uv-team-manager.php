<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class UV_Team_Manager_Table extends WP_List_Table {
    public function get_columns() {
        return [
            'cb'        => '<input type="checkbox" />',
            'name'      => __('Name', 'uv-kadence-child'),
            'avatar'    => __('Avatar', 'uv-kadence-child'),
            'phone'     => __('Phone', 'uv-kadence-child'),
            'position'  => __('Position', 'uv-kadence-child'),
            'locations' => __('Locations', 'uv-kadence-child'),
            'primary'   => __('Primary Locations', 'uv-kadence-child'),
        ];
    }

    public function get_sortable_columns() {
        return [
            'name'     => ['name', true],
            'position' => ['position', false],
        ];
    }

    public function prepare_items() {
        $per_page = 20;
        $paged    = $this->get_pagenum();
        $search   = isset($_REQUEST['s']) ? sanitize_text_field(wp_unslash($_REQUEST['s'])) : '';
        $sortable = $this->get_sortable_columns();
        $orderby  = isset($_REQUEST['orderby']) && array_key_exists($_REQUEST['orderby'], $sortable)
            ? sanitize_key($_REQUEST['orderby'])
            : 'name';
        $order = isset($_REQUEST['order']) && 'desc' === strtolower($_REQUEST['order']) ? 'DESC' : 'ASC';

        $args = [
            'number' => $per_page,
            'paged'  => $paged,
            'order'  => $order,
        ];

        if ($search) {
            $args['search'] = '*' . $search . '*';
        }

        if ('position' === $orderby) {
            $args['meta_key'] = 'uv_position_term';
            $args['orderby']  = 'meta_value';
        } else {
            $args['orderby'] = 'display_name';
        }

        $query       = new WP_User_Query($args);
        $this->items = $query->get_results();

        $this->set_pagination_args([
            'total_items' => $query->get_total(),
            'per_page'    => $per_page,
        ]);

        $columns = $this->get_columns();
        $hidden  = [];
        $this->_column_headers = [$columns, $hidden, $sortable];
    }

    protected function column_cb($user) {
        return '<input type="checkbox" name="uv_team_manager[ids][]" value="' . esc_attr($user->ID) . '" />';
    }

    protected function column_name($user) {
        return esc_html($user->display_name);
    }

    protected function column_avatar($user) {
        $avatar_id = get_user_meta($user->ID, 'uv_avatar_id', true);
        $default = function_exists('uv_people_get_avatar') ? uv_people_get_avatar($user->ID) : get_avatar($user->ID, 32);
        $preview = $avatar_id ? wp_get_attachment_image($avatar_id, [32, 32]) : $default;

        $html  = '<div class="uv-avatar-field" data-default="' . esc_attr($default) . '">';
        $html .= '<div class="uv-avatar-preview">' . $preview . '</div>';
        $html .= '<input type="hidden" class="uv-avatar-id" name="uv_team_manager[' . $user->ID . '][avatar_id]" value="' . esc_attr($avatar_id) . '" />';
        $html .= '<button type="button" class="button uv-avatar-button">' . esc_html__('Select', 'uv-kadence-child') . '</button>';
        $html .= '<button type="button" class="button uv-avatar-remove"' . ($avatar_id ? '' : ' style="display:none;"') . '>' . esc_html__('Remove', 'uv-kadence-child') . '</button>';
        $html .= '</div>';

        return $html;
    }

    protected function column_phone($user) {
        $val = get_user_meta($user->ID, 'uv_phone', true);
        return '<input type="text" name="uv_team_manager[' . $user->ID . '][phone]" value="' . esc_attr($val) . '" />';
    }

    protected function column_position($user) {
        $selected = absint(get_user_meta($user->ID, 'uv_position_term', true));
        $terms = get_terms([
            'taxonomy'   => 'uv_position',
            'hide_empty' => false,
        ]);
        $options = '<option value=""></option>';
        if (!is_wp_error($terms)) {
            foreach ($terms as $term) {
                $options .= '<option value="' . $term->term_id . '" ' . selected($selected, $term->term_id, false) . '>' . esc_html($term->name) . '</option>';
            }
        }
        return '<select name="uv_team_manager[' . $user->ID . '][position]">' . $options . '</select>';
    }

    protected function column_locations($user) {
        $selected = get_user_meta($user->ID, 'uv_location_terms', true);
        if (!is_array($selected)) {
            $selected = [];
        }
        $terms = get_terms([
            'taxonomy'   => 'uv_location',
            'hide_empty' => false,
        ]);
        $options = '';
        if (!is_wp_error($terms)) {
            foreach ($terms as $term) {
                $options .= '<option value="' . $term->term_id . '" ' . selected(in_array($term->term_id, $selected, true), true, false) . '>' . esc_html($term->name) . '</option>';
            }
        }
        return '<select multiple class="uv-location-select" name="uv_team_manager[' . $user->ID . '][locations][]">' . $options . '</select>';
    }

    protected function column_primary($user) {
        $selected = get_user_meta($user->ID, 'uv_primary_locations', true);
        if (!is_array($selected)) {
            $selected = [];
        }
        $terms = get_terms([
            'taxonomy'   => 'uv_location',
            'hide_empty' => false,
        ]);
        $options = '';
        if (!is_wp_error($terms)) {
            foreach ($terms as $term) {
                $options .= '<option value="' . $term->term_id . '" ' . selected(in_array($term->term_id, $selected, true), true, false) . '>' . esc_html($term->name) . '</option>';
            }
        }
        return '<select multiple class="uv-primary-location-select" name="uv_team_manager[' . $user->ID . '][primary_locations][]">' . $options . '</select>';
    }
}

function uv_render_team_manager_page() {
    if (!current_user_can('edit_users')) {
        wp_die(__('Sorry, you are not allowed to access this page.', 'uv-kadence-child'));
    }
    $table = new UV_Team_Manager_Table();
    $table->prepare_items();
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Team Manager', 'uv-kadence-child'); ?></h1>
        <form method="get">
            <input type="hidden" name="page" value="uv-team-manager" />
            <?php $table->search_box(__('Search Users', 'uv-kadence-child'), 'uv-team-search'); ?>
        </form>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <?php wp_nonce_field('uv_team_manager_save', 'uv_team_manager_nonce'); ?>
            <input type="hidden" name="action" value="uv_team_manager_save" />
            <?php $table->display(); ?>
            <?php submit_button(__('Save Changes', 'uv-kadence-child')); ?>
        </form>
    </div>
    <?php
}

function uv_team_manager_save_handler() {
    if (!current_user_can('edit_users')) {
        wp_die(__('You do not have permission to edit users.', 'uv-kadence-child'));
    }
    check_admin_referer('uv_team_manager_save', 'uv_team_manager_nonce');
    $data = isset($_POST['uv_team_manager']) ? (array)$_POST['uv_team_manager'] : [];
    foreach ($data as $uid => $fields) {
        $uid = (int)$uid;
        if ($uid <= 0 || !current_user_can('edit_user', $uid)) {
            continue;
        }
        $fields = wp_unslash($fields);
        if (isset($fields['phone'])) {
            update_user_meta($uid, 'uv_phone', sanitize_text_field($fields['phone']));
        }
        if (isset($fields['position'])) {
            update_user_meta($uid, 'uv_position_term', absint($fields['position']));
        }
        if (isset($fields['avatar_id'])) {
            $avatar_id = absint($fields['avatar_id']);
            if ($avatar_id) {
                update_user_meta($uid, 'uv_avatar_id', $avatar_id);
            } else {
                delete_user_meta($uid, 'uv_avatar_id');
            }
        }
        $loc_ids = array_map('intval', isset($fields['locations']) ? (array)$fields['locations'] : []);
        if (!empty($loc_ids)) {
            update_user_meta($uid, 'uv_location_terms', $loc_ids);
        } else {
            delete_user_meta($uid, 'uv_location_terms');
        }

        $primary_raw = array_map('intval', isset($fields['primary_locations']) ? (array)$fields['primary_locations'] : []);
        $primary_ids = array_values(array_intersect($primary_raw, $loc_ids));
        if (!empty($primary_ids)) {
            update_user_meta($uid, 'uv_primary_locations', $primary_ids);
        } else {
            delete_user_meta($uid, 'uv_primary_locations');
        }
    }
    wp_redirect(add_query_arg('updated', 1, admin_url('admin.php?page=uv-team-manager')));
    exit;
}
add_action('admin_post_uv_team_manager_save', 'uv_team_manager_save_handler');

add_action('admin_menu', function () {
    add_submenu_page(
        'uv-control-panel',
        __('Team Manager', 'uv-kadence-child'),
        __('Team Manager', 'uv-kadence-child'),
        'edit_users',
        'uv-team-manager',
        'uv_render_team_manager_page'
    );
});

add_action('admin_enqueue_scripts', function ($hook) {
    if ('uv-control-panel_page_uv-team-manager' !== $hook) {
        return;
    }
    if (function_exists('uv_register_select2_assets')) {
        uv_register_select2_assets();
    } else {
        $uv_people_file = WP_PLUGIN_DIR . '/uv-people/uv-people.php';
        if (file_exists($uv_people_file)) {
            if (!wp_script_is('select2', 'registered')) {
                wp_register_script(
                    'select2',
                    plugins_url('assets/select2/select2.min.js', $uv_people_file),
                    ['jquery'],
                    '4.0.13',
                    true
                );
            }
            if (!wp_style_is('select2', 'registered')) {
                wp_register_style(
                    'select2',
                    plugins_url('assets/select2/select2.min.css', $uv_people_file),
                    [],
                    '4.0.13'
                );
            }
        }
    }
    wp_enqueue_style('select2');
    wp_enqueue_script('select2');
    wp_enqueue_media();
    wp_enqueue_script(
        'uv-team-manager',
        get_stylesheet_directory_uri() . '/uv-team-manager.js',
        ['jquery', 'select2'],
        null,
        true
    );
    wp_localize_script('uv-team-manager', 'UVTeamManager', [
        'selectAvatar' => __('Select Avatar', 'uv-kadence-child'),
        'useImage'     => __('Use this image', 'uv-kadence-child'),
    ]);
});
