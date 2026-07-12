<?php
/**
 * Plugin Name: Geo Blocker – Control Site Access by Region and IP
 * Plugin URI: https://wordpress.org/plugins/geo-blocker/
 * Description: Blocks website access based on visitor's geolocation using the ipwho.is API.
 * Version: 1.0.0
 * Author: medshi
 * Author URI: https://profiles.wordpress.org/medshi8/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: geo-blocker
 * Domain Path: /languages
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants
define( 'MEDSHI_GEO_BLOCK_VERSION', '1.0.0' );
define( 'MEDSHI_GEO_BLOCK_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'MEDSHI_GEO_BLOCK_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'MEDSHI_GEO_BLOCK_PLUGIN_FILE', __FILE__ );

// Composer Autoloader
if ( file_exists( MEDSHI_GEO_BLOCK_PLUGIN_DIR . 'vendor/autoload.php' ) ) {
    require_once MEDSHI_GEO_BLOCK_PLUGIN_DIR . 'vendor/autoload.php';

    // Initialize the plugin
    if ( class_exists( 'Medshi\\GeoBlocker\\Plugin' ) ) {
        /**
         * Returns the main instance of Medshi_Geo_Blocker.
         *
         * @since 1.0.0
         * @return \Medshi\GeoBlocker\Plugin
         */
        function medshi_geo_blocker() {
            return \Medshi\GeoBlocker\Plugin::instance();
        }

        // Get Geo Blocker Running
        medshi_geo_blocker();
    }
}

// Activation Hook
register_activation_hook( __FILE__, 'medshi_geo_block_activate_plugin' );

/**
 * Plugin activation function.
 */
function medshi_geo_block_activate_plugin() {
    // Set default options
    if ( false === \get_option( 'medshi_geo_block_enabled' ) ) {
        \add_option( 'medshi_geo_block_enabled', '0' );
    }
    if ( false === \get_option( 'medshi_geo_block_mode' ) ) {
        \add_option( 'medshi_geo_block_mode', 'block_selected' );
    }
    if ( false === \get_option( 'medshi_geo_block_countries' ) ) {
        \add_option( 'medshi_geo_block_countries', '' );
    }
    
    // Set default values for blocked action settings
    if ( false === \get_option( 'medshi_geo_block_blocked_action' ) ) {
        \add_option( 'medshi_geo_block_blocked_action', 'show_message' );
    }
    if ( false === \get_option( 'medshi_geo_block_custom_message' ) ) {
        \add_option( 'medshi_geo_block_custom_message', \esc_html__( 'Access from your location is currently restricted.', 'geo-blocker' ) );
    }
    if ( false === \get_option( 'medshi_geo_block_redirect_url' ) ) {
        \add_option( 'medshi_geo_block_redirect_url', '' );
    }
    
    // Create database table for logs
    global $wpdb;
    $table_name = $wpdb->prefix . 'medshi_geo_block_logs';
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE $table_name (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        timestamp DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
        ip_address VARCHAR(100) NOT NULL DEFAULT '',
        country_code VARCHAR(10) NOT NULL DEFAULT '',
        continent VARCHAR(50) NOT NULL DEFAULT '',
        city VARCHAR(100) NOT NULL DEFAULT '',
        request_uri TEXT NOT NULL,
        user_agent TEXT NOT NULL,
        status VARCHAR(20) NOT NULL DEFAULT '',
        PRIMARY KEY  (id),
        KEY ip_address (ip_address(10)),
        KEY timestamp (timestamp)
    ) $charset_collate;";
    
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    \dbDelta( $sql );
}

// Deactivation Hook
register_deactivation_hook( __FILE__, 'medshi_geo_block_deactivate_plugin' );

/**
 * Plugin deactivation function.
 */
function medshi_geo_block_deactivate_plugin() {
    // Clear any plugin transients
    delete_transient( 'medshi_geo_block_dashboard_stats' );
    delete_transient( 'medshi_geo_block_logs_summary' );
    delete_transient( 'medshi_geo_export_all_logs' );
    
    // Clear any cached chart data - pattern based deletion
    global $wpdb;
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
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $sql is prepared below
        $wpdb->query( 
            $wpdb->prepare( 
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", 
                $pattern 
            )
        );
    }
    
    // Flush rewrite rules if the plugin added any
    flush_rewrite_rules();
} 