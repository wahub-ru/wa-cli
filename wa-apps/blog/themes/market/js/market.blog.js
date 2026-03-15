(/** @param {JQueryStatic} $ */ function ($) {
    var market = window.market;
    var market_blog = market.blog = market.blog || {};

    var Update = market.Update;

    var ComponentRegistry = market.ComponentRegistry;

    var ModalUtil = market.ModalUtil;
    var SlideDownUtil = market.SlideDownUtil;
    var PageSeparatorBuilder = market.PageSeparatorBuilder;

    var LazyLoad = market.LazyLoad;

    var BlogStream = market_blog.BlogStream = ComponentRegistry.register(function ($context) {
        return $context.select('.blog-stream');
    }, function ($stream) {
        var _private = {
            lazy_load: LazyLoad($stream),

            initEventListeners: function () {
                $stream.on('click', '.blog-stream__more-button', function () {
                    $stream.addClass('blog-stream_lazy-load');
                    _private.lazy_load.watch();
                });

                $stream.on('loading@market:lazy-load', function () {
                    $stream.addClass('blog-stream_loading');
                });

                $stream.on('loaded@market:lazy-load', function (e, response) {
                    var message = market.config.language['page_number'].replace(/(.+)%page%(.+)%pages_count%/, '$1' + _private.lazy_load.getPage() + '$2' + _private.lazy_load.getCountPages());

                    $stream.removeClass('blog-stream_loading');
                    $stream.find('.blog-stream__posts-container')
                        .append(PageSeparatorBuilder.create(message))
                        .append($(response).find('.blog-posts, .posts-thumbs'));
                    $stream.find('.blog-stream__pagination-container')
                        .replaceWith($(response).find('.blog-stream__pagination-container'));

                    Update($stream);
                });

                $stream.on('error@market:lazy-load', function () {
                    $stream.removeClass('blog-stream_loading');
                });

                $stream.on('done@market:lazy-load', function () {
                    $stream.addClass('blog-stream_lazy-load-done');
                });
            }
        };

        _private.initEventListeners();
    });

    var BlogCommentsContainer = market_blog.BlogCommentsContainer = ComponentRegistry.register(function ($context) {
        return $context.select('.blog-comments-container');
    }, function ($container, self) {
        var $commentForm = $('input[name="comment_form"]').val();

        var _private = {
            initEventListeners: function () {
                $container.find('.blog-comments-container__add-comment-button-container').on('click', function () {
                    ModalUtil.openContent($commentForm, { title: market.config.language['add_comment'] });
                });

                $container.find('.blog-comments-container__more-button').on('click', function () {
                    $container.addClass('blog-comments-container_show-all');
                });
            },

            initGlobalEventListeners: function () {
                $(document).on('added@market:blog-comment-form', '.blog-comment-form', _private.handleAddComment);
            },

            destroyGlobalEventListeners: function () {
                $(document).off('added@market:blog-comment-form', '.blog-comment-form', _private.handleAddComment);
            },

            handleAddComment: function (e, data) {
                if (!data.isReply) {
                    var $comments = $container.find('.blog-comments__comments-container');
                    var $comment = $('<div class="blog-comments__item"></div>');
                    $comment.html(data.template);
                    $comments.prepend($comment);

                    Update($comment);
                }

                $container.find('.blog-comments-container__header-text').html(data.count_str);
                self.updateState();
            }
        };

        $.extend(self, {
            updateState: function () {
                $container.toggleClass('blog-comments-container_has-comments', $container.find('.blog-comments__item').length > 0);
            }
        });

        _private.initEventListeners();
        _private.initGlobalEventListeners();

        return function () {
            _private.destroyGlobalEventListeners();
        };
    });

    var BlogComments = market_blog.BlogComments = ComponentRegistry.register(function ($context) {
        return $context.select('.blog-comments');
    }, function ($comments) {
        var _private = {
            initEventListeners: function () {
                $comments.find('.blog-comments__more-button').on('click', function () {
                    $comments.addClass('blog-comments_show-all');
                });
            }
        };

        _private.initEventListeners();
    });

    var BlogComment = market_blog.BlogComment = ComponentRegistry.register(function ($context) {
        return $context.select('.blog-comment');
    }, function ($comment, self) {
        var $commentFormInput = $('input[name="comment_reply_form"]');

        var _private = {
            id: $comment.data('comment_id'),
            is_open_reply_form: false,

            initEventListeners: function () {
                $comment.children('.blog-comment__info-container').find('.blog-comment__reply-button').on('click', function () {
                    if (_private.is_open_reply_form) {
                        return;
                    }

                    var $reply_form = $($commentFormInput.val());
                    var reply_form = BlogCommentForm($reply_form);
                    reply_form.setParentId(_private.id);
                    var $comments = $comment.children('.blog-comment__comments-container');
                    var $_comment = $('<div class="blog-comment__comment-container"></div>');
                    $_comment.html($reply_form).prependTo($comments);
                    Update($_comment);
                    _private.is_open_reply_form = true;
                    self.updateState();

                    $reply_form.on('close@market:blog-comment-form', function () {
                        $_comment.remove();
                        _private.is_open_reply_form = false;
                        self.updateState();
                    });

                    $reply_form.on('added@market:blog-comment-form', function (e, data) {
                        if (data.isReply) {
                            $_comment.html(data.template);
                            $_comment.appendTo($comments);
                            _private.is_open_reply_form = false;
                            self.updateState();
                        }
                    });
                });
            }
        };

        $.extend(self, {
            updateState: function () {
                $comment.toggleClass('blog-comment_has-reply', $comment.find('.blog-comment__comment-container').length > 0);
            }
        });

        _private.initEventListeners();
    });

    var BlogCommentForm = market_blog.BlogCommentForm = ComponentRegistry.register(function ($context) {
        return $context.select('.blog-comment-form');
    }, function ($form, self) {
        var _private = {
            initEventListeners: function () {
                $form.on('success@market:ajax-form', function (e, response) {
                    if (response.status !== 'ok') {
                        return;
                    }

                    var data = response.data;
                    var isReply = data.parent !== 0;

                    data.isReply = isReply;

                    $form.trigger('added@market:blog-comment-form', data);

                    if (!isReply) {
                        ModalUtil.close();
                    }
                });

                $form.on('error@market:ajax-form', function () {
                    window.location.reload();
                });

                $form.find('.blog-comment-form__close-button').on('click', function () {
                    $form.trigger('close@market:blog-comment-form');
                });
            }
        };

        $.extend(self, {
            setParentId: function (parent_id) {
                $form.find('.blog-comment-form__parent-input').val(parent_id);
            }
        });

        _private.initEventListeners();
    });

    var BlogSidebarTimelineYear = market_blog.BlogSidebarTimelineYear = ComponentRegistry.register(function ($context) {
        return $context.select('.blog-sidebar-timeline-year');
    }, function ($year, self) {
        var _private = {
            initEventListeners: function () {
                $year.find('.blog-sidebar-timeline-year__header-container').on('click', function () {
                    self.toggle();
                });
            },

            initGlobalEventListeners: function () {
                $(document).on('open@market:blog-sidebar-timeline-year', '.blog-sidebar-timeline-year', _private.handleOpen);
            },

            destroyGlobalEventListeners: function () {
                $(document).off('open@market:blog-sidebar-timeline-year', '.blog-sidebar-timeline-year', _private.handleOpen);
            },

            handleOpen: function () {
                if ($(this).is($year)) {
                    return;
                }

                self.close();
            }
        };

        $.extend(self, {
            toggle: function () {
                var is_open = $year.hasClass('blog-sidebar-timeline-year_open');

                if (is_open) {
                    self.close();
                } else {
                    self.open();
                }
            },

            open: function () {
                var $content = $year.find('.blog-sidebar-timeline-year__content-container');
                $year.trigger('open@market:blog-sidebar-timeline-year');

                SlideDownUtil.show($content).then(function () {
                    $year.removeClass('blog-sidebar-timeline-year_process-open');
                });

                $year.removeClass('blog-sidebar-timeline-year_process-close');
                $year.offset();
                $year.addClass('blog-sidebar-timeline-year_process-open');
                $year.addClass('blog-sidebar-timeline-year_open');
            },

            close: function () {
                var $content = $year.find('.blog-sidebar-timeline-year__content-container');
                $year.trigger('close@market:blog-sidebar-timeline-year');

                SlideDownUtil.hide($content).then(function () {
                    $year.removeClass('blog-sidebar-timeline-year_process-close');
                });

                $year.removeClass('blog-sidebar-timeline-year_process-open');
                $year.offset();
                $year.addClass('blog-sidebar-timeline-year_process-close');
                $year.removeClass('blog-sidebar-timeline-year_open');
            }
        });

        _private.initEventListeners();
        _private.initGlobalEventListeners();

        return function () {
            _private.destroyGlobalEventListeners();
        };
    });

    market_blog.BlogCategorySelect = ComponentRegistry.register(function ($context) {
        return $context.find('.r-blog-category-select');
    }, function ($select) {
        var _private = {
            initEventListeners: function () {
                $select.on('change', function () {
                    window.location = $select.val();
                });
            }
        };

        _private.initEventListeners();
    });

    market_blog.BlogYearSelect = ComponentRegistry.register(function ($context) {
        return $context.find('.r-blog-year-select');
    }, function ($select) {
        var _private = {
            initEventListeners: function () {
                $select.on('change', function () {
                    window.location = $select.val();
                });
            }
        };

        _private.initEventListeners();
    });

    market_blog.BlogMonthSelect = ComponentRegistry.register(function ($context) {
        return $context.find('.r-blog-month-select');
    }, function ($select) {
        var _private = {
            initEventListeners: function () {
                $select.on('change', function () {
                    window.location = $select.val();
                });
            }
        };

        _private.initEventListeners();
    });
})(jQuery);
