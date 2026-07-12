<?php

namespace Medshi\GeoBlocker\Core;

/**
 * Logger class for saving log data.
 *
 * @since 1.0.0
 */
class Logger {

    /**
     * Adds a log entry to the database.
     *
     * @since 1.0.0
     * @param array $data Associative array of log data.
     *              Expected keys: ip_address, country_code, continent, city, request_uri, user_agent, status.
     * @return bool|int False on failure, number of rows inserted on success.
     */
    public static function add_log_entry($data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'medshi_geo_block_logs';

        $insert_data = [
            'timestamp'    => \current_time('mysql', 1), // GMT/UTC time
            'ip_address'   => isset($data['ip_address']) ? \sanitize_text_field(substr($data['ip_address'], 0, 100)) : '',
            'country_code' => isset($data['country_code']) ? \sanitize_text_field(substr($data['country_code'], 0, 10)) : '',
            'continent'    => isset($data['continent']) ? \sanitize_text_field(substr($data['continent'], 0, 50)) : '',
            'city'         => isset($data['city']) ? \sanitize_text_field(substr($data['city'], 0, 100)) : '',
            'request_uri'  => isset($data['request_uri']) ? \esc_url_raw($data['request_uri']) : '',
            'user_agent'   => isset($data['user_agent']) ? \sanitize_textarea_field($data['user_agent']) : '',
            'status'       => isset($data['status']) ? \sanitize_key($data['status']) : '',
        ];

        $formats = [
            '%s', // timestamp
            '%s', // ip_address
            '%s', // country_code
            '%s', // continent
            '%s', // city
            '%s', // request_uri
            '%s', // user_agent
            '%s', // status
        ];

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Direct query necessary for custom logs table; no WP core function available for this operation
        $result = $wpdb->insert($table_name, $insert_data, $formats);
        
        // If insertion was successful, invalidate related caches
        if ($result) {
            // Invalidate dashboard stats cache
            \delete_transient('medshi_geo_block_dashboard_stats');
            
            // Invalidate logs summary cache
            \delete_transient('medshi_geo_block_logs_summary');
            
            // Invalidate chart data caches that might be affected
            $chart_cache_keys = [
                'medshi_chart_access_attempts_today',
                'medshi_chart_access_attempts_last_7_days',
                'medshi_chart_access_attempts_last_30_days',
                'medshi_chart_access_attempts_last_90_days',
                'medshi_chart_unique_ips_today',
                'medshi_chart_unique_ips_last_7_days',
                'medshi_chart_unique_ips_last_30_days',
                'medshi_chart_unique_ips_last_90_days',
                'medshi_chart_top_user_agents'
            ];
            
            foreach ($chart_cache_keys as $key) {
                \delete_transient($key);
            }
        }

        return $result;
    }
} 