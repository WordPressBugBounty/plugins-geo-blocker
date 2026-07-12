<?php

namespace Medshi\GeoBlocker\Admin;

/**
 * AJAX Handler class for Geo Blocker plugin.
 *
 * @since 1.0.0
 */
class AjaxHandler {
    
    /**
     * Clear all logs from the database.
     *
     * @since 1.0.0
     * @return void
     */
    public function clear_logs() {
        // Verify nonce
        $nonce = isset($_POST['nonce']) ? \sanitize_text_field(\wp_unslash($_POST['nonce'])) : '';
        if (empty($nonce) || !\wp_verify_nonce($nonce, 'medshi_geo_block_clear_logs_nonce')) {
            \wp_send_json_error(['message' => \esc_html__('Security check failed.', 'geo-blocker')], 403);
            return;
        }
        
        // Check user capability
        if (!\current_user_can('manage_options')) {
            \wp_send_json_error(['message' => \esc_html__('You do not have permission to perform this action.', 'geo-blocker')], 403);
            return;
        }
        
        // Access database and clear logs
        global $wpdb;
        $table_name = $wpdb->prefix . 'medshi_geo_block_logs';
        
        // Use $wpdb->query with prepare to safely handle table name
        $result = $wpdb->query($wpdb->prepare("TRUNCATE TABLE %i", $table_name));
        
        if ($result !== false) {
            // Delete related transients
            \delete_transient('medshi_geo_block_dashboard_stats');
            \delete_transient('medshi_geo_block_logs_summary');
            
            // Delete chart data transients - no direct wildcard method, so delete known variations
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
            
            \wp_send_json_success(['message' => \esc_html__('Logs cleared successfully.', 'geo-blocker')]);
        } else {
            \wp_send_json_error(['message' => \esc_html__('Failed to clear logs.', 'geo-blocker')]);
        }
    }

