(function ($) {
    "use strict";

    $.extend($.seonofchar = $.seonofchar || {}, {
        init: function (title, key, desc, page) {
            var self = this;

            var length;
            var $title = $(title);
            var $key = $(key);
            var $desc = $(desc);

            $title.closest('.field').after('<div class="seonofchar-wrap field"><div class="value"><span class="seonofchar-val" id="seonofchar-title"></span> <span class="seonofchar-hint" id="seonofchar-title-hint"></span></div></div>');
            $key.closest('.field').after('<div class="seonofchar-wrap field"><div class="value"><span class="seonofchar-val" id="seonofchar-key"></span> <span class="seonofchar-hint" id="seonofchar-key-hint"></span></div></div>');
            $desc.closest('.field').after('<div class="seonofchar-wrap field"><div class="value"><span class="seonofchar-val" id="seonofchar-desc"></span> <span class="seonofchar-hint" id="seonofchar-desc-hint"></span></div></div>');

            if (self.title_min_max.length >= 2) {
                $('#seonofchar-title-hint').text('(от ' + self.title_min_max[0] + ' до ' + self.title_min_max[1] + ')');
            }
            if (self.key_min_max.length >= 2) {
                $('#seonofchar-key-hint').text('(от ' + self.key_min_max[0] + ' до ' + self.key_min_max[1] + ')');
            }
            if (self.desc_min_max.length >= 2) {
                $('#seonofchar-desc-hint').text('(от ' + self.desc_min_max[0] + ' до ' + self.desc_min_max[1] + ')');
            }

            $('#s-product-edit-forms, #s-product-list-settings-form, #wa-page-advanced-params, #s-product-list-create-form').on('keyup change', title, function (e) {
                var target = $(e.target);
                length = target.val().length;
                if (length === 0 && $title.attr('placeholder') !== undefined) {
                    length = $title.attr('placeholder').length;
                }
                $('#seonofchar-title').text(length);
                self.light(length, '#seonofchar-title', self.title_min_max);
            });

            if (page === 'product' || page === 'category') {
                var tag = $title[0].tagName.toLowerCase();
                setInterval(function () {
                    if ($(title)[0].tagName.toLowerCase() !== tag) {
                        tag = $(title)[0].tagName.toLowerCase();
                        $(title).trigger('change');
                    }
                }, 300);
            }

            $key.on('keyup change', function () {
                length = $key.val().length;
                if (length === 0 && $key.attr('placeholder') !== undefined) {
                    length = $key.attr('placeholder').length;
                }
                $('#seonofchar-key').text(length);
                self.light(length, '#seonofchar-key', self.key_min_max);
            }).change();

            $desc.on('keyup change', function () {
                $('#seonofchar-desc').text($desc.val().length);
                self.light($desc.val().length, '#seonofchar-desc', self.desc_min_max);
            }).change();

            setTimeout(function () {
                $title.trigger('change');
            });
        },
        light: function (count, who, min_max) {
            if (min_max.length < 2) {
                return;
            }
            var _class = '';
            if (count < min_max[0]) {
                _class = 'seonofchar-warning';
            } else if (count >= min_max[0] && count <= min_max[1]) {
                _class = 'seonofchar-success';
            } else if (count > min_max[1]) {
                _class = 'seonofchar-danger';
            }
            $(who).removeClass('seonofchar-warning seonofchar-success seonofchar-danger').addClass(_class);
        }
    });
})(jQuery);