<?php
/**
 * Main plugin class for WP Site Analyzer
 *
 * @package WP_Site_Analyzer
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class WP_Site_Analyzer
 *
 * Main plugin class that coordinates all functionality
 */
class WP_Site_Analyzer {

    /**
     * Single instance of the class
     *
     * @var WP_Site_Analyzer
     */
    private static $instance = null;

    /**
     * Plugin version
     *
     * @var string
     */
    private $version;

    /**
     * Admin instance
     *
     * @var WP_Site_Analyzer_Admin
     */
    private $admin;

    /**
     * Scanner instances
     *
     * @var array
     */
    private $scanners = array();

    /**
     * Cache handler
     *
     * @var WP_Site_Analyzer_Cache_Handler
     */
    private $cache_handler;

    /**
     * Private constructor to prevent direct instantiation
     */
    private function __construct() {
        $this->version = WP_SITE_ANALYZER_VERSION;
        $this->load_dependencies();
        $this->set_locale();
    }

    /**
     * Get singleton instance
     *
     * @return WP_Site_Analyzer
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Load plugin dependencies
     */
    private function load_dependencies() {
        // Initialize cache handler
        $this->cache_handler = new WP_Site_Analyzer_Cache_Handler();

        // Initialize scanners
        $this->init_scanners();

        // Initialize admin if in admin area
        if ( is_admin() ) {
            $this->admin = new WP_Site_Analyzer_Admin();
        }
    }

    /**
     * Initialize all scanner classes
     */
    private function init_scanners() {
        $scanner_classes = array(
            'WP_Site_Analyzer_Post_Type_Scanner',
            'WP_Site_Analyzer_Taxonomy_Scanner',
            'WP_Site_Analyzer_Custom_Fields_Scanner',
            'WP_Site_Analyzer_Database_Scanner',
            'WP_Site_Analyzer_Plugin_Scanner',
            'WP_Site_Analyzer_Theme_Scanner',
            'WP_Site_Analyzer_Security_Scanner',
            'WP_Site_Analyzer_Theme_Style_Scanner',
        );

        foreach ( $scanner_classes as $scanner_class ) {
            if ( class_exists( $scanner_class ) ) {
                $this->scanners[ $scanner_class ] = new $scanner_class( $this->cache_handler );
            }
        }
    }

    /**
     * Set plugin locale for internationalization
     */
    private function set_locale() {
        add_action( 'init', array( $this, 'load_plugin_textdomain' ) );
    }

    /**
     * Load the plugin text domain
     */
    public function load_plugin_textdomain() {
        load_plugin_textdomain(
            'wp-site-analyzer',
            false,
            dirname( WP_SITE_ANALYZER_PLUGIN_BASENAME ) . '/languages/'
        );
    }

    /**
     * Run the plugin
     */
    public function run() {
        // Initialize hooks
        $this->init_hooks();

        // Register AJAX handlers
        $this->register_ajax_handlers();

        // Initialize integrations
        $this->init_integrations();
        
        // Initialize GitHub updater
        $this->init_github_updater();
    }

    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Add plugin action links
        add_filter( 'plugin_action_links_' . WP_SITE_ANALYZER_PLUGIN_BASENAME, array( $this, 'add_action_links' ) );

