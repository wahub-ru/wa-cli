(function ($) {
    $.Link_photo = {
        init: function () {
            var $submit_button = $('#link-plugin-submit');
            $submit_button.removeClass('red');
            $submit_button.removeClass('green');
            $submit_button.addClass('gray');
        },
        submit: function (el) {
            var self = this;
            var form = $(el).serialize();
            $.post('?plugin=link&action=save',
                form
                , function (d) {
                    var $submit_button = $('#link-plugin-submit');
                    if (d.status === 'ok') {
                        $submit_button.removeClass('gray');
                        $submit_button.removeClass('red');
                        $submit_button.addClass('green');
                    }
                    else {
                        $submit_button.removeClass('gray');
                        $submit_button.removeClass('green');
                        $submit_button.addClass('red');
                    }
                }, 'json'
            );

            return false;
        }
    }
})(jQuery);