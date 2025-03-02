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
// In your admin class
public function enqueue_admin_assets($hook) {
    if (strpos($hook, 'fruugosync-categories') !== false) {
        wp_enqueue_style(
            'fruugosync-category-mapping',
            FRUUGOSYNC_URL . 'assets/css/category-mapping.css',
            array(),
            FRUUGOSYNC_VERSION
        );

        wp_enqueue_script(
            'fruugosync-category-mapping',
            FRUUGOSYNC_URL . 'assets/js/category-mapping.js',
            array('jquery'),
            FRUUGOSYNC_VERSION,
            true
        );

        wp_localize_script('fruugosync-category-mapping', 'fruugosync_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('fruugosync-ajax-nonce')
        ));
    }
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
                update_option('fruugosync_api_status', array(
                    'status' => 'connected',
                    'error' => '',
                    'last_check' => time()
                ));
                add_settings_error(
                    'fruugosync_messages',
                    'connection_success',
                    __('Settings saved and connection successful', 'fruugosync'),
                    'success'
                );
            } else {
                update_option('fruugosync_api_status', array(
                    'status' => 'disconnected',
                    'error' => $test_result['message'],
                    'last_check' => time()
                ));
                add_settings_error(
                    'fruugosync_messages',
                    'connection_failed',
                    sprintf(__('Settings saved but connection failed: %s', 'fruugosync'), $test_result['message']),
                    'error'
                );
            }
        }
    
        // Get current API status
        $api_status = get_option('fruugosync_api_status', array(
            'status' => 'unknown',
            'error' => '',
            'last_check' => 0
        ));
    
        // Ensure we have array format
        if (!is_array($api_status)) {
            $api_status = array(
                'status' => 'unknown',
                'error' => '',
                'last_check' => 0
            );
        }
    
        // Include template
        require_once FRUUGOSYNC_TEMPLATES_PATH . 'admin/settings-page.php';
    }



    /**
     * Render category mapping page
     */
/**
 * get woocommerce categories
 */
private function get_woocommerce_categories() {
    $args = [
        'taxonomy'   => 'product_cat',
        'hide_empty' => false,
    ];

    $categories = get_terms($args);

    if (is_wp_error($categories)) {
        return [];
    }

    $formatted_categories = [];
    foreach ($categories as $category) {
        $formatted_categories[] = [
            'id'   => $category->term_id,
            'name' => $category->name,
            'slug' => $category->slug,
        ];
    }

    return $formatted_categories;
}



/**
 * Render category mapping page
 */

 private function render_hierarchical_categories($categories, $level = 0) {
    if (empty($categories) || !is_array($categories)) {
        return ''; // Return early if the data is not valid
    }

    $html = '';
    foreach ($categories as $category) {
        if (is_string($category)) {
            $html .= '<option value="' . esc_attr($category) . '">' . esc_html($category) . '</option>';
        } elseif (is_array($category) && isset($category['name'])) {
            $indentation = str_repeat('&nbsp;&nbsp;&nbsp;', $level);
            $html .= '<option value="' . esc_attr($category['id'] ?? $category['name']) . '">' .
                $indentation . esc_html($category['name']) . '</option>';

            if (!empty($category['children'])) {
                $html .= $this->render_hierarchical_categories($category['children'], $level + 1);
            }
        }
    }
    return $html;
}



