<?php
/**
 * Plugin Name: FruugoSync for WooCommerce
 * Plugin URI: https://example.com/fruugosync
 * Description: Synchronize WooCommerce products with Fruugo marketplace
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * Text Domain: fruugosync
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 8.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('FRUUGOSYNC_VERSION', '1.0.0');
define('FRUUGOSYNC_FILE', __FILE__);
define('FRUUGOSYNC_PATH', plugin_dir_path(__FILE__));
define('FRUUGOSYNC_URL', plugin_dir_url(__FILE__));

// Include required files
require_once FRUUGOSYNC_PATH . 'includes/class-fruugosync.php';

// Initialize the plugin
function fruugosync_init() {
    if (class_exists('WooCommerce')) {
        $plugin = new FruugoSync();
        $plugin->init();
    }
}
add_action('plugins_loaded', 'fruugosync_init');

// Activation hook
register_activation_hook(__FILE__, 'fruugosync_activate');
function fruugosync_activate() {
    // Create necessary directories
    $upload_dir = wp_upload_dir();
    $fruugo_dir = $upload_dir['basedir'] . '/cedcommerce_fruugouploads';
    
    if (!file_exists($fruugo_dir)) {
        wp_mkdir_p($fruugo_dir);
    }
}