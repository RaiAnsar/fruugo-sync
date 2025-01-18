<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handles admin interface functionality
 */
class FruugoSync_Admin {
    /**
     * @var FruugoSync_Settings
     */
    private $settings;

    /**
     * @var FruugoSync_API
     */
    private $api;

    /**
     * @var string Store last error message
     */
    private $last_error = '';

    /**
     * Constructor
     */
    public function __construct() {
        $this->settings = new FruugoSync_Settings();
        $this->api = new FruugoSync_API();
        $this->init_hooks();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Admin scripts and styles
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        
        // AJAX handlers
        add_action('wp_ajax_test_fruugo_connection', array($this, 'ajax_test_connection'));
        add_action('wp_ajax_refresh_fruugo_categories', array($this, 'ajax_refresh_categories'));
        add_action('wp_ajax_save_category_mapping', array($this, 'ajax_save_category_mapping'));

        // Settings
        add_action('admin_init', array($this, 'register_settings'));
    }

    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('fruugosync_settings_group', 'fruugosync_username');
        register_setting('fruugosync_settings_group', 'fruugosync_password');
        register_setting('fruugosync_settings_group', 'fruugosync_debug_mode');
    }

    /**
     * Add admin menu items
     */
    public function add_admin_menu() {
        add_menu_page(
            __('FruugoSync', 'fruugosync'),
            __('FruugoSync', 'fruugosync'),
            'manage_options',
            'fruugosync-settings',
            array($this, 'render_settings_page'),
            'dashicons-synchronization'
        );

        add_submenu_page(
            'fruugosync-settings',
            __('Category Mapping', 'fruugosync'),
            __('Category Mapping', 'fruugosync'),
            'manage_options',
            'fruugosync-categories',
            array($this, 'render_category_mapping_page')
        );
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
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
            'nonce' => wp_create_nonce('fruugosync-ajax-nonce'),
            'i18n' => array(
                'testing_connection' => __('Testing connection...', 'fruugosync'),
                'connection_success' => __('Successfully connected', 'fruugosync'),
                'connection_failed' => __('Connection failed', 'fruugosync'),
                'refreshing_categories' => __('Refreshing categories...', 'fruugosync'),
                'saving_mappings' => __('Saving mappings...', 'fruugosync')
            )
        ));
    }

    /**
     * Render settings page
     */
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        // Handle form submission
        if (isset($_POST['fruugosync_settings_submit'])) {
            check_admin_referer('fruugosync_settings');
            
            $username = sanitize_text_field($_POST['fruugosync_username']);
            $password = sanitize_text_field($_POST['fruugosync_password']);
            
            update_option('fruugosync_username', $username);
            update_option('fruugosync_password', $password);
            
            // Test connection with new credentials
            $test_result = $this->api->test_connection();
            if ($test_result['success']) {
                add_settings_error(
                    'fruugosync_messages',
                    'connection_success',
                    __('Settings saved and connection successful', 'fruugosync'),
                    'success'
                );
            } else {
                add_settings_error(
                    'fruugosync_messages',
                    'connection_failed',
                    sprintf(__('Settings saved but connection failed: %s', 'fruugosync'), $test_result['message']),
                    'error'
                );
            }
        }

        // Get current settings and status
        $api_status = $this->settings->get('fruugosync_api_status', array(
            'status' => 'unknown',
            'error' => ''
        ));

        // Include template
        require_once FRUUGOSYNC_TEMPLATES_PATH . 'admin/settings-page.php';
    }

    /**
     * Render category mapping page
     */
    public function render_category_mapping_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        // Check API connection first
        $api_status = $this->settings->get('fruugosync_api_status');
        if ($api_status['status'] !== 'connected') {
            add_settings_error(
                'fruugosync_messages',
                'not_connected',
                __('Please configure and test your API connection before mapping categories.', 'fruugosync'),
                'error'
            );
        }

        $woo_categories = get_terms(array(
            'taxonomy' => 'product_cat',
            'hide_empty' => false
        ));

        // Get Fruugo categories with error handling
        $fruugo_categories = $this->api->get_categories();
        $current_mappings = $this->settings->get_category_mappings();

        if (!$fruugo_categories['success']) {
            $this->last_error = $fruugo_categories['message'];
        }

        // Include template
        require_once FRUUGOSYNC_TEMPLATES_PATH . 'admin/category-mapping.php';
    }

    /**
     * AJAX handler for testing API connection
     */
    public function ajax_test_connection() {
        check_ajax_referer('fruugosync-ajax-nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => __('Unauthorized', 'fruugosync')
            ));
        }

        $result = $this->api->test_connection();
        
        if ($result['success']) {
            wp_send_json_success(array(
                'message' => __('Successfully connected to Fruugo API', 'fruugosync')
            ));
        } else {
            wp_send_json_error(array(
                'message' => $result['message']
            ));
        }
    }

    /**
     * AJAX handler for refreshing categories
     */
    public function ajax_refresh_categories() {
        check_ajax_referer('fruugosync-ajax-nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => __('Unauthorized', 'fruugosync')
            ));
        }

        $this->api->clear_cache();
        $result = $this->api->get_categories();
        
        if ($result['success']) {
            wp_send_json_success(array(
                'categories' => $result['data'],
                'message' => __('Categories refreshed successfully', 'fruugosync')
            ));
        } else {
            wp_send_json_error(array(
                'message' => $result['message']
            ));
        }
    }

    /**
     * AJAX handler for saving category mapping
     */
    public function ajax_save_category_mapping() {
        check_ajax_referer('fruugosync-ajax-nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => __('Unauthorized', 'fruugosync')
            ));
        }

        if (!isset($_POST['mappings']) || !is_array($_POST['mappings'])) {
            wp_send_json_error(array(
                'message' => __('Invalid mapping data', 'fruugosync')
            ));
        }

        $mappings = array_map('sanitize_text_field', $_POST['mappings']);
        $success = $this->settings->update('fruugosync_category_mappings', $mappings);
        
        if ($success) {
            wp_send_json_success(array(
                'message' => __('Category mappings saved successfully', 'fruugosync')
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Failed to save category mappings', 'fruugosync')
            ));
        }
    }
}