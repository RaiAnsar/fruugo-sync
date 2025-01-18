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

    private function get_fruugo_categories() {
        // This is a placeholder - we need to implement actual API call
        // For now, returning sample data
        return array(
            array('id' => '1', 'name' => 'Electronics'),
            array('id' => '2', 'name' => 'Home & Garden'),
            // Add more categories from Fruugo API
        );
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