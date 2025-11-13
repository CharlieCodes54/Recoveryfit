<?php
/**
 * Plugin Name:       RecoveryFit Member Usage Dashboard
 * Plugin URI:        https://recoveryfit.com/
 * Description:       Provides an interactive dashboard to explore MemberPress member usage metrics.
 * Version:           1.0.0
 * Author:            RecoveryFit Engineering
 * Author URI:        https://recoveryfit.com/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       recoveryfit-member-dashboard
 * Domain Path:       /languages
 */

namespace RecoveryFit\MemberDashboard;

if (!defined('ABSPATH')) {
    exit;
}

const RF_MD_VERSION = '1.0.0';
const RF_MD_PLUGIN_FILE = __FILE__;
const RF_MD_PLUGIN_DIR = __DIR__;

require_once RF_MD_PLUGIN_DIR . '/includes/class-rf-member-data.php';
require_once RF_MD_PLUGIN_DIR . '/includes/class-rf-member-dashboard.php';

/**
 * Bootstrap the dashboard once plugins are loaded.
 */
function bootstrap() {
    $dashboard = Dashboard::instance();
    $dashboard->init();
}
add_action('plugins_loaded', __NAMESPACE__ . '\\bootstrap');
