<?php
/**
 * Theme Style Formatter
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
 * Class WP_Site_Analyzer_Theme_Style_Formatter
 * 
 * Formats theme style analysis results for AI consumption
 */
class WP_Site_Analyzer_Theme_Style_Formatter {
    
    /**
     * Format scan results
     *
     * @param array $results Scan results
     * @return string Formatted markdown
     */
    public function format( $results ) {
        $output = array();
        
        // Header
        $output[] = '# WordPress Theme Style Guide';
        $output[] = '';
        $output[] = '*Generated on ' . date( 'Y-m-d H:i:s' ) . '*';
        $output[] = '';
        
        // Table of Contents
        $output[] = '## Table of Contents';
        $output[] = '';
        $output[] = '- [Theme Information](#theme-information)';
        $output[] = '- [Color Palette](#color-palette)';
        $output[] = '- [Typography](#typography)';
        $output[] = '- [Spacing System](#spacing-system)';
        $output[] = '- [Component Styles](#component-styles)';
        $output[] = '- [Layout System](#layout-system)';
        $output[] = '- [Visual Effects](#visual-effects)';
        $output[] = '- [CSS Variables](#css-variables)';
        $output[] = '- [Computed Styles](#computed-styles)';
        $output[] = '- [Implementation Guide](#implementation-guide)';
        $output[] = '';
        
        // Theme Information
        if ( isset( $results['data']['theme_info'] ) ) {
            $output[] = '## Theme Information {#theme-information}';
            $output[] = '';
            $this->format_theme_info( $results['data']['theme_info'], $output );
            $output[] = '';
        }
        
        // Color Palette
        if ( isset( $results['data']['color_palette'] ) ) {
            $output[] = '## Color Palette {#color-palette}';
            $output[] = '';
            $this->format_color_palette( $results['data']['color_palette'], $output );
            $output[] = '';
        }
        
        // Typography
        if ( isset( $results['data']['typography'] ) ) {
            $output[] = '## Typography {#typography}';
            $output[] = '';
            $this->format_typography( $results['data']['typography'], $output );
            $output[] = '';
        }
        
        // Spacing System
        if ( isset( $results['data']['spacing'] ) ) {
            $output[] = '## Spacing System {#spacing-system}';
            $output[] = '';
            $this->format_spacing( $results['data']['spacing'], $output );
            $output[] = '';
        }
        
        // Component Styles
        if ( isset( $results['data']['components'] ) ) {
            $output[] = '## Component Styles {#component-styles}';
            $output[] = '';
            $this->format_components( $results['data']['components'], $output );
            $output[] = '';
        }
        
        // Layout System
        if ( isset( $results['data']['layout'] ) ) {
            $output[] = '## Layout System {#layout-system}';
            $output[] = '';
            $this->format_layout( $results['data']['layout'], $output );
            $output[] = '';
        }
        
        // Visual Effects
        if ( isset( $results['data']['effects'] ) ) {
            $output[] = '## Visual Effects {#visual-effects}';
            $output[] = '';
            $this->format_effects( $results['data']['effects'], $output );
            $output[] = '';
        }
        
        // CSS Variables
        if ( isset( $results['data']['css_variables'] ) && ! empty( $results['data']['css_variables'] ) ) {
            $output[] = '## CSS Variables {#css-variables}';
            $output[] = '';
            $this->format_css_variables( $results['data']['css_variables'], $output );
            $output[] = '';
        }
        
        // Computed Styles
        if ( isset( $results['data']['computed_styles'] ) && ! empty( $results['data']['computed_styles'] ) ) {
            $output[] = '## Computed Styles {#computed-styles}';
            $output[] = '';
            $this->format_computed_styles( $results['data']['computed_styles'], $output );
            $output[] = '';
        }
        
        // Implementation Guide
        $output[] = '## Implementation Guide {#implementation-guide}';
        $output[] = '';
        $this->format_implementation_guide( $results, $output );
        
        return implode( "\n", $output );
    }
    
