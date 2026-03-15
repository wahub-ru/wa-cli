(/** @param {JQueryStatic} $ */ function ($) {
    var market = window.market;
    var config = market.config;
    var market_photos = market.photos = {};

    var ComponentRegistry = market.ComponentRegistry;

    var HistoryUtil = market.HistoryUtil;
    var PageSeparatorBuilder = market.PageSeparatorBuilder;

    var LazyLoad = market.LazyLoad;

    var PhotoPage = market_photos.PhotoPage = ComponentRegistry.register(function ($context) {
        return $context.select('.photos-photo-page');
    }, function ($page) {
        var _private = {
            initSwiper: function () {
                var $main_photos = $page.find('.photos-photo-page__main-photos');
                var $thumbs_photos = $page.find('.photos-photo-page__thumbs-photos');
                $thumbs_photos.addClass('photos-photo-page__thumbs-photos_swiper');
                var photo_offset = $main_photos.data('photo_offset');

                var thumbs_swiper = new Swiper($thumbs_photos.get(0), {
                    cssMode: true,
                    wrapperClass: 'photos-photo-page__thumbs-photos-wrapper',
                    slideClass: 'photos-photo-page__thumb-photo',
                    spaceBetween: 10,
                    slidesPerView: 4,
                    watchSlidesVisibility: true,
                    watchSlidesProgress: true,
                    navigation: {
                        prevEl: $page.find('.photos-photo-page__thumbs-arrow_prev').get(0),
                        nextEl: $page.find('.photos-photo-page__thumbs-arrow_next').get(0),
                        disabledClass: 'photos-photo-page__thumbs-arrow_disabled'
                    },
                    breakpoints: {
                        991: {
                            slidesPerView: 6
                        }
                    }
                });

                var main_photos_swiper = new Swiper($main_photos.get(0), {
                    cssMode: true,
                    wrapperClass: 'photos-photo-page__main-photos-wrapper',
                    slideClass: 'photos-photo-page__main-photo',
                    initialSlide: photo_offset,
                    thumbs: {
                        swiper: thumbs_swiper,
                        slideThumbActiveClass: 'photos-photo-page__thumb-photo_active'
                    }
                });

                main_photos_swiper.on('slideChange', function () {
                    var slide = main_photos_swiper.slides[main_photos_swiper.activeIndex];
                    var url = $(slide).data('photo_url');
                    var name = $(slide).data('photo_name');
                    $page.addClass('photos-photo-page_loading');

                    $.ajax({
                        url: url
                    }).then(function (response) {
                        HistoryUtil.replaceState({}, '', url);
                        document.title = name;
                        $page.find('.photos-photo-page__info-container').replaceWith(
                            $(response).find('.photos-photo-page__info-container'));
                        $page.removeClass('photos-photo-page_loading');
                    });
                });
            }
        };

        _private.initSwiper();
    });

    var Photos = market_photos.Photos = ComponentRegistry.register(function ($context) {
        return $context.select('.photos');
    }, function ($photos) {
        var _private = {
            lazy_load: LazyLoad($photos),

            initEventListeners: function () {
                $photos.on('click', '.photos__more-button', function () {
                    $photos.addClass('photos_lazy-load');
                    _private.lazy_load.watch();
                });

                $photos.on('loading@market:lazy-load', function () {
                    $photos.addClass('photos_loading');
                });

                $photos.on('loaded@market:lazy-load', function (e, response) {
                    var message = config.language['page_number'].replace(/(.+)%page%(.+)%pages_count%/,
                        '$1' + _private.lazy_load.getPage() + '$2' + _private.lazy_load.getCountPages());

                    $photos.removeClass('photos_loading');
                    $photos.find('.photos__photos-container')
                        .append(PageSeparatorBuilder.create(message))
                        .append($(response).find('.photos-thumbs'));
                    $photos.find('.photos__pagination-container')
                        .replaceWith($(response).find('.photos__pagination-container'));

                    market.Update($photos);
                });

                $photos.on('error@market:lazy-load', function () {
                    $photos.removeClass('photos_loading');
                });

                $photos.on('done@market:lazy-load', function () {
                    $photos.addClass('photos_lazy-load-done');
                });
            }
        };

        _private.initEventListeners();
    });
})(jQuery);