    /**
     * Get chart data from the logs based on specified filters.
     *
     * @since 1.0.0
     * @return void
     */
    public function get_chart_data() {
        // Verify nonce
        $nonce = isset($_POST['nonce']) ? \sanitize_text_field(\wp_unslash($_POST['nonce'])) : '';
        if (empty($nonce) || !\wp_verify_nonce($nonce, 'medshi_geo_block_chart_data_nonce')) {
            \wp_send_json_error(['message' => \esc_html__('Security check failed.', 'geo-blocker')], 403);
            return;
        }
        
        // Check user capability
        if (!\current_user_can('manage_options')) {
            \wp_send_json_error(['message' => \esc_html__('You do not have permission to access this data.', 'geo-blocker')], 403);
            return;
        }
        
        // Get and sanitize parameters
        $date_range = isset($_POST['date_range']) ? \sanitize_key(\wp_unslash($_POST['date_range'])) : 'last_7_days';
        $data_type = isset($_POST['data_type']) ? \sanitize_key(\wp_unslash($_POST['data_type'])) : 'access_attempts';
        $state = isset($_POST['state']) ? \sanitize_key(\wp_unslash($_POST['state'])) : 'all_states';
        $chart_type = isset($_POST['chart_type']) ? \sanitize_key(\wp_unslash($_POST['chart_type'])) : 'bar';
        
        // Create a transient key based on parameters
        $transient_key = 'medshi_chart_' . $data_type . '_' . $date_range;
        if ($state !== 'all_states') {
            $transient_key .= '_' . $state;
        }
        
        // Try to get cached data
        $cached_data = \get_transient($transient_key);
        if ($cached_data !== false) {
            // Add chart type which may be different from when the data was cached
            $cached_data['chart_type'] = $chart_type;
            \wp_send_json_success($cached_data);
            return;
        }
        
        // Access database
        global $wpdb;
        $table_name = $wpdb->prefix . 'medshi_geo_block_logs';
        
        // Prepare SQL parts
        $where_conditions = [];
        $prepare_args = [];
        
        // Determine date filter based on range
        switch ($date_range) {
            case 'today':
                $today_start = gmdate('Y-m-d 00:00:00');
                $where_conditions[] = "timestamp >= %s";
                $prepare_args[] = $today_start;
                break;
            case 'last_7_days':
                $start_date = gmdate('Y-m-d 00:00:00', strtotime('-7 days'));
                $where_conditions[] = "timestamp >= %s";
                $prepare_args[] = $start_date;
                break;
            case 'last_30_days':
                $start_date = gmdate('Y-m-d 00:00:00', strtotime('-30 days'));
                $where_conditions[] = "timestamp >= %s";
                $prepare_args[] = $start_date;
                break;
            case 'last_90_days':
                $start_date = gmdate('Y-m-d 00:00:00', strtotime('-90 days'));
                $where_conditions[] = "timestamp >= %s";
                $prepare_args[] = $start_date;
                break;
            default: // all_time
                // No date filter needed
                break;
        }
        
        // Determine state filter
        if ($state !== 'all_states') {
            $where_conditions[] = "status = %s";
            $prepare_args[] = $state;
        }
        
        // Build WHERE clause
        $where_clause = '';
        if (!empty($where_conditions)) {
            $where_clause = "WHERE " . implode(" AND ", $where_conditions);
        }
        
        // Process based on data type and chart type
        $labels = [];
        $counts = [];
        $background_colors = [];
        $chart_title = '';
        
        switch ($data_type) {
            case 'access_attempts':
                if ($date_range === 'today') {
                    // Hourly breakdown for today
                    $sql_parts = [
                        "SELECT HOUR(timestamp) as label, COUNT(id) as count", 
                        "FROM {$table_name}", // WPCS: DB OK. $table_name is safe.
                    ];
                    
                    if (!empty($where_conditions)) {
                        $sql_parts[] = "WHERE " . implode(" AND ", $where_conditions);
                    }
                    
                    $sql_parts[] = "GROUP BY HOUR(timestamp) ORDER BY label ASC";
                    
                    // Build and execute query
                    $sql = implode(" ", $sql_parts);
                    // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- SQL query string is dynamically built but uses placeholders; all dynamic values are correctly passed as arguments to prepare(). Table name is safe.
                    $prepared_sql = $wpdb->prepare($sql, ...$prepare_args);
                    // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $prepared_sql is the direct result of a correctly handled $wpdb->prepare() call on the preceding line.
                    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Necessary for custom table; results are cached via transients elsewhere in this function
                    $results = $wpdb->get_results($prepared_sql, ARRAY_A);
                    
                    // Prepare data for 24 hours (0-23)
                    $hours = range(0, 23);
                    $counts = array_fill(0, 24, 0);
                    
                    // Fill in actual counts
                    if ($results) {
                        foreach ($results as $row) {
                            $hour = (int) $row['label'];
                            $counts[$hour] = (int) $row['count'];
                        }
                    }
                    
                    // Format hour labels
                    $labels = array_map(function($hour) {
                        return sprintf('%02d:00', $hour);
                    }, $hours);
                    
                    $chart_title = \esc_html__('Today\'s Access Attempts by Hour', 'geo-blocker');
                } else {
                    // Daily breakdown for longer periods
                    $sql_parts = [
                        "SELECT DATE(timestamp) as label, COUNT(id) as count",
                        "FROM {$table_name}" // WPCS: DB OK. $table_name is safe.
                    ];
                    
                    if (!empty($where_conditions)) {
                        $sql_parts[] = "WHERE " . implode(" AND ", $where_conditions);
                    }
                    
                    $sql_parts[] = "GROUP BY DATE(timestamp) ORDER BY label ASC";
                    
                    // Build and execute query
                    $sql = implode(" ", $sql_parts);
                    // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- SQL query string is dynamically built but uses placeholders; all dynamic values are correctly passed as arguments to prepare(). Table name is safe.
                    $prepared_sql = $wpdb->prepare($sql, ...$prepare_args);
                    // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $prepared_sql is the direct result of a correctly handled $wpdb->prepare() call on the preceding line.
                    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Necessary for custom table; results are cached via transients elsewhere in this function
                    $results = $wpdb->get_results($prepared_sql, ARRAY_A);
                    
                    // Process results
                    if ($results) {
                        foreach ($results as $row) {
                            $labels[] = \date_i18n(get_option('date_format'), strtotime($row['label'] . ' GMT'));
                            $counts[] = (int) $row['count'];
                        }
                    }
                    
                    switch ($date_range) {
                        case 'last_7_days':
                            $chart_title = \esc_html__('Access Attempts - Last 7 Days', 'geo-blocker');
                            break;
                        case 'last_30_days':
                            $chart_title = \esc_html__('Access Attempts - Last 30 Days', 'geo-blocker');
                            break;
                        case 'last_90_days':
                            $chart_title = \esc_html__('Access Attempts - Last 90 Days', 'geo-blocker');
                            break;
                        default:
                            $chart_title = \esc_html__('Access Attempts - All Time', 'geo-blocker');
                    }
                }
                break;
                
            case 'unique_ips':
                if ($date_range === 'today') {
                    // Hourly breakdown of unique IPs for today
                    $sql_parts = [
                        "SELECT HOUR(timestamp) as label, COUNT(DISTINCT ip_address) as count", 
                        "FROM {$table_name}" // WPCS: DB OK. $table_name is safe.
                    ];
                    
                    if (!empty($where_conditions)) {
                        $sql_parts[] = "WHERE " . implode(" AND ", $where_conditions);
                    }
                    
                    $sql_parts[] = "GROUP BY HOUR(timestamp) ORDER BY label ASC";
                    
                    // Build and execute query
                    $sql = implode(" ", $sql_parts);
                    // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- SQL query string is dynamically built but uses placeholders; all dynamic values are correctly passed as arguments to prepare(). Table name is safe.
                    $prepared_sql = $wpdb->prepare($sql, ...$prepare_args);
                    // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $prepared_sql is the direct result of a correctly handled $wpdb->prepare() call on the preceding line.
                    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Necessary for custom table; results are cached via transients elsewhere in this function
                    $results = $wpdb->get_results($prepared_sql, ARRAY_A);
                    
                    // Prepare data for 24 hours (0-23)
                    $hours = range(0, 23);
                    $counts = array_fill(0, 24, 0);
                    
                    // Fill in actual counts
                    if ($results) {
                        foreach ($results as $row) {
                            $hour = (int) $row['label'];
                            $counts[$hour] = (int) $row['count'];
                        }
                    }
                    
                    // Format hour labels
                    $labels = array_map(function($hour) {
                        return sprintf('%02d:00', $hour);
                    }, $hours);
                    
                    $chart_title = \esc_html__('Today\'s Unique IPs by Hour', 'geo-blocker');
                } else {
                    // Daily breakdown of unique IPs for longer periods
                    $sql_parts = [
                        "SELECT DATE(timestamp) as label, COUNT(DISTINCT ip_address) as count",
                        "FROM {$table_name}" // WPCS: DB OK. $table_name is safe.
                    ];
                    
                    if (!empty($where_conditions)) {
                        $sql_parts[] = "WHERE " . implode(" AND ", $where_conditions);
                    }
                    
                    $sql_parts[] = "GROUP BY DATE(timestamp) ORDER BY label ASC";
                    
                    // Build and execute query
                    $sql = implode(" ", $sql_parts);
                    // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- SQL query string is dynamically built but uses placeholders; all dynamic values are correctly passed as arguments to prepare(). Table name is safe.
                    $prepared_sql = $wpdb->prepare($sql, ...$prepare_args);
                    // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $prepared_sql is the direct result of a correctly handled $wpdb->prepare() call on the preceding line.
                    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Necessary for custom table; results are cached via transients elsewhere in this function
                    $results = $wpdb->get_results($prepared_sql, ARRAY_A);
                    
                    // Process results
                    if ($results) {
                        foreach ($results as $row) {
                            $labels[] = \date_i18n(get_option('date_format'), strtotime($row['label'] . ' GMT'));
                            $counts[] = (int) $row['count'];
                        }
                    }
                    
                    switch ($date_range) {
                        case 'last_7_days':
                            $chart_title = \esc_html__('Unique IPs - Last 7 Days', 'geo-blocker');
                            break;
                        case 'last_30_days':
                            $chart_title = \esc_html__('Unique IPs - Last 30 Days', 'geo-blocker');
                            break;
                        case 'last_90_days':
                            $chart_title = \esc_html__('Unique IPs - Last 90 Days', 'geo-blocker');
                            break;
                        default:
                            $chart_title = \esc_html__('Unique IPs - All Time', 'geo-blocker');
                    }
                }
                break;
                
            case 'top_user_agents':
                // Top user agents (always a pie/bar regardless of time period)
                $sql_parts = [
                    "SELECT", 
                    "CASE", 
                    "    WHEN user_agent = '' OR user_agent IS NULL THEN 'Unknown'", 
                    "    ELSE LEFT(user_agent, 50)", 
                    "END as label,", 
                    "COUNT(id) as count",
                    "FROM {$table_name}" // WPCS: DB OK. $table_name is safe.
                ];
                
                if (!empty($where_conditions)) {
                    $sql_parts[] = "WHERE " . implode(" AND ", $where_conditions);
                }
                
                $sql_parts[] = "GROUP BY label ORDER BY count DESC LIMIT 10";
                
                // Build and execute query
                $sql = implode(" ", $sql_parts);
                // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- SQL query string is dynamically built but uses placeholders; all dynamic values are correctly passed as arguments to prepare(). Table name is safe.
                $prepared_sql = $wpdb->prepare($sql, ...$prepare_args);
                // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $prepared_sql is the direct result of a correctly handled $wpdb->prepare() call on the preceding line.
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Necessary for custom table; results are cached via transients elsewhere in this function
                $results = $wpdb->get_results($prepared_sql, ARRAY_A);
                
                $default_colors = ['#4A90E2', '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40', '#8A2BE2', '#3CB371', '#FF7F50'];
                
                // Process results
                if ($results) {
                    $color_index = 0;
                    foreach ($results as $row) {
                        $labels[] = $row['label'];
                        $counts[] = (int) $row['count'];
                        $background_colors[] = $default_colors[$color_index % count($default_colors)];
                        $color_index++;
                    }
                }
                
                $chart_title = \esc_html__('Top User Agents', 'geo-blocker');
                
                // Force chart type to be either pie or bar for this data type
                if ($chart_type === 'line') {
                    $chart_type = 'pie';
                }
                break;
                
            default:
                \wp_send_json_error(['message' => \esc_html__('Invalid data type.', 'geo-blocker')]);
                return;
        }
        
        // Prepare response based on chart type
        $response = [
            'chart_type' => $chart_type,
            'labels' => $labels,
            'counts' => $counts,
            'title' => $chart_title
        ];
        
        // Add colors for pie charts
        if ($chart_type === 'pie' && !isset($background_colors)) {
            $background_colors = [];
            $default_colors = ['#4A90E2', '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40', '#8A2BE2', '#3CB371', '#FF7F50'];
            
            for ($i = 0; $i < count($labels); $i++) {
                $background_colors[] = $default_colors[$i % count($default_colors)];
            }
            
            $response['colors'] = $background_colors;
        } elseif (isset($background_colors)) {
            $response['colors'] = $background_colors;
        }
        
        // Cache the response for 5 minutes
        \set_transient($transient_key, $response, 5 * MINUTE_IN_SECONDS);
        
        \wp_send_json_success($response);
    }
    
