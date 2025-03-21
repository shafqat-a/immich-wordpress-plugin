<?php
/**
 * Plugin Name: Immich Image Search
 * Plugin URI: https://github.com/shafqat-a/immich-wordpress-plugin
 * Description: Search and insert images from your Immich server in WordPress
 * Version: 1.1.0
 * Author: Shafqat
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if (!defined('ABSPATH')) {
    exit;
}

class ImmichWordPressPlugin {
    private $api_url;
    private $api_key;

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_immich_search_images', array($this, 'handle_search_request'));
        add_action('wp_ajax_nopriv_immich_search_images', array($this, 'handle_search_request'));
        add_action('wp_ajax_immich_get_image', array($this, 'handle_get_image_request'));
        add_action('media_buttons', array($this, 'add_immich_media_button'));
        add_action('admin_footer', array($this, 'add_immich_modal'));
        add_shortcode('immich_search', array($this, 'render_search_form'));
        
        $this->api_url = get_option('immich_api_url');
        $this->api_key = get_option('immich_api_key');
    }

    public function enqueue_admin_scripts($hook) {
        if ('post.php' != $hook && 'post-new.php' != $hook) {
            return;
        }

        wp_enqueue_style('immich-admin-style', plugins_url('css/admin-style.css', __FILE__));
        wp_enqueue_script('immich-admin-script', plugins_url('js/admin-script.js', __FILE__), array('jquery'), '1.1.0', true);
        wp_localize_script('immich-admin-script', 'immichAdmin', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('immich_admin_nonce')
        ));
    }

    public function add_immich_media_button() {
        echo '<button type="button" class="button immich-media-button" id="immich-media-button">';
        echo '<span class="wp-media-buttons-icon dashicons dashicons-images-alt"></span> Add Immich Image';
        echo '</button>';
    }

    public function add_immich_modal() {
        $screen = get_current_screen();
        if ($screen->base !== 'post' && $screen->base !== 'post-new') {
            return;
        }
        ?>
        <div id="immich-modal" class="immich-modal">
            <div class="immich-modal-content">
                <div class="immich-modal-header">
                    <h2>Insert Immich Image</h2>
                    <span class="immich-modal-close">&times;</span>
                </div>
                <div class="immich-modal-body">
                    <div class="immich-search-container">
                        <input type="text" id="immich-admin-search" placeholder="Search images..." />
                        <div id="immich-admin-results" class="immich-grid"></div>
                    </div>
                </div>
                <div class="immich-modal-footer">
                    <button type="button" class="button button-secondary immich-modal-close">Cancel</button>
                </div>
            </div>
        </div>
        <?php
    }

    public function handle_get_image_request() {
        check_ajax_referer('immich_admin_nonce', 'nonce');

        $image_id = sanitize_text_field($_POST['image_id']);
        
        if (empty($this->api_url) || empty($this->api_key) || empty($image_id)) {
            wp_send_json_error('Missing required parameters');
            return;
        }

        // Get the original image URL
        $image_url = $this->api_url . '/api/asset/download/' . $image_id;
        
        // Get image metadata
        $response = wp_remote_get(
            $this->api_url . '/api/asset/' . $image_id,
            array(
                'headers' => array(
                    'x-api-key' => $this->api_key,
                    'Accept' => 'application/json'
                )
            )
        );

        if (is_wp_error($response)) {
            wp_send_json_error($response->get_error_message());
            return;
        }

        $metadata = json_decode(wp_remote_retrieve_body($response), true);
        
        if (!$metadata) {
            wp_send_json_error('Failed to get image metadata');
            return;
        }

        // Download the image temporarily
        $temp_file = download_url($image_url);
        
        if (is_wp_error($temp_file)) {
            wp_send_json_error('Failed to download image');
            return;
        }

        $file_array = array(
            'name' => $metadata['originalFileName'],
            'tmp_name' => $temp_file
        );

        // Insert the image into WordPress media library
        $attachment_id = media_handle_sideload($file_array, 0);

        if (is_wp_error($attachment_id)) {
            @unlink($temp_file);
            wp_send_json_error('Failed to add image to media library');
            return;
        }

        // Get the image HTML
        $image_html = wp_get_attachment_image($attachment_id, 'full');
        
        wp_send_json_success(array(
            'id' => $attachment_id,
            'html' => $image_html,
            'url' => wp_get_attachment_url($attachment_id)
        ));
    }

    public function add_admin_menu() {
        add_options_page(
            'Immich Settings',
            'Immich Settings',
            'manage_options',
            'immich-settings',
            array($this, 'render_settings_page')
        );
    }

    public function register_settings() {
        register_setting('immich_settings', 'immich_api_url');
        register_setting('immich_settings', 'immich_api_key');
    }

    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h2>Immich Settings</h2>
            <form method="post" action="options.php">
                <?php
                settings_fields('immich_settings');
                do_settings_sections('immich_settings');
                ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">Immich Server URL</th>
                        <td>
                            <input type="url" name="immich_api_url" value="<?php echo esc_attr(get_option('immich_api_url')); ?>" class="regular-text" />
                            <p class="description">Enter your Immich server URL (e.g., https://your-immich-server.com)</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">API Key</th>
                        <td>
                            <input type="password" name="immich_api_key" value="<?php echo esc_attr(get_option('immich_api_key')); ?>" class="regular-text" />
                            <p class="description">Enter your Immich API key</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function enqueue_scripts() {
        wp_enqueue_style('immich-search-style', plugins_url('css/style.css', __FILE__));
        wp_enqueue_script('immich-search-script', plugins_url('js/script.js', __FILE__), array('jquery'), '1.0.0', true);
        wp_localize_script('immich-search-script', 'immichAjax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('immich_search_nonce')
        ));
    }

    public function handle_search_request() {
        $is_admin = check_ajax_referer('immich_admin_nonce', 'nonce', false);
        if (!$is_admin) {
            check_ajax_referer('immich_search_nonce', 'nonce');
        }

        $query = sanitize_text_field($_POST['query']);
        
        if (empty($this->api_url) || empty($this->api_key)) {
            wp_send_json_error('Immich API configuration is missing');
            return;
        }

        $response = wp_remote_get(
            $this->api_url . '/api/search?q=' . urlencode($query),
            array(
                'headers' => array(
                    'x-api-key' => $this->api_key,
                    'Accept' => 'application/json'
                )
            )
        );

        if (is_wp_error($response)) {
            wp_send_json_error($response->get_error_message());
            return;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (!$body || isset($body['error'])) {
            wp_send_json_error('Error retrieving images from Immich');
            return;
        }

        $images = array_map(function($item) {
            return array(
                'id' => $item['id'],
                'thumbnail' => $this->api_url . '/api/asset/thumbnail/' . $item['id'],
                'filename' => $item['originalFileName']
            );
        }, $body['assets'] ?? array());

        wp_send_json_success($images);
    }

    public function render_search_form() {
        ob_start();
        ?>
        <div class="immich-search-container">
            <form id="immich-search-form" class="immich-search-form">
                <input type="text" id="immich-search-input" placeholder="Search images..." />
                <button type="submit">Search</button>
            </form>
            <div id="immich-search-results" class="immich-search-results"></div>
        </div>
        <?php
        return ob_get_clean();
    }
}

$immich_plugin = new ImmichWordPressPlugin();