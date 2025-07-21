<?php
/**
 * Markdown Formatter for WP Site Analyzer
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
 * Class WP_Site_Analyzer_Markdown_Formatter
 *
 * Formats scan results as Markdown documentation
 */
class WP_Site_Analyzer_Markdown_Formatter {

    /**
     * Format scan results as Markdown
     *
     * @param array $results Raw scan results
     * @return string Markdown formatted output
     */
    public function format( $results ) {
        $output = $this->generate_header();
        $output .= $this->generate_table_of_contents( $results );
        $output .= $this->generate_summary( $results );
        
        // Format each scanner's results
        if ( isset( $results['WP_Site_Analyzer_Post_Type_Scanner'] ) ) {
            $output .= $this->format_post_types( $results['WP_Site_Analyzer_Post_Type_Scanner'] );
        }
        
        if ( isset( $results['WP_Site_Analyzer_Taxonomy_Scanner'] ) ) {
            $output .= $this->format_taxonomies( $results['WP_Site_Analyzer_Taxonomy_Scanner'] );
        }
        
        if ( isset( $results['WP_Site_Analyzer_Custom_Fields_Scanner'] ) ) {
            $output .= $this->format_custom_fields( $results['WP_Site_Analyzer_Custom_Fields_Scanner'] );
        }
        
        if ( isset( $results['WP_Site_Analyzer_Plugin_Scanner'] ) ) {
            $output .= $this->format_plugins( $results['WP_Site_Analyzer_Plugin_Scanner'] );
        }
        
        if ( isset( $results['WP_Site_Analyzer_Theme_Scanner'] ) ) {
            $output .= $this->format_theme( $results['WP_Site_Analyzer_Theme_Scanner'] );
        }
        
        // Add theme styles if available
        if ( isset( $results['WP_Site_Analyzer_Theme_Style_Scanner'] ) ) {
            $output .= $this->format_theme_styles( $results['WP_Site_Analyzer_Theme_Style_Scanner'] );
        }
        
        $output .= $this->generate_footer();
        
        return $output;
    }

    /**
     * Generate document header
     *
     * @return string Header markdown
     */
    private function generate_header() {
        $site_name = get_bloginfo( 'name' );
        $site_url = get_site_url();
        $date = current_time( 'Y-m-d H:i:s' );
        
        return "# WordPress Site Analysis Report\n\n"
            . "**Site:** {$site_name}\n"
            . "**URL:** {$site_url}\n"
            . "**Generated:** {$date}\n"
            . "**WordPress Version:** " . get_bloginfo( 'version' ) . "\n"
            . "**PHP Version:** " . phpversion() . "\n\n"
            . "---\n\n";
    }

    /**
     * Generate table of contents
     *
     * @param array $results Scan results
     * @return string Table of contents
     */
    private function generate_table_of_contents( $results ) {
        $toc = "## Table of Contents\n\n";
        
        $sections = array(
            'summary' => 'Executive Summary',
            'post-types' => 'Post Types',
            'taxonomies' => 'Taxonomies',
            'custom-fields' => 'Custom Fields',
            'plugins' => 'Plugins',
            'theme' => 'Theme',
            'theme-styles' => 'Theme Styles & Design',
        );
        
        foreach ( $sections as $anchor => $title ) {
            $toc .= "- [{$title}](#{$anchor})\n";
        }
        
        return $toc . "\n---\n\n";
    }

