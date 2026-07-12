<?php

namespace Medshi\GeoBlocker;

// Import necessary classes
use Medshi\GeoBlocker\Core\Blocker;
use Medshi\GeoBlocker\API\IpWhoIs;
use Medshi\GeoBlocker\Admin\AdminPage;
use Medshi\GeoBlocker\Core\Logger;

/**
 * The main plugin class.
 *
 * @since 1.0.0
 */
final class Plugin {

    /**
     * The single instance of the class.
     *
     * @since 1.0.0
     * @var Plugin|null
     */
    private static $_instance = null;

    /**
     * Main Plugin Instance.
     *
     * Ensures only one instance of Plugin is loaded or can be loaded.
     *
     * @since 1.0.0
     * @static
     * @return Plugin - Main instance.
     */
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Constructor.
     *
     * @since 1.0.0
     * @access private
     */
    private function __construct() {
        $this->init_hooks();
        $this->load_dependencies();
    }

    /**
     * Initialize WordPress hooks.
     *
     * @since 1.0.0
     * @access private
     */
    private function init_hooks() {
        \add_action( 'wp_loaded', [ $this, 'maybe_block_visitor' ], 10 );
    }
    
    /**
     * Load plugin dependencies.
     *
     * @since 1.0.0
     * @access private
     */
    private function load_dependencies() {
        if ( \is_admin() ) {
            new AdminPage(MEDSHI_GEO_BLOCK_PLUGIN_FILE, MEDSHI_GEO_BLOCK_PLUGIN_URL, MEDSHI_GEO_BLOCK_PLUGIN_DIR);
        }
    }

