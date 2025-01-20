<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <div class="info-box">
        <p><?php _e('Map your WooCommerce categories to Fruugo categories.', 'fruugosync'); ?></p>
    </div>

    <button type="button" id="refresh-categories" class="button">
        <?php _e('Refresh Fruugo Categories', 'fruugosync'); ?>
    </button>

    <h2><?php _e('Map WooCommerce Categories to Fruugo Categories', 'fruugosync'); ?></h2>
    
    <table class="category-mapping-table">
        <thead>
            <tr>
                <th><?php _e('WooCommerce Category', 'fruugosync'); ?></th>
                <th><?php _e('Fruugo Category', 'fruugosync'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($woocommerce_categories as $wc_category): ?>
                <tr>
                    <td><?php echo esc_html($wc_category); ?></td>
                    <td>
                        <select class="fruugo-category-dropdown">
                            <option value=""><?php _e('Select Fruugo Category', 'fruugosync'); ?></option>
                            <?php foreach ($fruugo_categories as $fruugo_category): ?>
                                <option value="<?php echo esc_attr($fruugo_category['id']); ?>">
                                    <?php echo esc_html($fruugo_category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
