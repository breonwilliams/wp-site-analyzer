<?php
/**
 * AI-Optimized Formatter for WP Site Analyzer
 *
 * @package WP_Site_Analyzer
 * @subpackage Formatters
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class WP_Site_Analyzer_AI_Formatter
 *
 * Formats scan results in an AI-friendly structure with context and relationships
 */
class WP_Site_Analyzer_AI_Formatter {

    /**
     * Format scan results for AI consumption
     *
     * @param array $results Raw scan results
     * @return string Formatted output
     */
    public function format( $results ) {
        $formatted = array(
            'meta' => $this->generate_meta_information(),
            'summary' => $this->generate_summary( $results ),
            'architecture' => $this->format_architecture( $results ),
            'relationships' => $this->format_relationships( $results ),
            'development_context' => $this->generate_development_context( $results ),
            'recommendations' => $this->generate_recommendations( $results ),
            'detailed_analysis' => $this->format_detailed_analysis( $results ),
        );

        return json_encode( $formatted, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
    }

    /**
     * Generate meta information about the scan
     *
     * @return array Meta information
     */
    private function generate_meta_information() {
        return array(
            'generator' => 'WP Site Analyzer v' . WP_SITE_ANALYZER_VERSION,
            'scan_date' => current_time( 'c' ),
            'wordpress_version' => get_bloginfo( 'version' ),
            'php_version' => phpversion(),
            'site_url' => get_site_url(),
            'active_theme' => wp_get_theme()->get( 'Name' ),
            'multisite' => is_multisite(),
            'purpose' => 'This document provides a comprehensive analysis of a WordPress site\'s structure, designed to help AI assistants understand the site architecture for development tasks.',
        );
    }

    /**
     * Generate executive summary
     *
     * @param array $results Scan results
     * @return array Summary data
     */
    private function generate_summary( $results ) {
        $summary = array(
            'overview' => 'WordPress site with custom content architecture',
            'key_metrics' => array(),
            'complexity_score' => $this->calculate_complexity_score( $results ),
            'primary_focus' => $this->determine_site_focus( $results ),
        );

        // Extract key metrics from results
        if ( isset( $results['WP_Site_Analyzer_Post_Type_Scanner']['statistics'] ) ) {
            $post_stats = $results['WP_Site_Analyzer_Post_Type_Scanner']['statistics'];
            $summary['key_metrics']['total_content_items'] = $post_stats['total_posts'] ?? 0;
            $summary['key_metrics']['content_types'] = $post_stats['total_post_types'] ?? 0;
            $summary['key_metrics']['custom_content_types'] = $post_stats['custom_post_types'] ?? 0;
        }

        if ( isset( $results['WP_Site_Analyzer_Taxonomy_Scanner']['statistics'] ) ) {
            $tax_stats = $results['WP_Site_Analyzer_Taxonomy_Scanner']['statistics'];
            $summary['key_metrics']['taxonomies'] = $tax_stats['total_taxonomies'] ?? 0;
            $summary['key_metrics']['custom_taxonomies'] = $tax_stats['custom_taxonomies'] ?? 0;
            $summary['key_metrics']['total_terms'] = $tax_stats['total_terms'] ?? 0;
        }

        return $summary;
    }

    /**
     * Format site architecture
     *
     * @param array $results Scan results
     * @return array Architecture data
     */
    private function format_architecture( $results ) {
        $architecture = array(
            'content_structure' => array(),
            'data_relationships' => array(),
            'url_patterns' => array(),
            'template_hierarchy' => array(),
        );

        // Process post types
        if ( isset( $results['WP_Site_Analyzer_Post_Type_Scanner']['post_types'] ) ) {
            foreach ( $results['WP_Site_Analyzer_Post_Type_Scanner']['post_types'] as $post_type => $data ) {
                $architecture['content_structure'][ $post_type ] = array(
                    'label' => $data['label'],
                    'description' => $data['description'] ?: "Content type for {$data['label']}",
                    'hierarchical' => $data['hierarchical'],
                    'public' => $data['public'],
                    'rest_enabled' => $data['show_in_rest'],
                    'supports' => $data['supports'],
                    'taxonomies' => $data['taxonomies'],
                    'count' => $data['count'],
                    'custom_fields' => $this->summarize_custom_fields( $data['meta_fields'] ),
                    'url_structure' => $this->generate_url_pattern( $post_type, $data ),
                    'development_notes' => $this->generate_post_type_notes( $post_type, $data ),
                );

                // Add template hierarchy if available
                if ( isset( $data['template_hierarchy'] ) ) {
                    $architecture['template_hierarchy'][ $post_type ] = $data['template_hierarchy'];
                }
            }
        }

        return $architecture;
    }

    /**
     * Format relationships between content types
     *
     * @param array $results Scan results
     * @return array Relationships
     */
    private function format_relationships( $results ) {
        $relationships = array(
            'post_type_taxonomies' => array(),
            'taxonomy_connections' => array(),
            'hierarchical_structures' => array(),
            'meta_relationships' => array(),
        );

        // Map post types to taxonomies
        if ( isset( $results['WP_Site_Analyzer_Post_Type_Scanner']['relationships'] ) ) {
            foreach ( $results['WP_Site_Analyzer_Post_Type_Scanner']['relationships'] as $post_type => $data ) {
                if ( isset( $data['taxonomies'] ) && ! empty( $data['taxonomies'] ) ) {
                    $relationships['post_type_taxonomies'][ $post_type ] = array(
                        'taxonomies' => $data['taxonomies'],
                        'description' => "The {$post_type} post type can be organized using: " . implode( ', ', $data['taxonomies'] ),
                    );
                }

                if ( isset( $data['hierarchical'] ) && $data['hierarchical'] ) {
                    $relationships['hierarchical_structures'][ $post_type ] = array(
                        'max_depth' => $data['max_depth'] ?? 0,
                        'description' => "The {$post_type} post type supports parent-child relationships",
                    );
                }
            }
        }

        // Map taxonomy relationships
        if ( isset( $results['WP_Site_Analyzer_Taxonomy_Scanner']['relationships'] ) ) {
            foreach ( $results['WP_Site_Analyzer_Taxonomy_Scanner']['relationships'] as $taxonomy => $data ) {
                $relationships['taxonomy_connections'][ $taxonomy ] = array(
                    'applies_to' => $data['post_types'],
                    'shared_with' => array_keys( $data['shared_with'] ?? array() ),
                    'has_meta' => $data['has_term_meta'] ?? false,
                );
            }
        }

        return $relationships;
    }

    /**
     * Generate development context
     *
     * @param array $results Scan results
     * @return array Development context
     */
    private function generate_development_context( $results ) {
        $context = array(
            'key_functions' => $this->generate_key_functions( $results ),
            'common_patterns' => $this->identify_common_patterns( $results ),
            'hooks_and_filters' => $this->generate_hooks_reference( $results ),
            'query_examples' => $this->generate_query_examples( $results ),
            'security_considerations' => $this->generate_security_notes( $results ),
        );

        return $context;
    }

    /**
     * Generate key WordPress functions for the site
     *
     * @param array $results Scan results
     * @return array Key functions
     */
    private function generate_key_functions( $results ) {
        $functions = array(
            'content_retrieval' => array(),
            'taxonomy_functions' => array(),
            'meta_functions' => array(),
        );

        // Add post type specific functions
        if ( isset( $results['WP_Site_Analyzer_Post_Type_Scanner']['post_types'] ) ) {
            foreach ( $results['WP_Site_Analyzer_Post_Type_Scanner']['post_types'] as $post_type => $data ) {
                if ( ! $data['_builtin'] ) {
                    $functions['content_retrieval'][] = array(
                        'function' => "get_posts( array( 'post_type' => '{$post_type}' ) )",
                        'description' => "Retrieve {$data['label']} posts",
                    );
                    
                    $functions['content_retrieval'][] = array(
                        'function' => "new WP_Query( array( 'post_type' => '{$post_type}' ) )",
                        'description' => "Query {$data['label']} with advanced options",
                    );
                }
            }
        }

        // Add taxonomy specific functions
        if ( isset( $results['WP_Site_Analyzer_Taxonomy_Scanner']['taxonomies'] ) ) {
            foreach ( $results['WP_Site_Analyzer_Taxonomy_Scanner']['taxonomies'] as $taxonomy => $data ) {
                if ( ! $data['_builtin'] ) {
                    $functions['taxonomy_functions'][] = array(
                        'function' => "get_terms( array( 'taxonomy' => '{$taxonomy}' ) )",
                        'description' => "Get all {$data['label']} terms",
                    );
                    
                    $functions['taxonomy_functions'][] = array(
                        'function' => "wp_get_post_terms( \$post_id, '{$taxonomy}' )",
                        'description' => "Get {$data['label']} for a specific post",
                    );
                }
            }
        }

        return $functions;
    }

    /**
     * Identify common patterns in the site
     *
     * @param array $results Scan results
     * @return array Common patterns
     */
    private function identify_common_patterns( $results ) {
        $patterns = array();

        // Check for common plugin patterns
        if ( isset( $results['WP_Site_Analyzer_Post_Type_Scanner']['post_types'] ) ) {
            $post_types = array_keys( $results['WP_Site_Analyzer_Post_Type_Scanner']['post_types'] );
            
            // E-commerce pattern
            if ( in_array( 'product', $post_types ) ) {
                $patterns['e-commerce'] = array(
                    'detected' => true,
                    'description' => 'Site appears to have e-commerce functionality with product post type',
                    'key_elements' => array( 'products', 'orders', 'customers' ),
                );
            }

            // Events pattern
            if ( array_intersect( array( 'event', 'events', 'tribe_events' ), $post_types ) ) {
                $patterns['events'] = array(
                    'detected' => true,
                    'description' => 'Site has event management functionality',
                    'key_elements' => array( 'events', 'venues', 'organizers' ),
                );
            }

            // Portfolio pattern
            if ( array_intersect( array( 'portfolio', 'project', 'work' ), $post_types ) ) {
                $patterns['portfolio'] = array(
                    'detected' => true,
                    'description' => 'Site includes portfolio or project showcase functionality',
                    'key_elements' => array( 'projects', 'clients', 'services' ),
                );
            }
        }

        return $patterns;
    }

    /**
     * Generate hooks reference
     *
     * @param array $results Scan results
     * @return array Hooks reference
     */
    private function generate_hooks_reference( $results ) {
        $hooks = array(
            'post_type_hooks' => array(),
            'taxonomy_hooks' => array(),
            'general_hooks' => array(
                array(
                    'hook' => 'init',
                    'description' => 'Register custom post types and taxonomies',
                ),
                array(
                    'hook' => 'pre_get_posts',
                    'description' => 'Modify main queries for custom content',
                ),
                array(
                    'hook' => 'template_redirect',
                    'description' => 'Handle custom routing and redirects',
                ),
            ),
        );

        // Add post type specific hooks
        if ( isset( $results['WP_Site_Analyzer_Post_Type_Scanner']['post_types'] ) ) {
            foreach ( $results['WP_Site_Analyzer_Post_Type_Scanner']['post_types'] as $post_type => $data ) {
                if ( ! $data['_builtin'] ) {
                    $hooks['post_type_hooks'][] = array(
                        'hook' => "save_post_{$post_type}",
                        'description' => "Triggered when saving a {$data['label']}",
                    );
                    
                    $hooks['post_type_hooks'][] = array(
                        'hook' => "manage_{$post_type}_posts_columns",
                        'description' => "Customize admin columns for {$data['label']}",
                    );
                }
            }
        }

        return $hooks;
    }

    /**
     * Generate query examples
     *
     * @param array $results Scan results
     * @return array Query examples
     */
    private function generate_query_examples( $results ) {
        $examples = array();

        if ( isset( $results['WP_Site_Analyzer_Post_Type_Scanner']['post_types'] ) ) {
            foreach ( $results['WP_Site_Analyzer_Post_Type_Scanner']['post_types'] as $post_type => $data ) {
                if ( ! $data['_builtin'] && $data['public'] ) {
                    $example = array(
                        'post_type' => $post_type,
                        'label' => $data['label'],
                        'basic_query' => "
\$args = array(
    'post_type' => '{$post_type}',
    'posts_per_page' => 10,
    'post_status' => 'publish'
);
\$query = new WP_Query( \$args );",
                    );

                    // Add taxonomy query if applicable
                    if ( ! empty( $data['taxonomies'] ) ) {
                        $taxonomy = $data['taxonomies'][0];
                        $example['taxonomy_query'] = "
\$args = array(
    'post_type' => '{$post_type}',
    'tax_query' => array(
        array(
            'taxonomy' => '{$taxonomy}',
            'field' => 'slug',
            'terms' => 'example-term'
        )
    )
);
\$query = new WP_Query( \$args );";
                    }

                    // Add meta query if custom fields exist
                    if ( ! empty( $data['meta_fields'] ) ) {
                        $meta_key = $data['meta_fields'][0]['key'] ?? 'meta_key';
                        $example['meta_query'] = "
\$args = array(
    'post_type' => '{$post_type}',
    'meta_query' => array(
        array(
            'key' => '{$meta_key}',
            'value' => 'example_value',
            'compare' => '='
        )
    )
);
\$query = new WP_Query( \$args );";
                    }

                    $examples[] = $example;
                }
            }
        }

        return $examples;
    }

    /**
     * Generate security notes
     *
     * @param array $results Scan results
     * @return array Security considerations
     */
    private function generate_security_notes( $results ) {
        $security = array(
            'capabilities' => array(),
            'data_validation' => array(),
            'best_practices' => array(
                'Always validate and sanitize user input',
                'Check user capabilities before allowing actions',
                'Use nonces for form submissions',
                'Escape output when displaying data',
                'Validate file uploads if applicable',
            ),
        );

        // Add capability requirements
        if ( isset( $results['WP_Site_Analyzer_Post_Type_Scanner']['post_types'] ) ) {
            foreach ( $results['WP_Site_Analyzer_Post_Type_Scanner']['post_types'] as $post_type => $data ) {
                if ( ! $data['_builtin'] && isset( $data['capabilities'] ) ) {
                    $security['capabilities'][ $post_type ] = array(
                        'edit' => $data['capabilities']['edit_post'] ?? "edit_{$post_type}",
                        'publish' => $data['capabilities']['publish_posts'] ?? "publish_{$post_type}s",
                        'delete' => $data['capabilities']['delete_post'] ?? "delete_{$post_type}",
                    );
                }
            }
        }

        return $security;
    }

    /**
     * Generate recommendations
     *
     * @param array $results Scan results
     * @return array Recommendations
     */
    private function generate_recommendations( $results ) {
        $recommendations = array(
            'performance' => array(),
            'development' => array(),
            'maintenance' => array(),
        );

        // Performance recommendations
        if ( isset( $results['WP_Site_Analyzer_Post_Type_Scanner']['statistics'] ) ) {
            $stats = $results['WP_Site_Analyzer_Post_Type_Scanner']['statistics'];
            
            if ( $stats['total_posts'] > 10000 ) {
                $recommendations['performance'][] = 'Consider implementing pagination and lazy loading for large content lists';
            }
            
            if ( $stats['total_meta_entries'] > 100000 ) {
                $recommendations['performance'][] = 'Large meta table detected - consider indexing frequently queried meta keys';
            }
        }

        // Development recommendations
        $recommendations['development'][] = 'Use WP_Query instead of query_posts() for custom queries';
        $recommendations['development'][] = 'Implement proper error handling for all database operations';
        $recommendations['development'][] = 'Follow WordPress coding standards for consistency';

        return $recommendations;
    }

    /**
     * Format detailed analysis
     *
     * @param array $results Scan results
     * @return array Detailed analysis
     */
    private function format_detailed_analysis( $results ) {
        // Include the raw results but organized by component
        return array(
            'post_types' => $results['WP_Site_Analyzer_Post_Type_Scanner'] ?? array(),
            'taxonomies' => $results['WP_Site_Analyzer_Taxonomy_Scanner'] ?? array(),
            'custom_fields' => $results['WP_Site_Analyzer_Custom_Fields_Scanner'] ?? array(),
            'database' => $results['WP_Site_Analyzer_Database_Scanner'] ?? array(),
            'plugins' => $results['WP_Site_Analyzer_Plugin_Scanner'] ?? array(),
            'theme' => $results['WP_Site_Analyzer_Theme_Scanner'] ?? array(),
            'security' => $results['WP_Site_Analyzer_Security_Scanner'] ?? array(),
        );
    }

    /**
     * Calculate site complexity score
     *
     * @param array $results Scan results
     * @return array Complexity score with breakdown
     */
    private function calculate_complexity_score( $results ) {
        $score = 0;
        $factors = array();

        // Post type complexity
        if ( isset( $results['WP_Site_Analyzer_Post_Type_Scanner']['statistics'] ) ) {
            $custom_types = $results['WP_Site_Analyzer_Post_Type_Scanner']['statistics']['custom_post_types'] ?? 0;
            $score += $custom_types * 10;
            $factors['custom_post_types'] = $custom_types;
        }

        // Taxonomy complexity
        if ( isset( $results['WP_Site_Analyzer_Taxonomy_Scanner']['statistics'] ) ) {
            $custom_tax = $results['WP_Site_Analyzer_Taxonomy_Scanner']['statistics']['custom_taxonomies'] ?? 0;
            $score += $custom_tax * 8;
            $factors['custom_taxonomies'] = $custom_tax;
        }

        // Meta complexity
        if ( isset( $results['WP_Site_Analyzer_Post_Type_Scanner']['statistics'] ) ) {
            $meta_keys = $results['WP_Site_Analyzer_Post_Type_Scanner']['statistics']['unique_meta_keys'] ?? 0;
            $score += min( $meta_keys, 50 ); // Cap at 50 points
            $factors['unique_meta_keys'] = $meta_keys;
        }

        return array(
            'score' => $score,
            'level' => $this->get_complexity_level( $score ),
            'factors' => $factors,
        );
    }

    /**
     * Get complexity level from score
     *
     * @param int $score Complexity score
     * @return string Complexity level
     */
    private function get_complexity_level( $score ) {
        if ( $score < 20 ) {
            return 'Simple';
        } elseif ( $score < 50 ) {
            return 'Moderate';
        } elseif ( $score < 100 ) {
            return 'Complex';
        } else {
            return 'Very Complex';
        }
    }

    /**
     * Determine site focus
     *
     * @param array $results Scan results
     * @return string Site focus
     */
    private function determine_site_focus( $results ) {
        if ( isset( $results['WP_Site_Analyzer_Post_Type_Scanner']['post_types'] ) ) {
            $post_types = array_keys( $results['WP_Site_Analyzer_Post_Type_Scanner']['post_types'] );
            
            if ( in_array( 'product', $post_types ) ) {
                return 'E-commerce';
            } elseif ( array_intersect( array( 'event', 'events' ), $post_types ) ) {
                return 'Events';
            } elseif ( array_intersect( array( 'portfolio', 'project' ), $post_types ) ) {
                return 'Portfolio/Agency';
            } elseif ( array_intersect( array( 'course', 'lesson' ), $post_types ) ) {
                return 'Learning Management';
            } elseif ( array_intersect( array( 'property', 'listing' ), $post_types ) ) {
                return 'Real Estate';
            } elseif ( array_intersect( array( 'job', 'job_listing' ), $post_types ) ) {
                return 'Job Board';
            }
        }

        return 'Content/Blog';
    }

    /**
     * Summarize custom fields
     *
     * @param array $meta_fields Meta fields data
     * @return array Summarized fields
     */
    private function summarize_custom_fields( $meta_fields ) {
        $summary = array(
            'count' => count( $meta_fields ),
            'types' => array(),
            'key_fields' => array(),
        );

        foreach ( $meta_fields as $field ) {
            // Count field types
            $type = $field['type'] ?? 'string';
            if ( ! isset( $summary['types'][ $type ] ) ) {
                $summary['types'][ $type ] = 0;
            }
            $summary['types'][ $type ]++;

            // Include important fields
            if ( $field['usage_count'] > 10 || $field['is_registered'] ) {
                $summary['key_fields'][] = array(
                    'key' => $field['key'],
                    'type' => $type,
                    'usage' => $field['usage_count'],
                );
            }
        }

        return $summary;
    }

    /**
     * Generate URL pattern for post type
     *
     * @param string $post_type Post type name
     * @param array $data Post type data
     * @return string URL pattern
     */
    private function generate_url_pattern( $post_type, $data ) {
        $home_url = trailingslashit( home_url() );
        
        if ( isset( $data['rewrite']['slug'] ) ) {
            return $home_url . $data['rewrite']['slug'] . '/%postname%/';
        } elseif ( $data['public'] ) {
            return $home_url . $post_type . '/%postname%/';
        } else {
            return 'Not publicly accessible';
        }
    }

    /**
     * Generate post type development notes
     *
     * @param string $post_type Post type name
     * @param array $data Post type data
     * @return array Development notes
     */
    private function generate_post_type_notes( $post_type, $data ) {
        $notes = array();

        if ( $data['show_in_rest'] ) {
            $notes[] = 'REST API enabled - can be accessed via /wp-json/wp/v2/' . ( $data['rest_base'] ?: $post_type );
        }

        if ( $data['hierarchical'] ) {
            $notes[] = 'Supports parent-child relationships like Pages';
        }

        if ( in_array( 'thumbnail', $data['supports'] ) ) {
            $notes[] = 'Supports featured images';
        }

        if ( ! empty( $data['taxonomies'] ) ) {
            $notes[] = 'Can be organized with: ' . implode( ', ', $data['taxonomies'] );
        }

        if ( $data['has_archive'] ) {
            $notes[] = 'Has archive page at /' . ( is_string( $data['has_archive'] ) ? $data['has_archive'] : $post_type );
        }

        return $notes;
    }
}