    /**
     * Format theme information
     */
    private function format_theme_info( $info, &$output ) {
        $output[] = '| Property | Value |';
        $output[] = '|----------|-------|';
        $output[] = '| Theme Name | ' . ( $info['name'] ?? 'Unknown' ) . ' |';
        $output[] = '| Version | ' . ( $info['version'] ?? 'Unknown' ) . ' |';
        $output[] = '| Author | ' . ( $info['author'] ?? 'Unknown' ) . ' |';
        $output[] = '| Text Domain | ' . ( $info['text_domain'] ?? 'Unknown' ) . ' |';
        
        if ( $info['is_child_theme'] ) {
            $output[] = '| Parent Theme | ' . ( $info['parent_theme'] ?? 'Unknown' ) . ' |';
        }
        
        if ( ! empty( $info['theme_supports'] ) ) {
            $output[] = '';
            $output[] = '### Theme Support Features';
            $output[] = '';
            foreach ( $info['theme_supports'] as $feature => $supported ) {
                if ( $supported ) {
                    $output[] = '- ' . str_replace( '-', ' ', ucwords( $feature ) );
                }
            }
        }
    }
    
    /**
     * Format color palette
     */
    private function format_color_palette( $colors, &$output ) {
        $output[] = '### Primary Colors';
        $output[] = '';
        
        if ( ! empty( $colors['css_colors'] ) ) {
            $output[] = '```css';
            $output[] = '/* Theme Color Variables */';
            foreach ( $colors['css_colors'] as $name => $value ) {
                $output[] = '--color-' . str_replace( '_', '-', $name ) . ': ' . $value . ';';
            }
            $output[] = '```';
            $output[] = '';
        }
        
        if ( ! empty( $colors['editor_palette'] ) ) {
            $output[] = '### Editor Color Palette';
            $output[] = '';
            $output[] = '| Name | Slug | Color |';
            $output[] = '|------|------|-------|';
            foreach ( $colors['editor_palette'] as $color ) {
                $output[] = '| ' . $color['name'] . ' | ' . $color['slug'] . ' | ' . $color['color'] . ' |';
            }
            $output[] = '';
        }
        
        if ( ! empty( $colors['all_colors'] ) ) {
            $output[] = '### All Detected Colors';
            $output[] = '';
            $output[] = '<details>';
            $output[] = '<summary>Click to expand full color list (' . count( $colors['all_colors'] ) . ' colors)</summary>';
            $output[] = '';
            
            // Group colors by type
            $hex_colors = array();
            $rgb_colors = array();
            $hsl_colors = array();
            
            foreach ( $colors['all_colors'] as $color ) {
                if ( strpos( $color, '#' ) === 0 ) {
                    $hex_colors[] = $color;
                } elseif ( strpos( $color, 'rgb' ) === 0 ) {
                    $rgb_colors[] = $color;
                } elseif ( strpos( $color, 'hsl' ) === 0 ) {
                    $hsl_colors[] = $color;
                }
            }
            
            if ( ! empty( $hex_colors ) ) {
                $output[] = '**Hex Colors:**';
                $output[] = '```css';
                foreach ( array_chunk( $hex_colors, 5 ) as $chunk ) {
                    $output[] = implode( ', ', $chunk );
                }
                $output[] = '```';
                $output[] = '';
            }
            
            if ( ! empty( $rgb_colors ) ) {
                $output[] = '**RGB Colors:**';
                $output[] = '```css';
                foreach ( $rgb_colors as $color ) {
                    $output[] = $color;
                }
                $output[] = '```';
                $output[] = '';
            }
            
            if ( ! empty( $hsl_colors ) ) {
                $output[] = '**HSL Colors:**';
                $output[] = '```css';
                foreach ( $hsl_colors as $color ) {
                    $output[] = $color;
                }
                $output[] = '```';
            }
            
            $output[] = '</details>';
        }
    }
    
