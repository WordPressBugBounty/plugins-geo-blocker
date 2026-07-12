<?php

namespace Medshi\GeoBlocker\Admin;

use Medshi\GeoBlocker\Admin\LogsListTable;
use Medshi\GeoBlocker\Utils\CountryHelper;
use Medshi\GeoBlocker\Admin\AjaxHandler;
use Medshi\GeoBlocker\Admin\ActionHandler;

/**
 * Admin settings page class.
 *
 * @since 1.0.0
 */
class AdminPage {
    
    /**
     * Plugin file path.
     *
     * @since 1.0.0
     * @var string
     */
    private $plugin_file;
    
    /**
     * Plugin URL.
     *
     * @since 1.0.0
     * @var string
     */
    private $plugin_url;
    
    /**
     * Plugin directory path.
     *
     * @since 1.0.0
     * @var string
     */
    private $plugin_dir;
    
    /**
     * Constructor.
     *
     * @since 1.0.0
     * @param string $plugin_file Path to the main plugin file.
     * @param string $plugin_url URL to the plugin directory.
     * @param string $plugin_dir Path to the plugin directory.
     */
    public function __construct($plugin_file = '', $plugin_url = '', $plugin_dir = '') {
        $this->plugin_file = $plugin_file;
        $this->plugin_url = $plugin_url;
        $this->plugin_dir = $plugin_dir;
        
        \add_action('admin_menu', [$this, 'add_admin_menu_page']);
        \add_action('admin_init', [$this, 'register_plugin_settings']);
        \add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        
        // Add settings link to plugins page
        if (!empty($this->plugin_file)) {
            \add_action('plugin_action_links_' . \plugin_basename($this->plugin_file), [$this, 'add_settings_link_on_plugins_page']);
        }
        
        // Initialize handlers
        $ajax_handler = new AjaxHandler();
        \add_action('wp_ajax_medshi_geo_block_clear_logs', [$ajax_handler, 'clear_logs']);
        \add_action('wp_ajax_medshi_geo_block_get_chart_data', [$ajax_handler, 'get_chart_data']);
        \add_action('wp_ajax_medshi_geo_block_refresh_log_table', [$ajax_handler, 'refresh_log_table']);
        \add_action('wp_ajax_medshi_geo_block_toggle_enabled_status', [$ajax_handler, 'toggle_blocker_status']);
        
        $action_handler = new ActionHandler();
        \add_action('admin_init', [$action_handler, 'maybe_handle_export']);
    }
    
