<?php
/**
 * Security Scanner
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
 * Class WP_Site_Analyzer_Security_Scanner
 *
 * Scans security-related configurations
 */
class WP_Site_Analyzer_Security_Scanner extends WP_Site_Analyzer_Scanner_Base {

    /**
     * Perform the scan
     *
     * @return array Scan results
     */
    public function scan() {
        $this->start_performance_monitor( 'security_scan' );

        $results = array(
            'user_roles' => $this->scan_user_roles(),
            'capabilities' => $this->scan_capabilities(),
            'file_permissions' => $this->check_file_permissions(),
            'security_headers' => $this->check_security_headers(),
            'performance_metrics' => $this->end_performance_monitor( 'security_scan' ),
        );

        return $results;
    }

    /**
     * Scan user roles
     *
     * @return array User roles data
     */
    private function scan_user_roles() {
        global $wp_roles;

        $roles_data = array();
        foreach ( $wp_roles->roles as $role_key => $role ) {
            $roles_data[ $role_key ] = array(
                'name' => $role['name'],
                'capabilities' => array_keys( array_filter( $role['capabilities'] ) ),
                'user_count' => count( get_users( array( 'role' => $role_key ) ) ),
            );
        }

        return array(
            'total_roles' => count( $wp_roles->roles ),
            'roles' => $roles_data,
        );
    }

    /**
     * Scan custom capabilities
     *
     * @return array Custom capabilities
     */
    private function scan_capabilities() {
        global $wp_roles;

        $all_caps = array();
        foreach ( $wp_roles->roles as $role ) {
            $all_caps = array_merge( $all_caps, array_keys( $role['capabilities'] ) );
        }

        $all_caps = array_unique( $all_caps );
        $default_caps = array(
            'switch_themes', 'edit_themes', 'activate_plugins', 'edit_plugins',
            'edit_users', 'edit_files', 'manage_options', 'moderate_comments',
            'manage_categories', 'manage_links', 'upload_files', 'unfiltered_html',
            'edit_posts', 'edit_others_posts', 'edit_published_posts', 'publish_posts',
            'edit_pages', 'read', 'edit_others_pages', 'edit_published_pages',
            'publish_pages', 'delete_pages', 'delete_others_pages', 'delete_published_pages',
            'delete_posts', 'delete_others_posts', 'delete_published_posts', 'delete_private_posts',
            'edit_private_posts', 'read_private_posts', 'delete_private_pages',
            'edit_private_pages', 'read_private_pages',
        );

        $custom_caps = array_diff( $all_caps, $default_caps );

        return array(
            'total_capabilities' => count( $all_caps ),
            'custom_capabilities' => array_values( $custom_caps ),
        );
    }

    /**
     * Check file permissions
     *
     * @return array File permissions data
     */
    private function check_file_permissions() {
        $critical_files = array(
            'wp-config.php' => ABSPATH . 'wp-config.php',
            '.htaccess' => ABSPATH . '.htaccess',
            'wp-content' => WP_CONTENT_DIR,
            'uploads' => wp_upload_dir()['basedir'],
        );

        $permissions = array();
        foreach ( $critical_files as $name => $path ) {
            if ( file_exists( $path ) ) {
                $permissions[ $name ] = array(
                    'exists' => true,
                    'writable' => is_writable( $path ),
                    'permissions' => substr( sprintf( '%o', fileperms( $path ) ), -4 ),
                );
            } else {
                $permissions[ $name ] = array( 'exists' => false );
            }
        }

        return $permissions;
    }

    /**
     * Check security headers
     *
     * @return array Security headers status
     */
    private function check_security_headers() {
        // This is a simplified check - in production, you'd want to make an actual HTTP request
        return array(
            'ssl_enabled' => is_ssl(),
            'admin_ssl' => defined( 'FORCE_SSL_ADMIN' ) && FORCE_SSL_ADMIN,
            'debug_mode' => defined( 'WP_DEBUG' ) && WP_DEBUG,
            'debug_display' => defined( 'WP_DEBUG_DISPLAY' ) && WP_DEBUG_DISPLAY,
            'script_debug' => defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG,
        );
    }
}