    /**
     * Format typography
     */
    private function format_typography( $typography, &$output ) {
        $output[] = '### Font Families';
        $output[] = '';
        
        if ( ! empty( $typography['font_family_base'] ) || ! empty( $typography['font_family_heading'] ) ) {
            $output[] = '```css';
            if ( ! empty( $typography['font_family_base'] ) ) {
                $output[] = '--font-family-base: ' . $typography['font_family_base'] . ';';
            }
            if ( ! empty( $typography['font_family_heading'] ) ) {
                $output[] = '--font-family-heading: ' . $typography['font_family_heading'] . ';';
            }
            if ( ! empty( $typography['font_family_mono'] ) ) {
                $output[] = '--font-family-mono: ' . $typography['font_family_mono'] . ';';
            }
            $output[] = '```';
            $output[] = '';
        }
        
        $output[] = '### Font Sizes';
        $output[] = '';
        
        if ( ! empty( $typography['editor_font_sizes'] ) ) {
            $output[] = '| Name | Slug | Size |';
            $output[] = '|------|------|------|';
            foreach ( $typography['editor_font_sizes'] as $size ) {
                $output[] = '| ' . $size['name'] . ' | ' . $size['slug'] . ' | ' . $size['size'] . 'px |';
            }
            $output[] = '';
        }
        
        $font_sizes = array();
        foreach ( $typography as $key => $value ) {
            if ( strpos( $key, 'font_size_' ) === 0 && ! empty( $value ) ) {
                $font_sizes[ $key ] = $value;
            }
        }
        
        if ( ! empty( $font_sizes ) ) {
            $output[] = '```css';
            foreach ( $font_sizes as $key => $value ) {
                $name = str_replace( 'font_size_', '--font-size-', $key );
                $output[] = $name . ': ' . $value . ';';
            }
            $output[] = '```';
            $output[] = '';
        }
        
        if ( ! empty( $typography['all_font_families'] ) ) {
            $output[] = '### All Font Families Used';
            $output[] = '';
            foreach ( $typography['all_font_families'] as $family ) {
                $output[] = '- `' . $family . '`';
            }
        }
    }
    
    /**
     * Format spacing
     */
    private function format_spacing( $spacing, &$output ) {
        $output[] = '### Spacing Scale';
        $output[] = '';
        
        $spacing_vars = array();
        foreach ( $spacing as $key => $value ) {
            if ( strpos( $key, 'spacing_' ) === 0 && ! empty( $value ) ) {
                $spacing_vars[ $key ] = $value;
            }
        }
        
        if ( ! empty( $spacing_vars ) ) {
            $output[] = '```css';
            foreach ( $spacing_vars as $key => $value ) {
                $name = str_replace( 'spacing_', '--spacing-', $key );
                $output[] = $name . ': ' . $value . ';';
            }
            $output[] = '```';
            $output[] = '';
        }
        
        if ( ! empty( $spacing['common_spacing_values'] ) ) {
            $output[] = '### Common Spacing Values';
            $output[] = '';
            
            // Group by unit type
            $rem_values = array();
            $px_values = array();
            $em_values = array();
            $percent_values = array();
            
            foreach ( $spacing['common_spacing_values'] as $value ) {
                if ( strpos( $value, 'rem' ) !== false ) {
                    $rem_values[] = $value;
                } elseif ( strpos( $value, 'px' ) !== false ) {
                    $px_values[] = $value;
                } elseif ( strpos( $value, 'em' ) !== false ) {
                    $em_values[] = $value;
                } elseif ( strpos( $value, '%' ) !== false ) {
                    $percent_values[] = $value;
                }
            }
            
            if ( ! empty( $rem_values ) ) {
                $output[] = '**REM Values:** ' . implode( ', ', array_slice( $rem_values, 0, 10 ) );
                if ( count( $rem_values ) > 10 ) {
                    $output[] = '*... and ' . ( count( $rem_values ) - 10 ) . ' more*';
                }
            }
            
            if ( ! empty( $px_values ) ) {
                $output[] = '**Pixel Values:** ' . implode( ', ', array_slice( $px_values, 0, 10 ) );
                if ( count( $px_values ) > 10 ) {
                    $output[] = '*... and ' . ( count( $px_values ) - 10 ) . ' more*';
                }
            }
        }
    }
    
