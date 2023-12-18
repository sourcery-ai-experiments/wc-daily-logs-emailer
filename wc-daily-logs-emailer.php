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
 * Version: 1.0
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

// Define the email to receive the log.
define( 'WC_LOG_EMAIL', 'your-email@example.com' );

// Schedule the action.
add_action( 'init', 'wc_schedule_daily_error_log_email' );
function wc_schedule_daily_error_log_email() {
	if ( ! as_next_scheduled_action( 'wc_daily_error_log_emailer_send_log' ) ) {
		$timestamp = strtotime( 'tomorrow 5:00 am' );
		as_schedule_recurring_action( $timestamp, DAY_IN_SECONDS, 'wc_daily_error_log_emailer_send_log' );
	}
}

// Action to send the log.
// Assuming logs log files look like this: fatal-errors-yyyy-mm-dd-788a25cfe5d6f8236c2037b7dc4100b0.log.
add_action( 'wc_daily_error_log_emailer_send_log', 'wc_daily_error_log_emailer_send_log' );
function wc_daily_error_log_emailer_send_log() {
	$yesterday    = date( 'Y-m-d', strtotime( '-1 day' ) );
	$log_filename = 'fatal-errors-' . $yesterday . '*.log';
	$log_files    = glob( WC_LOG_DIR . '/' . $log_filename );
    $site_name    = get_bloginfo('name');

	if ( ! empty( $log_files ) ) {
		foreach ( $log_files as $log_file ) {
			if ( file_exists( $log_file ) ) {
				$log_content = file_get_contents( $log_file );
				wp_mail( WC_LOG_EMAIL, "[$site_name] WooCommerce Fatal Errors Log for $yesterday", $log_content );
			}
		}
	}
}

// Deactivation hook to clear the scheduled action.
register_deactivation_hook( __FILE__, 'wc_daily_error_log_emailer_deactivate_action' );
function wc_daily_error_log_emailer_deactivate_action() {
	as_unschedule_all_actions( 'wc_daily_error_log_emailer_send_log' );
}
