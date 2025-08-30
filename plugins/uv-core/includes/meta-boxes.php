<?php

add_action('admin_enqueue_scripts', function($hook){
    $screen = get_current_screen();
    if($screen && $screen->taxonomy === 'uv_location' && in_array($hook, ['edit-tags.php','term.php'])){
        wp_enqueue_media();
        wp_enqueue_script('uv-term-image', plugins_url('assets/term-image.js', dirname(__DIR__) . '/uv-core.php'), ['jquery'], UV_CORE_VERSION, true);
        wp_localize_script('uv-term-image', 'uvTermImage', [
            'selectImage' => esc_html__('Velg bilde', 'uv-core'),
        ]);
    }

    if($screen && $screen->post_type === 'uv_experience' && in_array($hook, ['post.php','post-new.php'])){
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
        wp_enqueue_script('select2');
        wp_enqueue_style('select2');
        wp_enqueue_script('uv-admin', plugins_url('assets/admin.js', dirname(__DIR__) . '/uv-core.php'), ['jquery','select2'], UV_CORE_VERSION, true);
    }
});

// Term image: uv_location
add_action('uv_location_add_form_fields', function(){
    ?>
    <div class="form-field">
      <?php wp_nonce_field('uv_location_image_action', 'uv_location_image_nonce'); ?>
      <label for="uv_location_image"><?php esc_html_e('Stedsbilde', 'uv-core'); ?></label>
      <input type="hidden" id="uv_location_image" name="uv_location_image" value="">
      <button class="button uv-upload"><?php esc_html_e('Velg bilde', 'uv-core'); ?></button>
      <p class="description"><?php esc_html_e('Brukes på stedskort.', 'uv-core'); ?></p>
    </div>
    <?php
});

add_action('uv_location_edit_form_fields', function($term){
    $val = get_term_meta($term->term_id, 'uv_location_image', true);
    $img = $val ? wp_get_attachment_image($val, 'thumbnail') : '';
    ?>
    <tr class="form-field">
      <th scope="row"><label for="uv_location_image"><?php esc_html_e('Stedsbilde', 'uv-core'); ?></label></th>
      <td>
        <?php wp_nonce_field('uv_location_image_action', 'uv_location_image_nonce'); ?>
        <input type="hidden" id="uv_location_image" name="uv_location_image" value="<?php echo esc_attr($val); ?>">
        <button class="button uv-upload"><?php esc_html_e('Velg bilde', 'uv-core'); ?></button>
        <div><?php echo $img; ?></div>
      </td>
    </tr>
    <?php
}, 10, 1);

// Term page: uv_location
add_action('uv_location_add_form_fields', function(){
    ?>
    <div class="form-field">
      <?php wp_nonce_field('uv_location_page_action', 'uv_location_page_nonce'); ?>
      <label for="uv_location_page"><?php esc_html_e('Stedside', 'uv-core'); ?></label>
      <?php wp_dropdown_pages([
          'post_type' => 'page',
          'name' => 'uv_location_page',
          'id' => 'uv_location_page',
          'show_option_none' => esc_html__('— Ingen —', 'uv-core'),
          'option_none_value' => 0,
      ]); ?>
      <p class="description"><?php esc_html_e('Lenker vil bruke denne siden hvis angitt.', 'uv-core'); ?></p>
    </div>
    <?php
});

add_action('uv_location_edit_form_fields', function($term){
    $val = get_term_meta($term->term_id, 'uv_location_page', true);
    ?>
    <tr class="form-field">
      <th scope="row"><label for="uv_location_page"><?php esc_html_e('Stedside', 'uv-core'); ?></label></th>
      <td>
        <?php wp_nonce_field('uv_location_page_action', 'uv_location_page_nonce'); ?>
        <?php wp_dropdown_pages([
            'post_type' => 'page',
            'name' => 'uv_location_page',
            'id' => 'uv_location_page',
            'selected' => $val,
            'show_option_none' => esc_html__('— Ingen —', 'uv-core'),
            'option_none_value' => 0,
        ]); ?>
      </td>
    </tr>
    <?php
}, 10, 1);

