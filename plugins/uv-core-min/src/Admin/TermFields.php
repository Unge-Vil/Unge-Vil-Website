<?php

declare(strict_types=1);

namespace UV\CoreMin\Admin;

final class TermFields
{
    public static function register(): void
    {
        add_action('uv_location_add_form_fields', [self::class, 'renderLocationAddFields']);
        add_action('uv_location_edit_form_fields', [self::class, 'renderLocationEditFields']);
        add_action('created_uv_location', [self::class, 'saveLocation']);
        add_action('edited_uv_location', [self::class, 'saveLocation']);

        add_action('uv_position_add_form_fields', [self::class, 'renderPositionAddFields']);
        add_action('uv_position_edit_form_fields', [self::class, 'renderPositionEditFields']);
        add_action('created_uv_position', [self::class, 'savePosition']);
        add_action('edited_uv_position', [self::class, 'savePosition']);
    }

    public static function renderLocationAddFields(): void
    {
        wp_nonce_field('uv_core_min_location_term_meta', 'uv_core_min_location_nonce');
        ?>
        <div class="form-field">
            <label for="uv_location_image"><?php esc_html_e('Stedsbilde (attachment ID)', 'uv-core-min'); ?></label>
            <input type="number" id="uv_location_image" name="uv_location_image" value="" min="0">
        </div>
        <div class="form-field">
            <label for="uv_location_page"><?php esc_html_e('Stedside', 'uv-core-min'); ?></label>
            <?php wp_dropdown_pages([
                'post_type' => 'page',
                'name' => 'uv_location_page',
                'id' => 'uv_location_page',
                'show_option_none' => esc_html__('— Ingen —', 'uv-core-min'),
            ]); ?>
        </div>
        <?php
    }

    public static function renderLocationEditFields(\WP_Term $term): void
    {
        $imageId = absint((string) get_term_meta($term->term_id, 'uv_location_image', true));
        $pageId = absint((string) get_term_meta($term->term_id, 'uv_location_page', true));
        wp_nonce_field('uv_core_min_location_term_meta', 'uv_core_min_location_nonce');
        ?>
        <tr class="form-field">
            <th scope="row"><label for="uv_location_image"><?php esc_html_e('Stedsbilde (attachment ID)', 'uv-core-min'); ?></label></th>
            <td><input type="number" id="uv_location_image" name="uv_location_image" value="<?php echo esc_attr((string) $imageId); ?>" min="0"></td>
        </tr>
        <tr class="form-field">
            <th scope="row"><label for="uv_location_page"><?php esc_html_e('Stedside', 'uv-core-min'); ?></label></th>
            <td>
                <?php wp_dropdown_pages([
                    'post_type' => 'page',
                    'name' => 'uv_location_page',
                    'id' => 'uv_location_page',
                    'selected' => $pageId,
                    'show_option_none' => esc_html__('— Ingen —', 'uv-core-min'),
                ]); ?>
            </td>
        </tr>
        <?php
    }

    public static function saveLocation(int $termId): void
    {
        if (!current_user_can('manage_categories')) {
            return;
        }
        if (!isset($_POST['uv_core_min_location_nonce']) || !wp_verify_nonce(sanitize_text_field((string) wp_unslash($_POST['uv_core_min_location_nonce'])), 'uv_core_min_location_term_meta')) {
            return;
        }

        if (isset($_POST['uv_location_image'])) {
            update_term_meta($termId, 'uv_location_image', absint((string) wp_unslash($_POST['uv_location_image'])));
        }

        if (isset($_POST['uv_location_page'])) {
            update_term_meta($termId, 'uv_location_page', absint((string) wp_unslash($_POST['uv_location_page'])));
        }
    }

    public static function renderPositionAddFields(): void
    {
        wp_nonce_field('uv_core_min_position_term_meta', 'uv_core_min_position_nonce');
        ?>
        <div class="form-field term-rank-weight-wrap">
            <label for="uv_rank_weight"><?php esc_html_e('Rangeringsvekt', 'uv-core-min'); ?></label>
            <input type="number" name="uv_rank_weight" id="uv_rank_weight" value="0" step="1" min="0">
        </div>
        <?php
    }

    public static function renderPositionEditFields(\WP_Term $term): void
    {
        $value = (int) get_term_meta($term->term_id, 'uv_rank_weight', true);
        wp_nonce_field('uv_core_min_position_term_meta', 'uv_core_min_position_nonce');
        ?>
        <tr class="form-field term-rank-weight-wrap">
            <th scope="row"><label for="uv_rank_weight"><?php esc_html_e('Rangeringsvekt', 'uv-core-min'); ?></label></th>
            <td><input type="number" name="uv_rank_weight" id="uv_rank_weight" value="<?php echo esc_attr((string) $value); ?>" step="1" min="0"></td>
        </tr>
        <?php
    }

    public static function savePosition(int $termId): void
    {
        if (!current_user_can('manage_categories')) {
            return;
        }
        if (!isset($_POST['uv_core_min_position_nonce']) || !wp_verify_nonce(sanitize_text_field((string) wp_unslash($_POST['uv_core_min_position_nonce'])), 'uv_core_min_position_term_meta')) {
            return;
        }
        if (!isset($_POST['uv_rank_weight'])) {
            return;
        }

        update_term_meta($termId, 'uv_rank_weight', (int) wp_unslash($_POST['uv_rank_weight']));
    }
}