    /**
     * Format components
     */
    private function format_components( $components, &$output ) {
        // Buttons
        if ( ! empty( $components['buttons'] ) ) {
            $output[] = '### Button Styles';
            $output[] = '';
            
            foreach ( $components['buttons'] as $selector => $styles ) {
                $output[] = '**' . $selector . '**';
                $output[] = '```css';
                $output[] = $selector . ' {';
                foreach ( $styles as $property => $value ) {
                    $output[] = '    ' . $property . ': ' . $value . ';';
                }
                $output[] = '}';
                $output[] = '```';
                $output[] = '';
            }
        }
        
        // Forms
        if ( ! empty( $components['forms'] ) ) {
            $output[] = '### Form Styles';
            $output[] = '';
            
            foreach ( $components['forms'] as $selector => $styles ) {
                $output[] = '**' . $selector . '**';
                $output[] = '```css';
                $output[] = $selector . ' {';
                foreach ( $styles as $property => $value ) {
                    $output[] = '    ' . $property . ': ' . $value . ';';
                }
                $output[] = '}';
                $output[] = '```';
                $output[] = '';
            }
        }
        
        // Cards
        if ( ! empty( $components['cards'] ) ) {
            $output[] = '### Card/Box Styles';
            $output[] = '';
            
            foreach ( $components['cards'] as $selector => $styles ) {
                $output[] = '**' . $selector . '**';
                $output[] = '```css';
                $output[] = $selector . ' {';
                foreach ( $styles as $property => $value ) {
                    $output[] = '    ' . $property . ': ' . $value . ';';
                }
                $output[] = '}';
                $output[] = '```';
                $output[] = '';
            }
        }
    }
    
    /**
     * Format layout
     */
    private function format_layout( $layout, &$output ) {
        $output[] = '### Container Widths';
        $output[] = '';
        
        $output[] = '| Type | Width |';
        $output[] = '|------|-------|';
        
        if ( ! empty( $layout['content_width'] ) ) {
            $output[] = '| Content Width | ' . $layout['content_width'] . ' |';
        }
        if ( ! empty( $layout['container_width'] ) ) {
            $output[] = '| Container Width | ' . $layout['container_width'] . ' |';
        }
        if ( ! empty( $layout['wide_width'] ) ) {
            $output[] = '| Wide Width | ' . $layout['wide_width'] . ' |';
        }
        
        $output[] = '';
        
        if ( ! empty( $layout['media_queries'] ) ) {
            $output[] = '### Media Queries';
            $output[] = '';
            $output[] = '```css';
            foreach ( array_slice( $layout['media_queries'], 0, 10 ) as $query ) {
                $output[] = $query;
            }
            if ( count( $layout['media_queries'] ) > 10 ) {
                $output[] = '/* ... and ' . ( count( $layout['media_queries'] ) - 10 ) . ' more */';
            }
            $output[] = '```';
        }
    }
    
    /**
     * Format effects
     */
    private function format_effects( $effects, &$output ) {
        if ( ! empty( $effects['border_radius_values'] ) ) {
            $output[] = '### Border Radius Values';
            $output[] = '';
            $output[] = '```css';
            foreach ( array_unique( array_slice( $effects['border_radius_values'], 0, 10 ) ) as $value ) {
                $output[] = 'border-radius: ' . $value . ';';
            }
            $output[] = '```';
            $output[] = '';
        }
        
        if ( ! empty( $effects['box_shadow_values'] ) ) {
            $output[] = '### Box Shadow Values';
            $output[] = '';
            $output[] = '```css';
            foreach ( array_slice( $effects['box_shadow_values'], 0, 5 ) as $value ) {
                $output[] = 'box-shadow: ' . $value . ';';
            }
            $output[] = '```';
            $output[] = '';
        }
        
        if ( ! empty( $effects['transition_values'] ) ) {
            $output[] = '### Transition Values';
            $output[] = '';
            $output[] = '```css';
            foreach ( array_slice( $effects['transition_values'], 0, 5 ) as $value ) {
                $output[] = 'transition: ' . $value . ';';
            }
            $output[] = '```';
        }
    }
    
    /**
     * Format CSS variables
     */
    private function format_css_variables( $variables, &$output ) {
        $output[] = '```css';
        $output[] = ':root {';
        
        $count = 0;
        foreach ( $variables as $name => $value ) {
            $output[] = '    ' . $name . ': ' . $value . ';';
            $count++;
            if ( $count >= 50 ) {
                $output[] = '    /* ... and ' . ( count( $variables ) - 50 ) . ' more variables */';
                break;
            }
        }
        
        $output[] = '}';
        $output[] = '```';
    }
    
