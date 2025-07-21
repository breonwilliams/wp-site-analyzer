<?php
/**
 * Theme Style Scanner
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
 * Class WP_Site_Analyzer_Theme_Style_Scanner
 * 
 * Analyzes theme styles, colors, typography, and visual patterns
 */
class WP_Site_Analyzer_Theme_Style_Scanner extends WP_Site_Analyzer_Scanner_Base {
    
    /**
     * Scanner name
     *
     * @var string
     */
    protected $name = 'Theme Style Scanner';
    
    /**
     * Scanner slug
     *
     * @var string
     */
    protected $slug = 'theme_style';
    
    /**
     * Scan theme styles
     *
     * @return array
     */
    public function scan() {
        $this->start_performance_monitor( 'theme_style_scan' );
        
        $results = array(
            'theme_info' => $this->get_theme_info(),
            'color_palette' => $this->extract_color_palette(),
            'typography' => $this->extract_typography(),
            'spacing' => $this->extract_spacing_system(),
            'components' => $this->extract_component_styles(),
            'layout' => $this->extract_layout_patterns(),
            'effects' => $this->extract_visual_effects(),
            'css_variables' => $this->extract_css_variables(),
            'frameworks' => $this->detect_css_frameworks(),
            'custom_css' => $this->extract_custom_css(),
            'theme_json' => $this->parse_theme_json(),
            'computed_styles' => $this->get_computed_styles(),
        );
        
        $performance_metrics = $this->end_performance_monitor( 'theme_style_scan' );
        
        return array(
            'data' => $results,
            'statistics' => $this->calculate_statistics( $results ),
            'performance_metrics' => $performance_metrics,
        );
    }
    
    /**
     * Get theme information
     *
     * @return array
     */
    private function get_theme_info() {
        $theme = wp_get_theme();
        
        return array(
            'name' => $theme->get( 'Name' ),
            'version' => $theme->get( 'Version' ),
            'text_domain' => $theme->get( 'TextDomain' ),
            'template' => $theme->get_template(),
            'stylesheet' => $theme->get_stylesheet(),
            'theme_uri' => $theme->get( 'ThemeURI' ),
            'author' => $theme->get( 'Author' ),
            'is_child_theme' => is_child_theme(),
            'parent_theme' => is_child_theme() ? $theme->parent()->get( 'Name' ) : null,
            'theme_supports' => $this->get_theme_supports(),
        );
    }
    
    /**
     * Get theme supports
     *
     * @return array
     */
    private function get_theme_supports() {
        $supports = array();
        $features = array(
            'custom-logo',
            'custom-header',
            'custom-background',
            'post-thumbnails',
            'editor-styles',
            'wp-block-styles',
            'responsive-embeds',
            'editor-color-palette',
            'editor-font-sizes',
            'custom-line-height',
            'custom-units',
            'custom-spacing',
        );
        
        foreach ( $features as $feature ) {
            $supports[ $feature ] = current_theme_supports( $feature );
        }
        
        return $supports;
    }
    
    /**
     * Extract color palette
     *
     * @return array
     */
    private function extract_color_palette() {
        $colors = array();
        
        // Get editor color palette if available
        $editor_palette = get_theme_support( 'editor-color-palette' );
        if ( $editor_palette ) {
            $colors['editor_palette'] = $editor_palette[0];
        }
        
        // Get common CSS color properties
        $color_properties = array(
            'primary' => array( '--wp--preset--color--primary', '--color-primary', '--primary' ),
            'secondary' => array( '--wp--preset--color--secondary', '--color-secondary', '--secondary' ),
            'accent' => array( '--wp--preset--color--accent', '--color-accent', '--accent' ),
            'background' => array( '--wp--preset--color--background', '--color-background', '--bg-color' ),
            'foreground' => array( '--wp--preset--color--foreground', '--color-text', '--text-color' ),
            'heading' => array( '--wp--preset--color--heading', '--color-heading', '--heading-color' ),
            'link' => array( '--wp--preset--color--link', '--color-link', '--link-color' ),
            'link_hover' => array( '--wp--preset--color--link-hover', '--color-link-hover', '--link-hover' ),
            'button' => array( '--wp--preset--color--button', '--color-button', '--button-color' ),
            'button_hover' => array( '--wp--preset--color--button-hover', '--color-button-hover', '--button-hover' ),
            'border' => array( '--wp--preset--color--border', '--color-border', '--border-color' ),
            'success' => array( '--wp--preset--color--success', '--color-success', '--success' ),
            'warning' => array( '--wp--preset--color--warning', '--color-warning', '--warning' ),
            'error' => array( '--wp--preset--color--error', '--color-error', '--error', '--danger' ),
        );
        
        // Extract colors from stylesheets
        $stylesheets = $this->get_theme_stylesheets();
        foreach ( $stylesheets as $stylesheet ) {
            $css_content = file_get_contents( $stylesheet );
            
            foreach ( $color_properties as $name => $variants ) {
                foreach ( $variants as $property ) {
                    if ( preg_match( '/' . preg_quote( $property, '/' ) . '\s*:\s*([^;]+);/i', $css_content, $matches ) ) {
                        $colors['css_colors'][ $name ] = trim( $matches[1] );
                        break;
                    }
                }
            }
            
            // Extract all color values
            preg_match_all( '/#([a-fA-F0-9]{3}){1,2}\b|rgba?\([^)]+\)|hsla?\([^)]+\)/i', $css_content, $all_colors );
            if ( ! empty( $all_colors[0] ) ) {
                $colors['all_colors'] = array_values( array_unique( $all_colors[0] ) );
            }
        }
        
        return $colors;
    }
    
