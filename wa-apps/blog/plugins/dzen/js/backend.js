(function ($) {
    function moveDzenBlock() {
        var $fields = $('#dzen-post-fields');
        if (!$fields.length) {
            return;
        }

        var $target = $('#post-editor');
        if ($target.length) {
            $fields.insertAfter($target);
        }

        var $form = $target.closest('form');
        if (!$form.length) {
            $form = $('#post-edit-form, #post-form').first();
        }
        if (!$form.length && $fields.closest('form').length) {
            $form = $fields.closest('form');
        }

        if ($form.length && !$fields.closest('form').is($form)) {
            $form.append($fields);
        }
    }


    function normalizeUploadUrl(response) {
        if (!response) {
            return '';
        }

        if (response.data && response.data.url) {
            return response.data.url;
        }

        if (response.url) {
            return response.url;
        }

        return '';
    }

    function setCover(url) {
        var $input = $('.js-dzen-enclosure-input');
        var $preview = $('.js-dzen-cover-preview');
        var $remove = $('.js-dzen-remove-cover');
        var $delete = $('.js-dzen-enclosure-delete');

        $input.val(url || '');
        $delete.val(url ? '0' : '1');

        if (url) {
            $preview.attr('src', url).show();
            $remove.show();
        } else {
            $preview.hide().attr('src', '');
            $remove.hide();
        }

        updateRssPreview();
        validateDzen();
    }

    function updateDescriptionCounter() {
        var $field = $('.js-dzen-description');
        if (!$field.length) {
            return;
        }

        var value = String($field.val() || '');
        $('.js-dzen-description-count').text(value.length);
    }

    function updateRssPreview() {
        var guid = $.trim($('input[name="plugin[dzen][guid]"]').val() || '') || 'Будет сгенерирован автоматически';
        var enclosure = $.trim($('input[name="plugin[dzen][enclosure_url]"]').val() || '') || 'Не указано';

        $('.js-dzen-preview-guid').text(guid);
        $('.js-dzen-preview-enclosure').text(enclosure);
    }

    function getPostLinkValue() {
        var fromInput = $('input[name="post[link]"], input[name="url"]').first().val() || '';
        if (fromInput) {
            return fromInput;
        }

        var fromLocation = $('.js-post-url, .post-url, .s-post-url').first().text();
        return $.trim(fromLocation || '');
    }

    function renderCheckList(checks) {
        var $list = $('.js-dzen-validate-checks');
        $list.empty();

        if (!checks || !checks.length) {
            $list.hide();
            return;
        }

        var order = { error: 0, warning: 1, success: 2 };
        checks.sort(function (a, b) {
            var la = order[a.level] !== undefined ? order[a.level] : 3;
            var lb = order[b.level] !== undefined ? order[b.level] : 3;
            return la - lb;
        });

        $.each(checks, function (_, check) {
            var level = check.level || 'success';
            var icon = '✅';
            if (level === 'warning') {
                icon = '⚠️';
            } else if (level === 'error') {
                icon = '❌';
            }

            $list.append('<li class="is-' + level + '"><span class="dzen-check-icon">' + icon + '</span><div><strong>' + (check.title || 'Проверка') + '</strong><br>' + (check.message || '') + '</div></li>');
        });

        $list.show();
    }

    function normalizeValidateResponse(response) {
        if (response && response.data && response.status === 'ok' && !response.errors) {
            return response.data;
        }
        return response;
    }

    function renderValidateResult(response) {
        response = normalizeValidateResponse(response);

        var $status = $('.js-dzen-validate-status');
        var $errors = $('.js-dzen-validate-errors');
        var $warnings = $('.js-dzen-validate-warnings');
        var $preview = $('.js-dzen-validate-preview');

        $errors.empty().hide();
        $warnings.empty().hide();
        $preview.empty().hide();

        if (!response) {
            $status.removeClass('state-success state-warning').addClass('errormsg').text('Ошибка проверки').show();
            renderCheckList([]);
            return;
        }

        $status.removeClass('errormsg state-success state-warning');
        if (response.status === 'fail') {
            $status.addClass('errormsg').text('Критичные ошибки: ' + (response.errors ? response.errors.length : 0));
        } else if (response.status === 'warn') {
            $status.addClass('state-warning').text('Есть предупреждения: ' + (response.warnings ? response.warnings.length : 0));
        } else {
            $status.addClass('state-success').text('Проверка пройдена успешно');
        }
        $status.show();

        if (response.errors && response.errors.length) {
            $.each(response.errors, function (_, err) {
                $errors.append('<li><strong>' + (err.field || 'field') + ':</strong> ' + (err.message || '') + '<br><span class="hint">' + (err.hint || '') + '</span></li>');
            });
            $errors.show();
        }

        if (response.warnings && response.warnings.length) {
            $.each(response.warnings, function (_, warn) {
                $warnings.append('<li><strong>' + (warn.field || 'field') + ':</strong> ' + (warn.message || '') + '<br><span class="hint">' + (warn.hint || '') + '</span></li>');
            });
            $warnings.show();
        }

        renderCheckList(response.checks || []);

        if (response.preview) {
            $preview.append('<p><strong>Title:</strong> ' + (response.preview.title || '—') + '</p>');
            $preview.append('<p><strong>Link:</strong> ' + (response.preview.link || '—') + '</p>');
            $preview.append('<p><strong>Description:</strong> ' + (response.preview.description || '—') + '</p>');
            $preview.append('<p><strong>Enclosure:</strong> ' + (response.preview.enclosure || '—') + '</p>');
            $preview.append('<p><strong>Full-text:</strong> ' + (response.preview.full_text || '—') + '</p>');
            $preview.show();
        }
    }

    function validateDzen(force) {
        var postId = parseInt($('input[name="post[id]"], input[name="id"], #post-id').first().val(), 10) || 0;
        var $status = $('.js-dzen-validate-status');

        if (postId <= 0) {
            if (force) {
                $status.removeClass('errormsg state-success state-warning')
                    .addClass('state-warning')
                    .text('Сначала сохраните запись, затем запустите проверку')
                    .show();
            }
            return;
        }

        var payload = {
            post_id: postId
        };

        $status.removeClass('errormsg state-success state-warning').text('Проверяем...').show();

        $.getJSON('?plugin=dzen&module=backend&action=validate', payload)
            .done(renderValidateResult)
            .fail(function (xhr) {
                var message = 'Не удалось выполнить проверку';
                if (xhr && xhr.responseJSON && xhr.responseJSON.error) {
                    message = xhr.responseJSON.error;
                }
                $status.removeClass('state-success state-warning').addClass('errormsg').text(message).show();
            });
    }

    function initAccordion() {
        $('#dzen-post-fields .dzen-post-section').each(function () {
            var $section = $(this);
            if ($section.find('.dzen-post-section__toggle').length) {
                return;
            }

            var $title = $section.find('.dzen-post-section__title').first();
            if (!$title.length) {
                return;
            }

            var titleText = $.trim($title.text());
            var $body = $('<div class="dzen-post-section__body"></div>');
            $body.append($title.nextAll());

            var $toggle = $('<button type="button" class="dzen-post-section__toggle"></button>');
            $toggle.append('<span>' + titleText + '</span><span class="dzen-post-section__arrow"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-caret-down dzen-icon" viewBox="0 0 16 16" aria-hidden="true"><path d="M3.204 5h9.592L8 10.481zm-.753.659 4.796 5.48a1 1 0 0 0 1.506 0l4.796-5.48c.566-.647.106-1.659-.753-1.659H3.204a1 1 0 0 0-.753 1.659"/></svg></span>');

            $title.remove();
            $section.prepend($toggle).append($body);

            var shouldCollapse = titleText === 'Идентификаторы и ссылки';
            $section.toggleClass('is-collapsed', shouldCollapse);
            if (shouldCollapse) {
                $body.hide();
            }
        });

        $(document).on('click', '.dzen-post-section__toggle', function () {
            var $section = $(this).closest('.dzen-post-section');
            var $body = $section.find('.dzen-post-section__body').first();
            var collapsed = $section.hasClass('is-collapsed');
            $section.toggleClass('is-collapsed', !collapsed);
            $body.stop(true, true).slideToggle(140);
        });
    }

    function uploadFile(file, done, fail) {
        var fd = new FormData();
        fd.append('file', file);

        var csrf = $('input[name="_csrf"]').val();
        if (csrf) {
            fd.append('_csrf', csrf);
        }

        $.ajax({
            url: '?plugin=dzen&module=backend&action=upload',
            method: 'POST',
            data: fd,
            processData: false,
            contentType: false
        }).done(done).fail(fail);
    }

    function initUpload() {
        $(document).on('click', '.js-dzen-upload-btn', function (e) {
            e.preventDefault();
            $('#dzen-enclosure-file').trigger('click');
        });

        $(document).on('click', '.js-dzen-remove-cover', function (e) {
            e.preventDefault();
            setCover('');
            $('.js-dzen-upload-status').removeClass('state-success').text('Обложка удалена').show();
            validateDzen(false);
        });

        $(document).on('input change', '#dzen-post-fields input, #dzen-post-fields select, #dzen-post-fields textarea', function () {
            updateDescriptionCounter();
            updateRssPreview();
        });

        $(document).on('click', '.js-dzen-validate-btn', function (e) {
            e.preventDefault();
            validateDzen(true);
        });

        $(document).on('change', '#dzen-enclosure-file', function () {
            if (!this.files || !this.files.length) {
                return;
            }

            var $status = $('.js-dzen-upload-status');
            $status.removeClass('errormsg state-success').text('Загрузка...').show();

            uploadFile(this.files[0], function (response) {
                var url = normalizeUploadUrl(response);
                if (url) {
                    setCover(url);
                    $('.js-dzen-cover-preview').attr('src', url + (url.indexOf('?') === -1 ? '?_=' + Date.now() : '&_=' + Date.now()));
                    $status.addClass('state-success').text('Файл загружен');
                                } else {
                    $status.addClass('errormsg').text('Ошибка ответа загрузки');
                }
            }, function (xhr) {
                var message = 'Ошибка загрузки';
                if (xhr && xhr.responseJSON && xhr.responseJSON.errors) {
                    message = xhr.responseJSON.errors;
                }
                $status.addClass('errormsg').text(message);
            });
        });
        $(document).on('error', '.js-dzen-cover-preview', function () {
            var $status = $('.js-dzen-upload-status');
            $status.removeClass('state-success').addClass('errormsg').text('Файл загружен, но превью недоступно по URL').show();
        });
    }

    $(function () {
        moveDzenBlock();
        initUpload();
        initAccordion();
        updateDescriptionCounter();
        updateRssPreview();
        validateDzen();
    });
})(jQuery);
