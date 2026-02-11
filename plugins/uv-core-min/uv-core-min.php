<?php
/**
 * Plugin Name: UV Core Min
 * Description: Minimal data/admin core for Unge Vil CPTs, taxonomies, and essential metadata.
 * Version: 0.1.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: Unge Vil
 * Text Domain: uv-core-min
 */

declare(strict_types=1);

namespace UV\CoreMin;

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/src/Plugin.php';
require_once __DIR__ . '/src/PostTypes.php';
require_once __DIR__ . '/src/Taxonomies.php';
require_once __DIR__ . '/src/Meta.php';
require_once __DIR__ . '/src/Admin/Notices.php';
require_once __DIR__ . '/src/Admin/TermFields.php';

Plugin::boot(__FILE__);
