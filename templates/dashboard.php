<?php
/**
 * Dashboard Template
 *
 * @package MemberPressCorporateReporting
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap mepr-corp-dashboard">
    <h1 class="wp-heading-inline">
        <?php _e('Corporate Membership Reports', 'mepr-corporate-reporting'); ?>
    </h1>

    <hr class="wp-header-end">

    <!-- Summary Cards -->
    <div class="mepr-corp-summary" id="meprCorpSummary">
        <div class="mepr-corp-summary-card">
            <div class="mepr-corp-summary-icon">
                <span class="dashicons dashicons-groups"></span>
            </div>
            <div class="mepr-corp-summary-content">
                <div class="mepr-corp-summary-label"><?php _e('Corporate Accounts', 'mepr-corporate-reporting'); ?></div>
                <div class="mepr-corp-summary-value" id="totalCorporateAccounts">—</div>
            </div>
        </div>

        <div class="mepr-corp-summary-card">
            <div class="mepr-corp-summary-icon">
                <span class="dashicons dashicons-admin-users"></span>
            </div>
            <div class="mepr-corp-summary-content">
                <div class="mepr-corp-summary-label"><?php _e('Total Sub-Accounts', 'mepr-corporate-reporting'); ?></div>
                <div class="mepr-corp-summary-value" id="totalSubAccounts">—</div>
            </div>
        </div>

        <div class="mepr-corp-summary-card">
            <div class="mepr-corp-summary-icon">
                <span class="dashicons dashicons-chart-line"></span>
            </div>
            <div class="mepr-corp-summary-content">
                <div class="mepr-corp-summary-label"><?php _e('Total Logins', 'mepr-corporate-reporting'); ?></div>
                <div class="mepr-corp-summary-value" id="totalLogins">—</div>
            </div>
        </div>

        <div class="mepr-corp-summary-card">
            <div class="mepr-corp-summary-icon">
                <span class="dashicons dashicons-chart-bar"></span>
            </div>
            <div class="mepr-corp-summary-content">
                <div class="mepr-corp-summary-label"><?php _e('Avg. Logins/Account', 'mepr-corporate-reporting'); ?></div>
                <div class="mepr-corp-summary-value" id="averageLogins">—</div>
            </div>
        </div>
    </div>

    <!-- Filters Section -->
    <div class="mepr-corp-filters">
        <div class="mepr-corp-filters-row">
            <div class="mepr-corp-filter-group">
                <label for="filterSearch">
                    <?php _e('Search', 'mepr-corporate-reporting'); ?>
                </label>
                <input
                    type="text"
                    id="filterSearch"
                    class="mepr-corp-input"
                    placeholder="<?php esc_attr_e('Username, email, company...', 'mepr-corporate-reporting'); ?>"
                />
            </div>

            <div class="mepr-corp-filter-group">
                <label for="filterLocation">
                    <?php _e('Location', 'mepr-corporate-reporting'); ?>
                </label>
                <input
                    type="text"
                    id="filterLocation"
                    class="mepr-corp-input"
                    placeholder="<?php esc_attr_e('Filter by location...', 'mepr-corporate-reporting'); ?>"
                />
            </div>

            <div class="mepr-corp-filter-group">
                <label for="filterMinLogins">
                    <?php _e('Min. Logins', 'mepr-corporate-reporting'); ?>
                </label>
                <input
                    type="number"
                    id="filterMinLogins"
                    class="mepr-corp-input"
                    min="0"
                    value="0"
                    placeholder="0"
                />
            </div>

            <div class="mepr-corp-filter-group">
                <label for="sortBy">
                    <?php _e('Sort By', 'mepr-corporate-reporting'); ?>
                </label>
                <select id="sortBy" class="mepr-corp-select">
                    <option value="total_logins"><?php _e('Total Logins', 'mepr-corporate-reporting'); ?></option>
                    <option value="last_login_date"><?php _e('Last Login', 'mepr-corporate-reporting'); ?></option>
                    <option value="sub_account_count"><?php _e('Sub-Account Count', 'mepr-corporate-reporting'); ?></option>
                    <option value="company_name"><?php _e('Company Name', 'mepr-corporate-reporting'); ?></option>
                    <option value="parent_username"><?php _e('Username', 'mepr-corporate-reporting'); ?></option>
                </select>
            </div>

            <div class="mepr-corp-filter-group">
                <label for="sortOrder">
                    <?php _e('Order', 'mepr-corporate-reporting'); ?>
                </label>
                <select id="sortOrder" class="mepr-corp-select">
                    <option value="DESC"><?php _e('Descending', 'mepr-corporate-reporting'); ?></option>
                    <option value="ASC"><?php _e('Ascending', 'mepr-corporate-reporting'); ?></option>
                </select>
            </div>
        </div>

        <div class="mepr-corp-filters-actions">
            <button type="button" id="applyFilters" class="button button-primary">
                <span class="dashicons dashicons-filter"></span>
                <?php _e('Apply Filters', 'mepr-corporate-reporting'); ?>
            </button>

            <button type="button" id="resetFilters" class="button">
                <span class="dashicons dashicons-update"></span>
                <?php _e('Reset', 'mepr-corporate-reporting'); ?>
            </button>

            <button type="button" id="exportCSV" class="button button-secondary">
                <span class="dashicons dashicons-download"></span>
                <?php _e('Export CSV', 'mepr-corporate-reporting'); ?>
            </button>
        </div>
    </div>

    <!-- Results Section -->
    <div class="mepr-corp-results">
        <div id="meprCorpLoading" class="mepr-corp-loading" style="display: none;">
            <div class="mepr-corp-spinner"></div>
            <p><?php _e('Loading corporate account data...', 'mepr-corporate-reporting'); ?></p>
        </div>

        <div id="meprCorpError" class="notice notice-error mepr-corp-error" style="display: none;">
            <p></p>
        </div>

        <div id="meprCorpNoData" class="mepr-corp-no-data" style="display: none;">
            <span class="dashicons dashicons-info"></span>
            <p><?php _e('No corporate accounts found matching your criteria.', 'mepr-corporate-reporting'); ?></p>
        </div>

        <div id="meprCorpData" class="mepr-corp-data">
            <!-- Data will be inserted here via JavaScript -->
        </div>
    </div>
</div>
