<?php
/**
 * Taxonomy Scanner
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
 * Class WP_Site_Analyzer_Taxonomy_Scanner
 *
 * Scans and analyzes all registered taxonomies
 */
class WP_Site_Analyzer_Taxonomy_Scanner extends WP_Site_Analyzer_Scanner_Base {

    /**
     * Scanner version
     *
     * @var string
     */
    protected $scanner_version = '1.0.0';

    /**
     * Get scanner capabilities
     *
     * @return array
     */
    protected function get_capabilities() {
        return array(
            'scan_taxonomies' => true,
            'scan_terms' => true,
            'scan_term_meta' => true,
            'scan_hierarchies' => true,
            'scan_relationships' => true,
            'analyze_usage' => true,
        );
    }

    /**
     * Perform the scan
     *
     * @return array Scan results
     */
    public function scan() {
        $this->start_performance_monitor( 'taxonomy_scan' );

        // Check cache first
        $cache_key = 'taxonomy_scan_results';
        $cached_results = $this->get_cached_results( $cache_key );
        
        if ( false !== $cached_results ) {
            $this->log( 'Returning cached taxonomy scan results' );
            return $cached_results;
        }

        // Perform scan
        $results = array(
            'taxonomies' => $this->scan_taxonomies(),
            'statistics' => $this->get_taxonomy_statistics(),
            'relationships' => $this->scan_taxonomy_relationships(),
            'hierarchies' => $this->analyze_hierarchies(),
            'custom_capabilities' => $this->scan_custom_capabilities(),
            'performance_metrics' => $this->end_performance_monitor( 'taxonomy_scan' ),
        );

        // Cache results
        $this->set_cached_results( $cache_key, $results );

        return $this->sanitize_results( $results );
    }

    /**
     * Scan all registered taxonomies
     *
     * @return array Taxonomy data
     */
    private function scan_taxonomies() {
        $taxonomies = get_taxonomies( array(), 'objects' );
        $results = array();

        foreach ( $taxonomies as $taxonomy_name => $taxonomy_object ) {
            $results[ $taxonomy_name ] = $this->analyze_taxonomy( $taxonomy_object );
        }

        return $results;
    }

    /**
     * Analyze a single taxonomy
     *
     * @param WP_Taxonomy $taxonomy Taxonomy object
     * @return array Taxonomy analysis
     */
    private function analyze_taxonomy( $taxonomy ) {
        $analysis = array(
            'name' => $taxonomy->name,
            'label' => $taxonomy->label,
            'labels' => (array) $taxonomy->labels,
            'description' => $taxonomy->description,
            'object_type' => $taxonomy->object_type,
            'public' => $taxonomy->public,
            'publicly_queryable' => $taxonomy->publicly_queryable,
            'hierarchical' => $taxonomy->hierarchical,
            'show_ui' => $taxonomy->show_ui,
            'show_in_menu' => $taxonomy->show_in_menu,
            'show_in_nav_menus' => $taxonomy->show_in_nav_menus,
            'show_in_rest' => $taxonomy->show_in_rest,
            'rest_base' => $taxonomy->rest_base,
            'rest_controller_class' => $taxonomy->rest_controller_class,
            'show_tagcloud' => $taxonomy->show_tagcloud,
            'show_in_quick_edit' => $taxonomy->show_in_quick_edit,
            'show_admin_column' => $taxonomy->show_admin_column,
            'capabilities' => (array) $taxonomy->cap,
            'rewrite' => $taxonomy->rewrite,
            'query_var' => $taxonomy->query_var,
            'update_count_callback' => $taxonomy->update_count_callback,
            'default_term' => $taxonomy->default_term,
            'sort' => $taxonomy->sort,
            '_builtin' => $taxonomy->_builtin,
            'source' => $this->detect_taxonomy_source( $taxonomy ),
            'term_count' => wp_count_terms( array( 'taxonomy' => $taxonomy->name, 'hide_empty' => false ) ),
            'terms' => $this->get_sample_terms( $taxonomy->name ),
            'meta_fields' => $this->scan_term_meta_fields( $taxonomy->name ),
            'max_depth' => $taxonomy->hierarchical ? $this->get_max_term_depth( $taxonomy->name ) : 0,
            'usage_stats' => $this->get_taxonomy_usage_stats( $taxonomy->name ),
            'sample_urls' => $this->get_sample_term_urls( $taxonomy->name ),
        );

        // Add REST API fields if enabled
        if ( $taxonomy->show_in_rest ) {
            $analysis['rest_fields'] = $this->get_rest_fields( $taxonomy->name );
        }

        return $analysis;
    }

