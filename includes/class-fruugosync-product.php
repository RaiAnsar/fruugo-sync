<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handles product operations between WooCommerce and Fruugo
 */
class FruugoSync_Product {
    /**
     * @var FruugoSync_API
     */
    private $api;

    /**
     * @var FruugoSync_Settings
     */
    private $settings;

    /**
     * Constructor
     */
    public function __construct() {
        $this->api = new FruugoSync_API();
        $this->settings = new FruugoSync_Settings();
        $this->init_hooks();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Product updates
        add_action('woocommerce_update_product', array($this, 'handle_product_update'), 10, 1);
        add_action('woocommerce_product_quick_edit_save', array($this, 'handle_product_update'), 10, 1);
        add_action('woocommerce_product_bulk_edit_save', array($this, 'handle_product_update'), 10, 1);

        // Product deletions
        add_action('before_delete_post', array($this, 'handle_product_deletion'), 10, 1);

        // Stock updates
        add_action('woocommerce_product_set_stock', array($this, 'handle_stock_update'), 10, 1);
        add_action('woocommerce_variation_set_stock', array($this, 'handle_stock_update'), 10, 1);

        // Admin actions
        add_action('wp_ajax_generate_fruugo_csv', array($this, 'ajax_generate_product_csv'));
        add_action('wp_ajax_push_product_to_fruugo', array($this, 'ajax_push_product'));
    }

    /**
     * Format WooCommerce product for Fruugo
     */
    public function format_product_for_fruugo($product) {
        $product_data = array(
            'productId' => $product->get_sku() ?: $product->get_id(),
            'title' => $product->get_name(),
            'description' => strip_tags($product->get_description()),
            'brand' => $product->get_meta('_fruugo_brand') ?: get_bloginfo('name'),
            'category' => $this->get_fruugo_category($product),
            'price' => $product->get_regular_price(),
            'stockQuantity' => $product->get_stock_quantity(),
            'ean' => $product->get_meta('_fruugo_ean'),
            'imageUrl' => wp_get_attachment_url($product->get_image_id()),
            'attributes' => $this->get_product_attributes($product)
        );

        return apply_filters('fruugosync_format_product', $product_data, $product);
    }

    /**
     * Get Fruugo category for product
     */
    private function get_fruugo_category($product) {
        $wc_categories = get_the_terms($product->get_id(), 'product_cat');
        if (!$wc_categories || is_wp_error($wc_categories)) {
            return '';
        }

        $mappings = $this->settings->get_category_mappings();
        foreach ($wc_categories as $category) {
            if (isset($mappings[$category->term_id])) {
                return $mappings[$category->term_id];
            }
        }

        return '';
    }

    /**
     * Get product attributes
     */
    private function get_product_attributes($product) {
        $attributes = array();
        
        // Get product attributes
        $product_attributes = $product->get_attributes();
        foreach ($product_attributes as $attribute) {
            if ($attribute->is_taxonomy()) {
                $attribute_name = wc_attribute_label($attribute->get_name());
                $attribute_value = implode(', ', $attribute->get_terms());
            } else {
                $attribute_name = $attribute->get_name();
                $attribute_value = $attribute->get_options();
                $attribute_value = is_array($attribute_value) ? implode(', ', $attribute_value) : $attribute_value;
            }
            $attributes[] = array(
                'name' => $attribute_name,
                'value' => $attribute_value
            );
        }

        return $attributes;
    }

