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
        <button type="button" id="refresh-categories" class="button">
            <?php _e('Refresh Fruugo Categories', 'fruugosync'); ?>
        </button>

        <div class="loading-indicator" style="display: none;">
            <span class="spinner is-active"></span>
            <?php _e('Loading categories...', 'fruugosync'); ?>
        </div>

        <div id="category-error" class="notice notice-error" style="display: none;">
            <p></p>
        </div>

        <div class="category-tree-container">
            <ul class="ced_fruugo_cat_ul ced_fruugo_1lvl"></ul>
            <ul class="ced_fruugo_cat_ul ced_fruugo_2lvl"></ul>
            <ul class="ced_fruugo_cat_ul ced_fruugo_3lvl"></ul>
            <ul class="ced_fruugo_cat_ul ced_fruugo_4lvl"></ul>
            <ul class="ced_fruugo_cat_ul ced_fruugo_5lvl"></ul>
        </div>

        <div id="selected-categories" style="margin-top: 20px;">
            <h3><?php _e('Selected Categories', 'fruugosync'); ?></h3>
            <div class="selected-categories-list"></div>
        </div>
    </div>
</div>