/**
 * MemberPress Corporate Reporting - Admin JavaScript
 *
 * @package MemberPressCorporateReporting
 */

(function($) {
    'use strict';

    const MeprCorpReporting = {
        /**
         * Initialize
         */
        init: function() {
            this.bindEvents();
            this.loadData();
        },

        /**
         * Bind events
         */
        bindEvents: function() {
            $('#applyFilters').on('click', () => this.loadData());
            $('#resetFilters').on('click', () => this.resetFilters());
            $('#exportCSV').on('click', () => this.exportCSV());

            // Auto-apply filters on Enter key
            $('#filterSearch, #filterLocation, #filterMinLogins').on('keypress', (e) => {
                if (e.which === 13) {
                    this.loadData();
                }
            });

            // Delegate event for expanding/collapsing cards
            $(document).on('click', '.mepr-corp-account-header', function() {
                $(this).closest('.mepr-corp-account-card').toggleClass('expanded');
            });
        },

        /**
         * Get current filters
         */
        getFilters: function() {
            return {
                search: $('#filterSearch').val().trim(),
                location: $('#filterLocation').val().trim(),
                min_logins: parseInt($('#filterMinLogins').val()) || 0,
                order_by: $('#sortBy').val(),
                order: $('#sortOrder').val(),
                membership_ids: [3888, 3889] // Default corporate membership IDs
            };
        },

        /**
         * Reset filters
         */
        resetFilters: function() {
            $('#filterSearch').val('');
            $('#filterLocation').val('');
            $('#filterMinLogins').val('0');
            $('#sortBy').val('total_logins');
            $('#sortOrder').val('DESC');
            this.loadData();
        },

        /**
         * Load data via AJAX
         */
        loadData: function() {
            const $loading = $('#meprCorpLoading');
            const $error = $('#meprCorpError');
            const $noData = $('#meprCorpNoData');
            const $data = $('#meprCorpData');

            // Show loading state
            $loading.show();
            $error.hide();
            $noData.hide();
            $data.hide();

            // Get filters
            const filters = this.getFilters();

            // AJAX request
            $.ajax({
                url: meprCorp.ajax_url,
                type: 'POST',
                data: {
                    action: 'mepr_corp_get_report_data',
                    nonce: meprCorp.nonce,
                    ...filters
                },
                success: (response) => {
                    $loading.hide();

                    if (response.success) {
                        const { data, summary } = response.data;

                        // Update summary cards
                        this.updateSummary(summary);

                        if (data.length > 0) {
                            // Render data
                            this.renderData(data);
                            $data.show();
                        } else {
                            // Show no data message
                            $noData.show();
                        }
                    } else {
                        this.showError(response.data.message || meprCorp.strings.error);
                    }
                },
                error: (xhr, status, error) => {
                    $loading.hide();
                    this.showError(meprCorp.strings.error + ' ' + error);
                }
            });
        },

        /**
         * Update summary cards
         */
        updateSummary: function(summary) {
            $('#totalCorporateAccounts').text(summary.total_corporate_accounts.toLocaleString());
            $('#totalSubAccounts').text(summary.total_sub_accounts.toLocaleString());
            $('#totalLogins').text(summary.total_logins.toLocaleString());
            $('#averageLogins').text(summary.average_logins_per_account.toLocaleString());
        },

        /**
         * Show error message
         */
        showError: function(message) {
            const $error = $('#meprCorpError');
            $error.find('p').text(message);
            $error.show();
        },

        /**
         * Render data
         */
        renderData: function(data) {
            const $container = $('#meprCorpData');
            $container.empty();

            data.forEach((account) => {
                const card = this.renderAccountCard(account);
                $container.append(card);
            });
        },

        /**
         * Render account card
         */
        renderAccountCard: function(account) {
            const companyName = account.company_name || 'N/A';
            const location = account.location || 'N/A';
            const subAccountsHTML = this.renderSubAccounts(account.sub_accounts);

            return `
                <div class="mepr-corp-account-card">
                    <div class="mepr-corp-account-header">
                        <div class="mepr-corp-account-info">
                            <div class="mepr-corp-account-main">
                                <div class="mepr-corp-account-name">
                                    ${this.escapeHtml(account.parent_username)}
                                    ${account.subscription_status === 'active' ? '<span class="mepr-corp-status-badge active">Active</span>' : '<span class="mepr-corp-status-badge inactive">Inactive</span>'}
                                </div>
                                <div class="mepr-corp-account-meta">
                                    <strong>${this.escapeHtml(companyName)}</strong> • ${this.escapeHtml(location)}
                                </div>
                                <div class="mepr-corp-account-meta">
                                    ${this.escapeHtml(account.parent_email)}
                                </div>
                            </div>

                            <div class="mepr-corp-account-stat">
                                <div class="mepr-corp-account-stat-label">Total Logins</div>
                                <div class="mepr-corp-account-stat-value">${parseInt(account.total_logins).toLocaleString()}</div>
                            </div>

                            <div class="mepr-corp-account-stat">
                                <div class="mepr-corp-account-stat-label">Sub-Accounts</div>
                                <div class="mepr-corp-account-stat-value">${parseInt(account.sub_account_count).toLocaleString()}</div>
                            </div>

                            <div class="mepr-corp-account-stat">
                                <div class="mepr-corp-account-stat-label">Last Login</div>
                                <div class="mepr-corp-account-stat-value" style="font-size: 14px; font-weight: 600;">${this.escapeHtml(account.formatted_last_login)}</div>
                            </div>

                            <div class="mepr-corp-account-stat">
                                <div class="mepr-corp-account-stat-label">Signup Date</div>
                                <div class="mepr-corp-account-stat-value" style="font-size: 14px; font-weight: 600;">${this.formatDate(account.parent_signup_date)}</div>
                            </div>
                        </div>

                        <div class="mepr-corp-account-toggle">
                            <span class="dashicons dashicons-arrow-down-alt2"></span>
                        </div>
                    </div>

                    <div class="mepr-corp-sub-accounts">
                        <div class="mepr-corp-sub-accounts-title">
                            Sub-Accounts (${parseInt(account.sub_account_count).toLocaleString()})
                        </div>
                        ${subAccountsHTML}
                    </div>
                </div>
            `;
        },

        /**
         * Render sub-accounts table
         */
        renderSubAccounts: function(subAccounts) {
            if (!subAccounts || subAccounts.length === 0) {
                return '<div class="mepr-corp-sub-accounts-empty">No sub-accounts found</div>';
            }

            let html = `
                <table class="mepr-corp-sub-accounts-table">
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Display Name</th>
                            <th>Login Count</th>
                            <th>Last Login</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
            `;

            subAccounts.forEach((sub) => {
                const statusClass = sub.is_active ? 'active' : 'inactive';
                const statusText = sub.is_active ? 'Active' : 'Inactive';

                html += `
                    <tr>
                        <td><strong>${this.escapeHtml(sub.user_login)}</strong></td>
                        <td>${this.escapeHtml(sub.user_email)}</td>
                        <td>${this.escapeHtml(sub.display_name)}</td>
                        <td><strong>${parseInt(sub.login_count).toLocaleString()}</strong></td>
                        <td>${this.escapeHtml(sub.formatted_last_login)}</td>
                        <td><span class="mepr-corp-status-badge ${statusClass}">${statusText}</span></td>
                    </tr>
                `;
            });

            html += `
                    </tbody>
                </table>
            `;

            return html;
        },

        /**
         * Export to CSV
         */
        exportCSV: function() {
            const filters = this.getFilters();
            const params = new URLSearchParams({
                action: 'mepr_corp_export_csv',
                nonce: meprCorp.nonce,
                search: filters.search,
                location: filters.location,
                min_logins: filters.min_logins,
                order_by: filters.order_by,
                order: filters.order,
                membership_ids: filters.membership_ids.join(',')
            });

            window.location.href = meprCorp.ajax_url + '?' + params.toString();
        },

        /**
         * Format date
         */
        formatDate: function(dateString) {
            if (!dateString) return 'N/A';

            const date = new Date(dateString);
            const options = { year: 'numeric', month: 'short', day: 'numeric' };
            return date.toLocaleDateString('en-US', options);
        },

        /**
         * Escape HTML
         */
        escapeHtml: function(text) {
            if (!text) return '';

            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };

            return String(text).replace(/[&<>"']/g, (m) => map[m]);
        }
    };

    // Initialize on document ready
    $(document).ready(() => {
        if ($('.mepr-corp-dashboard').length) {
            MeprCorpReporting.init();
        }
    });

})(jQuery);
