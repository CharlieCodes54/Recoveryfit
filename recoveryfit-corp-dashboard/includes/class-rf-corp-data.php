<?php
/**
 * Data aggregation for the RecoveryFit Corporate Usage Dashboard.
 *
 * @package RecoveryFit\CorpDashboard
 */

namespace RecoveryFit\CorpDashboard;

use MeprUser;
use WP_User;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Handles fetching and structuring MemberPress corporate usage data.
 */
class Data {

    /**
     * Cached instance.
     *
     * @var Data|null
     */
    private static $instance = null;

    /**
     * WordPress database access.
     *
     * @var \wpdb
     */
    private $wpdb;

    /**
     * Constructor.
     */
    private function __construct() {
        global $wpdb;

        $this->wpdb = $wpdb;
    }

    /**
     * Singleton accessor.
     *
     * @return Data
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Build parent groups with aggregated statistics.
     *
     * @return array
     */
    public function build_parent_groups() {
        $table_name = $this->wpdb->prefix . RF_CORP_ACCOUNTS_TABLE_SUFFIX;

        $statuses = RF_ACTIVE_CORPORATE_STATUSES;
        if ( empty( $statuses ) ) {
            $query     = "SELECT * FROM {$table_name}";
            $prepared  = $query;
        } else {
            $placeholders = implode( ',', array_fill( 0, count( $statuses ), '%s' ) );
            $query        = "SELECT * FROM {$table_name} WHERE status IN ({$placeholders})";
            $params       = $statuses;
            array_unshift( $params, $query );
            $prepared = call_user_func_array( [ $this->wpdb, 'prepare' ], $params );
        }

        $results = $this->wpdb->get_results( $prepared );

        if ( empty( $results ) ) {
            return [];
        }

        $groups = [];

        foreach ( $results as $row ) {
            $corp_id = (int) $row->id;

            try {
                $corporate_account = new \MPCA_Corporate_Account( $corp_id );
            } catch ( \Throwable $th ) {
                continue;
            }

            $parent_user = $corporate_account->user();

            if ( ! $parent_user instanceof MeprUser ) {
                continue;
            }

            $parent_label = rf_get_parent_label( $parent_user );

            $sub_users = $corporate_account->sub_users();
            if ( ! is_array( $sub_users ) ) {
                $sub_users = [];
            }

            $members = array_merge( [ $parent_user ], $sub_users );

            $sub_accounts       = [];
            $group_total_logins = 0;
            $group_last_ts      = null;

            foreach ( $members as $member ) {
                if ( ! $member instanceof WP_User ) {
                    continue;
                }

                $entry = $this->build_user_entry( $member );

                $sub_accounts[] = $entry;
                $group_total_logins += (int) $entry['login_count'];

                if ( ! empty( $entry['last_login_ts'] ) ) {
                    if ( null === $group_last_ts || (int) $entry['last_login_ts'] > $group_last_ts ) {
                        $group_last_ts = (int) $entry['last_login_ts'];
                    }
                }
            }

            $groups[] = [
                'corp_id'        => $corp_id,
                'parent_user_id' => (int) $parent_user->ID,
                'parent_label'   => $parent_label,
                'total_logins'   => $group_total_logins,
                'last_login'     => $this->format_timestamp( $group_last_ts ),
                'last_login_ts'  => $group_last_ts,
                'sub_accounts'   => $sub_accounts,
            ];
        }

        return $groups;
    }

    /**
     * Build the invoice hierarchy containing parent groups.
     *
     * @return array
     */
    public function build_invoice_hierarchy() {
        $parent_groups = $this->build_parent_groups();

        if ( empty( $parent_groups ) ) {
            return [];
        }

        $invoices = [];

        foreach ( $parent_groups as $parent_group ) {
            $invoice_label = rf_get_invoice_label_for_parent( $parent_group['parent_label'] );

            if ( ! isset( $invoices[ $invoice_label ] ) ) {
                $invoices[ $invoice_label ] = [
                    'invoice_label' => $invoice_label,
                    'total_logins'  => 0,
                    'last_login'    => null,
                    'last_login_ts' => null,
                    'parents'       => [],
                ];
            }

            $invoices[ $invoice_label ]['parents'][] = $parent_group;
            $invoices[ $invoice_label ]['total_logins'] += (int) $parent_group['total_logins'];

            $parent_last_ts = $parent_group['last_login_ts'];
            if ( ! empty( $parent_last_ts ) ) {
                if ( null === $invoices[ $invoice_label ]['last_login_ts'] || $parent_last_ts > $invoices[ $invoice_label ]['last_login_ts'] ) {
                    $invoices[ $invoice_label ]['last_login_ts'] = $parent_last_ts;
                    $invoices[ $invoice_label ]['last_login']    = $this->format_timestamp( $parent_last_ts );
                }
            }
        }

        return array_values( $invoices );
    }

