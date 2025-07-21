<?php
/**
 * Admin functionality for WP Site Analyzer
 *
 * @package WP_Site_Analyzer
 * @subpackage Admin
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class WP_Site_Analyzer_Admin
 *
 * Handles all admin-related functionality
 */
class WP_Site_Analyzer_Admin {

    /**
     * Constructor
     */
    public function __construct() {
        $this->init_hooks();
    }

    /**
     * Initialize admin hooks
     */
    private function init_hooks() {
        // Admin menu
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        
        // Admin scripts and styles
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
        
        // Admin notices
        add_action( 'admin_notices', array( $this, 'display_admin_notices' ) );
        
        // AJAX handlers
        add_action( 'wp_ajax_wp_site_analyzer_scan', array( $this, 'handle_ajax_scan' ) );
        add_action( 'wp_ajax_wp_site_analyzer_export', array( $this, 'handle_ajax_export' ) );
        add_action( 'wp_ajax_wp_site_analyzer_get_progress', array( $this, 'handle_ajax_get_progress' ) );
        add_action( 'wp_ajax_wp_site_analyzer_clear_cache', array( $this, 'handle_ajax_clear_cache' ) );
        add_action( 'wp_ajax_wp_site_analyzer_test_cache', array( $this, 'handle_ajax_test_cache' ) );
    }

    /**
     * Add admin menu items
     */
    public function add_admin_menu() {
        // Main menu
        add_menu_page(
            __( 'WP Site Analyzer', 'wp-site-analyzer' ),
            __( 'Site Analyzer', 'wp-site-analyzer' ),
            'manage_options',
            'wp-site-analyzer',
            array( $this, 'render_dashboard_page' ),
            'dashicons-search',
            99
        );

        // Dashboard submenu
        add_submenu_page(
            'wp-site-analyzer',
            __( 'Dashboard', 'wp-site-analyzer' ),
            __( 'Dashboard', 'wp-site-analyzer' ),
            'manage_options',
            'wp-site-analyzer',
            array( $this, 'render_dashboard_page' )
        );


        // AI Report submenu
        add_submenu_page(
            'wp-site-analyzer',
            __( 'AI Report', 'wp-site-analyzer' ),
            __( 'AI Report', 'wp-site-analyzer' ),
            'manage_options',
            'wp-site-analyzer-ai-report',
            array( $this, 'render_ai_report_page' )
        );

        // Settings submenu
        add_submenu_page(
            'wp-site-analyzer',
            __( 'Settings', 'wp-site-analyzer' ),
            __( 'Settings', 'wp-site-analyzer' ),
            'manage_options',
            'wp-site-analyzer-settings',
            array( $this, 'render_settings_page' )
        );
    }

