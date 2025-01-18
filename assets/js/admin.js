jQuery(document).ready(function($) {
    // Helper function to show notices
    function showNotice(type, message) {
        const $notice = $('<div>')
            .addClass('notice notice-' + type + ' is-dismissible')
            .append($('<p>').text(message));
            
        $('.wrap > h1').after($notice);
        
        // Auto dismiss after 3 seconds
        setTimeout(function() {
            $notice.fadeOut(function() {
                $(this).remove();
            });
        }, 3000);
    }

    // Test Connection Handler
    $('#test-connection').on('click', function() {
        const $button = $(this);
        const $status = $('#api-status-indicator');
        
        if ($button.hasClass('loading')) return;
        
        $button.addClass('loading').prop('disabled', true)
               .text('Testing Connection...');
        
        $.ajax({
            url: fruugosync_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'test_fruugo_connection',
                nonce: fruugosync_ajax.nonce
            },
            success: function(response) {
                $status.removeClass('connected disconnected');
                
                if (response.success) {
                    $status.addClass('connected')
                           .html('<span class="status-icon"></span>' +
                                '<span class="status-text">Connected</span>');
                    showNotice('success', 'Successfully connected to Fruugo API');
                } else {
                    $status.addClass('disconnected')
                           .html('<span class="status-icon"></span>' +
                                '<span class="status-text">Not Connected</span>' +
                                '<p class="error-message">' + response.data + '</p>');
                    showNotice('error', 'Connection failed: ' + response.data);
                }
            },
            error: function(xhr, status, error) {
                $status.removeClass('connected')
                       .addClass('disconnected')
                       .html('<span class="status-icon"></span>' +
                            '<span class="status-text">Not Connected</span>' +
                            '<p class="error-message">Connection failed: ' + error + '</p>');
                showNotice('error', 'Connection failed: ' + error);
            },
            complete: function() {
                $button.removeClass('loading')
                       .prop('disabled', false)
                       .text('Test Connection');
            }
        });
    });

    // Refresh Categories Handler
    $('#refresh-categories').on('click', function() {
        const $button = $(this);
        
        if ($button.hasClass('loading')) return;
        
        $button.addClass('loading').prop('disabled', true)
               .text('Refreshing Categories...');
        
        $.ajax({
            url: fruugosync_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'refresh_fruugo_categories',
                nonce: fruugosync_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    location.reload(); // Reload to show updated categories
                } else {
                    showNotice('error', 'Failed to refresh categories: ' + response.data);
                }
            },
            error: function(xhr, status, error) {
                showNotice('error', 'Failed to refresh categories: ' + error);
            },
            complete: function() {
                $button.removeClass('loading')
                       .prop('disabled', false)
                       .text('Refresh Fruugo Categories');
            }
        });
    });

    // Category Mapping Form Handler
    $('#category-mapping-form').on('submit', function(e) {
        e.preventDefault();
        
        const $form = $(this);
        const $submitButton = $form.find('button[type="submit"]');
        
        if ($submitButton.hasClass('loading')) return;
        
        $submitButton.addClass('loading').prop('disabled', true)
                    .text('Saving Mappings...');

        const mappings = {};
        $form.find('select[name^="category_mapping"]').each(function() {
            const $select = $(this);
            const categoryId = $select.attr('name').match(/\[(\d+)\]/)[1];
            mappings[categoryId] = $select.val();
        });

        $.ajax({
            url: fruugosync_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'save_category_mapping',
                nonce: fruugosync_ajax.nonce,
                mappings: mappings
            },
            success: function(response) {
                if (response.success) {
                    showNotice('success', 'Category mappings saved successfully');
                } else {
                    showNotice('error', 'Failed to save mappings: ' + response.data);
                }
            },
            error: function(xhr, status, error) {
                showNotice('error', 'Failed to save mappings: ' + error);
            },
            complete: function() {
                $submitButton.removeClass('loading')
                            .prop('disabled', false)
                            .text('Save Mappings');
            }
        });
    });

    // Initialize select2 for category dropdowns if available
    if ($.fn.select2) {
        $('.fruugo-category-select').select2({
            width: '100%',
            placeholder: 'Select Fruugo Category'
        });
    }
});