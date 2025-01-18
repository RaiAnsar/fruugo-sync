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
 * Class FruugoSync_Loader
 * 
 * Handles plugin initialization and loading of required files
 */
final class FruugoSync_Loader {
    /**
     * @var FruugoSync_Loader Single instance of the class
     */
    private static $instance = null;

    /**
     * Main FruugoSync_Loader Instance
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * FruugoSync_Loader Constructor
     */
    private function __construct() {
        $this->check_requirements();
        $this->includes();
        $this->init_hooks();
    }

    /**
     * Check if all requirements are met
     */
    private function check_requirements() {
        // Check PHP version
        if (version_compare(PHP_VERSION, '7.4', '<')) {
            add_action('admin_notices', array($this, 'php_version_notice'));
            return;
        }

        // Check if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', array($this, 'woocommerce_notice'));
            return;
        }
    }

    /**
     * Include required files
     */
    private function includes() {
        // Core class files
        require_once FRUUGOSYNC_INCLUDES_PATH . 'class-fruugosync.php';
        require_once FRUUGOSYNC_INCLUDES_PATH . 'class-fruugosync-admin.php';
        require_once FRUUGOSYNC_INCLUDES_PATH . 'class-fruugosync-api.php';
        require_once FRUUGOSYNC_INCLUDES_PATH . 'class-fruugosync-product.php';
        require_once FRUUGOSYNC_INCLUDES_PATH . 'class-fruugosync-settings.php';
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('plugins_loaded', array($this, 'init'), 0);
        register_activation_hook(FRUUGOSYNC_FILE, array($this, 'activate'));
        register_deactivation_hook(FRUUGOSYNC_FILE, array($this, 'deactivate'));
    }

    /**
     * Initialize plugin
     */
    public function init() {
        // Initialize core classes
        FruugoSync::instance();
    }

    /**
     * Plugin activation
     */
    public function activate() {
        // Create necessary directories
        $this->create_directories();
        
        // Set default options
        $this->set_default_options();
        
        // Maybe flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Plugin deactivation
     */
    public function deactivate() {
        flush_rewrite_rules();
    }

    /**
     * Create required directories
     */
    private function create_directories() {
        // Create upload directory
        $upload_dir = wp_upload_dir();
        $fruugo_dir = $upload_dir['basedir'] . '/cedcommerce_fruugouploads';
        
        if (!file_exists($fruugo_dir)) {
            wp_mkdir_p($fruugo_dir);
        }

        // Create other required directories
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

    /**
     * Set default plugin options
     */
    private function set_default_options() {
        $default_options = array(
            'fruugosync_api_status' => array(
                'status' => 'unknown',
                'last_check' => 0
            ),
            'fruugosync_category_mappings' => array()
        );

        foreach ($default_options as $option_name => $default_value) {
            if (false === get_option($option_name)) {
                add_option($option_name, $default_value);
            }
        }
    }

    /**
     * Admin notice for PHP version requirement
     */
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

    /**
     * Admin notice for WooCommerce requirement
     */
    public function woocommerce_notice() {
        ?>
        <div class="notice notice-error">
            <p><?php _e('FruugoSync requires WooCommerce to be installed and activated.', 'fruugosync'); ?></p>
        </div>
        <?php
    }

    /**
     * Prevent cloning of the instance
     */
    public function __clone() {
        _doing_it_wrong(__FUNCTION__, __('Cloning is forbidden.', 'fruugosync'), FRUUGOSYNC_VERSION);
    }

    /**
     * Prevent unserializing of the instance
     */
    public function __wakeup() {
        _doing_it_wrong(__FUNCTION__, __('Unserializing instances of this class is forbidden.', 'fruugosync'), FRUUGOSYNC_VERSION);
    }
}

/**
 * Returns the main instance of FruugoSync_Loader
 */
function FruugoSync() {
    return FruugoSync_Loader::instance();
}

// Initialize the plugin
FruugoSync();