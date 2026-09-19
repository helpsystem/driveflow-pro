<?php
/**
 * DriveFlow Pro – Uninstall handler
 * Runs when the plugin is deleted from the WordPress admin. Cleans up all
 * plugin data so the site is left exactly as it was before installation.
 *
 * @package DriveFlow_Pro
 */

// Only run from WordPress uninstall mechanism.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

global $wpdb;

// Remove all custom tables.
$tables = array(
    $wpdb->prefix . 'driveflow_sessions',
    $wpdb->prefix . 'driveflow_students',
    $wpdb->prefix . 'driveflow_instructors',
    $wpdb->prefix . 'driveflow_vehicles',
    $wpdb->prefix . 'driveflow_student_credits',
    $wpdb->prefix . 'driveflow_blocked_slots',
);
foreach ( $tables as $table ) {
    $wpdb->query( "DROP TABLE IF EXISTS `{$table}`" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table names are internal constants.
}

// Remove all options.
delete_option( 'driveflow_pro_settings' );
delete_option( 'driveflow_pro_version' );
delete_option( 'driveflow_db_version' );

// Remove all transients left by the throttle helper and calendar cache.
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '%_transient_df_%' OR option_name LIKE '%_transient_timeout_df_%'" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- LIKE pattern contains no user input.