    /**
     * Refresh log table with filter parameters via AJAX.
     *
     * @since 1.0.0
     * @return void
     */
    public function refresh_log_table() {
        // Verify nonce
        $nonce = isset($_POST['nonce']) ? \sanitize_text_field(\wp_unslash($_POST['nonce'])) : '';
        if (empty($nonce) || !\wp_verify_nonce($nonce, 'medshi_geo_block_refresh_log_table_nonce')) {
            \wp_send_json_error(['message' => \esc_html__('Security check failed.', 'geo-blocker')], 403);
            return;
        }
        
        // Check user capability
        if (!\current_user_can('manage_options')) {
            \wp_send_json_error(['message' => \esc_html__('You do not have permission to perform this action.', 'geo-blocker')], 403);
            return;
        }
        
        // Get and sanitize parameters
        $_GET['medshi_table_log_date_range'] = isset($_POST['date_range']) ? \sanitize_key(\wp_unslash($_POST['date_range'])) : 'all_time';
        $_GET['medshi_table_log_search'] = isset($_POST['search_term']) ? \sanitize_text_field(\wp_unslash($_POST['search_term'])) : '';
        $_REQUEST['paged'] = isset($_POST['paged']) ? \absint(\wp_unslash($_POST['paged'])) : 1;
        
        // Add sorting parameters - make these available to both $_GET and $_REQUEST
        $orderby = isset($_POST['orderby']) ? \sanitize_key(\wp_unslash($_POST['orderby'])) : 'timestamp';
        $order = isset($_POST['order']) ? \sanitize_key(\wp_unslash($_POST['order'])) : 'DESC';
        
        // Validate order parameter
        if (!in_array(strtoupper($order), ['ASC', 'DESC'])) {
            $order = 'DESC';
        }
        
        // Set parameters in both $_GET and $_REQUEST for compatibility
        $_GET['orderby'] = $_REQUEST['orderby'] = $orderby;
        $_GET['order'] = $_REQUEST['order'] = $order;
        
        // Instantiate log table class
        $log_table = new \Medshi\GeoBlocker\Admin\LogsListTable();
        $log_table->prepare_items();
        
        // Capture the output of the table display
        \ob_start();
        $log_table->display();
        $table_html = \ob_get_clean();
        
        \wp_send_json_success(['table_html' => $table_html]);
    }

