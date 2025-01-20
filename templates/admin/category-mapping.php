<?php if (!defined('ABSPATH')) exit; ?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <div class="nav-tab-wrapper">
        <a href="#" class="nav-tab nav-tab-active">BULK PROFILE ASSIGNMENT</a>
        <a href="#" class="nav-tab">BULK PRODUCT UPLOAD</a>
        <a href="#" class="nav-tab">CSV EXPORT/IMPORT</a>
    </div>

    <div class="assign-profile-section">
        <h2 class="section-title">ASSIGN PROFILE TO CATEGORY</h2>

        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th class="column-category">Category</th>
                    <th class="column-slug">Slug</th>
                    <th class="column-select-profile">Select Profile</th>
                    <th class="column-selected-profile">Selected Profile</th>
                    <th class="column-progress">Progress</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if (!empty($woo_categories)) {
                    foreach ($woo_categories as $category) {
                        ?>
                        <tr>
                            <td><?php echo esc_html($category->name); ?></td>
                            <td><?php echo esc_html($category->slug); ?></td>
                            <td>
                                <select class="profile-select" data-category="<?php echo esc_attr($category->term_id); ?>">
                                    <option value="">--Select Profile--</option>
                                    <?php foreach ($profiles as $profile): ?>
                                        <option value="<?php echo esc_attr($profile['id']); ?>">
                                            <?php echo esc_html($profile['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td class="selected-profile">Profile Not selected</td>
                            <td>
                                <div class="progress-bar">
                                    <div class="progress-fill"></div>
                                </div>
                            </td>
                        </tr>
                        <?php
                    }
                }
                ?>
            </tbody>
        </table>
    </div>
</div>