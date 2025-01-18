<?php
class FruugoSync {
    private $api_base_url = 'https://api.fruugo.com/v3/';
    private $upload_dir;

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('wp_ajax_test_fruugo_connection', array($this, 'ajax_test_connection'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // Set upload directory
        $wp_upload_dir = wp_upload_dir();
        $this->upload_dir = $wp_upload_dir['basedir'] . '/cedcommerce_fruugouploads/';
    }

    public function enqueue_admin_scripts($hook) {
        // Only load on our plugin pages
        if (strpos($hook, 'fruugosync') === false) {
            return;
        }

        wp_enqueue_script(
            'fruugosync-admin', 
            plugins_url('/assets/js/admin.js', dirname(__FILE__)), 
            array('jquery'), 
            '1.0.0', 
            true
        );

        wp_localize_script('fruugosync-admin', 'fruugosync_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('fruugosync-ajax-nonce')
        ));
    }

    private function make_api_request($endpoint, $method = 'GET', $body = null) {
        $username = get_option('fruugosync_username');
        $password = get_option('fruugosync_password');
        
        if (empty($username) || empty($password)) {
            return [
                'success' => false,
                'message' => 'API credentials not configured'
            ];
        }

        $args = array(
            'method' => $method,
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode($username . ':' . $password),
                'Content-Type' => 'application/json',
                'X-Correlation-ID' => uniqid('fruugosync_'),
            ),
            'timeout' => 60, // Increased timeout
            'sslverify' => true
        );

        if ($body) {
            $args['body'] = json_encode($body);
        }

        $response = wp_remote_request($this->api_base_url . $endpoint, $args);

        if (is_wp_error($response)) {
            error_log('Fruugo API Error: ' . $response->get_error_message());
            return [
                'success' => false,
                'message' => 'API Error: ' . $response->get_error_message()
            ];
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);

        // Accept 200 and 202 as success codes
        if ($response_code === 200 || $response_code === 202) {
            return [
                'success' => true,
                'data' => json_decode($body, true)
            ];
        }

        return [
            'success' => false,
            'message' => "API Error (HTTP $response_code): " . wp_remote_retrieve_response_message($response)
        ];
    }

    public function ajax_test_connection() {
        check_ajax_referer('fruugosync-ajax-nonce', 'nonce');

        $result = $this->test_api_connection();
        wp_send_json($result);
    }

    private function test_api_connection() {
        // Test using orders endpoint as it should be available
        $result = $this->make_api_request('orders', 'POST', ['dateFrom' => date('Y-m-d\TH:i:s\Z')]);
        
        if ($result['success']) {
            update_option('fruugosync_api_status', [
                'status' => 'connected',
                'last_check' => time()
            ]);
        } else {
            update_option('fruugosync_api_status', [
                'status' => 'disconnected',
                'last_check' => time(),
                'error' => $result['message']
            ]);
        }

        return $result;
    }

    public function display_settings_page() {
        $api_status = get_option('fruugosync_api_status', ['status' => 'unknown']);
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
                <?php submit_button('Save Changes'); ?>
            </form>

            <!-- API Connection Status -->
            <div class="card">
                <h2>API Connection Status</h2>
                <div id="api-status-indicator" class="api-status <?php echo esc_attr($api_status['status']); ?>">
                    <span class="status-text">
                        <?php echo $api_status['status'] === 'connected' ? 'Connected' : 'Not Connected'; ?>
                    </span>
                    <?php if ($api_status['status'] === 'disconnected' && !empty($api_status['error'])): ?>
                        <p class="error-message"><?php echo esc_html($api_status['error']); ?></p>
                    <?php endif; ?>
                </div>
                <button type="button" id="test-connection" class="button">Test Connection</button>
            </div>

            <!-- Product Export Status -->
            <div class="card">
                <h2>Product Export Status</h2>
                <p>CSV Export Directory: <?php echo esc_html($this->upload_dir); ?></p>
                <button type="button" id="generate-csv" class="button button-primary">Generate Product CSV</button>
            </div>
        </div>

        <style>
        .card {
            background: #fff;
            border: 1px solid #ccd0d4;
            padding: 20px;
            margin-top: 20px;
            box-shadow: 0 1px 1px rgba(0,0,0,.04);
        }
        .api-status {
            padding: 10px;
            margin: 10px 0;
            border-radius: 4px;
        }
        .api-status.connected {
            background-color: #dff0d8;
            color: #3c763d;
        }
        .api-status.disconnected, .api-status.unknown {
            background-color: #f2dede;
            color: #a94442;
        }
        .error-message {
            margin-top: 5px;
            font-size: 13px;
        }
        </style>
        <?php
    }
}