<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Settings management class
 */
class FruugoSync_Settings {
    /**
     * @var array Default settings values
     */
    private $defaults = array();

    /**
     * @var array Plugin settings
     */
    private $settings = array();

    /**
     * Constructor
     */
    public function __construct() {
        $this->init_defaults();
        $this->init_settings();
        $this->init_hooks();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('admin_init', array($this, 'register_settings'));
    }

    /**
     * Initialize default settings
     */
    private function init_defaults() {
        $this->defaults = array(
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
    }

    /**
     * Initialize settings
     */
    private function init_settings() {
        foreach ($this->defaults as $key => $default) {
            $this->settings[$key] = get_option($key, $default);
        }
    }

    /**
     * Register WordPress settings
     */
    public function register_settings() {
        // Register all settings
        foreach ($this->defaults as $key => $default) {
            register_setting(
                'fruugosync_settings_group',
                $key,
                array(
                    'type' => 'array',
                    'sanitize_callback' => array($this, 'sanitize_setting'),
                    'default' => $default
                )
            );
        }
    }

    /**
     * Sanitize setting value
     */
    public function sanitize_setting($value) {
        if (is_array($value)) {
            array_walk_recursive($value, function(&$item) {
                if (is_string($item)) {
                    $item = sanitize_text_field($item);
                }
            });
            return $value;
        }
        return is_string($value) ? sanitize_text_field($value) : $value;
    }

    /**
     * Get a setting value
     */
    public function get($key, $default = false) {
        if (isset($this->settings[$key])) {
            return $this->settings[$key];
        }
        return $default;
    }

    /**
     * Update a setting value
     */
    public function update($key, $value) {
        // Sanitize the value
        $value = $this->sanitize_setting($value);

        // Update local cache
        $this->settings[$key] = $value;

        // Update WordPress option
        return update_option($key, $value);
    }

    /**
     * Get API credentials
     */
    public function get_api_credentials() {
        return array(
            'username' => $this->get('fruugosync_username', ''),
            'password' => $this->get('fruugosync_password', '')
        );
    }

    /**
     * Update API status
     */
    public function update_api_status($status, $error = '') {
        return $this->update('fruugosync_api_status', array(
            'status' => $status,
            'last_check' => time(),
            'error' => $error
        ));
    }

    /**
     * Get category mappings
     */
    public function get_category_mappings() {
        return $this->get('fruugosync_category_mappings', array());
    }

    /**
     * Update category mapping
     */
    public function update_category_mapping($wc_category_id, $fruugo_category_id) {
        $mappings = $this->get_category_mappings();
        $mappings[$wc_category_id] = $fruugo_category_id;
        return $this->update('fruugosync_category_mappings', $mappings);
    }

    /**
     * Delete category mapping
     */
    public function delete_category_mapping($wc_category_id) {
        $mappings = $this->get_category_mappings();
        if (isset($mappings[$wc_category_id])) {
            unset($mappings[$wc_category_id]);
            return $this->update('fruugosync_category_mappings', $mappings);
        }
        return false;
    }

    /**
     * Get mapped Fruugo category for a WooCommerce category
     */
    public function get_mapped_fruugo_category($wc_category_id) {
        $mappings = $this->get_category_mappings();
        return isset($mappings[$wc_category_id]) ? $mappings[$wc_category_id] : false;
    }

    /**
     * Check if debug mode is enabled
     */
    public function is_debug_mode() {
        return (bool) $this->get('fruugosync_debug_mode', false);
    }

    /**
     * Reset all settings to defaults
     */
    public function reset_settings() {
        foreach ($this->defaults as $key => $default) {
            $this->update($key, $default);
        }
        return true;
    }

    /**
     * Export settings
     */
    public function export_settings() {
        return array(
            'settings' => $this->settings,
            'version' => FRUUGOSYNC_VERSION,
            'timestamp' => current_time('timestamp')
        );
    }

    /**
     * Import settings
     */
    public function import_settings($data) {
        if (!isset($data['settings']) || !is_array($data['settings'])) {
            return false;
        }

        foreach ($data['settings'] as $key => $value) {
            if (array_key_exists($key, $this->defaults)) {
                $this->update($key, $value);
            }
        }

        return true;
    }
}