    /**
     * Check visitor's location and block if necessary.
     *
     * @since 1.0.0
     * @return void
     */
    public function maybe_block_visitor() {
        // Don't block essential WordPress actions
        if ( ( defined( 'DOING_AJAX' ) && \DOING_AJAX ) || // Legacy AJAX check
             ( function_exists( 'wp_doing_ajax' ) && \wp_doing_ajax() ) ||
             ( function_exists( 'wp_doing_cron' ) && \wp_doing_cron() ) ||
             ( defined( 'WP_CLI' ) && \WP_CLI ) ) {
            return;
        }
        
        // Get plugin settings
        $is_enabled = \get_option('medshi_geo_block_enabled', '0');
        $blocking_mode = \get_option('medshi_geo_block_mode', 'block_selected');
        $target_countries = (array) \get_option('medshi_geo_block_countries', []);
        
        // Check if the plugin is enabled
        if ('1' !== $is_enabled) {
            return;
        }
        
        // Check if this is a common browser auto-request that should be skipped
        $request_uri = isset($_SERVER['REQUEST_URI']) ? \wp_unslash($_SERVER['REQUEST_URI']) : '';
        
        // List of file patterns to skip (exact matches and contains)
        $skip_exact_matches = [
            '/favicon.ico',
            '/robots.txt',
        ];
        
        $skip_contains = [
            'favicon.ico',
            'apple-touch-icon',
            'robots.txt',
            'wp-json/',
            'sitemap',
        ];
        
        // Skip exact matches
        if (in_array($request_uri, $skip_exact_matches)) {
            return; // Skip processing entirely
        }
        
        // Skip if contains any of these strings
        foreach ($skip_contains as $term) {
            if (stripos($request_uri, $term) !== false) {
                return; // Skip processing entirely
            }
        }
        
        // Start of logic where logging applies
        $ip_address = Blocker::get_visitor_ip();
        $log_data = [
            'ip_address'   => $ip_address ?: 'Unknown',
            'request_uri'  => $request_uri ? \esc_url_raw($request_uri) : '',
            'user_agent'   => isset($_SERVER['HTTP_USER_AGENT']) ? \sanitize_text_field(\wp_unslash($_SERVER['HTTP_USER_AGENT'])) : '',
            'country_code' => 'N/A',
            'continent'    => '',
            'city'         => '',
        ];
        
        // Check for login page bypass
        $is_login_page = ( isset( $GLOBALS['pagenow'] ) && 'wp-login.php' === $GLOBALS['pagenow'] );
        
        if ( ! $is_login_page && isset( $_SERVER['PHP_SELF'] ) ) {
            // WPCS: Value is unslashed and sanitized.
            $php_self = \sanitize_text_field( \wp_unslash( $_SERVER['PHP_SELF'] ) );
            $is_login_page = strpos( $php_self, 'wp-login.php' ) !== false;
        }
        
        // Check for admin area access - new code to avoid logging admin visits
        $is_admin_area = \is_admin();
        $is_logged_in = \is_user_logged_in();
        
        if ( $is_login_page && isset( $_GET['geoguard-bypass'] ) ) {
            // WPCS: Nonce not required for login page bypass; value is unslashed and sanitized.
            $bypass = \sanitize_key( \wp_unslash( $_GET['geoguard-bypass'] ) );
            if ( '1' === $bypass ) {
                // Bypass parameter found on login page, do not block.
                $log_data['status'] = 'bypassed';
                Logger::add_log_entry($log_data);
                return;
            }
        }
        
        // If no IP address, we can't determine location
        if (!$ip_address) {
            // Only log if not an admin user in wp-admin
            if (!($is_admin_area && $is_logged_in)) {
                $log_data['status'] = 'allowed_no_ip';
                Logger::add_log_entry($log_data);
            }
            return;
        }
        
        $country_code = IpWhoIs::get_geolocation($ip_address);

        if ($country_code) {
            $log_data['country_code'] = $country_code;
            
            $should_block = false;
            
            // Determine if we should block based on blocking mode
            if ('block_selected' === $blocking_mode) {
                // Block only selected countries
                if (!empty($target_countries) && in_array($country_code, $target_countries)) {
                    $should_block = true;
                }
            } else { // 'allow_selected' mode
                // Block all except selected countries
                if (empty($target_countries)) {
                    // No countries allowed, block everyone
                    $should_block = true;
                } elseif (!in_array($country_code, $target_countries)) {
                    // Country not in allowed list - block
                    $should_block = true;
                }
            }
            
            if ($should_block) {
                $log_data['status'] = 'blocked';
                Logger::add_log_entry($log_data);
                
                // Get blocking action settings
                $blocked_action = \get_option('medshi_geo_block_blocked_action', 'show_message');
                $custom_message_raw = \get_option('medshi_geo_block_custom_message', \esc_html__('Access from your location is currently restricted.', 'geo-blocker'));
                $redirect_url = \get_option('medshi_geo_block_redirect_url', '');
                
                // Take appropriate action based on the selected blocking method
                switch ($blocked_action) {
                    case 'show_message':
                        // Escape the plain text message for HTML output
                        \wp_die(\esc_html($custom_message_raw), \esc_html__('Geo Blocker - Access Denied', 'geo-blocker'), ['response' => 403]);
                        break;
                        
                    case 'redirect':
                        $redirect_url = \esc_url_raw($redirect_url);
                        if (!empty($redirect_url)) {
                            \wp_redirect($redirect_url);
                            exit;
                        } else {
                            // Fallback to message if URL is empty
                            \wp_die(\esc_html($custom_message_raw), \esc_html__('Geo Blocker - Access Denied', 'geo-blocker'), ['response' => 403]);
                        }
                        break;
                        
                    case 'send_403':
                        \status_header(403);
                        \nocache_headers();
                        echo \esc_html__('403 Forbidden - Access Denied', 'geo-blocker');
                        exit;
                        break;
                        
                    default:
                        // Fallback to message for unknown action
                        \wp_die(\esc_html($custom_message_raw), \esc_html__('Geo Blocker - Access Denied', 'geo-blocker'), ['response' => 403]);
                }
                return;
            }
        }
        
        // If not blocked by this point, only log as allowed if not an admin user in wp-admin
        if (!($is_logged_in)) {
            $log_data['status'] = 'allowed';
            Logger::add_log_entry($log_data);
        }
    }
} 