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

    public function prepare_items() {
        $query = new WP_User_Query([
            'orderby' => 'display_name',
            'order'   => 'ASC',
            'number'  => 999,
        ]);
        $this->items = $query->get_results();
        $columns = $this->get_columns();
        $hidden = [];
        $sortable = [];
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
        $img = '';
        if (function_exists('uv_people_get_avatar')) {
            $img = uv_people_get_avatar($user->ID);
        } else {
            $img = get_avatar($user->ID, 32);
        }
        return '<div class="uv-avatar">' . $img . '</div>' .
            '<input type="number" name="uv_team_manager[' . $user->ID . '][avatar_id]" value="' . esc_attr($avatar_id) . '" />';
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
        if (isset($fields['phone'])) {
            update_user_meta($uid, 'uv_phone', sanitize_text_field($fields['phone']));
        }
        if (isset($fields['position'])) {
            update_user_meta($uid, 'uv_position_term', absint($fields['position']));
        }
        if (isset($fields['avatar_id'])) {
            update_user_meta($uid, 'uv_avatar_id', absint($fields['avatar_id']));
        }
        $loc_ids = array_map('intval', isset($fields['locations']) ? (array)$fields['locations'] : []);
        update_user_meta($uid, 'uv_location_terms', $loc_ids);

        $primary = array_map('intval', isset($fields['primary_locations']) ? (array)$fields['primary_locations'] : []);
        if (!empty($primary)) {
            update_user_meta($uid, 'uv_primary_locations', $primary);
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
    wp_enqueue_style('select2');
    wp_enqueue_script('select2');
    wp_add_inline_script('select2', 'jQuery(function($){ $(".uv-location-select, .uv-primary-location-select").select2(); });');
});
