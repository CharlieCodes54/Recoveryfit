<?php
/**
 * Plugin Name: RecoveryFit Corporate Usage Dashboard
 * Description: Provides a MemberPress corporate account usage dashboard with invoice grouping and login metrics.
 * Version: 1.0.0
 * Author: RecoveryFit
 * Text Domain: recoveryfit-corp-dashboard
 */

namespace RecoveryFit\CorpDashboard;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

const VERSION = '1.0.0';
const PLUGIN_FILE = __FILE__;
const PLUGIN_DIR  = __DIR__;

require_once __DIR__ . '/includes/rf-config.php';
require_once __DIR__ . '/includes/class-rf-corp-data.php';
require_once __DIR__ . '/includes/class-rf-corp-dashboard.php';

/**
 * Bootstrap the plugin after plugins are loaded.
 *
 * @return void
 */
function bootstrap() {
    if ( ! class_exists( '\\MeprUser' ) || ! class_exists( '\\MPCA_Corporate_Account' ) ) {
        add_action( 'admin_notices', __NAMESPACE__ . '\\render_missing_dependencies_notice' );
        return;
    }

    Dashboard::get_instance();
}
add_action( 'plugins_loaded', __NAMESPACE__ . '\\bootstrap' );

/**
 * Render an admin notice when required MemberPress components are missing.
 *
 * @return void
 */
function render_missing_dependencies_notice() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    echo '<div class="notice notice-error"><p>';
    echo esc_html__( 'RecoveryFit Corporate Usage Dashboard requires MemberPress and the Corporate Accounts add-on to be active.', 'recoveryfit-corp-dashboard' );
    echo '</p></div>';
}
