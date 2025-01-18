<?php
/**
 * Plugin Name: FruugoSync for WooCommerce
 * Description: Synchronize WooCommerce products with Fruugo marketplace
 * Version: 1.0.0
 * Author: Your Name
 * Text Domain: fruugosync
 * Requires WooCommerce: 5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('FRUUGOSYNC_VERSION', '1.0.0');
define('FRUUGOSYNC_FILE', __FILE__);
define('FRUUGOSYNC_PATH', plugin_dir_path(__FILE__));
define('FRUUGOSYNC_URL', plugin_dir_url(__FILE__));
define('FRUUGOSYNC_ASSETS_URL', FRUUGOSYNC_URL . 'assets/');
define('FRUUGOSYNC_INCLUDES_PATH', FRUUGOSYNC_PATH . 'includes/');

// Ensure our autoload file exists
if (file_exists(FRUUGOSYNC_INCLUDES_PATH . 'class-fruugosync.php')) {
    require_once FRUUGOSYNC_INCLUDES_PATH . 'class-fruugosync.php';
} else {
    add_action('admin_notices', function() {
        ?>
        <div class="notice notice-error">
            <p><?php _e('FruugoSync: Required files are missing. Please reinstall the plugin.', 'fruugosync'); ?></p>
        </div>
        <?php
    });
    return;
}

// Plugin activation
register_activation_hook(__FILE__, 'fruugosync_activate');
function fruugosync_activate() {
    // Create necessary directories
    $upload_dir = wp_upload_dir();
    $fruugo_dir = $upload_dir['basedir'] . '/cedcommerce_fruugouploads';
    
    if (!file_exists($fruugo_dir)) {
        wp_mkdir_p($fruugo_dir);
    }

    // Create assets directories if they don't exist
    $assets_dir = FRUUGOSYNC_PATH . 'assets/js';
    if (!file_exists($assets_dir)) {
        wp_mkdir_p($assets_dir);
    }

    // Create admin.js if it doesn't exist
    $admin_js_file = $assets_dir . '/admin.js';
    if (!file_exists($admin_js_file)) {
        $admin_js_content = <<<'EOT'
jQuery(document).ready(function($) {
    // Test Connection Handler
    $('#test-connection').on('click', function() {
        var $button = $(this);
        var $status = $('#api-status-indicator');
        
        // Disable button and show loading state
        $button.prop('disabled', true).text('Testing...');
        
        $.ajax({
            url: fruugosync_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'test_fruugo_connection',
                nonce: fruugosync_ajax.nonce
            },
            success: function(response) {
                $status.removeClass('connected disconnected unknown');
                
                if (response.success) {
                    $status.addClass('connected')
                        .html('<span class="status-text">Connected</span>');
                } else {
                    $status.addClass('disconnected')
                        .html('<span class="status-text">Not Connected</span>' +
                              '<p class="error-message">' + response.message + '</p>');
                }
            },
            error: function(xhr, status, error) {
                $status.removeClass('connected disconnected unknown')
                    .addClass('disconnected')
                    .html('<span class="status-text">Not Connected</span>' +
                          '<p class="error-message">Ajax error: ' + error + '</p>');
            },
            complete: function() {
                // Reset button state
                $button.prop('disabled', false).text('Test Connection');
            }
        });
    });

    // Generate CSV Handler
    $('#generate-csv').on('click', function() {
        var $button = $(this);
        $button.prop('disabled', true).text('Generating...');
        
        $.ajax({
            url: fruugosync_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'generate_fruugo_csv',
                nonce: fruugosync_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert('CSV generated successfully!');
                } else {
                    alert('Error generating CSV: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                alert('Error generating CSV: ' + error);
            },
            complete: function() {
                $button.prop('disabled', false).text('Generate Product CSV');
            }
        });
    });
});
EOT;
        file_put_contents($admin_js_file, $admin_js_content);
    }
}

// Plugin deactivation
register_deactivation_hook(__FILE__, 'fruugosync_deactivate');
function fruugosync_deactivate() {
    // Clean up if needed
}

// Initialize the plugin
function fruugosync_init() {
    // Check if WooCommerce is active
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', function() {
            ?>
            <div class="notice notice-error">
                <p><?php _e('FruugoSync requires WooCommerce to be installed and activated.', 'fruugosync'); ?></p>
            </div>
            <?php
        });
        return;
    }

    // Initialize the main plugin class
    global $fruugosync;
    $fruugosync = new FruugoSync();
}
add_action('plugins_loaded', 'fruugosync_init');

// Add settings link on plugin page
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'fruugosync_settings_link');
function fruugosync_settings_link($links) {
    $settings_link = '<a href="admin.php?page=fruugosync-settings">' . __('Settings', 'fruugosync') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}