    /**
     * Enqueue admin scripts and styles.
     *
     * @since 1.0.0
     * @param string $hook_suffix The current admin page.
     * @return void
     */
    public function enqueue_admin_assets($hook_suffix) {
        // Only load on our settings page
        if ('settings_page_medshi-geo-blocker-settings' !== $hook_suffix) {
            return;
        }
        
        // Enqueue our custom admin CSS
        \wp_enqueue_style(
            'medshi-geo-blocker-admin-css',
            $this->plugin_url . 'admin/css/medshi-geo-block-admin.css',
            [],
            MEDSHI_GEO_BLOCK_VERSION
        );
        
        // Enqueue Select2 CSS
        \wp_enqueue_style(
            'medshi-geo-blocker-select2-css',
            $this->plugin_url . 'admin/css/select2.min.css',
            [],
            MEDSHI_GEO_BLOCK_VERSION
        );
        
        // Enqueue Select2 JS
        \wp_enqueue_script(
            'medshi-geo-blocker-select2-js',
            $this->plugin_url . 'admin/js/select2.min.js',
            ['jquery'],
            MEDSHI_GEO_BLOCK_VERSION,
            true
        );
        
        // Enqueue Chart.js
        \wp_enqueue_script(
            'medshi-geo-blocker-chart-js',
            $this->plugin_url . 'admin/js/chart.min.js',
            [],
            MEDSHI_GEO_BLOCK_VERSION,
            true
        );
        
        // Enqueue our custom admin JS
        \wp_enqueue_script(
            'medshi-geo-blocker-admin-js',
            $this->plugin_url . 'admin/js/medshi-geo-block-admin.js',
            ['jquery', 'medshi-geo-blocker-select2-js', 'medshi-geo-blocker-chart-js'],
            MEDSHI_GEO_BLOCK_VERSION,
            true
        );
        
        // Get current date range from GET parameter
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reading GET for client-side display setup; value sanitized.
        $current_date_range = isset($_GET['medshi_log_date_range']) ? \sanitize_key(\wp_unslash($_GET['medshi_log_date_range'])) : 'last_7_days';
        
        // Fetch country data for the country selector
        $all_countries = \Medshi\GeoBlocker\Utils\CountryHelper::get_all_countries();
        $saved_countries = (array) \get_option('medshi_geo_block_countries', []);
        
        // Localize script with data for AJAX
        \wp_localize_script(
            'medshi-geo-blocker-admin-js',
            'medshiGeoBlockerAdmin',
            [
                'ajax_url' => \admin_url('admin-ajax.php'),
                'clear_logs_action' => 'medshi_geo_block_clear_logs',
                'clear_logs_nonce' => \wp_create_nonce('medshi_geo_block_clear_logs_nonce'),
                'get_chart_data_action' => 'medshi_geo_block_get_chart_data',
                'get_chart_data_nonce' => \wp_create_nonce('medshi_geo_block_chart_data_nonce'),
                'refresh_log_table_action' => 'medshi_geo_block_refresh_log_table',
                'refresh_log_table_nonce' => \wp_create_nonce('medshi_geo_block_refresh_log_table_nonce'),
                'toggle_status_action' => 'medshi_geo_block_toggle_enabled_status',
                'toggle_status_nonce' => \wp_create_nonce('medshi_geo_block_toggle_status_nonce'),
                'status_enabled_text' => \esc_js(\__('Geo-Blocking: Enabled', 'geo-blocker')),
                'status_disabled_text' => \esc_js(\__('Geo-Blocking: Disabled', 'geo-blocker')),
                'status_toggle_error' => \esc_js(\__('Error updating status.', 'geo-blocker')),
                'error_refreshing_table' => \esc_js(\__('Error refreshing log table.', 'geo-blocker')),
                'chart_data_error' => \esc_js(\__('Could not load chart data.', 'geo-blocker')),
                'chart_title' => \esc_js(\__('Log Status Overview', 'geo-blocker')),
                'current_date_range' => $current_date_range,
                'default_chart_type' => 'attempts_by_hour',
                'default_chart_date_range' => 'today',
                'messages' => [
                    'confirm_clear' => \esc_html__('Are you sure you want to clear all logs? This action cannot be undone.', 'geo-blocker'),
                    'cleared' => \esc_html__('Logs cleared successfully.', 'geo-blocker'),
                    'error' => \esc_html__('An error occurred. Please try again.', 'geo-blocker')
                ],
                // Country selector data and settings
                'all_countries' => $all_countries,
                'saved_countries' => $saved_countries,
                'country_list_selector_id' => 'medshi-geo-block-country-list',
                'country_search_input_id' => 'medshi-geo-block-country-search',
                'hidden_inputs_container_id' => 'medshi-geo-block-selected-countries-hidden-inputs',
                'country_toggle_class' => 'medshi-geo-block-country-toggle',
                'no_countries_found_text' => \esc_js(\__('No countries match your search.', 'geo-blocker'))
            ]
        );
    }
    
    /**
     * Add the admin menu page.
     *
     * @since 1.0.0
     * @return void
     */
    public function add_admin_menu_page() {
        \add_options_page(
            \esc_html__('Geo Blocker Settings', 'geo-blocker'),
            \esc_html__('Geo Blocker', 'geo-blocker'),
            'manage_options',
            'medshi-geo-blocker-settings',
            [$this, 'render_settings_page']
        );
    }
    
