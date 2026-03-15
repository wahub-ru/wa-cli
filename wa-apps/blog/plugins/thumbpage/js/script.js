$(document).ready(function() {
    var button = $('#uploadButton'), interval;
    var _csrf = $('input[name=_csrf]').val();
    var page_id = $('input[name=page_id]').val();
    
    $('.thumbpage-buttons').on('click', '.ajax-upload-button-wrapper #uploadButton', function() {
        $(this).closest('.ajax-upload-button-wrapper').find('input[type=file]').click();
        return false;
    });
    
    $.ajax_upload(button, {
        action: '?plugin=thumbpage&action=saveImage',
        name: 'thumbpage',
        data: {_csrf: _csrf, page_id: page_id},
        onComplete: function(file, response) {
            var response = $.parseJSON(response);
            if (response.status == 'ok') {
                $("#thumbpage-response").text(file);
                $("#thumbpage-preview").html('<img src="' + response.data.preview + '" />');
                $("#deleteButton").show();
            } else if (response.status == 'fail') {
                $("#response").text(response.errors.join());
            }

        },
    });

    $('#deleteButton').click(function() {
        var _csrf = $('input[name=_csrf]').val();
        var page_id = $('input[name=page_id]').val();
        $.ajax({
            url: "?plugin=thumbpage&action=deleteImage",
            dataType: 'json',
            type: 'POST',
            data: {_csrf: _csrf, page_id: page_id}
        }).done(function(response) {
            $("#thumbpage-preview").html('');
            if (response.data.message) {
                $("#thumbpage-response").text(response.data.message);
            } else {
                $("#thumbpage-response").text('');
            }
            $("#deleteButton").hide();
        });
    });

});