add_action('created_uv_location', function($term_id){
    if(!isset($_POST['uv_location_image_nonce'])) return;
    if(!current_user_can('manage_categories')) return;
    check_admin_referer('uv_location_image_action', 'uv_location_image_nonce');
    if(isset($_POST['uv_location_image'])){
        update_term_meta($term_id, 'uv_location_image', intval($_POST['uv_location_image']));
    }
}, 10, 1);
add_action('edited_uv_location', function($term_id){
    if(!isset($_POST['uv_location_image_nonce'])) return;
    if(!current_user_can('manage_categories')) return;
    check_admin_referer('uv_location_image_action', 'uv_location_image_nonce');
    if(isset($_POST['uv_location_image'])){
        update_term_meta($term_id, 'uv_location_image', intval($_POST['uv_location_image']));
    }
}, 10, 1);

add_action('created_uv_location', function($term_id){
    if(!isset($_POST['uv_location_page_nonce'])) return;
    if(!current_user_can('manage_categories')) return;
    check_admin_referer('uv_location_page_action', 'uv_location_page_nonce');
    if(isset($_POST['uv_location_page'])){
        $page_id = absint($_POST['uv_location_page']);
        if($page_id){
            update_term_meta($term_id, 'uv_location_page', $page_id);
        } else {
            delete_term_meta($term_id, 'uv_location_page');
        }
    }
}, 10, 1);
add_action('edited_uv_location', function($term_id){
    if(!isset($_POST['uv_location_page_nonce'])) return;
    if(!current_user_can('manage_categories')) return;
    check_admin_referer('uv_location_page_action', 'uv_location_page_nonce');
    if(isset($_POST['uv_location_page'])){
        $page_id = absint($_POST['uv_location_page']);
        if($page_id){
            update_term_meta($term_id, 'uv_location_page', $page_id);
        } else {
            delete_term_meta($term_id, 'uv_location_page');
        }
    }
}, 10, 1);

// Partner meta boxes
add_action('add_meta_boxes_uv_partner', function(){
    add_meta_box('uv_partner_url', esc_html__('Ekstern URL', 'uv-core'), function($post){
        $val = get_post_meta($post->ID, 'uv_partner_url', true);
        wp_nonce_field('uv_partner_url_action', 'uv_partner_url_nonce');
        echo '<p><label>' . esc_html__('Nettside', 'uv-core') . '</label><input type="url" style="width:100%" name="uv_partner_url" value="' . esc_attr($val) . '"></p>';
    }, 'uv_partner', 'side', 'high');
    add_meta_box('uv_partner_display', esc_html__('Visning', 'uv-core'), function($post){
        $val = get_post_meta($post->ID, 'uv_partner_display', true);
        if(!$val) {
            $val = has_post_thumbnail($post->ID) ? 'circle_title' : 'title_only';
        }
        wp_nonce_field('uv_partner_display_action', 'uv_partner_display_nonce');
        echo '<p><label class="screen-reader-text" for="uv_partner_display">' . esc_html__('Visning', 'uv-core') . '</label>';
        echo '<select id="uv_partner_display" name="uv_partner_display">';
        $opts = [
            'logo_only'   => esc_html__('Kun logo', 'uv-core'),
            'logo_title'  => esc_html__('Logo og tittel', 'uv-core'),
            'circle_title'=> esc_html__('Sirkel og tittel', 'uv-core'),
            'title_only'  => esc_html__('Kun tittel', 'uv-core'),
        ];
        foreach($opts as $k => $label){
            echo '<option value="' . esc_attr($k) . '"' . selected($val, $k, false) . '>' . $label . '</option>';
        }
        echo '</select></p>';
    }, 'uv_partner', 'side', 'high');
});
add_action('save_post_uv_partner', function($post_id){
    if(!current_user_can('edit_post', $post_id)) return;

    if(isset($_POST['uv_partner_url_nonce'])){
        check_admin_referer('uv_partner_url_action', 'uv_partner_url_nonce');
        if(isset($_POST['uv_partner_url'])){
            update_post_meta($post_id, 'uv_partner_url', esc_url_raw($_POST['uv_partner_url']));
        }
    }

    if(isset($_POST['uv_partner_display_nonce'])){
        check_admin_referer('uv_partner_display_action', 'uv_partner_display_nonce');
        if(isset($_POST['uv_partner_display'])){
            $allowed = ['logo_only','logo_title','circle_title','title_only'];
            $val = in_array($_POST['uv_partner_display'],$allowed) ? $_POST['uv_partner_display'] : 'circle_title';
            update_post_meta($post_id, 'uv_partner_display', $val);
        }
    }
}, 10, 1);