    /**
     * Add settings link on the plugins page.
     *
     * @since 1.0.0
     * @param array $links Array of plugin action links.
     * @return array Modified array of plugin action links.
     */
    public function add_settings_link_on_plugins_page($links) {
        $settings_url = \admin_url('options-general.php?page=medshi-geo-blocker-settings');
        $settings_link = '<a href="' . \esc_url($settings_url) . '">' . \esc_html__('Settings', 'geo-blocker') . '</a>';
        \array_unshift($links, $settings_link);
        return $links;
    }
    
    /**
     * Register plugin settings.
     *
     * @since 1.0.0
     * @return void
     */
    public function register_plugin_settings() {
        // Register settings
        // phpcs:ignore PluginCheck.CodeAnalysis.SettingSanitization.register_settingDynamic -- Valid sanitize_callback provided in args.
        \register_setting(
            'medshi_geo_block_settings_group',
            'medshi_geo_block_enabled',
            ['sanitize_callback' => [$this, 'sanitize_checkbox']]
        );
        
        // phpcs:ignore PluginCheck.CodeAnalysis.SettingSanitization.register_settingDynamic -- Valid sanitize_callback provided in args.
        \register_setting(
            'medshi_geo_block_settings_group',
            'medshi_geo_block_mode',
            ['sanitize_callback' => [$this, 'sanitize_mode_selection']]
        );
        
        // phpcs:ignore PluginCheck.CodeAnalysis.SettingSanitization.register_settingDynamic -- Valid sanitize_callback provided in args.
        \register_setting(
            'medshi_geo_block_settings_group',
            'medshi_geo_block_countries',
            ['sanitize_callback' => [$this, 'sanitize_countries_list']]
        );
        
        // Register new blocking action settings
        // phpcs:ignore PluginCheck.CodeAnalysis.SettingSanitization.register_settingDynamic -- Valid sanitize_callback provided in args.
        \register_setting(
            'medshi_geo_block_settings_group',
            'medshi_geo_block_blocked_action',
            ['sanitize_callback' => [$this, 'sanitize_blocked_action']]
        );
        
        // phpcs:ignore PluginCheck.CodeAnalysis.SettingSanitization.register_settingDynamic -- Valid sanitize_callback provided in args.
        \register_setting(
            'medshi_geo_block_settings_group',
            'medshi_geo_block_custom_message',
            ['sanitize_callback' => 'sanitize_textarea_field']
        );
        
        // phpcs:ignore PluginCheck.CodeAnalysis.SettingSanitization.register_settingDynamic -- Valid sanitize_callback provided in args.
        \register_setting(
            'medshi_geo_block_settings_group',
            'medshi_geo_block_redirect_url',
            ['sanitize_callback' => 'esc_url_raw']
        );
        
        // Add settings section
        \add_settings_section(
            'medshi_geo_block_main_section',
            \esc_html__('Blocking Configuration', 'geo-blocker'),
            [$this, 'render_main_section_text'],
            'medshi-geo-blocker-settings'
        );
        
        // Add settings fields
        \add_settings_field(
            'medshi_geo_block_enabled_field',
            \esc_html__('Enable Geo Blocker', 'geo-blocker'),
            [$this, 'render_enabled_field'],
            'medshi-geo-blocker-settings',
            'medshi_geo_block_main_section'
        );
        
        \add_settings_field(
            'medshi_geo_block_mode_field',
            \esc_html__('Blocking Mode', 'geo-blocker'),
            [$this, 'render_mode_field'],
            'medshi-geo-blocker-settings',
            'medshi_geo_block_main_section'
        );
        
        \add_settings_field(
            'medshi_geo_block_countries_field',
            \esc_html__('Target Countries', 'geo-blocker'),
            [$this, 'render_countries_field'],
            'medshi-geo-blocker-settings',
            'medshi_geo_block_main_section'
        );
        
        // Add new settings fields for blocking actions
        \add_settings_field(
            'medshi_geo_block_blocked_action_field',
            \esc_html__('Blocked Action', 'geo-blocker'),
            [$this, 'render_blocked_action_field'],
            'medshi-geo-blocker-settings',
            'medshi_geo_block_main_section'
        );
        
        \add_settings_field(
            'medshi_geo_block_custom_message_field',
            \esc_html__('Custom Block Message', 'geo-blocker'),
            [$this, 'render_custom_message_field'],
            'medshi-geo-blocker-settings',
            'medshi_geo_block_main_section'
        );
        
        \add_settings_field(
            'medshi_geo_block_redirect_url_field',
            \esc_html__('Redirect URL', 'geo-blocker'),
            [$this, 'render_redirect_url_field'],
            'medshi-geo-blocker-settings',
            'medshi_geo_block_main_section'
        );
    }
    
