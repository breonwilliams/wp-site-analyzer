<?php
/**
 * GitHub Updater for WP Site Analyzer
 *
 * @package WP_Site_Analyzer
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class WP_Site_Analyzer_GitHub_Updater
 *
 * Handles automatic updates from GitHub repository
 */
class WP_Site_Analyzer_GitHub_Updater {

    /**
     * GitHub repository owner/name
     *
     * @var string
     */
    private $repo_slug;

    /**
     * Plugin file
     *
     * @var string
     */
    private $plugin_file;

    /**
     * Plugin data
     *
     * @var array
     */
    private $plugin_data;

    /**
     * GitHub API result
     *
     * @var mixed
     */
    private $github_api_result;

    /**
     * Access token for private repos (optional)
     *
     * @var string
     */
    private $access_token;

    /**
     * Constructor
     *
     * @param string $plugin_file Main plugin file
     * @param string $github_repo GitHub repository in format 'username/repository'
     * @param string $access_token Optional GitHub access token for private repos
     */
    public function __construct( $plugin_file, $github_repo, $access_token = '' ) {
        $this->plugin_file = $plugin_file;
        $this->repo_slug = $github_repo;
        $this->access_token = $access_token;

        add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_for_update' ) );
        add_filter( 'plugins_api', array( $this, 'plugin_info' ), 20, 3 );
        add_filter( 'upgrader_source_selection', array( $this, 'fix_plugin_folder' ), 10, 4 );
        add_filter( 'upgrader_pre_download', array( $this, 'pre_download' ), 10, 3 );
        add_action( 'upgrader_process_complete', array( $this, 'purge_transients' ), 10, 2 );
    }

    /**
     * Get plugin data
     *
     * @return array Plugin data
     */
    private function get_plugin_data() {
        if ( is_null( $this->plugin_data ) ) {
            $this->plugin_data = get_plugin_data( $this->plugin_file );
        }
        return $this->plugin_data;
    }

    /**
     * Get GitHub release info
     *
     * @return mixed GitHub API response or false
     */
    private function get_github_release_info() {
        if ( ! is_null( $this->github_api_result ) ) {
            return $this->github_api_result;
        }

        // Check transient first
        $transient_name = 'wp_site_analyzer_github_' . md5( $this->repo_slug );
        $transient = get_transient( $transient_name );

        if ( $transient !== false ) {
            $this->github_api_result = $transient;
            return $this->github_api_result;
        }

        // Make API request
        $url = "https://api.github.com/repos/{$this->repo_slug}/releases/latest";
        
        $args = array(
            'timeout' => 10,
            'headers' => array(
                'Accept' => 'application/vnd.github.v3+json',
            ),
        );

        // Add authorization header if access token is provided
        if ( ! empty( $this->access_token ) ) {
            $args['headers']['Authorization'] = 'token ' . $this->access_token;
        }

        $response = wp_remote_get( $url, $args );

        if ( is_wp_error( $response ) ) {
            return false;
        }

        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        if ( ! isset( $data['tag_name'] ) ) {
            return false;
        }

        // Cache for 6 hours
        set_transient( $transient_name, $data, 6 * HOUR_IN_SECONDS );
        $this->github_api_result = $data;

        return $this->github_api_result;
    }

    /**
     * Check for plugin update
     *
     * @param object $transient WordPress update transient
     * @return object Modified transient
     */
    public function check_for_update( $transient ) {
        if ( empty( $transient->checked ) ) {
            return $transient;
        }

        $plugin_data = $this->get_plugin_data();
        $github_release = $this->get_github_release_info();

        if ( $github_release === false ) {
            return $transient;
        }

        // Extract version from tag name (remove 'v' prefix if present)
        $latest_version = ltrim( $github_release['tag_name'], 'v' );
        $current_version = $plugin_data['Version'];

        // Check if update is available
        if ( version_compare( $latest_version, $current_version, '>' ) ) {
            $plugin_basename = plugin_basename( $this->plugin_file );
            
            // Get download URL
            $download_url = $github_release['zipball_url'];
            
            // If there are assets, use the first zip asset
            if ( ! empty( $github_release['assets'] ) ) {
                foreach ( $github_release['assets'] as $asset ) {
                    if ( strpos( $asset['name'], '.zip' ) !== false ) {
                        $download_url = $asset['browser_download_url'];
                        break;
                    }
                }
            }

            // Don't add token to URL here, do it in pre_download filter

            // Create update object
            $update = array(
                'slug' => dirname( $plugin_basename ),
                'plugin' => $plugin_basename,
                'new_version' => $latest_version,
                'url' => "https://github.com/{$this->repo_slug}",
                'package' => $download_url,
                'icons' => array(),
                'banners' => array(),
                'banners_rtl' => array(),
                'tested' => get_bloginfo( 'version' ),
                'requires_php' => $plugin_data['RequiresPHP'] ?? '5.6',
                'requires' => $plugin_data['RequiresWP'] ?? '4.7',
            );

            $transient->response[ $plugin_basename ] = (object) $update;
        }

        return $transient;
    }

