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

        <?php if (!empty($errors)): ?>
            <div class="notice notice-error">
                <p><?php echo esc_html($errors); ?></p>
            </div>
        <?php endif; ?>

        <div class="category-tree-container">
            <ul class="ced_fruugo_cat_ul ced_fruugo_1lvl">
                <h1>Root Categories</h1>
            </ul>
            <ul class="ced_fruugo_cat_ul ced_fruugo_2lvl"></ul>
            <ul class="ced_fruugo_cat_ul ced_fruugo_3lvl"></ul>
            <ul class="ced_fruugo_cat_ul ced_fruugo_4lvl"></ul>
            <ul class="ced_fruugo_cat_ul ced_fruugo_5lvl"></ul>
        </div>

        <div class="selected-categories-wrapper">
            <h3><?php _e('Selected Categories', 'fruugosync'); ?></h3>
            <div class="selected-categories-list"></div>
        </div>
    </div>
</div>