jQuery(document).ready(function($) {
    // Refresh categories handler
    $('#refresh-categories').on('click', function(e) {
        e.preventDefault();
        var button = $(this);
        
        // Disable button and show loading state
        button.prop('disabled', true);
        
        $.ajax({
            url: fruugosync_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'refresh_fruugo_categories',
                nonce: fruugosync_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    renderRootCategories(response.data);
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
    $(document).on('click', '.ced_fruugo_expand_fruugocat', function() {
        var $this = $(this);
        var level = parseInt($this.data('cat-level'));
        var parentCat = $this.data('parent-cat-name');
        
        $this.addClass('loading');
        
        // Clear any existing subcategories at deeper levels
        clearSubcategories(level);
        
        $.ajax({
            url: fruugosync_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'get_fruugo_subcategories',
                nonce: fruugosync_ajax.nonce,
                parent: parentCat,
                level: level + 1
            },
            success: function(response) {
                if (response.success) {
                    renderSubcategories(response.data, level + 1);
                }
            },
            complete: function() {
                $this.removeClass('loading');
            }
        });
    });

    function renderRootCategories(categories) {
        var $container = $('.ced_fruugo_1lvl');
        $container.empty();

        if (!categories || !categories.length) {
            showError('No categories available');
            return;
        }

        categories.forEach(function(category) {
            var $li = $('<li>');
            var $label = $('<label>', {
                class: 'ced_fruugo_expand_fruugocat',
                'data-parent-cat-name': category,
                'data-cat-level': '1',
                text: category + ' '
            });
            
            $label.append(
                $('<img>', {
                    class: 'ced_fruugo_category_loader',
                    src: fruugosync_ajax.plugin_url + '/assets/images/loading.gif',
                    width: 20,
                    height: 20
                })
            );
            
            $li.append($label);
            $container.append($li);
        });

        // Clear other levels
        clearSubcategories(1);
    }

    function renderSubcategories(categories, level) {
        var $container = $('.ced_fruugo_' + level + 'lvl');
        $container.empty();

        categories.forEach(function(category) {
            var $li = $('<li>');
            var $label = $('<label>', {
                class: 'ced_fruugo_expand_fruugocat',
                'data-parent-cat-name': category,
                'data-cat-level': level,
                text: category + ' '
            });
            
            $label.append(
                $('<img>', {
                    class: 'ced_fruugo_category_loader',
                    src: fruugosync_ajax.plugin_url + '/assets/images/loading.gif',
                    width: 20,
                    height: 20
                })
            );
            
            $li.append($label);
            $container.append($li);
        });
    }

    function clearSubcategories(fromLevel) {
        for (var i = fromLevel + 1; i <= 5; i++) {
            $('.ced_fruugo_' + i + 'lvl').empty();
        }
    }

    function showError(message) {
        var $error = $('<div>', {
            class: 'notice notice-error',
            html: $('<p>', { text: message })
        });

        $('.wrap').find('.notice-error').remove();
        $('.wrap').prepend($error);
    }
});