    /**
     * Format computed styles
     */
    private function format_computed_styles( $computed_styles, &$output ) {
        // WordPress theme mods
        $has_theme_mods = false;
        foreach ( array( 'background_color', 'header_textcolor', 'link_color', 'main_text_color', 'secondary_text_color' ) as $mod ) {
            if ( ! empty( $computed_styles[ $mod ] ) ) {
                $has_theme_mods = true;
                break;
            }
        }
        
        if ( $has_theme_mods ) {
            $output[] = '### Theme Customizer Settings';
            $output[] = '';
            $output[] = '| Setting | Value |';
            $output[] = '|---------|-------|';
            
            foreach ( array( 'background_color', 'header_textcolor', 'link_color', 'main_text_color', 'secondary_text_color' ) as $mod ) {
                if ( ! empty( $computed_styles[ $mod ] ) ) {
                    $label = ucwords( str_replace( '_', ' ', $mod ) );
                    $value = $computed_styles[ $mod ];
                    if ( strpos( $value, '#' ) !== 0 ) {
                        $value = '#' . $value;
                    }
                    $output[] = '| ' . $label . ' | ' . $value . ' |';
                }
            }
            $output[] = '';
        }
        
        // Elementor settings
        if ( ! empty( $computed_styles['elementor_colors'] ) || ! empty( $computed_styles['elementor_typography'] ) ) {
            $output[] = '### Elementor Global Settings';
            $output[] = '';
            $output[] = '**Note:** This theme uses Elementor. The following are global Elementor settings that override theme styles.';
            $output[] = '';
            
            if ( ! empty( $computed_styles['elementor_colors'] ) ) {
                $output[] = '#### Elementor System Colors';
                $output[] = '';
                $output[] = '| Name | Color |';
                $output[] = '|------|-------|';
                foreach ( $computed_styles['elementor_colors'] as $color ) {
                    if ( isset( $color['title'], $color['color'] ) ) {
                        $output[] = '| ' . $color['title'] . ' | ' . $color['color'] . ' |';
                    }
                }
                $output[] = '';
            }
            
            if ( ! empty( $computed_styles['elementor_typography'] ) ) {
                $output[] = '#### Elementor System Typography';
                $output[] = '';
                $output[] = '| Name | Settings |';
                $output[] = '|------|----------|';
                foreach ( $computed_styles['elementor_typography'] as $typo ) {
                    if ( isset( $typo['title'] ) ) {
                        $settings = array();
                        if ( isset( $typo['typography_font_family'] ) ) {
                            $settings[] = 'Font: ' . $typo['typography_font_family'];
                        }
                        if ( isset( $typo['typography_font_size'] ) ) {
                            $settings[] = 'Size: ' . $typo['typography_font_size']['size'] . $typo['typography_font_size']['unit'];
                        }
                        if ( isset( $typo['typography_font_weight'] ) ) {
                            $settings[] = 'Weight: ' . $typo['typography_font_weight'];
                        }
                        $output[] = '| ' . $typo['title'] . ' | ' . implode( ', ', $settings ) . ' |';
                    }
                }
                $output[] = '';
            }
            
            if ( ! empty( $computed_styles['elementor_container_width'] ) ) {
                $output[] = '**Elementor Container Width:** ' . $computed_styles['elementor_container_width'];
                $output[] = '';
            }
        }
    }
    
