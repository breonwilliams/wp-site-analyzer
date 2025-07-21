# WP Site Analyzer

A comprehensive WordPress site analysis tool that scans and documents all content types, taxonomies, custom fields, and site structure in an AI-friendly format.

## Features

- Scans all post types, taxonomies, and custom fields
- Analyzes theme styles and design elements
- Generates AI-optimized reports for LLMs
- Caches results for improved performance
- Automatic updates from GitHub

## Installation

1. Download the plugin from the releases page
2. Upload to your WordPress site's `wp-content/plugins/` directory
3. Activate the plugin through the WordPress admin

## Automatic Updates from GitHub

This plugin supports automatic updates directly from your GitHub repository. When you push a new release to GitHub, all sites with this plugin installed will receive update notifications.

### Setting Up GitHub Updates

1. **Configure Your Repository**
   - Go to **Settings** → **WP Site Analyzer** → **Settings** in your WordPress admin
   - Enter your GitHub repository in the format: `username/repository-name`
   - For private repositories, generate and enter a GitHub personal access token

2. **Creating a GitHub Personal Access Token** (for private repos only)
   - Go to [GitHub Settings → Tokens](https://github.com/settings/tokens)
   - Click "Generate new token"
   - Give it a descriptive name
   - Select the `repo` scope
   - Generate and copy the token
   - Paste it in the plugin settings

3. **Publishing Updates**
   - Update the version number in the main plugin file (`wp-site-analyzer.php`)
   - Commit and push your changes
   - Create a new release on GitHub:
     - Go to your repository → Releases → "Create a new release"
     - Create a tag matching your version (e.g., `v1.0.1` or `1.0.1`)
     - Write release notes
     - Publish the release
   - WordPress sites will check for updates every 12 hours automatically

### Version Format

- Use semantic versioning: `MAJOR.MINOR.PATCH`
- The GitHub tag can include or exclude the 'v' prefix (both `v1.0.1` and `1.0.1` work)
- Always update the version in the plugin header when making changes

### Update Process

1. Sites check for updates every 12 hours
2. If a newer version is found, WordPress shows an update notification
3. Users can update through the standard WordPress update interface
4. The plugin maintains its folder name during updates

## Development

### File Structure

```
wp-site-analyzer/
├── assets/
│   ├── css/
│   └── js/
├── includes/
│   ├── admin/
│   ├── formatters/
│   ├── scanners/
│   └── utilities/
├── languages/
├── wp-site-analyzer.php
└── README.md
```

### Hooks and Filters

The plugin provides several hooks for customization:

- `wp_site_analyzer_scan_complete` - Fired after a scan completes
- `wp_site_analyzer_results` - Filter scan results before caching

## Requirements

- WordPress 5.8 or higher
- PHP 7.4 or higher

## License

GPL v2 or later

## Support

For issues and feature requests, please use the GitHub issues page.
EOF < /dev/null