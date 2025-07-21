<?php
/**
 * Post Type Scanner
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
 * Class WP_Site_Analyzer_Post_Type_Scanner
 *
 * Scans and analyzes all registered post types
 */
class WP_Site_Analyzer_Post_Type_Scanner extends WP_Site_Analyzer_Scanner_Base {

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
            'scan_post_types' => true,
            'scan_post_counts' => true,
            'scan_post_statuses' => true,
            'scan_post_supports' => true,
            'scan_post_taxonomies' => true,
            'scan_post_meta' => true,
            'scan_post_capabilities' => true,
            'scan_post_templates' => true,
        );
    }

    /**
     * Perform the scan
     *
     * @return array Scan results
     */
    public function scan() {
        $this->start_performance_monitor( 'post_type_scan' );

        // Check cache first
        $cache_key = 'post_type_scan_results';
        $cached_results = $this->get_cached_results( $cache_key );
        
        if ( false !== $cached_results ) {
            $this->log( 'Returning cached post type scan results' );
            return $cached_results;
        }

        // Perform scan
        $results = array(
            'post_types' => $this->scan_post_types(),
            'statistics' => $this->get_post_type_statistics(),
            'relationships' => $this->scan_post_type_relationships(),
            'templates' => $this->scan_post_type_templates(),
            'custom_statuses' => $this->scan_custom_post_statuses(),
            'performance_metrics' => $this->end_performance_monitor( 'post_type_scan' ),
        );

        // Cache results
        $this->set_cached_results( $cache_key, $results );

        return $this->sanitize_results( $results );
    }

    /**
     * Scan all registered post types
     *
     * @return array Post type data
     */
    private function scan_post_types() {
        $post_types = get_post_types( array(), 'objects' );
        $results = array();

        foreach ( $post_types as $post_type_name => $post_type_object ) {
            $results[ $post_type_name ] = $this->analyze_post_type( $post_type_object );
        }

        return $results;
    }

    /**
     * Analyze a single post type
     *
     * @param WP_Post_Type $post_type Post type object
     * @return array Post type analysis
     */
    private function analyze_post_type( $post_type ) {
        $analysis = array(
            'name' => $post_type->name,
            'label' => $post_type->label,
            'labels' => (array) $post_type->labels,
            'description' => $post_type->description,
            'public' => $post_type->public,
            'hierarchical' => $post_type->hierarchical,
            'exclude_from_search' => $post_type->exclude_from_search,
            'publicly_queryable' => $post_type->publicly_queryable,
            'show_ui' => $post_type->show_ui,
            'show_in_menu' => $post_type->show_in_menu,
            'show_in_nav_menus' => $post_type->show_in_nav_menus,
            'show_in_admin_bar' => $post_type->show_in_admin_bar,
            'show_in_rest' => $post_type->show_in_rest,
            'rest_base' => $post_type->rest_base,
            'rest_controller_class' => $post_type->rest_controller_class,
            'menu_position' => $post_type->menu_position,
            'menu_icon' => $post_type->menu_icon,
            'capability_type' => $post_type->capability_type,
            'capabilities' => (array) $post_type->cap,
            'map_meta_cap' => $post_type->map_meta_cap,
            'supports' => $this->get_post_type_supports( $post_type->name ),
            'taxonomies' => get_object_taxonomies( $post_type->name ),
            'has_archive' => $post_type->has_archive,
            'rewrite' => $post_type->rewrite,
            'query_var' => $post_type->query_var,
            'can_export' => $post_type->can_export,
            'delete_with_user' => $post_type->delete_with_user,
            '_builtin' => $post_type->_builtin,
            'source' => $this->detect_post_type_source( $post_type ),
            'meta_fields' => $this->scan_post_type_meta_fields( $post_type->name ),
            'count' => $this->get_post_type_count( $post_type->name ),
            'statuses' => $this->get_post_type_status_counts( $post_type->name ),
            'custom_fields_count' => $this->count_custom_fields( $post_type->name ),
            'sample_permalinks' => $this->get_sample_permalinks( $post_type->name ),
        );

        // Add template hierarchy information
        if ( $post_type->public ) {
            $analysis['template_hierarchy'] = $this->get_template_hierarchy( $post_type->name );
        }

        // Add Gutenberg block information if REST enabled
        if ( $post_type->show_in_rest ) {
            $analysis['allowed_blocks'] = $this->get_allowed_blocks( $post_type->name );
            $analysis['block_patterns'] = $this->get_block_patterns( $post_type->name );
        }

        return $analysis;
    }

    /**
     * Get post type supports array
     *
     * @param string $post_type Post type name
     * @return array Supported features
     */
    private function get_post_type_supports( $post_type ) {
        $all_supports = array(
            'title', 'editor', 'author', 'thumbnail', 'excerpt', 
            'trackbacks', 'custom-fields', 'comments', 'revisions', 
            'page-attributes', 'post-formats'
        );

        $supports = array();
        foreach ( $all_supports as $feature ) {
            if ( post_type_supports( $post_type, $feature ) ) {
                $supports[] = $feature;
            }
        }

        return $supports;
    }

    /**
     * Detect the source of a post type (theme, plugin, core)
     *
     * @param WP_Post_Type $post_type Post type object
     * @return array Source information
     */
    private function detect_post_type_source( $post_type ) {
        if ( $post_type->_builtin ) {
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
     * Scan meta fields for a post type
     *
     * @param string $post_type Post type name
     * @return array Meta field information
     */
    private function scan_post_type_meta_fields( $post_type ) {
        global $wpdb;

        // Get sample post IDs
        $sample_ids = $wpdb->get_col( $wpdb->prepare(
            "SELECT ID FROM {$wpdb->posts} WHERE post_type = %s AND post_status != 'auto-draft' LIMIT 10",
            $post_type
        ) );

        if ( empty( $sample_ids ) ) {
            return array();
        }

        // Get all meta keys for these posts
        $meta_keys = $wpdb->get_col( $wpdb->prepare(
            "SELECT DISTINCT meta_key FROM {$wpdb->postmeta} WHERE post_id IN (" . implode(',', $sample_ids) . ") ORDER BY meta_key"
        ) );

        $meta_fields = array();
        foreach ( $meta_keys as $meta_key ) {
            // Skip private meta keys unless specifically configured
            if ( substr( $meta_key, 0, 1 ) === '_' && ! apply_filters( 'wp_site_analyzer_include_private_meta', false ) ) {
                continue;
            }

            $meta_fields[] = array(
                'key' => $meta_key,
                'type' => $this->detect_meta_field_type( $post_type, $meta_key ),
                'usage_count' => $this->get_meta_key_usage_count( $post_type, $meta_key ),
                'sample_values' => $this->get_meta_sample_values( $post_type, $meta_key ),
                'is_protected' => is_protected_meta( $meta_key, 'post' ),
                'is_registered' => registered_meta_key_exists( 'post', $meta_key, $post_type ),
            );
        }

        return $meta_fields;
    }

    /**
     * Detect meta field type
     *
     * @param string $post_type Post type name
     * @param string $meta_key Meta key
     * @return string Field type
     */
    private function detect_meta_field_type( $post_type, $meta_key ) {
        // Check if registered with specific type
        $registered = get_registered_meta_keys( 'post', $post_type );
        if ( isset( $registered[ $meta_key ] ) && isset( $registered[ $meta_key ]['type'] ) ) {
            return $registered[ $meta_key ]['type'];
        }

        // Sample values to detect type
        global $wpdb;
        $sample_values = $wpdb->get_col( $wpdb->prepare(
            "SELECT meta_value FROM {$wpdb->postmeta} pm
            JOIN {$wpdb->posts} p ON pm.post_id = p.ID
            WHERE p.post_type = %s AND pm.meta_key = %s
            LIMIT 5",
            $post_type, $meta_key
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
     * Get meta key usage count
     *
     * @param string $post_type Post type name
     * @param string $meta_key Meta key
     * @return int Usage count
     */
    private function get_meta_key_usage_count( $post_type, $meta_key ) {
        global $wpdb;
        
        return (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(DISTINCT pm.post_id) FROM {$wpdb->postmeta} pm
            JOIN {$wpdb->posts} p ON pm.post_id = p.ID
            WHERE p.post_type = %s AND pm.meta_key = %s",
            $post_type, $meta_key
        ) );
    }

    /**
     * Get sample values for a meta key
     *
     * @param string $post_type Post type name
     * @param string $meta_key Meta key
     * @return array Sample values
     */
    private function get_meta_sample_values( $post_type, $meta_key ) {
        global $wpdb;
        
        $values = $wpdb->get_col( $wpdb->prepare(
            "SELECT DISTINCT meta_value FROM {$wpdb->postmeta} pm
            JOIN {$wpdb->posts} p ON pm.post_id = p.ID
            WHERE p.post_type = %s AND pm.meta_key = %s
            LIMIT 3",
            $post_type, $meta_key
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
     * Get post type count
     *
     * @param string $post_type Post type name
     * @return int Post count
     */
    private function get_post_type_count( $post_type ) {
        $counts = wp_count_posts( $post_type );
        $total = 0;
        
        foreach ( $counts as $status => $count ) {
            if ( $status !== 'auto-draft' ) {
                $total += $count;
            }
        }
        
        return $total;
    }

    /**
     * Get post type status counts
     *
     * @param string $post_type Post type name
     * @return array Status counts
     */
    private function get_post_type_status_counts( $post_type ) {
        $counts = wp_count_posts( $post_type );
        $result = array();
        
        foreach ( $counts as $status => $count ) {
            if ( $count > 0 && $status !== 'auto-draft' ) {
                $result[ $status ] = $count;
            }
        }
        
        return $result;
    }

    /**
     * Count custom fields for a post type
     *
     * @param string $post_type Post type name
     * @return int Custom field count
     */
    private function count_custom_fields( $post_type ) {
        global $wpdb;
        
        return (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(DISTINCT meta_key) FROM {$wpdb->postmeta} pm
            JOIN {$wpdb->posts} p ON pm.post_id = p.ID
            WHERE p.post_type = %s",
            $post_type
        ) );
    }

    /**
     * Get sample permalinks for a post type
     *
     * @param string $post_type Post type name
     * @return array Sample permalinks
     */
    private function get_sample_permalinks( $post_type ) {
        $post = get_posts( array(
            'post_type' => $post_type,
            'posts_per_page' => 1,
            'post_status' => 'publish',
        ) );

        if ( empty( $post ) ) {
            return array();
        }

        return array(
            'single' => get_permalink( $post[0] ),
            'archive' => get_post_type_archive_link( $post_type ),
        );
    }

    /**
     * Get template hierarchy for a post type
     *
     * @param string $post_type Post type name
     * @return array Template hierarchy
     */
    private function get_template_hierarchy( $post_type ) {
        $templates = array();

        // Single templates
        $templates['single'] = array(
            "single-{$post_type}.php",
            'single.php',
            'index.php',
        );

        // Archive templates
        if ( $post_type_object = get_post_type_object( $post_type ) ) {
            if ( $post_type_object->has_archive ) {
                $templates['archive'] = array(
                    "archive-{$post_type}.php",
                    'archive.php',
                    'index.php',
                );
            }
        }

        return $templates;
    }

    /**
     * Get allowed Gutenberg blocks for a post type
     *
     * @param string $post_type Post type name
     * @return array|null Allowed blocks or null if not restricted
     */
    private function get_allowed_blocks( $post_type ) {
        $allowed_blocks = apply_filters( 'allowed_block_types_all', true, get_post_type_object( $post_type ) );
        
        if ( is_array( $allowed_blocks ) ) {
            return $allowed_blocks;
        }
        
        return null;
    }

    /**
     * Get block patterns for a post type
     *
     * @param string $post_type Post type name
     * @return array Block patterns
     */
    private function get_block_patterns( $post_type ) {
        $patterns = WP_Block_Patterns_Registry::get_instance()->get_all_registered();
        $post_type_patterns = array();

        foreach ( $patterns as $pattern ) {
            if ( empty( $pattern['postTypes'] ) || in_array( $post_type, $pattern['postTypes'] ) ) {
                $post_type_patterns[] = array(
                    'name' => $pattern['name'],
                    'title' => $pattern['title'],
                    'categories' => $pattern['categories'] ?? array(),
                );
            }
        }

        return $post_type_patterns;
    }

    /**
     * Get overall post type statistics
     *
     * @return array Statistics
     */
    private function get_post_type_statistics() {
        global $wpdb;

        return array(
            'total_post_types' => count( get_post_types() ),
            'public_post_types' => count( get_post_types( array( 'public' => true ) ) ),
            'custom_post_types' => count( get_post_types( array( '_builtin' => false ) ) ),
            'total_posts' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status NOT IN ('auto-draft', 'inherit')" ),
            'total_meta_entries' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->postmeta}" ),
            'unique_meta_keys' => (int) $wpdb->get_var( "SELECT COUNT(DISTINCT meta_key) FROM {$wpdb->postmeta}" ),
        );
    }

    /**
     * Scan post type relationships
     *
     * @return array Relationship data
     */
    private function scan_post_type_relationships() {
        $relationships = array();
        $post_types = get_post_types( array(), 'objects' );

        foreach ( $post_types as $post_type_name => $post_type ) {
            // Get taxonomies
            $taxonomies = get_object_taxonomies( $post_type_name );
            if ( ! empty( $taxonomies ) ) {
                $relationships[ $post_type_name ]['taxonomies'] = $taxonomies;
            }

            // Check for parent-child relationships
            if ( $post_type->hierarchical ) {
                $relationships[ $post_type_name ]['hierarchical'] = true;
                $relationships[ $post_type_name ]['max_depth'] = $this->get_max_hierarchy_depth( $post_type_name );
            }

            // Check for post format support
            if ( post_type_supports( $post_type_name, 'post-formats' ) ) {
                $relationships[ $post_type_name ]['post_formats'] = get_theme_support( 'post-formats' )[0] ?? array();
            }
        }

        return $relationships;
    }

    /**
     * Get maximum hierarchy depth for a post type
     *
     * @param string $post_type Post type name
     * @return int Maximum depth
     */
    private function get_max_hierarchy_depth( $post_type ) {
        global $wpdb;
        
        $max_depth = 0;
        $posts = $wpdb->get_results( $wpdb->prepare(
            "SELECT ID, post_parent FROM {$wpdb->posts} WHERE post_type = %s AND post_status != 'auto-draft'",
            $post_type
        ) );

        foreach ( $posts as $post ) {
            $depth = 0;
            $parent = $post->post_parent;
            
            while ( $parent > 0 ) {
                $depth++;
                $parent_post = wp_cache_get( $parent, 'posts' );
                if ( ! $parent_post ) {
                    $parent_post = $wpdb->get_row( $wpdb->prepare(
                        "SELECT post_parent FROM {$wpdb->posts} WHERE ID = %d",
                        $parent
                    ) );
                }
                $parent = $parent_post ? $parent_post->post_parent : 0;
                
                if ( $depth > 10 ) {
                    break; // Prevent infinite loops
                }
            }
            
            $max_depth = max( $max_depth, $depth );
        }

        return $max_depth;
    }

    /**
     * Scan post type templates
     *
     * @return array Template data
     */
    private function scan_post_type_templates() {
        $templates = array();
        $post_types = get_post_types( array( 'public' => true ) );

        foreach ( $post_types as $post_type ) {
            // Get page templates if supported
            if ( post_type_supports( $post_type, 'page-attributes' ) ) {
                $page_templates = wp_get_theme()->get_page_templates( null, $post_type );
                if ( ! empty( $page_templates ) ) {
                    $templates[ $post_type ] = array_map( function( $file, $name ) {
                        return array(
                            'file' => $file,
                            'name' => $name,
                        );
                    }, array_keys( $page_templates ), $page_templates );
                }
            }
        }

        return $templates;
    }

    /**
     * Scan custom post statuses
     *
     * @return array Custom status data
     */
    private function scan_custom_post_statuses() {
        global $wp_post_statuses;
        
        $custom_statuses = array();
        
        foreach ( $wp_post_statuses as $status => $status_object ) {
            if ( ! in_array( $status, array( 'publish', 'future', 'draft', 'pending', 'private', 'trash', 'auto-draft', 'inherit' ) ) ) {
                $custom_statuses[ $status ] = array(
                    'label' => $status_object->label,
                    'public' => $status_object->public,
                    'exclude_from_search' => $status_object->exclude_from_search,
                    'show_in_admin_all_list' => $status_object->show_in_admin_all_list,
                    'show_in_admin_status_list' => $status_object->show_in_admin_status_list,
                    'label_count' => $status_object->label_count,
                );
            }
        }

        return $custom_statuses;
    }
}