        // Register REST API endpoints
        add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
    }

    /**
     * Register AJAX handlers
     */
    private function register_ajax_handlers() {
        add_action( 'wp_ajax_wp_site_analyzer_scan', array( $this, 'handle_ajax_scan' ) );
        add_action( 'wp_ajax_wp_site_analyzer_export', array( $this, 'handle_ajax_export' ) );
        add_action( 'wp_ajax_wp_site_analyzer_get_progress', array( $this, 'handle_ajax_get_progress' ) );
    }

    /**
     * Initialize third-party integrations
     */
    private function init_integrations() {
        // ACF Integration
        if ( class_exists( 'ACF' ) ) {
            new WP_Site_Analyzer_ACF_Integration();
        }

        // WooCommerce Integration
        if ( class_exists( 'WooCommerce' ) ) {
            new WP_Site_Analyzer_WooCommerce_Integration();
        }

        // Elementor Integration
        if ( did_action( 'elementor/loaded' ) ) {
            new WP_Site_Analyzer_Elementor_Integration();
        }

        // Gutenberg Integration
        new WP_Site_Analyzer_Gutenberg_Integration();
    }

    /**
     * Initialize GitHub updater for automatic updates
     */
    private function init_github_updater() {
        // Get GitHub repository from settings
        $settings = get_option( 'wp_site_analyzer_settings' );
        $github_repo = isset( $settings['github_repo'] ) ? $settings['github_repo'] : 'username/wp-site-analyzer';
        $access_token = isset( $settings['github_token'] ) ? $settings['github_token'] : '';
        
        // Initialize updater
        new WP_Site_Analyzer_GitHub_Updater(
            WP_SITE_ANALYZER_PLUGIN_FILE,
            $github_repo,
            $access_token
        );
    }

    /**
     * Add plugin action links
     *
     * @param array $links Existing links
     * @return array Modified links
     */
    public function add_action_links( $links ) {
        $settings_link = sprintf(
            '<a href="%s">%s</a>',
            admin_url( 'admin.php?page=wp-site-analyzer' ),
            __( 'Settings', 'wp-site-analyzer' )
        );
        
        array_unshift( $links, $settings_link );
        return $links;
    }

    /**
     * Register REST API routes
     */
    public function register_rest_routes() {
        register_rest_route( 'wp-site-analyzer/v1', '/scan', array(
            'methods'  => 'POST',
            'callback' => array( $this, 'rest_scan_site' ),
            'permission_callback' => array( $this, 'rest_permission_check' ),
        ) );

        register_rest_route( 'wp-site-analyzer/v1', '/export', array(
            'methods'  => 'GET',
            'callback' => array( $this, 'rest_export_data' ),
            'permission_callback' => array( $this, 'rest_permission_check' ),
        ) );
    }

    /**
     * Check REST API permissions
     *
     * @return bool
     */
    public function rest_permission_check() {
        return is_user_logged_in() && current_user_can( 'manage_options' );
    }

    /**
     * Handle AJAX scan request
     */
    public function handle_ajax_scan() {
        // Verify nonce
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'wp_site_analyzer_scan' ) ) {
            wp_send_json_error( __( 'Security verification failed. Please refresh the page and try again.', 'wp-site-analyzer' ) );
        }

        // Check permissions
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'You do not have permission to perform this action.', 'wp-site-analyzer' ) );
        }

        // Get scan options
        $raw_options = isset( $_POST['options'] ) ? json_decode( stripslashes( $_POST['options'] ), true ) : array();
        
        // Sanitize options
        $options = array();
        if ( is_array( $raw_options ) ) {
            if ( isset( $raw_options['scanners'] ) && is_array( $raw_options['scanners'] ) ) {
                $options['scanners'] = array_map( 'sanitize_text_field', $raw_options['scanners'] );
            }
        }

        // Perform scan
        $results = $this->perform_scan( $options );

        // Return results
        wp_send_json_success( $results );
    }

    /**
     * Perform site scan
     *
     * @param array $options Scan options
     * @return array Scan results
     */
    public function perform_scan( $options = array() ) {
        $results = array();
        $start_time = microtime( true );

        // Update progress
        update_option( 'wp_site_analyzer_scan_progress', array(
            'status' => 'running',
            'current' => 0,
            'total' => count( $this->scanners ),
            'message' => __( 'Starting scan...', 'wp-site-analyzer' ),
        ) );

        $current = 0;
        foreach ( $this->scanners as $scanner_name => $scanner ) {
            if ( ! empty( $options['scanners'] ) && ! in_array( $scanner_name, $options['scanners'] ) ) {
                continue;
            }

            // Update progress
            update_option( 'wp_site_analyzer_scan_progress', array(
                'status' => 'running',
                'current' => $current,
                'total' => count( $this->scanners ),
                'message' => sprintf( __( 'Running %s...', 'wp-site-analyzer' ), $scanner_name ),
            ) );

            // Run scanner
            try {
                $scanner_results = $scanner->scan();
                $results[ $scanner_name ] = $scanner_results;
            } catch ( Exception $e ) {
                $results[ $scanner_name ] = array( 'error' => $e->getMessage() );
            }
            $current++;
        }

        // Calculate execution time
        $execution_time = microtime( true ) - $start_time;

        // Update progress - complete
        update_option( 'wp_site_analyzer_scan_progress', array(
            'status' => 'complete',
            'current' => count( $this->scanners ),
            'total' => count( $this->scanners ),
            'message' => __( 'Scan complete!', 'wp-site-analyzer' ),
            'execution_time' => $execution_time,
        ) );

        // Prepare final results
        $final_results = array(
            'results' => $results,
            'execution_time' => $execution_time,
            'timestamp' => current_time( 'mysql' ),
        );
        
        // Cache results
        $cache_result = $this->cache_handler->set( 'scan_results', $final_results, 3600 ); // Cache for 1 hour
        
        // Also store in option as backup
        update_option( 'wp_site_analyzer_scan_results_backup', $final_results, false );
        
        // Update last scan time
        update_option( 'wp_site_analyzer_last_scan', current_time( 'mysql' ) );

        return $final_results;
    }

    /**
     * Get scanner instances
     *
     * @return array
     */
    public function get_scanners() {
        return $this->scanners;
    }

    /**
     * Get cache handler
     *
     * @return WP_Site_Analyzer_Cache_Handler
     */
    public function get_cache_handler() {
        return $this->cache_handler;
    }

    /**
     * Plugin activation
     */
    public static function activate() {
        // Create database tables if needed
        self::create_tables();

        // Set default options
        self::set_default_options();

        // Clear rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Plugin deactivation
     */
    public static function deactivate() {
        // Clean up scheduled tasks
        wp_clear_scheduled_hook( 'wp_site_analyzer_cleanup' );

        // Clear transients
        delete_transient( 'wp_site_analyzer_scan_results' );

        // Clear rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Plugin uninstall
     */
    public static function uninstall() {
        // Remove options
        delete_option( 'wp_site_analyzer_settings' );
        delete_option( 'wp_site_analyzer_scan_progress' );
        delete_option( 'wp_site_analyzer_version' );

        // Remove tables if configured to do so
        $settings = get_option( 'wp_site_analyzer_settings' );
        if ( ! empty( $settings['remove_data_on_uninstall'] ) ) {
            self::drop_tables();
        }
    }

    /**
     * Create database tables
     */
    private static function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wp_site_analyzer_scans (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            scan_data longtext NOT NULL,
            scan_date datetime DEFAULT CURRENT_TIMESTAMP,
            scan_type varchar(50) NOT NULL,
            PRIMARY KEY (id),
            KEY scan_date (scan_date),
            KEY scan_type (scan_type)
        ) $charset_collate;";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
    }

    /**
     * Drop database tables
     */
    private static function drop_tables() {
        global $wpdb;
        $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}wp_site_analyzer_scans" );
    }

    /**
     * Set default plugin options
     */
    private static function set_default_options() {
        $defaults = array(
            'enable_caching' => true,
            'cache_duration' => 3600,
            'batch_size' => 100,
            'memory_limit' => '256M',
            'time_limit' => 300,
            'enable_integrations' => array(
                'acf' => true,
                'woocommerce' => true,
                'elementor' => true,
                'gutenberg' => true,
            ),
            'export_formats' => array(
                'json' => true,
                'markdown' => true,
                'ai_optimized' => true,
            ),
            'remove_data_on_uninstall' => false,
        );

        add_option( 'wp_site_analyzer_settings', $defaults );
        add_option( 'wp_site_analyzer_version', WP_SITE_ANALYZER_VERSION );
    }
}