    /**
     * Sanitize checkbox input.
     *
     * @since 1.0.0
     * @param mixed $input Checkbox input value.
     * @return string '1' if checked, '0' if not.
     */
    public function sanitize_checkbox($input) {
        return ($input === 1 || $input === '1' || $input === 'on') ? '1' : '0';
    }
    
    /**
     * Sanitize mode selection input.
     *
     * @since 1.0.0
     * @param string $input Mode selection input value.
     * @return string Sanitized mode value.
     */
    public function sanitize_mode_selection($input) {
        return ($input === 'allow_selected') ? 'allow_selected' : 'block_selected';
    }
    
    /**
     * Sanitize and validate countries list input.
     * 
     * Processes the array of country codes submitted from the form's hidden inputs
     * generated by JavaScript. Ensures that only valid country codes are saved.
     *
     * @since 1.0.0
     * @param mixed $input Countries list input value.
     * @return array Sanitized countries list as array.
     */
    public function sanitize_countries_list($input) {
        // Handle case when no countries are selected or input is invalid
        if (!is_array($input) || empty($input)) {
            return [];
        }
        
        // Get list of valid country codes from CountryHelper
        $all_countries_data = CountryHelper::get_all_countries();
        $valid_country_codes = [];
        
        if (!is_array($all_countries_data) || empty($all_countries_data)) {
            // Country data can't be retrieved - this is a critical issue
            return [];
        }
        
        // Extract valid country codes
        foreach ($all_countries_data as $country_obj) {
            if (isset($country_obj->code)) {
                $valid_country_codes[] = $country_obj->code;
            }
        }

        $sanitized_codes = [];
        $invalid_codes = [];
        
        foreach ($input as $code) {
            // Basic sanitization to prevent XSS
            $clean_code = \sanitize_text_field($code);
            $upper_code = strtoupper($clean_code);
            
            // Check if it's a valid format AND a known country code
            if (preg_match('/^[A-Z]{2}$/', $upper_code) && in_array($upper_code, $valid_country_codes, true)) {
                $sanitized_codes[] = $upper_code;
            } else {
                $invalid_codes[] = $clean_code;
            }
        }
        
        return array_unique($sanitized_codes);
    }
    
    /**
     * Sanitize blocked action input.
     *
     * @since 1.0.0
     * @param string $input Blocked action input value.
     * @return string Sanitized blocked action value.
     */
    public function sanitize_blocked_action($input) {
        $valid_actions = ['show_message', 'redirect', 'send_403'];
        return in_array($input, $valid_actions, true) ? $input : 'show_message';
    }
    
    /**
     * Render the main section text.
     *
     * @since 1.0.0
     * @return void
     */
    public function render_main_section_text() {
        ?>
        <p><?php \esc_html_e('These settings control how Geo Blocker identifies and blocks visitors.', 'geo-blocker'); ?></p>
        <?php
    }
    
