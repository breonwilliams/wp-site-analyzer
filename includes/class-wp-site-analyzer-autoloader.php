<?php
/**
 * Autoloader for WP Site Analyzer
 *
 * @package WP_Site_Analyzer
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class WP_Site_Analyzer_Autoloader
 *
 * Handles autoloading of plugin classes
 */
class WP_Site_Analyzer_Autoloader {

    /**
     * Register the autoloader
     */
    public static function register() {
        spl_autoload_register( array( __CLASS__, 'autoload' ) );
    }

    /**
     * Autoload classes
     *
     * @param string $class_name The class name to load
     */
    public static function autoload( $class_name ) {
        // Only autoload our plugin's classes
        if ( 0 !== strpos( $class_name, 'WP_Site_Analyzer' ) ) {
            return;
        }

        // Convert class name to file name
        $file_name = 'class-' . str_replace( '_', '-', strtolower( $class_name ) ) . '.php';

        // Define the paths to check
        $paths = array(
            WP_SITE_ANALYZER_PLUGIN_DIR . 'includes/',
            WP_SITE_ANALYZER_PLUGIN_DIR . 'includes/admin/',
            WP_SITE_ANALYZER_PLUGIN_DIR . 'includes/scanners/',
            WP_SITE_ANALYZER_PLUGIN_DIR . 'includes/formatters/',
            WP_SITE_ANALYZER_PLUGIN_DIR . 'includes/integrations/',
            WP_SITE_ANALYZER_PLUGIN_DIR . 'includes/utilities/',
        );

        // Check each path for the file
        foreach ( $paths as $path ) {
            $file_path = $path . $file_name;
            if ( file_exists( $file_path ) ) {
                require_once $file_path;
                return;
            }
        }
    }
}