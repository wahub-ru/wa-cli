if (!$.wa_blog_plugins_dzen) {
    $.wa_blog_plugins_dzen = {
        init: function () {
            var self = this;
            $('#plugin-dzen-form').on('submit.plugins_dzen', function (event) {
                return self.submitHandler(this, event);
            });

            self.initDefaultEnclosureUpload();
            self.initUi();
        },

        initUi: function () {
            var self = this;

            $(document).on('change', 'input[name="settings[feed_mode]"]', function () {
                var isSeparate = $(this).val() === 'separate_by_blog' && $(this).is(':checked');
                $('.js-dzen-feed-blogs-wrap').toggle(isSeparate);
                self.renderFeedUrls();
                self.updateDefaultStatus();
            });

            $(document).on('change', 'input[name="settings[feed_blog_ids][]"]', function () {
                self.renderFeedUrls();
            });

            $(document).on('click', '.js-dzen-copy-url', function () {
                var value = String($(this).data('url') || $(this).siblings('.js-dzen-feed-url-input').val() || '');
                if (!value) {
                    return;
                }

                var done = function () {
                    $('.js-dzen-copy-status').text('URL скопирован в буфер обмена').show().delay(1400).fadeOut(200);
                };

                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(value).then(done);
                    return;
                }

                var $tmp = $('<input type="text">').val(value).appendTo('body');
                $tmp.trigger('select');
                document.execCommand('copy');
                $tmp.remove();
                done();
            });

            self.renderFeedUrls();

            $(document).on('click', '.js-dzen-show-token-btn', function (e) {
                e.preventDefault();
                var $token = $('.js-dzen-api-token-input');
                if (!$token.length) {
                    return;
                }

                var isHidden = $token.attr('type') === 'password';
                $token.attr('type', isHidden ? 'text' : 'password');
                $(this).text(isHidden ? 'Скрыть токен' : 'Показать токен');
            });

            $(document).on('click', '.js-dzen-create-token-btn', function (e) {
                e.preventDefault();
                self.submitWithTokenAction('create');
            });

            $(document).on('click', '.js-dzen-recreate-token-btn', function (e) {
                e.preventDefault();
                self.submitWithTokenAction('recreate');
            });

            $(document).on('click', '.js-dzen-delete-token-btn', function (e) {
                e.preventDefault();
                if (!window.confirm('Удалить API токен?')) {
                    return;
                }
                self.submitWithTokenAction('delete');
            });
        },

        renderFeedUrls: function () {
            var $form = $('#plugin-dzen-form');
            var $wrap = $form.find('.js-dzen-feed-urls-wrap');
            if (!$wrap.length) {
                return;
            }

            var mode = String($form.find('input[name="settings[feed_mode]"]:checked').val() || 'all_in_one');
            var allInOneUrl = String($form.data('feed-url-all') || '');
            var hasAvailableFeedUrls = String($form.data('has-feed-urls') || '0') === '1';
            var urls = [];

            if (mode === 'separate_by_blog') {
                $form.find('input[name="settings[feed_blog_ids][]"]:checked').each(function () {
                    var url = String($(this).data('feed-url') || '');
                    if (url) {
                        urls.push(url);
                    }
                });
            } else if (allInOneUrl) {
                urls.push(allInOneUrl);
            }

            var primary = urls.length ? urls[0] : '';
            $('.js-dzen-feed-url-input').val(primary);
            $('.js-dzen-open-url').attr('href', primary || '#');

            if (!urls.length) {
                if (!hasAvailableFeedUrls) {
                    $wrap.html('<span class="hint">Нет доступных блогов. Создайте блог в приложении «Блог» и добавьте его в маршрутизацию сайта.</span>');
                } else if (mode === 'separate_by_blog') {
                    $wrap.html('<span class="hint">Выберите хотя бы один блог.</span>');
                } else {
                    $wrap.html('<span class="hint">URL фида недоступен для текущих настроек.</span>');
                }
                return;
            }

            var makeSafeUrl = function (url) {
                var value = String(url || '').trim();
                if (!value) {
                    return '';
                }

                if (/^https?:\/\//i.test(value) || /^\/\//.test(value) || value.indexOf('/') === 0) {
                    return value;
                }

                return '';
            };

            var feedStats = {};
            try {
                feedStats = JSON.parse(String($form.attr('data-feed-stats') || '{}')) || {};
            } catch (e) {
                feedStats = {};
            }

            var allBlogStats = feedStats.by_blog || {};
            var selectedBlogIds = [];
            $form.find('input[name="settings[feed_blog_ids][]"]:checked').each(function () {
                selectedBlogIds.push(String($(this).val()));
            });

            var allBlogIds = [];
            $form.find('input[name="settings[feed_blog_ids][]"]').each(function () {
                allBlogIds.push(String($(this).val()));
            });

            var effectiveBlogIds = selectedBlogIds.length ? selectedBlogIds : allBlogIds;
            var allInOneStats = { total: 0, recent: 0, required_total: 10, required_recent: 3 };
            $.each(effectiveBlogIds, function (_, blogId) {
                var blogStats = allBlogStats[blogId] || {};
                allInOneStats.total += parseInt(blogStats.total || 0, 10);
                allInOneStats.recent += parseInt(blogStats.recent || 0, 10);
            });

            var missingTotal = Math.max(0, (allInOneStats.required_total || 10) - allInOneStats.total);
            var missingRecent = Math.max(0, (allInOneStats.required_recent || 3) - allInOneStats.recent);
            if (allInOneStats.total <= 0) {
                allInOneStats.level = 'error';
                allInOneStats.message = 'Фид не готов, нет записей.';
            } else if (!missingTotal && !missingRecent) {
                allInOneStats.level = 'success';
                allInOneStats.message = 'Все в порядке, можно публиковать.';
            } else {
                allInOneStats.level = 'warning';
                var parts = [];
                if (missingRecent > 0) {
                    parts.push('Добавьте ещё ' + missingRecent + ' пост(а), у вас ' + allInOneStats.recent + ' новых за последний месяц');
                }
                if (missingTotal > 0) {
                    parts.push('в фиде ' + allInOneStats.total + ' постов, а требуется минимум 10');
                }
                allInOneStats.message = parts.join('. ') + '.';
            }

            var renderFeedMeta = function (stats) {
                if (!stats) {
                    return $('<div class="dzen-feed-meta is-warning">Нет данных по фиду</div>');
                }

                var level = String(stats.level || 'warning');
                var icon = level === 'success' ? '✅' : (level === 'error' ? '❌' : '⚠️');
                var total = parseInt(stats.total || 0, 10);
                var recent = parseInt(stats.recent || 0, 10);

                return $('<div class="dzen-feed-meta is-' + level + '"></div>')
                    .append('<div class="dzen-feed-meta__count">Материалов в фиде: <strong>' + total + '</strong> · За месяц: <strong>' + recent + '</strong></div>')
                    .append('<div class="dzen-feed-meta__message">' + icon + ' ' + (stats.message || '') + '</div>');
            };

            $wrap.empty();
            $.each(urls, function (_, url) {
                var safeUrl = makeSafeUrl(url);
                if (!safeUrl) {
                    return;
                }

                var $box = $('<div class="dzen-url-box"></div>');
                $('<input type="text" readonly class="js-dzen-feed-url-input">').val(safeUrl).appendTo($box);

                var copyIcon = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-copy dzen-icon" viewBox="0 0 16 16" aria-hidden="true"><path fill-rule="evenodd" d="M4 2a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2zm2-1a1 1 0 0 0-1 1v8a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1zM2 5a1 1 0 0 0-1 1v8a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1v-1h1v1a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h1v1z"/></svg>';
                $('<button type="button" class="button small js-dzen-copy-url" title="Скопировать URL" aria-label="Скопировать URL"></button>')
                    .attr('data-url', safeUrl)
                    .html(copyIcon)
                    .appendTo($box);

                var openIcon = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-box-arrow-up-right dzen-icon" viewBox="0 0 16 16" aria-hidden="true"><path fill-rule="evenodd" d="M8.636 3.5a.5.5 0 0 0-.5-.5H1.5A1.5 1.5 0 0 0 0 4.5v10A1.5 1.5 0 0 0 1.5 16h10a1.5 1.5 0 0 0 1.5-1.5V7.864a.5.5 0 0 0-1 0V14.5a.5.5 0 0 1-.5.5h-10a.5.5 0 0 1-.5-.5v-10a.5.5 0 0 1 .5-.5h6.636a.5.5 0 0 0 .5-.5"/><path fill-rule="evenodd" d="M16 .5a.5.5 0 0 0-.5-.5h-5a.5.5 0 0 0 0 1h3.793L6.146 9.146a.5.5 0 1 0 .708.708L15 1.707V5.5a.5.5 0 0 0 1 0z"/></svg>';
                $('<a target="_blank" rel="noopener" class="button small light-gray" title="Открыть URL" aria-label="Открыть URL"></a>')
                    .attr('href', safeUrl)
                    .html(openIcon)
                    .appendTo($box);

                var stats = null;
                if (mode === 'all_in_one') {
                    stats = allInOneStats;
                } else {
                    var $blogCheckbox = $form.find('input[name="settings[feed_blog_ids][]"]').filter(function () {
                        return String($(this).data('feed-url') || '') === safeUrl;
                    }).first();
                    var blogId = String($blogCheckbox.val() || '');
                    stats = allBlogStats[blogId] || null;
                }
                $box.append(renderFeedMeta(stats));

                $wrap.append($box);
            });
        },


        updateDefaultStatus: function () {
            var url = String($('.js-dzen-default-enclosure-input').val() || '').trim();
            var $status = $('.js-dzen-default-status');
            if (!$status.length) {
                return;
            }

            if (url) {
                $status.removeClass('is-muted').addClass('is-ok').text('Обложка по умолчанию настроена');
            } else {
                $status.removeClass('is-ok').addClass('is-muted').text('Обложка по умолчанию не задана');
            }
        },

        submitWithTokenAction: function (action) {
            var $form = $('#plugin-dzen-form');
            $form.find('.js-dzen-api-token-action').val(action);
            $form.trigger('submit');
        },

        initDefaultEnclosureUpload: function () {
            function setDefaultEnclosure(url) {
                var $input = $('.js-dzen-default-enclosure-input');
                var $preview = $('.js-dzen-default-enclosure-preview');
                var $remove = $('.js-dzen-default-remove-btn');
                var $delete = $('.js-dzen-default-enclosure-delete');

                $input.val(url || '');
                $delete.val(url ? '0' : '1');

                if (url) {
                    $preview.attr('src', url).show();
                    $remove.show();
                } else {
                    $preview.hide().attr('src', '');
                    $remove.hide();
                }

                $.wa_blog_plugins_dzen.updateDefaultStatus();
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

            $(document).on('click', '.js-dzen-default-upload-btn', function (e) {
                e.preventDefault();
                $('#dzen-default-enclosure-file').trigger('click');
            });

            $(document).on('click', '.js-dzen-default-remove-btn', function (e) {
                e.preventDefault();
                setDefaultEnclosure('');
                $('.js-dzen-default-upload-status').removeClass('state-success').text('Изображение удалено').show();
            });

            $(document).on('input', '.js-dzen-default-enclosure-input', function () {
                var v = $(this).val();
                if (v) {
                    $('.js-dzen-default-enclosure-delete').val('0');
                    $('.js-dzen-default-enclosure-preview').attr('src', v).show();
                    $('.js-dzen-default-remove-btn').show();
                }
                $.wa_blog_plugins_dzen.updateDefaultStatus();
            });

            $(document).on('change', '#dzen-default-enclosure-file', function () {
                if (!this.files || !this.files.length) {
                    return;
                }

                var $status = $('.js-dzen-default-upload-status');
                $status.removeClass('errormsg state-success').text('Загрузка...').show();

                uploadFile(this.files[0], function (response) {
                    var url = response && response.data && response.data.url ? response.data.url : '';
                    if (url) {
                        setDefaultEnclosure(url);
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
        },

        submitHandler: function (form, event) {
            event.preventDefault();

            var $form = $(form);
            var $submit = $form.find('.js-dzen-save-btn').first();
            var $status = $form.find('.js-status');
            var defaultText = $submit.val() || 'Save';
            var action = String($form.find('.js-dzen-api-token-action').val() || 'keep');

            $status.removeClass('errormsg state-success').hide();
            $submit.prop('disabled', true).val(defaultText + '...');

            $.post($form.attr('action'), $form.serialize())
                .done(function () {
                    $status.addClass('state-success').text('Сохранено').show();

                    if (action === 'create' || action === 'recreate' || action === 'delete') {
                        window.location.reload();
                    }
                })
                .fail(function (xhr) {
                    var message = 'Ошибка сохранения';
                    if (xhr && xhr.responseText) {
                        message += ': ' + xhr.responseText;
                    }
                    $status.addClass('errormsg').text(message).show();
                })
                .always(function () {
                    $submit.prop('disabled', false).val(defaultText);
                    $form.find('.js-dzen-api-token-action').val('keep');
                });

            return false;
        }
    };

    $.wa_blog_plugins_dzen.init();
}
