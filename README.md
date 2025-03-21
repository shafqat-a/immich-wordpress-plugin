# Immich WordPress Plugin

This WordPress plugin allows you to search and display images from your Immich server directly in your WordPress site.

## Features

- Search images from your Immich server in real-time
- Display search results in a responsive grid layout
- Secure API key management through WordPress settings
- Live search with debouncing
- Lazy loading of images for better performance
- Mobile-friendly interface
- Easy to use shortcode: `[immich_search]`

## Installation

1. Download the plugin files from this repository
2. Upload the plugin folder to the `/wp-content/plugins/` directory of your WordPress installation
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Go to Settings > Immich Settings to configure your Immich server URL and API key

## Configuration

1. Navigate to Settings > Immich Settings in your WordPress admin panel
2. Enter your Immich server URL (e.g., https://your-immich-server.com)
3. Enter your API key from your Immich server
4. Save changes

## Usage

Add the search form to any page or post using the shortcode:

```
[immich_search]
```

The search form will appear with a text input field and a search button. As users type, the plugin will automatically search for matching images in your Immich server and display them in a responsive grid.

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- Active Immich server instance
- Valid Immich API key

## Security

- All API requests are made server-side
- API key is stored securely in WordPress options
- All user inputs are sanitized
- AJAX requests are protected with nonces

## Support

For support, please create an issue in the [GitHub repository](https://github.com/shafqat-a/immich-wordpress-plugin).

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

This plugin is licensed under the GPL v2 or later.

## Changelog

### 1.0.0
- Initial release
- Basic image search functionality
- Responsive grid layout
- Real-time search with debouncing
- Settings page for API configuration