<?php
/**
 * Plugin Scanner
 *
 * @package WP_Site_Analyzer
 * @subpackage Scanners
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class WP_Site_Analyzer_Plugin_Scanner
 *
 * Scans installed plugins and their configurations
 */
class WP_Site_Analyzer_Plugin_Scanner extends WP_Site_Analyzer_Scanner_Base {

    /**
     * Perform the scan
     *
     * @return array Scan results
     */
    public function scan() {
        $this->start_performance_monitor( 'plugin_scan' );

        $results = array(
            'active_plugins' => $this->scan_active_plugins(),
            'all_plugins' => $this->scan_all_plugins(),
            'must_use_plugins' => $this->scan_mu_plugins(),
            'plugin_dependencies' => $this->analyze_dependencies(),
            'performance_metrics' => $this->end_performance_monitor( 'plugin_scan' ),
        );

        return $results;
    }

    /**
     * Scan active plugins
     *
     * @return array Active plugins data
     */
    private function scan_active_plugins() {
        $active_plugins = get_option( 'active_plugins', array() );
        $plugins_data = array();

        foreach ( $active_plugins as $plugin ) {
            $plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin, false );
            $plugins_data[] = array(
                'file' => $plugin,
                'name' => $plugin_data['Name'],
                'version' => $plugin_data['Version'],
                'author' => $plugin_data['Author'],
                'description' => $plugin_data['Description'],
            );
        }

        return array(
            'count' => count( $active_plugins ),
            'plugins' => $plugins_data,
        );
    }

    /**
     * Scan all installed plugins
     *
     * @return array All plugins data
     */
    private function scan_all_plugins() {
        $all_plugins = get_plugins();
        $active_plugins = get_option( 'active_plugins', array() );

        $categorized = array(
            'active' => array(),
            'inactive' => array(),
        );

        foreach ( $all_plugins as $plugin_file => $plugin_data ) {
            $plugin_info = array(
                'file' => $plugin_file,
                'name' => $plugin_data['Name'],
                'version' => $plugin_data['Version'],
            );

            if ( in_array( $plugin_file, $active_plugins ) ) {
                $categorized['active'][] = $plugin_info;
            } else {
                $categorized['inactive'][] = $plugin_info;
            }
        }

        return array(
            'total' => count( $all_plugins ),
            'categorized' => $categorized,
        );
    }

    /**
     * Scan must-use plugins
     *
     * @return array Must-use plugins data
     */
    private function scan_mu_plugins() {
        $mu_plugins = get_mu_plugins();

        return array(
            'count' => count( $mu_plugins ),
            'plugins' => array_map( function( $plugin ) {
                return array(
                    'name' => $plugin['Name'],
                    'version' => $plugin['Version'],
                );
            }, $mu_plugins ),
        );
    }

    /**
     * Analyze plugin dependencies
     *
     * @return array Dependencies analysis
     */
    private function analyze_dependencies() {
        $known_dependencies = array(
            'woocommerce' => array( 'e-commerce', 'products', 'orders' ),
            'elementor' => array( 'page-builder', 'visual-editor' ),
            'acf' => array( 'custom-fields', 'meta-boxes' ),
            'yoast' => array( 'seo', 'meta-tags' ),
        );

        $active_plugins = get_option( 'active_plugins', array() );
        $detected = array();

        foreach ( $active_plugins as $plugin ) {
            foreach ( $known_dependencies as $key => $features ) {
                if ( strpos( $plugin, $key ) !== false ) {
                    $detected[ $key ] = $features;
                }
            }
        }

        return $detected;
    }
}