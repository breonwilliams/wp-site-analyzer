<?php
/**
 * Theme Scanner
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
 * Class WP_Site_Analyzer_Theme_Scanner
 *
 * Scans theme configuration and features
 */
class WP_Site_Analyzer_Theme_Scanner extends WP_Site_Analyzer_Scanner_Base {

    /**
     * Perform the scan
     *
     * @return array Scan results
     */
    public function scan() {
        $this->start_performance_monitor( 'theme_scan' );

        $results = array(
            'active_theme' => $this->scan_active_theme(),
            'theme_support' => $this->scan_theme_support(),
            'template_files' => $this->scan_template_files(),
            'child_theme' => $this->scan_child_theme(),
            'performance_metrics' => $this->end_performance_monitor( 'theme_scan' ),
        );

        return $results;
    }

    /**
     * Scan active theme
     *
     * @return array Active theme data
     */
    private function scan_active_theme() {
        $theme = wp_get_theme();

        return array(
            'name' => $theme->get( 'Name' ),
            'version' => $theme->get( 'Version' ),
            'author' => $theme->get( 'Author' ),
            'description' => $theme->get( 'Description' ),
            'template' => $theme->get_template(),
            'stylesheet' => $theme->get_stylesheet(),
            'theme_uri' => $theme->get( 'ThemeURI' ),
            'text_domain' => $theme->get( 'TextDomain' ),
            'is_child_theme' => is_child_theme(),
        );
    }

    /**
     * Scan theme support features
     *
     * @return array Theme support data
     */
    private function scan_theme_support() {
        global $_wp_theme_features;

        $features = array();
        $check_features = array(
            'post-thumbnails', 'custom-logo', 'custom-header', 'custom-background',
            'post-formats', 'html5', 'title-tag', 'automatic-feed-links',
            'editor-style', 'align-wide', 'responsive-embeds', 'wp-block-styles',
        );

        foreach ( $check_features as $feature ) {
            if ( current_theme_supports( $feature ) ) {
                $features[ $feature ] = true;
                if ( isset( $_wp_theme_features[ $feature ] ) && is_array( $_wp_theme_features[ $feature ] ) ) {
                    $features[ $feature ] = $_wp_theme_features[ $feature ];
                }
            }
        }

        return $features;
    }

    /**
     * Scan template files
     *
     * @return array Template files data
     */
    private function scan_template_files() {
        $theme = wp_get_theme();
        $files = $theme->get_files( array( 'php' ) );

        $templates = array(
            'page_templates' => $theme->get_page_templates(),
            'template_count' => count( $files ),
            'key_templates' => array(),
        );

        // Check for key template files
        $key_files = array(
            'index.php', 'single.php', 'page.php', 'archive.php',
            'search.php', '404.php', 'header.php', 'footer.php',
            'functions.php', 'sidebar.php',
        );

        foreach ( $key_files as $file ) {
            if ( isset( $files[ $file ] ) ) {
                $templates['key_templates'][] = $file;
            }
        }

        return $templates;
    }

    /**
     * Scan child theme details
     *
     * @return array Child theme data
     */
    private function scan_child_theme() {
        if ( ! is_child_theme() ) {
            return array( 'is_child' => false );
        }

        $child = wp_get_theme();
        $parent = wp_get_theme( $child->get_template() );

        return array(
            'is_child' => true,
            'parent_theme' => array(
                'name' => $parent->get( 'Name' ),
                'version' => $parent->get( 'Version' ),
                'template' => $parent->get_template(),
            ),
            'child_modifications' => array(
                'functions_php' => file_exists( get_stylesheet_directory() . '/functions.php' ),
                'style_css' => file_exists( get_stylesheet_directory() . '/style.css' ),
            ),
        );
    }
}