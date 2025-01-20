jQuery(document).ready(function($) {
    // Category Refresh Handler
    $('#refresh-categories').on('click', function(e) {
        e.preventDefault();
        var button = $(this);
        button.prop('disabled', true);

        $.ajax({
            url: fruugosync_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'refresh_fruugo_categories',
                nonce: fruugosync_ajax.nonce
            },
            success: function(response) {
                if (response.success && response.data) {
                    // Clear existing categories
                    $('.ced_fruugo_cat_ul').empty();
                    
                    // Add root categories
                    var $rootContainer = $('.ced_fruugo_1lvl');
                    response.data.forEach(function(category) {
                        $rootContainer.append(
                            '<li>' +
                            '<label class="ced_fruugo_expand_fruugocat" ' +
                            'data-parent-cat-name="' + category + '" ' +
                            'data-cat-level="1">' +
                            category +
                            '</label>' +
                            '</li>'
                        );
                    });
                } else {
                    var message = response.data ? response.data.message : 'Failed to load categories';
                    $('.wrap').prepend(
                        '<div class="notice notice-error is-dismissible">' +
                        '<p>' + message + '</p>' +
                        '</div>'
                    );
                }
            },
            error: function() {
                $('.wrap').prepend(
                    '<div class="notice notice-error is-dismissible">' +
                    '<p>Failed to refresh categories</p>' +
                    '</div>'
                );
            },
            complete: function() {
                button.prop('disabled', false);
            }
        });
    });
});