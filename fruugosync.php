<?php
/**
 * Plugin Name: FruugoSync for WooCommerce
 * Description: Synchronize WooCommerce products with Fruugo marketplace
 * Version: 1.0.0
 * Author: Your Name
 * Text Domain: fruugosync
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 8.0
 */

// Exit if accessed directly
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
define('FRUUGOSYNC_TEMPLATES_PATH', FRUUGOSYNC_PATH . 'templates/');

/**
 * Check if WooCommerce is active
 */
function is_woocommerce_active() {
    $active_plugins = (array) get_option('active_plugins', array());
    if (is_multisite()) {
        $active_plugins = array_merge($active_plugins, get_site_option('active_sitewide_plugins', array()));
    }
    return in_array('woocommerce/woocommerce.php', $active_plugins) || array_key_exists('woocommerce/woocommerce.php', $active_plugins);
}

/**
 * Display WooCommerce missing notice
 */
function fruugosync_wc_missing_notice() {
    ?>
    <div class="notice notice-error">
        <p><?php _e('FruugoSync requires WooCommerce to be installed and activated.', 'fruugosync'); ?></p>
    </div>
    <?php
}

/**
 * Initialize the plugin
 */
function fruugosync_init() {
    load_plugin_textdomain('fruugosync', false, dirname(plugin_basename(__FILE__)) . '/languages');

    if (!is_woocommerce_active()) {
        add_action('admin_notices', 'fruugosync_wc_missing_notice');
        return;
    }

    // Load required files
    require_once FRUUGOSYNC_INCLUDES_PATH . 'class-fruugosync.php';
    require_once FRUUGOSYNC_INCLUDES_PATH . 'class-fruugosync-admin.php';
    require_once FRUUGOSYNC_INCLUDES_PATH . 'class-fruugosync-api.php';
    require_once FRUUGOSYNC_INCLUDES_PATH . 'class-fruugosync-product.php';
    require_once FRUUGOSYNC_INCLUDES_PATH . 'class-fruugosync-settings.php';

    // Initialize plugin
    FruugoSync::instance();
}

/**
 * Plugin activation
 */
function fruugosync_activate() {
    // Create necessary directories
    $upload_dir = wp_upload_dir();
    $fruugo_dir = $upload_dir['basedir'] . '/cedcommerce_fruugouploads';
    
    if (!file_exists($fruugo_dir)) {
        wp_mkdir_p($fruugo_dir);
    }

    // Create required plugin directories
    $directories = array(
        FRUUGOSYNC_PATH . 'assets/css',
        FRUUGOSYNC_PATH . 'assets/js',
        FRUUGOSYNC_PATH . 'includes',
        FRUUGOSYNC_PATH . 'templates/admin'
    );

    foreach ($directories as $directory) {
        if (!file_exists($directory)) {
            wp_mkdir_p($directory);
        }
    }

    // Set default options
    $default_options = array(
        'fruugosync_username' => '',
        'fruugosync_password' => '',
        'fruugosync_api_status' => array(
            'status' => 'unknown',
            'last_check' => 0,
            'error' => ''
        ),
        'fruugosync_category_mappings' => array(),
        'fruugosync_debug_mode' => false
    );

    foreach ($default_options as $option_name => $default_value) {
        if (false === get_option($option_name)) {
            add_option($option_name, $default_value);
        }
    }

    // Clear any existing transients
    delete_transient('fruugosync_categories');
}

/**
 * Plugin deactivation
 */
function fruugosync_deactivate() {
    // Clear scheduled hooks if any
    wp_clear_scheduled_hook('fruugosync_sync_products');
    wp_clear_scheduled_hook('fruugosync_sync_orders');

    // Clear transients
    delete_transient('fruugosync_categories');
}

/**
 * Plugin uninstall
 */
function fruugosync_uninstall() {
    // Remove all plugin options
    $options = array(
        'fruugosync_username',
        'fruugosync_password',
        'fruugosync_api_status',
        'fruugosync_category_mappings',
        'fruugosync_debug_mode'
    );

    foreach ($options as $option) {
        delete_option($option);
    }

    // Clear transients
    delete_transient('fruugosync_categories');
}

// Register hooks
register_activation_hook(__FILE__, 'fruugosync_activate');
register_deactivation_hook(__FILE__, 'fruugosync_deactivate');
register_uninstall_hook(__FILE__, 'fruugosync_uninstall');

// Initialize plugin after WooCommerce
add_action('plugins_loaded', 'fruugosync_init', 20);

// Add settings link on plugin page
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'fruugosync_settings_link');
function fruugosync_settings_link($links) {
    $settings_link = '<a href="admin.php?page=fruugosync-settings">' . __('Settings', 'fruugosync') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}