    /**
     * Toggle the plugin's enabled/disabled status.
     *
     * @since 1.0.0
     * @return void
     */
    public function toggle_blocker_status() {
        // Verify nonce
        $nonce = isset($_POST['nonce']) ? \sanitize_text_field(\wp_unslash($_POST['nonce'])) : '';
        if (empty($nonce) || !\wp_verify_nonce($nonce, 'medshi_geo_block_toggle_status_nonce')) {
            \wp_send_json_error(['message' => \esc_html__('Security check failed.', 'geo-blocker')], 403);
            return;
        }
        
        // Check user capability
        if (!\current_user_can('manage_options')) {
            \wp_send_json_error(['message' => \esc_html__('You do not have permission to perform this action.', 'geo-blocker')], 403);
            return;
        }
        
        // Get the new state from POST and sanitize it
        $is_enabled_raw = isset($_POST['is_enabled']) ? \sanitize_text_field(\wp_unslash($_POST['is_enabled'])) : 'false';
        
        // Sanitize to '1' for true or '0' for false
        $new_status = ($is_enabled_raw === 'true' || $is_enabled_raw === '1') ? '1' : '0';
        
        // Update the option
        $updated = \update_option('medshi_geo_block_enabled', $new_status);
        
        if ($updated) {
            $status_text = ($new_status === '1') 
                ? \__('Geo-Blocking: Enabled', 'geo-blocker') 
                : \__('Geo-Blocking: Disabled', 'geo-blocker');
            
            \wp_send_json_success([
                'new_text' => $status_text,
                'new_status_val' => $new_status
            ]);
        } else {
            \wp_send_json_error(['message' => \esc_html__('Error updating status.', 'geo-blocker')]);
        }
    }
} 