<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handles all API interactions with Fruugo
 */
class FruugoSync_API {
    /**
     * @var string API base URL
     */
    private $api_base_url = 'https://api.fruugo.com/v3/';

    /**
     * @var FruugoSync_Settings
     */
    private $settings;

    /**
     * Constructor
     */
    public function __construct() {
        $this->settings = new FruugoSync_Settings();
    }

    /**
     * Make an API request to Fruugo
     */
    public function make_request($endpoint, $method = 'GET', $body = null) {
        $credentials = $this->settings->get_api_credentials();
        
        if (empty($credentials['username']) || empty($credentials['password'])) {
            return array(
                'success' => false,
                'message' => __('API credentials are not configured.', 'fruugosync')
            );
        }

        $args = array(
            'method'    => $method,
            'headers'   => array(
                'Authorization' => 'Basic ' . base64_encode($credentials['username'] . ':' . $credentials['password']),
                'Content-Type' => 'application/json',
                'X-Correlation-ID' => uniqid('fruugosync_'),
            ),
            'timeout'   => 60,
            'sslverify' => true
        );

        if ($body) {
            $args['body'] = json_encode($body);
        }

        // Log request if debug mode is enabled
        if ($this->settings->is_debug_mode()) {
            error_log('FruugoSync API Request: ' . $endpoint);
            error_log('Request Args: ' . print_r($args, true));
        }

        $response = wp_remote_request($this->api_base_url . $endpoint, $args);

        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            error_log('FruugoSync API Error: ' . $error_message);
            return array(
                'success' => false,
                'message' => $error_message
            );
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);

        // Log response if debug mode is enabled
        if ($this->settings->is_debug_mode()) {
            error_log('API Response Code: ' . $response_code);
            error_log('API Response: ' . $response_body);
        }

        if ($response_code === 202) {
            return array(
                'success' => true,
                'data' => json_decode($response_body, true)
            );
        }

        if ($response_code !== 200) {
            $error_message = sprintf(
                __('API request failed with code %d: %s', 'fruugosync'),
                $response_code,
                wp_remote_retrieve_response_message($response)
            );
            return array(
                'success' => false,
                'message' => $error_message
            );
        }

        return array(
            'success' => true,
            'data' => json_decode($response_body, true)
        );
    }

    /**
     * Test API connection
     */
    public function test_connection() {
        $result = $this->make_request('orders', 'POST', array(
            'dateFrom' => date('Y-m-d\TH:i:s\Z', strtotime('-1 day'))
        ));

        if ($result['success']) {
            $this->settings->update_api_status('connected');
        } else {
            $this->settings->update_api_status('disconnected', $result['message']);
        }

        return $result;
    }

    /**
     * Get Fruugo categories
     */
    public function get_categories() {
        // First check transient
        $categories = get_transient('fruugosync_categories');
        if ($categories !== false) {
            return array(
                'success' => true,
                'data' => $categories
            );
        }

        $result = $this->make_request('categories');
        
        if ($result['success'] && !empty($result['data'])) {
            set_transient('fruugosync_categories', $result['data'], DAY_IN_SECONDS);
        }

        return $result;
    }

    /**
     * Push product to Fruugo
     */
    public function push_product($product_data) {
        return $this->make_request('products', 'POST', array(
            'products' => array($product_data)
        ));
    }

    /**
     * Update product on Fruugo
     */
    public function update_product($product_data) {
        return $this->make_request('products/partial', 'POST', array(
            'products' => array($product_data)
        ));
    }

    /**
     * Get product status from Fruugo
     */
    public function get_product_status($product_id) {
        return $this->make_request('products/status', 'POST', array(
            'skus' => array(
                array('productId' => $product_id)
            )
        ));
    }

    /**
     * Clear API cache
     */
    public function clear_cache() {
        delete_transient('fruugosync_categories');
        return true;
    }
}