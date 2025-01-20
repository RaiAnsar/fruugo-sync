<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handles all API interactions with Fruugo
 * Supports both v3 JSON API and legacy XML endpoints
 */
class FruugoSync_API {
    /**
     * API URLs
     */
    private $api_urls = array(
        'v3_base' => 'https://marketplace.fruugo.com/v3/',
        'v3_order' => 'https://order-api.fruugo.com/v3/',
        'legacy' => 'https://www.fruugo.com/',
        'inventory' => 'https://www.fruugo.com/stockstatus-api'
    );

    /**
     * @var array Stored credentials
     */
    private $credentials;

    /**
     * Constructor
     */
    public function __construct() {
        $this->init_credentials();
    }

    /**
     * Initialize API credentials
     */
    private function init_credentials() {
        $saved_details = get_option('ced_fruugo_details', array());
        $this->credentials = array(
            'username' => isset($saved_details['userString']) ? $saved_details['userString'] : '',
            'password' => isset($saved_details['passString']) ? $saved_details['passString'] : ''
        );
    }

    /**
     * Test API connection
     */
    public function test_connection() {
        if (empty($this->credentials['username']) || empty($this->credentials['password'])) {
            return array(
                'success' => false,
                'message' => __('API credentials not configured', 'fruugosync')
            );
        }

        // Test v3 API
        $test_result = $this->make_request('orders', 'POST', array(
            'dateFrom' => gmdate('Y-m-d\TH:i:s\Z', strtotime('-1 day'))
        ), array(
            'api_version' => 'v3',
            'timeout' => 15
        ));

        return $test_result;
    }



    /**
 * Clear categories cache
 */
public function clear_cache() {
    delete_transient('fruugosync_categories');
    return true;
}

    /**
     * Get Fruugo categories and cache them
     */
/**
 * Get Fruugo categories and cache them
 */
/**
 * Get Fruugo categories from local JSON
 */
/**
 * Get Fruugo categories and store them in JSON
 */
/**
 * Get Fruugo categories
 */
/**
 * Get Fruugo categories
 */
public function get_categories($force_refresh = false) {
error_log("Debug path info:");
error_log("FRUUGOSYNC_PATH: " . FRUUGOSYNC_PATH);
error_log("__FILE__: " . __FILE__);
error_log("dirname(__FILE__): " . dirname(__FILE__));
error_log("Current working directory: " . getcwd());
    // Get absolute path to the file
    $file_path = realpath(FRUUGOSYNC_PATH . 'data/json/category.json');
    error_log("Absolute path to category file: " . $file_path);

    // Verify the file exists and is readable
    if (!$file_path || !is_readable($file_path)) {
        error_log("File not found or not readable at: " . FRUUGOSYNC_PATH . 'data/json/category.json');
        return array(
            'success' => false,
            'message' => 'Category file not found or not readable'
        );
    }

    // Read the file contents
    $content = file_get_contents($file_path);
    if ($content === false) {
        error_log("Failed to read category file");
        return array(
            'success' => false,
            'message' => 'Failed to read category file'
        );
    }

    // Parse JSON
    $categories = json_decode($content, true);
    if (!$categories) {
        error_log("Failed to parse JSON from category file");
        return array(
            'success' => false,
            'message' => 'Invalid category data'
        );
    }

    // Extract level1 categories
    $level1_cats = array();
    foreach ($categories as $cat) {
        if (!empty($cat['level1'])) {
            $level1_cats[] = trim($cat['level1']);
        }
    }
    $level1_cats = array_unique($level1_cats);

    return array(
        'success' => true,
        'data' => array_values($level1_cats)
    );
}

/**
 * Get subcategories for a specific level and parent
 */
public function get_subcategories($parent_category, $level) {
    try {
        $json_file = FRUUGOSYNC_PATH . 'data/json/category.json';
        
        if (!file_exists($json_file)) {
            throw new Exception('Category file not found');
        }

        $categories = json_decode(file_get_contents($json_file), true);
        if (!$categories) {
            throw new Exception('Invalid category data');
        }

        $subcategories = array();
        $parent_level = 'level' . ($level - 1);
        $current_level = 'level' . $level;

        foreach ($categories as $category) {
            if (trim($category[$parent_level]) === trim($parent_category) && !empty($category[$current_level])) {
                $subcategories[] = trim($category[$current_level]);
            }
        }

        return array(
            'success' => true,
            'data' => array_unique($subcategories)
        );

    } catch (Exception $e) {
        error_log('FruugoSync Subcategory Error: ' . $e->getMessage());
        return array(
            'success' => false,
            'message' => 'Error loading subcategories: ' . $e->getMessage()
        );
    }
}

/**
 * Format categories into the expected structure
 */
private function format_categories_for_storage($categories) {
    $formatted = array();
    foreach ($categories as $category) {
        // Format to match their structure with levels
        $formatted[] = array(
            'level1' => $category['name'],
            'level2' => isset($category['children']) ? array_column($category['children'], 'name') : array(),
            // Add more levels as needed
        );
    }
    return $formatted;
}

/**
 * Update local categories file
 */
private function update_local_categories($categories) {
    $json_file = FRUUGOSYNC_PATH . 'data/categories.json';
    $json_dir = dirname($json_file);
    
    if (!file_exists($json_dir)) {
        wp_mkdir_p($json_dir);
    }
    
    return file_put_contents($json_file, wp_json_encode($categories, JSON_PRETTY_PRINT));
}

