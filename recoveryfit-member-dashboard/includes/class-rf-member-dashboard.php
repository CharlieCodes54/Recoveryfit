<?php
/**
 * Admin integration for the RecoveryFit Member Usage Dashboard.
 */

namespace RecoveryFit\MemberDashboard;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handles menu registration, asset loading, and page rendering.
 */
class Dashboard {
    /**
     * Singleton instance.
     *
     * @var Dashboard|null
     */
    protected static $instance = null;

    /**
     * Menu slug.
     */
    const MENU_SLUG = 'recoveryfit-member-dashboard';

    /**
     * Cached hook suffix for the dashboard page.
     *
     * @var string|null
     */
    protected $page_hook = null;

    /**
     * Retrieve the singleton instance.
     */
    public static function instance(): Dashboard {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Set up admin hooks.
     */
    public function init(): void {
        add_action('admin_menu', [ $this, 'register_menu' ]);
        add_action('admin_enqueue_scripts', [ $this, 'enqueue_assets' ]);
    }

    /**
     * Register the dashboard menu item.
     */
    public function register_menu(): void {
        $this->page_hook = add_menu_page(
            __('Member Usage Dashboard', 'recoveryfit-member-dashboard'),
            __('Member Usage Dashboard', 'recoveryfit-member-dashboard'),
            'manage_options',
            self::MENU_SLUG,
            [ $this, 'render_page' ],
            'dashicons-chart-bar',
            58
        );
    }

    /**
     * Enqueue CSS and JS for the dashboard.
     */
    public function enqueue_assets(string $hook_suffix): void {
        if ($this->page_hook !== $hook_suffix) {
            return;
        }

        wp_enqueue_style(
            'rf-member-dashboard',
            plugins_url('assets/css/rf-member-dashboard.css', RF_MD_PLUGIN_FILE),
            [],
            RF_MD_VERSION
        );

        wp_enqueue_script(
            'rf-member-dashboard',
            plugins_url('assets/js/rf-member-dashboard.js', RF_MD_PLUGIN_FILE),
            [ 'wp-i18n' ],
            RF_MD_VERSION,
            true
        );

        $data = Member_Data::instance()->get_dashboard_payload();

        wp_localize_script(
            'rf-member-dashboard',
            'RF_MEMBER_DATA',
            [
                'members'      => $data['members'],
                'totals'       => $data['totals'],
                'memberpress'  => class_exists('MeprUser'),
                'strings'      => [
                    'noMembers' => __('No members found for the current filters.', 'recoveryfit-member-dashboard'),
                ],
            ]
        );
    }

    /**
     * Render the dashboard markup.
     */
    public function render_page(): void {
        echo '<div class="wrap rf-md-wrap">';
        echo '<h1>' . esc_html__('Member Usage Dashboard', 'recoveryfit-member-dashboard') . '</h1>';

        if (!class_exists('MeprUser')) {
            echo '<div class="notice notice-warning"><p>' . esc_html__('MemberPress is not active. Membership insights may be limited.', 'recoveryfit-member-dashboard') . '</p></div>';
        }

        echo '<div id="rf-member-dashboard-root" class="rf-md-root"></div>';
        echo '</div>';
    }
}