    /**
     * Detect the source of a taxonomy (theme, plugin, core)
     *
     * @param WP_Taxonomy $taxonomy Taxonomy object
     * @return array Source information
     */
    private function detect_taxonomy_source( $taxonomy ) {
        if ( $taxonomy->_builtin ) {
            return array(
                'type' => 'core',
                'name' => 'WordPress Core',
            );
        }

        // Try to detect from backtrace
        $backtrace = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS );
        $theme_dir = get_theme_root();
        $plugin_dir = WP_PLUGIN_DIR;

        foreach ( $backtrace as $trace ) {
            if ( isset( $trace['file'] ) ) {
                if ( strpos( $trace['file'], $theme_dir ) !== false ) {
                    return array(
                        'type' => 'theme',
                        'name' => wp_get_theme()->get( 'Name' ),
                        'file' => str_replace( $theme_dir, '', $trace['file'] ),
                    );
                } elseif ( strpos( $trace['file'], $plugin_dir ) !== false ) {
                    $plugin_file = str_replace( $plugin_dir . '/', '', $trace['file'] );
                    $plugin_data = $this->get_plugin_from_file( $plugin_file );
                    return array(
                        'type' => 'plugin',
                        'name' => $plugin_data['name'] ?? 'Unknown Plugin',
                        'file' => $plugin_file,
                    );
                }
            }
        }

