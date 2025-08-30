<?php
/**
 * Migration script to move uv_team_assignment data into user meta.
 *
 * Usage: wp eval-file plugins/uv-people/migrate-team-assignments.php
 */

if (!defined('ABSPATH')) {
    exit; // Ensure WordPress context
}

if (!function_exists('get_posts')) {
    echo "This script must be run within WordPress.";
    return;
}

$assignments = get_posts([
    'post_type'      => 'uv_team_assignment',
    'posts_per_page' => -1,
    'post_status'    => 'any',
    'fields'         => 'ids',
]);

foreach ($assignments as $pid) {
    $uid = intval(get_post_meta($pid, 'uv_user_id', true));
    $lid = intval(get_post_meta($pid, 'uv_location_id', true));
    if (!$uid || !$lid) {
        wp_delete_post($pid, true);
        continue;
    }
    $locs = get_user_meta($uid, 'uv_location_terms', true);
    if (!is_array($locs)) {
        $locs = [];
    }
    if (!in_array($lid, $locs, true)) {
        $locs[] = $lid;
        update_user_meta($uid, 'uv_location_terms', $locs);
    }
    $primary = get_post_meta($pid, 'uv_is_primary', true) === '1';
    if ($primary) {
        $primary_locs = get_user_meta($uid, 'uv_primary_locations', true);
        if (!is_array($primary_locs)) {
            $primary_locs = [];
        }
        if (!in_array($lid, $primary_locs, true)) {
            $primary_locs[] = $lid;
            update_user_meta($uid, 'uv_primary_locations', $primary_locs);
        }
    }
    wp_delete_post($pid, true);
}

echo sprintf("Migrated %d assignments.\n", count($assignments));
