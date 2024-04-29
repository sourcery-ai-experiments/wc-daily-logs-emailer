<?php
/**
 * Woo Daily Error Log Emailer
 *
 * @package   wc-daily-logs-emailer
 * @link      https://github.com/mslepko/wc-daily-logs-emailer
 * @author    WP Maintenance PRO <support@wp-maintenance.pro>
 * @copyright Michal Slepko
 * @license   GPL v2 or later
 *
 * Plugin Name: WooCommerce Daily Error Log Emailer
 * Description: Sends the previous day's WooCommerce fatal error log to specified email(s) using Action Scheduler.
 * Version: 1.2.0
 * Author: WP Maintenance PRO
 * Plugin URI: https://github.com/mslepko/wc-daily-logs-emailer
 * Author URI: https://wp-maintenance.pro
 * Requires Plugins: woocommerce
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

// Register admin menu and settings.
add_action( 'admin_menu', 'wc_daily_logs_emailer_add_admin_menu' );
add_action( 'admin_init', 'wc_daily_logs_emailer_settings_init' );

/**
 * Add admin menu for WooCommerce Daily Logs Emailer.
 */
function wc_daily_logs_emailer_add_admin_menu() {
	add_options_page(
		'WooCommerce Daily Logs Emailer Settings',
		'WooCommerce Logs Emailer',
		'manage_options',
		'wc-daily-logs-emailer',
		'wc_daily_logs_emailer_settings_page'
	);
}

/**
 * Initialize the settings for WooCommerce Daily Logs Emailer.
 * */
function wc_daily_logs_emailer_settings_init() {
	register_setting( 'wcDailyLogsEmailer', 'wc_log_email_settings' );

	add_settings_section(
		'wc_daily_logs_emailer_section',
		__( 'Configure your daily error log email settings.', 'wc-daily-logs-emailer' ),
		'wc_daily_logs_emailer_settings_section_callback',
		'wcDailyLogsEmailer',
		array(
			'after_section' => wc_daily_logs_emailer_settings_description(),
		)
	);

	add_settings_field(
		'wc_log_email',
		__( 'Log Email Address', 'wc-daily-logs-emailer' ),
		'wc_log_email_render',
		'wcDailyLogsEmailer',
		'wc_daily_logs_emailer_section'
	);
}

/**
 * Renders the input field for the log email address setting.
 */
function wc_log_email_render() {
	$options     = get_option( 'wc_log_email_settings' );
	$admin_email = get_option( 'admin_email' );
	// Check for RECOVERY_MODE_EMAIL.
	$recovery_mode_email = defined( 'RECOVERY_MODE_EMAIL' ) ? RECOVERY_MODE_EMAIL : false;
	$to_email            = $recovery_mode_email ? $recovery_mode_email : $admin_email;
	?>
	<input type='email' name='wc_log_email_settings[wc_log_email]' value='<?php echo esc_attr( $options['wc_log_email'] ?? '' ); ?>' class="regular-text">
	<p class="description">Enter the email address to receive the logs. Separate multiple emails with commas. </p>
	<p>Leave blank to use <strong><?php echo esc_html( $to_email ); ?></strong></p>
	<?php
}

/**
 * Callback function for the settings section in WooCommerce Daily Logs Emailer.
 */
function wc_daily_logs_emailer_settings_section_callback() {
	echo '<p>Adjust the settings for how and where you receive WooCommerce error logs.</p>';
}

	/**
	 * Displays additional information about the email recipient settings.
	 */
function wc_daily_logs_emailer_settings_description() {
	$admin_email = get_option( 'admin_email' );
	// Check for RECOVERY_MODE_EMAIL.
	$recovery_mode_email = defined( 'RECOVERY_MODE_EMAIL' ) ? RECOVERY_MODE_EMAIL : false;
	$to_email            = $recovery_mode_email ? $recovery_mode_email : $admin_email;

	return 'Email recipient settings priority
	<ol>
		<li>Email(s) set above.</li>
		<li>RECOVERY_MODE_EMAIL setting from wp-config.php - ' . esc_html( $recovery_mode_email ? $recovery_mode_email : 'none set' ) . '.</li>
		<li>Administration Email Address from Settings->General - ' . esc_html( $admin_email ) . '.</li>
	</ol>';
}

