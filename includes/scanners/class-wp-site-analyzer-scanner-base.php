<?php
/**
 * Base scanner abstract class
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
 * Abstract class WP_Site_Analyzer_Scanner_Base
 *
 * Base class that all scanners must extend
 */
abstract class WP_Site_Analyzer_Scanner_Base {

    /**
     * Cache handler instance
     *
     * @var WP_Site_Analyzer_Cache_Handler
     */
    protected $cache_handler;

    /**
     * Scanner name
     *
     * @var string
     */
    protected $scanner_name;

    /**
     * Scanner version
     *
     * @var string
     */
    protected $scanner_version = '1.0.0';

    /**
     * Performance monitor
     *
     * @var array
     */
    protected $performance_data = array();

    /**
     * Constructor
     *
     * @param WP_Site_Analyzer_Cache_Handler $cache_handler Cache handler instance
     */
    public function __construct( $cache_handler ) {
        $this->cache_handler = $cache_handler;
        $this->scanner_name = get_class( $this );
        $this->init();
    }

    /**
     * Initialize the scanner
     * Can be overridden by child classes for specific initialization
     */
    protected function init() {
        // Override in child classes if needed
    }

    /**
     * Abstract method that must be implemented by all scanners
     *
     * @return array Scan results
     */
    abstract public function scan();

    /**
     * Get cached results
     *
     * @param string $cache_key Cache key
     * @return mixed|false Cached data or false if not found
     */
    protected function get_cached_results( $cache_key ) {
        if ( ! $this->is_caching_enabled() ) {
            return false;
        }

        return $this->cache_handler->get( $cache_key );
    }

    /**
     * Set cached results
     *
     * @param string $cache_key Cache key
     * @param mixed $data Data to cache
     * @param int $expiration Expiration time in seconds
     * @return bool Success status
     */
    protected function set_cached_results( $cache_key, $data, $expiration = 3600 ) {
        if ( ! $this->is_caching_enabled() ) {
            return false;
        }

        return $this->cache_handler->set( $cache_key, $data, $expiration );
    }

    /**
     * Check if caching is enabled
     *
     * @return bool
     */
    protected function is_caching_enabled() {
        $settings = get_option( 'wp_site_analyzer_settings' );
        return ! empty( $settings['enable_caching'] );
    }

    /**
     * Start performance monitoring
     *
     * @param string $operation Operation name
     */
    protected function start_performance_monitor( $operation ) {
        $this->performance_data[ $operation ] = array(
            'start_time' => microtime( true ),
            'start_memory' => memory_get_usage( true ),
        );
    }

    /**
     * End performance monitoring
     *
     * @param string $operation Operation name
     * @return array Performance metrics
     */
    protected function end_performance_monitor( $operation ) {
        if ( ! isset( $this->performance_data[ $operation ] ) ) {
            return array();
        }

        $end_time = microtime( true );
        $end_memory = memory_get_usage( true );

        $metrics = array(
            'execution_time' => $end_time - $this->performance_data[ $operation ]['start_time'],
            'memory_used' => $end_memory - $this->performance_data[ $operation ]['start_memory'],
            'peak_memory' => memory_get_peak_usage( true ),
        );

        unset( $this->performance_data[ $operation ] );

        return $metrics;
    }

    /**
     * Batch process large datasets
     *
     * @param array $items Items to process
     * @param callable $callback Processing callback
     * @param int $batch_size Batch size
     * @return array Processed results
     */
    protected function batch_process( $items, $callback, $batch_size = 100 ) {
        $results = array();
        $chunks = array_chunk( $items, $batch_size );

        foreach ( $chunks as $chunk ) {
            // Process batch
            $batch_results = call_user_func( $callback, $chunk );
            
            // Merge results
            if ( is_array( $batch_results ) ) {
                $results = array_merge( $results, $batch_results );
            }

            // Allow WordPress to breathe
            if ( function_exists( 'wp_cache_flush' ) ) {
                wp_cache_flush();
            }
        }

        return $results;
    }

    /**
     * Log scanner activity
     *
     * @param string $message Log message
     * @param string $level Log level (info, warning, error)
     */
    protected function log( $message, $level = 'info' ) {
        if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
            return;
        }

        $log_entry = sprintf(
            '[%s] [%s] [%s] %s',
            current_time( 'mysql' ),
            $this->scanner_name,
            strtoupper( $level ),
            $message
        );

        error_log( $log_entry );
    }

    /**
     * Sanitize and validate scan results
     *
     * @param array $results Raw scan results
     * @return array Sanitized results
     */
    protected function sanitize_results( $results ) {
        if ( ! is_array( $results ) ) {
            return array();
        }

        // Recursively sanitize array values
        array_walk_recursive( $results, function( &$value ) {
            if ( is_string( $value ) ) {
                $value = sanitize_text_field( $value );
            }
        });

        return $results;
    }

    /**
     * Get scanner metadata
     *
     * @return array Scanner metadata
     */
    public function get_metadata() {
        return array(
            'name' => $this->scanner_name,
            'version' => $this->scanner_version,
            'capabilities' => $this->get_capabilities(),
        );
    }

    /**
     * Get scanner capabilities
     * Override in child classes to specify what the scanner can do
     *
     * @return array Scanner capabilities
     */
    protected function get_capabilities() {
        return array();
    }

    /**
     * Check if the scanner should run based on conditions
     *
     * @return bool
     */
    protected function should_run() {
        // Check memory limit
        $memory_limit = $this->get_memory_limit();
        $current_usage = memory_get_usage( true );
        
        if ( $current_usage > $memory_limit * 0.8 ) {
            $this->log( 'Scanner skipped due to high memory usage', 'warning' );
            return false;
        }

        return true;
    }

    /**
     * Get memory limit in bytes
     *
     * @return int Memory limit in bytes
     */
    protected function get_memory_limit() {
        $memory_limit = ini_get( 'memory_limit' );
        
        if ( preg_match( '/^(\d+)(.)$/', $memory_limit, $matches ) ) {
            if ( $matches[2] == 'M' ) {
                return $matches[1] * 1024 * 1024;
            } else if ( $matches[2] == 'K' ) {
                return $matches[1] * 1024;
            }
        }

        return 128 * 1024 * 1024; // Default 128M
    }

    /**
     * Format bytes to human readable format
     *
     * @param int $bytes Bytes to format
     * @param int $decimals Decimal places
     * @return string Formatted string
     */
    protected function format_bytes( $bytes, $decimals = 2 ) {
        $units = array( 'B', 'KB', 'MB', 'GB', 'TB' );
        
        $bytes = max( $bytes, 0 );
        $pow = floor( ( $bytes ? log( $bytes ) : 0 ) / log( 1024 ) );
        $pow = min( $pow, count( $units ) - 1 );
        
        $bytes /= ( 1 << ( 10 * $pow ) );
        
        return round( $bytes, $decimals ) . ' ' . $units[$pow];
    }
}