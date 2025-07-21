# Changelog

All notable changes to WP Site Analyzer will be documented in this file.

## [1.0.3] - 2024-01-21

### Fixed
- Fixed fatal error "Cannot access offset of type string on string" on dashboard
- Corrected data structure access for scan summary display
- Fixed accessing cached results data structure

## [1.0.2] - 2024-01-21

### Fixed
- Fixed GitHub updater to properly handle zipball folder structure
- Improved download handling for GitHub releases
- Fixed "No valid plugins were found" error during updates

### Changed
- Enhanced the upgrader_source_selection filter to properly rename folders
- Improved error handling in the update process

## [1.0.1] - 2024-01-21

### Added
- Debug panel on dashboard (visible when WP_DEBUG is enabled)
- Console logging for troubleshooting scan issues
- Cache testing functionality
- Clear cache button in debug panel
- Error logging throughout scan process

### Fixed
- Fixed issue where AI Report showed "No scan results available" after successful scan
- Corrected data structure handling in markdown formatter
- Improved error handling with try-catch blocks in scanners

### Changed
- Enhanced scan result caching mechanism
- Added more detailed progress tracking
- Improved error messages for better debugging

## [1.0.0] - 2024-01-21

### Initial Release
- Post Type Scanner
- Taxonomy Scanner  
- Custom Fields Scanner
- Theme Style Scanner
- Plugin Scanner
- Theme Scanner
- Database Scanner
- Security Scanner
- AI-optimized report generation
- Markdown export
- Automatic GitHub updates
- Bootstrap-styled admin interface
EOF < /dev/null