    /**
     * Extract typography settings
     *
     * @return array
     */
    private function extract_typography() {
        $typography = array();
        
        // Get editor font sizes
        $editor_font_sizes = get_theme_support( 'editor-font-sizes' );
        if ( $editor_font_sizes ) {
            $typography['editor_font_sizes'] = $editor_font_sizes[0];
        }
        
        // Common font properties to look for
        $font_properties = array(
            'font_family_base' => array( '--wp--preset--font-family--system', '--font-family-base', 'body' ),
            'font_family_heading' => array( '--wp--preset--font-family--heading', '--font-family-heading', 'h1, h2, h3, h4, h5, h6' ),
            'font_family_mono' => array( '--wp--preset--font-family--monospace', '--font-family-mono', 'code, pre' ),
            'font_size_base' => array( '--wp--preset--font-size--normal', '--font-size-base', 'body' ),
            'font_size_h1' => array( '--wp--preset--font-size--huge', '--font-size-h1', 'h1' ),
            'font_size_h2' => array( '--wp--preset--font-size--x-large', '--font-size-h2', 'h2' ),
            'font_size_h3' => array( '--wp--preset--font-size--large', '--font-size-h3', 'h3' ),
            'line_height_base' => array( '--wp--custom--line-height--normal', '--line-height-base', 'body' ),
            'font_weight_normal' => array( '--wp--custom--font-weight--normal', '--font-weight-normal' ),
            'font_weight_bold' => array( '--wp--custom--font-weight--bold', '--font-weight-bold' ),
        );
        
        $stylesheets = $this->get_theme_stylesheets();
        foreach ( $stylesheets as $stylesheet ) {
            $css_content = file_get_contents( $stylesheet );
            
            foreach ( $font_properties as $name => $variants ) {
                foreach ( $variants as $property ) {
                    // For CSS variables
                    if ( strpos( $property, '--' ) === 0 ) {
                        if ( preg_match( '/' . preg_quote( $property, '/' ) . '\s*:\s*([^;]+);/i', $css_content, $matches ) ) {
                            $typography[ $name ] = trim( $matches[1] );
                            break;
                        }
                    } else {
                        // For selectors - look for the specific property
                        $pattern = '/' . preg_quote( $property, '/' ) . '\s*\{[^}]*';
                        if ( strpos( $name, 'font_family' ) !== false ) {
                            $pattern .= 'font-family:\s*([^;]+)';
                        } elseif ( strpos( $name, 'font_size' ) !== false ) {
                            $pattern .= 'font-size:\s*([^;]+)';
                        } elseif ( strpos( $name, 'line_height' ) !== false ) {
                            $pattern .= 'line-height:\s*([^;]+)';
                        } elseif ( strpos( $name, 'font_weight' ) !== false ) {
                            $pattern .= 'font-weight:\s*([^;]+)';
                        }
                        $pattern .= ';/i';
                        
                        if ( preg_match( $pattern, $css_content, $matches ) ) {
                            $typography[ $name ] = trim( $matches[1] );
                            break;
                        }
                    }
                }
            }
        }
        
        // Extract all font families
        preg_match_all( '/font-family:\s*([^;]+);/i', $css_content, $font_families );
        if ( ! empty( $font_families[1] ) ) {
            $typography['all_font_families'] = array_values( array_unique( array_map( 'trim', $font_families[1] ) ) );
        }
        
        return $typography;
    }
    
