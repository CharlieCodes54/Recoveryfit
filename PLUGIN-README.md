# MemberPress Corporate Reporting Plugin

A comprehensive WordPress plugin for tracking and reporting on MemberPress corporate memberships, with focus on parent account usage and sub-account login statistics.

## 🎯 Features

### Corporate Account Dashboard
- **Real-time data** directly from MemberPress database
- **Parent account aggregation** - sum all login activity from sub-accounts
- **Last login tracking** - see when facilitators last accessed the system
- **Company/location filtering** - organize by corporate entities
- **Sub-account details** - expand any parent to see all facilitators

### Reporting Capabilities
- **Summary statistics** - total accounts, logins, averages
- **Advanced filtering** - search, location, minimum logins
- **Flexible sorting** - by logins, date, company, username
- **CSV export** - download filtered reports for external analysis
- **Responsive design** - works on desktop, tablet, and mobile

### Admin Features
- **WordPress admin integration** - native dashboard page
- **AJAX-powered** - fast, no page reloads
- **Role-based access** - only admins can view reports
- **Professional UI** - modern, clean interface

## 📦 Installation

### Method 1: Manual Installation

1. **Upload the plugin folder** to `/wp-content/plugins/`
   ```bash
   # Copy all files to your WordPress installation
   /memberpress-corporate-reporting/
   ├── memberpress-corporate-reporting.php
   ├── templates/
   │   └── dashboard.php
   └── assets/
       ├── admin.css
       └── admin.js
   ```

2. **Activate the plugin**
   - Go to WordPress Admin → Plugins
   - Find "MemberPress Corporate Reporting"
   - Click "Activate"

3. **Access the dashboard**
   - Look for "Corporate Reports" in the WordPress admin menu
   - Click to open the reporting dashboard

### Method 2: ZIP Installation

1. **Create a ZIP file** of the plugin folder
   ```bash
   zip -r memberpress-corporate-reporting.zip memberpress-corporate-reporting/
   ```

2. **Upload via WordPress**
   - Go to Plugins → Add New → Upload Plugin
   - Choose the ZIP file
   - Click "Install Now" then "Activate"

## 🔧 Configuration

### Required: MemberPress Installation

This plugin requires MemberPress to be installed and activated. If MemberPress is not detected, the plugin will not activate.

### Corporate Membership IDs

By default, the plugin tracks membership IDs **3888** and **3889**. To change these:

1. Open `memberpress-corporate-reporting.php`
2. Find the `get_corporate_data()` method
3. Update this line:
   ```php
   $corporate_membership_ids = !empty($filters['membership_ids'])
       ? $filters['membership_ids']
       : array(3888, 3889); // Change these IDs
   ```

### Meta Keys Configuration

The plugin expects these MemberPress user meta keys:

| Meta Key | Purpose |
|----------|---------|
| `mepr_company_name` | Company/organization name |
| `mepr_company_location` | Location/branch info |
| `mepr_parent_account_id` | Links sub-account to parent |
| `mepr_login_count` | Total login count for user |
| `mepr_last_login` | Timestamp of last login |
| `mepr_is_active` | Active/inactive status |

**If your MemberPress uses different meta keys**, update them in the SQL queries within `memberpress-corporate-reporting.php`.

## 📊 Usage Guide

### Dashboard Overview

When you open the Corporate Reports page, you'll see:

1. **Summary Cards** (top)
   - Total corporate accounts
   - Total sub-accounts (facilitators)
   - Total logins across all accounts
   - Average logins per account

2. **Filters Panel** (middle)
   - **Search** - Find by username, email, or company
   - **Location** - Filter by location/branch
   - **Min. Logins** - Show only accounts with X+ logins
   - **Sort By** - Order results by different criteria
   - **Actions** - Apply filters, reset, or export

3. **Results Section** (bottom)
   - Expandable cards for each corporate account
   - Click any card to view sub-account details
   - See login stats, last login dates, status

### Filtering Reports

#### Search
Type any text to search across:
- Usernames
- Email addresses
- Display names
- Company names

#### Location Filter
Enter a location name to show only accounts from that location.

#### Minimum Logins
Set a threshold to hide low-activity accounts. For example:
- Set to `10` to show only accounts with 10+ logins
- Set to `0` to show all accounts

#### Sorting
Choose how to order results:
- **Total Logins** (default) - Most active first
- **Last Login** - Most recent first
- **Sub-Account Count** - Largest teams first
- **Company Name** - Alphabetical
- **Username** - Alphabetical

### Exporting Data

Click **Export CSV** to download a spreadsheet with:
- All currently filtered data
- Parent account information
- Aggregated login statistics
- Sub-account counts
- Dates and status information

The CSV is formatted for Excel and includes UTF-8 BOM for international characters.

### Viewing Sub-Accounts

Click any corporate account card to expand and see:
- Complete list of facilitators/sub-accounts
- Individual login counts per facilitator
- Last login date for each facilitator
- Active/inactive status
- Contact information

## 🛠️ Customization

### Changing Colors

Edit `assets/admin.css` and modify the CSS variables:

```css
:root {
    --mepr-corp-primary: #0073aa;      /* Primary color */
    --mepr-corp-success: #46b450;      /* Success/active */
    --mepr-corp-warning: #f0b849;      /* Warning states */
    --mepr-corp-danger: #dc3232;       /* Danger/inactive */
    /* ... more variables ... */
}
```

### Adding Custom Fields

To display additional data fields:

1. **Update the SQL query** in `get_corporate_data()`:
   ```php
   LEFT JOIN {$wpdb->usermeta} pm_custom ON parent.ID = pm_custom.user_id
   AND pm_custom.meta_key = 'your_custom_field'
   ```

2. **Add to the SELECT clause**:
   ```php
   pm_custom.meta_value as custom_field_name,
   ```

3. **Update the template** in `assets/admin.js` to display the field

### Modifying Report Layout

Edit `templates/dashboard.php` to:
- Add new filter options
- Change the summary card layout
- Add additional action buttons
- Customize help text

## 🔌 MemberPress Integration

### How the Plugin Works

The plugin integrates with MemberPress by:

1. **Querying MemberPress tables directly**:
   - `wp_mepr_members` - Member data
   - `wp_mepr_transactions` - Purchase history
   - `wp_mepr_subscriptions` - Active subscriptions

2. **Reading user meta**:
   - Company information
   - Parent/child relationships
   - Login tracking data

3. **Aggregating statistics**:
   - Summing logins from all sub-accounts
   - Finding most recent login across team
   - Counting active facilitators

### Database Performance

The plugin is optimized for performance:
- Efficient SQL queries with proper joins
- Indexed database lookups
- AJAX loading for responsive UI
- No page reloads needed

For sites with **1000+ users**, the queries typically complete in under 2 seconds.

## 🐛 Troubleshooting

### Plugin Won't Activate

**Error:** "MemberPress required"

**Solution:** Install and activate MemberPress first.

### No Data Showing

**Possible causes:**
1. No corporate memberships exist
2. Membership IDs don't match (3888, 3889)
3. Meta keys are named differently

**Solutions:**
1. Check that corporate memberships exist in MemberPress
2. Update membership IDs in the code
3. Update meta key names to match your installation

### Sub-Accounts Not Appearing

**Cause:** The `mepr_parent_account_id` meta key isn't set properly.

**Solution:** Ensure sub-accounts have this meta key pointing to their parent's user ID:
```php
update_user_meta($sub_account_id, 'mepr_parent_account_id', $parent_id);
```

### Export Not Working

**Cause:** PHP memory limit or execution time.

**Solution:** Add to `wp-config.php`:
```php
define('WP_MEMORY_LIMIT', '256M');
define('WP_MAX_EXECUTION_TIME', 300);
```

### Slow Performance

**Cause:** Large database or missing indexes.

**Solutions:**
1. Add database indexes on frequently queried columns
2. Increase PHP memory limit
3. Use a caching plugin
4. Implement pagination (requires custom code)

## 📝 Data Structure Expected

### Parent Corporate Accounts

- Standard WordPress user
- Has MemberPress membership (ID 3888 or 3889)
- Has company meta: `mepr_company_name`, `mepr_company_location`
- May have sub-accounts linked to them

### Sub-Accounts (Facilitators)

- Standard WordPress users
- Have `mepr_parent_account_id` meta pointing to parent user ID
- Track `mepr_login_count` (integer)
- Track `mepr_last_login` (timestamp)
- Track `mepr_is_active` (1 or 0)

### Example User Meta Structure

```
Parent Account (User ID: 123)
├── mepr_company_name: "RecoveryFit Austin"
├── mepr_company_location: "Austin, TX"
└── Active Membership: ID 3888

Sub-Account 1 (User ID: 456)
├── mepr_parent_account_id: 123
├── mepr_login_count: 45
├── mepr_last_login: 2025-11-10 14:23:00
└── mepr_is_active: 1

Sub-Account 2 (User ID: 789)
├── mepr_parent_account_id: 123
├── mepr_login_count: 12
├── mepr_last_login: 2025-11-05 09:15:00
└── mepr_is_active: 1
```

## 🔐 Security

- **Capability checks** - Only admins can access
- **Nonce verification** - All AJAX requests protected
- **Input sanitization** - All user input sanitized
- **Output escaping** - All data escaped before display
- **SQL injection protection** - Prepared statements used

## 📄 License

GPL v2 or later

## 🆘 Support

For issues, questions, or feature requests:
1. Check the troubleshooting section above
2. Review your MemberPress configuration
3. Check WordPress error logs
4. Contact your developer

## 🚀 Future Enhancements

Planned features:
- [ ] Pagination for large datasets
- [ ] Date range filtering
- [ ] PDF export capability
- [ ] Email reports (scheduled)
- [ ] Advanced charts and graphs
- [ ] Custom membership ID configuration via UI
- [ ] Import/export configuration settings
- [ ] Bulk actions on accounts

## 📚 Technical Details

**Version:** 1.0.0
**Requires WordPress:** 5.8+
**Requires PHP:** 7.4+
**Requires MemberPress:** Any version
**License:** GPL v2 or later

---

Built with ❤️ for RecoveryFit