    /**
     * Render the enabled field.
     *
     * @since 1.0.0
     * @return void
     */
    public function render_enabled_field() {
        ?>
        <input type="checkbox" id="medshi_geo_block_enabled" name="medshi_geo_block_enabled" value="1" <?php \checked('1', \get_option('medshi_geo_block_enabled', '0')); ?> />
        <label for="medshi_geo_block_enabled"><?php \esc_html_e('Enable overall geo-blocking functionality.', 'geo-blocker'); ?></label>
        <?php
    }
    
    /**
     * Render the mode field.
     *
     * @since 1.0.0
     * @return void
     */
    public function render_mode_field() {
        ?>
        <div id="medshi-geo-block-mode-tabs-container" class="medshi-geo-block-mode-tabs-container">
            <div class="medshi-geo-block-mode-tab" data-mode="block_selected" title="<?php \esc_attr_e('Block only the countries selected below. All other countries will be allowed.', 'geo-blocker'); ?>">
                <?php \esc_html_e('Block Selected Countries', 'geo-blocker'); ?>
            </div>
            <div class="medshi-geo-block-mode-tab" data-mode="allow_selected" title="<?php \esc_attr_e('Allow only the countries selected below. All other countries will be blocked.', 'geo-blocker'); ?>">
                <?php \esc_html_e('Allow Selected Countries', 'geo-blocker'); ?>
            </div>
            <input type="hidden" id="medshi_geo_block_mode_hidden" name="medshi_geo_block_mode" value="<?php echo \esc_attr(\get_option('medshi_geo_block_mode', 'block_selected')); ?>" />
        </div>
        <p class="description"><?php \esc_html_e('Select the desired blocking mode.', 'geo-blocker'); ?></p>
        <?php
    }
    
    /**
     * Render the countries field.
     *
     * @since 1.0.0
     * @return void
     */
    public function render_countries_field() {
        // $all_countries = CountryHelper::get_all_countries(); // Data will be used by JS
        // $saved_countries = (array) \get_option('medshi_geo_block_countries', []); // Current saved values might be useful for JS to pre-select
        ?>
        <div id="medshi-geo-block-country-selector-container" class="medshi-geo-block-country-selector-container">
            <div class="medshi-geo-block-region-filters" id="medshi-geo-block-region-filters-container">
                <p><em><?php \esc_html_e('Region filter buttons will appear here.', 'geo-blocker'); ?></em></p>
            </div>
            <div class="medshi-geo-block-country-search-container">
                <input type="search" id="medshi-geo-block-country-search" class="medshi-geo-block-country-search" placeholder="<?php \esc_attr_e('Search countries...', 'geo-blocker'); ?>" />
                <span class="dashicons dashicons-search"></span>
            </div>
            <div class="medshi-geo-block-select-all-controls" id="medshi-geo-block-select-all-container">
                <p><em><?php \esc_html_e('Select/Deselect All controls will appear here.', 'geo-blocker'); ?></em></p>
            </div>
            <div id="medshi-geo-block-country-list" class="medshi-geo-block-country-list medshi-geo-block-country-list-columns">
                <p><em><?php \esc_html_e('Country list will be rendered here by JavaScript.', 'geo-blocker'); ?></em></p>
            </div>
            <div id="medshi-geo-block-selected-countries-hidden-inputs">
                <?php
                // Optionally, if you want to pre-populate hidden fields for already saved countries,
                // you could loop through $saved_countries here and output them.
                // However, the instruction implies JS will handle this dynamically.
                // Example: foreach ($saved_countries as $code) { 
                // echo '<input type="hidden" name="medshi_geo_block_countries[]" value="' . esc_attr($code) . '">'
                // }
                ?>
            </div>
        </div>
        <p class="description"><?php \esc_html_e('Select countries using the interactive selector. Your selections will be saved using hidden fields populated by JavaScript.', 'geo-blocker'); ?></p>
        <?php
    }
    
