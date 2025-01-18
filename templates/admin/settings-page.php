<?php
if (!defined('ABSPATH')) {
    exit;
}

// Ensure api_status is an array
$api_status = is_array($api_status) ? $api_status : array(
    'status' => 'unknown',
    'error' => ''
);
?>
<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <?php if (!class_exists('WooCommerce')): ?>
        <div class="notice notice-error">
            <p><?php _e('FruugoSync requires WooCommerce to be installed and activated.', 'fruugosync'); ?></p>
        </div>
    <?php endif; ?>

    <div class="notice notice-info">
        <p><?php _e('Configure your Fruugo API credentials and settings below.', 'fruugosync'); ?></p>
    </div>

    <form method="post" action="options.php" id="fruugosync-settings-form">
        <?php settings_fields('fruugosync_settings_group'); ?>
        
        <div class="card">
            <h2><?php _e('API Credentials', 'fruugosync'); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="fruugosync_username"><?php _e('Username', 'fruugosync'); ?></label>
                    </th>
                    <td>
                        <input type="text" 
                               id="fruugosync_username" 
                               name="fruugosync_username" 
                               value="<?php echo esc_attr(get_option('fruugosync_username')); ?>" 
                               class="regular-text">
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="fruugosync_password"><?php _e('Password', 'fruugosync'); ?></label>
                    </th>
                    <td>
                        <input type="password" 
                               id="fruugosync_password" 
                               name="fruugosync_password" 
                               value="<?php echo esc_attr(get_option('fruugosync_password')); ?>" 
                               class="regular-text">
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </div>

        <div class="card">
            <h2><?php _e('API Connection Status', 'fruugosync'); ?></h2>
            <div class="api-status-wrapper">
                <div id="api-status-indicator" class="api-status <?php echo esc_attr($api_status['status']); ?>">
                    <span class="status-icon"></span>
                    <span class="status-text">
                        <?php 
                        if ($api_status['status'] === 'connected') {
                            _e('Connected', 'fruugosync');
                        } else {
                            _e('Not Connected', 'fruugosync');
                        }
                        ?>
                    </span>
                    <?php if (!empty($api_status['error'])): ?>
                        <p class="error-message"><?php echo esc_html($api_status['error']); ?></p>
                    <?php endif; ?>
                </div>
                <button type="button" id="test-connection" class="button button-secondary">
                    <?php _e('Test Connection', 'fruugosync'); ?>
                </button>
            </div>
        </div>

        <div class="card">
            <h2><?php _e('Debug Settings', 'fruugosync'); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="fruugosync_debug_mode">
                            <?php _e('Debug Mode', 'fruugosync'); ?>
                        </label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" 
                                   id="fruugosync_debug_mode" 
                                   name="fruugosync_debug_mode" 
                                   value="1" 
                                   <?php checked(get_option('fruugosync_debug_mode')); ?>>
                            <?php _e('Enable debug logging', 'fruugosync'); ?>
                        </label>
                        <p class="description">
                            <?php _e('When enabled, debug information will be logged to wp-content/debug.log', 'fruugosync'); ?>
                        </p>
                    </td>
                </tr>
            </table>
        </div>
    </form>
</div>