    /**
     * Format implementation guide
     */
    private function format_implementation_guide( $results, &$output ) {
        $output[] = '### Quick Start CSS Template';
        $output[] = '';
        $output[] = 'Use this CSS template as a starting point for matching this theme\'s visual style:';
        $output[] = '';
        $output[] = '```css';
        $output[] = '/* Theme-Compatible Styles */';
        $output[] = ':root {';
        
        // Add primary colors
        if ( ! empty( $results['data']['color_palette']['css_colors'] ) ) {
            $output[] = '    /* Colors */';
            foreach ( $results['data']['color_palette']['css_colors'] as $name => $value ) {
                $output[] = '    --color-' . str_replace( '_', '-', $name ) . ': ' . $value . ';';
            }
        }
        
        // Add typography
        if ( ! empty( $results['data']['typography'] ) ) {
            $output[] = '    ';
            $output[] = '    /* Typography */';
            if ( ! empty( $results['data']['typography']['font_family_base'] ) ) {
                $output[] = '    --font-family-base: ' . $results['data']['typography']['font_family_base'] . ';';
            }
            if ( ! empty( $results['data']['typography']['font_size_base'] ) ) {
                $output[] = '    --font-size-base: ' . $results['data']['typography']['font_size_base'] . ';';
            }
            if ( ! empty( $results['data']['typography']['line_height_base'] ) ) {
                $output[] = '    --line-height-base: ' . $results['data']['typography']['line_height_base'] . ';';
            }
        }
        
        // Add spacing
        if ( ! empty( $results['data']['spacing'] ) ) {
            $output[] = '    ';
            $output[] = '    /* Spacing */';
            foreach ( $results['data']['spacing'] as $key => $value ) {
                if ( strpos( $key, 'spacing_' ) === 0 && ! empty( $value ) ) {
                    $name = str_replace( 'spacing_', '--spacing-', $key );
                    $output[] = '    ' . $name . ': ' . $value . ';';
                }
            }
        }
        
        $output[] = '}';
        $output[] = '';
        $output[] = '/* Base Styles */';
        $output[] = 'body {';
        $output[] = '    font-family: var(--font-family-base);';
        $output[] = '    font-size: var(--font-size-base);';
        $output[] = '    line-height: var(--line-height-base);';
        $output[] = '    color: var(--color-foreground);';
        $output[] = '    background-color: var(--color-background);';
        $output[] = '}';
        $output[] = '';
        $output[] = 'a {';
        $output[] = '    color: var(--color-link);';
        $output[] = '    text-decoration: underline;';
        $output[] = '}';
        $output[] = '';
        $output[] = 'a:hover {';
        $output[] = '    color: var(--color-link-hover);';
        $output[] = '}';
        $output[] = '```';
        $output[] = '';
        
        $output[] = '### Integration Notes';
        $output[] = '';
        
        // Detected frameworks
        if ( ! empty( $results['statistics']['detected_frameworks'] ) ) {
            $output[] = '**Detected CSS Frameworks:** ' . implode( ', ', $results['statistics']['detected_frameworks'] );
            $output[] = '';
            $output[] = 'This theme appears to use ' . implode( ' and ', $results['statistics']['detected_frameworks'] ) . '. Consider using the same framework(s) for consistency.';
            $output[] = '';
        }
        
        // Block theme
        if ( ! empty( $results['statistics']['is_block_theme'] ) ) {
            $output[] = '**Block Theme:** This is a WordPress block theme. Use block patterns and theme.json for best compatibility.';
            $output[] = '';
        }
        
        // Elementor
        if ( ! empty( $results['statistics']['uses_elementor'] ) ) {
            $output[] = '**Elementor Page Builder:** This site uses Elementor. Most styling is controlled through Elementor\'s global settings and individual widget styles rather than theme CSS.';
            $output[] = '';
            $output[] = 'To match this site\'s style:';
            $output[] = '1. Use Elementor\'s Global Colors and Typography settings';
            $output[] = '2. Check Site Settings → Global Colors & Fonts in Elementor';
            $output[] = '3. Style inheritance follows Elementor\'s system, not traditional CSS';
            $output[] = '';
        }
        
        $output[] = '### Best Practices';
        $output[] = '';
        $output[] = '1. **Use CSS Variables** - This theme uses CSS custom properties extensively. Use the provided variables for consistency.';
        $output[] = '2. **Match Spacing** - Follow the spacing scale to maintain visual rhythm.';
        $output[] = '3. **Typography Hierarchy** - Respect the established font sizes and families.';
        $output[] = '4. **Color Consistency** - Stick to the defined color palette.';
        $output[] = '5. **Component Patterns** - Follow the existing button, form, and card styles.';
        
        if ( ! empty( $results['data']['theme_json'] ) ) {
            $output[] = '';
            $output[] = '### theme.json Configuration';
            $output[] = '';
            $output[] = 'This theme uses theme.json. Key settings:';
            $output[] = '';
            $output[] = '```json';
            $output[] = json_encode( array(
                'version' => $results['data']['theme_json']['version'],
                'settings' => array(
                    'color' => $results['data']['theme_json']['settings']['color'] ?? null,
                    'typography' => $results['data']['theme_json']['settings']['typography'] ?? null,
                    'spacing' => $results['data']['theme_json']['settings']['spacing'] ?? null,
                )
            ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
            $output[] = '```';
        }
    }
}