    /**
     * Generate executive summary
     *
     * @param array $results Scan results
     * @return string Summary markdown
     */
    private function generate_summary( $results ) {
        $output = "## Executive Summary {#summary}\n\n";
        
        // Post type statistics
        if ( isset( $results['WP_Site_Analyzer_Post_Type_Scanner']['statistics'] ) ) {
            $stats = $results['WP_Site_Analyzer_Post_Type_Scanner']['statistics'];
            $output .= "### Content Statistics\n\n";
            $output .= "- **Total Post Types:** " . $stats['total_post_types'] . "\n";
            $output .= "- **Custom Post Types:** " . $stats['custom_post_types'] . "\n";
            $output .= "- **Total Posts:** " . number_format( $stats['total_posts'] ) . "\n";
            $output .= "- **Total Meta Entries:** " . number_format( $stats['total_meta_entries'] ) . "\n\n";
        }
        
        // Taxonomy statistics
        if ( isset( $results['WP_Site_Analyzer_Taxonomy_Scanner']['statistics'] ) ) {
            $stats = $results['WP_Site_Analyzer_Taxonomy_Scanner']['statistics'];
            $output .= "### Taxonomy Statistics\n\n";
            $output .= "- **Total Taxonomies:** " . $stats['total_taxonomies'] . "\n";
            $output .= "- **Custom Taxonomies:** " . $stats['custom_taxonomies'] . "\n";
            $output .= "- **Total Terms:** " . number_format( $stats['total_terms'] ) . "\n\n";
        }
        
        return $output . "---\n\n";
    }

    /**
     * Format post types section
     *
     * @param array $data Post type scanner data
     * @return string Post types markdown
     */
    private function format_post_types( $data ) {
        $output = "## Post Types {#post-types}\n\n";
        
        if ( ! isset( $data['post_types'] ) ) {
            return $output . "No post type data available.\n\n";
        }
        
        foreach ( $data['post_types'] as $post_type => $info ) {
            $output .= "### {$info['label']} (`{$post_type}`)\n\n";
            
            // Basic information
            $output .= "**Description:** " . ( $info['description'] ?: 'No description provided' ) . "\n\n";
            $output .= "**Type:** " . ( $info['_builtin'] ? 'Built-in' : 'Custom' ) . "\n";
            $output .= "**Count:** " . number_format( $info['count'] ) . " items\n";
            $output .= "**Public:** " . ( $info['public'] ? 'Yes' : 'No' ) . "\n";
            $output .= "**Hierarchical:** " . ( $info['hierarchical'] ? 'Yes' : 'No' ) . "\n";
            
            // REST API
            if ( $info['show_in_rest'] ) {
                $output .= "**REST API:** Enabled (base: `" . ( $info['rest_base'] ?: $post_type ) . "`)\n";
            }
            
            // Supports
            if ( ! empty( $info['supports'] ) ) {
                $output .= "\n**Supports:**\n";
                foreach ( $info['supports'] as $feature ) {
                    $output .= "- {$feature}\n";
                }
            }
            
            // Taxonomies
            if ( ! empty( $info['taxonomies'] ) ) {
                $output .= "\n**Taxonomies:**\n";
                foreach ( $info['taxonomies'] as $taxonomy ) {
                    $output .= "- {$taxonomy}\n";
                }
            }
            
            // Custom fields
            if ( ! empty( $info['meta_fields'] ) ) {
                $output .= "\n**Custom Fields:** " . count( $info['meta_fields'] ) . " fields detected\n";
                
                // Show first 5 fields
                $output .= "\n<details>\n<summary>View custom fields</summary>\n\n";
                $output .= "| Field Key | Type | Usage Count |\n";
                $output .= "|-----------|------|-------------|\n";
                
                $shown = 0;
                foreach ( $info['meta_fields'] as $field ) {
                    if ( $shown++ >= 5 ) break;
                    $output .= "| `{$field['key']}` | {$field['type']} | {$field['usage_count']} |\n";
                }
                
                if ( count( $info['meta_fields'] ) > 5 ) {
                    $output .= "\n*... and " . ( count( $info['meta_fields'] ) - 5 ) . " more fields*\n";
                }
                
                $output .= "\n</details>\n";
            }
            
            $output .= "\n---\n\n";
        }
        
        return $output;
    }

