<?php
/**
 * Data provider for the RecoveryFit Member Usage Dashboard.
 */

namespace RecoveryFit\MemberDashboard;

use WP_User;
use WP_User_Query;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Builds structured data for MemberPress users.
 */
class Member_Data {
    /**
     * Singleton instance.
     *
     * @var Member_Data|null
     */
    protected static $instance = null;

    /**
     * Retrieve the singleton instance.
     */
    public static function instance(): Member_Data {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Fetch member data and derived insights for the dashboard.
     *
     * @return array<string,mixed>
     */
    public function get_dashboard_payload(): array {
        $members = $this->get_members();

        $totals = [
            'total_members'      => count($members),
            'active_30'          => 0,
            'never_logged_in'    => 0,
            'total_login_events' => 0,
        ];

        $now = time();
        $thirty_days_ago = $now - (30 * DAY_IN_SECONDS);

        foreach ($members as $member) {
            $totals['total_login_events'] += (int) $member['login_count'];

            if (empty($member['last_login_ts'])) {
                $totals['never_logged_in']++;
            }

            if (!empty($member['last_login_ts']) && (int) $member['last_login_ts'] >= $thirty_days_ago) {
                $totals['active_30']++;
            }
        }

        return [
            'members' => $members,
            'totals'  => $totals,
        ];
    }

    /**
     * Retrieve all MemberPress members.
     *
     * @return array<int,array<string,mixed>>
     */
    protected function get_members(): array {
        $args = [
            'fields'  => [ 'ID' ],
            'orderby' => 'registered',
            'order'   => 'DESC',
            // Explicitly request all users; leaving "number" empty defaults to 10.
            'number'  => -1,
        ];

        $query = new WP_User_Query($args);
        $results = [];

        foreach ($query->get_results() as $user_obj) {
            if ($user_obj instanceof WP_User) {
                $results[] = $this->format_member($user_obj);
            }
        }

        return $results;
    }

    /**
     * Format a user into the dashboard structure.
     */
    protected function format_member(WP_User $user): array {
        $user_id = (int) $user->ID;
        $login_count = (int) get_user_meta($user_id, 'mepr_login_count', true);
        $last_login_raw = get_user_meta($user_id, 'mepr_last_login', true);
        $last_login_ts = $this->normalize_timestamp($last_login_raw);

        $memberships = [];
        if (class_exists('MeprUser')) {
            $mepr_user = new \MeprUser($user_id);
            $subscriptions = $mepr_user->active_product_subscriptions('objects');

            if (is_array($subscriptions)) {
                foreach ($subscriptions as $subscription) {
                    if (!is_object($subscription)) {
                        continue;
                    }

                    $product = method_exists($subscription, 'product') ? $subscription->product() : null;
                    $product_id = $product ? (int) $product->ID : 0;
                    $product_title = $product ? $product->post_title : __('Unknown product', 'recoveryfit-member-dashboard');

                    $memberships[] = [
                        'subscription_id' => (int) ($subscription->ID ?? 0),
                        'product_id'      => $product_id,
                        'product_title'   => $product_title,
                        'status'          => $subscription->status ?? '',
                        'created_at'      => $subscription->created_at ?? '',
                    ];
                }
            }
        }

        return [
            'user_id'        => $user_id,
            'display_name'   => $user->display_name,
            'email'          => $user->user_email,
            'username'       => $user->user_login,
            'role'           => implode(', ', $user->roles),
            'login_count'    => $login_count,
            'last_login'     => $last_login_ts ? gmdate('Y-m-d H:i:s', $last_login_ts) : null,
            'last_login_ts'  => $last_login_ts,
            'registered_at'  => $user->user_registered,
            'memberships'    => $memberships,
        ];
    }

    /**
     * Normalize various timestamp formats into a UNIX timestamp.
     *
     * @param mixed $value Raw meta value.
     */
    protected function normalize_timestamp($value): ?int {
        if (empty($value)) {
            return null;
        }

        if (is_numeric($value)) {
            $int_val = (int) $value;
            return $int_val > 0 ? $int_val : null;
        }

        $timestamp = strtotime((string) $value);
        return $timestamp ?: null;
    }
}