        return array(
            'type' => 'unknown',
            'name' => 'Unknown Source',
        );
    }

    /**
     * Get plugin information from file path
     *
     * @param string $file File path
     * @return array Plugin data
     */
    private function get_plugin_from_file( $file ) {
        $plugin_dir = dirname( $file );
        $plugins = get_plugins();

        foreach ( $plugins as $plugin_file => $plugin_data ) {
            if ( strpos( $plugin_file, $plugin_dir ) === 0 ) {
                return array(
                    'name' => $plugin_data['Name'],
                    'version' => $plugin_data['Version'],
                    'author' => $plugin_data['Author'],
                );
            }
        }

        return array( 'name' => 'Unknown Plugin' );
    }

    /**
     * Get sample terms for a taxonomy
     *
     * @param string $taxonomy Taxonomy name
     * @return array Sample terms
     */
    private function get_sample_terms( $taxonomy ) {
        $terms = get_terms( array(
            'taxonomy' => $taxonomy,
            'number' => 5,
            'hide_empty' => false,
            'orderby' => 'count',
            'order' => 'DESC',
        ) );

        if ( is_wp_error( $terms ) ) {
            return array();
        }

        return array_map( function( $term ) {
            return array(
                'term_id' => $term->term_id,
                'name' => $term->name,
                'slug' => $term->slug,
                'count' => $term->count,
                'parent' => $term->parent,
                'description' => $term->description,
            );
        }, $terms );
    }

    /**
     * Scan term meta fields for a taxonomy
     *
     * @param string $taxonomy Taxonomy name
     * @return array Meta field information
     */
    private function scan_term_meta_fields( $taxonomy ) {
        global $wpdb;

        // Get sample term IDs
        $sample_ids = get_terms( array(
            'taxonomy' => $taxonomy,
            'number' => 10,
            'fields' => 'ids',
            'hide_empty' => false,
        ) );

        if ( empty( $sample_ids ) || is_wp_error( $sample_ids ) ) {
            return array();
        }

        // Get all meta keys for these terms
        $meta_keys = $wpdb->get_col( 
            "SELECT DISTINCT meta_key FROM {$wpdb->termmeta} 
            WHERE term_id IN (" . implode(',', array_map('intval', $sample_ids)) . ") 
            ORDER BY meta_key"
        );

        $meta_fields = array();
        foreach ( $meta_keys as $meta_key ) {
            // Skip private meta keys unless specifically configured
            if ( substr( $meta_key, 0, 1 ) === '_' && ! apply_filters( 'wp_site_analyzer_include_private_meta', false ) ) {
                continue;
            }

            $meta_fields[] = array(
                'key' => $meta_key,
                'type' => $this->detect_term_meta_field_type( $taxonomy, $meta_key ),
                'usage_count' => $this->get_term_meta_usage_count( $taxonomy, $meta_key ),
                'sample_values' => $this->get_term_meta_sample_values( $taxonomy, $meta_key ),
                'is_protected' => is_protected_meta( $meta_key, 'term' ),
                'is_registered' => registered_meta_key_exists( 'term', $meta_key ),
            );
        }

        return $meta_fields;
    }

    /**
     * Detect term meta field type
     *
     * @param string $taxonomy Taxonomy name
     * @param string $meta_key Meta key
     * @return string Field type
     */
    private function detect_term_meta_field_type( $taxonomy, $meta_key ) {
        // Check if registered with specific type
        $registered = get_registered_meta_keys( 'term' );
        if ( isset( $registered[ $meta_key ] ) && isset( $registered[ $meta_key ]['type'] ) ) {
            return $registered[ $meta_key ]['type'];
        }

        // Sample values to detect type
        global $wpdb;
        $sample_values = $wpdb->get_col( $wpdb->prepare(
            "SELECT tm.meta_value FROM {$wpdb->termmeta} tm
            JOIN {$wpdb->term_taxonomy} tt ON tm.term_id = tt.term_id
            WHERE tt.taxonomy = %s AND tm.meta_key = %s
            LIMIT 5",
            $taxonomy, $meta_key
        ) );

        // Analyze sample values
        $types = array();
        foreach ( $sample_values as $value ) {
            if ( is_serialized( $value ) ) {
                $types[] = 'serialized';
            } elseif ( is_numeric( $value ) ) {
                $types[] = strpos( $value, '.' ) !== false ? 'float' : 'integer';
            } elseif ( $value === '0' || $value === '1' ) {
                $types[] = 'boolean';
            } elseif ( filter_var( $value, FILTER_VALIDATE_URL ) ) {
                $types[] = 'url';
            } elseif ( filter_var( $value, FILTER_VALIDATE_EMAIL ) ) {
                $types[] = 'email';
            } elseif ( strlen( $value ) > 100 ) {
                $types[] = 'textarea';
            } else {
                $types[] = 'string';
            }
        }

        // Return most common type
        $type_counts = array_count_values( $types );
        arsort( $type_counts );
        return key( $type_counts ) ?: 'string';
    }

    /**
     * Get term meta usage count
     *
     * @param string $taxonomy Taxonomy name
     * @param string $meta_key Meta key
     * @return int Usage count
     */
    private function get_term_meta_usage_count( $taxonomy, $meta_key ) {
        global $wpdb;
        
        return (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(DISTINCT tm.term_id) FROM {$wpdb->termmeta} tm
            JOIN {$wpdb->term_taxonomy} tt ON tm.term_id = tt.term_id
            WHERE tt.taxonomy = %s AND tm.meta_key = %s",
            $taxonomy, $meta_key
        ) );
    }

    /**
     * Get sample values for term meta
     *
     * @param string $taxonomy Taxonomy name
     * @param string $meta_key Meta key
     * @return array Sample values
     */
    private function get_term_meta_sample_values( $taxonomy, $meta_key ) {
        global $wpdb;
        
        $values = $wpdb->get_col( $wpdb->prepare(
            "SELECT DISTINCT tm.meta_value FROM {$wpdb->termmeta} tm
            JOIN {$wpdb->term_taxonomy} tt ON tm.term_id = tt.term_id
            WHERE tt.taxonomy = %s AND tm.meta_key = %s
            LIMIT 3",
            $taxonomy, $meta_key
        ) );

        // Unserialize if needed and truncate long values
        return array_map( function( $value ) {
            if ( is_serialized( $value ) ) {
                $value = maybe_unserialize( $value );
                return is_array( $value ) || is_object( $value ) ? '[' . gettype( $value ) . ']' : $value;
            }
            return strlen( $value ) > 50 ? substr( $value, 0, 50 ) . '...' : $value;
        }, $values );
    }

    /**
     * Get maximum term hierarchy depth
     *
     * @param string $taxonomy Taxonomy name
     * @return int Maximum depth
     */
    private function get_max_term_depth( $taxonomy ) {
        $terms = get_terms( array(
            'taxonomy' => $taxonomy,
            'hide_empty' => false,
            'fields' => 'id=>parent',
        ) );

        if ( is_wp_error( $terms ) || empty( $terms ) ) {
            return 0;
        }

        $max_depth = 0;
        foreach ( $terms as $term_id => $parent_id ) {
            $depth = 0;
            $current_parent = $parent_id;
            
            while ( $current_parent > 0 && isset( $terms[ $current_parent ] ) ) {
                $depth++;
                $current_parent = $terms[ $current_parent ];
                
                if ( $depth > 10 ) {
                    break; // Prevent infinite loops
                }
            }
            
            $max_depth = max( $max_depth, $depth );
        }

        return $max_depth;
    }

    /**
     * Get taxonomy usage statistics
     *
     * @param string $taxonomy Taxonomy name
     * @return array Usage statistics
     */
    private function get_taxonomy_usage_stats( $taxonomy ) {
        global $wpdb;

        $stats = array(
            'total_terms' => wp_count_terms( array( 'taxonomy' => $taxonomy, 'hide_empty' => false ) ),
            'used_terms' => wp_count_terms( array( 'taxonomy' => $taxonomy, 'hide_empty' => true ) ),
            'empty_terms' => 0,
            'posts_with_terms' => 0,
            'average_terms_per_post' => 0,
            'most_used_terms' => array(),
        );

        // Calculate empty terms
        $stats['empty_terms'] = $stats['total_terms'] - $stats['used_terms'];

        // Get posts with terms
        $post_types = get_taxonomy( $taxonomy )->object_type;
        if ( ! empty( $post_types ) ) {
            $stats['posts_with_terms'] = (int) $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(DISTINCT object_id) FROM {$wpdb->term_relationships} tr
                JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
                WHERE tt.taxonomy = %s",
                $taxonomy
            ) );

            // Calculate average terms per post
            $total_relationships = (int) $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->term_relationships} tr
                JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
                WHERE tt.taxonomy = %s",
                $taxonomy
            ) );

            if ( $stats['posts_with_terms'] > 0 ) {
                $stats['average_terms_per_post'] = round( $total_relationships / $stats['posts_with_terms'], 2 );
            }
        }

        // Get most used terms
        $most_used = get_terms( array(
            'taxonomy' => $taxonomy,
            'number' => 5,
            'orderby' => 'count',
            'order' => 'DESC',
            'hide_empty' => true,
        ) );

        if ( ! is_wp_error( $most_used ) ) {
            $stats['most_used_terms'] = array_map( function( $term ) {
                return array(
                    'name' => $term->name,
                    'slug' => $term->slug,
                    'count' => $term->count,
                );
            }, $most_used );
        }

        return $stats;
    }

    /**
     * Get sample term URLs
     *
     * @param string $taxonomy Taxonomy name
     * @return array Sample URLs
     */
    private function get_sample_term_urls( $taxonomy ) {
        $term = get_terms( array(
            'taxonomy' => $taxonomy,
            'number' => 1,
            'hide_empty' => false,
        ) );

        if ( is_wp_error( $term ) || empty( $term ) ) {
            return array();
        }

        return array(
            'term_link' => get_term_link( $term[0] ),
            'edit_link' => get_edit_term_link( $term[0]->term_id, $taxonomy ),
        );
    }

    /**
     * Get REST API fields for taxonomy
     *
     * @param string $taxonomy Taxonomy name
     * @return array REST fields
     */
    private function get_rest_fields( $taxonomy ) {
        $fields = array();
        
        // Get registered REST fields
        $rest_fields = apply_filters( 'rest_api_init', array() );
        
        // Basic REST fields that are always available
        $fields['default'] = array( 'id', 'count', 'description', 'link', 'name', 'slug', 'taxonomy', 'parent', 'meta' );
        
        // Check for additional fields registered via REST API
        global $wp_rest_additional_fields;
        if ( isset( $wp_rest_additional_fields[ $taxonomy ] ) ) {
            $fields['additional'] = array_keys( $wp_rest_additional_fields[ $taxonomy ] );
        }

        return $fields;
    }

    /**
     * Get overall taxonomy statistics
     *
     * @return array Statistics
     */
    private function get_taxonomy_statistics() {
        global $wpdb;

        $stats = array(
            'total_taxonomies' => count( get_taxonomies() ),
            'public_taxonomies' => count( get_taxonomies( array( 'public' => true ) ) ),
            'custom_taxonomies' => count( get_taxonomies( array( '_builtin' => false ) ) ),
            'hierarchical_taxonomies' => count( get_taxonomies( array( 'hierarchical' => true ) ) ),
            'total_terms' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->terms}" ),
            'total_term_relationships' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->term_relationships}" ),
            'total_term_meta' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->termmeta}" ),
            'unique_term_meta_keys' => (int) $wpdb->get_var( "SELECT COUNT(DISTINCT meta_key) FROM {$wpdb->termmeta}" ),
        );

        // Get taxonomies by post type
        $post_types = get_post_types( array(), 'names' );
        $taxonomies_by_post_type = array();
        
        foreach ( $post_types as $post_type ) {
            $taxonomies = get_object_taxonomies( $post_type );
            if ( ! empty( $taxonomies ) ) {
                $taxonomies_by_post_type[ $post_type ] = $taxonomies;
            }
        }
        
        $stats['taxonomies_by_post_type'] = $taxonomies_by_post_type;

        return $stats;
    }

    /**
     * Scan taxonomy relationships
     *
     * @return array Relationship data
     */
    private function scan_taxonomy_relationships() {
        $relationships = array();
        $taxonomies = get_taxonomies( array(), 'objects' );

        foreach ( $taxonomies as $taxonomy_name => $taxonomy ) {
            $relationships[ $taxonomy_name ] = array(
                'post_types' => $taxonomy->object_type,
                'shared_with' => array(),
            );

            // Find other taxonomies that share the same post types
            foreach ( $taxonomies as $other_taxonomy_name => $other_taxonomy ) {
                if ( $taxonomy_name !== $other_taxonomy_name ) {
                    $shared_post_types = array_intersect( $taxonomy->object_type, $other_taxonomy->object_type );
                    if ( ! empty( $shared_post_types ) ) {
                        $relationships[ $taxonomy_name ]['shared_with'][ $other_taxonomy_name ] = $shared_post_types;
                    }
                }
            }

            // Check for term meta relationships
            $relationships[ $taxonomy_name ]['has_term_meta'] = $this->taxonomy_has_term_meta( $taxonomy_name );
        }

        return $relationships;
    }

    /**
     * Check if taxonomy has term meta
     *
     * @param string $taxonomy Taxonomy name
     * @return bool
     */
    private function taxonomy_has_term_meta( $taxonomy ) {
        global $wpdb;
        
        $count = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->termmeta} tm
            JOIN {$wpdb->term_taxonomy} tt ON tm.term_id = tt.term_id
            WHERE tt.taxonomy = %s
            LIMIT 1",
            $taxonomy
        ) );

        return $count > 0;
    }

    /**
     * Analyze taxonomy hierarchies
     *
     * @return array Hierarchy analysis
     */
    private function analyze_hierarchies() {
        $hierarchies = array();
        $taxonomies = get_taxonomies( array( 'hierarchical' => true ), 'objects' );

        foreach ( $taxonomies as $taxonomy_name => $taxonomy ) {
            $hierarchy_data = array(
                'max_depth' => $this->get_max_term_depth( $taxonomy_name ),
                'total_parent_terms' => 0,
                'total_child_terms' => 0,
                'orphaned_terms' => 0,
                'circular_references' => array(),
                'depth_distribution' => array(),
            );

            // Analyze term relationships
            $terms = get_terms( array(
                'taxonomy' => $taxonomy_name,
                'hide_empty' => false,
                'fields' => 'id=>parent',
            ) );

            if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
                foreach ( $terms as $term_id => $parent_id ) {
                    if ( $parent_id > 0 ) {
                        $hierarchy_data['total_child_terms']++;
                        if ( ! isset( $terms[ $parent_id ] ) ) {
                            $hierarchy_data['orphaned_terms']++;
                        }
                    } else {
                        $hierarchy_data['total_parent_terms']++;
                    }

                    // Calculate depth for distribution
                    $depth = 0;
                    $current_parent = $parent_id;
                    $visited = array( $term_id );
                    
                    while ( $current_parent > 0 && isset( $terms[ $current_parent ] ) ) {
                        if ( in_array( $current_parent, $visited ) ) {
                            $hierarchy_data['circular_references'][] = array(
                                'term_id' => $term_id,
                                'circular_parent' => $current_parent,
                            );
                            break;
                        }
                        
                        $visited[] = $current_parent;
                        $depth++;
                        $current_parent = $terms[ $current_parent ];
                    }
                    
                    if ( ! isset( $hierarchy_data['depth_distribution'][ $depth ] ) ) {
                        $hierarchy_data['depth_distribution'][ $depth ] = 0;
                    }
                    $hierarchy_data['depth_distribution'][ $depth ]++;
                }
            }

            $hierarchies[ $taxonomy_name ] = $hierarchy_data;
        }

        return $hierarchies;
    }

    /**
     * Scan custom taxonomy capabilities
     *
     * @return array Custom capabilities
     */
    private function scan_custom_capabilities() {
        $custom_caps = array();
        $taxonomies = get_taxonomies( array( '_builtin' => false ), 'objects' );

        foreach ( $taxonomies as $taxonomy_name => $taxonomy ) {
            if ( is_array( $taxonomy->capabilities ) ) {
                $default_caps = get_taxonomy_labels( get_taxonomy( 'category' ) );
                $custom = array();

                foreach ( $taxonomy->capabilities as $cap => $value ) {
                    // Check if this capability differs from default
                    if ( ! isset( $default_caps->$cap ) || $default_caps->$cap !== $value ) {
                        $custom[ $cap ] = $value;
                    }
                }

                if ( ! empty( $custom ) ) {
                    $custom_caps[ $taxonomy_name ] = $custom;
                }
            }
        }

        return $custom_caps;
    }
}