    /**
     * Build an entry for a specific user with login metrics and memberships.
     *
     * @param WP_User $user User instance.
     *
     * @return array
     */
    private function build_user_entry( WP_User $user ) {
        $login_count = (int) get_user_meta( $user->ID, RF_LOGIN_COUNT_META_KEY, true );
        $last_login  = get_user_meta( $user->ID, RF_LAST_LOGIN_META_KEY, true );

        $last_login_ts = null;
        $last_login_str = null;

        if ( ! empty( $last_login ) ) {
            if ( is_numeric( $last_login ) ) {
                $last_login_ts = (int) $last_login;
            } else {
                $parsed = strtotime( (string) $last_login );
                if ( false !== $parsed ) {
                    $last_login_ts = $parsed;
                }
            }

            if ( null !== $last_login_ts ) {
                $last_login_str = $this->format_timestamp( $last_login_ts );
            } else {
                $last_login_str = (string) $last_login;
            }
        }

        $memberships = $this->get_user_memberships( $user );

        $name = trim( $user->first_name . ' ' . $user->last_name );
        if ( '' === $name ) {
            $name = (string) $user->display_name;
        }
        if ( '' === $name ) {
            $name = (string) $user->user_login;
        }

        $registered_at = $this->format_datetime_string( $user->user_registered );

        return [
            'user_id'       => (int) $user->ID,
            'name'          => $name,
            'email'         => (string) $user->user_email,
            'username'      => (string) $user->user_login,
            'login_count'   => $login_count,
            'last_login'    => $last_login_str,
            'last_login_ts' => $last_login_ts,
            'memberships'   => $memberships,
            'registered_at' => $registered_at,
        ];
    }

    /**
     * Retrieve active memberships for a user.
     *
     * @param WP_User $user User instance.
     *
     * @return array
     */
    private function get_user_memberships( WP_User $user ) {
        if ( ! $user instanceof MeprUser ) {
            $user = new MeprUser( $user->ID );
        }

        $memberships   = [];
        $subscriptions = $user->active_product_subscriptions( 'objects' );

        if ( ! is_array( $subscriptions ) ) {
            return $memberships;
        }

        foreach ( $subscriptions as $subscription ) {
            if ( ! is_object( $subscription ) ) {
                continue;
            }

            $product = null;
            if ( method_exists( $subscription, 'product' ) ) {
                $product = $subscription->product();
            }

            $product_id    = ( $product && isset( $product->ID ) ) ? (int) $product->ID : 0;
            $product_title = '';

            if ( $product_id ) {
                if ( isset( $product->post_title ) && '' !== $product->post_title ) {
                    $product_title = (string) $product->post_title;
                } else {
                    $product_title = get_the_title( $product_id );
                }
            }

            $memberships[] = [
                'product_id'      => $product_id,
                'product_title'   => $product_title,
                'subscription_id' => isset( $subscription->id ) ? (int) $subscription->id : 0,
            ];
        }

        return $memberships;
    }

    /**
     * Format a timestamp into a localized string.
     *
     * @param int|null $timestamp Unix timestamp.
     *
     * @return string|null
     */
    private function format_timestamp( $timestamp ) {
        if ( empty( $timestamp ) ) {
            return null;
        }

        return date_i18n( 'Y-m-d H:i:s', (int) $timestamp );
    }

    /**
     * Normalize MySQL datetime strings.
     *
     * @param string $datetime Datetime string.
     *
     * @return string
     */
    private function format_datetime_string( $datetime ) {
        if ( empty( $datetime ) ) {
            return '';
        }

        $timestamp = strtotime( $datetime );
        if ( false === $timestamp ) {
            return (string) $datetime;
        }

        return date_i18n( 'Y-m-d H:i:s', $timestamp );
    }
}
