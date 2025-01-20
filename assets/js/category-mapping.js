jQuery(document).ready(function($) {
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
                    var $container = $('.ced_fruugo_cat_ul');
                    $container.empty();
                    
                    response.data.forEach(function(category) {
                        $container.append(
                            '<li><a href="#" class="category-link">' + 
                            category + 
                            '</a></li>'
                        );
                    });
                } else {
                    $('.wrap').prepend(
                        '<div class="notice notice-error">' +
                        '<p>' + (response.data.message || 'Failed to load categories') + '</p>' +
                        '</div>'
                    );
                }
            },
            error: function() {
                $('.wrap').prepend(
                    '<div class="notice notice-error">' +
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