    /**
     * Format taxonomies section
     *
     * @param array $data Taxonomy scanner data
     * @return string Taxonomies markdown
     */
    private function format_taxonomies( $data ) {
        $output = "## Taxonomies {#taxonomies}\n\n";
        
        if ( ! isset( $data['taxonomies'] ) ) {
            return $output . "No taxonomy data available.\n\n";
        }
        
        foreach ( $data['taxonomies'] as $taxonomy => $info ) {
            $output .= "### {$info['label']} (`{$taxonomy}`)\n\n";
            
            // Basic information
            $output .= "**Description:** " . ( $info['description'] ?: 'No description provided' ) . "\n\n";
            $output .= "**Type:** " . ( $info['_builtin'] ? 'Built-in' : 'Custom' ) . "\n";
            $output .= "**Terms:** " . number_format( $info['term_count'] ) . "\n";
            $output .= "**Hierarchical:** " . ( $info['hierarchical'] ? 'Yes' : 'No' ) . "\n";
            
            // Post types
            if ( ! empty( $info['object_type'] ) ) {
                $output .= "**Applies to:** " . implode( ', ', array_map( function( $pt ) {
                    return "`{$pt}`";
                }, $info['object_type'] ) ) . "\n";
            }
            
            // REST API
            if ( $info['show_in_rest'] ) {
                $output .= "**REST API:** Enabled (base: `" . ( $info['rest_base'] ?: $taxonomy ) . "`)\n";
            }
            
            // Usage statistics
            if ( isset( $info['usage_stats'] ) && ! empty( $info['usage_stats']['most_used_terms'] ) ) {
                $output .= "\n**Most Used Terms:**\n";
                foreach ( $info['usage_stats']['most_used_terms'] as $term ) {
                    $output .= "- {$term['name']} ({$term['count']} posts)\n";
                }
            }
            
            $output .= "\n---\n\n";
        }
        
        return $output;
    }

    /**
     * Format custom fields section
     *
     * @param array $data Custom fields scanner data
     * @return string Custom fields markdown
     */
    private function format_custom_fields( $data ) {
        $output = "## Custom Fields {#custom-fields}\n\n";
        
        if ( isset( $data['summary'] ) ) {
            $output .= "### Summary\n\n";
            $output .= "- **Total Meta Keys:** " . $data['summary']['total_meta_keys'] . "\n";
            $output .= "- **Post Meta Keys:** " . $data['summary']['post_meta_keys'] . "\n";
            $output .= "- **Term Meta Keys:** " . $data['summary']['term_meta_keys'] . "\n";
            $output .= "- **User Meta Keys:** " . $data['summary']['user_meta_keys'] . "\n\n";
        }
        
        // ACF Fields
        if ( isset( $data['acf_fields'] ) && $data['acf_fields']['available'] ) {
            $output .= "### Advanced Custom Fields (ACF)\n\n";
            $output .= "**Field Groups:** " . $data['acf_fields']['field_groups'] . "\n\n";
            
            if ( ! empty( $data['acf_fields']['groups'] ) ) {
                foreach ( $data['acf_fields']['groups'] as $group ) {
                    $output .= "- **{$group['title']}** ({$group['field_count']} fields)\n";
                }
            }
            $output .= "\n";
        }
        
        return $output . "---\n\n";
    }

    /**
     * Format plugins section
     *
     * @param array $data Plugin scanner data
     * @return string Plugins markdown
     */
    private function format_plugins( $data ) {
        $output = "## Plugins {#plugins}\n\n";
        
        if ( isset( $data['active_plugins'] ) ) {
            $output .= "### Active Plugins ({$data['active_plugins']['count']})\n\n";
            
            foreach ( $data['active_plugins']['plugins'] as $plugin ) {
                $output .= "- **{$plugin['name']}** v{$plugin['version']}\n";
                if ( $plugin['author'] ) {
                    $output .= "  - Author: {$plugin['author']}\n";
                }
            }
            $output .= "\n";
        }
        
        if ( isset( $data['must_use_plugins'] ) && $data['must_use_plugins']['count'] > 0 ) {
            $output .= "### Must-Use Plugins ({$data['must_use_plugins']['count']})\n\n";
            
            foreach ( $data['must_use_plugins']['plugins'] as $plugin ) {
                $output .= "- **{$plugin['name']}** v{$plugin['version']}\n";
            }
            $output .= "\n";
        }
        
        return $output . "---\n\n";
    }

