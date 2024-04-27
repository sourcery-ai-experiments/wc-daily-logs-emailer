<?php
/**
 * Woo Daily Error Log Emailer
 *
 * @package   wc-daily-logs-emailer
 * @link      https://github.com/mslepko/wc-daily-logs-emailer
 * @author    Michal Slepko <michal@rootscope.co.uk>
 * @copyright Michal Slepko
 * @license   GPL v2 or later
 *
 * Plugin Name: WooCommerce Daily Error Log Emailer with Action Scheduler
 * Description: Sends the previous day's WooCommerce fatal error log to a specified email using Action Scheduler.
 * Version: 1.1.0
 * Author: Michal Slepko
 * Plugin URI: https://github.com/mslepko/wc-daily-logs-emailer
 * Author URI: https://github.com/mslepko
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Hook to add admin menu for plugin settings.
add_action( 'admin_menu', 'wc_daily_logs_emailer_add_admin_menu' );

/**
 * Adds an options page for 'Woo Daily Logs Emailer' in the WordPress admin menu.
 *
 * This function utilizes the `add_options_page` function of WordPress to create a new settings page
 * under the Settings menu in WP Admin. The new page is named 'Woo Daily Logs Emailer Settings' with
 * the slug 'wc-daily-logs-emailer', and it uses the callback 'wc_daily_logs_emailer_settings_page'
 * when rendering the page content.
 *
 * @see        add_options_page() For how option pages are added in WordPress.
 */
function wc_daily_logs_emailer_add_admin_menu() {
	add_options_page( 'Woo Daily Logs Emailer Settings', 'Woo Logs Emailer', 'manage_options', 'wc-daily-logs-emailer', 'wc_daily_logs_emailer_settings_page' );
}

/**
 * Displays the 'Woo Daily Logs Emailer' settings page in the WordPress admin area.
 *
 * This function is called when the user clicks on the 'Woo Logs Emailer' menu item
 * under 'Settings'. It should contain the HTML output or action
 * to generate the settings page.
 */
function wc_daily_logs_emailer_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	// Check if settings form has been submitted.
	if ( isset( $_POST['wc_log_email'] ) ) {
		// Verify nonce for security.
		check_admin_referer( 'wc_daily_logs_emailer_update_settings' );

		// Update option with the new value from the form.
		update_option( 'wc_log_email', sanitize_email( $_POST['wc_log_email'] ) );
	}

	// Retrieve the current email setting.
		$admin_email = get_option( 'admin_email' );
	$current_email   = get_option( 'wc_log_email' );

	// Check for RECOVERY_MODE_EMAIL.
	$recovery_mode_email = defined( 'RECOVERY_MODE_EMAIL' ) ? RECOVERY_MODE_EMAIL : false;
		$to_email        = $recovery_mode_email ? $recovery_mode_email : $admin_email;

	include_once ABSPATH . 'wp-admin/includes/plugin.php';
	$action_scheduler_available = is_plugin_active( 'woocommerce/action-scheduler/action-scheduler.php' );

	?>
	<div class="wrap">
		<h2>WC Daily Logs Emailer Settings</h2>
		<form method="post" action="">
			<?php wp_nonce_field( 'wc_daily_logs_emailer_update_settings' ); ?>
			<table class="form-table">
				<tr valign="top">
					<th scope="row">Log Email Address</th>
					<td>
						<input type="email" name="wc_log_email" value="<?php echo esc_attr( $current_email ); ?>" class="regular-text" /><br>
						<span class="description">Enter the email address to receive the logs. Leave blank to use <?php echo $to_email; ?>.</span>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">Email recipient settings priority</th>
					<td><ol>
						<li>Email set above.</li>
						<li>RECOVERY_MODE_EMAIL setting from wp-config.php<?php $recovery_mode_email ? ( ' - ' . $recovery_mode_email . ')' ) : ''; ?>.</li>
							<li>Administration Email Address from Settings->General - <?php echo $admin_email; ?>.</li>
					</ol>
					</td>
				</tr>
			</table>
			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}

