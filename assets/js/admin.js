// jQuery(document).ready(function($) {
//     // Helper function to show notices
//     function showNotice(type, message) {
//         const $notice = $('<div>')
//             .addClass('notice notice-' + type + ' is-dismissible')
//             .append($('<p>').text(message));
            
//         $('.wrap > h1').after($notice);
        
//         // Auto dismiss after 3 seconds
//         setTimeout(function() {
//             $notice.fadeOut(function() {
//                 $(this).remove();
//             });
//         }, 3000);
//     }

//     // Test Connection Handler
//     $('#test-connection').on('click', function() {
//         const $button = $(this);
//         const $status = $('#api-status-indicator');
        
//         if ($button.hasClass('loading')) return;
        
//         $button.addClass('loading').prop('disabled', true)
//                .text('Testing Connection...');
        
//         $.ajax({
//             url: fruugosync_ajax.ajax_url,
//             type: 'POST',
//             data: {
//                 action: 'test_fruugo_connection',
//                 nonce: fruugosync_ajax.nonce
//             },
//             success: function(response) {
//                 $status.removeClass('connected disconnected');
                
//                 if (response.success) {
//                     $status.addClass('connected')
//                            .html('<span class="status-icon"></span>' +
//                                 '<span class="status-text">Connected</span>');
//                     showNotice('success', 'Successfully connected to Fruugo API');
//                 } else {
//                     $status.addClass('disconnected')
//                            .html('<span class="status-icon"></span>' +
//                                 '<span class="status-text">Not Connected</span>' +
//                                 '<p class="error-message">' + response.data + '</p>');
//                     showNotice('error', 'Connection failed: ' + response.data);
//                 }
//             },
//             error: function(xhr, status, error) {
//                 $status.removeClass('connected')
//                        .addClass('disconnected')
//                        .html('<span class="status-icon"></span>' +
//                             '<span class="status-text">Not Connected</span>' +
//                             '<p class="error-message">Connection failed: ' + error + '</p>');
//                 showNotice('error', 'Connection failed: ' + error);
//             },
//             complete: function() {
//                 $button.removeClass('loading')
//                        .prop('disabled', false)
//                        .text('Test Connection');
//             }
//         });
//     });

//     // Refresh Categories Handler
//     $('#refresh-categories').on('click', function() {
//         const $button = $(this);
        
//         if ($button.hasClass('loading')) return;
        
//         $button.addClass('loading').prop('disabled', true)
//                .text('Refreshing Categories...');
        
//         $.ajax({
//             url: fruugosync_ajax.ajax_url,
//             type: 'POST',
//             data: {
//                 action: 'refresh_fruugo_categories',
//                 nonce: fruugosync_ajax.nonce
//             },
//             success: function(response) {
//                 if (response.success) {
//                     location.reload(); // Reload to show updated categories
//                 } else {
//                     showNotice('error', 'Failed to refresh categories: ' + response.data);
//                 }
//             },
//             error: function(xhr, status, error) {
//                 showNotice('error', 'Failed to refresh categories: ' + error);
//             },
//             complete: function() {
//                 $button.removeClass('loading')
//                        .prop('disabled', false)
//                        .text('Refresh Fruugo Categories');
//             }
//         });
//     });

//     // Category Mapping Form Handler
//     $('#category-mapping-form').on('submit', function(e) {
//         e.preventDefault();
        
//         const $form = $(this);
//         const $submitButton = $form.find('button[type="submit"]');
        
//         if ($submitButton.hasClass('loading')) return;
        
//         $submitButton.addClass('loading').prop('disabled', true)
//                     .text('Saving Mappings...');

//         const mappings = {};
//         $form.find('select[name^="category_mapping"]').each(function() {
//             const $select = $(this);
//             const categoryId = $select.attr('name').match(/\[(\d+)\]/)[1];
//             mappings[categoryId] = $select.val();
//         });

