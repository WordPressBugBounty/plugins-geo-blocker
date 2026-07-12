<?php

namespace Medshi\GeoBlocker\Admin;

/**
 * Action Handler class for Geo Blocker plugin.
 *
 * @since 1.0.0
 */
class ActionHandler {
    
    /**
     * Check for and handle export actions.
     *
     * @since 1.0.0
     * @return void
     */
    public function maybe_handle_export() {
        // Check if we have an export action
        if (!isset($_GET['medshi_action'])) {
            return;
        }
        
        $action = \sanitize_key(\wp_unslash($_GET['medshi_action']));
        
        // Handle CSV export
        if ('export_csv_logs' === $action) {
            // Verify nonce
            $nonce_csv = '';
            if (isset($_GET['_wpnonce_export_csv'])) {
                $nonce_csv = \sanitize_key(\wp_unslash($_GET['_wpnonce_export_csv']));
            }
            
            if (empty($nonce_csv) || !\wp_verify_nonce($nonce_csv, 'medshi_geo_block_export_csv_nonce')) {
                \wp_die(\esc_html__('Security check failed.', 'geo-blocker'), \esc_html__('Error', 'geo-blocker'), ['response' => 403]);
            }
            
            // Check user capability
            if (!\current_user_can('manage_options')) {
                \wp_die(\esc_html__('You do not have permission to export logs.', 'geo-blocker'), \esc_html__('Error', 'geo-blocker'), ['response' => 403]);
            }
            
            // Process CSV export
            $this->_do_export_csv();
        }
        
        // Handle JSON export
        if ('export_json_logs' === $action) {
            // Verify nonce
            $nonce_json = '';
            if (isset($_GET['_wpnonce_export_json'])) {
                $nonce_json = \sanitize_key(\wp_unslash($_GET['_wpnonce_export_json']));
            }
            
            if (empty($nonce_json) || !\wp_verify_nonce($nonce_json, 'medshi_geo_block_export_json_nonce')) {
                \wp_die(\esc_html__('Security check failed.', 'geo-blocker'), \esc_html__('Error', 'geo-blocker'), ['response' => 403]);
            }
            
            // Check user capability
            if (!\current_user_can('manage_options')) {
                \wp_die(\esc_html__('You do not have permission to export logs.', 'geo-blocker'), \esc_html__('Error', 'geo-blocker'), ['response' => 403]);
            }
            
            // Process JSON export
            $this->_do_export_json();
        }
    }
    
    /**
     * Export logs as CSV.
     *
     * @since 1.0.0
     * @return void
     */
    private function _do_export_csv() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'medshi_geo_block_logs';
        
        // Use $wpdb->get_results directly with the table name
        $logs = $wpdb->get_results(
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $table_name is a safe, prefix-based value
            "SELECT * FROM {$table_name} ORDER BY timestamp ASC",
            ARRAY_A
        );
        
        // Set headers for CSV download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=geo-blocker-logs-' . gmdate('Y-m-d') . '.csv');
        
        // Start output buffering
        ob_start();
        
        // Generate CSV data
        $csv_data = '';
        
        // Add CSV headers
        if (!empty($logs)) {
            // Get column headers
            $headers = array_keys($logs[0]);
            $csv_data .= $this->generate_csv_line($headers);
            
            // Add data rows
            foreach ($logs as $log) {
                $csv_data .= $this->generate_csv_line($log);
            }
        } else {
            // Write header for empty log
            $headers = ['id', 'ip_address', 'country_code', 'continent', 'city', 'status', 'request_uri', 'user_agent', 'timestamp'];
            $csv_data .= $this->generate_csv_line($headers);
            
            // Add empty row message
            $csv_data .= $this->generate_csv_line([\esc_html__('No log entries found', 'geo-blocker')]);
        }
        
        // Output the CSV data
        echo $csv_data; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CSV data is properly generated
        
        // Clean the buffer and send to browser
        ob_end_flush();
        exit;
    }
    
    /**
     * Generate a CSV line from an array of values.
     *
     * @since 1.0.0
     * @param array $fields Array of field values.
     * @return string Formatted CSV line with trailing newline.
     */
    private function generate_csv_line($fields) {
        $csv_line = '';
        $delimiter = ',';
        $enclosure = '"';
        $escape = '\\';
        
        foreach ($fields as $index => $field) {
            // Escape field if needed
            if (strpos($field, $delimiter) !== false || 
                strpos($field, $enclosure) !== false || 
                strpos($field, "\n") !== false || 
                strpos($field, "\r") !== false || 
                strpos($field, "\t") !== false ||
                strpos($field, ' ') !== false) {
                
                // Escape enclosures with another enclosure
                $field = str_replace($enclosure, $enclosure . $enclosure, $field);
                // Wrap field in enclosures
                $field = $enclosure . $field . $enclosure;
            }
            
            // Add delimiter except for the first field
            if ($index > 0) {
                $csv_line .= $delimiter;
            }
            
            $csv_line .= $field;
        }
        
        return $csv_line . "\n";
    }
    
    /**
     * Export logs as JSON.
     *
     * @since 1.0.0
     * @return void
     */
    private function _do_export_json() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'medshi_geo_block_logs';
        
        // Use $wpdb->get_results directly with the table name
        $logs = $wpdb->get_results(
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $table_name is a safe, prefix-based value
            "SELECT * FROM {$table_name} ORDER BY timestamp ASC",
            ARRAY_A
        );
        
        // Set headers for JSON download
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename=geo-blocker-logs-' . gmdate('Y-m-d') . '.json');
        
        // Output JSON data
        echo \wp_json_encode($logs, JSON_PRETTY_PRINT);
        exit;
    }
} 