    /**
     * Render the blocked action field.
     *
     * @since 1.0.0
     * @return void
     */
    public function render_blocked_action_field() {
        $blocked_action = \get_option('medshi_geo_block_blocked_action', 'show_message');
        ?>
        <select id="medshi_geo_block_blocked_action_select" name="medshi_geo_block_blocked_action">
            <option value="show_message" <?php \selected('show_message', $blocked_action); ?>><?php \esc_html_e('Show Custom Message', 'geo-blocker'); ?></option>
            <option value="redirect" <?php \selected('redirect', $blocked_action); ?>><?php \esc_html_e('Redirect to URL', 'geo-blocker'); ?></option>
            <option value="send_403" <?php \selected('send_403', $blocked_action); ?>><?php \esc_html_e('Send 403 Forbidden', 'geo-blocker'); ?></option>
        </select>
        <p class="description"><?php \esc_html_e('Choose the action to take when a visitor is blocked.', 'geo-blocker'); ?></p>
        <?php
    }
    
    /**
     * Render the custom message field.
     *
     * @since 1.0.0
     * @return void
     */
    public function render_custom_message_field() {
        $custom_message = \get_option('medshi_geo_block_custom_message', \esc_html__('Access from your location is currently restricted.', 'geo-blocker'));
        ?>
        <textarea id="medshi_geo_block_custom_message" name="medshi_geo_block_custom_message" rows="4" class="large-text"><?php echo \esc_textarea($custom_message); ?></textarea>
        <p class="description"><?php \esc_html_e('This message will be shown if "Show Custom Message" is selected. Only plain text is allowed.', 'geo-blocker'); ?></p>
        <?php
    }
    
    /**
     * Render the redirect URL field.
     *
     * @since 1.0.0
     * @return void
     */
    public function render_redirect_url_field() {
        $redirect_url = \get_option('medshi_geo_block_redirect_url', '');
        ?>
        <input type="url" id="medshi_geo_block_redirect_url" name="medshi_geo_block_redirect_url" value="<?php echo \esc_url($redirect_url); ?>" class="regular-text">
        <p class="description"><?php \esc_html_e('Enter a full URL (e.g., https://example.com/blocked). Used if "Redirect to URL" is selected.', 'geo-blocker'); ?></p>
        <?php
    }
    
