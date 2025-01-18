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

class FruugoSync {
    private $api_base_url = 'https://api.fruugo.com/v3/';
    private $upload_dir;
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // Set upload directory
        $wp_upload_dir = wp_upload_dir();
        $this->upload_dir = $wp_upload_dir['basedir'] . '/cedcommerce_fruugouploads/';
    }

    public function add_admin_menu() {
        add_menu_page(
            'FruugoSync Settings',
            'FruugoSync',
            'manage_options',
            'fruugosync-settings',
            array($this, 'display_settings_page'),
            'dashicons-synchronization'
        );

        add_submenu_page(
            'fruugosync-settings',
            'Category Mapping',
            'Category Mapping',
            'manage_options',
            'fruugosync-categories',
            array($this, 'display_category_mapping')
        );
    }

    public function register_settings() {
        register_setting('fruugosync_settings', 'fruugosync_username');
        register_setting('fruugosync_settings', 'fruugosync_password');
        register_setting('fruugosync_settings', 'fruugosync_category_mappings');
    }

    public function enqueue_admin_scripts($hook) {
        if ('fruugosync_page_fruugosync-categories' !== $hook) {
            return;
        }
        
        wp_enqueue_script('fruugosync-admin', plugins_url('js/admin.js', __FILE__), array('jquery'), '1.0.0', true);
        wp_localize_script('fruugosync-admin', 'fruugosync_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('fruugosync-ajax-nonce')
        ));
    }

    public function display_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('fruugosync_settings');
                do_settings_sections('fruugosync_settings');
                ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="fruugosync_username">Fruugo Username</label>
                        </th>
                        <td>
                            <input type="text" id="fruugosync_username" name="fruugosync_username" 
                                   value="<?php echo esc_attr(get_option('fruugosync_username')); ?>" class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="fruugosync_password">Fruugo Password</label>
                        </th>
                        <td>
                            <input type="password" id="fruugosync_password" name="fruugosync_password" 
                                   value="<?php echo esc_attr(get_option('fruugosync_password')); ?>" class="regular-text">
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>

            <div class="card">
                <h2>Product Export Status</h2>
                <p>CSV Export Directory: <?php echo esc_html($this->upload_dir); ?></p>
                <button type="button" class="button button-primary" id="generate_product_csv">
                    Generate Product CSV
                </button>
            </div>
        </div>
        <?php
    }

    public function display_category_mapping() {
        $fruugo_categories = $this->get_fruugo_categories();
        $woo_categories = get_terms([
            'taxonomy' => 'product_cat',
            'hide_empty' => false,
        ]);
        $saved_mappings = get_option('fruugosync_category_mappings', array());
        
        ?>
        <div class="wrap">
            <h1>Category Mapping</h1>
            <form method="post" action="options.php">
                <?php settings_fields('fruugosync_settings'); ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>WooCommerce Category</th>
                            <th>Fruugo Category</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($woo_categories as $woo_cat): ?>
                        <tr>
                            <td><?php echo esc_html($woo_cat->name); ?></td>
                            <td>
                                <select name="category_mapping[<?php echo esc_attr($woo_cat->term_id); ?>]">
                                    <option value="">Select Fruugo Category</option>
                                    <?php foreach ($fruugo_categories as $fruugo_cat): ?>
                                        <option value="<?php echo esc_attr($fruugo_cat['id']); ?>"
                                            <?php selected(isset($saved_mappings[$woo_cat->term_id]) ? $saved_mappings[$woo_cat->term_id] : '', $fruugo_cat['id']); ?>>
                                            <?php echo esc_html($fruugo_cat['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php submit_button('Save Category Mappings'); ?>
            </form>
        </div>
        <?php
    }



    public function generate_product_csv() {
        $products = wc_get_products(array(
            'limit' => -1,
            'status' => 'publish'
        ));

        $csv_file = $this->upload_dir . 'merchant_products.csv';
        $fp = fopen($csv_file, 'w');

        // Write CSV headers
        fputcsv($fp, array(
            'ProductId',
            'SkuId',
            'Title',
            'Description',
            'Category',
            'Price',
            'StockQuantity'
        ));

        foreach ($products as $product) {
            fputcsv($fp, array(
                $product->get_id(),
                $product->get_sku(),
                $product->get_name(),
                strip_tags($product->get_description()),
                $this->get_mapped_category($product),
                $product->get_price(),
                $product->get_stock_quantity()
            ));
        }

        fclose($fp);
        return $csv_file;
    }

    private function get_mapped_category($product) {
        $mappings = get_option('fruugosync_category_mappings', array());
        $woo_cats = get_the_terms($product->get_id(), 'product_cat');
        
        if (!$woo_cats || is_wp_error($woo_cats)) {
            return '';
        }

        foreach ($woo_cats as $cat) {
            if (isset($mappings[$cat->term_id])) {
                return $mappings[$cat->term_id];
            }
        }

        return '';
    }

    private function make_api_request($endpoint, $method = 'GET', $body = null) {
        $username = get_option('fruugosync_username');
        $password = get_option('fruugosync_password');
        
        if (empty($username) || empty($password)) {
            $this->add_admin_notice('Fruugo API credentials are not configured.', 'error');
            return false;
        }
        
        $args = array(
            'method' => $method,
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode($username . ':' . $password),
                'Content-Type' => 'application/json',
                'X-Correlation-ID' => uniqid('fruugosync_'),
            ),
            'timeout' => 30
        );
    
        if ($body) {
            $args['body'] = json_encode($body);
        }
    
        $response = wp_remote_request($this->api_base_url . $endpoint, $args);
    
        if (is_wp_error($response)) {
            $this->add_admin_notice('API Error: ' . $response->get_error_message(), 'error');
            error_log('Fruugo API Error: ' . $response->get_error_message());
            return false;
        }
    
        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code !== 200) {
            $error_message = wp_remote_retrieve_response_message($response);
            $this->add_admin_notice("API Error (HTTP $response_code): $error_message", 'error');
            error_log("Fruugo API Error: HTTP $response_code - $error_message");
            return false;
        }
    
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->add_admin_notice('Failed to parse API response', 'error');
            error_log('Fruugo API Error: Invalid JSON response');
            return false;
        }
    
        return $data;
    }
    
    private function add_admin_notice($message, $type = 'success') {
        add_settings_error(
            'fruugosync_messages',
            'fruugosync_message',
            $message,
            $type
        );
    }
    
    private function get_fruugo_categories($force_refresh = false) {
        if ($force_refresh) {
            delete_transient('fruugosync_categories');
        }
    
        // Check cache first
        $cached_categories = get_transient('fruugosync_categories');
        if ($cached_categories && !$force_refresh) {
            return $cached_categories;
        }
    
        // Fetch from API
        $categories = $this->make_api_request('categories');
        
        if ($categories) {
            // Process categories into hierarchical structure
            $processed_categories = $this->process_categories_hierarchy($categories);
            // Cache for 24 hours
            set_transient('fruugosync_categories', $processed_categories, DAY_IN_SECONDS);
            return $processed_categories;
        }
        
        return array();
    }
    
    private function process_categories_hierarchy($categories) {
        // Process categories into parent-child relationship
        $categoriesById = array();
        $rootCategories = array();
    
        // First pass: create category objects
        foreach ($categories as $category) {
            $categoriesById[$category['id']] = array(
                'id' => $category['id'],
                'name' => $category['name'],
                'parent_id' => isset($category['parent_id']) ? $category['parent_id'] : null,
                'children' => array()
            );
        }
    
        // Second pass: build hierarchy
        foreach ($categoriesById as $id => $category) {
            if ($category['parent_id'] && isset($categoriesById[$category['parent_id']])) {
                $categoriesById[$category['parent_id']]['children'][] = &$categoriesById[$id];
            } else {
                $rootCategories[] = &$categoriesById[$id];
            }
        }
    
        return $rootCategories;
    }
    
    private function render_category_options($categories, $selected_value, $level = 0) {
        $output = '';
        foreach ($categories as $category) {
            $padding = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $level);
            $selected = selected($selected_value, $category['id'], false);
            $output .= sprintf(
                '<option value="%s" %s>%s%s</option>',
                esc_attr($category['id']),
                $selected,
                $padding,
                esc_html($category['name'])
            );
            
            if (!empty($category['children'])) {
                $output .= $this->render_category_options($category['children'], $selected_value, $level + 1);
            }
        }
        return $output;
    }
    
    public function display_category_mapping() {
        // Handle refresh categories action
        if (isset($_POST['refresh_categories']) && check_admin_referer('fruugosync_category_mapping')) {
            $categories = $this->get_fruugo_categories(true);
            if ($categories) {
                $this->add_admin_notice('Fruugo categories refreshed successfully!');
            }
        }
    
        // Handle save mappings action
        if (isset($_POST['save_category_mappings']) && check_admin_referer('fruugosync_category_mapping')) {
            $mappings = isset($_POST['category_mapping']) ? (array) $_POST['category_mapping'] : array();
            update_option('fruugosync_category_mappings', $mappings);
            $this->add_admin_notice('Category mappings saved successfully!');
        }
    
        $fruugo_categories = $this->get_fruugo_categories();
        $woo_categories = get_terms([
            'taxonomy' => 'product_cat',
            'hide_empty' => false,
        ]);
        $saved_mappings = get_option('fruugosync_category_mappings', array());
        
        // Display any pending admin notices
        settings_errors('fruugosync_messages');
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <!-- Refresh Categories Form -->
            <form method="post" action="" style="margin-bottom: 20px;">
                <?php wp_nonce_field('fruugosync_category_mapping'); ?>
                <input type="submit" name="refresh_categories" class="button" 
                       value="<?php esc_attr_e('Refresh Fruugo Categories', 'fruugosync'); ?>">
            </form>
    
            <!-- Category Mapping Form -->
            <form method="post" action="">
                <?php wp_nonce_field('fruugosync_category_mapping'); ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>WooCommerce Category</th>
                            <th>Fruugo Category</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        if (empty($fruugo_categories)) {
                            ?>
                            <tr>
                                <td colspan="2">
                                    <?php _e('No Fruugo categories available. Please check your API credentials and try refreshing.', 'fruugosync'); ?>
                                </td>
                            </tr>
                            <?php
                        } else {
                            foreach ($woo_categories as $woo_cat): 
                            ?>
                            <tr>
                                <td><?php echo esc_html($woo_cat->name); ?></td>
                                <td>
                                    <select name="category_mapping[<?php echo esc_attr($woo_cat->term_id); ?>]">
                                        <option value=""><?php _e('Select Fruugo Category', 'fruugosync'); ?></option>
                                        <?php 
                                        echo $this->render_category_options(
                                            $fruugo_categories, 
                                            isset($saved_mappings[$woo_cat->term_id]) ? $saved_mappings[$woo_cat->term_id] : ''
                                        );
                                        ?>
                                    </select>
                                </td>
                            </tr>
                            <?php 
                            endforeach;
                        }
                        ?>
                    </tbody>
                </table>
                <p class="submit">
                    <input type="submit" name="save_category_mappings" class="button button-primary" 
                           value="<?php esc_attr_e('Save Category Mappings', 'fruugosync'); ?>">
                </p>
            </form>
        </div>
        <?php
    }


}

// Initialize the plugin
function fruugosync_init() {
    if (class_exists('WooCommerce')) {
        new FruugoSync();
    }
}
add_action('plugins_loaded', 'fruugosync_init');

// Activation hook
register_activation_hook(__FILE__, 'fruugosync_activate');

function fruugosync_activate() {
    // Create upload directory
    $upload_dir = wp_upload_dir();
    $fruugo_dir = $upload_dir['basedir'] . '/cedcommerce_fruugouploads';
    
    if (!file_exists($fruugo_dir)) {
        wp_mkdir_p($fruugo_dir);
    }
}