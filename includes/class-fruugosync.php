<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Core plugin class
 */
class FruugoSync {
    /**
     * @var FruugoSync Single instance of this class
     */
    private static $instance = null;

    /**
     * @var FruugoSync_Admin Admin instance
     */
    public $admin = null;

    /**
     * @var FruugoSync_API API instance
     */
    public $api = null;

    /**
     * @var FruugoSync_Product Product instance
     */
    public $product = null;

    /**
     * @var FruugoSync_Settings Settings instance
     */
    public $settings = null;

    /**
     * Main FruugoSync Instance
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * FruugoSync Constructor
     */
    private function __construct() {
        $this->init_hooks();
        $this->init_components();
    }

    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        add_action('init', array($this, 'init'), 0);
        add_action('wp_loaded', array($this, 'on_wp_loaded'));
        
        // Add translation support
        add_action('plugins_loaded', array($this, 'load_plugin_textdomain'));
    }

    /**
     * Initialize plugin components
     */
    private function init_components() {
        // Initialize components only if we're in admin or handling a webhook
        if (is_admin() || (defined('DOING_CRON') && DOING_CRON)) {
            $this->admin = new FruugoSync_Admin();
            $this->settings = new FruugoSync_Settings();
        }

        // These components should always be loaded
        $this->api = new FruugoSync_API();
        $this->product = new FruugoSync_Product();
    }

    /**
     * Initialize plugin
     */
    public function init() {
        // Register custom post types, taxonomies, etc. if needed
        do_action('fruugosync_init');
    }

    /**
     * Actions to perform when WordPress is fully loaded
     */
    public function on_wp_loaded() {
        // Handle any webhook callbacks or other late-loading functionality
        do_action('fruugosync_loaded');
    }

    /**
     * Load plugin translations
     */
    public function load_plugin_textdomain() {
        load_plugin_textdomain(
            'fruugosync',
            false,
            dirname(plugin_basename(FRUUGOSYNC_FILE)) . '/languages/'
        );
    }

    /**
     * Get plugin path
     */
    public function plugin_path() {
        return untrailingslashit(FRUUGOSYNC_PATH);
    }

    /**
     * Get plugin URL
     */
    public function plugin_url() {
        return untrailingslashit(FRUUGOSYNC_URL);
    }

    /**
     * Get template path
     */
    public function template_path() {
        return apply_filters('fruugosync_template_path', 'fruugosync/');
    }

    /**
     * Get the plugin file
     */
    public function get_plugin_file() {
        return FRUUGOSYNC_FILE;
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