    /**
     * Extract spacing system
     *
     * @return array
     */
    private function extract_spacing_system() {
        $spacing = array();
        
        // Get custom spacing if available
        $spacing_scale = get_theme_support( 'custom-spacing' );
        if ( $spacing_scale ) {
            $spacing['custom_spacing'] = $spacing_scale;
        }
        
        // Common spacing properties
        $spacing_properties = array(
            'spacing_unit' => array( '--wp--preset--spacing--unit', '--spacing-unit', '--space-1' ),
            'spacing_xs' => array( '--wp--preset--spacing--20', '--spacing-xs', '--space-xs' ),
            'spacing_sm' => array( '--wp--preset--spacing--30', '--spacing-sm', '--space-sm' ),
            'spacing_md' => array( '--wp--preset--spacing--40', '--spacing-md', '--space-md' ),
            'spacing_lg' => array( '--wp--preset--spacing--50', '--spacing-lg', '--space-lg' ),
            'spacing_xl' => array( '--wp--preset--spacing--60', '--spacing-xl', '--space-xl' ),
            'container_padding' => array( '--wp--custom--spacing--outer', '--container-padding' ),
            'grid_gap' => array( '--wp--style--block-gap', '--grid-gap', '--gap' ),
        );
        
        $stylesheets = $this->get_theme_stylesheets();
        foreach ( $stylesheets as $stylesheet ) {
            $css_content = file_get_contents( $stylesheet );
            
            foreach ( $spacing_properties as $name => $variants ) {
                foreach ( $variants as $property ) {
                    if ( preg_match( '/' . preg_quote( $property, '/' ) . '\s*:\s*([^;]+);/i', $css_content, $matches ) ) {
                        $spacing[ $name ] = trim( $matches[1] );
                        break;
                    }
                }
            }
            
            // Extract common spacing values
            preg_match_all( '/(?:margin|padding)(?:-(?:top|right|bottom|left))?:\s*([^;]+);/i', $css_content, $spacing_values );
            if ( ! empty( $spacing_values[1] ) ) {
                $unique_values = array_unique( array_map( 'trim', $spacing_values[1] ) );
                // Filter out inherit, auto, etc.
                $spacing['common_spacing_values'] = array_values( array_filter( $unique_values, function( $value ) {
                    return preg_match( '/\d+(?:px|rem|em|%|vh|vw)/', $value );
                } ) );
            }
        }
        
        return $spacing;
    }
    
    /**
     * Extract component styles
     *
     * @return array
     */
    private function extract_component_styles() {
        $components = array();
        
        // Button styles
        $components['buttons'] = $this->extract_button_styles();
        
        // Form styles
        $components['forms'] = $this->extract_form_styles();
        
        // Card/Box styles
        $components['cards'] = $this->extract_card_styles();
        
        // Navigation styles
        $components['navigation'] = $this->extract_navigation_styles();
        
        return $components;
    }
    
    /**
     * Extract button styles
     *
     * @return array
     */
    private function extract_button_styles() {
        $button_styles = array();
        
        $button_selectors = array(
            '.button',
            '.btn',
            '.wp-block-button__link',
            'button',
            'input[type="submit"]',
            'input[type="button"]',
        );
        
        $stylesheets = $this->get_theme_stylesheets();
        foreach ( $stylesheets as $stylesheet ) {
            $css_content = file_get_contents( $stylesheet );
            
            foreach ( $button_selectors as $selector ) {
                $pattern = '/' . preg_quote( $selector, '/' ) . '\s*{([^}]+)}/i';
                if ( preg_match( $pattern, $css_content, $matches ) ) {
                    $styles = $this->parse_css_rules( $matches[1] );
                    if ( ! empty( $styles ) ) {
                        $button_styles[ $selector ] = $styles;
                    }
                }
            }
        }
        
        return $button_styles;
    }
    