//         $.ajax({
//             url: fruugosync_ajax.ajax_url,
//             type: 'POST',
//             data: {
//                 action: 'save_category_mapping',
//                 nonce: fruugosync_ajax.nonce,
//                 mappings: mappings
//             },
//             success: function(response) {
//                 if (response.success) {
//                     showNotice('success', 'Category mappings saved successfully');
//                 } else {
//                     showNotice('error', 'Failed to save mappings: ' + response.data);
//                 }
//             },
//             error: function(xhr, status, error) {
//                 showNotice('error', 'Failed to save mappings: ' + error);
//             },
//             complete: function() {
//                 $submitButton.removeClass('loading')
//                             .prop('disabled', false)
//                             .text('Save Mappings');
//             }
//         });
//     });

//     // Initialize select2 for category dropdowns if available
//     if ($.fn.select2) {
//         $('.fruugo-category-select').select2({
//             width: '100%',
//             placeholder: 'Select Fruugo Category'
//         });
//     }
// });

jQuery(document).ready(function($) {
    // Category management
    var fruugoCategories = {
        init: function() {
            this.bindEvents();
            this.loadCategories();
        },

        bindEvents: function() {
            $('#refresh-categories').on('click', this.refreshCategories.bind(this));
            $('.category-tree-container').on('click', '.category-item', this.toggleCategory.bind(this));
            $('.category-tree-container').on('click', '.expand-category', this.expandCategory.bind(this));
        },

        loadCategories: function() {
            try {
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
                            this.renderCategories(response.data.categories);
                        } else {
                            this.showError(response.data.message || 'Failed to load categories');
                        }
                    }.bind(this),
                    error: function(xhr, status, error) {
                        this.showError('Error loading categories: ' + error);
                    }.bind(this),
                    complete: function() {
                        $('.loading-indicator').hide();
                    }
                });
            } catch (error) {
                console.error('Error in loadCategories:', error);
                this.showError('An unexpected error occurred while loading categories');
                $('.loading-indicator').hide();
            }
        },

        refreshCategories: function(e) {
            e.preventDefault();
            try {
                var button = $(e.target);
                button.prop('disabled', true);
                $('.loading-indicator').show();
                $('#category-error').hide();

                $.ajax({
                    url: fruugosync_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'refresh_fruugo_categories',
                        nonce: fruugosync_ajax.nonce,
                        force_refresh: true
                    },
                    timeout: 60000, // 60 second timeout
                    success: function(response) {
                        if (response.success) {
                            this.renderCategories(response.data.categories);
                        } else {
                            this.showError(response.data.message || 'Failed to refresh categories');
                        }
                    }.bind(this),
                    error: function(xhr, status, error) {
                        if (status === 'timeout') {
                            this.showError('Request timed out. Please try again.');
                        } else {
                            this.showError('Error refreshing categories: ' + error);
                        }
                    }.bind(this),
                    complete: function() {
                        button.prop('disabled', false);
                        $('.loading-indicator').hide();
                    }
                });
            } catch (error) {
                console.error('Error in refreshCategories:', error);
                this.showError('An unexpected error occurred while refreshing categories');
                button.prop('disabled', false);
                $('.loading-indicator').hide();
            }
        },

        renderCategories: function(categories) {
            try {
                var container = $('.ced_fruugo_1lvl');
                container.empty();

                if (!categories || !categories.length) {
                    this.showError('No categories available');
                    return;
                }

                categories.forEach(function(category) {
                    var item = $('<li>', {
                        class: 'category-item'
                    });

                    var label = $('<label>', {
                        text: category.name
                    });

                    if (category.children && category.children.length) {
                        var expandButton = $('<span>', {
                            class: 'expand-category dashicons dashicons-arrow-right-alt2',
                            'data-category-id': category.id
                        });
                        item.append(expandButton);
                    }

                    item.append(label);
                    container.append(item);
                });
            } catch (error) {
                console.error('Error in renderCategories:', error);
                this.showError('Error rendering categories');
            }
        },

        showError: function(message) {
            var errorDiv = $('#category-error');
            errorDiv.find('p').text(message);
            errorDiv.show();
        }
    };

    // Initialize category management
    fruugoCategories.init();
});