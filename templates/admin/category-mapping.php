<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <div class="info-box">
        <p><?php _e('Map your WooCommerce categories to Fruugo categories.', 'fruugosync'); ?></p>
    </div>

    <button type="button" id="refresh-categories" class="button">
        <?php _e('Refresh Fruugo Categories', 'fruugosync'); ?>
    </button>

    <h2><?php _e('Root Categories', 'fruugosync'); ?></h2>
    
    <div class="category-tree-container">
        <ul class="ced_fruugo_cat_ul ced_fruugo_1lvl"></ul>
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