    /**
     * Extract form styles
     *
     * @return array
     */
    private function extract_form_styles() {
        $form_styles = array();
        
        $form_selectors = array(
            'input[type="text"]',
            'input[type="email"]',
            'textarea',
            'select',
            '.form-control',
            '.input',
        );
        
        $stylesheets = $this->get_theme_stylesheets();
        foreach ( $stylesheets as $stylesheet ) {
            $css_content = file_get_contents( $stylesheet );
            
            foreach ( $form_selectors as $selector ) {
                $pattern = '/' . preg_quote( $selector, '/' ) . '\s*{([^}]+)}/i';
                if ( preg_match( $pattern, $css_content, $matches ) ) {
                    $styles = $this->parse_css_rules( $matches[1] );
                    if ( ! empty( $styles ) ) {
                        $form_styles[ $selector ] = $styles;
                    }
                }
            }
        }
        
        return $form_styles;
    }
    
    /**
     * Extract card styles
     *
     * @return array
     */
    private function extract_card_styles() {
        $card_styles = array();
        
        $card_selectors = array(
            '.card',
            '.box',
            '.panel',
            '.wp-block-group',
            'article',
        );
        
        $stylesheets = $this->get_theme_stylesheets();
        foreach ( $stylesheets as $stylesheet ) {
            $css_content = file_get_contents( $stylesheet );
            
            foreach ( $card_selectors as $selector ) {
                $pattern = '/' . preg_quote( $selector, '/' ) . '\s*{([^}]+)}/i';
                if ( preg_match( $pattern, $css_content, $matches ) ) {
                    $styles = $this->parse_css_rules( $matches[1] );
                    if ( ! empty( $styles ) ) {
                        $card_styles[ $selector ] = $styles;
                    }
                }
            }
        }
        
        return $card_styles;
    }
    
    /**
     * Extract navigation styles
     *
     * @return array
     */
    private function extract_navigation_styles() {
        $nav_styles = array();
        
        $nav_selectors = array(
            '.navigation',
            '.nav',
            '.menu',
            '.wp-block-navigation',
            'nav',
        );
        
        $stylesheets = $this->get_theme_stylesheets();
        foreach ( $stylesheets as $stylesheet ) {
            $css_content = file_get_contents( $stylesheet );
            
            foreach ( $nav_selectors as $selector ) {
                $pattern = '/' . preg_quote( $selector, '/' ) . '\s*{([^}]+)}/i';
                if ( preg_match( $pattern, $css_content, $matches ) ) {
                    $styles = $this->parse_css_rules( $matches[1] );
                    if ( ! empty( $styles ) ) {
                        $nav_styles[ $selector ] = $styles;
                    }
                }
            }
        }
        
        return $nav_styles;
    }
    
    /**
     * Extract layout patterns
     *
     * @return array
     */
    private function extract_layout_patterns() {
        $layout = array();
        
        // Get content width
        global $content_width;
        if ( isset( $content_width ) ) {
            $layout['content_width'] = $content_width . 'px';
        }
        
        // Common layout properties
        $layout_properties = array(
            'container_width' => array( '--wp--custom--layout--content-size', '--container-width', '.container' ),
            'wide_width' => array( '--wp--custom--layout--wide-size', '--wide-width', '.alignwide' ),
            'grid_columns' => array( '--wp--custom--layout--grid-columns', '--grid-columns' ),
            'breakpoint_sm' => array( '--wp--custom--breakpoint--sm', '--breakpoint-sm' ),
            'breakpoint_md' => array( '--wp--custom--breakpoint--md', '--breakpoint-md' ),
            'breakpoint_lg' => array( '--wp--custom--breakpoint--lg', '--breakpoint-lg' ),
        );
        
        $stylesheets = $this->get_theme_stylesheets();
        foreach ( $stylesheets as $stylesheet ) {
            $css_content = file_get_contents( $stylesheet );
            
            foreach ( $layout_properties as $name => $variants ) {
                foreach ( $variants as $property ) {
                    if ( strpos( $property, '--' ) === 0 ) {
                        if ( preg_match( '/' . preg_quote( $property, '/' ) . '\s*:\s*([^;]+);/i', $css_content, $matches ) ) {
                            $layout[ $name ] = trim( $matches[1] );
                            break;
                        }
                    } else {
                        if ( preg_match( '/' . preg_quote( $property, '/' ) . '\s*{[^}]*(?:max-)?width:\s*([^;]+);/i', $css_content, $matches ) ) {
                            $layout[ $name ] = trim( $matches[1] );
                            break;
                        }
                    }
                }
            }
            
            // Extract media queries
            preg_match_all( '/@media[^{]+{/i', $css_content, $media_queries );
            if ( ! empty( $media_queries[0] ) ) {
                $layout['media_queries'] = array_values( array_unique( $media_queries[0] ) );
            }
        }
        
        return $layout;
    }
    