    /**
     * Save categories to JSON file
     */
    private function save_categories_to_file($categories) {
        $folder_path = WP_CONTENT_DIR . '/uploads/fruugosync/categories/';
        if (!file_exists($folder_path)) {
            wp_mkdir_p($folder_path);
        }

        $file_path = $folder_path . 'category.json';
        file_put_contents($file_path, wp_json_encode($categories));
    }

    /**
     * Get legacy categories using XML format
     */
    private function get_legacy_categories() {
        // Implementation of legacy category fetching
        $response = $this->make_xml_request('categories', 'GET');
        if ($response['success'] && !empty($response['data'])) {
            $xml_array = XML2Array::createArray($response['data']);
            return array(
                'success' => true,
                'data' => $this->format_legacy_categories($xml_array)
            );
        }
        return $response;
    }

    /**
     * Update inventory
     */
    public function update_inventory($product_data) {
        // Try v3 API first
        $v3_result = $this->make_request('products/inventory', 'POST', array(
            'products' => array($product_data)
        ), array('api_version' => 'v3'));

        if ($v3_result['success']) {
            return $v3_result;
        }

        // Fallback to legacy inventory API
        return $this->update_legacy_inventory($product_data);
    }

    /**
     * Update inventory using legacy API
     */
    private function update_legacy_inventory($product_data) {
        $xml = $this->convert_inventory_to_xml($product_data);
        return $this->make_xml_request('stockstatus-api', 'POST', $xml);
    }

    /**
     * Make API request
     */
    private function make_request($endpoint, $method = 'GET', $body = null, $args = array()) {
        $api_version = isset($args['api_version']) ? $args['api_version'] : 'v3';
        $base_url = ($api_version === 'v3') ? $this->api_urls['v3_base'] : $this->api_urls['legacy'];
        
        if ($endpoint === 'orders' && $api_version === 'v3') {
            $base_url = $this->api_urls['v3_order'];
        }

        $url = trailingslashit($base_url) . $endpoint;
        
        $request_args = array(
            'method' => $method,
            'timeout' => isset($args['timeout']) ? $args['timeout'] : 30,
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode($this->credentials['username'] . ':' . $this->credentials['password']),
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'X-Correlation-ID' => 'fruugosync_' . uniqid()
            ),
            'sslverify' => false // Consider enabling in production
        );

        if ($body && in_array($method, array('POST', 'PUT'))) {
            $request_args['body'] = wp_json_encode($body);
        }

        // Log request if debug mode
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('FruugoSync API Request: ' . $url);
            error_log('Request Args: ' . print_r($request_args, true));
        }

        $response = wp_remote_request($url, $request_args);

        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => $response->get_error_message()
            );
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);

        if ($response_code === 429) {
            return array(
                'success' => false,
                'message' => 'Rate limit exceeded',
                'retry_after' => wp_remote_retrieve_header($response, 'Retry-After')
            );
        }

        if ($response_code >= 200 && $response_code < 300) {
            return array(
                'success' => true,
                'data' => json_decode($response_body, true)
            );
        }

        return array(
            'success' => false,
            'message' => "HTTP Error $response_code: " . wp_remote_retrieve_response_message($response)
        );
    }

    /**
     * Make XML API request
     */
    private function make_xml_request($endpoint, $method = 'GET', $body = null) {
        $url = $this->api_urls['legacy'] . $endpoint;
        
        $args = array(
            'method' => $method,
            'timeout' => 90,
            'headers' => array(
                'Content-Type' => 'application/xml',
                'Authorization' => 'Basic ' . base64_encode($this->credentials['username'] . ':' . $this->credentials['password'])
            ),
            'body' => $body,
            'sslverify' => false
        );

        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => $response->get_error_message()
            );
        }

        return array(
            'success' => true,
            'data' => wp_remote_retrieve_body($response)
        );
    }

    /**
     * Convert inventory data to XML
     */
    private function convert_inventory_to_xml($data) {
        // Implement XML conversion logic here
        // Use the Array2XML class if needed
        return '';
    }

    /**
     * Format legacy categories into new format
     */
    private function format_legacy_categories($xml_array) {
        // Implement category format conversion
        return array();
    }
}