    /**
     * Generate CSV file for Fruugo
     */
    public function generate_product_csv() {
        $upload_dir = wp_upload_dir();
        $csv_file = $upload_dir['basedir'] . '/cedcommerce_fruugouploads/fruugo_products.csv';

        // Create directory if it doesn't exist
        wp_mkdir_p(dirname($csv_file));

        $fp = fopen($csv_file, 'w');
        if (!$fp) {
            throw new Exception('Unable to create CSV file');
        }

        // Write CSV headers
        $headers = array(
            'Product ID',
            'Title',
            'Description',
            'Brand',
            'Category',
            'Price',
            'Stock Quantity',
            'EAN',
            'Image URL'
        );
        fputcsv($fp, $headers);

        // Get all published products
        $args = array(
            'status' => 'publish',
            'limit' => -1
        );
        $products = wc_get_products($args);

        foreach ($products as $product) {
            $product_data = $this->format_product_for_fruugo($product);
            fputcsv($fp, array(
                $product_data['productId'],
                $product_data['title'],
                $product_data['description'],
                $product_data['brand'],
                $product_data['category'],
                $product_data['price'],
                $product_data['stockQuantity'],
                $product_data['ean'],
                $product_data['imageUrl']
            ));
        }

        fclose($fp);
        return $csv_file;
    }

    /**
     * Handle product update
     */
    public function handle_product_update($product_id) {
        $product = wc_get_product($product_id);
        if (!$product) return;

        try {
            $product_data = $this->format_product_for_fruugo($product);
            $result = $this->api->update_product($product_data);

            if (!$result['success']) {
                error_log('FruugoSync: Failed to update product ' . $product_id . ': ' . $result['message']);
            }
        } catch (Exception $e) {
            error_log('FruugoSync: Error updating product ' . $product_id . ': ' . $e->getMessage());
        }
    }

    /**
     * Handle product deletion
     */
    public function handle_product_deletion($post_id) {
        if (get_post_type($post_id) !== 'product') return;

        try {
            $product = wc_get_product($post_id);
            if (!$product) return;

            // Set stock to 0 and status to not available
            $product_data = array(
                'productId' => $product->get_sku() ?: $product->get_id(),
                'stockQuantity' => 0,
                'status' => 'NOTAVAILABLE'
            );

            $result = $this->api->update_product($product_data);
            if (!$result['success']) {
                error_log('FruugoSync: Failed to mark product as unavailable ' . $post_id . ': ' . $result['message']);
            }
        } catch (Exception $e) {
            error_log('FruugoSync: Error handling product deletion ' . $post_id . ': ' . $e->getMessage());
        }
    }

    /**
     * Handle stock update
     */
    public function handle_stock_update($product) {
        try {
            $product_data = array(
                'productId' => $product->get_sku() ?: $product->get_id(),
                'stockQuantity' => $product->get_stock_quantity()
            );

            $result = $this->api->update_product($product_data);
            if (!$result['success']) {
                error_log('FruugoSync: Failed to update stock for product ' . $product->get_id() . ': ' . $result['message']);
            }
        } catch (Exception $e) {
            error_log('FruugoSync: Error updating stock for product ' . $product->get_id() . ': ' . $e->getMessage());
        }
    }

    /**
     * AJAX handler for generating product CSV
     */
    public function ajax_generate_product_csv() {
        check_ajax_referer('fruugosync-ajax-nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error('Unauthorized');
        }

        try {
            $csv_file = $this->generate_product_csv();
            wp_send_json_success(array(
                'message' => 'CSV file generated successfully',
                'file' => basename($csv_file)
            ));
        } catch (Exception $e) {
            wp_send_json_error('Failed to generate CSV: ' . $e->getMessage());
        }
    }

    /**
     * AJAX handler for pushing a single product
     */
    public function ajax_push_product() {
        check_ajax_referer('fruugosync-ajax-nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error('Unauthorized');
        }

        $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
        if (!$product_id) {
            wp_send_json_error('Invalid product ID');
        }

        try {
            $product = wc_get_product($product_id);
            if (!$product) {
                wp_send_json_error('Product not found');
            }

            $product_data = $this->format_product_for_fruugo($product);
            $result = $this->api->push_product($product_data);

            if ($result['success']) {
                wp_send_json_success('Product pushed to Fruugo successfully');
            } else {
                wp_send_json_error('Failed to push product: ' . $result['message']);
            }
        } catch (Exception $e) {
            wp_send_json_error('Error pushing product: ' . $e->getMessage());
        }
    }
}