<?php
/**
 * Admin dashboard rendering for RecoveryFit Corporate Usage Dashboard.
 *
 * @package RecoveryFit\CorpDashboard
 */

namespace RecoveryFit\CorpDashboard;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Handles admin UI integration for the dashboard.
 */
class Dashboard {

    /**
     * Singleton instance.
     *
     * @var Dashboard|null
     */
    private static $instance = null;

    /**
     * Screen hook suffix for the dashboard page.
     *
     * @var string
     */
    private $page_hook = '';

    /**
     * Data service instance.
     *
     * @var Data
     */
    private $data_service;

    /**
     * Constructor.
     */
    private function __construct() {
        $this->data_service = Data::get_instance();

        add_action( 'admin_menu', [ $this, 'register_menu' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
    }

    /**
     * Get the singleton instance.
     *
     * @return Dashboard
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Register the admin menu entry.
     *
     * @return void
     */
    public function register_menu() {
        $capability = 'manage_options';

        $this->page_hook = add_submenu_page(
            'memberpress',
            __( 'Corporate Usage Dashboard', 'recoveryfit-corp-dashboard' ),
            __( 'Corporate Usage Dashboard', 'recoveryfit-corp-dashboard' ),
            $capability,
            'rf-corp-dashboard',
            [ $this, 'render_page' ]
        );

        if ( empty( $this->page_hook ) ) {
            $this->page_hook = add_menu_page(
                __( 'Corporate Usage Dashboard', 'recoveryfit-corp-dashboard' ),
                __( 'Corporate Usage', 'recoveryfit-corp-dashboard' ),
                $capability,
                'rf-corp-dashboard',
                [ $this, 'render_page' ],
                'dashicons-chart-area',
                56
            );
        }
    }

    /**
     * Enqueue assets for the dashboard page.
     *
     * @param string $hook Hook suffix for the current admin page.
     *
     * @return void
     */
    public function enqueue_assets( $hook ) {
        if ( $hook !== $this->page_hook ) {
            return;
        }

        $plugin_url = plugin_dir_url( PLUGIN_FILE );

        wp_enqueue_style(
            'rf-corp-dashboard',
            $plugin_url . 'assets/css/rf-corp-dashboard.css',
            [],
            VERSION
        );

        wp_enqueue_script(
            'rf-corp-dashboard',
            $plugin_url . 'assets/js/rf-corp-dashboard.js',
            [],
            VERSION,
            true
        );
    }

    /**
     * Render the dashboard page.
     *
     * @return void
     */
    public function render_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have permission to access this page.', 'recoveryfit-corp-dashboard' ) );
        }

        $invoice_groups = $this->data_service->build_invoice_hierarchy();

        wp_localize_script(
            'rf-corp-dashboard',
            'RF_CORP_DATA',
            [
                'invoices' => $invoice_groups,
            ]
        );
        ?>
        <div class="wrap rf-corp-dashboard-wrap">
            <h1><?php esc_html_e( 'Corporate Usage Dashboard', 'recoveryfit-corp-dashboard' ); ?></h1>
            <div id="rf-corp-dashboard-root">
                <p><?php esc_html_e( 'Loading corporate usage data...', 'recoveryfit-corp-dashboard' ); ?></p>
            </div>
        </div>
        <?php
    }
}
