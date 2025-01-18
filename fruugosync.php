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

final class FruugoSync_Loader {
    private static $instance = null;
    private $settings;
    private $api;
    private $admin;
    private $product;

    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->check_requirements();
        $this->includes();
        $this->init_hooks();
        $this->init_components();
    }

    private function check_requirements() {
        if (version_compare(PHP_VERSION, '7.4', '<')) {
            add_action('admin_notices', array($this, 'php_version_notice'));
            return;
        }

        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', array($this, 'woocommerce_notice'));
            return;
        }
    }

    private function includes() {
        require_once FRUUGOSYNC_INCLUDES_PATH . 'class-fruugosync-settings.php';
        require_once FRUUGOSYNC_INCLUDES_PATH . 'class-fruugosync-api.php';
        require_once FRUUGOSYNC_INCLUDES_PATH . 'class-fruugosync-admin.php';
        require_once FRUUGOSYNC_INCLUDES_PATH . 'class-fruugosync-product.php';
    }

    private function init_components() {
        $this->settings = new FruugoSync_Settings();
        $this->api = new FruugoSync_API();
        $this->admin = new FruugoSync_Admin();
        $this->product = new FruugoSync_Product();
    }

    private function init_hooks() {
        // Plugin lifecycle hooks
        register_activation_hook(FRUUGOSYNC_FILE, array($this, 'activate'));
        register_deactivation_hook(FRUUGOSYNC_FILE, array($this, 'deactivate'));
        
        // Admin hooks
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        add_filter('plugin_action_links_' . plugin_basename(FRUUGOSYNC_FILE), array($this, 'add_plugin_links'));
    }

    public function admin_enqueue_scripts($hook) {
        // Only load on our plugin pages
        if (strpos($hook, 'fruugosync') === false) {
            return;
        }

        wp_enqueue_style(
            'fruugosync-admin',
            FRUUGOSYNC_ASSETS_URL . 'css/admin.css',
            array(),
            FRUUGOSYNC_VERSION
        );

        wp_enqueue_script(
            'fruugosync-admin',
            FRUUGOSYNC_ASSETS_URL . 'js/admin.js',
            array('jquery'),
            FRUUGOSYNC_VERSION,
            true
        );

        wp_localize_script('fruugosync-admin', 'fruugosync_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('fruugosync-ajax-nonce')
        ));
    }

    public function activate() {
        $this->create_directories();
        $this->set_default_options();
        flush_rewrite_rules();
    }

    public function deactivate() {
        flush_rewrite_rules();
    }

    private function create_directories() {
        // Create upload directory
        $upload_dir = wp_upload_dir();
        $fruugo_dir = $upload_dir['basedir'] . '/cedcommerce_fruugouploads';
        
        if (!file_exists($fruugo_dir)) {
            wp_mkdir_p($fruugo_dir);
        }

        // Create plugin directories
        $directories = array(
            FRUUGOSYNC_PATH . 'assets/css',
            FRUUGOSYNC_PATH . 'assets/js',
            FRUUGOSYNC_PATH . 'templates/admin'
        );

        foreach ($directories as $directory) {
            if (!file_exists($directory)) {
                wp_mkdir_p($directory);
            }
        }
    }

    private function set_default_options() {
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
    }

    public function add_plugin_links($links) {
        $settings_link = '<a href="admin.php?page=fruugosync-settings">' . 
                        __('Settings', 'fruugosync') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }

    public function php_version_notice() {
        ?>
        <div class="notice notice-error">
            <p><?php 
                printf(
                    __('FruugoSync requires PHP version 7.4 or higher. You are running version %s.', 'fruugosync'), 
                    PHP_VERSION
                ); 
            ?></p>
        </div>
        <?php
    }

    public function woocommerce_notice() {
        ?>
        <div class="notice notice-error">
            <p><?php _e('FruugoSync requires WooCommerce to be installed and activated.', 'fruugosync'); ?></p>
        </div>
        <?php
    }

    public function __clone() {
        _doing_it_wrong(__FUNCTION__, __('Cloning is forbidden.', 'fruugosync'), FRUUGOSYNC_VERSION);
    }

    public function __wakeup() {
        _doing_it_wrong(__FUNCTION__, __('Unserializing instances of this class is forbidden.', 'fruugosync'), FRUUGOSYNC_VERSION);
    }
}

function FruugoSync() {
    return FruugoSync_Loader::instance();
}

// Initialize the plugin
add_action('plugins_loaded', 'FruugoSync', 20);