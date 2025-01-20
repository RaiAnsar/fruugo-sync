jQuery(document).ready(function($) {
    // Test Connection Handler
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
                if (response.success && response.data.categories) {
                    renderCategories(response.data.categories);
                } else {
                    showError(response.data.message || 'Failed to load categories');
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
    $('.category-tree-container').on('click', '.ced_fruugo_expand_fruugocat', function() {
        var $this = $(this);
        var level = parseInt($this.data('cat-level'));
        var parentCat = $this.data('parent-cat-name');
        var catName = $this.data('cat-name');

        $.ajax({
            url: fruugosync_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'get_fruugo_subcategories',
                nonce: fruugosync_ajax.nonce,
                parent: parentCat,
                category: catName,
                level: level
            },
            success: function(response) {
                if (response.success) {
                    renderSubcategories(response.data, level);
                }
            }
        });
    });

    function renderCategories(categories) {
        var $container = $('.ced_fruugo_1lvl');
        $container.find('li').remove(); // Keep the h1 heading

        if (!categories || !categories.length) {
            showError('No categories available');
            return;
        }

        categories.forEach(function(category) {
            $container.append(
                '<li>' +
                '<label class="ced_fruugo_expand_fruugocat" ' +
                'data-parent-cat-name="' + category + '" ' +
                'data-cat-name="' + category + '" ' +
                'data-cat-level="1">' +
                category + '>' +
                '<img class="ced_fruugo_category_loader" src="' + fruugosync_ajax.plugin_url + 'assets/images/loading.gif" width="20px" height="20px">' +
                '</label>' +
                '</li>'
            );
        });
    }

    function renderSubcategories(categories, level) {
        var $container = $('.ced_fruugo_' + (level + 1) + 'lvl');
        $container.empty();

        if (categories && categories.length) {
            categories.forEach(function(category) {
                $container.append(
                    '<li>' +
                    '<label class="ced_fruugo_expand_fruugocat" ' +
                    'data-parent-cat-name="' + category + '" ' +
                    'data-cat-name="' + category + '" ' +
                    'data-cat-level="' + (level + 1) + '">' +
                    category + '>' +
                    '<img class="ced_fruugo_category_loader" src="' + fruugosync_ajax.plugin_url + 'assets/images/loading.gif" width="20px" height="20px">' +
                    '</label>' +
                    '</li>'
                );
            });
        }
    }

    function showError(message) {
        var $error = $('<div class="notice notice-error"><p>' + message + '</p></div>');
        $('.fruugosync-category-mapping').prepend($error);
        setTimeout(function() {
            $error.fadeOut(function() {
                $(this).remove();
            });
        }, 5000);
    }
});