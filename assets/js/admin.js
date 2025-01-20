jQuery(document).ready(function($) {
    // API Test Connection
    if ($('#test-connection').length) {
        $('#test-connection').on('click', function() {
            var button = $(this);
            button.prop('disabled', true);
            
            $.ajax({
                url: fruugosync_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'test_fruugo_connection',
                    nonce: fruugosync_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $('#api-status-indicator')
                            .removeClass('unknown disconnected')
                            .addClass('connected')
                            .find('.status-text')
                            .text('Connected');
                    } else {
                        $('#api-status-indicator')
                            .removeClass('unknown connected')
                            .addClass('disconnected')
                            .find('.status-text')
                            .text('Not Connected');
                    }
                },
                error: function() {
                    $('#api-status-indicator')
                        .removeClass('unknown connected')
                        .addClass('disconnected')
                        .find('.status-text')
                        .text('Connection Failed');
                },
                complete: function() {
                    button.prop('disabled', false);
                }
            });
        });
    }

    // Category Management
    if ($('#refresh-categories').length) {
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
                        renderCategories(response.data);
                    } else {
                        showError(response.data ? response.data.message : 'Failed to load categories');
                    }
                },
                error: function() {
                    showError('Failed to refresh categories');
                },
                complete: function() {
                    button.prop('disabled', false);
                }
            });
        });

        // Category expansion handler
        $(document).on('click', '.ced_fruugo_expand_fruugocat', function() {
            var $this = $(this);
            var level = parseInt($this.attr('data-cat-level'));
            var parentCat = $this.attr('data-parent-cat-name');

            // Clear next level
            $('.ced_fruugo_' + (level + 1) + 'lvl').empty();
            
            $this.addClass('loading');

            $.ajax({
                url: fruugosync_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'get_fruugo_subcategories',
                    nonce: fruugosync_ajax.nonce,
                    parent: parentCat,
                    level: level
                },
                success: function(response) {
                    if (response.success && response.data) {
                        renderSubcategories(response.data, level);
                    }
                },
                complete: function() {
                    $this.removeClass('loading');
                }
            });
        });
    }

    function renderCategories(categories) {
        var $container = $('.ced_fruugo_1lvl');
        $container.empty();

        if (!categories || !categories.length) {
            showError('No categories available');
            return;
        }

        categories.forEach(function(category) {
            $container.append(
                '<li>' +
                '<label class="ced_fruugo_expand_fruugocat" ' +
                'data-parent-cat-name="' + category + '" ' +
                'data-cat-level="1">' +
                category +
                '<span class="dashicons dashicons-arrow-right-alt2"></span>' +
                '</label>' +
                '</li>'
            );
        });
    }

    function renderSubcategories(categories, level) {
        var $container = $('.ced_fruugo_' + (level + 1) + 'lvl');
        $container.empty();

        categories.forEach(function(category) {
            $container.append(
                '<li>' +
                '<label class="ced_fruugo_expand_fruugocat" ' +
                'data-parent-cat-name="' + category + '" ' +
                'data-cat-level="' + (level + 1) + '">' +
                category +
                '<span class="dashicons dashicons-arrow-right-alt2"></span>' +
                '</label>' +
                '</li>'
            );
        });
    }

    function showError(message) {
        var $error = $('<div class="notice notice-error"><p>' + message + '</p></div>');
        $('.wrap').find('.notice-error').remove();
        $('.wrap').prepend($error);
    }
});