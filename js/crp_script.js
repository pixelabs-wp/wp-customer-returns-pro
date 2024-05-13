jQuery(document).ready(function($) {
    // Function to show loader within the clicked button
    function showButtonLoader($button, $class) {
        $button.data('originalText', $button.text()); // Store the original button text
        $button.text(''); // Set button text to empty
        $button.prop('disabled', true); // Disable the button
        var type = $class;
        var loaderHtml = '<span class="'+type+'"></span>';
        $button.append(loaderHtml); // Append the loader
    }

    // Function to hide loader within the clicked button
    function hideButtonLoader($button, $class) {
        var type = $class;
        $button.text($button.data('originalText')); // Restore the original button text
        $button.prop('disabled', false); // Enable the button
        $button.find('.'+type).remove(); // Remove the loader
    }

    // Function to update the table
    function updateTable() {
        $.ajax({
            type: 'GET',
            url: window.location.href, // Reload the current page to update the table
            success: function(response) {
                var $table = $(response).find('#form_data');
                $('#form_data').html($table.html());
            }
        });
    }

    // Add Ajax logic for form submission
    $('#submit-blacklist').on('click', function() {
        var $class = "loader";
        var $button = $(this);
        showButtonLoader($button, $class); // Show loader within the clicked button
        var detailType = $('#detail-type').val();
        var detail = $('#detail').val();
        if(detail){
            if(detailType == 'email')
            {
                var emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                // Check if the email matches the pattern
                if (emailPattern.test(detail)) {
                    $.ajax({
                        type: 'POST',
                        url: crp_ajax_object.ajax_url,
                        data: {
                            action: 'crp_add_to_blacklist',
                            detailType: detailType,
                            detail: detail
                        },
                        success: function(response) {
                            hideButtonLoader($button, $class);
                            updateTable(); // Update the table after successful form submission
                            // Hide loader within the clicked button
                        }
                    });
                }
                else{
                    hideButtonLoader($button, $class);
                    // Invalid email address
                    alert('Invalid email address');
                }
            } else {
                $.ajax({
                    type: 'POST',
                    url: crp_ajax_object.ajax_url,
                    data: {
                        action: 'crp_add_to_blacklist',
                        detailType: detailType,
                        detail: detail
                    },
                    success: function(response) {
                        hideButtonLoader($button, $class);
                        updateTable(); // Update the table after successful form submission
                        // Hide loader within the clicked button
                    }
                });
            }
        }
        else{
            if(detailType == 'email')
            {
                hideButtonLoader($button, $class);
                alert('Add an email address');
            }else if(detailType == 'phone')
            {
                hideButtonLoader($button, $class);
                alert('Add a phone no');
            }
            
        }
    });

    // Add JavaScript logic for delete action
    $('#form_data').on('click', '.delete', function() {
        var $button = $(this);
        var $class = "loader-red";
        showButtonLoader($button, $class); // Show loader within the clicked button
        var id = $(this).data('id');

        $.ajax({
            type: 'POST',
            url: crp_ajax_object.ajax_url,
            data: {
                action: 'crp_delete_from_blacklist',
                id: id
            },
            success: function(response) {
                hideButtonLoader($button, $class);
                updateTable(); // Update the table after successful deletion

                 // Hide loader within the clicked button
            }
        });
    });

    // Add JavaScript logic for toggle block/unblock action
    $('#form_data').on('click', '.toggle-status', function() {
        var $button = $(this);
        var $class = "loader";
        showButtonLoader($button, $class); // Show loader within the clicked button
        var id = $(this).data('id');

        $.ajax({
            type: 'POST',
            url: crp_ajax_object.ajax_url,
            data: {
                action: 'crp_toggle_blacklist_status',
                id: id
            },
            success: function(response) {
                hideButtonLoader($button, $class);
                updateTable(); // Update the table after successful status toggle
                 // Hide loader within the clicked button
            }
        });
    });

    $('.switch, .switch2').on('click', function() {
        var checkboxName = $(this).prev('input[type="checkbox"]').attr('name');
        var switchState = $(this).prev('input[type="checkbox"]').is(':checked') ? 'off' : 'on';

        // Send AJAX request to update the switch state
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'update_switch_state',
                checkbox_name: checkboxName,
                switch_state: switchState
            },
            success: function(response) {
                if (response.success) {
                    console.log('Switch state updated successfully.');
                } else {
                    console.error('Error updating switch state: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error: ' + error);
            }
        });
    });
});