    /**
     * Extract visual effects
     *
     * @return array
     */
    private function extract_visual_effects() {
        $effects = array();
        
        $effect_properties = array(
            'border_radius' => array( '--wp--custom--border--radius', '--border-radius', 'border-radius' ),
            'box_shadow' => array( '--wp--custom--shadow--natural', '--box-shadow', 'box-shadow' ),
            'transition' => array( '--wp--custom--transition--duration', '--transition', 'transition' ),
        );
        
        $stylesheets = $this->get_theme_stylesheets();
        foreach ( $stylesheets as $stylesheet ) {
            $css_content = file_get_contents( $stylesheet );
            
            // Extract all border-radius values
            preg_match_all( '/border-radius:\s*([^;]+);/i', $css_content, $border_radii );
            if ( ! empty( $border_radii[1] ) ) {
                $effects['border_radius_values'] = array_values( array_unique( array_map( 'trim', $border_radii[1] ) ) );
            }
            
            // Extract all box-shadow values
            preg_match_all( '/box-shadow:\s*([^;]+);/i', $css_content, $box_shadows );
            if ( ! empty( $box_shadows[1] ) ) {
                $effects['box_shadow_values'] = array_values( array_unique( array_map( 'trim', $box_shadows[1] ) ) );
            }
            
            // Extract all transition values
            preg_match_all( '/transition:\s*([^;]+);/i', $css_content, $transitions );
            if ( ! empty( $transitions[1] ) ) {
                $effects['transition_values'] = array_values( array_unique( array_map( 'trim', $transitions[1] ) ) );
            }
        }
        
        return $effects;
    }
    
    /**
     * Extract CSS variables
     *
     * @return array
     */
    private function extract_css_variables() {
        $variables = array();
        
        $stylesheets = $this->get_theme_stylesheets();
        foreach ( $stylesheets as $stylesheet ) {
            $css_content = file_get_contents( $stylesheet );
            
            // Extract CSS custom properties
            preg_match_all( '/--[\w-]+:\s*[^;]+;/i', $css_content, $css_vars );
            if ( ! empty( $css_vars[0] ) ) {
                foreach ( $css_vars[0] as $var ) {
                    if ( preg_match( '/(--[\w-]+):\s*([^;]+);/i', $var, $matches ) ) {
                        $variables[ trim( $matches[1] ) ] = trim( $matches[2] );
                    }
                }
            }
        }
        
        return $variables;
    }
    
    /**
     * Detect CSS frameworks
     *
     * @return array
     */
    private function detect_css_frameworks() {
        $frameworks = array();
        
        $framework_patterns = array(
            'bootstrap' => array( '.container-fluid', '.row', '.col-', 'btn-primary' ),
            'tailwind' => array( '.flex', '.grid', '.bg-', '.text-', 'hover:' ),
            'foundation' => array( '.row', '.columns', '.button', '.callout' ),
            'bulma' => array( '.columns', '.column', '.button', '.hero' ),
            'materialize' => array( '.materialize', '.waves-effect', '.card-panel' ),
        );
        
        $stylesheets = $this->get_theme_stylesheets();
        foreach ( $stylesheets as $stylesheet ) {
            $css_content = file_get_contents( $stylesheet );
            
            foreach ( $framework_patterns as $framework => $patterns ) {
                $found_patterns = 0;
                foreach ( $patterns as $pattern ) {
                    if ( strpos( $css_content, $pattern ) !== false ) {
                        $found_patterns++;
                    }
                }
                
                if ( $found_patterns >= 2 ) {
                    $frameworks[] = $framework;
                }
            }
        }
        
        return array_unique( $frameworks );
    }
    
    /**
     * Extract custom CSS
     *
     * @return array
     */
    private function extract_custom_css() {
        $custom_css = array();
        
        // Get customizer CSS
        $custom_css['customizer'] = wp_get_custom_css();
        
        // Get additional CSS from theme mods
        $theme_mods = get_theme_mods();
        if ( isset( $theme_mods['custom_css'] ) ) {
            $custom_css['theme_mods'] = $theme_mods['custom_css'];
        }
        
        return $custom_css;
    }
    
