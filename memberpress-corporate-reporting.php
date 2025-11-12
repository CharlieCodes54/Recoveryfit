<?php
/**
 * Plugin Name: MemberPress Corporate Reporting
 * Plugin URI: https://github.com/CharlieCodes54/Recoveryfit
 * Description: Advanced reporting dashboard for MemberPress corporate memberships showing parent account usage and sub-account login statistics
 * Version: 1.0.0
 * Author: RecoveryFit
 * Author URI: https://recoveryfit.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: mepr-corporate-reporting
 * Requires at least: 5.8
 * Requires PHP: 7.4
 *
 * @package MemberPressCorporateReporting
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('MEPR_CORP_VERSION', '1.0.0');
define('MEPR_CORP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('MEPR_CORP_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Main Plugin Class
 */
class MemberPress_Corporate_Reporting {

    /**
     * Instance of this class
     */
    private static $instance = null;

    /**
     * Get instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('wp_ajax_mepr_corp_get_report_data', array($this, 'ajax_get_report_data'));
        add_action('wp_ajax_mepr_corp_export_csv', array($this, 'ajax_export_csv'));
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Corporate Reports', 'mepr-corporate-reporting'),
            __('Corporate Reports', 'mepr-corporate-reporting'),
            'manage_options',
            'mepr-corporate-reports',
            array($this, 'render_dashboard_page'),
            'dashicons-chart-bar',
            30
        );
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        if ('toplevel_page_mepr-corporate-reports' !== $hook) {
            return;
        }

        // Enqueue CSS
        wp_enqueue_style(
            'mepr-corp-admin-css',
            MEPR_CORP_PLUGIN_URL . 'assets/admin.css',
            array(),
            MEPR_CORP_VERSION
        );

        // Enqueue jQuery if not already loaded
        wp_enqueue_script('jquery');

        // Enqueue admin JS
        wp_enqueue_script(
            'mepr-corp-admin-js',
            MEPR_CORP_PLUGIN_URL . 'assets/admin.js',
            array('jquery'),
            MEPR_CORP_VERSION,
            true
        );

        // Localize script with data
        wp_localize_script('mepr-corp-admin-js', 'meprCorp', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('mepr_corp_nonce'),
            'debug' => defined('WP_DEBUG') && WP_DEBUG,
            'plugin_url' => MEPR_CORP_PLUGIN_URL,
            'strings' => array(
                'loading' => __('Loading...', 'mepr-corporate-reporting'),
                'error' => __('Error loading data. Please try again.', 'mepr-corporate-reporting'),
                'no_data' => __('No data found.', 'mepr-corporate-reporting'),
            )
        ));
    }

    /**
     * Get corporate account data
     */
    public function get_corporate_data($filters = array()) {
        global $wpdb;

        // MemberPress tables
        $members_table = $wpdb->prefix . 'mepr_members';
        $transactions_table = $wpdb->prefix . 'mepr_transactions';
        $subscriptions_table = $wpdb->prefix . 'mepr_subscriptions';

        // Get corporate membership IDs from filters or use defaults (3888, 3889)
        $corporate_membership_ids = !empty($filters['membership_ids'])
            ? $filters['membership_ids']
            : array(3888, 3889);

        $membership_ids_str = implode(',', array_map('intval', $corporate_membership_ids));

        // Query to get parent corporate accounts with their sub-accounts
        // Only get PARENT accounts (those WITHOUT mpca_corporate_account_id meta key)
        $query = "
            SELECT
                parent.ID as parent_id,
                parent.user_login as parent_username,
                parent.user_email as parent_email,
                parent.display_name as parent_display_name,
                pm_company.meta_value as company_name,
                pm_location.meta_value as location,
                t.product_id as membership_id,
                COUNT(DISTINCT sub.ID) as sub_account_count,
                SUM(CAST(COALESCE(sm_login_count.meta_value, 0) AS UNSIGNED)) as total_logins,
                MAX(sm_last_login.meta_value) as last_login_date,
                t.created_at as parent_signup_date,
                t.status as transaction_status,
                s.status as subscription_status
            FROM {$wpdb->users} parent
            INNER JOIN {$transactions_table} t ON parent.ID = t.user_id
            LEFT JOIN {$subscriptions_table} s ON t.subscription_id = s.id
            LEFT JOIN {$wpdb->usermeta} pm_company ON parent.ID = pm_company.user_id AND pm_company.meta_key = 'mepr_company'
            LEFT JOIN {$wpdb->usermeta} pm_location ON parent.ID = pm_location.user_id AND pm_location.meta_key = 'mepr_location'
            LEFT JOIN {$wpdb->usermeta} pm_is_sub ON parent.ID = pm_is_sub.user_id AND pm_is_sub.meta_key = 'mpca_corporate_account_id'
            LEFT JOIN {$wpdb->users} sub ON sub.ID IN (
                SELECT user_id FROM {$wpdb->usermeta}
                WHERE meta_key = 'mpca_corporate_account_id'
                AND meta_value = parent.ID
            )
            LEFT JOIN {$wpdb->usermeta} sm_login_count ON sub.ID = sm_login_count.user_id AND sm_login_count.meta_key = '# Logins'
            LEFT JOIN {$wpdb->usermeta} sm_last_login ON sub.ID = sm_last_login.user_id AND sm_last_login.meta_key = 'Last Login'
            WHERE t.product_id IN ({$membership_ids_str})
            AND t.status IN ('complete', 'confirmed')
            AND pm_is_sub.meta_value IS NULL
        ";

        // Add filters
        if (!empty($filters['search'])) {
            $search = $wpdb->esc_like($filters['search']);
            $query .= $wpdb->prepare(" AND (parent.user_login LIKE %s OR parent.user_email LIKE %s OR parent.display_name LIKE %s OR pm_company.meta_value LIKE %s)",
                "%{$search}%", "%{$search}%", "%{$search}%", "%{$search}%");
        }

        if (!empty($filters['location'])) {
            $location = $wpdb->esc_like($filters['location']);
            $query .= $wpdb->prepare(" AND pm_location.meta_value LIKE %s", "%{$location}%");
        }

        if (!empty($filters['min_logins'])) {
            // This will be applied after grouping in PHP
        }

        $query .= " GROUP BY parent.ID, t.product_id";

        // Add sorting
        $order_by = !empty($filters['order_by']) ? $filters['order_by'] : 'total_logins';
        $order = !empty($filters['order']) && strtoupper($filters['order']) === 'ASC' ? 'ASC' : 'DESC';

        $valid_order_columns = array('parent_username', 'company_name', 'location', 'total_logins', 'last_login_date', 'sub_account_count');
        if (in_array($order_by, $valid_order_columns)) {
            $query .= " ORDER BY {$order_by} {$order}";
        } else {
            $query .= " ORDER BY total_logins DESC";
        }

        $results = $wpdb->get_results($query, ARRAY_A);

        // Post-process: filter by min_logins if needed
        if (!empty($filters['min_logins'])) {
            $min_logins = intval($filters['min_logins']);
            $results = array_filter($results, function($row) use ($min_logins) {
                return intval($row['total_logins']) >= $min_logins;
            });
        }

        // Enhance results with sub-account details
        foreach ($results as &$row) {
            $row['sub_accounts'] = $this->get_sub_accounts($row['parent_id']);
            $row['formatted_last_login'] = !empty($row['last_login_date'])
                ? human_time_diff(strtotime($row['last_login_date']), current_time('timestamp')) . ' ago'
                : 'Never';
        }

        return $results;
    }