    /**
     * Enqueue admin scripts and styles
     *
     * @param string $hook_suffix Current admin page
     */
    public function enqueue_admin_assets( $hook_suffix ) {
        // Only load on our plugin pages
        if ( strpos( $hook_suffix, 'wp-site-analyzer' ) === false ) {
            return;
        }

        // Styles
        wp_enqueue_style(
            'wp-site-analyzer-admin',
            WP_SITE_ANALYZER_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            WP_SITE_ANALYZER_VERSION
        );

        // Scripts
        wp_enqueue_script(
            'wp-site-analyzer-admin',
            WP_SITE_ANALYZER_PLUGIN_URL . 'assets/js/admin.js',
            array( 'jquery', 'wp-util' ),
            WP_SITE_ANALYZER_VERSION,
            true
        );

        // Localize script
        wp_localize_script( 'wp-site-analyzer-admin', 'wpSiteAnalyzer', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'wp_site_analyzer_scan' ),
            'strings' => array(
                'scanning' => __( 'Scanning...', 'wp-site-analyzer' ),
                'scan_complete' => __( 'Scan complete!', 'wp-site-analyzer' ),
                'scan_error' => __( 'An error occurred during scanning.', 'wp-site-analyzer' ),
                'export_error' => __( 'An error occurred during export.', 'wp-site-analyzer' ),
                'confirm_scan' => __( 'Are you sure you want to start a new scan? This may take several minutes.', 'wp-site-analyzer' ),
            ),
        ) );
    }

    /**
     * Display admin notices
     */
    public function display_admin_notices() {
        // Check if we need to display any notices
        $screen = get_current_screen();
        if ( strpos( $screen->id, 'wp-site-analyzer' ) === false ) {
            return;
        }

        // Check for success message
        if ( isset( $_GET['scan_complete'] ) && $_GET['scan_complete'] === '1' ) {
            ?>
            <div class="notice notice-success is-dismissible">
                <p><?php esc_html_e( 'Site scan completed successfully!', 'wp-site-analyzer' ); ?></p>
            </div>
            <?php
        }

        // Check for export success
        if ( isset( $_GET['export_complete'] ) && $_GET['export_complete'] === '1' ) {
            ?>
            <div class="notice notice-success is-dismissible">
                <p><?php esc_html_e( 'Export completed successfully!', 'wp-site-analyzer' ); ?></p>
            </div>
            <?php
        }
    }

    /**
     * Render dashboard page
     */
    public function render_dashboard_page() {
        // Get last scan data
        $last_scan = get_option( 'wp_site_analyzer_last_scan' );
        $cache_handler = new WP_Site_Analyzer_Cache_Handler();
        $cached_results = $cache_handler->get( 'scan_results' );
        
        ?>
        <div class="wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
            
            <div class="wp-site-analyzer-dashboard">
                <div class="dashboard-section action-section">
                    <h2><?php esc_html_e( 'Quick Actions', 'wp-site-analyzer' ); ?></h2>
                    
                    <div class="quick-actions">
                        <button id="start-scan" class="button button-primary button-hero">
                            <?php esc_html_e( 'Start New Scan', 'wp-site-analyzer' ); ?>
                        </button>
                        
                        <?php if ( $cached_results ) : ?>
                        <button id="view-ai-report" class="button button-secondary button-hero" onclick="window.location.href='<?php echo admin_url( 'admin.php?page=wp-site-analyzer-ai-report' ); ?>'">
                            <?php esc_html_e( 'View AI Report', 'wp-site-analyzer' ); ?>
                        </button>
                        <?php endif; ?>
                    </div>
                    
                    <div id="scan-progress" style="display: none;">
                        <h3><?php esc_html_e( 'Scan Progress', 'wp-site-analyzer' ); ?></h3>
                        <div class="progress-bar">
                            <div class="progress-bar-fill" style="width: 0%;"></div>
                        </div>
                        <p class="progress-message"></p>
                    </div>
                </div>
                
                <?php if ( $cached_results ) : ?>
                <div class="dashboard-section">
                    <h2><?php esc_html_e( 'Last Scan Summary', 'wp-site-analyzer' ); ?></h2>
                    <ul class="scan-summary">
                        <li>
                            <strong><?php esc_html_e( 'Date:', 'wp-site-analyzer' ); ?></strong> 
                            <?php 
                            $timestamp = isset( $cached_results['timestamp'] ) ? $cached_results['timestamp'] : $last_scan;
                            if ( $timestamp ) {
                                echo esc_html( human_time_diff( strtotime( $timestamp ), current_time( 'timestamp' ) ) ) . ' ' . esc_html__( 'ago', 'wp-site-analyzer' );
                            } else {
                                esc_html_e( 'Unknown', 'wp-site-analyzer' );
                            }
                            ?>
                        </li>
                        <li>
                            <strong><?php esc_html_e( 'Execution Time:', 'wp-site-analyzer' ); ?></strong> 
                            <?php 
                            if ( isset( $cached_results['execution_time'] ) ) {
                                echo esc_html( round( $cached_results['execution_time'], 2 ) ) . ' ' . esc_html__( 'seconds', 'wp-site-analyzer' );
                            } else {
                                esc_html_e( 'Unknown', 'wp-site-analyzer' );
                            }
                            ?>
                        </li>
                        <li>
                            <strong><?php esc_html_e( 'Post Types Found:', 'wp-site-analyzer' ); ?></strong> 
                            <?php echo isset( $cached_results['results']['WP_Site_Analyzer_Post_Type_Scanner']['statistics']['total_post_types'] ) ? esc_html( $cached_results['results']['WP_Site_Analyzer_Post_Type_Scanner']['statistics']['total_post_types'] ) : '0'; ?>
                        </li>
                        <li>
                            <strong><?php esc_html_e( 'Taxonomies Found:', 'wp-site-analyzer' ); ?></strong> 
                            <?php echo isset( $cached_results['results']['WP_Site_Analyzer_Taxonomy_Scanner']['statistics']['total_taxonomies'] ) ? esc_html( $cached_results['results']['WP_Site_Analyzer_Taxonomy_Scanner']['statistics']['total_taxonomies'] ) : '0'; ?>
                        </li>
                    </ul>
                </div>
                <?php endif; ?>
                
                <div class="dashboard-section">
                    <h2><?php esc_html_e( 'About WP Site Analyzer', 'wp-site-analyzer' ); ?></h2>
                    <p><?php esc_html_e( 'This plugin analyzes your WordPress site and generates a comprehensive report optimized for AI models. The report includes:', 'wp-site-analyzer' ); ?></p>
                    <ul style="list-style: disc; margin-left: 20px;">
                        <li><?php esc_html_e( 'Complete site structure and content types', 'wp-site-analyzer' ); ?></li>
                        <li><?php esc_html_e( 'Theme styles and visual design patterns', 'wp-site-analyzer' ); ?></li>
                        <li><?php esc_html_e( 'Custom fields and metadata', 'wp-site-analyzer' ); ?></li>
                        <li><?php esc_html_e( 'Plugin and theme information', 'wp-site-analyzer' ); ?></li>
                        <li><?php esc_html_e( 'Database structure and relationships', 'wp-site-analyzer' ); ?></li>
                    </ul>
                    <p><strong><?php esc_html_e( 'How to use:', 'wp-site-analyzer' ); ?></strong> <?php esc_html_e( 'Click "Start New Scan" to analyze your site, then view the AI Report to copy and share with AI models.', 'wp-site-analyzer' ); ?></p>
                </div>
                
                <?php if ( current_user_can( 'manage_options' ) ) : // Temporarily removed WP_DEBUG check for debugging ?>
                <div class="dashboard-section" style="background: #fff3cd; border-color: #ffeaa7;">
                    <h2><?php esc_html_e( 'Debug Information', 'wp-site-analyzer' ); ?></h2>
                    
                    <?php
                    // Check cache
                    $cache_handler = new WP_Site_Analyzer_Cache_Handler();
                    $cached_results = $cache_handler->get( 'scan_results' );
                    $last_scan = get_option( 'wp_site_analyzer_last_scan' );
                    $scan_progress = get_option( 'wp_site_analyzer_scan_progress' );
                    ?>
                    
                    <p><strong>Cache Status:</strong> <?php echo $cached_results ? 'Results cached' : 'No cached results'; ?></p>
                    <p><strong>Last Scan:</strong> <?php echo $last_scan ? esc_html( $last_scan ) : 'Never'; ?></p>
                    <p><strong>Scan Progress:</strong> <?php echo '<pre>' . esc_html( json_encode( $scan_progress, JSON_PRETTY_PRINT ) ) . '</pre>'; ?></p>
                    
                    <?php if ( $cached_results ) : ?>
                        <details>
                            <summary style="cursor: pointer; font-weight: bold; margin: 10px 0;">View Cached Results (Click to expand)</summary>
                            <pre style="background: #f8f9fa; padding: 10px; overflow: auto; max-height: 400px;"><?php 
                                echo esc_html( json_encode( array(
                                    'execution_time' => $cached_results['execution_time'] ?? 'N/A',
                                    'timestamp' => $cached_results['timestamp'] ?? 'N/A',
                                    'scanners_found' => array_keys( $cached_results['results'] ?? array() ),
                                    'results_sample' => array_map( function( $scanner_results ) {
                                        return array(
                                            'has_data' => ! empty( $scanner_results ),
                                            'keys' => is_array( $scanner_results ) ? array_keys( $scanner_results ) : 'Not an array'
                                        );
                                    }, $cached_results['results'] ?? array() )
                                ), JSON_PRETTY_PRINT ) ); 
                            ?></pre>
                        </details>
                        
                        <p style="margin-top: 10px;">
                            <button class="button" onclick="wpSiteAnalyzerDebug.clearCache()"><?php esc_html_e( 'Clear Cache', 'wp-site-analyzer' ); ?></button>
                            <button class="button" onclick="wpSiteAnalyzerDebug.testCache()"><?php esc_html_e( 'Test Cache Access', 'wp-site-analyzer' ); ?></button>
                        </p>
                    <?php endif; ?>
                    
                    <h3 style="margin-top: 20px;"><?php esc_html_e( 'JavaScript Debug Logs', 'wp-site-analyzer' ); ?></h3>
                    <p><?php esc_html_e( 'Debug logs are stored in your browser and persist across page refreshes.', 'wp-site-analyzer' ); ?></p>
                    <div id="debug-log-viewer" style="background: #f0f0f0; padding: 10px; border-radius: 3px; max-height: 300px; overflow-y: auto; font-family: monospace; font-size: 12px; margin: 10px 0;">
                        <div style="color: #666;"><?php esc_html_e( 'Loading debug logs...', 'wp-site-analyzer' ); ?></div>
                    </div>
                    <p>
                        <button class="button" onclick="wpSiteAnalyzerDebug.refreshLogs()"><?php esc_html_e( 'Refresh Logs', 'wp-site-analyzer' ); ?></button>
                        <button class="button" onclick="wpSiteAnalyzerDebug.clearLogs()"><?php esc_html_e( 'Clear Logs', 'wp-site-analyzer' ); ?></button>
                        <button class="button" onclick="wpSiteAnalyzerDebug.exportLogs()"><?php esc_html_e( 'Export Logs', 'wp-site-analyzer' ); ?></button>
                    </p>
                </div>
                
                <script>
                window.wpSiteAnalyzerDebug = {
                    clearCache: function() {
                        if (!confirm('Clear all cached scan results?')) return;
                        
                        jQuery.post(wpSiteAnalyzer.ajax_url, {
                            action: 'wp_site_analyzer_clear_cache',
                            nonce: wpSiteAnalyzer.nonce
                        }, function(response) {
                            console.log('Clear cache response:', response);
                            alert(response.success ? 'Cache cleared!' : 'Failed to clear cache');
                            location.reload();
                        });
                    },
                    testCache: function() {
                        jQuery.post(wpSiteAnalyzer.ajax_url, {
                            action: 'wp_site_analyzer_test_cache',
                            nonce: wpSiteAnalyzer.nonce
                        }, function(response) {
                            console.log('Cache test response:', response);
                            alert('Check browser console for cache test results');
                        });
                    },
                    refreshLogs: function() {
                        var logs = JSON.parse(localStorage.getItem('wp_site_analyzer_debug') || '[]');
                        var viewer = document.getElementById('debug-log-viewer');
                        
                        if (logs.length === 0) {
                            viewer.innerHTML = '<div style="color: #666;">No debug logs found.</div>';
                            return;
                        }
                        
                        var html = '';
                        logs.forEach(function(log) {
                            var time = new Date(log.time).toLocaleTimeString();
                            var dataStr = log.data ? ' - ' + JSON.stringify(log.data) : '';
                            var color = log.message.includes('error') || log.message.includes('failed') ? '#d00' : '#333';
                            html += '<div style="margin: 2px 0; color: ' + color + ';">' + time + ' - ' + log.message + dataStr + '</div>';
                        });
                        
                        viewer.innerHTML = html;
                        viewer.scrollTop = viewer.scrollHeight;
                    },
                    clearLogs: function() {
                        if (!confirm('Clear all debug logs?')) return;
                        wpSiteAnalyzerClearLogs();
                        this.refreshLogs();
                        alert('Debug logs cleared!');
                    },
                    exportLogs: function() {
                        var logs = JSON.parse(localStorage.getItem('wp_site_analyzer_debug') || '[]');
                        var text = 'WP Site Analyzer Debug Logs\n';
                        text += '==========================\n\n';
                        
                        logs.forEach(function(log) {
                            text += log.time + ' - ' + log.message;
                            if (log.data) {
                                text += '\n' + JSON.stringify(log.data, null, 2);
                            }
                            text += '\n\n';
                        });
                        
                        var blob = new Blob([text], { type: 'text/plain' });
                        var url = URL.createObjectURL(blob);
                        var a = document.createElement('a');
                        a.href = url;
                        a.download = 'wp-site-analyzer-debug-' + new Date().toISOString().split('T')[0] + '.log';
                        document.body.appendChild(a);
                        a.click();
                        document.body.removeChild(a);
                        URL.revokeObjectURL(url);
                    }
                };
                
                // Auto-refresh logs on page load
                jQuery(document).ready(function() {
                    if (window.wpSiteAnalyzerDebug && window.wpSiteAnalyzerDebug.refreshLogs) {
                        wpSiteAnalyzerDebug.refreshLogs();
                    }
                });
                </script>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }


    /**
     * Render settings page
     */
    public function render_settings_page() {
        // Handle form submission
        if ( isset( $_POST['submit'] ) && check_admin_referer( 'wp_site_analyzer_settings' ) ) {
            $this->save_settings();
            ?>
            <div class="notice notice-success is-dismissible">
                <p><?php esc_html_e( 'Settings saved successfully!', 'wp-site-analyzer' ); ?></p>
            </div>
            <?php
        }
        
        $settings = get_option( 'wp_site_analyzer_settings' );
        
        ?>
        <div class="wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
            
            <form method="post" action="">
                <?php wp_nonce_field( 'wp_site_analyzer_settings' ); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="enable_caching"><?php esc_html_e( 'Enable Caching', 'wp-site-analyzer' ); ?></label>
                        </th>
                        <td>
                            <input type="checkbox" id="enable_caching" name="settings[enable_caching]" value="1" <?php checked( ! empty( $settings['enable_caching'] ) ); ?> />
                            <p class="description"><?php esc_html_e( 'Cache scan results to improve performance.', 'wp-site-analyzer' ); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="cache_duration"><?php esc_html_e( 'Cache Duration', 'wp-site-analyzer' ); ?></label>
                        </th>
                        <td>
                            <input type="number" id="cache_duration" name="settings[cache_duration]" value="<?php echo esc_attr( $settings['cache_duration'] ?? 3600 ); ?>" min="300" step="300" />
                            <p class="description"><?php esc_html_e( 'Cache duration in seconds (minimum 300).', 'wp-site-analyzer' ); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="batch_size"><?php esc_html_e( 'Batch Size', 'wp-site-analyzer' ); ?></label>
                        </th>
                        <td>
                            <input type="number" id="batch_size" name="settings[batch_size]" value="<?php echo esc_attr( $settings['batch_size'] ?? 100 ); ?>" min="10" max="1000" />
                            <p class="description"><?php esc_html_e( 'Number of items to process in each batch.', 'wp-site-analyzer' ); ?></p>
                        </td>
                    </tr>
                </table>
                
                <h2><?php esc_html_e( 'GitHub Updates', 'wp-site-analyzer' ); ?></h2>
                <p><?php esc_html_e( 'Configure automatic updates from your GitHub repository.', 'wp-site-analyzer' ); ?></p>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="github_repo"><?php esc_html_e( 'GitHub Repository', 'wp-site-analyzer' ); ?></label>
                        </th>
                        <td>
                            <input type="text" id="github_repo" name="settings[github_repo]" value="<?php echo esc_attr( $settings['github_repo'] ?? '' ); ?>" class="regular-text" placeholder="username/repository" />
                            <p class="description"><?php esc_html_e( 'Enter your GitHub repository in the format: username/repository', 'wp-site-analyzer' ); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="github_token"><?php esc_html_e( 'GitHub Access Token', 'wp-site-analyzer' ); ?></label>
                        </th>
                        <td>
                            <input type="password" id="github_token" name="settings[github_token]" value="<?php echo esc_attr( $settings['github_token'] ?? '' ); ?>" class="regular-text" />
                            <p class="description"><?php esc_html_e( 'Optional: Enter a GitHub personal access token for private repositories. Leave empty for public repositories.', 'wp-site-analyzer' ); ?></p>
                            <p class="description">
                                <a href="https://github.com/settings/tokens" target="_blank"><?php esc_html_e( 'Create a personal access token', 'wp-site-analyzer' ); ?></a>
                            </p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Save settings
     */
    private function save_settings() {
        $settings = $_POST['settings'] ?? array();
        
        // Sanitize settings
        $sanitized = array(
            'enable_caching' => ! empty( $settings['enable_caching'] ),
            'cache_duration' => max( 300, intval( $settings['cache_duration'] ?? 3600 ) ),
            'batch_size' => max( 10, min( 1000, intval( $settings['batch_size'] ?? 100 ) ) ),
            'github_repo' => sanitize_text_field( $settings['github_repo'] ?? '' ),
            'github_token' => sanitize_text_field( $settings['github_token'] ?? '' ),
        );
        
        update_option( 'wp_site_analyzer_settings', $sanitized );
    }

    /**
     * Handle AJAX scan request
     */
    public function handle_ajax_scan() {
        // This is handled in the main plugin class
        // We just need to ensure it's registered
    }

    /**
     * Handle AJAX export request
     */
    public function handle_ajax_export() {
        // Verify nonce
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'wp_site_analyzer_scan' ) ) {
            wp_send_json_error( __( 'Security verification failed. Please refresh the page and try again.', 'wp-site-analyzer' ) );
        }

        // Check permissions
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'You do not have permission to perform this action.', 'wp-site-analyzer' ) );
        }

        $format = sanitize_text_field( $_POST['format'] ?? 'json' );
        $cache_handler = new WP_Site_Analyzer_Cache_Handler();
        $results = $cache_handler->get( 'scan_results' );

        if ( ! $results ) {
            wp_send_json_error( __( 'No scan results available', 'wp-site-analyzer' ) );
        }

        // Format based on requested type
        switch ( $format ) {
            case 'markdown':
                $formatter = new WP_Site_Analyzer_Markdown_Formatter();
                $output = $formatter->format( $results );
                $filename = 'wp-site-analysis-' . date( 'Y-m-d' ) . '.md';
                $content_type = 'text/markdown';
                break;
                
            case 'ai_optimized':
                $formatter = new WP_Site_Analyzer_AI_Formatter();
                $output = $formatter->format( $results );
                $filename = 'wp-site-analysis-ai-' . date( 'Y-m-d' ) . '.json';
                $content_type = 'application/json';
                break;
                
            default:
                $output = json_encode( $results, JSON_PRETTY_PRINT );
                $filename = 'wp-site-analysis-' . date( 'Y-m-d' ) . '.json';
                $content_type = 'application/json';
        }

        // Send download headers
        header( 'Content-Type: ' . $content_type );
        header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
        header( 'Content-Length: ' . strlen( $output ) );
        echo $output;
        exit;
    }

    /**
     * Handle AJAX progress request
     */
    public function handle_ajax_get_progress() {
        // Verify nonce
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'wp_site_analyzer_scan' ) ) {
            wp_send_json_error( __( 'Security check failed', 'wp-site-analyzer' ) );
        }
        
        // Check capabilities
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'Insufficient permissions', 'wp-site-analyzer' ) );
        }
        
        $progress = get_option( 'wp_site_analyzer_scan_progress', array(
            'status' => 'idle',
            'current' => 0,
            'total' => 0,
            'message' => '',
        ) );

        wp_send_json_success( $progress );
    }

    /**
     * Render AI Report page
     */
    public function render_ai_report_page() {
        $cache_handler = new WP_Site_Analyzer_Cache_Handler();
        $results = $cache_handler->get( 'scan_results' );
        
        // Debug logging
        error_log( 'WP Site Analyzer: AI Report - Cache results: ' . ( $results ? 'Found' : 'Not found' ) );
        if ( $results ) {
            error_log( 'WP Site Analyzer: AI Report - Results structure: ' . json_encode( array_keys( $results ) ) );
            if ( isset( $results['results'] ) ) {
                error_log( 'WP Site Analyzer: AI Report - Scanner results: ' . json_encode( array_keys( $results['results'] ) ) );
            }
        }
        
        if ( ! $results || ! isset( $results['results'] ) ) {
            ?>
            <div class="wrap">
                <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
                <p><?php esc_html_e( 'No scan results available. Please run a scan first.', 'wp-site-analyzer' ); ?></p>
                <a href="<?php echo admin_url( 'admin.php?page=wp-site-analyzer' ); ?>" class="button button-primary">
                    <?php esc_html_e( 'Go to Dashboard', 'wp-site-analyzer' ); ?>
                </a>
                
                <?php if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) : ?>
                <div style="margin-top: 20px; padding: 10px; background: #fff3cd; border: 1px solid #ffeaa7;">
                    <h3>Debug Information:</h3>
                    <pre><?php 
                    echo 'Cache Key: wp_site_analyzer_cache_scan_results\n';
                    echo 'Cache Result: ' . ( $results ? 'Data found' : 'No data' ) . '\n';
                    echo 'Results Structure: ' . json_encode( $results ? array_keys( $results ) : 'null', JSON_PRETTY_PRINT );
                    ?></pre>
                </div>
                <?php endif; ?>
            </div>
            <?php
            return;
        }

        // Generate the markdown report
        $markdown_formatter = new WP_Site_Analyzer_Markdown_Formatter();
        // Pass the results array directly, not the wrapper
        $markdown_content = $markdown_formatter->format( $results['results'] );
        
        // Convert markdown to HTML
        $html_content = $this->markdown_to_html( $markdown_content );
        
        ?>
        <div class="wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
            
            <div class="wp-site-analyzer-ai-report">
                <div class="report-actions">
                    <button class="button button-primary" id="copy-report">
                        <?php esc_html_e( 'Copy Report to Clipboard', 'wp-site-analyzer' ); ?>
                    </button>
                    
                    <button class="button" id="select-all-report">
                        <?php esc_html_e( 'Select All', 'wp-site-analyzer' ); ?>
                    </button>
                    
                    <button class="button" id="download-markdown">
                        <?php esc_html_e( 'Download as Markdown', 'wp-site-analyzer' ); ?>
                    </button>
                </div>
                
                <div id="report-content" class="report-content">
                    <?php echo wp_kses_post( $html_content ); ?>
                </div>
                
                <!-- Hidden textarea for copying markdown -->
                <textarea id="markdown-source" style="display: none;"><?php echo esc_textarea( $markdown_content ); ?></textarea>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Copy report to clipboard
            $('#copy-report').on('click', function() {
                var markdownSource = $('#markdown-source').val();
                var $button = $(this);
                var originalText = $button.html();
                
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(markdownSource).then(function() {
                        showCopySuccess($button, originalText);
                    }).catch(function(err) {
                        console.error('Failed to copy:', err);
                        fallbackCopy();
                    });
                } else {
                    fallbackCopy();
                }
                
                function fallbackCopy() {
                    var $temp = $('<textarea>');
                    $('body').append($temp);
                    $temp.val(markdownSource).select();
                    document.execCommand('copy');
                    $temp.remove();
                    showCopySuccess($button, originalText);
                }
                
                function showCopySuccess($btn, original) {
                    $btn.html('<?php esc_html_e( '✓ Copied!', 'wp-site-analyzer' ); ?>');
                    setTimeout(function() {
                        $btn.html(original);
                    }, 2000);
                }
            });
            
            // Select all content
            $('#select-all-report').on('click', function() {
                var range = document.createRange();
                range.selectNodeContents(document.getElementById('report-content'));
                var selection = window.getSelection();
                selection.removeAllRanges();
                selection.addRange(range);
            });
            
            // Download markdown
            $('#download-markdown').on('click', function() {
                var content = $('#markdown-source').val();
                var blob = new Blob([content], { type: 'text/markdown' });
                var url = URL.createObjectURL(blob);
                var a = document.createElement('a');
                a.href = url;
                a.download = 'wp-site-analysis-' + new Date().toISOString().split('T')[0] + '.md';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);
            });
            
            // Smooth scrolling for anchor links
            $(document).on('click', 'a.anchor-link', function(e) {
                e.preventDefault();
                var target = $(this).attr('href');
                if (target && target.startsWith('#')) {
                    var $target = $(target);
                    if ($target.length) {
                        $('html, body').animate({
                            scrollTop: $target.offset().top - 100
                        }, 500);
                    }
                }
            });
            
            // Highlight current section in table of contents
            var sections = $('.section-header');
            var toc = $('.ai-report-content > ul:first-of-type');
            
            if (toc.length && sections.length) {
                $(window).scroll(function() {
                    var scrollPos = $(this).scrollTop() + 150;
                    
                    sections.each(function() {
                        var $section = $(this);
                        var sectionTop = $section.offset().top;
                        var sectionId = $section.attr('id');
                        
                        if (scrollPos >= sectionTop) {
                            toc.find('a').removeClass('active');
                            toc.find('a[href="#' + sectionId + '"]').addClass('active');
                        }
                    });
                });
            }
        });
        </script>
        
        <style>
        .ai-report-content > ul:first-of-type a.active {
            color: #0a58ca;
            font-weight: 600;
        }
        
        /* Remove dashicons from buttons in report actions */
        .report-actions .button .dashicons {
            display: none !important;
        }
        </style>
        <?php
    }

    /**
     * Convert markdown to HTML
     *
     * @param string $markdown Markdown content
     * @return string HTML content
     */
    private function markdown_to_html( $markdown ) {
        // Split into lines for better processing
        $lines = explode("\n", $markdown);
        $html_lines = array();
        $in_code_block = false;
        $in_table = false;
        $in_list = false;
        $list_stack = array();
        
        foreach ($lines as $i => $line) {
            // Handle code blocks
            if (preg_match('/^```(.*)$/', $line, $matches)) {
                if (!$in_code_block) {
                    // Close any open lists before code blocks
                    while (!empty($list_stack)) {
                        $closed_type = array_pop($list_stack);
                        $html_lines[] = '</' . $closed_type . '>';
                    }
                    $in_code_block = true;
                    $language = $matches[1] ?: 'plaintext';
                    $html_lines[] = '<pre class="code-block" data-language="' . esc_attr($language) . '"><code>';
                } else {
                    $in_code_block = false;
                    $html_lines[] = '</code></pre>';
                }
                continue;
            }
            
            // If in code block, don't process
            if ($in_code_block) {
                $html_lines[] = esc_html($line);
                continue;
            }
            
            // Handle horizontal rules
            if (preg_match('/^---+$/', $line)) {
                // Close any open lists before horizontal rules
                while (!empty($list_stack)) {
                    $closed_type = array_pop($list_stack);
                    $html_lines[] = '</' . $closed_type . '>';
                }
                $html_lines[] = '<hr class="section-divider">';
                continue;
            }
            
            // Handle headers with ID anchors
            if (preg_match('/^(#{1,6})\s+(.+?)(?:\s*\{#(.+?)\})?$/', $line, $matches)) {
                // Close any open lists before headers
                while (!empty($list_stack)) {
                    $closed_type = array_pop($list_stack);
                    $html_lines[] = '</' . $closed_type . '>';
                }
                
                $level = strlen($matches[1]);
                $text = $matches[2];
                $id = isset($matches[3]) ? $matches[3] : $this->create_slug($text);
                
                $html_lines[] = '<h' . $level . ' id="' . esc_attr($id) . '" class="section-header level-' . $level . '">' 
                    . $this->convert_inline_markdown($text) . '</h' . $level . '>';
                continue;
            }
            
            // Handle tables
            if (preg_match('/^\|(.+)\|$/', $line)) {
                // Close any open lists before tables
                while (!empty($list_stack)) {
                    $closed_type = array_pop($list_stack);
                    $html_lines[] = '</' . $closed_type . '>';
                }
                
                if (!$in_table) {
                    $in_table = true;
                    $html_lines[] = '<div class="table-wrapper"><table class="data-table">';
                }
                
                // Check if this is a separator line
                if (preg_match('/^\|[\s\-:|]+\|$/', $line)) {
                    continue;
                }
                
                $cells = array_map('trim', explode('|', trim($line, '|')));
                $tag = (count($html_lines) > 0 && strpos(end($html_lines), '<table') !== false) ? 'th' : 'td';
                
                $html_lines[] = '<tr>';
                foreach ($cells as $cell) {
                    $html_lines[] = '<' . $tag . '>' . $this->convert_inline_markdown($cell) . '</' . $tag . '>';
                }
                $html_lines[] = '</tr>';
                
                // Check if next line is not a table
                if (!isset($lines[$i + 1]) || !preg_match('/^\|(.+)\|$/', $lines[$i + 1])) {
                    $in_table = false;
                    $html_lines[] = '</table></div>';
                }
                continue;
            }
            
            // Handle lists
            if (preg_match('/^(\s*)([-*+]|\d+\.)\s+(.+)$/', $line, $matches)) {
                $indent = strlen($matches[1]);
                $marker = $matches[2];
                $content = $matches[3];
                $is_ordered = preg_match('/^\d+\./', $marker);
                
                // Calculate list level
                $level = floor($indent / 2);
                
                // Close deeper levels
                while (count($list_stack) > $level + 1) {
                    $closed_type = array_pop($list_stack);
                    $html_lines[] = '</' . $closed_type . '>';
                }
                
                // Open new list if needed
                if (count($list_stack) <= $level || (count($list_stack) > $level && $list_stack[$level] !== ($is_ordered ? 'ol' : 'ul'))) {
                    // Close current list at this level if type differs
                    if (count($list_stack) > $level) {
                        $html_lines[] = '</' . $list_stack[$level] . '>';
                        $list_stack[$level] = $is_ordered ? 'ol' : 'ul';
                    } else {
                        $list_stack[] = $is_ordered ? 'ol' : 'ul';
                    }
                    $html_lines[] = '<' . ($is_ordered ? 'ol' : 'ul') . ' class="styled-list">';
                }
                
                $html_lines[] = '<li>' . $this->convert_inline_markdown($content) . '</li>';
                continue;
            }
            
            // No need for this block anymore since we handle list closing before headers, tables, and HRs
            
            // Handle blockquotes
            if (preg_match('/^>\s*(.*)$/', $line, $matches)) {
                // Close any open lists before blockquotes
                while (!empty($list_stack)) {
                    $closed_type = array_pop($list_stack);
                    $html_lines[] = '</' . $closed_type . '>';
                }
                $html_lines[] = '<blockquote>' . $this->convert_inline_markdown($matches[1]) . '</blockquote>';
                continue;
            }
            
            // Handle paragraphs
            if (!empty(trim($line))) {
                // Check if this line starts with enough spaces to be a list continuation
                $is_list_continuation = false;
                if (!empty($list_stack) && preg_match('/^(\s{2,})/', $line)) {
                    $is_list_continuation = true;
                }
                
                if (!$is_list_continuation) {
                    // Close any open lists before paragraphs
                    while (!empty($list_stack)) {
                        $closed_type = array_pop($list_stack);
                        $html_lines[] = '</' . $closed_type . '>';
                    }
                    $html_lines[] = '<p>' . $this->convert_inline_markdown($line) . '</p>';
                } else {
                    // This is a continuation of a list item
                    $html_lines[] = $this->convert_inline_markdown(trim($line));
                }
            } else {
                // Empty line - only close lists if we're at the end or next line is a block element
                $should_close = false;
                
                // Check if next line exists and is a block element
                if (isset($lines[$i+1])) {
                    $next_line = $lines[$i+1];
                    // Check for headers, HRs, tables, blockquotes, or code blocks
                    if (preg_match('/^(#{1,6}\s|---+$|^\||^>|^```)/', $next_line)) {
                        $should_close = true;
                    }
                    // Check for two consecutive empty lines
                    else if (empty(trim($next_line))) {
                        $should_close = true;
                    }
                } else {
                    // We're at the end of the document
                    $should_close = true;
                }
                
                if ($should_close && !empty($list_stack)) {
                    while (!empty($list_stack)) {
                        $closed_type = array_pop($list_stack);
                        $html_lines[] = '</' . $closed_type . '>';
                    }
                }
                $html_lines[] = '';
            }
        }
        
        // Close any open lists
        while (!empty($list_stack)) {
            $closed_type = array_pop($list_stack);
            $html_lines[] = '</' . $closed_type . '>';
        }
        
        // Join all lines
        $html = implode("\n", $html_lines);
        
        // First, handle <details> and <summary> markdown syntax if present
        $html = preg_replace('/^<details>$/m', '<details class="collapsible-section">', $html);
        $html = preg_replace('/^<\/details>$/m', '</details>', $html);
        $html = preg_replace('/^<summary>(.*?)<\/summary>$/m', '<summary class="collapsible-header">$1</summary>', $html);
        
        // Then handle nested details/summary blocks
        $html = preg_replace_callback('/<details class="collapsible-section">(.*?)<\/details>/s', function($matches) {
            $content = $matches[1];
            // Ensure content after summary is wrapped
            $content = preg_replace('/<\/summary>\s*(.+)/s', '</summary><div class="collapsible-content">$1</div>', $content);
            return '<details class="collapsible-section">' . $content . '</details>';
        }, $html);
        
        // Wrap in container
        $html = '<article class="ai-report-content">' . $html . '</article>';
        
        return $html;
    }
    
    /**
     * Convert inline markdown elements
     *
     * @param string $text Text to convert
     * @return string Converted text
     */
    private function convert_inline_markdown( $text ) {
        // Escape HTML first
        $text = esc_html( $text );
        
        // Convert code spans FIRST (before other formatting)
        $text = preg_replace( '/`([^`]+)`/', '<code class="inline-code">$1</code>', $text );
        
        // Convert bold
        $text = preg_replace( '/\*\*(.+?)\*\*/', '<strong>$1</strong>', $text );
        
        // Convert italic (but not lists)
        $text = preg_replace( '/(?<!\*)\*([^\*]+)\*(?!\*)/', '<em>$1</em>', $text );
        
        // Convert links with special handling for anchors
        $text = preg_replace_callback( '/\[([^\]]+)\]\(([^)]+)\)/', function($matches) {
            $link_text = $matches[1];
            $url = $matches[2];
            
            // Check if it's an anchor link
            if (strpos($url, '#') === 0) {
                return '<a href="' . esc_attr($url) . '" class="anchor-link">' . $link_text . '</a>';
            }
            
            return '<a href="' . esc_url($url) . '" target="_blank" rel="noopener">' . $link_text . '</a>';
        }, $text );
        
        return $text;
    }
    
    /**
     * Create a slug from text
     *
     * @param string $text Text to slugify
     * @return string Slug
     */
    private function create_slug( $text ) {
        $slug = strtolower( $text );
        $slug = preg_replace( '/[^a-z0-9]+/', '-', $slug );
        $slug = trim( $slug, '-' );
        return $slug;
    }
    
    /**
     * Handle AJAX clear cache request
     */
    public function handle_ajax_clear_cache() {
        // Verify nonce
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'wp_site_analyzer_scan' ) ) {
            wp_send_json_error( __( 'Security verification failed.', 'wp-site-analyzer' ) );
        }
        
        // Check permissions
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'Insufficient permissions.', 'wp-site-analyzer' ) );
        }
        
        // Clear cache
        $cache_handler = new WP_Site_Analyzer_Cache_Handler();
        $cache_handler->delete( 'scan_results' );
        
        // Clear progress
        delete_option( 'wp_site_analyzer_scan_progress' );
        delete_option( 'wp_site_analyzer_last_scan' );
        
        wp_send_json_success( array( 'message' => 'Cache cleared successfully' ) );
    }
    
    /**
     * Handle AJAX test cache request
     */
    public function handle_ajax_test_cache() {
        // Verify nonce
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'wp_site_analyzer_scan' ) ) {
            wp_send_json_error( __( 'Security verification failed.', 'wp-site-analyzer' ) );
        }
        
        // Check permissions
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'Insufficient permissions.', 'wp-site-analyzer' ) );
        }
        
        // Test cache
        $cache_handler = new WP_Site_Analyzer_Cache_Handler();
        
        // Get cached results
        $cached_results = $cache_handler->get( 'scan_results' );
        
        // Test transient directly
        $transient_key = 'wp_site_analyzer_cache_scan_results';
        $transient_value = get_transient( $transient_key );
        
        // Test options
        $last_scan = get_option( 'wp_site_analyzer_last_scan' );
        $scan_progress = get_option( 'wp_site_analyzer_scan_progress' );
        
        $debug_info = array(
            'cache_handler_result' => $cached_results ? 'Found cached results' : 'No cached results',
            'transient_exists' => $transient_value !== false,
            'transient_key' => $transient_key,
            'cached_data_structure' => $cached_results ? array_keys( $cached_results ) : null,
            'results_exist' => isset( $cached_results['results'] ),
            'results_count' => isset( $cached_results['results'] ) ? count( $cached_results['results'] ) : 0,
            'last_scan' => $last_scan,
            'scan_progress' => $scan_progress,
            'current_time' => current_time( 'mysql' ),
        );
        
        wp_send_json_success( $debug_info );
    }
}