    /**
     * Parse theme.json if available
     *
     * @return array|null
     */
    private function parse_theme_json() {
        $theme_json_path = get_template_directory() . '/theme.json';
        
        if ( file_exists( $theme_json_path ) ) {
            $theme_json = json_decode( file_get_contents( $theme_json_path ), true );
            
            if ( $theme_json ) {
                return array(
                    'version' => $theme_json['version'] ?? null,
                    'settings' => $theme_json['settings'] ?? array(),
                    'styles' => $theme_json['styles'] ?? array(),
                    'customTemplates' => $theme_json['customTemplates'] ?? array(),
                    'templateParts' => $theme_json['templateParts'] ?? array(),
                );
            }
        }
        
        return null;
    }
    
    /**
     * Get theme stylesheets
     *
     * @return array
     */
    private function get_theme_stylesheets() {
        $stylesheets = array();
        
        // Main theme stylesheet
        $stylesheets[] = get_stylesheet_directory() . '/style.css';
        
        // Check for common additional stylesheets
        $additional_files = array(
            '/assets/css/style.css',
            '/assets/css/main.css',
            '/css/style.css',
            '/css/main.css',
            '/dist/style.css',
            '/dist/main.css',
        );
        
        foreach ( $additional_files as $file ) {
            $path = get_stylesheet_directory() . $file;
            if ( file_exists( $path ) ) {
                $stylesheets[] = $path;
            }
        }
        
        return array_filter( $stylesheets, 'file_exists' );
    }
    
    /**
     * Parse CSS rules from string
     *
     * @param string $css_string
     * @return array
     */
    private function parse_css_rules( $css_string ) {
        $rules = array();
        
        $properties = array(
            'color', 'background-color', 'background', 'border', 'border-radius',
            'padding', 'margin', 'font-family', 'font-size', 'font-weight',
            'line-height', 'text-decoration', 'box-shadow', 'transition'
        );
        
        foreach ( $properties as $property ) {
            if ( preg_match( '/' . $property . ':\s*([^;]+);/i', $css_string, $matches ) ) {
                $rules[ $property ] = trim( $matches[1] );
            }
        }
        
        return $rules;
    }
    
    /**
     * Get computed styles from active theme
     *
     * @return array
     */
    private function get_computed_styles() {
        $styles = array();
        
        // Get styles from theme mods
        $theme_mods = get_theme_mods();
        
        // Common style-related theme mods
        $style_mods = array(
            'background_color',
            'header_textcolor',
            'link_color',
            'main_text_color',
            'secondary_text_color',
        );
        
        foreach ( $style_mods as $mod ) {
            if ( isset( $theme_mods[ $mod ] ) ) {
                $styles[ $mod ] = $theme_mods[ $mod ];
            }
        }
        
        // Get default WordPress styles
        $styles['content_width'] = isset( $GLOBALS['content_width'] ) ? $GLOBALS['content_width'] . 'px' : null;
        
        // Try to get Elementor settings if available
        if ( class_exists( 'Elementor\Plugin' ) ) {
            $kit = \Elementor\Plugin::$instance->kits_manager->get_active_kit();
            if ( $kit ) {
                $kit_settings = $kit->get_settings();
                
                // Extract Elementor global colors
                if ( isset( $kit_settings['system_colors'] ) ) {
                    $styles['elementor_colors'] = $kit_settings['system_colors'];
                }
                
                // Extract Elementor global fonts
                if ( isset( $kit_settings['system_typography'] ) ) {
                    $styles['elementor_typography'] = $kit_settings['system_typography'];
                }
                
                // Container width
                if ( isset( $kit_settings['container_width'] ) ) {
                    $styles['elementor_container_width'] = $kit_settings['container_width']['size'] . $kit_settings['container_width']['unit'];
                }
            }
        }
        
        return $styles;
    }
    
    /**
     * Calculate statistics
     *
     * @param array $results
     * @return array
     */
    private function calculate_statistics( $results ) {
        return array(
            'theme_name' => $results['theme_info']['name'] ?? 'Unknown',
            'is_block_theme' => wp_is_block_theme(),
            'has_theme_json' => ! empty( $results['theme_json'] ),
            'color_count' => count( $results['color_palette']['all_colors'] ?? array() ),
            'font_family_count' => count( $results['typography']['all_font_families'] ?? array() ),
            'css_variable_count' => count( $results['css_variables'] ?? array() ),
            'detected_frameworks' => $results['frameworks'] ?? array(),
            'has_custom_css' => ! empty( $results['custom_css']['customizer'] ),
            'uses_elementor' => class_exists( 'Elementor\Plugin' ),
        );
    }
}