    /**
     * Get sub-accounts for a parent
     */
    public function get_sub_accounts($parent_id) {
        global $wpdb;

        $subscriptions_table = $wpdb->prefix . 'mepr_subscriptions';

        $query = $wpdb->prepare("
            SELECT
                u.ID,
                u.user_login,
                u.user_email,
                u.display_name,
                um_login_count.meta_value as login_count,
                um_last_login.meta_value as last_login,
                (SELECT COUNT(*) FROM {$subscriptions_table} s
                 WHERE s.user_id = u.ID AND s.status = 'active') as active_subscription_count
            FROM {$wpdb->users} u
            INNER JOIN {$wpdb->usermeta} um_parent ON u.ID = um_parent.user_id
            LEFT JOIN {$wpdb->usermeta} um_login_count ON u.ID = um_login_count.user_id AND um_login_count.meta_key = '# Logins'
            LEFT JOIN {$wpdb->usermeta} um_last_login ON u.ID = um_last_login.user_id AND um_last_login.meta_key = 'Last Login'
            WHERE um_parent.meta_key = 'mpca_corporate_account_id'
            AND um_parent.meta_value = %d
            ORDER BY um_last_login.meta_value DESC
        ", $parent_id);

        $sub_accounts = $wpdb->get_results($query, ARRAY_A);

        // Format the data
        foreach ($sub_accounts as &$sub) {
            $sub['login_count'] = intval($sub['login_count'] ?? 0);
            $sub['formatted_last_login'] = !empty($sub['last_login'])
                ? human_time_diff(strtotime($sub['last_login']), current_time('timestamp')) . ' ago'
                : 'Never';
            // Check if user has active subscription
            $sub['is_active'] = isset($sub['active_subscription_count']) && intval($sub['active_subscription_count']) > 0;
        }

        return $sub_accounts;
    }

    /**
     * AJAX handler for getting report data
     */
    public function ajax_get_report_data() {
        try {
            check_ajax_referer('mepr_corp_nonce', 'nonce');

            if (!current_user_can('manage_options')) {
                wp_send_json_error(array('message' => 'Unauthorized'));
            }

            $filters = array(
                'search' => isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '',
                'location' => isset($_POST['location']) ? sanitize_text_field($_POST['location']) : '',
                'membership_ids' => isset($_POST['membership_ids']) ? array_map('intval', $_POST['membership_ids']) : array(),
                'min_logins' => isset($_POST['min_logins']) ? intval($_POST['min_logins']) : 0,
                'order_by' => isset($_POST['order_by']) ? sanitize_text_field($_POST['order_by']) : 'total_logins',
                'order' => isset($_POST['order']) ? sanitize_text_field($_POST['order']) : 'DESC',
            );

            $data = $this->get_corporate_data($filters);

            // Calculate summary statistics
            $summary = array(
                'total_corporate_accounts' => count($data),
                'total_sub_accounts' => array_sum(array_column($data, 'sub_account_count')),
                'total_logins' => array_sum(array_column($data, 'total_logins')),
                'average_logins_per_account' => count($data) > 0 ? round(array_sum(array_column($data, 'total_logins')) / count($data), 2) : 0,
            );

            wp_send_json_success(array(
                'data' => $data,
                'summary' => $summary,
                'debug' => array(
                    'filters' => $filters,
                    'record_count' => count($data),
                )
            ));
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => 'Error: ' . $e->getMessage(),
                'trace' => defined('WP_DEBUG') && WP_DEBUG ? $e->getTraceAsString() : null
            ));
        }
    }

    /**
     * AJAX handler for CSV export
     */
    public function ajax_export_csv() {
        check_ajax_referer('mepr_corp_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        $filters = array(
            'search' => isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '',
            'location' => isset($_GET['location']) ? sanitize_text_field($_GET['location']) : '',
            'membership_ids' => isset($_GET['membership_ids']) ? array_map('intval', explode(',', $_GET['membership_ids'])) : array(),
            'min_logins' => isset($_GET['min_logins']) ? intval($_GET['min_logins']) : 0,
            'order_by' => isset($_GET['order_by']) ? sanitize_text_field($_GET['order_by']) : 'total_logins',
            'order' => isset($_GET['order']) ? sanitize_text_field($_GET['order']) : 'DESC',
        );

        $data = $this->get_corporate_data($filters);

        // Set headers for CSV download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=memberpress-corporate-report-' . date('Y-m-d') . '.csv');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');

        // Add BOM for Excel UTF-8 support
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        // CSV Headers
        fputcsv($output, array(
            'Parent ID',
            'Parent Username',
            'Parent Email',
            'Company Name',
            'Location',
            'Membership ID',
            'Sub-Account Count',
            'Total Logins',
            'Last Login Date',
            'Last Login (Human)',
            'Signup Date',
            'Transaction Status',
            'Subscription Status'
        ));

        // CSV Data
        foreach ($data as $row) {
            fputcsv($output, array(
                $row['parent_id'],
                $row['parent_username'],
                $row['parent_email'],
                $row['company_name'] ?? 'N/A',
                $row['location'] ?? 'N/A',
                $row['membership_id'],
                $row['sub_account_count'],
                $row['total_logins'],
                $row['last_login_date'] ?? 'Never',
                $row['formatted_last_login'],
                $row['parent_signup_date'],
                $row['transaction_status'],
                $row['subscription_status'] ?? 'N/A'
            ));
        }

        fclose($output);
        exit;
    }

    /**
     * Render dashboard page
     */
    public function render_dashboard_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        include MEPR_CORP_PLUGIN_DIR . 'templates/dashboard.php';
    }
}

// Initialize the plugin
add_action('plugins_loaded', array('MemberPress_Corporate_Reporting', 'get_instance'));

/**
 * Activation hook
 */
register_activation_hook(__FILE__, 'mepr_corp_activate');
function mepr_corp_activate() {
    // Check if MemberPress is active
    if (!class_exists('MeprAppCtrl')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(
            __('MemberPress Corporate Reporting requires MemberPress to be installed and activated.', 'mepr-corporate-reporting'),
            __('Plugin Activation Error', 'mepr-corporate-reporting'),
            array('back_link' => true)
        );
    }

    // Create plugin tables if needed (for future enhancements)
    // For now, we're using MemberPress's existing tables
}
