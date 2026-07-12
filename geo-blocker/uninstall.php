<?php
/**
 * Uninstall script for Geo Blocker plugin
 *
 * This file runs when the plugin is deleted via the WordPress admin interface.
 * It removes all options and database tables created by the plugin.
 *
 * @package Medshi\GeoBlocker
 */

// If uninstall.php is not called by WordPress, die
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    die;
}

// Delete plugin options
$options_to_delete = [
    'medshi_geo_block_enabled',
    'medshi_geo_block_mode',
    'medshi_geo_block_countries',
    'medshi_geo_block_blocked_action',
    'medshi_geo_block_custom_message',
    'medshi_geo_block_redirect_url',
];

foreach ( $options_to_delete as $option_name ) {
    delete_option( $option_name );
}

// Delete custom database table
global $wpdb;
$table_name = $wpdb->prefix . 'medshi_geo_block_logs';
// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is constructed safely with the WordPress prefix. DROP TABLE cannot be properly prepared.
$wpdb->query( "DROP TABLE IF EXISTS {$table_name}" );

// Delete known static transients
$static_transients = [
    'medshi_geo_block_dashboard_stats',
    'medshi_geo_block_logs_summary',
    'medshi_geo_export_all_logs',
];

foreach ( $static_transients as $transient_name ) {
    delete_transient( $transient_name );
}

// Delete pattern-based transients
$transient_patterns = [
    '_transient_medshi_chart_%',
    '_transient_timeout_medshi_chart_%',
    '_transient_medshi_chart_access_attempts_%',
    '_transient_timeout_medshi_chart_access_attempts_%',
    '_transient_medshi_chart_unique_ips_%',
    '_transient_timeout_medshi_chart_unique_ips_%',
    '_transient_medshi_chart_top_user_agents%',
    '_transient_timeout_medshi_chart_top_user_agents%',
];

foreach ( $transient_patterns as $pattern ) {
    $sql = $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $pattern );
    // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $sql is prepared in the line above
    $wpdb->query( $sql );
} 