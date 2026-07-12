<?php

namespace Medshi\GeoBlocker\Admin;

// Check if WP_List_Table exists
if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * List table class for displaying visitor logs.
 *
 * @since 1.0.0
 */
class LogsListTable extends \WP_List_Table {

    /**
     * Total number of items
     *
     * @since 1.0.0
     * @var int
     */
    public $total_items = 0;

    /**
     * Array of items to be displayed
     *
     * @since 1.0.0
     * @var array
     */
    public $items = [];

    /**
     * Constructor.
     *
     * @since 1.0.0
     */
    public function __construct() {
        parent::__construct([
            'singular' => 'log',
            'plural'   => 'logs',
            'ajax'     => false
        ]);
    }

    /**
     * Get table columns.
     *
     * @since 1.0.0
     * @return array
     */
    public function get_columns() {
        return [
            'cb'           => '<input type="checkbox" />',
            'timestamp'    => \esc_html__('Timestamp', 'geo-blocker'),
            'ip_address'   => \esc_html__('IP Address', 'geo-blocker'),
            'country_code' => \esc_html__('Country', 'geo-blocker'),
            'request_uri'  => \esc_html__('URI', 'geo-blocker'),
            'user_agent'   => \esc_html__('User Agent', 'geo-blocker'),
            'status'       => \esc_html__('Status', 'geo-blocker'),
        ];
    }

    /**
     * Get sortable columns for the table.
     *
     * @since 1.0.0
     * @return array
     */
    protected function get_sortable_columns() {
        return [
            'timestamp'    => ['timestamp', false], // false => default sort is DESC for time
            'ip_address'   => ['ip_address', true],
            'country_code' => ['country_code', true],
            'status'       => ['status', true],
        ];
    }

    /**
     * Prepare items for table.
     *
     * @since 1.0.0
     * @return void
     */
    public function prepare_items() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'medshi_geo_block_logs';
        
        // Define column headers
        $columns = $this->get_columns();
        $hidden = [];
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = [$columns, $hidden, $sortable];
        
        // Pagination settings
        $per_page = 20;
        $current_page = $this->get_pagenum();
        $offset = ($current_page - 1) * $per_page;
        
        // Get date range filter
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce not required for read-only display/filter GET parameters; values are sanitized.
        $current_date_range = isset($_GET['medshi_table_log_date_range']) ? \sanitize_key(\wp_unslash($_GET['medshi_table_log_date_range'])) : 'all_time';
        
        // Get search filter
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce not required for read-only display/filter GET parameters; values are sanitized.
        $search_term = isset($_GET['medshi_table_log_search']) ? \sanitize_text_field(\wp_unslash($_GET['medshi_table_log_search'])) : '';
        
        // Get sort parameters
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce not required for read-only display/filter REQUEST parameters; values are sanitized.
        $orderby = isset($_REQUEST['orderby']) ? \sanitize_key(\wp_unslash($_REQUEST['orderby'])) : 'timestamp';
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce not required for read-only display/filter REQUEST parameters; values are sanitized.
        $order_input = isset($_REQUEST['order']) ? strtoupper(\sanitize_key(\wp_unslash($_REQUEST['order']))) : 'DESC';
        $order = in_array($order_input, ['ASC', 'DESC']) ? $order_input : 'DESC';
        
        // Validate orderby against whitelist
        $valid_sort_columns = [
            'timestamp' => 'timestamp',
            'ip_address' => 'ip_address',
            'country_code' => 'country_code',
            'status' => 'status'
        ];
        
        // Default sort column
        $db_column_for_orderby = 'timestamp';
        
        // Check if requested orderby column is valid
        if (isset($valid_sort_columns[$orderby])) {
            $db_column_for_orderby = $valid_sort_columns[$orderby];
        }
        
        // Build the SQL parts
        $where_conditions = [];
        $prepare_args = [];
        
        // Determine where clause based on date range
        if ($current_date_range === 'today') {
            $today_start_gmt = gmdate('Y-m-d 00:00:00');
            $where_conditions[] = "timestamp >= %s";
            $prepare_args[] = $today_start_gmt;
        } elseif ($current_date_range === 'last_7_days') {
            $start_date = gmdate('Y-m-d 00:00:00', strtotime('-7 days GMT'));
            $where_conditions[] = "timestamp >= %s";
            $prepare_args[] = $start_date;
        } elseif ($current_date_range === 'last_30_days') {
            $start_date = gmdate('Y-m-d 00:00:00', strtotime('-30 days GMT'));
            $where_conditions[] = "timestamp >= %s";
            $prepare_args[] = $start_date;
        } elseif ($current_date_range === 'last_90_days') {
            $start_date = gmdate('Y-m-d 00:00:00', strtotime('-90 days GMT'));
            $where_conditions[] = "timestamp >= %s";
            $prepare_args[] = $start_date;
        }
        // 'all_time' doesn't need a where clause
        