// Schedule the action with either Action Scheduler or WP Cron based on availability.
add_action( 'init', 'wc_schedule_daily_error_log_email' );
/**
 * Schedules the daily error log email.
 *
 * This function checks if the WooCommerce Action Scheduler plugin is active. If it is, it schedules
 * the 'wc_daily_error_log_emailer_send_log' action to run daily at 5:00 am using Action Scheduler.
 * If the Action Scheduler plugin is not active, it schedules the same action using WP Cron.
 *
 * The action is responsible for sending the WooCommerce fatal error log of the previous day to a specified email.
 *
 * @see as_next_scheduled_action() For checking the next scheduled action in Action Scheduler.
 * @see as_schedule_recurring_action() For scheduling a recurring action in Action Scheduler.
 * @see wp_next_scheduled() For checking the next scheduled action in WP Cron.
 * @see wp_schedule_event() For scheduling a recurring action in WP Cron.
 */
function wc_schedule_daily_error_log_email() {
	include_once ABSPATH . 'wp-admin/includes/plugin.php';
	if ( is_plugin_active( 'woocommerce/action-scheduler/action-scheduler.php' ) ) {
		if ( ! as_next_scheduled_action( 'wc_daily_error_log_emailer_send_log' ) ) {
			$timestamp = strtotime( 'tomorrow 5:00 am' );
			as_schedule_recurring_action( $timestamp, DAY_IN_SECONDS, 'wc_daily_error_log_emailer_send_log' );
		}
	} elseif ( ! wp_next_scheduled( 'wc_daily_error_log_emailer_send_log' ) ) {
			wp_schedule_event( time(), 'daily', 'wc_daily_error_log_emailer_send_log' );
	}
}

/**
 * Sends the WooCommerce fatal error log of the previous day to a specified email.
 *
 * This function retrieves the fatal error logs from the previous day, reads the content of each log file,
 * and sends it to the email address specified in the 'wc_log_email' option. If the 'wc_log_email' option
 * is not set, it sends the logs to the admin email. The email subject includes the site name and the date
 * of the logs.
 *
 * @see get_option() For retrieving the 'wc_log_email' and 'admin_email' options.
 * @see get_bloginfo() For retrieving the site name.
 * @see wp_mail() For sending the email with the log content.
 */
function wc_daily_error_log_emailer_send_log() {
	$yesterday    = date( 'Y-m-d', strtotime( '-1 day' ) );
	$log_filename = 'fatal-errors-' . $yesterday . '*.log';
	$log_files    = glob( WC_LOG_DIR . '/' . $log_filename );
	$email        = get_option( 'wc_log_email', get_option( 'admin_email' ) );
	$site_name    = get_bloginfo( 'name' );

	if ( ! empty( $log_files ) ) {
		foreach ( $log_files as $log_file ) {
			if ( file_exists( $log_file ) ) {
				$log_content = file_get_contents( $log_file );
				wp_mail( $email, "[$site_name] WooCommerce Fatal Errors Log for $yesterday", $log_content );
			}
		}
	}
}

// Deactivation hook to clear the scheduled action.
register_deactivation_hook( __FILE__, 'wc_daily_error_log_emailer_deactivate_action' );
/**
 * Deactivates the scheduled action for sending daily error logs.
 *
 * This function is hooked to the plugin deactivation hook. When the plugin is deactivated,
 * it checks if the Action Scheduler function 'as_unschedule_all_actions' exists. If it does,
 * it unschedules all actions with the hook 'wc_daily_error_log_emailer_send_log' using Action Scheduler.
 * If the Action Scheduler function does not exist, it unschedules the same action using WP Cron.
 *
 * @see as_unschedule_all_actions() For unscheduling actions in Action Scheduler.
 * @see wp_next_scheduled() For getting the next scheduled action in WP Cron.
 * @see wp_unschedule_event() For unscheduling actions in WP Cron.
 */
function wc_daily_error_log_emailer_deactivate_action() {
	if ( function_exists( 'as_unschedule_all_actions' ) ) {
		as_unschedule_all_actions( 'wc_daily_error_log_emailer_send_log' );
	} else {
		$timestamp = wp_next_scheduled( 'wc_daily_error_log_emailer_send_log' );
		wp_unschedule_event( $timestamp, 'wc_daily_error_log_emailer_send_log' );
	}

	// Delete the 'wc_log_email' option from the database.
	delete_option( 'wc_log_email' );
}

