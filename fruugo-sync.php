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
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
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
    }

    public function register_settings() {
        register_setting('fruugosync_settings', 'fruugosync_username');
        register_setting('fruugosync_settings', 'fruugosync_password');
        register_setting('fruugosync_settings', 'fruugosync_category');
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
                    <tr>
                        <th scope="row">
                            <label for="fruugosync_category">WooCommerce Category</label>
                        </th>
                        <td>
                            <?php
                            $selected_cat = get_option('fruugosync_category');
                            $categories = get_terms([
                                'taxonomy' => 'product_cat',
                                'hide_empty' => false,
                            ]);
                            
                            if (!empty($categories)) {
                                echo '<select name="fruugosync_category" id="fruugosync_category">';
                                echo '<option value="">Select a category</option>';
                                foreach ($categories as $category) {
                                    echo '<option value="' . esc_attr($category->term_id) . '" ' . 
                                         selected($selected_cat, $category->term_id, false) . '>' . 
                                         esc_html($category->name) . '</option>';
                                }
                                echo '</select>';
                            }
                            ?>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
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