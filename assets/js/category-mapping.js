jQuery(document).ready(function ($) {
    // Save category mapping
    $('.save-mapping').on('click', function () {
        var wcCategory = $(this).data('wc-category');
        var fruugoCategory = $(this).closest('tr').find('.fruugo-category-dropdown').val();

        if (!fruugoCategory) {
            alert('Please select a Fruugo category.');
            return;
        }

        $.ajax({
            url: fruugosync_ajax.ajax_url,
            method: 'POST',
            data: {
                action: 'save_category_mapping',
                wc_category: wcCategory,
                fruugo_category: fruugoCategory,
                nonce: fruugosync_ajax.nonce
            },
            success: function (response) {
                if (response.success) {
                    alert('Mapping saved successfully.');
                } else {
                    alert('Failed to save mapping: ' + response.data.message);
                }
            },
            error: function () {
                alert('An error occurred while saving the mapping.');
            }
        });
    });

    // Refresh Fruugo categories
    $('#refresh-categories').on('click', function () {
        $.ajax({
            url: fruugosync_ajax.ajax_url,
            method: 'POST',
            data: {
                action: 'refresh_fruugo_categories',
                nonce: fruugosync_ajax.nonce
            },
            success: function (response) {
                if (response.success) {
                    alert('Fruugo categories refreshed successfully.');
                    // Reload or update the dropdown dynamically
                } else {
                    alert('Failed to refresh categories: ' + response.data.message);
                }
            },
            error: function () {
                alert('An error occurred while refreshing categories.');
            }
        });
    });
});
