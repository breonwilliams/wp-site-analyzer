<?php
/**
 * Custom Fields Scanner
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
 * Class WP_Site_Analyzer_Custom_Fields_Scanner
 *
 * Scans and analyzes custom fields across the site
 */
class WP_Site_Analyzer_Custom_Fields_Scanner extends WP_Site_Analyzer_Scanner_Base {

    /**
     * Perform the scan
     *
     * @return array Scan results
     */
    public function scan() {
        $this->start_performance_monitor( 'custom_fields_scan' );

        $results = array(
            'summary' => array(
                'total_meta_keys' => 0,
                'post_meta_keys' => 0,
                'term_meta_keys' => 0,
                'user_meta_keys' => 0,
            ),
            'post_meta' => $this->scan_post_meta(),
            'registered_meta' => $this->scan_registered_meta(),
            'acf_fields' => $this->scan_acf_fields(),
            'performance_metrics' => $this->end_performance_monitor( 'custom_fields_scan' ),
        );

        return $results;
    }

    /**
     * Scan post meta fields
     *
     * @return array Post meta data
     */
    private function scan_post_meta() {
        global $wpdb;

        $meta_keys = $wpdb->get_col( "
            SELECT DISTINCT meta_key 
            FROM {$wpdb->postmeta} 
            WHERE meta_key NOT LIKE '\_%' 
            ORDER BY meta_key 
            LIMIT 100
        " );

        return array(
            'keys' => $meta_keys,
            'count' => count( $meta_keys ),
        );
    }

    /**
     * Scan registered meta
     *
     * @return array Registered meta
     */
    private function scan_registered_meta() {
        $registered = array(
            'post' => get_registered_meta_keys( 'post' ),
            'term' => get_registered_meta_keys( 'term' ),
            'user' => get_registered_meta_keys( 'user' ),
            'comment' => get_registered_meta_keys( 'comment' ),
        );

        return $registered;
    }

    /**
     * Scan ACF fields if available
     *
     * @return array ACF fields data
     */
    private function scan_acf_fields() {
        if ( ! function_exists( 'acf_get_field_groups' ) ) {
            return array( 'available' => false );
        }

        $field_groups = acf_get_field_groups();
        $acf_data = array(
            'available' => true,
            'field_groups' => count( $field_groups ),
            'groups' => array(),
        );

        foreach ( $field_groups as $group ) {
            $fields = acf_get_fields( $group['key'] );
            $acf_data['groups'][] = array(
                'title' => $group['title'],
                'key' => $group['key'],
                'location' => $group['location'],
                'field_count' => count( $fields ),
            );
        }

        return $acf_data;
    }
}