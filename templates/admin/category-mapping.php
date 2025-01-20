<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <div class="info-box">
        <p class="primary-text"><?php _e('Map your WooCommerce categories to Fruugo categories.', 'fruugosync'); ?></p>
    </div>

    <button type="button" id="refresh-categories" class="button">
        <?php _e('Refresh Fruugo Categories', 'fruugosync'); ?>
    </button>

    <div class="categories-section">
        <h2><?php _e('Root Categories', 'fruugosync'); ?></h2>
        <div class="category-tree-container">
            <ul class="ced_fruugo_cat_ul ced_fruugo_1lvl"></ul>
            <ul class="ced_fruugo_cat_ul ced_fruugo_2lvl"></ul>
            <ul class="ced_fruugo_cat_ul ced_fruugo_3lvl"></ul>
            <ul class="ced_fruugo_cat_ul ced_fruugo_4lvl"></ul>
            <ul class="ced_fruugo_cat_ul ced_fruugo_5lvl"></ul>
        </div>
    </div>

    <div class="selected-categories-section">
        <h3><?php _e('Selected Categories', 'fruugosync'); ?></h3>
        <div class="selected-categories-list"></div>
    </div>
</div>

<style>
.info-box {
    border-left: 4px solid #00a0d2;
    padding: 12px;
    margin: 20px 0;
    background: #fff;
}

.primary-text {
    margin: 0;
}

.categories-section {
    margin-top: 20px;
}

.selected-categories-section {
    margin-top: 30px;
    background: #fff;
    padding: 20px;
    border: 1px solid #ddd;
}

.category-tree-container {
    margin-top: 15px;
}

.ced_fruugo_cat_ul {
    margin: 0;
    padding: 0 0 0 20px;
    list-style: none;
}

.ced_fruugo_cat_ul li {
    margin: 5px 0;
    padding: 5px;
}

.ced_fruugo_expand_fruugocat {
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 10px;
}

.ced_fruugo_category_loader {
    display: none;
}

.loading .ced_fruugo_category_loader {
    display: inline;
}
</style>