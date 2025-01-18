<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <div class="notice notice-info">
        <p><?php _e('Map your WooCommerce categories to Fruugo categories.', 'fruugosync'); ?></p>
    </div>

    <div class="fruugosync-category-mapping">
        <div class="actions-bar">
            <button type="button" id="refresh-categories" class="button">
                <?php _e('Refresh Fruugo Categories', 'fruugosync'); ?>
            </button>
        </div>

        <?php if (!$fruugo_categories['success']): ?>
            <div class="notice notice-error">
                <p><?php echo esc_html($fruugo_categories['message']); ?></p>
            </div>
        <?php else: ?>
            <form id="category-mapping-form" method="post">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th class="column-woo-category">
                                <?php _e('WooCommerce Category', 'fruugosync'); ?>
                            </th>
                            <th class="column-fruugo-category">
                                <?php _e('Fruugo Category', 'fruugosync'); ?>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($woo_categories)): ?>
                            <tr>
                                <td colspan="2">
                                    <?php _e('No WooCommerce categories found.', 'fruugosync'); ?>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($woo_categories as $woo_category): ?>
                                <tr>
                                    <td class="column-woo-category">
                                        <?php echo esc_html($woo_category->name); ?>
                                    </td>
                                    <td class="column-fruugo-category">
                                        <select name="category_mapping[<?php echo esc_attr($woo_category->term_id); ?>]"
                                                class="fruugo-category-select">
                                            <option value="">
                                                <?php _e('Select Fruugo Category', 'fruugosync'); ?>
                                            </option>
                                            <?php 
                                            foreach ($fruugo_categories['data'] as $cat): 
                                                $selected = isset($current_mappings[$woo_category->term_id]) && 
                                                          $current_mappings[$woo_category->term_id] === $cat['id'];
                                            ?>
                                                <option value="<?php echo esc_attr($cat['id']); ?>" 
                                                        <?php selected($selected); ?>>
                                                    <?php echo esc_html($cat['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>

                <div class="actions-bar bottom">
                    <button type="submit" class="button button-primary">
                        <?php _e('Save Mappings', 'fruugosync'); ?>
                    </button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>