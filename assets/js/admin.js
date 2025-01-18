jQuery(document).ready(function($) {
    // Test Connection Handler
    $('#test-connection').on('click', function() {
        var $button = $(this);
        var $status = $('#api-status-indicator');
        
        // Disable button and show loading state
        $button.prop('disabled', true).text('Testing...');
        
        $.ajax({
            url: fruugosync_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'test_fruugo_connection',
                nonce: fruugosync_ajax.nonce
            },
            success: function(response) {
                $status.removeClass('connected disconnected unknown');
                
                if (response.success) {
                    $status.addClass('connected')
                        .html('<span class="status-text">Connected</span>');
                } else {
                    $status.addClass('disconnected')
                        .html('<span class="status-text">Not Connected</span>' +
                              '<p class="error-message">' + response.message + '</p>');
                }
            },
            error: function(xhr, status, error) {
                $status.removeClass('connected disconnected unknown')
                    .addClass('disconnected')
                    .html('<span class="status-text">Not Connected</span>' +
                          '<p class="error-message">Ajax error: ' + error + '</p>');
            },
            complete: function() {
                // Reset button state
                $button.prop('disabled', false).text('Test Connection');
            }
        });
    });

    // Generate CSV Handler
    $('#generate-csv').on('click', function() {
        var $button = $(this);
        $button.prop('disabled', true).text('Generating...');
        
        // Add CSV generation logic here
        
        setTimeout(function() {
            $button.prop('disabled', false).text('Generate Product CSV');
        }, 2000);
    });
});