// Related post meta box
add_action('add_meta_boxes_uv_experience', function(){
    add_meta_box('uv_related_post', esc_html__('Relatert innlegg','uv-core'), function($post){
        wp_nonce_field('uv_related_post_action', 'uv_related_post_nonce');
        $selected = get_post_meta($post->ID, 'uv_related_post', true);
        $posts    = get_posts([
            'post_type'      => 'post',
            'posts_per_page' => -1,
        ]);

        $dropdown  = '<select name="uv_related_post" class="uv-post-select">';
        $dropdown .= '<option value="0">' . esc_html__('— Ingen —', 'uv-core') . '</option>';
        foreach ($posts as $p) {
            $dropdown .= sprintf(
                '<option value="%1$s"%2$s>%3$s</option>',
                esc_attr($p->ID),
                selected($selected, $p->ID, false),
                esc_html(get_the_title($p))
            );
        }
        $dropdown .= '</select>';
        echo $dropdown;
    }, 'uv_experience', 'side', 'high');
});
add_action('save_post_uv_experience', function($post_id){
    if(!isset($_POST['uv_related_post_nonce'])) return;
    if(!current_user_can('edit_post', $post_id)) return;
    check_admin_referer('uv_related_post_action', 'uv_related_post_nonce');
    $val = isset($_POST['uv_related_post']) ? absint($_POST['uv_related_post']) : 0;
    if($val){
        update_post_meta($post_id, 'uv_related_post', $val);
    }else{
        delete_post_meta($post_id, 'uv_related_post');
    }
}, 10, 1);

// Experience users meta box
add_action('add_meta_boxes_uv_experience', function(){
    add_meta_box('uv_experience_users', esc_html__('Teammedlemmer','uv-core'), function($post){
        wp_nonce_field('uv_experience_users_action', 'uv_experience_users_nonce');
        $selected = get_post_meta($post->ID, 'uv_experience_users', false);
        $dropdown = wp_dropdown_users([
            'name'             => 'uv_experience_users[]',
            'id'               => 'uv_experience_users',
            'selected'         => $selected,
            'include_selected' => true,
            'multi'            => true,
            'show'             => 'display_name',
            'number'           => 50,
            'class'            => 'uv-user-select',
            'echo'             => false,
        ]);
        echo str_replace('<select', '<select multiple="multiple" style="width:100%;"', $dropdown);
    }, 'uv_experience', 'side', 'high');
});
add_action('save_post_uv_experience', function($post_id){
    if(!isset($_POST['uv_experience_users_nonce'])) return;
    if(!current_user_can('edit_post', $post_id)) return;
    check_admin_referer('uv_experience_users_action', 'uv_experience_users_nonce');
    $user_ids = isset($_POST['uv_experience_users']) ? array_filter(array_map('absint', (array)$_POST['uv_experience_users'])) : [];
    delete_post_meta($post_id, 'uv_experience_users');
    foreach($user_ids as $uid){
        add_post_meta($post_id, 'uv_experience_users', $uid);
    }
}, 10, 1);

add_action('init', function(){
    register_post_meta('uv_experience', 'uv_related_post', [
        'single' => true,
        'type' => 'integer',
        'show_in_rest' => true,
        'sanitize_callback' => 'absint',
        'auth_callback' => function(){ return current_user_can('edit_posts'); },
    ]);
    register_post_meta('uv_experience', 'uv_experience_users', [
        'single' => false,
        'type' => 'integer',
        'show_in_rest' => true,
        'sanitize_callback' => 'absint',
        'auth_callback' => function(){ return current_user_can('edit_posts'); },
    ]);
});

function uv_core_get_experiences_for_user($user_id){
    return get_posts([
        'post_type' => 'uv_experience',
        'posts_per_page' => -1,
        'meta_query' => [[
            'key' => 'uv_experience_users',
            'value' => absint($user_id),
            'compare' => '=',
            'type' => 'NUMERIC',
        ]]
    ]);
}
