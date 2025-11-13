<?php
/**
 * RecoveryFit Corporate Usage Dashboard configuration and helpers.
 *
 * @package RecoveryFit\CorpDashboard
 */

namespace RecoveryFit\CorpDashboard;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use MeprUser;

const RF_CORP_ACCOUNTS_TABLE_SUFFIX = 'mepr_corporate_accounts';
const RF_SUBACCOUNT_META_KEY        = 'mpca_corporate_account_id';
const RF_LOGIN_COUNT_META_KEY       = 'mepr_login_count';
const RF_LAST_LOGIN_META_KEY        = 'mepr_last_login';

const RF_ACTIVE_CORPORATE_STATUSES = [ 'enabled', 'active' ];

const RF_PARENT_INVOICE_MAP = [
    'Jenny_OPI'                        => 'OPI',
    'Newport_Charlotte'                => 'North Carolina RTC',
    'Newport-Cerro Vista'              => 'OCYA',
    'Newport-Charlotte NC'             => 'North Carolina RTC',
    'Newport-Charlotte RTC'            => 'North Carolina RTC',
    'Newport-Deerhaven'                => 'OCYA',
    'Newport-DeerhavenMiramar'         => 'OCYA',
    'Newport-East Bay'                 => 'Norcal-Eastbay',
    'Newport-Eastbay'                  => 'Norcal-Eastbay',
    'Newport-Lewis'                    => 'OCYA',
    'Newport-Miramar'                  => 'OCYA',
    'Newport-Periwinkle'               => 'OCYA',
    'Newport-Westlake'                 => 'Westlake OP',
    'NewportAcademy-Fairfax'           => 'Fairfax',
    'Newporthealthcare-shinglesprings' => 'Sacramento RTC',
    'newporthealthcare-shinglesprings' => 'Sacramento RTC',
];

/**
 * Normalize a label for comparison.
 *
 * @param string $label Original label.
 *
 * @return string
 */
function rf_normalize_label( $label ) {
    $label = strtolower( trim( (string) $label ) );
    $label = preg_replace( '/[\-_]+/', ' ', $label );
    $label = preg_replace( '/\s+/', ' ', $label );

    return $label ?: '';
}

/**
 * Get the invoice label for a parent label.
 *
 * @param string $parent_label Parent label to resolve.
 *
 * @return string
 */
function rf_get_invoice_label_for_parent( $parent_label ) {
    if ( isset( RF_PARENT_INVOICE_MAP[ $parent_label ] ) ) {
        return RF_PARENT_INVOICE_MAP[ $parent_label ];
    }

    $normalized_parent = rf_normalize_label( $parent_label );

    foreach ( RF_PARENT_INVOICE_MAP as $key => $value ) {
        if ( rf_normalize_label( $key ) === $normalized_parent ) {
            return $value;
        }
    }

    return 'Unmapped';
}

/**
 * Derive the parent label for a user.
 *
 * @param MeprUser $user MemberPress user object.
 *
 * @return string
 */
function rf_get_parent_label( MeprUser $user ) {
    $company  = trim( (string) get_user_meta( $user->ID, 'mepr_company', true ) );
    $location = trim( (string) get_user_meta( $user->ID, 'mepr_tc_location', true ) );

    if ( '' !== $company && '' !== $location ) {
        return $company . '-' . $location;
    }

    if ( '' !== $company ) {
        return $company;
    }

    $login = (string) $user->user_login;
    if ( '' !== $login ) {
        $parts = preg_split( '/[\-_]/', $login );
        if ( is_array( $parts ) && count( $parts ) >= 2 ) {
            return $parts[0] . '-' . $parts[1];
        }

        return $login;
    }

    return sprintf( 'User-%d', (int) $user->ID );
}