/**
 * Function to display the settings page for WooCommerce Daily Logs Emailer.
 *
 * This function displays the settings page for WooCommerce Daily Logs Emailer.
 */
function wc_daily_logs_emailer_settings_page() {
	?>
	<div class="wrap">
		<h1>WooCommerce Daily Logs Emailer Settings</h1>
		<form action='options.php' method='post'>
			<?php
			settings_fields( 'wcDailyLogsEmailer' );
			do_settings_sections( 'wcDailyLogsEmailer' );
			submit_button();
			?>
		</form>
	</div>
	<?php
}

// }

// Schedule the action with either Action Scheduler.
register_activation_hook( __FILE__, 'wc_schedule_daily_error_log_email' );

/**
 * Schedule the daily error log email.
 *
 * This function checks if the 'wc_daily_error_log_emailer_send_log' action is scheduled.
 * If it is not scheduled, it schedules the action to run at 5:00 am the next day.
 *
 * @see as_next_scheduled_action() For checking if the action is scheduled.
 * @see as_schedule_recurring_action() For scheduling the action.
 */
function wc_schedule_daily_error_log_email() {
	include_once ABSPATH . 'wp-admin/includes/plugin.php';
	if ( ! as_next_scheduled_action( 'wc_daily_error_log_emailer_send_log' ) ) {
		as_schedule_recurring_action( strtotime( 'tomorrow 5:00 am' ), DAY_IN_SECONDS, 'wc_daily_error_log_emailer_send_log' );
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
	$options      = get_option( 'wc_log_email_settings' );

	$recovery_mode_email = defined( 'RECOVERY_MODE_EMAIL' ) ? RECOVERY_MODE_EMAIL : false;
	$default_email       = $recovery_mode_email ? $recovery_mode_email : get_option( 'admin_email' );

	$emails    = explode( ',', $options['wc_log_email'] ?? $default_email );
	$site_name = get_bloginfo( 'name' );

	foreach ( $emails as $email ) {
		$email = trim( $email );
		if ( is_email( $email ) && ! empty( $log_files ) ) {
			foreach ( $log_files as $log_file ) {
				if ( file_exists( $log_file ) ) {
					$log_content = file_get_contents( $log_file );
					wp_mail( $email, "[$site_name] WooCommerce Fatal Errors Log for $yesterday", $log_content );
				}
			}
		}
	}
}

/**
 * Deactivation hook to clear the scheduled action.
 *
 * @see as_unschedule_all_actions() For clearing all scheduled actions.
 * @see delete_option() For deleting the 'wc_log_email' option.
 */

register_deactivation_hook( __FILE__, 'wc_daily_error_log_emailer_deactivate_action' );

/**
 * Deactivate the plugin and clear the scheduled action.
 *
 * This function clears all scheduled actions with the hook 'wc_daily_error_log_emailer_send_log'
 * and deletes the 'wc_log_email' option from the database.
 *
 * @see as_unschedule_all_actions() For clearing all scheduled actions.
 * @see delete_option() For deleting the 'wc_log_email' option.
 */
function wc_daily_error_log_emailer_deactivate_action() {
	if ( function_exists( 'as_unschedule_all_actions' ) ) {
		as_unschedule_all_actions( 'wc_daily_error_log_emailer_send_log' );
	}

	// Delete the 'wc_log_email' option from the database.
	delete_option( 'wc_log_email' );
}


add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'wc_daily_logs_emailer_add_settings_link' );

/**
 * Add settings link to plugin page
 *
 * @param array $links Array of plugin action links.
 * @return array Modified array of plugin action links.
 */
function wc_daily_logs_emailer_add_settings_link( $links ) {
	$settings_link = '<a href="' . admin_url( 'options-general.php?page=wc-daily-logs-emailer' ) . '">' . __( 'Settings' ) . '</a>';
	array_unshift( $links, $settings_link );
	return $links;
}