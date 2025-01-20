jQuery(document).ready(function($) {
    // Save category mapping
    $('.fruugo-category-dropdown').on('change', function() {
        var wcCategory = $(this).closest('tr').find('td:first').text();
        var fruugoCategory = $(this).val();

        if (fruugoCategory) {
            $.ajax({
                url: fruugosync_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'save_category_mapping',
                    wc_category: wcCategory,
                    fruugo_category: fruugoCategory,
                    nonce: fruugosync_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        alert('Category mapping saved successfully!');
                    } else {
                        alert('Failed to save category mapping.');
                    }
                },
                error: function() {
                    alert('Error occurred while saving mapping.');
                }
            });
        }
    });
});
