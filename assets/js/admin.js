jQuery(document).ready(function($) {
    // Test connection functionality
    $('#test-connection').on('click', function() {
        var button = $(this);
        var originalText = button.text();
        
        button.prop('disabled', true).text('Testing...');
        $('.api-status-wrapper .spinner').addClass('is-active');

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
                    
                    $('.error-message').hide();
                } else {
                    $('#api-status-indicator')
                        .removeClass('unknown connected')
                        .addClass('disconnected')
                        .find('.status-text')
                        .text('Not Connected');
                    
                    $('.error-message').show().text(response.data.message);
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
                button.prop('disabled', false).text(originalText);
                $('.api-status-wrapper .spinner').removeClass('is-active');
            }
        });
    });

    // Category management
    var categoryManager = {
        init: function() {
            this.bindEvents();
            this.loadCategories();
        },

        bindEvents: function() {
            $('#refresh-categories').on('click', this.refreshCategories.bind(this));
            $('.category-tree-container').on('click', '.expand-category', this.loadSubcategories.bind(this));
            $('.category-tree-container').on('click', '.category-checkbox', this.handleCategorySelection.bind(this));
        },

        loadCategories: function() {
            $('.loading-indicator').show();
            $('#category-error').hide();

            $.ajax({
                url: fruugosync_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'refresh_fruugo_categories',
                    nonce: fruugosync_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        this.renderCategories(response.data.level1_categories);
                        this.loadSavedMappings();
                    } else {
                        $('#category-error').show().find('p').text(response.data.message);
                    }
                }.bind(this),
                error: function() {
                    $('#category-error').show().find('p').text('Failed to load categories');
                }.bind(this),
                complete: function() {
                    $('.loading-indicator').hide();
                }
            });
        },

        refreshCategories: function(e) {
            e.preventDefault();
            this.loadCategories();
        },

        loadSubcategories: function(e) {
            var $button = $(e.currentTarget);
            var $parent = $button.closest('li');
            var level = parseInt($parent.data('level')) + 1;
            var categoryName = $parent.data('category');

            if ($button.hasClass('loading')) return;

            $button.addClass('loading').find('.spinner').addClass('is-active');

            $.ajax({
                url: fruugosync_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'get_fruugo_subcategories',
                    nonce: fruugosync_ajax.nonce,
                    parent: categoryName,
                    level: level
                },
                success: function(response) {
                    if (response.success) {
                        this.renderSubcategories(response.data.categories, level, $parent);
                    }
                }.bind(this),
                complete: function() {
                    $button.removeClass('loading').find('.spinner').removeClass('is-active');
                }
            });
        },

        renderCategories: function(categories) {
            var $container = $('.ced_fruugo_1lvl').empty();
            
            if (!categories || !categories.length) {
                $('#category-error').show().find('p').text('No categories available');
                return;
            }

            categories.forEach(function(category) {
                var $item = $('<li>', {
                    'data-category': category,
                    'data-level': 1
                }).appendTo($container);

                $('<input>', {
                    type: 'checkbox',
                    class: 'category-checkbox',
                    'data-category': category
                }).appendTo($item);

                $('<span>', {
                    text: category,
                    class: 'category-name'
                }).appendTo($item);

                $('<button>', {
                    class: 'expand-category',
                    html: '<span class="spinner"></span>'
                }).appendTo($item);
            });
        },

        renderSubcategories: function(categories, level, $parent) {
            var $container = $('.ced_fruugo_' + level + 'lvl');
            $container.empty();

            if (!categories || !categories.length) return;

            categories.forEach(function(category) {
                var $item = $('<li>', {
                    'data-category': category,
                    'data-level': level,
                    'data-parent': $parent.data('category')
                }).appendTo($container);

                $('<input>', {
                    type: 'checkbox',
                    class: 'category-checkbox',
                    'data-category': category
                }).appendTo($item);

                $('<span>', {
                    text: category,
                    class: 'category-name'
                }).appendTo($item);

                $('<button>', {
                    class: 'expand-category',
                    html: '<span class="spinner"></span>'
                }).appendTo($item);
            });
        },

        loadSavedMappings: function() {
            $.ajax({
                url: fruugosync_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'get_category_mappings',
                    nonce: fruugosync_ajax.nonce
                },
                success: function(response) {
                    if (response.success && response.data.mappings) {
                        this.displaySavedMappings(response.data.mappings);
                    }
                }.bind(this)
            });
        },

        displaySavedMappings: function(mappings) {
            var $container = $('.selected-categories-list');
            $container.empty();

            Object.keys(mappings).forEach(function(wooCategory) {
                var fruugoCategory = mappings[wooCategory];
                $('<div>', {
                    class: 'mapping-item',
                    text: wooCategory + ' → ' + fruugoCategory
                }).appendTo($container);
            });
        }
    };

    // Initialize both functionalities
    categoryManager.init();
});