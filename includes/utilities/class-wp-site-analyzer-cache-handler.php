<?php
/**
 * Cache Handler for WP Site Analyzer
 *
 * @package WP_Site_Analyzer
 * @subpackage Utilities
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class WP_Site_Analyzer_Cache_Handler
 *
 * Handles caching operations for the plugin
 */
class WP_Site_Analyzer_Cache_Handler {

    /**
     * Cache prefix
     *
     * @var string
     */
    private $cache_prefix = 'wp_site_analyzer_';

    /**
     * Cache group
     *
     * @var string
     */
    private $cache_group = 'wp_site_analyzer';

    /**
     * Default expiration time (1 hour)
     *
     * @var int
     */
    private $default_expiration = 3600;

    /**
     * Constructor
     */
    public function __construct() {
        // Add cache group to non-persistent groups if object cache is available
        if ( wp_using_ext_object_cache() ) {
            wp_cache_add_non_persistent_groups( $this->cache_group );
        }
    }

    /**
     * Get cached data
     *
     * @param string $key Cache key
     * @return mixed|false Cached data or false
     */
    public function get( $key ) {
        $key = $this->sanitize_key( $key );

        // Try object cache first
        if ( wp_using_ext_object_cache() ) {
            $data = wp_cache_get( $key, $this->cache_group );
            if ( false !== $data ) {
                return $data;
            }
        }

        // Fall back to transients
        return get_transient( $this->cache_prefix . $key );
    }

    /**
     * Set cached data
     *
     * @param string $key Cache key
     * @param mixed $data Data to cache
     * @param int $expiration Expiration time in seconds
     * @return bool Success status
     */
    public function set( $key, $data, $expiration = null ) {
        $key = $this->sanitize_key( $key );
        $expiration = $expiration ?? $this->default_expiration;
        
        // Debug logging
        error_log( 'WP Site Analyzer Cache: Setting key - ' . $key . ', Expiration: ' . $expiration );

        // Use object cache if available
        if ( wp_using_ext_object_cache() ) {
            $result = wp_cache_set( $key, $data, $this->cache_group, $expiration );
            error_log( 'WP Site Analyzer Cache: Set in object cache - ' . ( $result ? 'success' : 'failed' ) );
            return $result;
        }

        // Fall back to transients
        $transient_key = $this->cache_prefix . $key;
        $result = set_transient( $transient_key, $data, $expiration );
        error_log( 'WP Site Analyzer Cache: Set transient ' . $transient_key . ' - ' . ( $result ? 'success' : 'failed' ) );
        
        return $result;
    }

    /**
     * Delete cached data
     *
     * @param string $key Cache key
     * @return bool Success status
     */
    public function delete( $key ) {
        $key = $this->sanitize_key( $key );

        // Delete from object cache
        if ( wp_using_ext_object_cache() ) {
            wp_cache_delete( $key, $this->cache_group );
        }

        // Delete transient
        return delete_transient( $this->cache_prefix . $key );
    }

    /**
     * Clear all cached data
     *
     * @return bool Success status
     */
    public function clear_all() {
        global $wpdb;

        // Clear object cache group
        if ( wp_using_ext_object_cache() ) {
            wp_cache_flush_group( $this->cache_group );
        }

        // Clear transients
        $wpdb->query( $wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
            '_transient_' . $this->cache_prefix . '%',
            '_transient_timeout_' . $this->cache_prefix . '%'
        ) );

        return true;
    }

    /**
     * Check if cache is enabled
     *
     * @return bool
     */
    public function is_enabled() {
        $settings = get_option( 'wp_site_analyzer_settings' );
        return ! empty( $settings['enable_caching'] );
    }

    /**
     * Get cache statistics
     *
     * @return array Cache statistics
     */
    public function get_stats() {
        global $wpdb;

        $stats = array(
            'cache_type' => wp_using_ext_object_cache() ? 'object_cache' : 'transients',
            'cache_enabled' => $this->is_enabled(),
        );

        // Count transients if using transient storage
        if ( ! wp_using_ext_object_cache() ) {
            $stats['transient_count'] = (int) $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE %s",
                '_transient_' . $this->cache_prefix . '%'
            ) );
        }

        return $stats;
    }

    /**
     * Sanitize cache key
     *
     * @param string $key Raw cache key
     * @return string Sanitized key
     */
    private function sanitize_key( $key ) {
        // Validate key is not empty
        if ( empty( $key ) ) {
            $key = 'default';
        }
        
        // Remove any characters that might cause issues
        $key = preg_replace( '/[^a-zA-Z0-9_\-]/', '_', $key );
        
        // Ensure key is not empty after sanitization
        if ( empty( $key ) ) {
            $key = 'sanitized_' . md5( $key );
        }
        
        // Ensure key length is within limits (WordPress transient name limit is 172 chars)
        if ( strlen( $this->cache_prefix . $key ) > 172 ) {
            $key = md5( $key );
        }

        return $key;
    }

    /**
     * Remember a value using a callback if not cached
     *
     * @param string $key Cache key
     * @param callable $callback Callback to generate value
     * @param int $expiration Expiration time
     * @return mixed Cached or generated value
     */
    public function remember( $key, $callback, $expiration = null ) {
        $value = $this->get( $key );

        if ( false === $value ) {
            $value = call_user_func( $callback );
            $this->set( $key, $value, $expiration );
        }

        return $value;
    }

    /**
     * Get multiple cached values
     *
     * @param array $keys Array of cache keys
     * @return array Array of values keyed by cache key
     */
    public function get_multiple( $keys ) {
        $results = array();

        foreach ( $keys as $key ) {
            $results[ $key ] = $this->get( $key );
        }

        return $results;
    }

    /**
     * Set multiple cached values
     *
     * @param array $data Array of key => value pairs
     * @param int $expiration Expiration time
     * @return array Array of success statuses keyed by cache key
     */
    public function set_multiple( $data, $expiration = null ) {
        $results = array();

        foreach ( $data as $key => $value ) {
            $results[ $key ] = $this->set( $key, $value, $expiration );
        }

        return $results;
    }

    /**
     * Delete multiple cached values
     *
     * @param array $keys Array of cache keys
     * @return array Array of success statuses keyed by cache key
     */
    public function delete_multiple( $keys ) {
        $results = array();

        foreach ( $keys as $key ) {
            $results[ $key ] = $this->delete( $key );
        }

        return $results;
    }

    /**
     * Increment a numeric cached value
     *
     * @param string $key Cache key
     * @param int $offset Amount to increment by
     * @return int|false New value or false on failure
     */
    public function increment( $key, $offset = 1 ) {
        $value = $this->get( $key );

        if ( false === $value ) {
            $value = 0;
        }

        if ( ! is_numeric( $value ) ) {
            return false;
        }

        $new_value = (int) $value + (int) $offset;
        $this->set( $key, $new_value );

        return $new_value;
    }

    /**
     * Decrement a numeric cached value
     *
     * @param string $key Cache key
     * @param int $offset Amount to decrement by
     * @return int|false New value or false on failure
     */
    public function decrement( $key, $offset = 1 ) {
        return $this->increment( $key, -$offset );
    }
}