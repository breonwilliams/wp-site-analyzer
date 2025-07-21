<?php
/**
 * Database Scanner
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
 * Class WP_Site_Analyzer_Database_Scanner
 *
 * Scans database structure and custom tables
 */
class WP_Site_Analyzer_Database_Scanner extends WP_Site_Analyzer_Scanner_Base {

    /**
     * Perform the scan
     *
     * @return array Scan results
     */
    public function scan() {
        $this->start_performance_monitor( 'database_scan' );

        $results = array(
            'tables' => $this->scan_database_tables(),
            'custom_tables' => $this->identify_custom_tables(),
            'table_sizes' => $this->get_table_sizes(),
            'performance_metrics' => $this->end_performance_monitor( 'database_scan' ),
        );

        return $results;
    }

    /**
     * Scan database tables
     *
     * @return array Database tables
     */
    private function scan_database_tables() {
        global $wpdb;

        $tables = $wpdb->get_col( "SHOW TABLES" );
        $wp_tables = array();
        $custom_tables = array();

        foreach ( $tables as $table ) {
            if ( strpos( $table, $wpdb->prefix ) === 0 ) {
                $wp_tables[] = $table;
            } else {
                $custom_tables[] = $table;
            }
        }

        return array(
            'wordpress_tables' => $wp_tables,
            'custom_tables' => $custom_tables,
            'total_tables' => count( $tables ),
        );
    }

    /**
     * Identify custom tables
     *
     * @return array Custom tables analysis
     */
    private function identify_custom_tables() {
        global $wpdb;

        $core_tables = array(
            $wpdb->posts, $wpdb->postmeta, $wpdb->comments, $wpdb->commentmeta,
            $wpdb->terms, $wpdb->term_taxonomy, $wpdb->term_relationships, $wpdb->termmeta,
            $wpdb->users, $wpdb->usermeta, $wpdb->options, $wpdb->links,
        );

        $all_tables = $wpdb->get_col( "SHOW TABLES LIKE '{$wpdb->prefix}%'" );
        $custom = array_diff( $all_tables, $core_tables );

        return array(
            'tables' => array_values( $custom ),
            'count' => count( $custom ),
        );
    }

    /**
     * Get table sizes
     *
     * @return array Table size information
     */
    private function get_table_sizes() {
        global $wpdb;

        $prefix_like = $wpdb->esc_like( $wpdb->prefix ) . '%';
        $tables = $wpdb->get_results( $wpdb->prepare( "
            SELECT 
                table_name AS 'name',
                ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'size_mb',
                table_rows AS 'rows'
            FROM information_schema.TABLES 
            WHERE table_schema = DATABASE()
            AND table_name LIKE %s
            ORDER BY (data_length + index_length) DESC
            LIMIT 20
        ", $prefix_like ) );

        return $tables;
    }
}