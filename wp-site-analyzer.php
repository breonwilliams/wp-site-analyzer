<?php
/**
 * Plugin Name: WP Site Analyzer
 * Plugin URI: https://github.com/yourusername/wp-site-analyzer
 * Description: Comprehensive WordPress site analysis tool that scans and documents all content types, taxonomies, custom fields, and site structure in an AI-friendly format.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://yourwebsite.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wp-site-analyzer
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define plugin constants
define( 'WP_SITE_ANALYZER_VERSION', '1.0.0' );
define( 'WP_SITE_ANALYZER_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WP_SITE_ANALYZER_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WP_SITE_ANALYZER_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'WP_SITE_ANALYZER_PLUGIN_FILE', __FILE__ );

// Autoloader
require_once WP_SITE_ANALYZER_PLUGIN_DIR . 'includes/class-wp-site-analyzer-autoloader.php';
WP_Site_Analyzer_Autoloader::register();

// Initialize the plugin
function wp_site_analyzer_init() {
    $plugin = WP_Site_Analyzer::get_instance();
    $plugin->run();
}

// Hook into WordPress
add_action( 'plugins_loaded', 'wp_site_analyzer_init' );

// Activation hook
register_activation_hook( __FILE__, array( 'WP_Site_Analyzer', 'activate' ) );

// Deactivation hook
register_deactivation_hook( __FILE__, array( 'WP_Site_Analyzer', 'deactivate' ) );

// Uninstall hook
register_uninstall_hook( __FILE__, array( 'WP_Site_Analyzer', 'uninstall' ) );