    /**
     * Render the settings page.
     *
     * @since 1.0.0
     * @return void
     */
    public function render_settings_page() {
        // Get and sanitize the active tab
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reading GET for admin page tab navigation; value sanitized.
        $active_tab = isset($_GET['tab']) ? \sanitize_key(\wp_unslash($_GET['tab'])) : 'dashboard';
        
        ?>
        <div class="wrap">
            <h1><?php \esc_html_e('Geo Blocker', 'geo-blocker'); ?></h1>
            
            <h2 class="nav-tab-wrapper">
                <a href="<?php echo \esc_url(\admin_url('options-general.php?page=medshi-geo-blocker-settings&tab=dashboard')); ?>" class="nav-tab <?php echo $active_tab === 'dashboard' ? 'nav-tab-active' : ''; ?>"><?php \esc_html_e('Dashboard', 'geo-blocker'); ?></a>
                <a href="<?php echo \esc_url(\admin_url('options-general.php?page=medshi-geo-blocker-settings&tab=blocking_rules')); ?>" class="nav-tab <?php echo $active_tab === 'blocking_rules' ? 'nav-tab-active' : ''; ?>"><?php \esc_html_e('Blocking Rules', 'geo-blocker'); ?></a>
                <a href="<?php echo \esc_url(\admin_url('options-general.php?page=medshi-geo-blocker-settings&tab=logs_display')); ?>" class="nav-tab <?php echo $active_tab === 'logs_display' ? 'nav-tab-active' : ''; ?>"><?php \esc_html_e('Logs', 'geo-blocker'); ?></a>
            </h2>
            
            <div class="medshi-geo-block-tab-content">
                <?php
                // Conditionally render tab content
                if ($active_tab === 'dashboard') {
                    $this->render_dashboard_tab();
                } elseif ($active_tab === 'blocking_rules') {
                    $this->render_blocking_rules_tab();
                } elseif ($active_tab === 'logs_display') {
                    $this->render_logs_display_tab();
                }
                ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render the dashboard tab with plugin status and statistics.
     *
     * @since 1.0.0
     * @access private
     * @return void
     */
    private function render_dashboard_tab() {
        // Get plugin status
        $is_plugin_enabled = \get_option('medshi_geo_block_enabled', '0');
        $status_text = ($is_plugin_enabled === '1') 
            ? \esc_html__('Active', 'geo-blocker') 
            : \esc_html__('Inactive', 'geo-blocker');
        $status_class = ($is_plugin_enabled === '1') ? 'active' : 'inactive';
        
        // Generate login bypass URL
        $login_url = \site_url('/wp-login.php');
        $bypass_url = \add_query_arg('geoguard-bypass', '1', $login_url);
        
        // Try to get cached stats
        $cached_stats = \get_transient('medshi_geo_block_dashboard_stats');
        if (false !== $cached_stats) {
            // Use cached stats
            $blocked_today = $cached_stats['blocked_today'];
            $allowed_today = $cached_stats['allowed_today'];
            $total_blocked = $cached_stats['total_blocked'];
            $recent_logs = $cached_stats['recent_logs'];
        } else {
            // Get statistics from database
            global $wpdb;
            $table_name = $wpdb->prefix . 'medshi_geo_block_logs';
            
            // Get today's stats
            $today_start_gmt = gmdate('Y-m-d 00:00:00');
            
            // WPCS: DB OK. $table_name is safe, constructed from $wpdb->prefix
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Necessary for custom table.
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching -- Data is cached using transients outside this direct call.
            $blocked_today = (int) $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(id) FROM {$wpdb->prefix}medshi_geo_block_logs WHERE status = %s AND timestamp >= %s",
                    'blocked',
                    $today_start_gmt
                )
            );
            
            // WPCS: DB OK. $table_name is safe, constructed from $wpdb->prefix
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Necessary for custom table.
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching -- Data is cached using transients outside this direct call.
            $allowed_today = (int) $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(id) FROM {$wpdb->prefix}medshi_geo_block_logs WHERE status = %s AND timestamp >= %s",
                    'allowed',
                    $today_start_gmt
                )
            );
            
            // Get all-time blocked count
            // WPCS: DB OK. $table_name is safe, constructed from $wpdb->prefix
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Necessary for custom table.
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching -- Data is cached using transients outside this direct call.
            $total_blocked = (int) $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(id) FROM {$wpdb->prefix}medshi_geo_block_logs WHERE status = %s",
                    'blocked'
                )
            );
            
            // Get the last 5 log entries
            // WPCS: DB OK. $table_name is safe, constructed from $wpdb->prefix
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Necessary for custom table.
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching -- Data is cached using transients outside this direct call.
            $recent_logs = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT timestamp, ip_address, country_code, status 
                    FROM {$wpdb->prefix}medshi_geo_block_logs 
                    ORDER BY timestamp DESC 
                    LIMIT %d",
                    5
                )
            );
            
            // Cache the stats for 5 minutes
            $stats_to_cache = [
                'blocked_today' => $blocked_today,
                'allowed_today' => $allowed_today,
                'total_blocked' => $total_blocked,
                'recent_logs' => $recent_logs
            ];
            
            \set_transient('medshi_geo_block_dashboard_stats', $stats_to_cache, 5 * MINUTE_IN_SECONDS);
        }
        
        // NEW: Get count of countries in block/allow list
        $target_countries_array = (array) \get_option('medshi_geo_block_countries', []);
        $count_target_countries = count($target_countries_array);
        $blocking_mode = \get_option('medshi_geo_block_mode', 'block_selected');
        
        // Include the dashboard view
        include $this->plugin_dir . 'admin/views/tab-dashboard.php';
    }
    
    /**
     * Render the blocking rules tab with settings form.
     *
     * @since 1.0.0
     * @access private
     * @return void
     */
    private function render_blocking_rules_tab() {
        // Include the blocking rules view
        include $this->plugin_dir . 'admin/views/tab-blocking-rules.php';
    }
    
    /**
     * Render the logs display tab with log table and actions.
     *
     * @since 1.0.0
     * @access private
     * @return void
     */
    private function render_logs_display_tab() {
        // Get current date range filter
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reading GET for logs display filter; value sanitized.
        $current_range = isset($_GET['medshi_log_date_range']) ? \sanitize_key(\wp_unslash($_GET['medshi_log_date_range'])) : 'last_7_days';
        
        // Try to get cached summary data
        $transient_key = 'medshi_geo_block_logs_summary';
        $cached_summary = \get_transient($transient_key);
        
        if (false !== $cached_summary) {
            // Use cached data
            $total_attempts_all_time = $cached_summary['total_attempts_all_time'];
            $unique_ips_all_time = $cached_summary['unique_ips_all_time'];
            $blocked_today = $cached_summary['blocked_today']; 
            $allowed_today = $cached_summary['allowed_today'];
        } else {
            // Fetch summary card data from database
            global $wpdb;
            $table_name = $wpdb->prefix . 'medshi_geo_block_logs';
            
            // Total access attempts (all time)
            // WPCS: DB OK. $table_name is safe. Query has no dynamic parts.
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Necessary for custom table.
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching -- Data is cached using transients outside this direct call.
            $total_attempts_all_time = (int) $wpdb->get_var("SELECT COUNT(id) FROM {$table_name}");
            
            // Unique IPs (all time)
            // WPCS: DB OK. $table_name is safe, constructed from $wpdb->prefix
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Necessary for custom table.
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching -- Data is cached using transients outside this direct call.
            $unique_ips_all_time = (int) $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(DISTINCT ip_address) FROM {$wpdb->prefix}medshi_geo_block_logs WHERE ip_address != %s AND ip_address != %s",
                    'Unknown',
                    ''
                )
            );
            
            // Today's stats (blocked and allowed)
            $today_start_gmt = gmdate('Y-m-d 00:00:00'); // Use gmdate for consistency with DB timestamp
            
            // WPCS: DB OK. $table_name is safe, constructed from $wpdb->prefix
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Necessary for custom table.
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching -- Data is cached using transients outside this direct call.
            $blocked_today = (int) $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(id) FROM {$wpdb->prefix}medshi_geo_block_logs WHERE status = %s AND timestamp >= %s", 
                    'blocked', 
                    $today_start_gmt
                )
            );
            
            // WPCS: DB OK. $table_name is safe, constructed from $wpdb->prefix
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Necessary for custom table.
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching -- Data is cached using transients outside this direct call.
            $allowed_today = (int) $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(id) FROM {$wpdb->prefix}medshi_geo_block_logs WHERE status = %s AND timestamp >= %s", 
                    'allowed', 
                    $today_start_gmt
                )
            );
            
            // Cache the results for 5 minutes
            $summary_to_cache = [
                'total_attempts_all_time' => $total_attempts_all_time,
                'unique_ips_all_time' => $unique_ips_all_time,
                'blocked_today' => $blocked_today,
                'allowed_today' => $allowed_today
            ];
            
            \set_transient($transient_key, $summary_to_cache, 5 * MINUTE_IN_SECONDS);
        }
        
        // Include the logs display view
        include $this->plugin_dir . 'admin/views/tab-logs-display.php';
    }
} 