    /**
     * Format theme section
     *
     * @param array $data Theme scanner data
     * @return string Theme markdown
     */
    private function format_theme( $data ) {
        $output = "## Theme {#theme}\n\n";
        
        if ( isset( $data['active_theme'] ) ) {
            $theme = $data['active_theme'];
            $output .= "### Active Theme\n\n";
            $output .= "**Name:** {$theme['name']} v{$theme['version']}\n";
            $output .= "**Author:** {$theme['author']}\n";
            
            if ( $theme['is_child_theme'] ) {
                $output .= "**Type:** Child Theme\n";
                if ( isset( $data['child_theme']['parent_theme'] ) ) {
                    $output .= "**Parent Theme:** {$data['child_theme']['parent_theme']['name']} v{$data['child_theme']['parent_theme']['version']}\n";
                }
            }
            
            $output .= "\n";
        }
        
        if ( isset( $data['theme_support'] ) && ! empty( $data['theme_support'] ) ) {
            $output .= "### Theme Support\n\n";
            foreach ( $data['theme_support'] as $feature => $value ) {
                $output .= "- {$feature}\n";
            }
            $output .= "\n";
        }
        
        if ( isset( $data['template_files'] ) ) {
            $output .= "### Template Files\n\n";
            $output .= "**Total Templates:** " . $data['template_files']['template_count'] . "\n\n";
            
            if ( ! empty( $data['template_files']['page_templates'] ) ) {
                $output .= "**Custom Page Templates:**\n";
                foreach ( $data['template_files']['page_templates'] as $file => $name ) {
                    $output .= "- {$name} (`{$file}`)\n";
                }
            }
        }
        
        return $output . "\n---\n\n";
    }

    /**
     * Format theme styles
     *
     * @param array $data Theme style scanner data
     * @return string Formatted markdown
     */
    private function format_theme_styles( $data ) {
        $output = "## Theme Styles & Design {#theme-styles}\n\n";
        
        // Use the theme style formatter to generate the content
        $formatter = new WP_Site_Analyzer_Theme_Style_Formatter();
        $style_report = $formatter->format( $data );
        
        // Extract just the relevant sections (skip the header and TOC)
        $lines = explode( "\n", $style_report );
        $start_capturing = false;
        $filtered_lines = array();
        
        foreach ( $lines as $line ) {
            // Start capturing after the TOC
            if ( strpos( $line, '## Theme Information' ) !== false ) {
                $start_capturing = true;
            }
            
            // Stop before Implementation Guide (we'll have our own at the end)
            if ( strpos( $line, '## Implementation Guide' ) !== false ) {
                break;
            }
            
            if ( $start_capturing ) {
                // Adjust heading levels (## becomes ###, ### becomes ####)
                if ( preg_match( '/^##\s+(.+)/', $line, $matches ) ) {
                    $line = '### ' . $matches[1];
                } elseif ( preg_match( '/^###\s+(.+)/', $line, $matches ) ) {
                    $line = '#### ' . $matches[1];
                }
                $filtered_lines[] = $line;
            }
        }
        
        $output .= implode( "\n", $filtered_lines );
        
        return $output . "\n---\n\n";
    }

    /**
     * Generate document footer
     *
     * @return string Footer markdown
     */
    private function generate_footer() {
        return "\n---\n\n"
            . "*Generated by WP Site Analyzer v" . WP_SITE_ANALYZER_VERSION . "*\n"
            . "*Report generated on " . current_time( 'Y-m-d \a\t H:i:s' ) . "*\n";
    }
}