    /**
     * Get plugin info for WordPress plugin modal
     *
     * @param false|object|array $result
     * @param string $action
     * @param object $args
     * @return false|object
     */
    public function plugin_info( $result, $action, $args ) {
        if ( $action !== 'plugin_information' ) {
            return $result;
        }

        if ( $args->slug !== dirname( plugin_basename( $this->plugin_file ) ) ) {
            return $result;
        }

        $github_release = $this->get_github_release_info();

        if ( $github_release === false ) {
            return $result;
        }

        $plugin_data = $this->get_plugin_data();

        // Parse markdown body to HTML
        $body_html = $this->parse_markdown( $github_release['body'] ?? '' );

        $plugin_info = array(
            'name' => $plugin_data['Name'],
            'slug' => dirname( plugin_basename( $this->plugin_file ) ),
            'version' => ltrim( $github_release['tag_name'], 'v' ),
            'author' => $plugin_data['AuthorName'],
            'author_profile' => $plugin_data['AuthorURI'],
            'last_updated' => $github_release['published_at'],
            'homepage' => $plugin_data['PluginURI'],
            'short_description' => $plugin_data['Description'],
            'sections' => array(
                'description' => $plugin_data['Description'],
                'changelog' => '<h4>' . $github_release['name'] . '</h4>' . $body_html,
            ),
            'download_link' => $github_release['zipball_url'],
        );

        return (object) $plugin_info;
    }

    /**
     * Fix plugin folder name after update
     *
     * @param string $source
     * @param string $remote_source
     * @param WP_Upgrader $upgrader
     * @param array $args
     * @return string|WP_Error
     */
    public function fix_plugin_folder( $source, $remote_source, $upgrader, $args ) {
        global $wp_filesystem;

        if ( ! isset( $args['plugin'] ) || $args['plugin'] !== plugin_basename( $this->plugin_file ) ) {
            return $source;
        }

        // Get proper folder name
        $proper_folder_name = dirname( plugin_basename( $this->plugin_file ) );
        
        // GitHub zipball structure: username-repo-hash/
        $source_files = $wp_filesystem->dirlist( $remote_source );
        if ( ! $source_files ) {
            return $source;
        }
        
        // Find the actual plugin folder (should be the first directory)
        $github_folder = '';
        foreach ( $source_files as $file => $file_info ) {
            if ( $file_info['type'] === 'd' ) {
                $github_folder = $file;
                break;
            }
        }
        
        if ( empty( $github_folder ) ) {
            return new WP_Error( 'github_updater', 'Could not find plugin folder in GitHub download' );
        }
        
        $github_source = trailingslashit( $remote_source ) . $github_folder;
        $new_source = trailingslashit( $remote_source ) . $proper_folder_name;
        
        // Check if main plugin file exists
        $plugin_file_name = basename( $this->plugin_file );
        if ( ! $wp_filesystem->exists( $github_source . '/' . $plugin_file_name ) ) {
            return new WP_Error( 'github_updater', 'Plugin file not found in GitHub download' );
        }
        
        // Move to correct folder name
        if ( $github_source !== $new_source ) {
            if ( $wp_filesystem->move( $github_source, $new_source ) ) {
                return $new_source;
            } else {
                return new WP_Error( 'github_updater', 'Could not rename plugin folder' );
            }
        }
        
        return $new_source;
    }

    /**
     * Purge transients after update
     *
     * @param WP_Upgrader $upgrader_object
     * @param array $options
     */
    public function purge_transients( $upgrader_object, $options ) {
        if ( $options['action'] == 'update' && $options['type'] == 'plugin' ) {
            delete_transient( 'wp_site_analyzer_github_' . md5( $this->repo_slug ) );
        }
    }

    /**
     * Simple markdown parser
     *
     * @param string $text Markdown text
     * @return string HTML
     */
    private function parse_markdown( $text ) {
        $html = $text;
        
        // Convert headers
        $html = preg_replace( '/^### (.+)$/m', '<h4>$1</h4>', $html );
        $html = preg_replace( '/^## (.+)$/m', '<h3>$1</h3>', $html );
        $html = preg_replace( '/^# (.+)$/m', '<h2>$1</h2>', $html );
        
        // Convert bold
        $html = preg_replace( '/\*\*(.+?)\*\*/', '<strong>$1</strong>', $html );
        
        // Convert italic
        $html = preg_replace( '/\*(.+?)\*/', '<em>$1</em>', $html );
        
        // Convert links
        $html = preg_replace( '/\[([^\]]+)\]\(([^)]+)\)/', '<a href="$2">$1</a>', $html );
        
        // Convert line breaks
        $html = nl2br( $html );
        
        // Convert lists
        $html = preg_replace( '/^\* (.+)$/m', '<li>$1</li>', $html );
        $html = preg_replace( '/(<li>.*<\/li>)/s', '<ul>$1</ul>', $html );
        
        return $html;
    }
    
    /**
     * Handle download for GitHub URLs
     *
     * @param bool $reply
     * @param string $package
     * @param object $upgrader
     * @return bool|WP_Error
     */
    public function pre_download( $reply, $package, $upgrader ) {
        // Check if this is our plugin
        if ( strpos( $package, 'api.github.com' ) === false ) {
            return $reply;
        }
        
        // Check if it's our repo
        if ( strpos( $package, $this->repo_slug ) === false ) {
            return $reply;
        }
        
        // Add authorization header if needed
        if ( ! empty( $this->access_token ) ) {
            $args = array(
                'timeout' => 300,
                'headers' => array(
                    'Authorization' => 'token ' . $this->access_token,
                ),
            );
            
            // Download the file
            $tmp_file = download_url( $package, 300, $args );
        } else {
            // For public repos, just download normally
            $tmp_file = download_url( $package );
        }
        
        if ( is_wp_error( $tmp_file ) ) {
            return $tmp_file;
        }
        
        // Connect to WP_Filesystem
        global $wp_filesystem;
        if ( ! $wp_filesystem ) {
            WP_Filesystem();
        }
        
        // Move to upgrader's working directory
        $working_dir = $upgrader->skin->get_upgrader_data( 'working_dir' );
        if ( ! $working_dir ) {
            $working_dir = $upgrader->unpack_package( $tmp_file, true );
        }
        
        return $tmp_file;
    }
}