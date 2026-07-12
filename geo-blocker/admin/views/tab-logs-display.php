<?php
/**
 * Logs Display tab view for Geo Blocker plugin
 *
 * @package Medshi\GeoBlocker
 */

// If accessed directly, exit
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="medshi-geo-block-logs-tab">
    <h2><?php \esc_html_e('Visitor Logs', 'geo-blocker'); ?></h2>
    
    
    
    <!-- Summary Cards Area -->
    <div class="medshi-geo-block-section medshi-geo-block-summary-cards-area">
        <div class="medshi-geo-block-summary-cards-container">
            <!-- Card 1: Total Access Attempts -->
            <div class="medshi-geo-block-summary-card">
                <div class="medshi-geo-block-card-header">
                    <span class="dashicons dashicons-chart-line"></span>
                    <span class="medshi-geo-block-card-title"><?php \esc_html_e('Total Access Attempts', 'geo-blocker'); ?></span>
                </div>
                <div class="medshi-geo-block-card-body">
                    <div class="medshi-geo-block-card-value"><?php echo \esc_html(\number_format_i18n($total_attempts_all_time ?? 0)); ?></div>
                    <div class="medshi-geo-block-card-desc"><?php \esc_html_e('all time', 'geo-blocker'); ?></div>
                </div>
            </div>

            <!-- Card 2: Unique IPs -->
            <div class="medshi-geo-block-summary-card">
                <div class="medshi-geo-block-card-header">
                    <span class="dashicons dashicons-networking"></span>
                    <span class="medshi-geo-block-card-title"><?php \esc_html_e('Unique IPs', 'geo-blocker'); ?></span>
                </div>
                <div class="medshi-geo-block-card-body">
                    <div class="medshi-geo-block-card-value"><?php echo \esc_html(\number_format_i18n($unique_ips_all_time ?? 0)); ?></div>
                    <div class="medshi-geo-block-card-desc"><?php \esc_html_e('distinct sources (all time)', 'geo-blocker'); ?></div>
                </div>
            </div>

            <!-- Card 3: Today's Blocked -->
            <div class="medshi-geo-block-summary-card medshi-geo-block-card-blocked">
                <div class="medshi-geo-block-card-header">
                    <span class="dashicons dashicons-shield-alt"></span>
                    <span class="medshi-geo-block-card-title"><?php \esc_html_e('Blocked Today', 'geo-blocker'); ?></span>
                </div>
                <div class="medshi-geo-block-card-body">
                    <div class="medshi-geo-block-card-value"><?php echo \esc_html(\number_format_i18n($blocked_today ?? 0)); ?></div>
                    <div class="medshi-geo-block-card-desc"><?php \esc_html_e('in last 24 hours', 'geo-blocker'); ?></div>
                </div>
            </div>

            <!-- Card 4: Today's Allowed -->
            <div class="medshi-geo-block-summary-card medshi-geo-block-card-allowed">
                <div class="medshi-geo-block-card-header">
                    <span class="dashicons dashicons-yes-alt"></span>
                    <span class="medshi-geo-block-card-title"><?php \esc_html_e('Allowed Today', 'geo-blocker'); ?></span>
                </div>
                <div class="medshi-geo-block-card-body">
                    <div class="medshi-geo-block-card-value"><?php echo \esc_html(\number_format_i18n($allowed_today ?? 0)); ?></div>
                    <div class="medshi-geo-block-card-desc"><?php \esc_html_e('in last 24 hours', 'geo-blocker'); ?></div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Analytics Overview Section (for Chart) -->
    <div class="medshi-geo-block-section medshi-geo-block-analytics-overview">
        <div class="medshi-geo-block-section-header">
            <span class="dashicons dashicons-chart-bar"></span>
            <h3><?php \esc_html_e('Analytics Overview', 'geo-blocker'); ?></h3>
            <div class="medshi-geo-block-section-filters">
                <div class="medshi-geo-block-filter-item">
                    <select id="medshi_chart_filter_date_range" name="medshi_chart_filter_date_range" class="medshi-geo-block-chart-filter">
                        <option value="today">
                            📅 <?php \esc_html_e('Today', 'geo-blocker'); ?>
                        </option>
                        <option value="last_7_days">
                            📆 <?php \esc_html_e('Last 7 Days', 'geo-blocker'); ?>
                        </option>
                        <option value="last_30_days">
                            📆 <?php \esc_html_e('Last 30 Days', 'geo-blocker'); ?>
                        </option>
                        <option value="last_90_days">
                            📆 <?php \esc_html_e('Last 90 Days', 'geo-blocker'); ?>
                        </option>
                    </select>
                </div>
                
                <div class="medshi-geo-block-filter-item">
                    <select id="medshi_chart_filter_data_type" name="medshi_chart_filter_data_type" class="medshi-geo-block-chart-filter">
                        <option value="access_attempts">
                            👁️ <?php \esc_html_e('Access Attempts', 'geo-blocker'); ?>
                        </option>
                        <option value="unique_ips">
                            🌐 <?php \esc_html_e('Unique IPs', 'geo-blocker'); ?>
                        </option>
                        <option value="top_user_agents">
                            📱 <?php \esc_html_e('Top User Agents', 'geo-blocker'); ?>
                        </option>
                    </select>
                </div>
                
                <div class="medshi-geo-block-filter-item">
                    <select id="medshi_chart_filter_state" name="medshi_chart_filter_state" class="medshi-geo-block-chart-filter">
                        <option value="all_states">
                            🛡️ <?php \esc_html_e('All States', 'geo-blocker'); ?>
                        </option>
                        <option value="blocked">
                            ❌ <?php \esc_html_e('Blocked', 'geo-blocker'); ?>
                        </option>
                        <option value="allowed">
                            ✅ <?php \esc_html_e('Allowed', 'geo-blocker'); ?>
                        </option>
                        <option value="bypassed">
                            🔓 <?php \esc_html_e('Bypassed', 'geo-blocker'); ?>
                        </option>
                    </select>
                </div>
                
                <div class="medshi-geo-block-filter-item">
                    <select id="medshi_chart_filter_chart_type" name="medshi_chart_filter_chart_type" class="medshi-geo-block-chart-filter">
                        <option value="line">
                            📈 <?php \esc_html_e('Line Chart', 'geo-blocker'); ?>
                        </option>
                        <option value="pie">
                            🥧 <?php \esc_html_e('Pie Chart', 'geo-blocker'); ?>
                        </option>
                        <option value="bar">
                            📊 <?php \esc_html_e('Bar Chart', 'geo-blocker'); ?>
                        </option>
                    </select>
                </div>
            </div>
        </div>
        <div class="medshi-geo-block-section-content">
            <div class="medshi-geo-block-chart-container">
                <canvas id="medshiGeoBlockLogChart"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Access Logs Section (for Table) -->
    <div class="medshi-geo-block-section medshi-geo-block-access-logs">
        <div class="medshi-geo-block-section-header">
            <span class="dashicons dashicons-list-view"></span>
            <h3><?php \esc_html_e('Access Logs', 'geo-blocker'); ?></h3>
            <div class="medshi-geo-block-section-filters">
                <div class="medshi-geo-block-filter-item">
                    <select id="medshi_table_log_date_range" name="medshi_table_log_date_range" class="medshi-geo-block-table-filter">
                        <?php
                        // WPCS: Nonce not required for display/filter GET parameter; value is sanitized and escaped.
                        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reading GET parameter for display filtering only; value is properly sanitized
                        $current_table_date_range = isset($_GET['medshi_table_log_date_range']) ? \sanitize_key(\wp_unslash($_GET['medshi_table_log_date_range'])) : 'all_time'; // Default to all_time
                        $table_date_ranges = [
                            'all_time'      => \esc_html__('All Time', 'geo-blocker'),
                            'today'         => \esc_html__('Today', 'geo-blocker'),
                            'last_7_days'   => \esc_html__('Last 7 Days', 'geo-blocker'),
                            'last_30_days'  => \esc_html__('Last 30 Days', 'geo-blocker'),
                            'last_90_days'  => \esc_html__('Last 90 Days', 'geo-blocker'),
                        ];
                        foreach ($table_date_ranges as $value => $label) {
                            echo '<option value="' . \esc_attr($value) . '"' . \selected($current_table_date_range, $value, false) . '>' . \esc_html($label) . '</option>';
                        }
                        ?>
                    </select>
                </div>

                <div class="medshi-geo-block-filter-item">
                    <input type="search" id="medshi_table_log_search" name="medshi_table_log_search" class="medshi-geo-block-table-filter"
                           value="<?php 
                           // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reading GET parameter for search input display; value is properly sanitized and escaped
                           echo isset($_GET['medshi_table_log_search']) ? \esc_attr(\sanitize_text_field(\wp_unslash($_GET['medshi_table_log_search']))) : ''; 
                           ?>" 
                           placeholder="<?php \esc_attr_e('Search logs...', 'geo-blocker'); ?>" />
                </div>
            </div>
        </div>
        <div class="medshi-geo-block-section-content">
            <div class="medshi-geo-block-table-wrapper">
                <div id="medshi-geo-block-logs-table-container">
                    <?php
                    $log_table = new \Medshi\GeoBlocker\Admin\LogsListTable();
                    $log_table->prepare_items(); // This will now pick up the new GET params
                    $log_table->display();
                    ?>
                </div>
            </div>
            
            <!-- Moved action buttons to the bottom -->
            <div class="medshi-geo-block-actions-container">
                <button id="medshi-geo-block-clear-logs-button" class="button button-danger">
                    <?php \esc_html_e('Clear All Logs', 'geo-blocker'); ?>
                </button>
                <a href="<?php echo \esc_url(wp_nonce_url(admin_url( 'options-general.php?page=medshi-geo-blocker-settings&tab=logs_display&medshi_action=export_csv_logs' ), 'medshi_geo_block_export_csv_nonce', '_wpnonce_export_csv' ) ); ?>" class="button">
                    <?php \esc_html_e('Export Logs (CSV)', 'geo-blocker'); ?>
                </a>
                <a href="<?php echo \esc_url(wp_nonce_url(admin_url( 'options-general.php?page=medshi-geo-blocker-settings&tab=logs_display&medshi_action=export_json_logs' ), 'medshi_geo_block_export_json_nonce', '_wpnonce_export_json' ) ); ?>" class="button">
                    <?php \esc_html_e('Export Logs (JSON)', 'geo-blocker'); ?>
                </a>
            </div>
        </div>
    </div>
</div> 