public function render_category_mapping_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    $woocommerce_categories = $this->get_woocommerce_categories();

    if (empty($woocommerce_categories) || !is_array($woocommerce_categories)) {
        $woocommerce_categories = []; // Fallback if no categories are found
    }

    $categories = $this->api->get_categories();

    if (!$categories['success'] || empty($categories['data'])) {
        echo '<div class="notice notice-error"><p>' . __('Failed to load Fruugo categories.', 'fruugosync') . '</p></div>';
        return;
    }

    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <div class="notice notice-info">
            <p><?php _e('Map your WooCommerce categories to Fruugo categories.', 'fruugosync'); ?></p>
        </div>
        <table class="category-mapping-table">
            <thead>
                <tr>
                    <th><?php _e('WooCommerce Category', 'fruugosync'); ?></th>
                    <th><?php _e('Fruugo Category', 'fruugosync'); ?></th>
                    <th><?php _e('Actions', 'fruugosync'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($woocommerce_categories as $wc_category): ?>
                    <tr>
                        <td><?php echo esc_html($wc_category['name']); ?></td>
                        <td>
                            <select class="fruugo-category-dropdown">
                                <option value=""><?php _e('Select Fruugo Category', 'fruugosync'); ?></option>
                                <?php echo $this->render_hierarchical_categories($categories['data']); ?>
                            </select>
                        </td>
                        <td>
                            <button class="button save-mapping" data-wc-category="<?php echo esc_attr($wc_category['slug']); ?>">
                                <?php _e('Save Mapping', 'fruugosync'); ?>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}

    /**
     * AJAX handler for testing API connection
     */
    // public function ajax_test_connection() {
    //     check_ajax_referer('fruugosync-ajax-nonce', 'nonce');
        
    //     if (!current_user_can('manage_options')) {
    //         wp_send_json_error(array(
    //             'message' => __('Unauthorized', 'fruugosync')
    //         ));
    //     }

    //     $result = $this->api->test_connection();
        
    //     if ($result['success']) {
    //         wp_send_json_success(array(
    //             'message' => __('Successfully connected to Fruugo API', 'fruugosync')
    //         ));
    //     } else {
    //         wp_send_json_error(array(
    //             'message' => $result['message']
    //         ));
    //     }
    // }

    public function ajax_test_connection() {
        check_ajax_referer('fruugosync-ajax-nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => __('Unauthorized', 'fruugosync')
            ));
        }
    
        $result = $this->api->test_connection();
        
        if ($result['success']) {
            // Update API status on successful connection
            update_option('fruugosync_api_status', array(
                'status' => 'connected',
                'error' => '',
                'last_check' => time()
            ));
    
            wp_send_json_success(array(
                'message' => __('Successfully connected to Fruugo API', 'fruugosync')
            ));
        } else {
            // Update API status on failed connection
            update_option('fruugosync_api_status', array(
                'status' => 'disconnected',
                'error' => $result['message'],
                'last_check' => time()
            ));
    
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
    
        // Delete existing transients
        delete_transient('fruugosync_categories');
        delete_transient('fruugosync_level1_categories');
    
        $result = $this->api->get_categories(true);
        
        if ($result['success']) {
            wp_send_json_success(array(
                'level1_categories' => $result['data']['level1_categories'],
                'message' => __('Categories refreshed successfully', 'fruugosync')
            ));
        } else {
            wp_send_json_error(array(
                'message' => $result['message']
            ));
        }
    }
    
    /**
     * AJAX handler for getting subcategories
     */
    public function ajax_get_subcategories() {
        check_ajax_referer('fruugosync-ajax-nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }
    
        $parent = isset($_POST['parent']) ? sanitize_text_field($_POST['parent']) : '';
        $level = isset($_POST['level']) ? intval($_POST['level']) : 1;
    
        if (empty($parent)) {
            wp_send_json_error(array('message' => 'Parent category is required'));
        }
    
        $result = $this->api->get_subcategories($parent, $level);
        
        if ($result['success']) {
            wp_send_json_success($result['data']);
        } else {
            wp_send_json_error(array('message' => $result['message']));
        }
    }
    /**
     * AJAX handler for saving category mapping
     */
    public function ajax_save_category_mapping() {
        check_ajax_referer('fruugosync-ajax-nonce', 'nonce');
    
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Unauthorized', 'fruugosync')]);
        }
    
        $wc_category = isset($_POST['wc_category']) ? sanitize_text_field($_POST['wc_category']) : '';
        $fruugo_category = isset($_POST['fruugo_category']) ? sanitize_text_field($_POST['fruugo_category']) : '';
    
        if (empty($wc_category) || empty($fruugo_category)) {
            wp_send_json_error(['message' => __('Invalid data provided.', 'fruugosync')]);
        }
    
        // Save the mapping in the database (example using options)
        $mappings = get_option('fruugosync_category_mappings', []);
        $mappings[$wc_category] = $fruugo_category;
    
        if (update_option('fruugosync_category_mappings', $mappings)) {
            wp_send_json_success(['message' => __('Mapping saved successfully.', 'fruugosync')]);
        } else {
            wp_send_json_error(['message' => __('Failed to save mapping.', 'fruugosync')]);
        }
    }
    
    
}