        // Add search term to where clause if provided
        if (!empty($search_term)) {
            $search_like = '%' . $wpdb->esc_like($search_term) . '%';
            $where_conditions[] = "(ip_address LIKE %s OR request_uri LIKE %s OR user_agent LIKE %s)";
            $prepare_args[] = $search_like;
            $prepare_args[] = $search_like;
            $prepare_args[] = $search_like;
        }
        
        // Build the count query
        $sql_parts = ["SELECT COUNT(id) FROM {$table_name}"]; // WPCS: DB OK. $table_name is safe.
        
        // Add WHERE clause if we have conditions
        if (!empty($where_conditions)) {
            $sql_parts[] = "WHERE " . implode(" AND ", $where_conditions);
        }
        
        // Get total items count
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Necessary for querying custom table data.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching -- Caching for this admin list table deemed not critical or adds undue complexity for dynamic filters.
        if (!empty($prepare_args)) {
            // Only use prepare when we have arguments to prepare
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- SQL is dynamically built with proper placeholders matched to $prepare_args array spread parameter
            $this->total_items = $wpdb->get_var(
                $wpdb->prepare(
                    implode(" ", $sql_parts),
                    ...$prepare_args
                )
            );
        } else {
            // Direct query is safe when no user input is involved
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- No user input in this query path
            $this->total_items = $wpdb->get_var(implode(" ", $sql_parts));
        }
        
        // Set pagination arguments
        $this->set_pagination_args([
            'total_items' => $this->total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil($this->total_items / $per_page)
        ]);
        
        // Build the main items query - reuse the same WHERE conditions
        $sql_parts = ["SELECT * FROM {$table_name}"]; // WPCS: DB OK. $table_name is safe.
        
        if (!empty($where_conditions)) {
            $sql_parts[] = "WHERE " . implode(" AND ", $where_conditions);
        }
        
        // Add ORDER BY (these values are whitelisted above)
        $sql_parts[] = sprintf("ORDER BY %s %s", esc_sql($db_column_for_orderby), esc_sql($order));
        
        // Add LIMIT and OFFSET
        $sql_parts[] = "LIMIT %d";
        $prepare_args[] = $per_page;
        
        $sql_parts[] = "OFFSET %d";
        $prepare_args[] = $offset;
        
        // Execute the final query
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Necessary for querying custom table data.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching -- Caching for this admin list table deemed not critical or adds undue complexity for dynamic filters.
        if (!empty($prepare_args)) {
            // Only use prepare when we have arguments to prepare
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- SQL is dynamically built with proper placeholders matched to $prepare_args array spread parameter
            $this->items = $wpdb->get_results(
                $wpdb->prepare(
                    implode(" ", $sql_parts),
                    ...$prepare_args
                ),
                ARRAY_A
            );
        } else {
            // Direct query is safe when no user input is involved
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- No user input in this query path
            $this->items = $wpdb->get_results(implode(" ", $sql_parts), ARRAY_A);
        }
    }

    /**
     * Default column renderer.
     *
     * @since 1.0.0
     * @param array  $item        Item data.
     * @param string $column_name Column name.
     * @return string
     */
    protected function column_default($item, $column_name) {
        if (isset($item[$column_name])) {
            return \esc_html($item[$column_name]);
        }
        return '';
    }

    /**
     * Checkbox column renderer.
     *
     * @since 1.0.0
     * @param array $item Item data.
     * @return string
     */
    protected function column_cb($item) {
        return sprintf(
            '<input type="checkbox" name="log_id[]" value="%s" />',
            \esc_attr($item['id'])
        );
    }

    /**
     * Request URI column renderer.
     *
     * @since 1.0.0
     * @param array $item Item data.
     * @return string
     */
    protected function column_request_uri($item) {
        $uri = isset($item['request_uri']) ? $item['request_uri'] : '';
        if (strlen($uri) > 50) {
            $uri = substr($uri, 0, 47) . '...';
        }
        return \esc_html($uri);
    }

    /**
     * User agent column renderer.
     *
     * @since 1.0.0
     * @param array $item Item data.
     * @return string
     */
    protected function column_user_agent($item) {
        $agent = isset($item['user_agent']) ? $item['user_agent'] : '';
        if (strlen($agent) > 50) {
            $agent = substr($agent, 0, 47) . '...';
        }
        return \esc_html($agent);
    }

    /**
     * Status column renderer.
     *
     * @since 1.0.0
     * @param array $item Item data.
     * @return string
     */
    protected function column_status($item) {
        $status = isset($item['status']) ? $item['status'] : '';
        $status_class = '';
        
        switch ($status) {
            case 'blocked':
                $status_class = 'error';
                break;
            case 'bypassed':
                $status_class = 'warning';
                break;
            case 'allowed':
                $status_class = 'success';
                break;
            case 'allowed_no_ip':
                $status_class = 'info';
                break;
        }
        
        return '<span class="log-status ' . \esc_attr($status_class) . '">' . \esc_html($status) . '</span>';
    }
} 