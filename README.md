# Plugin Name: WooCommerce Daily Error Log Emailer

## Description: Sends the previous day's WooCommerce fatal error log to a specified email using Action Scheduler or WP Conr.

1. Go to Settings->WooCommerce Daily Logs Emailer

2. Update email or leave bland to use default values

3. Action Scheduler: This script now uses Action Scheduler to schedule a daily task at 5 AM. Ensure that WooCommerce is installed and active, as it includes Action Scheduler.

4. If Action Scheduler is not present on the site the plugin will use WP Cron to schedule the emails. This is very unlikely as WooCommerce has Action Scheduler integrated

5. Timezone: Make sure your WordPress installation is set to the correct timezone.

6. Support queries: [https://wp-maintenance.pro/#contact](https://wp-maintenance.pro/#contact)