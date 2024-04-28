=== Error Logs Emailer for WooCommerce ===
Contributors: mslepko
Tags: woocommerce, errors, error log, developers, debug
Requires at least: 6.0
Tested up to: 6.5
Requires PHP: 7.4
Stable tag: 1.2.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Sends the previous day's WooCommerce fatal error log to specified email(s) using Action Scheduler.

== Description ==

[Error Logs Emailer for WooCommerce](https://github.com/rootscopeltd/wc-error-logs-emailer) is a free plugin to send fatal error logged by WooCommerce

- Know what errors are happening on your site without constantly checking the logs
- Set the email where error logs should be sent if it's not a site admin
- Add multiple email addresses
- Emails are sent at 5AM 

== Installation ==

1. Upload the plugin to your site, activate it.
2. By default emails with errors are sent to admin email address.
3. Go to Settings page to change it.

== Changelog ==

= 1.2.2 =
* Plugin Check fixes
* Updated date() to gmdate() to prevent runtime timezone changes
* Plugin name change to Error Logs Emailer for WooCommerce

= 1.2.1 =
* Making sure to register the action with Action Scheduler.
* Removed unnecessary file include

= 1.2 =
* Settings API: Now uses WordPress Settings API for a cleaner and more secure handling of the settings.
* Multiple Emails: Improved email handling for multiple recipients with validation.
* Scheduling Logic: Ensured that the email schedule doesn't overlap or create multiple cron jobs unnecessarily.
* Security and Validation: Added more robust security features, including checks for valid email addresses.

= 1.1 =
* Added setting page to update email to

= 1.0 =
* Initial version with just a simple usage of RECOVERY_MODE_EMAIL setting
