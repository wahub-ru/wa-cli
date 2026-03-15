(/** @param {JQueryStatic} $ */ function ($) {
    var market = window.market;
    var config = market.config;
    var market_shop = market.shop = market.shop || {};

    var translate = market.translate;

    var Update = market.Update;

    var ComponentRegistry = market.ComponentRegistry;

    var ModalUtil = market.ModalUtil;
    var QueryUtil = market.QueryUtil;
    var ScrollUtil = market.ScrollUtil;
    var FlyUtil = market.FlyUtil;
    var HistoryUtil = market.HistoryUtil;
    var PluralUtil = market.PluralUtil;
    var InfoPanelUtil = market.InfoPanelUtil;
    var ResponsiveUtil = market.ResponsiveUtil;
    var TouchLockUtil = market.TouchLockUtil;
    var CookieSet = market.CookieSet;
    var MatchMedia = market.MatchMedia;
    var NumberUtil = market.NumberUtil;
    var CartUtil = market.CartUtil;
    var DebounceUtil = market.DebounceUtil;

    var LinkUtil = market.LinkUtil;
    var ViewportControl = market.ViewportControl;
    var PageSeparatorBuilder = market.PageSeparatorBuilder;
    var Counter = market.Counter;

    var CurrencyUtil = market_shop.CurrencyUtil = (function () {
        return {
            format: function (number, currency_info, without_html, unit, unit_delim) {
                var decimals = currency_info.frac_digits;
                var dec_point = currency_info.decimal_point;
                var thousands_sep = currency_info.thousands_sep;
                decimals = Math.abs(decimals);
    
                if (isNaN(decimals)) {
                    decimals = 2;
                }
    
                if (dec_point === undefined) {
                    dec_point = ',';
                }
    
                if (thousands_sep === undefined) {
                    thousands_sep = '.';
                }
    
                number = (+number || 0).toFixed(decimals);
    
                var integer_part = parseInt(number) + '';
                var integer_part_length = integer_part.length;
                var padding_length = 0;
    
                if (integer_part_length > 3) {
                    padding_length = integer_part_length % 3;
                }
    
                var result = '';
    
                if (padding_length) {
                    result += integer_part.substr(0, padding_length) + thousands_sep;
                }
    
                result += integer_part.substr(padding_length).replace(/(\d{3})(?=\d)/g, '$1' + thousands_sep);
    
                if (decimals && (number - integer_part)) {
                    result += dec_point + Math.abs(number - integer_part).toFixed(decimals).slice(2);
                }
    
                var sign;
    
                if (without_html) {
                    sign = currency_info.sign;
                } else {
                    sign = currency_info.sign_html;
    
                    if (unit !== '' && unit !== false && typeof unit !== 'undefined' && config.commons['has_premium']) {
                        var delim = config.language['for'] + ' ';
    
                        if (typeof unit_delim !== 'undefined') {
                            delim = unit_delim;
                        }
    
                        var unit_html = '<span class="unit">' + delim + unit + '</span>';
                        sign = sign + unit_html;
                    }
                }
    
                if (!currency_info.sign_position) {
                    return sign + currency_info.sign_delim + result;
                } else {
                    return result + currency_info.sign_delim + sign;
                }
            },
    
            parse: function (format_number, currency_info, without_html) {
                var decimals = currency_info.frac_digits;
                decimals = Math.abs(decimals);
    
                if (isNaN(decimals)) {
                    decimals = 2;
                }
    
                var dec_point = currency_info.decimal_point;
    
                if (dec_point === undefined) {
                    dec_point = ',';
                }
    
                var sign;
    
                if (without_html) {
                    sign = currency_info.sign;
                } else {
                    sign = currency_info.sign_html;
                }
    
                if (!currency_info.sign_position) {
                    format_number = format_number.substr(-(currency_info.sign_delim.length + sign.length));
                } else {
                    format_number = format_number.substr(0, format_number.length - (currency_info.sign_delim.length + sign.length));
                }
    
                var decimal_part = '0';
                var integer_part = format_number;
    
                if (format_number.substr(-decimals - 1, 1) === dec_point) {
                    decimal_part = format_number.substr(-decimals);
                    integer_part = format_number.substr(0, format_number.length - decimals - 1);
                }
    
                var integer_sign = 1;
    
                if (integer_part.charAt(0) === '-') {
                    integer_sign = -1;
                    integer_part = integer_part.substr(1);
                }
    
                var integer_part_length = integer_part.length;
                var padding_length = 0;
    
                if (integer_part_length > 4) {
                    padding_length = integer_part_length % 4;
                }
    
                var result = integer_part.substr(0, padding_length);
                result += integer_part.substr(padding_length === 0 ? 0 : padding_length + 1).replace(/.(?=\d\d\d)/, '');
    
                return (parseFloat(result) + parseFloat('0.' + decimal_part)) * integer_sign;
            }
        };
    })();
    
    var ProductCompare = market_shop.ProductCompare = ComponentRegistry.register(function ($context) {
        return $context.select('.product-compare');
    }, function ($button, self) {
        $.extend(self, {
            initEventListeners: function () {
                $button.on('click', function (e) {
                    e.preventDefault();
                    var $button = $(this);
                    var product_id = $button.data('product_id');
    
                    if (!product_id) {
                        console.error('product_id undefined');
    
                        return;
                    }
    
                    if ($button.hasClass('product-compare_active')) {
                        CompareSet.remove(product_id);
                    } else {
                        CompareSet.add(product_id);
                        market.Analytics.reachGoal('add_to_compare');
                    }
                });
    
                $(document).on('shop_update_compare@market:global', function () {
                    self.updateState();
                });
    
                return false;
            },
            updateState: function () {
                var product_id = $button.data('product_id');
    
                if (!product_id) {
                    console.error('product_id undefined');
    
                    return;
                }
    
                var inCompare = CompareSet.has(product_id);
                var title = config.language['to_compare'];
    
                if (inCompare) {
                    title = config.language['remove_from_compare'];
                }
    
                $button.toggleClass('product-compare_active', inCompare);
                $button.attr('title', title);
            }
        });
    
        self.initEventListeners();
        self.updateState();
    });
    
    var ProductFavorite = market_shop.ProductFavorite = ComponentRegistry.register(function ($context) {
        return $context.select('.product-favorite');
    }, function ($button, self) {
        $.extend(self, {
            initEventListeners: function () {
                $button.on('click', function (e) {
                    e.preventDefault();
                    var $button = $(this);
                    var product_id = $button.data('product_id');
    
                    if (!product_id) {
                        console.error('product_id undefined');
    
                        return;
                    }
    
                    if ($button.hasClass('product-favorite_active')) {
                        FavoriteSet.remove(product_id);
                    } else {
                        FavoriteSet.add(product_id);
                        market.Analytics.reachGoal('add_to_favorite');
                    }
                });
    
                $(document).on('shop_update_favorite@market:global', function () {
                    self.updateState();
                });
    
                return false;
            },
            updateState: function () {
                var product_id = $button.data('product_id');
    
                if (!product_id) {
                    console.error('product_id undefined');
    
                    return;
                }
    
                var inFavorite = FavoriteSet.has(product_id);
                var title = config.language['to_favorites'];
    
                if (inFavorite) {
                    title = config.language['remove_from_favorites'];
                }
    
                $button.toggleClass('product-favorite_active', inFavorite);
                $button.attr('title', title);
            }
        });
    
        self.initEventListeners();
        self.updateState();
    });
    
    var Products = market_shop.Products = ComponentRegistry.register(function ($context) {
        return $context.select('.products');
    }, function ($products, self) {
        $.extend(self, {
            page: null,
            pages_points: [],
            pages_count: null,
            products_count: null,
            current_xhr: null,
            initEventListeners: function () {
                $products.on('change', 'select.products__sorting-select', function (e) {
                    self.replaceProducts(e.target.value, 1);
                });
    
                $products.on('click', '.products-view-types__item', function () {
                    var $view_type = $(this).find('.products-view-type');
                    var type = $view_type.data('view_type');
                    $.cookie('shop_products-view-type', type, {
                        path: config.commons.wa_url
                    });
    
                    self.refreshProducts();
                });
    
                $products.on('click', '.products__pagination a.pagination-item', function (e) {
                    e.preventDefault();
    
                    var url = $(this).attr('href');
    
                    if (QueryUtil.parse(url).length) {
                        $('.category-page__description').hide();
                        $('.category-page__additional-description').hide();
                    } else {
                        $('.category-page__description').show();
                        $('.category-page__additional-description').show();
                    }
    
                    self.replaceProducts(e.target.href);
    
                    setTimeout(function () {
                        ScrollUtil.scrollToTop($products.offset().top);
                    }, 100);
                });
    
                $products.on('click', '.products__lazy-load-button', function () {
                    $products.addClass('products_lazy-load_process');
                    self.getViewportControl().watch();
                });
    
                $products.on('click', '.products__reset-filters', function () {
                    self.resetFilters();
                    $products.find('.products__filters').remove();
                });
    
                $products.on('click', '.products__filters-button', function () {
                    var $button = $(this);
    
                    var filter = {};
    
                    if ($button.data('type')) {
                        filter.type = $button.data('type');
                    }
    
                    if ($button.data('code')) {
                        filter.code = $button.data('code');
                    }
    
                    if ($button.data('value')) {
                        filter.value = $button.data('value');
                    }
    
                    $button.remove();
    
                    if ($products.find('.products__filters-button').length === 0) {
                        $products.find('.products__filters').remove();
                    }
    
                    self.disableFilter(filter);
                });
    
                $products.on('click', '.products__delete-list-button', function () {
                    self.delete();
                });
    
                $products.on('click', '.products__recovery-list-button', function () {
                    self.recovery();
                });
    
                $products.on('pre_view view', function () {
                    if (self.page === self.pages_count) {
                        $products.removeClass('products_lazy-load_process');
                        $products.addClass('products_lazy-load_done');
    
                        return;
                    }
    
                    self.getViewportControl().unwatch();
    
                    self.loadNextPage().then(function () {
                        self.getViewportControl().watch();
                    });
                });
    
                $products.on('delete', '.product', function (e) {
                    e.stopImmediatePropagation();
                    $products.trigger('delete_product', [$(this)]);
                });
    
                $products.on('recovery', '.product', function (e) {
                    e.stopImmediatePropagation();
                    $products.trigger('recovery_product', [$(this)]);
                });
            },
            resetFilters: function () {
                $('.filters').each(function () {
                    var filters = Filters($(this));
                    filters.reset();
                });
            },
            disableFilter: function (filter) {
                $('.filters').each(function () {
                    var filters = Filters($(this));
                    filters.disableFilter(filter);
                });
            },
            getViewportControl: function () {
                return ViewportControl($products.find('.products__lazy-load'));
            },
            replaceProducts: function (url, page) {
                if (page) {
                    self.page = page;
                }
    
                $products.addClass('products_loading');
                var promise = self.fetchProducts(url, page);
    
                promise.then(function (response) {
                    var $response_products = $(response).find('.products');
    
                    if ($response_products.length === 0) {
                        window.location.reload();
    
                        return;
                    }
    
                    self.page = $response_products.data('page');
                    self.pages_count = $response_products.data('pages_count');
                    self.products_count = $response_products.data('products_count');
                    self.pages_points = [];
    
                    $(window).off('scroll.pages');
    
                    var $contentBlock = $products.find('.products__main');
                    var $newContent = $response_products.find('.products__main').contents();
                    var $excludeAjaxBlocks = $contentBlock.find('[data-exclude-ajax]');
    
                    if ($excludeAjaxBlocks.length > 0) {
                        $excludeAjaxBlocks.each(function () {
                            var $block = $(this);
                            var blockId = $block.data('exclude-ajax');
                            var $newBlock = $newContent.find('[data-exclude-ajax=' + blockId + ']');
    
                            $newBlock.replaceWith($block);
                        });
                    }
    
                    $contentBlock.html($newContent);
                    $products.removeClass('products_lazy-load_process');
                    $products.removeClass('products_lazy-load_done');
    
                    Update($products);
                    $(document).trigger('products_updated@market:global');
    
                    self.updateProductsCount();
    
                    $products.removeClass('products_loading');
                }, function (xhr, status) {
                    if (status === 'abort') {
                        return;
                    }
    
                    $products.removeClass('products_loading');
                });
    
                HistoryUtil.replaceState({}, '', url);
    
                return promise;
            },
            updateProductsCount: function () {
                $('.filters .filters__submit-button').attr('data-products-count', self.products_count);
            },
            loadNextPage: function () {
                self.page++;
                var page = self.page;
                var pages_count = self.pages_count;
                var url = window.location.toString();
                var promise = self.fetchProducts(url, page);
    
                var params = QueryUtil.parse(url);
                var firstPageParams = params;
    
                if (page) {
                    params = params.filter(function (param) {
                        return param.name !== 'page';
                    });
    
                    if (page > 1) {
                        params.push({
                            name: 'page',
                            value: page
                        });
                    }
                }
    
                var anchor = document.createElement('a');
                anchor.href = url;
                anchor.search = QueryUtil.serialize(params);
    
                if (page === 2) {
                    var firstPageAnchor = document.createElement('a');
                    firstPageAnchor.href = url;
                    firstPageAnchor.search = QueryUtil.serialize(firstPageParams);
    
                    self.pages_points.push({
                        url: url,
                        coords: 0
                    });
    
                    self.initPagesPoints();
                }
    
                promise.then(function (response) {
                    var $response_products = $(response).find('.products');
    
                    if ($response_products.length === 0) {
                        window.location.reload();
    
                        return;
                    }
    
                    HistoryUtil.replaceState({}, '', anchor);
    
                    var message = config.language['page_number'].replace(/(.+)%page%(.+)%pages_count%/, '$1' + page + '$2' + pages_count);
                    var pageSeparator = PageSeparatorBuilder.create(message);
                    $products.find('.products__list').append(pageSeparator);
    
                    pageSeparator.attr('data-page-url', anchor.href);
    
                    self.pages_points.push({
                        url: pageSeparator.data('page-url'),
                        coords: pageSeparator.offset().top
                    });
    
                    $products.find('.products__list').append($response_products.find('.products__list').contents());
                    $products.find('.products__pagination').html($response_products.find('.products__pagination').contents());
    
                    Update($products);
                });
    
                return promise;
            },
            refreshProducts: function () {
                self.replaceProducts(window.location);
            },
            fetchProducts: function (url, page) {
                if (self.current_xhr) {
                    self.current_xhr.abort();
                    self.current_xhr = null;
                }
    
                var params = QueryUtil.parse(url);
                params.push({
                    name: '_',
                    value: Date.now()
                });
    
                if (page) {
                    params = params.filter(function (param) {
                        return param.name !== 'page';
                    });
    
                    if (page > 1) {
                        params.push({
                            name: 'page',
                            value: page
                        });
                    }
                }
    
                var anchor = document.createElement('a');
                anchor.href = url;
                anchor.search = QueryUtil.serialize(params);
    
                return $.ajax({
                    url: anchor.href,
                    method: 'get',
                    beforeSend: function (xhr) {
                        self.current_xhr = xhr;
                    },
                    complete: function () {
                        self.current_xhr = null;
                    }
                });
            },
            getProducts: function () {
                return $products.find('.product');
            },
            delete: function () {
                self.getProducts().each(function () {
                    var product = Product($(this));
                    product.delete();
                });
    
                $products.trigger('delete');
                $products.addClass('products_delete');
            },
            recovery: function () {
                self.getProducts().each(function () {
                    var product = Product($(this));
                    product.recovery();
                });
    
                $products.trigger('recovery');
                $products.removeClass('products_delete');
            },
            setMinHeight: function () {
                var productsList = $products.find('.products__list');
                var productCardHeight = productsList.find('.product:first').outerHeight();
                productsList.css('min-height', productCardHeight + 'px');
            },
            initPagesPoints: function () {
                var $window = $(window);
                var prevScrollTop = 0;
                $window.on('scroll.pages', function () {
                    var height = $window.height();
                    var scrollTop = $window.scrollTop() + height / 2;
                    var direction = scrollTop > prevScrollTop ? 'down' : 'up';
    
                    var url = null;
                    $(self.pages_points).each(function (i, point) {
                        var pointUrl = point['url'];
                        var pointCoords = point['coords'];
    
                        if (direction === 'down' && scrollTop > pointCoords) {
                            url = pointUrl;
                        } else if (direction === 'up' && scrollTop < pointCoords) {
                            var previousPoint = self.pages_points[Math.max(i - 1, 0)];
                            url = previousPoint['url'];
    
                            return false;
                        }
                    });
    
                    if (url && url !== window.location.href) {
                        HistoryUtil.replaceState({}, '', url);
                    }
    
                    prevScrollTop = scrollTop;
                });
            }
        });
    
        self.page = +$products.data('page');
        self.pages_count = +$products.data('pages_count');
        self.products_count = +$products.data('products_count');
        self.setMinHeight();
        self.initEventListeners();
    });
    
    var ProductPage = market_shop.ProductPage = ComponentRegistry.register(function ($context) {
        return $context.select('.product-page');
    }, function ($product, self) {
        market.RecentlySet.touch($product.data('product_id'));
    });
    
    var Filters = market_shop.Filters = ComponentRegistry.register(function ($context) {
        return $context.select('.filters');
    }, function ($filters, self) {
        $.extend(self, {
            timeout_id: null,
            hint_timeout_id: null,
            $hint_target: $(),
            initEventListeners: function () {
                self.getForm().on('submit', function (e) {
                    e.preventDefault();
    
                    clearTimeout(self.timeout_id);
                    self.updateProducts();
                });
    
                self.getForm().on('change', function (e) {
                    e.preventDefault();
    
                    self.$hint_target = $(e.target);
                    clearTimeout(self.timeout_id);
                    self.timeout_id = setTimeout(function () {
                        self.updateProducts();
                    }, 500);
                });
    
                $filters.find('.filters__submit-button').on('click', function () {
                    self.getForm().trigger('submit');
                    $(document).trigger('filters_header@market:global');
    
                    return false;
                });
                $filters.find('.filters__reset-button').on('click', function () {
                    self.reset();
                });
            },
            setHint: function (text) {
                $filters.find('.filters__hint').each(function () {
                    var $hint = $(this);
                    $hint.text(text);
                    var $height_element = self.$hint_target.closest('.filter__value, .filters__footer');
    
                    if ($height_element.length === 0) {
                        $height_element = self.$hint_target;
                    }
    
                    self.$hint_target = $();
    
                    if (!$height_element.is(':visible')) {
                        return;
                    }
    
                    $hint.css('top', ($height_element.offset()['top'] - $filters.offset()['top'] + $height_element.height() / 2) + 'px');
    
                    if (self.isHorizontal()) {
                        $hint.css({
                            left: ($height_element.offset()['left'] + $height_element.width() - $filters.offset()['left']) + 'px'
                        });
                    }
    
                    $hint.get(0).offsetTop;
                    $hint.addClass('filters__hint_show');
    
                    clearTimeout(self.hint_timeout_id);
                    self.hint_timeout_id = setTimeout(function () {
                        $hint.removeClass('filters__hint_show');
                    }, 2000);
                });
            },
            getForm: function () {
                return $filters.find('.filters__form');
            },
            updateProducts: function () {
                var $form = self.getForm();
    
                $('.products').each(function () {
                    var products = Products($(this));
    
                    if (products) {
                        products.replaceProducts('?' + $form.serialize(), 1).then(function (response) {
                            var text = PluralUtil.plural(products.products_count, [
                                'Найден ' + products.products_count + ' товар',
                                'Найдено ' + products.products_count + ' товара',
                                'Найдено ' + products.products_count + ' товаров'
                            ]);
    
                            self.setHint(text);
    
                            $(document).trigger('filters_filtration_complete@market:global', [$(response)]);
                        });
                    }
                });
            },
            reset: function () {
                $filters.find('.filters__filter').each(function () {
                    var filter = Filter($(this), self);
                    filter.reset();
                });
                self.getForm().trigger('submit');
            },
            disableFilter: function (filter) {
                var $form = self.getForm();
                var form = $form.get(0);
    
                if (filter.type === 'checkbox') {
                    $(form.elements[filter.code + '[]']).filter('[value="' + filter.value + '"]')
                        .prop('checked', false)
                        .trigger('change');
                } else if (filter.type === 'radio') {
                    $(form.elements[filter.code]).filter('[value=""]')
                        .prop('checked', true)
                        .trigger('change');
                } else if (filter.type === 'range') {
                    $(form.elements[filter.code + '[min]']).val('')
                        .trigger('change');
                    $(form.elements[filter.code + '[max]']).val('')
                        .trigger('change');
                } else if (filter.type === 'price') {
                    $(form.elements['price_min']).val('')
                        .trigger('change');
                    $(form.elements['price_max']).val('')
                        .trigger('change');
                }
            },
    
            isHorizontal: function () {
                return $filters.hasClass('filters_horizontal');
            }
        });
    
        self.initEventListeners();
    });
    
    var Filter = market_shop.Filter = ComponentRegistry.register(function ($context) {
        return $context.select('.filter');
    }, function ($filter, self) {
        $.extend(self, {
            initEventListeners: function () {
                if (self.isCheckboxType() || self.isColorType()) {
                    $filter.on('change', '.filter__values', function (e) {
                        var $checkbox = $(e.target);
                        var $value = $checkbox.closest('.filter__value');
    
                        $value.toggleClass('filter__value_selected', $checkbox.is(':checked'));
                        self.setSelectedCount();
                    });
                }
    
                $filter.on('change', '.radio__control', function () {
                    self.setSelectedCount();
                });
    
                $filter.on('change', '.range-slider__input', function () {
                    self.setSelectedCount();
                });
    
                $filter.find('.filter__search').each(function () {
                    const $searchBlock = $(this);
                    const $searchField = $searchBlock.find('.filter__search-field');
                    const $clearSearch = $searchBlock.find('.filter__clear-search');
                    const $filterBlock = $searchBlock.closest('.filter');
                    const $filterValuesBlock = $filterBlock.find('.filter__values');
                    const $filterValues = $filterValuesBlock.find('.filter__value');
    
                    $searchField.on('input change', function (e) {
                        e.stopPropagation();
                        const value = this.value.toLowerCase();
                        $filterValuesBlock.scrollTop = 0;
                        $clearSearch.toggleClass('filter__clear-search_active', value.length > 0);
    
                        $filterValues.each(function () {
                            const $filterValue = $(this);
                            const isSearchedValue = $filterValue.text().toLowerCase().includes(value);
                            $filterValue.toggleClass('filter__value_hidden', !isSearchedValue);
                        });
                    });
    
                    $clearSearch.on('click', function () {
                        $searchField.val('').trigger('change');
                    });
                });
    
                $(document).on('filters_filtration_complete@market:global', function () {
                    self.setActiveValues();
                });
            },
            isCheckboxType: function () {
                return $filter.is('.filter_type_checkbox');
            },
            isColorType: function () {
                return $filter.is('.filter_type_color-checkbox');
            },
            isBooleanType: function () {
                return $filter.is('.filter_type_boolean');
            },
            isRangeType: function () {
                return $filter.is('.filter_type_range') || $filter.is('.filter_type_price');
            },
            disableValue: function ($value) {
                $value.addClass('filter__value_disabled');
    
                var $control = $value.find('.checkbox__control');
                $control.prop('disabled', true);
            },
            enableValue: function ($value) {
                $value.removeClass('filter__value_disabled');
    
                var $control = $value.find('.checkbox__control');
                $control.prop('disabled', false);
            },
            getValues: function () {
                return $filter.find('.filter__value');
            },
            getSelectedValues: function () {
                return self.getValues().filter('.filter__value_selected');
            },
            isSelected: function () {
                var isSelected = false;
    
                if (self.isBooleanType()) {
                    isSelected = !$filter.find('.filter__reset-radio_default').is(':checked');
                } else if (self.isRangeType()) {
                    $filter.find('.range-slider__input').each(function () {
                        var input = $(this);
    
                        if (input.val() !== '' && isSelected === false) {
                            isSelected = true;
                        }
                    });
                }
    
                return isSelected;
            },
            getHiddenValues: function () {
                var $hidden_values = $();
                var $values = self.getValues();
                var limit = $filter.data('values_limit');
                limit = Math.max(0, limit - self.getSelectedValues().length);
    
                $values.each(function () {
                    var $value = $(this);
                    var is_selected = $value.is('.filter__value_selected');
    
                    if (!is_selected) {
                        if (limit === 0) {
                            $hidden_values = $hidden_values.add($value);
                        } else {
                            limit = limit - 1;
                        }
                    }
                });
    
                return $hidden_values;
            },
            setActiveValues: function () {
                self.getValues().each(function () {
                    var $value = $(this);
                    var isSelected = $value.hasClass('filter__value_selected');
                    var isColor = $value.hasClass('filter__value_color');
    
                    $value.toggleClass('filter__value_active', isSelected && !isColor);
                });
            },
            reset: function () {
                $filter.find('.filter__reset-input').each(function () {
                    var $input = $(this);
    
                    if ($input.val() !== '') {
                        $input.val('').trigger('change');
                    }
                });
                $filter.find('.filter__reset-checkbox').each(function () {
                    var $checkbox = $(this);
    
                    if ($checkbox.prop('checked')) {
                        $checkbox.prop('checked', false).trigger('change');
                    }
                });
                $filter.find('.filter__reset-radio').each(function () {
                    var $radio = $(this);
    
                    if ($radio.is('.filter__reset-radio_default') && !$radio.prop('checked')) {
                        $radio.prop('checked', true).trigger('change');
                    }
                });
    
                self.setSelectedCount();
            },
            isHorizontal: function () {
                return $filter.hasClass('filter_horizontal');
            },
            selectedCount: function () {
                var count = 0;
    
                if (self.isCheckboxType() || self.isColorType()) {
                    count = self.getSelectedValues().length;
                } else {
                    if (self.isSelected()) {
                        count = 1;
                    } else {
                        count = 0;
                    }
                }
    
                return count;
            },
            setSelectedCount: function () {
                if (self.isHorizontal()) {
                    var filtersBlock = $filter.closest('.filters__filters');
                    var countBlock = $filter.find('.filter__count');
    
                    if (self.selectedCount() > 0) {
                        $filter.addClass('filter_selected');
                        countBlock.text(self.selectedCount());
                    } else {
                        $filter.removeClass('filter_selected');
                        countBlock.text('');
                    }
    
                    var hasSelectedFilters = filtersBlock.find('.filter_selected').length > 0;
    
                    filtersBlock.toggleClass('filters__filters_selected', hasSelectedFilters);
                }
            }
        });
    
        self.setSelectedCount();
        self.initEventListeners();
        $filter.data('market_shop_filter', self);
    });
    
    var CategoryPage = market_shop.CategoryPage = ComponentRegistry.register(function ($context) {
        return $context.find('.category-page');
    }, function ($category_page, self) {
        var _private = {
            findSubcategoriesWrapper: function ($element) {
                return $element.find('.category-page__subcategories-wrapper');
            }
        };
    
        $.extend(self, {
            initEventListeners: function () {
                $(document).on('filters_filtration_complete@market:global', function (event, $response) {
                    var $subcategories_wrapper = self.getSubcategoriesWrapper();
    
                    if ($subcategories_wrapper.length > 0) {
                        var $response_subcategories_wrapper = _private.findSubcategoriesWrapper($response);
    
                        $subcategories_wrapper.html($response_subcategories_wrapper.html());
                        Update($subcategories_wrapper);
                    }
                });
                $(document).on('click', '.description-block__more-btn', function () {
                    var $descriptionBlock = $(this).closest('.description-block');
                    var $descriptionContentBlock = $descriptionBlock.find('.description-block__content-wrapper');
                    self.showFullDescription($descriptionContentBlock);
                    $(this).find('span').toggle();
                });
            },
            getSubcategoriesWrapper: function () {
                return _private.findSubcategoriesWrapper($category_page);
            },
            showFullDescription: function ($descriptionContentBlock) {
                $descriptionContentBlock.toggleClass('description-block__content-wrapper--show');
            },
            checkDescriptionsHeight: function ($element) {
                var $descriptionBlocks = $element.find('.description-block');
                $descriptionBlocks.each(function () {
                    var $descriptionWrapper = $(this).find('.description-block__content-wrapper');
                    var $descriptionMore = $(this).find('.description-block__more');
                    var $blockHeight = $descriptionWrapper.height();
                    var $descriptionContent = $descriptionWrapper.find('> *');
    
                    var $contentHeight = 0;
    
                    $descriptionContent.each(function () {
                        $contentHeight = $contentHeight + $(this).height();
                    });
    
                    if ($contentHeight <= $blockHeight && $descriptionMore !== undefined) {
                        $descriptionMore.hide();
                    } else {
                        $descriptionMore.show();
                    }
                });
            }
        });
    
        self.initEventListeners();
        self.checkDescriptionsHeight($category_page);
    });
    
    var BrandPage = market_shop.BrandPage = ComponentRegistry.register(function ($context) {
        return $context.find('.brand-page');
    }, function ($brand_page, self) {
        var _private = {
            findCategoriesWrapper: function ($element) {
                return $element.find('.brand-page__categories-container-wrapper');
            }
        };
    
        $.extend(self, {
            initEventListeners: function () {
                $(document).on('filters_filtration_complete@market:global', function (event, $response) {
                    var $categories_wrapper = self.getCategoriesWrapper();
    
                    if ($categories_wrapper.length > 0) {
                        var $response_categories_wrapper = _private.findCategoriesWrapper($response);
    
                        $categories_wrapper.html($response_categories_wrapper.html());
                    }
                });
            },
            getCategoriesWrapper: function () {
                return _private.findCategoriesWrapper($brand_page);
            }
        });
    
        self.initEventListeners();
    });
    
    var ReviewImages = market_shop.ProductImages = ComponentRegistry.register(function ($context) {
        return $context.select('.review-images');
    }, function ($review_images, self) {
        var is_tabs = $review_images.closest('.content-tabs').length > 0 || $review_images.closest('.responsive-tabs').length > 0;
        var $slider = $review_images.find('.review-images__slider');
        var has_slider = $slider.length > 0;
        $.extend(self, {
            initSwiper: function () {
                if (has_slider) {
                    new Swiper($slider.get(0), {
                        cssMode: true,
                        spaceBetween: 10,
                        slidesPerView: 'auto',
                        watchSlidesVisibility: true,
                        watchSlidesProgress: true,
                        direction: 'horizontal',
                        navigation: {
                            prevEl: $review_images.find('.review-images__arrow_prev').get(0),
                            nextEl: $review_images.find('.review-images__arrow_next').get(0),
                            disabledClass: 'review-images__arrow_disabled'
                        }
                    });
                }
            },
            observeReviewsBlock: function () {
                if (is_tabs && has_slider) {
                    var reviewsTabs = $('.content-tabs__content[data-slug="reviews"], .responsive-tabs__tab-container[data-slug="reviews"]');
                    reviewsTabs.each(function () {
                        var reviewsTab = $(this);
                        var reviewTabClass = reviewsTab.attr('class').split(' ')[0];
                        var reviewTabSelectedClass = reviewTabClass + '_selected';
    
                        if (reviewsTab.hasClass(reviewTabSelectedClass)) {
                            self.initSwiper();
                        } else {
                            market.ObserverUtil.observeClass(reviewTabClass, function () {
                                if (reviewsTab.hasClass(reviewTabSelectedClass)) {
                                    self.initSwiper();
                                }
                            });
                        }
                    });
                } else {
                    self.initSwiper();
                }
            }
        });
    
        self.observeReviewsBlock();
    });
    
    var ProductImages = market_shop.ProductImages = ComponentRegistry.register(function ($context) {
        return $context.select('.product-images');
    }, function ($product_images, self) {
        $.extend(self, {
            initSwiper: function () {
                var initial_slide = 0;
                var selectedImage = $product_images.find('.product-images__image_sku-selected');
    
                if (selectedImage.length > 0) {
                    initial_slide = selectedImage.data('index');
                } else if ($product_images.hasClass('product-images_has-video')) {
                    initial_slide = 1;
                }
    
                var $thumbs = $product_images.find('.product-images__thumbs');
                var thumbs_swiper = null;
    
                if ($thumbs.length !== 0) {
                    $thumbs.addClass('product-images__thumbs_swiper-init');
                    var direction = 'horizontal';
    
                    if ($product_images.hasClass('product-images_thumbs-position_left')) {
                        direction = 'vertical';
                    }
    
                    var actualSize = 0;
                    var thumbsElements = $thumbs.find('.product-images__thumb');
    
                    thumbsElements.each(function () {
                        var item = $(this);
                        actualSize = actualSize + item.width() + 10;
                    });
    
                    thumbs_swiper = new Swiper($thumbs.get(0), {
                        cssMode: true,
                        slidesPerView: 'auto',
                        watchSlidesVisibility: true,
                        watchSlidesProgress: true,
                        direction: direction,
                        navigation: {
                            prevEl: $product_images.find('.product-images__arrow_prev').get(0),
                            nextEl: $product_images.find('.product-images__arrow_next').get(0),
                            disabledClass: 'product-images__arrow_disabled'
                        }
                    });
    
                    $product_images.find('.product-images__image_video').removeClass('product-images__image_hide');
    
                    var $thumbs_swiper_wrapper = $(thumbs_swiper.$wrapperEl);
    
                    if (actualSize < $thumbs_swiper_wrapper.width()) {
                        $thumbs_swiper_wrapper.css({
                            width: actualSize - 10,
                            margin: '0 auto'
                        });
                    }
                }
    
                var $images = $product_images.find('.product-images__images');
                $images.addClass('product-images__images_swiper-init');
    
                var images_swiper = new Swiper($images.get(0), {
                    cssMode: true,
                    initialSlide: initial_slide,
                    spaceBetween: 10,
                    thumbs: {
                        swiper: thumbs_swiper
                    }
                });
    
                $images.find('.product-images__image').on('click', function (e) {
                    e.preventDefault();
    
                    if ($(this).data('prevent-open-gallery')) {
                        return false;
                    }
    
                    var form = ProductCartForm($product_images.closest('.product-cart-form'));
                    var product_data = form.productData;
                    var $gallery = $(product_data.gallary);
                    $gallery.data('initial_slide', images_swiper.realIndex);
                    self.pauseVideo();
                    ModalUtil.openContent($gallery, {
                        classes: 'product-gallery-modal',
                        contentClasses: 'product-gallery-modal__content',
                        title: ResponsiveUtil.isTabletMax() ? $product_images.data('product-name') : ''
                    });
                });
    
                images_swiper.on('slideChange', function () {
                    var swiper = this;
                    var currentSlide = $(swiper.slides[swiper.activeIndex]);
                    var isVideoSlide = $(currentSlide).hasClass('.product-images__image_video');
    
                    if (!isVideoSlide) {
                        self.pauseVideo();
                    }
                });
            },
            initZoom: function () {
                var $images = $product_images.find('.product-images__images');
                var $zoom_container = $product_images.find('.product-images__zoom-container');
                var image_selector = '.product-images__image:not([data-no-zoom])';
    
                var initImageZoom = function ($img) {
                    $img.on('mouseover', function () {
                        if ($img.hasClass('lazy-image') && !$img.hasClass('lazy-image_ready') && !$img.hasClass('lazy-image_no-delay')) {
                            return;
                        }
    
                        $img.zoom({
                            $container: $zoom_container
                        });
                        $img.off('mouseover');
                    });
                };
    
                $images.find(image_selector).each(function () {
                    var $image = $(this);
    
                    var $img = $image.find('img');
    
                    if ($img.length) {
                        initImageZoom($img);
                    }
                });
    
                $(document).on('lazy_image_completed@market:global', function (e, $img) {
                    if (!$img.closest($images).length) {
                        return;
                    }
    
                    if (!$img.closest(image_selector).length) {
                        return;
                    }
    
                    initImageZoom($img);
                });
            },
            selectImage: function (image_id) {
                var $images = $product_images.find('.product-images__images');
                var images_swiper = $images.get(0).swiper;
    
                if (!images_swiper) {
                    return;
                }
    
                $(images_swiper.slides).each(function (i) {
                    if (image_id == $(this).data('image_id')) {
                        $images.get(0).swiper.slideTo(i);
                    }
                });
            },
            pauseVideo: function () {
                $product_images.find('.product-images__video').trigger('pause');
            }
        });
    
        self.initSwiper();
    
        if (ResponsiveUtil.isDesktopMin() && $product_images.data('is_enabled_zoom')) {
            self.initZoom();
        }
    });
    
    var ProductGallery = market_shop.ProductGallery = ComponentRegistry.register(function ($context) {
        return $context.select('.product-gallery');
    }, function ($product_gallery, self) {
        var _private = {
            initArrived: function () {
                var $arrived = $product_gallery.find('.product-gallery__arrived .plugin_arrived-button a');
    
                $product_gallery.find('.product-gallery__arrived-button').on('click', function () {
                    $arrived.trigger('click');
    
                    $('.plugin_arrived-overlay').remove();
                    var $popup = $('.plugin_arrived-popup');
                    _private.initPopup($popup);
                });
            },
    
            initPopup: function ($popup) {
                var $decorator = $('<div class="arrived-decorator"></div>');
                $decorator.append($popup);
                ModalUtil.openContent($decorator);
            }
        };
    
        $.extend(self, {
            initEventListeners: function () {
                var in_process = false;
    
                $product_gallery.on('click', '.product-gallery__add-to-cart-button', function (e) {
                    e.preventDefault();
    
                    if ($(this).hasClass('button_disabled')) {
                        return false;
                    }
    
                    $product_gallery.find('.product-gallery__add-to-cart-form').trigger('submit');
                });
    
                $product_gallery.on('submit', '.product-gallery__add-to-cart-form', function (e) {
                    e.preventDefault();
    
                    if ($product_gallery.hasClass('product-gallery_has-multi-skus')) {
                        var url = $product_gallery.data('url');
                        ModalUtil.openAjax(url + '?modal=1', function (data) {
                            return $(data).find('.product-cart-form').get(0);
                        });
    
                        return;
                    }
    
                    if (in_process) {
                        return;
                    }
    
                    in_process = true;
                    var $form = $(this);
                    var data = $form.serializeArray();
                    data.push({ name: 'html', value: true });
    
                    CartUtil.addByData(data).then(function (response) {
                        in_process = false;
                        $(document).trigger('shop_cart_add_product@market:global', arguments);
    
                        var effect = config.shop.add_to_cart_effect;
    
                        if (effect !== 'fly') {
                            return;
                        }
    
                        var $counter = $('.cart-counter:visible:first');
    
                        if ($counter.length !== 1) {
                            return;
                        }
    
                        var $image = $product_gallery.find('.product-gallery__image.swiper-slide-active');
                        FlyUtil.flyImage($image, $counter);
                    }, function () {
                        in_process = false;
                    });
                });
            },
            initSwiper: function () {
                var initial_slide = $product_gallery.data('initial_slide') || 0;
    
                var $thumbs = $product_gallery.find('.product-gallery__thumbs');
                var thumbs_swiper = null;
    
                if ($thumbs.length !== 0) {
                    $thumbs.addClass('product-gallery__thumbs_swiper-init');
    
                    thumbs_swiper = new Swiper($thumbs.get(0), {
                        slidesPerView: 'auto',
                        freeMode: true,
                        watchSlidesVisibility: true,
                        watchSlidesProgress: true,
                        direction: 'vertical',
                        mousewheel: true,
                        navigation: {
                            prevEl: $product_gallery.find('.product-gallery__thumb-arrow_prev').get(0),
                            nextEl: $product_gallery.find('.product-gallery__thumb-arrow_next').get(0),
                            disabledClass: 'product-gallery__thumb-arrow_disabled'
                        }
                    });
                }
    
                var $images = $product_gallery.find('.product-gallery__images');
                $images.addClass('product-gallery__images_swiper-init');
    
                var thumbs = {};
    
                if (ResponsiveUtil.isDesktopMin()) {
                    thumbs = {
                        swiper: thumbs_swiper
                    };
                }
    
                new Swiper($images.get(0), {
                    cssMode: true,
                    initialSlide: initial_slide,
                    thumbs: thumbs,
                    navigation: {
                        prevEl: $product_gallery.find('.product-gallery__arrow_prev').get(0),
                        nextEl: $product_gallery.find('.product-gallery__arrow_next').get(0),
                        disabledClass: 'product-gallery__arrow_disabled'
                    },
                    pagination: {
                        el: '.gallery-pagination',
                        bulletClass: 'gallery-pagination__bullet',
                        bulletActiveClass: 'gallery-pagination__bullet_active',
                        clickable: true
                    }
                });
            }
        });
    
        _private.initArrived();
    
        self.initSwiper();
        self.initEventListeners();
    });
    
    var ProductCartForm = market_shop.ProductCartForm = ComponentRegistry.register(function ($context) {
        return $context.select('.product-cart-form');
    }, function ($product_cart_form, self) {
        var _private = {
            product_url: $product_cart_form.data('product_url'),
    
            initGlobalEventListeners: function () {
                $(document).on('shop-dp-city-select-save', _private.handleDpSelect);
            },
    
            destroyGlobalEventListeners: function () {
                $(document).off('shop-dp-city-select-save', _private.handleDpSelect);
            },
    
            handleDpSelect: function () {
                var $container = $product_cart_form.select('.product-cart-form__dp-container');
                $container.addClass('product-cart-form__dp-container_loading');
    
                $.ajax({
                    url: _private.product_url
                }).done(function (response) {
                    $container.replaceWith($(response).select('.product-cart-form__dp-container'));
                    window.shop_dp.loader.assets.product.initAsyncCalculation(); /* TODO: Проверить правильность фикса и не костыльность */
                });
                window.shop_dp_dialog('close');
                $('#dp-dialog-city-select').remove();
    
                throw new Error();
            }
        };
    
        $.extend(self, {
            productData: null,
            initData: function () {
                var $dataInput = $product_cart_form.find('[name="product_data"]');
                self.productData = JSON.parse($dataInput.val());
                $dataInput.remove();
            },
            initEventListeners: function () {
                $product_cart_form.on('submit', function (e) {
                    e.preventDefault();
    
                    if ($product_cart_form.hasClass('product-cart-form_added')) {
                        window.location = config.shop.real_cart_url;
    
                        return;
                    }
    
                    var data = $product_cart_form.serializeArray();
                    var addedQuantity = null;
                    $(data).each(function () {
                        var entry = this;
    
                        if (entry.name === 'quantity') {
                            addedQuantity = NumberUtil.formatNumber(entry.value);
                        }
                    });
                    data.push({ name: 'html', value: true });
    
                    var sku_data = self.getSkuData();
                    var sku_max_count = sku_data['max_count'];
                    var max_count_is_null = sku_max_count === null;
    
                    if (!max_count_is_null && addedQuantity > sku_max_count) {
                        var message = config.language['message_max_count'].replace(/(.+)%sku_count%(.+)%sku_name%(.+)%max_sku_quantity%/, '$1' + sku_data['count'] + '$2' + sku_data['name'] + '$3' + sku_max_count);
    
                        if (sku_max_count <= 0) {
                            message = config.language['message_max_count_in_cart'].replace(/(.+)%sku_count%(.+)%sku_name%/, '$1' + sku_data['count'] + '$2' + sku_data['name']);
                        }
    
                        InfoPanelUtil.showMessage(message);
    
                        return false;
                    }
    
                    CartUtil.addByData(data).then(function (response) {
                        $(document).trigger('shop_cart_add_product@market:global', arguments);
    
                        if (response.status !== 'ok') {
                            return;
                        }
    
                        sku_data['is_in_cart'] = true;
                        sku_data['in_cart_quantity'] = sku_data['in_cart_quantity'] + addedQuantity;
    
                        if (!max_count_is_null) {
                            sku_data['max_count'] = sku_max_count - addedQuantity;
                        }
    
                        if (config.shop.to_toggle_cart_button) {
                            self.updateButton();
                            self.updateSku();
                        }
    
                        var effect = config.shop.add_to_cart_effect;
    
                        if (effect !== 'fly') {
                            return;
                        }
    
                        var $flyTarget;
                        var $counter = $('.cart-counter:visible:first');
                        var $orderPageFlyTarget = $('.order-page .header_h2');
    
                        if ($counter.length) {
                            $flyTarget = $counter.eq(0);
                        } else if ($orderPageFlyTarget.length) {
                            $flyTarget = $orderPageFlyTarget.eq(0);
                        }
    
                        if (!$flyTarget) {
                            return;
                        }
    
                        var $image = $product_cart_form.find('.product-images__image.swiper-slide-active');
                        FlyUtil.flyImage($image, $flyTarget, {
                            zIndex: 100
                        });
                    });
                });
    
                $product_cart_form.on('change', '.quantity', function (e) {
                    e.stopPropagation();
                    self.updateFullPrice();
                });
    
                $product_cart_form.on('change', function () {
                    self.updateSku();
                    self.updateServices();
                    self.updateAjaxBlocks();
                    self.updatePrices();
                    self.updateImage();
                    self.updateButton();
                });
            },
            updateServices: function () {
                var services_data = self.getServicesData();
                var product_data = self.productData;
    
                if (!product_data || !services_data) {
                    return;
                }
    
                $product_cart_form.find('.service').each(function () {
                    var $service = $(this);
                    var service = Service($service);
                    var service_id = service.getServiceId();
                    var service_data = services_data[service_id];
                    var has_variants = typeof service_data === 'object';
                    var is_disabled = service_data === false;
    
                    if (service.isDisabled()) {
                        if (!is_disabled) {
                            service.enable();
                        }
                    }
    
                    if (has_variants) {
                        service.updateVariants(service_data, product_data.currency);
                    } else if (is_disabled) {
                        service.disable();
                    } else {
                        service.updatePrice(service_data, product_data.currency);
                    }
                });
            },
            updateAjaxBlocks: function () {
                var sku_data = self.getSkuData();
                var ajaxBlocks = {
                    $stocksHtml: '.product-skus-stocks',
                    $deliveryDateHtml: '.delivery-date'
                };
    
                var promise;
    
                if (sku_data && !sku_data.$ajaxBlocks) {
                    var sku_id = sku_data.id;
                    let url = new URL(_private.product_url, window.location);
                    url.search = '?cart=1&sku=' + sku_id;
    
                    $product_cart_form.addClass('product-cart-form_ajax-loading');
    
                    sku_data.$ajaxBlocks = {};
    
                    promise = $.get(url.toString()).then((html) => {
                        $.each(ajaxBlocks, function (key, selector) {
                            var $newAjaxBlock = $(html).find(selector);
    
                            if ($newAjaxBlock.length > 0) {
                                sku_data.$ajaxBlocks[key] = $newAjaxBlock.clone();
                            }
                        });
                    }).always(function () {
                        $product_cart_form.removeClass('product-cart-form_ajax-loading');
                    });
                } else {
                    promise = $.Deferred().resolve();
                }
    
                promise.then(function () {
                    self.updateSkuStocks(sku_data);
                    self.updateDeliveryDate(sku_data);
                });
            },
            updateSkuStocks: function (sku_data) {
                var $skus_stocks = $product_cart_form.find('.product-skus-stocks');
    
                if ($skus_stocks.length === 0) {
                    return;
                }
    
                if (!sku_data) {
                    let text = $skus_stocks.data('no-sku-text');
                    $skus_stocks.html(text);
    
                    return;
                }
    
                $skus_stocks.replace(sku_data.$ajaxBlocks.$stocksHtml.clone());
            },
            updateDeliveryDate: function (sku_data) {
                var $delivery_date = $product_cart_form.find('.delivery-date');
    
                if ($delivery_date.length === 0 || !sku_data) {
                    return;
                }
    
                $delivery_date.replace(sku_data.$ajaxBlocks.$deliveryDateHtml.clone());
            },
            updatePrices: function () {
                self.updatePrice();
                self.updateComparePrice();
                self.updateBasePrice();
                self.updateFullPrice();
            },
            updatePrice: function () {
                var sku_data = self.getSkuData();
    
                if (!sku_data) {
                    return;
                }
    
                var product_data = self.productData;
                var price = sku_data.price + self.getServicesPrice();
                var compare_price = sku_data.compare_price;
                var has_compare_price = compare_price !== 0;
                var stock_unit_name = '';
                var unitDelim = undefined;
    
                if (config.shop['fractional_config']['stock_units_enabled']) {
                    if (product_data['stock_unit_id'] && config.shop['units'][product_data['stock_unit_id']]) {
                        var hasBaseUnit = product_data['stock_unit_id'] !== product_data['base_unit_id'] && product_data['show_fractional'];
                        stock_unit_name = config.shop['units'][product_data['stock_unit_id']]['name_short'];
                        has_compare_price = has_compare_price && !hasBaseUnit;
    
                        if (!hasBaseUnit) {
                            unitDelim = ' /';
                        }
                    }
                }
    
                var $add_to_cart = $product_cart_form.find('.product-add-to-cart');
                var add_to_cart = ProductAddToCart($add_to_cart);
                add_to_cart.updatePrice(price, product_data.currency, has_compare_price, stock_unit_name, unitDelim);
            },
            updateComparePrice: function () {
                var sku_data = self.getSkuData();
    
                if (!sku_data) {
                    return;
                }
    
                var product_data = self.productData;
                var compare_price = sku_data.compare_price;
    
                if (compare_price !== 0) {
                    compare_price += self.getServicesPrice();
                }
    
                if (config.shop['fractional_config']['stock_units_enabled']) {
                    if (product_data['stock_unit_id'] && product_data['stock_unit_id'] !== product_data['base_unit_id'] && product_data['show_fractional']) {
                        compare_price = 0;
                    }
                }
    
                var $add_to_cart = $product_cart_form.find('.product-add-to-cart');
                var add_to_cart = ProductAddToCart($add_to_cart);
                add_to_cart.updateComparePrice(compare_price, product_data.currency);
            },
            updateBasePrice: function () {
                var sku_data = self.getSkuData();
    
                if (!sku_data) {
                    return;
                }
    
                var product_data = self.productData;
                var stock_base_ratio = sku_data['stock_base_ratio'];
                stock_base_ratio = (parseFloat(stock_base_ratio) > 0 ? parseFloat(stock_base_ratio) : null);
                var base_unit_name = '';
    
                if (config.shop['fractional_config']['base_units_enabled']) {
                    if (product_data['base_unit_id'] && (product_data['stock_unit_id'] !== product_data['base_unit_id']) && config.shop['units'][product_data['base_unit_id']] && stock_base_ratio) {
                        base_unit_name = config.shop['units'][product_data['base_unit_id']]['name_short'];
                    }
                }
    
                if (base_unit_name !== '') {
                    var price = sku_data.price + self.getServicesPrice();
                    var base_price = (price / stock_base_ratio);
                    var $add_to_cart = $product_cart_form.find('.product-add-to-cart');
                    var add_to_cart = ProductAddToCart($add_to_cart);
                    add_to_cart.updateBasePrice(base_price, product_data.currency, base_unit_name);
                    add_to_cart.updateBaseRatio(stock_base_ratio);
                }
            },
            updateFullPrice: function () {
                var product_data = self.productData;
                var sku_data = self.getSkuData();
    
                if (!sku_data) {
                    return;
                }
    
                var $quantityBlock = $product_cart_form.find('.quantity');
                var quantityValue = Quantity($quantityBlock).getValue();
                var fullProductPrice = sku_data.price * quantityValue;
                var fullPrice = (sku_data.price + self.getServicesPrice()) * quantityValue;
    
                var $add_to_cart = $product_cart_form.find('.product-add-to-cart');
                var add_to_cart = ProductAddToCart($add_to_cart);
    
                add_to_cart.updateFullPrice(fullPrice, product_data.currency);
                add_to_cart.updateBonuses(fullProductPrice);
            },
            updateImage: function () {
                var sku_data = self.getSkuData();
    
                if (!sku_data) {
                    return;
                }
    
                var image_id = sku_data.image_id;
    
                var $product_images = $product_cart_form.find('.product-images');
    
                if ($product_images.length !== 0) {
                    var product_images = ProductImages($product_images);
                    product_images.selectImage(image_id);
                }
            },
            updateButton: function () {
                var $add_to_cart = $product_cart_form.find('.product-add-to-cart');
                var add_to_cart = ProductAddToCart($add_to_cart);
                var sku_data = self.getSkuData();
    
                if (!sku_data) {
                    return;
                }
    
                var isInCart = sku_data['is_in_cart'];
                var isAvailable = self.isAvailable();
    
                if (isAvailable) {
                    add_to_cart.enableButtons();
                } else {
                    add_to_cart.disableButtons();
                }
    
                $product_cart_form.toggleClass('product-cart-form_not-available', !isAvailable);
    
                if (config.shop.to_toggle_cart_button) {
                    add_to_cart.toggleButton(isInCart);
                }
            },
            updateQuantityProps: function () {
                var sku_data = self.getSkuData();
    
                if (!sku_data) {
                    return;
                }
    
                var $quantityBlock = $product_cart_form.find('.quantity');
    
                var props = {
                    input_min: parseFloat(sku_data['order_count_min']),
                    input_max: parseFloat(sku_data['max_count']),
                    input_step: parseFloat(sku_data['order_count_step']),
                    ratio: { base: parseFloat(sku_data['stock_base_ratio']) },
                    isAvailable: self.isAvailable()
                };
    
                Quantity($quantityBlock).updateProps(props);
            },
            updateSku: function () {
                var sku_data = self.getSkuData();
    
                if (!sku_data) {
                    return;
                }
    
                var $sku_value = $product_cart_form.find('.product-cart-form__sku-value');
                var sku = sku_data.sku;
                var isInCart = sku_data['is_in_cart'];
    
                $sku_value.text(sku);
                self.updateQuantityProps();
    
                if (config.shop.to_toggle_cart_button) {
                    $product_cart_form.toggleClass('product-cart-form_has-sku', !!sku);
                    $product_cart_form.toggleClass('product-cart-form_added', isInCart);
                    $product_cart_form.toggleClass('product-cart-form_add2cart', !isInCart);
                }
    
                self.updateFeatures(sku_data);
    
                if (config.shop.change_url_by_sku) {
                    self.updateUrl(sku_data.id);
                }
            },
            isAvailable: function () {
                var sku_data = self.getSkuData();
    
                if (!sku_data) {
                    return false;
                }
    
                return sku_data.available;
            },
            getButton: function () {
                return $product_cart_form.find('.product-cart-form__button');
            },
            getServicesPrice: function () {
                var sum = 0;
    
                $product_cart_form.find('.service').each(function () {
                    var $service = $(this);
                    var service = Service($service);
                    sum += self.getServicePrice(service);
                });
    
                return sum;
            },
            getServicePrice: function (service) {
                var services_data = self.getServicesData();
                var service_data = services_data[service.getServiceId()];
                var has_variants = typeof service_data === 'object';
                var is_disabled = service_data === false;
    
                if (service.isChecked()) {
                    if (has_variants) {
                        var variant = service_data[service.getServiceVariantId()];
    
                        if (variant) {
                            return +variant[1];
                        }
                    } else if (!is_disabled) {
                        return +service_data;
                    }
                }
    
                return 0;
            },
            getServicesData: function () {
                var product_data = self.productData;
                var sku_id = self.getSkuId();
    
                if (!product_data || !sku_id) {
                    return null;
                }
    
                return product_data.services[sku_id];
            },
            getProductId: function () {
                var data = self.getFormData();
    
                return data.reduce(function (prev, item) {
                    return item.name === 'product_id' ? item.value : prev;
                }, null);
            },
            getSkuId: function () {
                var data = self.getFormData();
    
                var sku_id = data.reduce(function (prev, item) {
                    return item.name === 'sku_id' ? item.value : prev;
                }, null);
    
                if (sku_id === null) {
                    var product_data = self.productData;
    
                    if (product_data && product_data.features) {
                        var key = self.getFeaturesValuesKey();
                        var features = product_data.features;
    
                        if (key in features) {
                            var feature = features[key];
                            sku_id = feature.id;
                        }
                    }
                }
    
                return sku_id;
            },
            getSkuData: function () {
                var sku_id = self.getSkuId();
                var product_data = self.productData;
    
                if (product_data) {
                    if (!sku_id) {
                        var key = self.getFeaturesValuesKey();
    
                        if (product_data.features[key]) {
                            sku_id = product_data.features[key].id;
                        }
                    }
    
                    if (sku_id) {
                        return product_data.skus[sku_id];
                    }
                }
    
                return null;
            },
            selectFeatureSku: function (feature_sku_id) {
                var $feature_selects = $('.product-feature-select');
                var features_sku_data = feature_sku_id.split(';');
    
                features_sku_data.forEach(function (feature_sku_data_initial) {
                    if (!feature_sku_data_initial) {
                        return;
                    }
    
                    var feature_sku_data = feature_sku_data_initial.split(':');
                    var feature_id = feature_sku_data[0];
                    var value = feature_sku_data[1];
    
                    var $feature_select = $feature_selects.filter('[data-id="' + feature_id + '"]');
                    var $values = $feature_select.find('.product-feature-select__value, .product-feature-select__color');
    
                    if (!$values.length) {
                        return;
                    }
    
                    $values.each(function () {
                        var $value = $(this);
                        var is_color = $value.hasClass('product-feature-select__color');
    
                        $value.removeClass(is_color ? 'product-feature-select__color_selected' : 'product-feature-select__value_selected');
    
                        if ($value.data('value') != value) {
                            return;
                        }
    
                        $value.removeClass(is_color ? 'product-feature-select__color_disabled' : 'product-feature-select__value_disabled');
                        $value.addClass(is_color ? 'product-feature-select__color_selected' : 'product-feature-select__value_selected');
                    });
                });
            },
            getFeaturesValues: function () {
                var data = self.getFormData();
                var is_feature_regexp = /^features\[\d+]$/;
                var feature_regexp = /^features\[(\d+)]$/;
    
                return data.filter(function (item) {
                    return is_feature_regexp.test(item.name);
                }, null).map(function (item) {
                    var feature_id = item.name.match(feature_regexp)[1];
    
                    return { feature_id: feature_id, value_id: item.value };
                });
            },
            getFeaturesValuesKey: function () {
                var features_values = self.getFeaturesValues();
    
                return features_values.map(function (item) {
                    return item.feature_id + ':' + item.value_id + ';';
                }).join('');
            },
            getFeaturesValuesSpecificKey: function (feature_id, value_id) {
                var features_values = self.getFeaturesValues();
    
                return features_values.map(function (item) {
                    if (item.feature_id == feature_id) {
                        return feature_id + ':' + value_id + ';';
                    } else {
                        return item.feature_id + ':' + item.value_id + ';';
                    }
                }).join('');
            },
            filterFeatureSelectAvailability: function () {
                var self = this;
                var product_data = self.productData;
                var sku_data = self.getSkuData();
                var hide_unavailable_skus = !!+product_data.hide_unavailable_feature_skus;
    
                if (!hide_unavailable_skus) {
                    return;
                }
    
                var $feature_selects = $('.product-feature-select');
    
                $feature_selects.each(function () {
                    var $feature_select = $(this);
                    var id = $feature_select.data('id');
                    var $values = $feature_select.find('.product-feature-select__value, .product-feature-select__color');
    
                    $values.each(function () {
                        var $value = $(this);
                        var value = $value.data('value');
                        var is_color = $value.hasClass('product-feature-select__color');
                        var disabled_classname = is_color ? 'product-feature-select__color_disabled' : 'product-feature-select__value_disabled';
    
                        var key = self.getFeaturesValuesSpecificKey(id, value);
    
                        if (!(key in product_data.features)) {
                            return;
                        }
    
                        var feature_data = product_data.features[key];
                        var is_disabled = !feature_data.available;
    
                        $value.toggleClass(disabled_classname, is_disabled);
                    });
                });
            },
            getFormData: function () {
                return $product_cart_form.serializeArray();
            },
            updateUrl: function (skuId) {
                var url = new URL(window.location);
                var params = new URLSearchParams(url.search);
                params.set('sku', skuId);
                url.search = params.toString();
                HistoryUtil.replaceState({}, '', url.toString());
            },
            updateFeatures: function (skuData) {
                if (!self.productData.features_values || !skuData.features) {
                    return;
                }
    
                let features = Object.assign({}, self.productData.features_values, skuData.features);
    
                $('.product-full-features .product-feature, .product-short-features .product-feature').each(function () {
                    let $feature = $(this);
                    let featureCode = $feature.data('feature-code');
                    let value = features[featureCode];
                    $feature.toggleClass('product-feature_hidden', value === undefined);
    
                    if (value === undefined) {
                        $feature.addClass('product-feature_hidden');
                        $feature.find('.product-feature__value').html('');
                    } else {
                        $feature.removeClass('product-feature_hidden');
                        $feature.find('.product-feature__value').html(value);
                    }
                });
            }
        });
    
        self.initData();
        self.initEventListeners();
        self.updateServices();
        self.filterFeatureSelectAvailability();
        _private.initGlobalEventListeners();
    
        return function () {
            _private.destroyGlobalEventListeners();
        };
    });
    
    var ProductAddToCart = market_shop.ProductAddToCart = ComponentRegistry.register(function ($context) {
        return $context.select('.product-add-to-cart');
    }, function ($add_to_cart, self) {
        var _private = {
            initEventListeners: function () {
                $add_to_cart.on('click', 'a.product-add-to-cart__button', function (e) {
                    e.preventDefault();
    
                    if ($(this).hasClass('button_disabled')) {
                        return false;
                    }
    
                    $(this).closest('.product-cart-form').trigger('submit');
                });
            },
            initArrived: function () {
                var $arrived = $add_to_cart.find('.product-add-to-cart__arrived .plugin_arrived-button a');
    
                $add_to_cart.find('.product-add-to-cart__arrived-button').on('click', function () {
                    $arrived.trigger('click');
    
                    $('.plugin_arrived-overlay').remove();
                    var $popup = $('.plugin_arrived-popup');
                    _private.initPopup($popup);
                });
            },
    
            initPopup: function ($popup) {
                var $decorator = $('<div class="arrived-decorator"></div>');
                $decorator.append($popup);
                ModalUtil.openContent($decorator);
            }
        };
    
        $.extend(self, {
            updatePrice: function (price, currency, has_compare_price, stock_unit_name, unitDelim) {
                var html = '';
    
                if (price === 0 && config.shop.zero_price_text !== '') {
                    html = config.shop.zero_price_text;
                    $add_to_cart.addClass('product-add-to-cart_zero-price');
                } else {
                    html = CurrencyUtil.format(price, currency, false, stock_unit_name, unitDelim);
                    $add_to_cart.removeClass('product-add-to-cart_zero-price');
                }
    
                $add_to_cart.find('.product-add-to-cart__price')
                    .html(html)
                    .toggleClass('product-add-to-cart__price_with-compare', has_compare_price);
            },
            updateComparePrice: function (compare_price, currency) {
                $add_to_cart.find('.product-add-to-cart__compare-price').html(CurrencyUtil.format(compare_price, currency, false));
                $add_to_cart.toggleClass('product-add-to-cart_has-compare-price', compare_price !== 0);
            },
            updateBasePrice: function (base_price, currency, stock_unit_name) {
                var $base_price = $add_to_cart.find('.product-add-to-cart__base-price');
    
                if ($base_price.length > 0) {
                    $base_price.html(CurrencyUtil.format(base_price, currency, false, stock_unit_name));
                }
            },
            updateFullPrice: function (full_price, currency) {
                var $full_price = $add_to_cart.find('.product-add-to-cart__full-price');
    
                if ($full_price.length > 0) {
                    $full_price.html(CurrencyUtil.format(full_price, currency, false));
                }
            },
            updateBaseRatio: function (stock_base_ratio) {
                var $base_ratio = $add_to_cart.find('.product-add-to-cart__base-ratio');
    
                if ($base_ratio.length > 0) {
                    if (stock_base_ratio) {
                        $base_ratio.html(stock_base_ratio).show();
                    } else {
                        $base_ratio.hide();
                    }
                }
            },
            updateBonuses: function (price) {
                var $bonusesBlock = $add_to_cart.find('.product-add-to-cart__bonuses');
    
                if ($bonusesBlock.length > 0) {
                    var $bonusesValueBlock = $bonusesBlock.find('.bonus-block__value');
                    var bonusesRate = Number($bonusesValueBlock.data('bonuses-rate'));
    
                    $bonusesValueBlock.text(parseFloat(price / bonusesRate).toFixed(2));
                }
            },
            enableButtons: function () {
                $add_to_cart.removeClass('product-add-to-cart_disabled');
                $add_to_cart.find('.product-add-to-cart__button').prop('disabled', false);
            },
            disableButtons: function () {
                $add_to_cart.addClass('product-add-to-cart_disabled');
                $add_to_cart.find('.product-add-to-cart__button').prop('disabled', true);
            },
            toggleButton: function (state) {
                var button = $add_to_cart.find('.product-add-to-cart__button');
                var btnText = state ? button.data('success-text') : button.data('default-text');
    
                button.toggleClass('button_style_inverse', state).text(btnText);
            }
        });
    
        _private.initEventListeners();
        _private.initArrived();
    });
    
    var ProductThumb = market_shop.ProductThumb = ComponentRegistry.register(function ($context) {
        return $context.select('.product-thumb');
    }, function ($product, self) {
        var _private = {
            initArrived: function () {
                var $arrived = $product.find('.product-thumb__arrived .plugin_arrived-button a');
    
                $product.find('.product-thumb__arrived-button').on('click', function () {
                    $arrived.trigger('click');
    
                    $('.plugin_arrived-overlay').remove();
                    var $popup = $('.plugin_arrived-popup');
                    _private.initPopup($popup);
                });
            },
    
            initPopup: function ($popup) {
                var $decorator = $('<div class="arrived-decorator"></div>');
                $decorator.append($popup);
                ModalUtil.openContent($decorator);
                market.Select.create($decorator.find('select'), null, true);
            },
    
            initEventListeners: function () {
                $product.find('.quantity').on('set_value@market:quantity', function () {
                    var quantity = Quantity($(this));
                    $product.find('.product-thumb__quantity-value').val(quantity.getValue());
                });
    
                if ($product.hasClass('product-thumb_hidden-blocks') && $product.find('.product-thumb__hidden').length > 0) {
                    $product.on('mouseover', function () {
                        $product.addClass('product-thumb_show-hidden').trigger('show_hidden');
                    });
                    $product.on('mouseout', function () {
                        $product.removeClass('product-thumb_show-hidden').trigger('hide_hidden');
                    });
                }
            }
        };
    
        $.extend(self, {
            initSwiper: function () {
                var $imagesGallery = $product.find('.product-thumb__image-box_gallery');
    
                if ($imagesGallery.length > 0) {
                    var isLoop = false;
                    var isDinamicBullets = false;
                    var $images = $imagesGallery.find('.swiper-lazy');
                    $images.each(function () {
                        var $image = $(this);
                        var src = $image.data('src');
                        var srcset = $image.data('srcset');
                        var sizesData = $image.data('sizes');
                        var defaultSize = src.replace(/^.*\/[0-9]+\.(.*)\..*$/, '$1');
    
                        if (sizesData) {
                            var sizes = config.shop.images_sizes[sizesData];
    
                            if (sizes) {
                                $.each(sizes, function (vp, size) {
                                    if (MatchMedia('(max-width: ' + vp + 'px)') && defaultSize >= size) {
                                        $image.attr('data-src', src.replace(/^(.*\/[0-9]+\.)(.*)(\..*)$/, '$1' + size + '$3'));
                                        $image.attr('data-srcset', srcset.replace(/^(.*\/[0-9]+\.)(.*)(\..*)$/, '$1' + size * 2 + '$3'));
    
                                        return false;
                                    }
                                });
                            }
                        }
                    });
    
                    if (ResponsiveUtil.isMobileMax()) {
                        isDinamicBullets = true;
                    }
    
                    if (ResponsiveUtil.isTabletMax()) {
                        isLoop = true;
                    }
    
                    $imagesGallery.addClass('product-thumb__image-box_swiper-init');
    
                    var swiper = new Swiper($imagesGallery.get(0), {
                        cssMode: true,
                        slidesPerView: 1,
                        loop: isLoop,
                        pagination: {
                            el: '.gallery-pagination',
                            bulletClass: 'gallery-pagination__bullet',
                            bulletActiveClass: 'gallery-pagination__bullet_active',
                            dynamicBullets: isDinamicBullets,
                            dynamicMainBullets: 5,
                            clickable: true
                        },
                        lazy: {
                            loadPrevNext: true,
                            loadPrevNextAmount: 1,
                            elementClass: 'swiper-lazy'
                        }
                    });
    
                    $(document).trigger('swiper_lazyload@market:global', swiper);
                }
            },
            initDesktopSlider: function () {
                var $galleryContainer = $product.find('.product-thumb__image-box_gallery-container');
                var $imagesGallery = $galleryContainer.find('.product-thumb__gallery-d');
    
                if ($imagesGallery.length > 0) {
                    var $imagesGalleryImages = $imagesGallery.find('.product-thumb__gallery-image');
                    var pagination = $imagesGallery.next('.product-thumb__gallery-pagination');
                    var paginationItems = pagination.find('.gallery-pagination__bullet');
    
                    var fieldsPagination = $imagesGallery.find('.product-thumb__gallery-field-pagination');
                    var fieldsPaginationItems = fieldsPagination.find('.product-thumb__gallery-field-item');
    
                    paginationItems.on('click', function (e) {
                        e.preventDefault();
                        var bullet = $(this);
                        var index = bullet.data('index');
    
                        $imagesGalleryImages.hide();
                        $($imagesGalleryImages[index]).show();
                        paginationItems.removeClass('gallery-pagination__bullet_active');
                        bullet.addClass('gallery-pagination__bullet_active');
                    });
    
                    fieldsPaginationItems.on('mouseover', function () {
                        var field = $(this);
                        var index = field.data('index');
    
                        pagination.find('[data-index="' + index + '"]').trigger('click');
                    });
    
                    $galleryContainer.on('mouseleave', function (e) {
                        pagination.find('[data-index="0"]').trigger('click');
                    });
                }
            },
            initSlider: function () {
                if (!$product.parent().hasClass('products-slider__item')) {
                    self.initSwiper();
                }
    
                self.initDesktopSlider();
            },
            setMinHeight: function () {
                if ($product.hasClass('product-thumb_hidden-blocks')) {
                    var productItem = $product.closest('.products-thumbs__item');
                    var height = $product.outerHeight();
    
                    productItem.css('min-height', height);
                }
            }
        });
    
        self.setMinHeight();
        _private.initArrived();
        _private.initEventListeners();
        self.initSlider();
    });
    
    var ProductExtend = market_shop.ProductExtend = ComponentRegistry.register(function ($context) {
        return $context.select('.product-extend');
    }, function ($product, self) {
        var _private = {
            initArrived: function () {
                var $arrived = $product.find('.product-extend__arrived .plugin_arrived-button a:eq(0)');
    
                $product.find('.product-extend__arrived-button').on('click', function () {
                    $arrived.trigger('click');
    
                    $('.plugin_arrived-overlay').remove();
                    var $popup = $('.plugin_arrived-popup');
                    _private.initPopup($popup);
                });
            },
    
            initPopup: function ($popup) {
                var $decorator = $('<div class="arrived-decorator"></div>');
                $decorator.append($popup);
                ModalUtil.openContent($decorator);
                market.Select.create($decorator.find('select'), null, true);
            },
    
            initEventListeners: function () {
                $product.find('.quantity').on('set_value@market:quantity', function () {
                    var quantity = Quantity($(this));
                    $product.find('.product-extend__quantity-value').val(quantity.getValue());
                });
            }
        };
    
        $.extend(self, {
            initSwiper: function () {
                var $imagesGalleries = $product.find('.product-extend__image-box_gallery');
    
                if ($imagesGalleries.length > 0) {
                    var isLoop = false;
                    var isDinamicBullets = false;
    
                    if (ResponsiveUtil.isTabletMax()) {
                        isDinamicBullets = true;
                    }
    
                    if (ResponsiveUtil.isTabletMax()) {
                        isLoop = true;
                    }
    
                    $imagesGalleries.each(function () {
                        var $imagesGallery = $(this);
                        $imagesGallery.addClass('product-extend__image-box_swiper-init');
    
                        var swiper = new Swiper($imagesGallery.get(0), {
                            cssMode: true,
                            slidesPerView: 1,
                            loop: isLoop,
                            pagination: {
                                el: '.gallery-pagination',
                                bulletClass: 'gallery-pagination__bullet',
                                bulletActiveClass: 'gallery-pagination__bullet_active',
                                dynamicBullets: isDinamicBullets,
                                dynamicMainBullets: 5,
                                clickable: true
                            },
                            lazy: {
                                loadPrevNext: true,
                                elementClass: 'swiper-lazy'
                            }
                        });
    
                        $(document).trigger('swiper_lazyload@market:global', swiper);
                    });
                }
            },
            initDesktopSlider: function () {
                var $galleryContainer = $product.find('.product-extend__image-box_gallery');
                var $imagesGallery = $galleryContainer.find('.product-extend__gallery-d');
    
                if ($imagesGallery.length > 0) {
                    var $imagesGalleryImages = $imagesGallery.find('.image-box');
                    var pagination = $imagesGallery.next('.product-extend__gallery-pagination');
                    var paginationItems = pagination.find('.gallery-pagination__bullet');
    
                    var fieldsPagination = $imagesGallery.find('.product-extend__gallery-field-pagination');
                    var fieldsPaginationItems = fieldsPagination.find('.product-extend__gallery-field-item');
    
                    paginationItems.on('click', function () {
                        var bullet = $(this);
                        var index = bullet.data('index');
    
                        $imagesGalleryImages.hide();
                        $($imagesGalleryImages[index]).show();
                        paginationItems.removeClass('gallery-pagination__bullet_active');
                        bullet.addClass('gallery-pagination__bullet_active');
                    });
    
                    fieldsPaginationItems.on('mouseover', function () {
                        var field = $(this);
                        var index = field.data('index');
    
                        pagination.find('[data-index="' + index + '"]').trigger('click');
                    });
    
                    $galleryContainer.on('mouseleave', function (e) {
                        pagination.find('[data-index="0"]').trigger('click');
                    });
                }
            },
            initSlider: function () {
                var isDesktop = $product.find('.product-extend__gallery-d').length > 0;
    
                if (isDesktop) {
                    self.initDesktopSlider();
                } else {
                    self.initSwiper();
                }
            }
        });
    
        _private.initArrived();
        _private.initEventListeners();
    
        self.initSlider();
    });
    
    var ProductCompact = market_shop.ProductCompact = ComponentRegistry.register(function ($context) {
        return $context.select('.product-compact');
    }, function ($product) {
        var _private = {
            initArrived: function () {
                var $arrived = $product.find('.product-compact__arrived .plugin_arrived-button a');
    
                $product.find('.product-compact__arrived-button').on('click', function () {
                    $arrived.trigger('click');
    
                    $('.plugin_arrived-overlay').remove();
                    var $popup = $('.plugin_arrived-popup');
                    _private.initPopup($popup);
                });
            },
    
            initPopup: function ($popup) {
                var $decorator = $('<div class="arrived-decorator"></div>');
                $decorator.append($popup);
                ModalUtil.openContent($decorator);
                market.Select.create($decorator.find('select'), null, true);
            },
    
            initEventListeners: function () {
                $product.find('.quantity').on('set_value@market:quantity', function () {
                    var quantity = Quantity($(this));
                    $product.find('.product-compact__quantity-value').val(quantity.getValue());
                });
            }
        };
    
        _private.initArrived();
        _private.initEventListeners();
    });
    
    market_shop.ProductsSliderDecorator = ComponentRegistry.register(function ($context) {
        return $context.select('.products-slider-decorator');
    }, function ($container) {
        var $products_thumbs = $container.find('.products-thumbs');
    
        var _private = {
            init: function () {
                $products_thumbs.addClass('products-thumbs_slider swiper');
                $products_thumbs.find('.products-thumbs__wrapper').addClass('swiper-wrapper');
                $products_thumbs.find('.products-thumbs__item').addClass('swiper-slide');
            }
        };
    
        _private.init();
        Update($container);
    });
    
    var ProductsSlider = market_shop.ProductsSlider = ComponentRegistry.register(function ($context) {
        return $context.select('.products-slider');
    }, function ($products_slider) {
        var _private = {
            initEventListeners: function () {
                if (ResponsiveUtil.isDesktopMin()) {
                    $products_slider.on('mouseover', function () {
                        $(this).css('z-index', '2');
                    });
    
                    $products_slider.on('mouseout', function () {
                        $(this).css('z-index', '');
                    });
                }
            },
    
            initSwiper: function () {
                var space = $products_slider.attr('data-space');
                var slidesPerView = $products_slider.attr('data-slides-per-view');
                var is_cols_view = $products_slider.hasClass('products-slider_cols');
                var is_compact = $products_slider.hasClass('products-slider_compact');
                var isCssMode = $products_slider.data('cssMode') || true;
    
                if (ResponsiveUtil.isDesktopMin() && $products_slider.hasClass('products-thumbs')) {
                    _private.setStyles(isCssMode);
                }
    
                var $sliderBlock = $products_slider.closest('.slider-block');
    
                var swiper = new Swiper($products_slider.get(0), {
                    cssMode: isCssMode,
                    slidesPerView: 'auto',
                    watchSlidesVisibility: true,
                    watchSlidesProgress: true, navigation: {
                        prevEl: $sliderBlock.find('.slider-block__prev-button').get(0),
                        nextEl: $sliderBlock.find('.slider-block__next-button').get(0)
                    }
                });
    
                $products_slider.trigger('init@market:swiper', swiper);
            },
    
            setStyles: function (isCssMode) {
                var productsBlocks = $products_slider.find('.product');
                var hiddenBlocksHeights = 0;
    
                productsBlocks.each(function () {
                    var productBlock = $(this);
                    var hiddenBlocks = productBlock.find('.product-thumb__hidden');
                    var blocksHeight = 0;
    
                    if (hiddenBlocks.length) {
                        hiddenBlocks.each(function () {
                            var block = $(this);
    
                            blocksHeight = blocksHeight + block.height();
                        });
    
                        if (blocksHeight > hiddenBlocksHeights) {
                            hiddenBlocksHeights = blocksHeight;
                        }
                    }
                });
    
                if (hiddenBlocksHeights > 0) {
                    var offset = hiddenBlocksHeights + 100;
                    var sliderPadding = parseInt($products_slider.css('padding-bottom'));
    
                    if (isCssMode) {
                        $products_slider.find('.products-thumbs__wrapper').css({
                            paddingBottom: offset + 'px',
                            marginBottom: '-' + offset + 'px'
                        });
    
                        offset = offset + sliderPadding;
                    }
    
                    $products_slider.css({
                        paddingBottom: offset + 'px',
                        marginBottom: '-' + offset + 'px'
                    });
                }
            }
        };
    
        _private.initSwiper();
    
        _private.initEventListeners();
    });
    
    var Product = market_shop.Product = ComponentRegistry.register(function ($context) {
        return $context.select('.product');
    }, function ($product, self) {
        $.extend(self, {
            initEventListeners: function () {
                $product.on('click', '.product__delete-button', function (e) {
                    e.preventDefault();
                    self.delete();
                });
    
                $product.on('click', '.product__recovery-button', function (e) {
                    e.preventDefault();
                    self.recovery();
                });
    
                $product.on('click', '.product__quick-view', function (e) {
                    e.preventDefault();
                    var url = $product.data('url');
                    var queryString = '';
                    var params = QueryUtil.parse(url);
    
                    params.push({
                        name: 'modal',
                        value: 1
                    });
    
                    url = QueryUtil.clear(url);
                    queryString = '?' + QueryUtil.serialize(params);
    
                    ModalUtil.openAjax(url + queryString, function (data) {
                        return $(data).find('.product-cart-form').get(0);
                    }, {
                        classes: 'product-modal',
                        title: $product.data('product_name'),
                        contentClasses: 'modal__content_header'
                    });
    
                    return false;
                });
    
                $product.on('click', '.product__add-to-cart-button', function (e) {
                    e.preventDefault();
    
                    if ($(this).hasClass('button_disabled')) {
                        return false;
                    }
    
                    $product.find('.product__add-to-cart-form').trigger('submit');
                });
    
                var in_process = false;
    
                $product.on('submit', '.product__add-to-cart-form', function (e) {
                    e.preventDefault();
    
                    if ($product.hasClass('product_has-multi-skus') || $product.hasClass('product_no-add')) {
                        var url = $product.data('url');
    
                        if ($product.hasClass('product_has-multi-skus')) {
                            var queryString = '';
                            var params = QueryUtil.parse(url);
                            params.push({
                                name: 'modal',
                                value: 1
                            });
    
                            if (ResponsiveUtil.isTabletMax()) {
                                params.push({
                                    name: 'mobile',
                                    value: 1
                                });
                            }
    
                            url = QueryUtil.clear(url);
                            queryString = '?' + QueryUtil.serialize(params);
    
                            ModalUtil.openAjax(url + queryString, function (data) {
                                return $(data).find('.product-cart-form').get(0);
                            }, {
                                classes: 'product-modal',
                                title: $product.data('product_name'),
                                contentClasses: 'modal__content_header'
                            });
                        } else {
                            window.location = url;
                        }
    
                        return;
                    }
    
                    if ($product.hasClass('product_added')) {
                        window.location = config.shop.real_cart_url;
    
                        return;
                    }
    
                    if (in_process) {
                        return;
                    }
    
                    in_process = true;
                    var $form = $(this);
                    var data = $form.serializeArray();
                    var max_count = $form.data('max-count');
                    var max_count_is_null = max_count === '';
    
                    data.push({ name: 'html', value: true });
    
                    var addedQuantity = null;
                    $(data).each(function () {
                        var entry = this;
    
                        if (entry.name === 'quantity') {
                            addedQuantity = parseInt(entry.value);
                        }
                    });
    
                    if (!max_count_is_null && addedQuantity > max_count) {
                        var message = config.language['message_max_count'].replace(/(.+)%sku_count%(.+)%sku_name%(.+)%max_sku_quantity%/, '$1' + $form.data('sku-count') + '$2' + $form.data('sku-name') + '$3' + max_count);
    
                        if ($form.data('max-count') <= 0) {
                            message = config.language['message_max_count_in_cart'].replace(/(.+)%sku_count%(.+)%sku_name%/, '$1' + $form.data('sku-count') + '$2' + $form.data('sku-name'));
                        }
    
                        InfoPanelUtil.showMessage(message);
    
                        return false;
                    }
    
                    CartUtil.addByData(data).then(function (response) {
                        in_process = false;
    
                        if ($product.attr('data-add-to-cart-effects') === 'disabled') {
                            $(document).trigger('shop_cart_add_product_effects_off@market:global', arguments);
                        } else {
                            $(document).trigger('shop_cart_add_product@market:global', arguments);
                        }
    
                        if (response.status !== 'ok') {
                            return;
                        }
    
                        if (!max_count_is_null) {
                            $form.data('max-count', max_count - addedQuantity);
                        }
    
                        if ($product.hasClass('product_add2cart') && config.shop.to_toggle_cart_button) {
                            var $btn = $product.find('.product__add-to-cart-form .button');
                            var $textBlock = $btn.find('.product__btn-text');
    
                            if ($textBlock.length === 0) {
                                $textBlock = $btn;
                            }
    
                            $btn.addClass('button_style_inverse');
                            $textBlock.text($btn.data('success-text'));
                            $product.addClass('product_added');
                        }
    
                        var effect = config.shop.add_to_cart_effect;
    
                        if (effect !== 'fly') {
                            return;
                        }
    
                        var $counter = $('.cart-counter:visible:first');
    
                        if ($counter.length !== 1) {
                            return;
                        }
    
                        var $image = $product.find('.product__image:visible');
                        FlyUtil.flyImage($image, $counter, {
                            zIndex: 100
                        });
                    }, function () {
                        in_process = false;
                    });
                });
            },
            getProductId: function () {
                return $product.data('product_id');
            },
            isDelete: function () {
                return $product.hasClass('product_delete');
            },
            delete: function () {
                if (self.isDelete()) {
                    return;
                }
    
                $product.addClass('product_delete');
                $product.trigger('delete');
            },
            recovery: function () {
                if (!self.isDelete()) {
                    return;
                }
    
                $product.removeClass('product_delete');
                $product.trigger('recovery');
            },
            decorate: function () {
                var $select = $product.find('select');
    
                if ($select.length > 0) {
                    market.Select.create($select, 's', true);
                }
            }
        });
    
        self.decorate();
        self.initEventListeners();
    });
    
    var ProductFeatureSelect = market_shop.ProductFeatureSelect = ComponentRegistry.register(function ($context) {
        return $context.select('.product-feature-select');
    }, function ($feature_select, self) {
        var form = ProductCartForm($feature_select.closest('.product-cart-form'));
    
        $.extend(self, {
            selectHandler: function () {
                form.filterFeatureSelectAvailability();
                form.updateServices();
            },
            initEventListeners: function () {
                $feature_select.on('click', '.product-feature-select__value', function () {
                    var $input = self.getInput();
                    var $values = $feature_select.find('.product-feature-select__value');
                    var $value = $(this);
                    var value = $value.data('value');
    
                    $values.removeClass('product-feature-select__value_selected');
                    $value.addClass('product-feature-select__value_selected');
                    $input.val(value);
                    $input.trigger('change');
    
                    self.selectHandler();
                });
    
                $feature_select.on('click', '.product-feature-select__color', function () {
                    var $input = self.getInput();
                    var $values = $feature_select.find('.product-feature-select__color');
                    var $value = $(this);
                    var value = $value.data('value');
    
                    $values.removeClass('product-feature-select__color_selected');
                    $value.addClass('product-feature-select__color_selected');
                    $input.val(value);
                    $input.trigger('change');
    
                    self.selectHandler();
                });
            },
            getInput: function () {
                return $feature_select.find('.product-feature-select__input');
            }
        });
    
        self.initEventListeners();
    });
    
    var Service = market_shop.Service = ComponentRegistry.register(function ($context) {
        return $context.select('.service');
    }, function ($service, self) {
        $.extend(self, {
            initEventListeners: function () {
                $service.on('change', 'input.service__checkbox', function () {
                    self.refreshSelectVariants();
    
                    if (self.isChecked()) {
                        $service.trigger('check@market:service');
                    } else {
                        $service.trigger('uncheck@market:service');
                    }
                });
    
                $service.on('change', 'select.service__select-variants', function () {
                    $service.trigger('change@market:service');
                });
    
                $service.on('click', '.service__select-variants', function () {
                    if (!self.isDisabled() && !self.isChecked()) {
                        var $checkbox = self.getCheckbox();
                        $checkbox
                            .prop('checked', true)
                            .trigger('change');
                    }
                });
            },
            isHideUnavailableVariants: function () {
                return $service.find('.service__variants_hide_unavailable').length > 0;
            },
            getServiceId: function () {
                return +self.getCheckbox().val();
            },
            getServiceVariantId: function () {
                if (self.hasVariants()) {
                    return +self.getSelectVariants().val();
                } else {
                    return +self.getInputVariant().val();
                }
            },
            refreshSelectVariants: function () {
                var $checkbox = self.getCheckbox();
                var is_checked = $checkbox.prop('checked');
                var $select = self.getSelectVariants();
                $select.prop('disabled', !is_checked);
            },
            updatePrice: function (price, currency_info) {
                var $price = $service.find('.service__price');
                $price.html(CurrencyUtil.format(price, currency_info, false));
            },
            updateVariants: function (variants, currency_info) {
                var $select_variants = self.getSelectVariants();
                var $variants = self.getVariants();
                var selected_variant_id = $select_variants.val();
                var is_selected = true;
    
                $variants.each(function () {
                    var $variant = $(this);
                    var variant_id = $variant.val();
                    var variant = variants[variant_id];
    
                    if (variant) {
                        var name = variant[0];
                        var price = variant[1];
                        var variant_text = name + ' (+' + CurrencyUtil.format(price, currency_info, true) + ')';
    
                        self.enableVariant($variant);
                        $variant.data('price', price);
                        $variant.text(variant_text);
                    } else {
                        if (variant_id === selected_variant_id) {
                            is_selected = false;
                        }
    
                        self.disableVariant($variant);
                    }
                });
    
                if (!is_selected) {
                    $variants.filter(':enabled:first').prop('selected', true);
                }
            },
            getVariants: function () {
                return $service.find('.service__variant');
            },
            getCheckbox: function () {
                return $service.find('input.service__checkbox');
            },
            getSelectVariants: function () {
                return $service.find('select.service__select-variants');
            },
            getInputVariant: function () {
                return $service.find('.service__input-variant');
            },
            hasVariants: function () {
                return self.getSelectVariants().length > 0;
            },
            isDisabled: function () {
                return self.getCheckbox().prop('disabled');
            },
            isChecked: function () {
                return self.getCheckbox().prop('checked');
            },
            enable: function () {
                var $checkbox = self.getCheckbox();
                $checkbox.prop('disabled', false);
            },
            disable: function () {
                var $checkbox = self.getCheckbox();
                $checkbox
                    .prop('disabled', true)
                    .prop('checked', false);
            },
            enableVariant: function ($variant) {
                $variant.prop('disabled', false);
    
                if (self.isHideUnavailableVariants()) {
                    $variant.show();
                }
            },
            disableVariant: function ($variant) {
                $variant.prop('disabled', true);
    
                if (self.isHideUnavailableVariants()) {
                    $variant.hide();
                }
            }
        });
    
        self.initEventListeners();
    });
    
    var Quantity = market_shop.Quantity = ComponentRegistry.register(function ($context) {
        return $context.select('.quantity');
    }, function ($quantity, self) {
        $.extend(self, {
            timeout_id: null,
            initEventListeners: function () {
                $quantity.on('click', '.quantity__minus-button', function () {
                    if (self.getInput().prop('disabled') === true || $quantity.hasClass('quantity_min')) {
                        return false;
                    }
    
                    self.setValue(self.getValue() - self.getStep());
                    self.startTimeoutChange();
                });
    
                $quantity.on('click', '.quantity__plus-button', function () {
                    if (self.getInput().prop('disabled') === true || ($quantity.hasClass('quantity_max') && !self.isIgnoreMax())) {
                        return false;
                    }
    
                    self.setValue(self.getValue() + self.getStep());
                    self.startTimeoutChange();
                });
    
                $quantity.on('change', '.quantity__field', function (e) {
                    e.stopPropagation();
                    var $field = $(this);
    
                    if ($field.hasClass('quantity__input')) {
                        return false;
                    }
    
                    var value = $field.val();
                    var ratio = $field.data('ratio');
                    var inputValue = value / ratio;
                    var $input = self.getInput();
    
                    $input.val(inputValue);
                    $input.trigger('change');
                });
    
                $quantity.on('change', '.quantity__input', function (e) {
                    if (self.getInput().prop('disabled') === true) {
                        return false;
                    }
    
                    e.stopPropagation();
    
                    self.refreshValue();
                    $quantity.trigger('change');
                });
            },
            getInput: function () {
                return $quantity.find('.quantity__input');
            },
            getFields: function () {
                return $quantity.find('.quantity__field');
            },
            getValue: function () {
                return self.handleValue(self.getInput().val());
            },
            setValue: function (value) {
                var handledValue = self.handleValue(value);
                var $input = self.getInput();
    
                self.getInput().val(handledValue);
                self.setFieldsValues(handledValue);
                self.toggleClass();
                $quantity.trigger('set_value@market:quantity');
            },
            setFieldsValues: function (value) {
                var $fields = self.getFields().filter(':not(.quantity__input)');
    
                $fields.each(function () {
                    var $field = $(this);
                    var ratio = parseFloat($field.data('ratio'));
                    var handledValue = NumberUtil.formatNumber(value * ratio);
    
                    $field.val(handledValue);
                });
            },
            startTimeoutChange: function () {
                clearTimeout(self.timeout_id);
                self.timeout_id = setTimeout(function () {
                    self.getInput().trigger('change');
                }, 500);
            },
            handleValue: function (value) {
                value = (typeof value !== 'number' ? parseFloat(value) : value);
                value = parseFloat(value.toFixed(3));
    
                var minValue = self.getMinValue(),
                    maxValue = self.getMaxValue(),
                    multiplicity = self.getMultiplicity();
    
                if (isNaN(value) || value < minValue) {
                    value = minValue;
                }
    
                var steps_count = Math.floor(value / multiplicity);
                var x1 = (multiplicity * steps_count).toFixed(3) * 1;
    
                if (x1 !== value) {
                    value = multiplicity * (steps_count + 1);
                }
    
                if (maxValue && value > maxValue && !self.isIgnoreMax()) {
                    value = maxValue;
                }
    
                return NumberUtil.formatNumber(Math.max(minValue, value));
            },
            refreshValue: function () {
                self.setValue(self.getValue());
            },
            getMinValue: function () {
                var minVal = self.getInput().data('min');
    
                return NumberUtil.validateNumber('float', minVal) !== 'NaN' ? minVal : 1;
            },
            getMaxValue: function () {
                var maxVal = self.getInput().data('max');
    
                return NumberUtil.validateNumber('float', maxVal) !== 'NaN' ? maxVal : null;
            },
            getStep: function () {
                var stepVal = self.getInput().data('step');
    
                return NumberUtil.validateNumber('float', stepVal) !== 'NaN' ? stepVal : 1;
            },
            getActiveFieldStep: function () {
                var step = self.getStep();
                var $fields = self.getFields();
    
                if ($fields.length >= 1) {
                    var $activeField = $fields.filter('.quantity__field_active');
                    var ratio = parseFloat($activeField.data('ratio') || 1);
    
                    step = step * ratio;
                }
    
                return step;
            },
            getMultiplicity: function () {
                var multiplicityVal = self.getInput().data('multiplicity');
    
                return NumberUtil.validateNumber('float', multiplicityVal) !== 'NaN' ? multiplicityVal : 1;
            },
            toggleClass: function () {
                var value = self.getValue();
                var minValue = self.getMinValue();
                var maxValue = self.getMaxValue();
    
                $quantity.toggleClass('quantity_min', value <= minValue);
    
                if (maxValue && !self.isIgnoreMax()) {
                    $quantity.toggleClass('quantity_max', value >= maxValue);
                }
            },
            toggleFields: function (fieldId) {
                var $fields = self.getFields();
                var activeClass = 'quantity__field_active';
                var $activeField = $fields.filter('[data-field-id="' + fieldId + '"]');
    
                $fields.removeClass(activeClass);
                $activeField.addClass(activeClass);
    
                self.updateStepBlocks();
            },
            isIgnoreMax: function () {
                return $quantity.hasClass('quantity_ignore-max');
            },
            updateProps: function (props) {
                var $quantityInput = self.getInput();
                $quantityInput.data('min', props['input_min']);
                $quantityInput.data('max', props['input_max']);
                $quantityInput.data('step', props['input_step']);
    
                self.updateMinProp();
                self.updateMaxProp();
                self.updateFieldsRatio(props['ratio']);
                self.updateStepBlocks();
    
                self.toggleInputsDisabled(props['isAvailable']);
            },
            updateMinProp: function () {
                var minValue = self.getMinValue();
    
                if (minValue) {
                    var value = self.getValue();
    
                    if (value <= minValue) {
                        self.setValue(minValue);
                    }
                }
            },
            updateMaxProp: function () {
                var maxValue = self.getMaxValue();
    
                if (maxValue && !self.isIgnoreMax()) {
                    var value = self.getValue();
    
                    if (value >= maxValue) {
                        self.setValue(maxValue);
                    }
                } else {
                    self.toggleClass();
                }
            },
            updateStepBlocks: function () {
                var activeFieldStep = self.getActiveFieldStep();
    
                $quantity.find('.quantity__step').text(activeFieldStep);
                $quantity.toggleClass('quantity_step', activeFieldStep > 1);
            },
            updateFieldsRatio: function (newRatio) {
                self.getFields().each(function () {
                    var $field = $(this);
                    var fieldId = $field.data('field-id');
    
                    if (newRatio[fieldId]) {
                        $field.data('ratio', newRatio[fieldId]);
                    }
                });
    
                self.refreshValue();
            },
            toggleInputsDisabled: function (toToggle) {
                self.getFields().prop('disabled', !toToggle);
            }
        });
    
        self.initEventListeners();
    });
    
    var QuantityToggles = market_shop.QuantityToggles = ComponentRegistry.register(function ($context) {
        return $context.select('.quantity-toggles');
    }, function ($quantityToggles, self) {
        var $items = $quantityToggles.find('.quantity-toggles__item');
        var $prices = $quantityToggles.find('.quantity-toggles__unit-price');
        var $quantityWaFields = $quantityToggles.find('.quantity-toggles__field');
    
        var _private = {
            initEventListeners: function () {
                $items.on('click', function () {
                    var $item = $(this);
                    _private.toggleBlocks($item);
                });
            },
            getQuantity: function () {
                return $quantityToggles.find('.quantity');
            },
            toggleBlocks: function ($item) {
                var fieldId = $item.data('field-id');
    
                $items.removeClass('quantity-toggles__item_active');
                $item.addClass('quantity-toggles__item_active');
    
                if ($quantityWaFields.length === 0) {
                    var quantity = Quantity(_private.getQuantity());
                    quantity.toggleFields(fieldId);
                } else {
                    var $activeField = $quantityWaFields.filter('[data-field-id="' + fieldId + '"]');
    
                    $quantityWaFields.removeClass('quantity-toggles__field_active');
                    $activeField.addClass('quantity-toggles__field_active');
    
                    $quantityToggles.find('.quantity-toggles__step').text($activeField.data('step'));
                }
    
                $prices.removeClass('quantity-toggles__unit-price_active');
                $prices.filter('[data-unit-id="' + fieldId + '"]').addClass('quantity-toggles__unit-price_active');
            }
        };
    
        _private.initEventListeners();
    });
    
    var ComparePage = market_shop.ComparePage = ComponentRegistry.register(function ($context) {
        return $context.select('.compare-page');
    }, function ($compare_page, self) {
        var _private = {
            product_ids: $compare_page.data('product_ids'),
            selected_type: $compare_page.data('selected_type'),
            rows_by_index: [],
    
            initRows: function () {
                $compare_page.find('.compare-page__row_feature, .compare-page__row_divider, '
                  + '.compare-page__row_feature-value, .compare-page__row_divider-value').each(function () {
                    var $row = $(this);
                    var index = $row.index();
    
                    if (!_private.rows_by_index[index]) {
                        _private.rows_by_index[index] = $();
                    }
    
                    _private.rows_by_index[index] = _private.rows_by_index[index].add($row);
                });
            },
    
            initSwiper: function () {
                if (_private.product_ids.length <= 4) {
                    return;
                }
    
                var $products_header = $compare_page.find('.compare-page__products_header');
    
                var products_header_swiper = new Swiper($products_header.get(0), {
                    cssMode: true,
                    wrapperClass: 'compare-page__products-wrapper',
                    slideClass: 'compare-page__product',
                    spaceBetween: 0,
                    slidesPerView: 4,
                    watchSlidesVisibility: true,
                    watchSlidesProgress: true,
                    loopFillGroupWithBlank: true,
                    navigation: {
                        prevEl: $compare_page.find('.compare-page__prev-arrow').get(0),
                        nextEl: $compare_page.find('.compare-page__next-arrow').get(0)
                    },
                    breakpoints: {
                        1199: {
                            slidesPerView: 3
                        }
                    }
                });
    
                var $products_features = $compare_page.find('.compare-page__products_features');
    
                var products_features_swiper = new Swiper($products_features.get(0), {
                    cssMode: true,
                    wrapperClass: 'compare-page__products-wrapper',
                    slideClass: 'compare-page__product',
                    spaceBetween: 0,
                    slidesPerView: 4,
                    watchSlidesVisibility: true,
                    watchSlidesProgress: true,
                    loopFillGroupWithBlank: true,
                    navignation: {},
                    breakpoints: {
                        1199: {
                            slidesPerView: 3
                        }
                    }
                });
    
                products_features_swiper.controller.control = products_header_swiper;
                products_header_swiper.controller.control = products_features_swiper;
            },
    
            initEventListeners: function () {
                $compare_page.on('change_view', '.compare-control', function (e, data) {
                    if (data.view === 'hide_same') {
                        self.hideSame();
                    } else {
                        self.showSame();
                    }
                });
    
                $compare_page.on('change_type', '.compare-control', function (e, data) {
                    self.replace(data.url);
                });
    
                $compare_page.on('delete', '.compare-product', function (e, data) {
                    var product_id = data.product_id;
                    CompareSet.remove(product_id);
    
                    if (_private.selected_type === 'all') {
                        self.replace('');
                    } else {
                        var _product_ids = _private.product_ids.filter(function (id) {
                            return id !== product_id;
                        });
    
                        if (_product_ids.length === 0) {
                            self.replace(config.shop.compare_url);
                        } else {
                            var url = config.shop.compare_id_url.replace('{$id}', _product_ids.join(','));
                            self.replace(url);
                        }
                    }
                });
    
                $compare_page.on('click', '.compare-control__delete-list-button', function () {
                    _private.product_ids.forEach(function (id) {
                        CompareSet.remove(id);
                    });
    
                    self.replace(config.shop.compare_url);
                });
    
                $(window).on('scroll', _private.handleScroll);
            },
    
            handleScroll: function () {
                self.updateFixedState();
            }
        };
    
        $.extend(self, {
            destruct: function () {
                $(window).off('scroll', _private.handleScroll);
            },
    
            updateFixedState: function () {
                var $header = $compare_page.find('.compare-page__header');
    
                if ($header.length === 0 || _private.product_ids.length === 0) {
                    return;
                }
    
                var is_fixed = $header.offset().top === window.scrollY;
    
                $compare_page.toggleClass('compare-page_fixed', is_fixed);
            },
    
            updateRowsHeight: function () {
                $compare_page.find('.compare-page__row_feature, .compare-page__row_divider').each(function () {
                    var $row = $(this);
                    var $rows = _private.rows_by_index[$row.index()] || $();
                    var max = 0;
    
                    $rows.height('auto');
    
                    $rows.each(function () {
                        var $row = $(this);
                        max = Math.max(max, $row.outerHeight());
                    });
    
                    $rows.outerHeight(max);
                });
            },
    
            showSame: function () {
                return $compare_page.removeClass('compare-page_hide-same');
            },
    
            hideSame: function () {
                return $compare_page.addClass('compare-page_hide-same');
            },
    
            replace: function (url) {
                $compare_page.addClass('compare-page_loading');
    
                $.ajax({
                    url: url
                }).then(function (response) {
                    var $new_compare_page = $(response).find('.compare-page');
                    $compare_page.replaceWith($new_compare_page);
                    HistoryUtil.replaceState({}, '', url);
                    Update($new_compare_page.parent());
                }, function () {
                    $compare_page.removeClass('compare-page_loading');
                });
            }
        });
    
        _private.initEventListeners();
        _private.initRows();
        _private.initSwiper();
    
        self.updateRowsHeight();
    });
    
    var CompareProduct = market_shop.CompareProduct = ComponentRegistry.register(function ($context) {
        return $context.select('.compare-product');
    }, function ($product) {
        var _private = {
            product_id: $product.data('product_id'),
    
            initEventListeners: function () {
                $product.on('click', '.compare-product__delete-button', function (e) {
                    e.preventDefault();
                    $product.trigger('delete', {
                        product_id: _private.product_id
                    });
                });
            }
        };
    
        _private.initEventListeners();
    });
    
    var CompareControl = market_shop.CompareControl = ComponentRegistry.register(function ($context) {
        return $context.select('.compare-control');
    }, function ($control) {
        var _private = {
            initEventListeners: function () {
                $control.find('input.compare-control__view').on('change', function () {
                    $control.trigger('change_view', {
                        view: $(this).val()
                    });
                });
    
                $control.find('select.compare-control__type').on('change', function () {
                    $control.trigger('change_type', {
                        url: $(this).val()
                    });
                });
            }
        };
    
        _private.initEventListeners();
    });
    
    var CartPage = market_shop.CartPage = ComponentRegistry.register(function ($context) {
        return $context.select('.cart-page');
    }, function ($page, self) {
        var _private = {
            isBuy1step: function () {
                return $page.find('.cart-summary').hasClass('cart-summary_buy1step');
            },
    
            initEventListeners: function () {
                $page.find('.cart-page__form').on('success@market:ajax-form', function (e, response) {
                    var $html = $(response);
                    var $new_summary_container = $html.find('.cart-page__container-summary');
                    $page.find('.cart-page__container-summary').replaceWith($new_summary_container);
                    Update($new_summary_container);
    
                    $('.cart-item__error').remove();
                    $('.quantity__input').prop('disabled', false);
                    $('.cart-item__container-quantity .quantity_error').removeClass('quantity_error');
    
                    var $items_errors = $html.find('.cart-item__error');
    
                    if ($items_errors.length) {
                        $items_errors.each(function () {
                            var $item_error = $(this);
                            var $cart_item = $item_error.closest('.cart-item');
                            var item_id = $cart_item.data('item_id');
                            var $original_cart_item = $('.cart-item').filter('[data-item_id="' + item_id + '"]');
    
                            if (!$original_cart_item.find('.cart-item__error').length) {
                                $original_cart_item.append($item_error);
                            }
                        });
                    }
    
                    var $no_available_hints = $html.find('.cart-item__no-available');
    
                    if ($no_available_hints.length) {
                        $no_available_hints.each(function () {
                            var $no_available_hint = $(this);
                            var $cart_item = $no_available_hint.closest('.cart-item');
                            var item_id = $cart_item.data('item_id');
                            var $original_cart_item = $('.cart-item').filter('[data-item_id="' + item_id + '"]');
    
                            if (!$original_cart_item.find('.cart-item__container-quantity .cart-item__no-available').length) {
                                $original_cart_item.find('.cart-item__container-quantity').append($no_available_hint);
                            }
    
                            if (!$original_cart_item.find('.cart-item__responsive-quantity-container .cart-item__no-available').length) {
                                $original_cart_item.find('.cart-item__responsive-quantity-container').append($no_available_hint);
                            }
    
                            $original_cart_item.find('.quantity__input').prop('disabled', true);
                        });
                    }
    
                    var $items_quantity_errors = $html.find('.cart-item__container-quantity .quantity_error');
    
                    if ($items_quantity_errors.length) {
                        $items_quantity_errors.each(function () {
                            var $item_quantity_error = $(this);
                            var $cart_item = $item_quantity_error.closest('.cart-item');
                            var item_id = $cart_item.data('item_id');
                            var $original_cart_item = $('.cart-item').filter('[data-item_id="' + item_id + '"]');
                            $original_cart_item.find('.quantity').addClass('quantity_error');
                        });
                    }
                });
    
                $page.find('.cart-page__clear-button').on('click', function () {
                    CartUtil.clear().then(function () {
                        var afterRefresh = null;
    
                        if (_private.isBuy1step()) {
                            afterRefresh = function () {
                                $('.buy1step-page__checkout-box').remove();
                            };
                        }
    
                        self.refresh(afterRefresh);
                    });
                });
    
                $page.on('change', '.cart-summary', function (e) {
                    e.stopPropagation();
                    var $target = $(e.target);
    
                    if ($(e.target).is('input[type="text"]') && !$target.attr('name') !== 'coupon_code') {
                        return false;
                    }
    
                    $page.find('.cart-page__form').trigger('submit');
                });
    
                $(document).on('shop_cart_add@market:global shop_cart_update@market:global shop_cart_delete@market:global', _private.handleCart);
    
                $(document).on('shop_cart_add_product@market:global', _private.handleCartAddProduct);
    
                $(document).on('change', '.cart-item', _private.handleChangeCartItem);
    
                if (_private.isBuy1step()) {
                    $(document).on('click', '.cart-summary__button', function (e) {
                        e.preventDefault();
                        e.stopPropagation();
    
                        ScrollUtil.scrollTo($('.buy1step-page__checkout-box').offset().top - 15);
                    });
                }
            },
    
            handleChangeCartItem: function (e) {
                if ($(e.target).closest($page).length === 0) {
                    self.refresh();
                }
            },
    
            handleCartAddProduct: function () {
                self.refresh();
            },
    
            handleCart: function (e, response) {
                var add_affiliate_bonus = 0;
                var add_affiliate_bonus_regexp = new RegExp('^' + config.shop.add_affiliate_bonus_string + '$');
    
                try {
                    add_affiliate_bonus = response.data.add_affiliate_bonus.match(add_affiliate_bonus_regexp)[1] || 0;
                } catch (ignored) { /* empty */ }
    
                self.setAddAffiliateBonus(add_affiliate_bonus);
                var currency_info = config.shop.currency_info[config.shop.currency];
                var affiliate_discount_raw = response.data.affiliate_discount && CurrencyUtil.parse(response.data.affiliate_discount, currency_info, false);
                affiliate_discount_raw = affiliate_discount_raw || 0;
                var discount_raw = CurrencyUtil.parse(response.data.discount, currency_info, false);
                var total_raw = CurrencyUtil.parse(response.data.total, currency_info, false);
                var subtotal_raw = total_raw + discount_raw;
    
                if (self.isUseAffiliate()) {
                    subtotal_raw += affiliate_discount_raw;
                }
    
                self.setAffiliateDiscount(response.data.affiliate_discount);
                self.setDiscount(response.data.discount);
                self.setTotal(response.data.total);
                self.setSubtotal(subtotal_raw);
    
                if (+response.data.count === 0) {
                    self.refresh();
                } else {
                    if ($page.data('is_enabled_refresh_shapes')) {
                        self.refreshShapes();
                    }
                }
            },
    
            getSummary: function () {
                return CartSummary($page.find('.cart-summary'));
            },
    
            refresh: function () {
                var deferred = $.Deferred();
    
                var request = $.ajax({
                    url: ''
                });
    
                request.then(function (response) {
                    var $response = $(response);
                    var $new_page = $response.find('.cart-page').add($response.filter('.cart-page'));
    
                    deferred.resolve($new_page);
                }, deferred.reject);
    
                return deferred;
            },
    
            touchCartItems: function ($new_page) {
                var $cart_items = $page.find('.cart-item');
                var $new_cart_items = $new_page.find('.cart-item');
                $cart_items.each(function () {
                    var $cart_item = $(this);
                    var item_id = $cart_item.data('item_id');
                    var $new_cart_item = $new_cart_items.filter('[data-item_id="' + item_id + '"]');
                    $cart_item.toggleClass('cart-item_removed', $new_cart_item.length === 0);
    
                    if ($new_cart_item.length) {
                        var replace_classnames = ['.cart-item__responsive-total-container .cart-item__total-price', '.cart-item__responsive-total-container .cart-item__total-compare-price', '.cart-item__responsive-total-container .cart-item__sub-total', '.cart-item__container-total .cart-item__total-price', '.cart-item__container-total .cart-item__total-compare-price', '.cart-item__container-total .cart-item__sub-total'];
                        replace_classnames.forEach(function (replace_classname) {
                            $cart_item.find(replace_classname).replaceWith($new_cart_item.find(replace_classname));
                        });
                        $cart_item.find('.quantity__input').val($new_cart_item.find('.quantity__input').val());
                    }
    
                    Update($cart_item);
                });
            }
        };
    
        $.extend(self, {
            isUseAffiliate: function () {
                return _private.getSummary().isUseAffiliate();
            },
    
            destruct: function () {
                $(document).off('shop:cart_add market:shop:cart_update market:shop:cart_delete', _private.handleCart);
    
                $(document).off('shop_cart_add_product@market:global', _private.handleCartAddProduct);
    
                $(document).off('change', '.cart-item', _private.handleChangeCartItem);
            },
    
            refresh: function (afterRefresh) {
                _private.refresh().then(function ($new_page) {
                    $page.replaceWith($new_page);
    
                    if (typeof afterRefresh === 'function') {
                        afterRefresh();
                    }
    
                    Update($new_page.parent());
                });
            },
    
            refreshShapes: function () {
                var $cart_items_container = $page.find('.cart-items-container');
                $cart_items_container.addClass('cart-items-container_loading');
    
                _private.refresh().then(function ($new_page) {
                    _private.touchCartItems($new_page);
                    $cart_items_container.removeClass('cart-items-container_loading');
                });
            },
    
            setTotal: function (total) {
                _private.getSummary().setTotal(total);
            },
    
            setDiscount: function (discount) {
                _private.getSummary().setDiscount(discount);
            },
    
            setAffiliateDiscount: function (affiliate_discount) {
                _private.getSummary().setAffiliateDiscount(affiliate_discount);
            },
    
            setAddAffiliateBonus: function (add_affiliate_bonus) {
                _private.getSummary().setAddAffiliateBonus(add_affiliate_bonus);
            },
    
            setSubtotal: function (subtotal) {
                _private.getSummary().setSubtotal(subtotal);
            }
        });
    
        _private.initEventListeners();
    });
    
    var CartSummary = market_shop.CartSummary = ComponentRegistry.register(function ($context) {
        return $context.select('.cart-summary');
    }, function ($summary, self) {
        var _private = {
            use_affiliate: !!$summary.data('use_affiliate'),
    
            initEventListeners: function () {
                $summary.find('input.cart-summary__use-affiliate-input').on('change', function () {
                    $summary.trigger('change');
                });
            }
        };
    
        $.extend(self, {
            isUseAffiliate: function () {
                return _private.use_affiliate;
            },
    
            setTotal: function (total) {
                $summary.find('.cart-summary__total').html(total);
            },
    
            setDiscount: function (discount) {
                $summary.find('.cart-summary__discount').html(discount);
            },
    
            setAffiliateDiscount: function (discount) {
                $summary.find('.cart-summary__affiliate-discount').html(discount);
            },
    
            setAddAffiliateBonus: function (add_affiliate_bonus) {
                $summary.find('.cart-summary__add-affiliate-bonus').html(add_affiliate_bonus + ' б.');
            },
    
            setSubtotal: function (subtotal) {
                var $row_value = $summary.find('.cart-summary__sub-total_sub-total').find('.row-value__value-text');
                var currency_info = config.shop.currency_info[config.shop.currency];
                $row_value.html(CurrencyUtil.format(subtotal, currency_info, false));
            },
    
            refresh: function () {
                $.ajax({
                    url: ''
                }).then(function (response) {
                    var $new_summary = $(response).find('.cart-summary');
                    $summary.replaceWith($new_summary);
    
                    Update($new_summary.parent());
                });
            }
        });
    
        _private.initEventListeners();
    });
    
    var CartItem = market_shop.CartItem = ComponentRegistry.register(function ($context) {
        return $context.select('.cart-item');
    }, function ($item, self) {
        var _private = {
            initEventListeners: function () {
                $item.on('change', '.quantity', function (e) {
                    e.stopPropagation();
    
                    var quantity = Quantity($(this));
    
                    CartUtil.updateItem(self.getId(), quantity.getValue(), true).then(function (response) {
                        if (response.data.error) {
                            _private.setQuantityValue(quantity.getValue());
                            InfoPanelUtil.showMessage(response.data.error);
    
                            return;
                        }
    
                        $item.find('.cart-item__quantity').text(quantity.getValue());
                        _private.setQuantityValue(quantity.getValue());
                        self.setTotal(response.data.item_total);
                        $item.trigger('change');
                        $item.trigger('change@market:cart-item');
                    });
                });
    
                $item.on('click', '.cart-item__delete-button', function () {
                    CartUtil.deleteItem(self.getId()).then(function () {
                        $item.trigger('delete@market:cart-item');
                        $item.remove();
                    });
                });
    
                $item.on('check@market:service', '.service', function () {
                    var $service = $(this);
                    var service = Service($service);
    
                    CartUtil.addService(self.getId(), service.getServiceId(), service.getServiceVariantId()).then(function (response) {
                        self.setTotal(response.data.item_total);
                        $service.data('item_id', response.data.id);
                        $item.trigger('change');
                        $item.trigger('change@market:cart-item');
                    });
                });
    
                $item.on('uncheck@market:service', '.service', function () {
                    var $service = $(this);
    
                    CartUtil.deleteItem($service.data('item_id')).then(function (response) {
                        self.setTotal(response.data.item_total);
                        $service.removeData('item_id');
                        $item.trigger('change');
                        $item.trigger('change@market:cart-item');
                    });
                });
    
                $item.on('change@market:service', '.service', function () {
                    var $service = $(this);
                    var service = Service($service);
    
                    CartUtil.updateService($service.data('item_id'), service.getServiceVariantId()).then(function (response) {
                        self.setTotal(response.data.item_total);
                        $item.trigger('change');
                        $item.trigger('change@market:cart-item');
                    });
                });
            },
    
            setQuantityValue: function (value) {
                $item.find('.quantity').each(function () {
                    var quantity = Quantity($(this));
                    quantity.setValue(value);
                });
            }
        };
    
        $.extend(self, {
            getId: function () {
                return $item.data('item_id');
            },
    
            setTotal: function (total) {
                $item.find('.cart-item__total-price').html(total);
            }
        });
    
        _private.initEventListeners();
    });
    
    market_shop.CartItems = ComponentRegistry.register(function ($context) {
        return $context.select('.cart-items');
    }, function ($items) {
        var _private = {
            initEventListeners: function () {
                $items.on('delete@market:cart-item', '.cart-item', function () {
                    var $item = $(this);
                    var $item_wrapper = $item.closest('.cart-items__item');
                    $item_wrapper.remove();
    
                    if (_private.isBuy1step() && $items.find('.cart-item').length === 0) {
                        $('.buy1step-page__checkout-box').remove();
                    }
                });
            },
    
            isBuy1step: function () {
                return $('.cart-summary').hasClass('cart-summary_buy1step');
            }
        };
    
        _private.initEventListeners();
    });
    
    var CartItemModal = market_shop.CartItemModal = ComponentRegistry.register(function ($context) {
        return $context.select('.cart-item-modal');
    }, function ($modal, self) {
        var _private = {
            initEventListeners: function () {
                $modal.find('.cart-item-modal__continue-button').on('click', function () {
                    ModalUtil.close();
                });
    
                $(document).on('shop_cart_add@market:global', _private.handleCartChange);
    
                $(document).on('shop_cart_update@market:global', _private.handleCartChange);
    
                $(document).on('shop_cart_delete@market:global', _private.handleCartChange);
    
                $(document).on('shop_cart_clear@market:global', _private.handleCartClean);
            },
    
            handleCartChange: function (e, response) {
                self.changeCount(response.data.count);
                self.changeTotal(response.data.total);
            },
    
            handleCartClean: function () {
                self.changeCount(0);
                self.changeTotal(0);
            }
        };
    
        $.extend(self, {
            destruct: function () {
                $(document).off('shop_cart_add@market:global', _private.handleCartChange);
    
                $(document).off('shop_cart_update@market:global', _private.handleCartChange);
    
                $(document).off('shop_cart_delete@market:global', _private.handleCartChange);
    
                $(document).off('shop_cart_clear@market:global', _private.handleCartClean);
            },
    
            changeCount: function (count) {
                $modal.find('.cart-item-modal__count').text(count + ' ' + PluralUtil.getPluralValue('products', count));
            },
    
            changeTotal: function (total) {
                $modal.find('.cart-item-modal__total').html(total);
            }
        });
    
        _private.initEventListeners();
    });
    
    var CheckoutPage = market_shop.CheckoutPage = ComponentRegistry.register(function ($context) {
        return $context.select('.checkout-page');
    }, function ($page, self) {
        $.extend(self, {
            initEventListeners: function () {
                $page.find('.checkout-page__back-button').on('click', function () {
                    window.location = $(this).data('href');
                });
    
                $page.on('click', '.checkout-page__responsive-step', function () {
                    var $step = $(this);
                    window.location = $step.data('step_href');
                });
            },
    
            preventDoubleCheckout: function () {
                var stepBlock = $page.find('.checkout-page__step-container');
                var form = stepBlock.find('.checkout-page__form_last');
    
                if (form) {
                    var is_locked = false;
    
                    form.on('submit', function (event) {
                        if (!is_locked) {
                            is_locked = true;
                            form.find('button').attr('disabled', 'disabled');
                            stepBlock.addClass('checkout-page__step-container_loading');
    
                            return true;
                        }
    
                        return false;
                    });
                }
            }
        });
    
        self.initEventListeners();
        self.preventDoubleCheckout();
    });
    
    market_shop.CheckoutSummary = ComponentRegistry.register(function ($context) {
        return $context.select('.checkout-summary');
    }, function ($summary, self) {
        var _private = {
            total: $summary.data('total'),
            initGlobalEventListeners: function () {
                $(document).on('check@market:checkout-shipping-method update_rate@market:checkout-shipping-method', '.checkout-shipping-method', function () {
                    _private.updateTotal();
                });
            },
    
            updateTotal: function () {
                $('.checkout-shipping-method').each(function () {
                    var method = market_shop.CheckoutShippingMethod($(this));
    
                    if (method.isChecked()) {
                        var rate = method.getRate();
                        var rate_raw = rate ? parseFloat(rate['rate_raw']) : 0;
                        var is_free = method.isFree();
    
                        self.setShipping(rate_raw, is_free);
                        self.setTotal(_private.total + (is_free ? 0 : rate_raw));
                    }
                });
            },
    
            destroyGlobalEventListeners: function () {
    
            }
        };
    
        $.extend(self, {
            setShipping: function (shipping, is_free) {
                var $shipping_sub_total = $summary.find('.checkout-summary__sub-total_shipping');
                var $row_value = $shipping_sub_total.find('.row-value__value-text');
                var format_shipping = is_free ? 'Бесплатно' : CurrencyUtil.format(shipping, config.shop.currency_info[config.shop.currency]);
    
                $row_value.html(format_shipping);
                $summary.toggleClass('checkout-summary_has-shipping', !!shipping || is_free);
            },
    
            setTotal: function (total) {
                var format_total = CurrencyUtil.format(total, config.shop.currency_info[config.shop.currency]);
                $summary.find('.checkout-summary__total').html(format_total);
            }
        });
    
        _private.initGlobalEventListeners();
        _private.updateTotal();
    });
    
    var CheckoutSteps = market_shop.CheckoutSteps = ComponentRegistry.register(function ($context) {
        return $context.select('.checkout-steps');
    }, function ($steps, self) {
        $.extend(self, {
            initEventListeners: function () {
                $steps.find('.checkout-steps__step').on('click', function () {
                    var $step = $(this);
                    window.location = $step.data('step_href');
                });
            }
        });
    
        self.initEventListeners();
    });
    
    var CheckoutContactinfo = market_shop.CheckoutContactinfo = ComponentRegistry.register(function ($context) {
        return $context.select('.checkout-contactinfo');
    }, function ($contactinfo, self) {
        $.extend(self, {
            initEventListeners: function () {
                $contactinfo.find('.checkout-contactinfo__billing-matches-shipping-button').on('click', function () {
                    $contactinfo.removeClass('checkout-contactinfo_billing-matches-shipping');
                    $contactinfo.find('.checkout-contactinfo__billing-matches-shipping').remove();
                });
            }
        });
    
        self.initEventListeners();
    });
    
    var CheckoutMethod = market_shop.CheckoutMethod = ComponentRegistry.register(function ($context) {
        return $context.select('.checkout-method');
    }, function ($method, self) {
        $.extend(self, {
            initEventListeners: function () {
                $method.find('.checkout-method__control').on('change', function () {
                    $(document).trigger('shop:checkout_method_change');
                });
    
                $(document).on('shop:checkout_method_change', function () {
                    self.updateState();
                });
    
                self.updateState();
            },
            getControl: function () {
                return $method.find('input.checkout-method__control');
            },
            updateState: function () {
                $method.toggleClass('checkout-method_checked', self.isChecked());
            },
            enable: function () {
                $method.removeClass('checkout-method_disabled');
            },
            disable: function () {
                $method.addClass('checkout-method_disabled');
                self.getControl().prop('checked', false);
                self.updateState();
            },
            isChecked: function () {
                return self.getControl().prop('checked');
            }
        });
    
        self.initEventListeners();
    });
    
    var CheckoutShippingMethod = market_shop.CheckoutShippingMethod = ComponentRegistry.register(function ($context) {
        return $context.select('.checkout-shipping-method');
    }, function ($method, self) {
        var _private = {
            shipping_id: $method.data('shipping_id'),
            shipping_rates: $method.data('shipping_rates'),
            initEventListeners: function () {
                var $control = $method.find('.checkout-shipping-method__control');
    
                $control.on('change', function () {
                    self.updateState();
                });
    
                var $rates = $method.find('select.checkout-shipping-method__rates');
    
                $rates.on('change', function () {
                    self.updateRate();
                });
    
                $method.on('click', function (e) {
                    if (self.isChecked()) {
                        return;
                    }
    
                    if ($(e.target).closest('.checkout-shipping-method__label, .checkout-shipping-method__content-container').length > 0) {
                        return;
                    }
    
                    $control.trigger('click');
                });
    
                $method.on('change', '.checkout-shipping-method__content-container', function () {
                    self.send().then(function () {
                        self.updateRates();
                    });
                });
    
                $(document).on('check@market:checkout-shipping-method', '.checkout-shipping-method', _private.handleCheck);
            },
    
            handleCheck: function () {
                self.updateState();
            },
    
            setRate: function (rate) {
                $method.find('.checkout-shipping-method__price-container').html(rate || '');
                $method.find('.checkout-shipping-method__responsive-price-container').html(rate || '');
            },
    
            setComment: function (comment) {
                var $container = $method.find('.checkout-shipping-method__comment-container');
                $container.html(comment);
                $container.toggleClass('checkout-shipping-method__comment-container_empty', !comment);
            },
    
            setError: function (error) {
                var $container = $method.find('.checkout-shipping-method__error-container');
                $container.html(error);
                $container.toggleClass('checkout-shipping-method__error-container_empty', !error);
            },
    
            setEstDelivery: function (est_delivery) {
                var $container = $method.find('.checkout-shipping-method__est-delivery-container');
                var $est_delivery = $container.find('.checkout-shipping-method__est-delivery');
                $est_delivery.html(est_delivery);
                $container.toggleClass('checkout-shipping-method__est-delivery-container_empty', !est_delivery);
            },
    
            getRate: function () {
                var $rates = $method.find('select.checkout-shipping-method__rates');
    
                if (!_private.shipping_rates) {
                    return null;
                }
    
                var rate = _private.shipping_rates.find(function (rate) {
                    return rate.id == $rates.val();
                });
    
                if (!rate) {
                    return null;
                }
    
                return rate;
            },
    
            handleShippingRates: function (shipping_rates) {
                shipping_rates.forEach(function (rate) {
                    if (rate.rate === null) {
                        rate.rate_raw = 0;
    
                        return;
                    } else if (!isNaN(rate.rate) || !rate.currency) {
                        rate.rate_raw = rate.rate;
    
                        return;
                    }
    
                    rate.rate_raw = CurrencyUtil.parse(rate.rate, config.shop.currency_info[config.shop.currency], true);
                });
            }
        };
    
        $.extend(self, {
            destruct: function () {
                $(document).off('check', '.checkout-shipping-method', _private.handleCheck);
            },
    
            updateState: function () {
                var $control = $method.find('.checkout-shipping-method__control');
    
                if (!$method.hasClass('checkout-shipping-method_checked') && $control.prop('checked')) {
                    self.check();
                } else if ($method.hasClass('checkout-shipping-method_checked') && !$control.prop('checked')) {
                    self.uncheck();
                }
            },
    
            isChecked: function () {
                self.updateState();
    
                return $method.hasClass('checkout-shipping-method_checked');
            },
    
            isLoaded: function () {
                return _private.shipping_rates !== undefined;
            },
    
            isFree: function () {
                var rate = _private.getRate();
                var rate_raw = rate ? parseFloat(rate['rate_raw']) : 0;
    
                return rate_raw === 0 || this.isFreedelivery();
            },
    
            isFreedelivery: function () {
                return !!$method.find('.freedelivery-new-price').length;
            },
    
            load: function () {
                var deferred = $.Deferred();
    
                var request = $.ajax({
                    url: config.shop.data_shipping_url,
                    data: {
                        shipping_id: [_private.shipping_id],
                        html: true
                    }
                });
    
                request.then(function (response) {
                    if (typeof response.data[_private.shipping_id] === 'string') {
                        _private.setError(response.data[_private.shipping_id]);
                        deferred.reject(response);
    
                        return;
                    }
    
                    _private.setError('');
                    _private.shipping_rates = response.data[_private.shipping_id];
                    _private.handleShippingRates(_private.shipping_rates);
                    deferred.resolve(response);
                }, deferred.reject);
    
                return deferred;
            },
    
            send: function () {
                var $form = $method.closest('form');
    
                var deferred = $.Deferred();
    
                var request = $.ajax({
                    url: config.shop.data_shipping_url,
                    method: 'post',
                    data: $form.serialize()
                });
    
                request.then(function (response) {
                    if (typeof response.data === 'string') {
                        _private.setError(response.data);
                        deferred.reject(response);
    
                        return;
                    }
    
                    _private.setError('');
                    _private.shipping_rates = response.data;
                    _private.handleShippingRates(_private.shipping_rates);
                    deferred.resolve(response);
                }, deferred.reject);
    
                return deferred;
            },
    
            check: function () {
                $method.addClass('checkout-shipping-method_checked');
                $method.trigger('check@market:checkout-shipping-method');
                $method.get(0).offsetParent;
    
                if (!ResponsiveUtil.isDesktopMin()) {
                    ScrollUtil.scrollTo($method.offset()['top'] - 60 - 15);
                }
            },
    
            trigger: function () {
                $method.find('.checkout-shipping-method__control').trigger('change');
            },
    
            uncheck: function () {
                $method.removeClass('checkout-shipping-method_checked');
                $method.trigger('uncheck@market:checkout-shipping-method');
            },
    
            updateRates: function () {
                var $rates = $method.find('select.checkout-shipping-method__rates');
                $rates.empty();
    
                _private.shipping_rates.forEach(function (rate) {
                    var $option = $('<option></option>');
                    $option.val(rate.id);
                    $option.text(rate.name + ' (' + rate.rate + ')');
                    $rates.append($option);
                });
    
                $method.find('.checkout-shipping-method__rates-container').toggleClass('checkout-shipping-method__rates-container_multi', _private.shipping_rates.length > 1);
    
                self.updateRate();
            },
    
            updateRate: function () {
                var rate = _private.getRate();
    
                if (!rate) {
                    return;
                }
    
                _private.setRate(rate.rate_html);
                _private.setComment(rate.comment);
                _private.setEstDelivery(rate.est_delivery);
    
                $method.trigger('update_rate@market:checkout-shipping-method');
            },
    
            getRate: function () {
                return _private.getRate();
            }
        });
    
        _private.initEventListeners();
    
        if (!self.isLoaded()) {
            self.load().then(function () {
                self.updateRates();
                $method.removeClass('checkout-shipping-method_loading');
            }, function () {
                $method.removeClass('checkout-shipping-method_loading');
            });
        }
    });
    
    market_shop.CheckoutShipping = ComponentRegistry.register(function ($context) {
        return $context.select('.checkout-shipping');
    }, function ($shipping) {
        var _private = {
            getMethod: function () {
                var $method = $shipping.find('.checkout-shipping-method_checked');
    
                return CheckoutShippingMethod($method);
            }
        };
    
        if ($shipping.data('is_shipping_changed')) {
            _private.getMethod().trigger();
        }
    });
    
    var CheckoutPaymentMethod = market_shop.CheckoutPaymentMethod = ComponentRegistry.register(function ($context) {
        return $context.select('.checkout-payment-method');
    }, function ($method, self) {
        var _private = {
            initEventListeners: function () {
                var $control = $method.find('.checkout-payment-method__control');
    
                $control.on('change', function () {
                    self.updateState();
                });
    
                $method.on('click', function (e) {
                    if ($(e.target).closest('.checkout-payment-method__label, .checkout-payment-method__content-container').length > 0) {
                        return;
                    }
    
                    $control.trigger('click');
                });
    
                $(document).on('check', '.checkout-payment-method', _private.handleCheck);
    
                self.synchronizeViewedWithInput();
            },
    
            handleCheck: function () {
                self.updateState();
            }
        };
    
        $.extend(self, {
            destruct: function () {
                $(document).off('check', '.checkout-payment-method', _private.handleCheck);
            },
    
            updateState: function () {
                if (!self.isViewedSynchronizedWithInput()) {
                    self.synchronizeViewedWithInput();
                    self.triggerCheckEvent();
                }
            },
    
            isViewedSynchronizedWithInput: function () {
                return self.isInputChecked() === self.isViewChecked();
            },
    
            synchronizeViewedWithInput: function () {
                if (self.isInputChecked()) {
                    self.viewChecked();
                } else {
                    self.viewUnchecked();
                }
            },
    
            triggerCheckEvent: function () {
                var event_name = self.isInputChecked()
                    ? 'check'
                    : 'uncheck';
    
                $method.trigger(event_name);
            },
    
            isInputChecked: function () {
                return $method.find('.checkout-payment-method__control').prop('checked');
            },
    
            isViewChecked: function () {
                return $method.hasClass('checkout-payment-method_checked');
            },
    
            viewChecked: function () {
                $method.addClass('checkout-payment-method_checked');
            },
    
            viewUnchecked: function () {
                $method.removeClass('checkout-payment-method_checked');
            }
        });
    
        _private.initEventListeners();
    });
    
    var CheckoutSignup = market_shop.CheckoutSignup = ComponentRegistry.register(function ($context) {
        return $context.select('.checkout-signup');
    }, function ($signup, self) {
        $.extend(self, {
            initEventListeners: function () {
                self.getControl().on('change', function () {
                    self.updateState();
                });
    
                self.updateState();
            },
            updateState: function () {
                $signup.toggleClass('checkout-signup_active', self.isActive());
            },
            isActive: function () {
                return self.getControl().prop('checked');
            },
            getControl: function () {
                return $signup.find('input.checkout-signup__control');
            }
        });
    
        self.initEventListeners();
    });
    
    var ReviewsThumbsSlider = market_shop.ReviewsThumbsSlider = ComponentRegistry.register(function ($context) {
        return $context.select('.reviews-thumbs_slider');
    }, function ($reviews_thumbs) {
        var _private = {
            initSwiper: function () {
                var $sliderBlock = $reviews_thumbs.closest('.slider-block');
    
                var swiper = new Swiper($reviews_thumbs.get(0), {
                    cssMode: true,
                    slidesPerView: 'auto',
                    watchSlidesVisibility: true,
                    watchSlidesProgress: true,
                    navigation: {
                        prevEl: $sliderBlock.find('.slider-block__prev-button').get(0),
                        nextEl: $sliderBlock.find('.slider-block__next-button').get(0)
                    }
                });
    
                $reviews_thumbs.trigger('init@market:swiper', swiper);
            }
        };
        _private.initSwiper();
    });
    
    var BrandsThumbsSlider = market_shop.BrandsThumbsSlider = ComponentRegistry.register(function ($context) {
        return $context.select('.brands-thumbs_slider');
    }, function ($brands_thumbs) {
        var _private = {
            initSwiper: function () {
                var $sliderBlock = $brands_thumbs.closest('.slider-block');
    
                var swiper = new Swiper($brands_thumbs.get(0), {
                    cssMode: true,
                    slidesPerView: 'auto',
                    watchSlidesVisibility: true,
                    watchSlidesProgress: true,
                    navigation: {
                        prevEl: $sliderBlock.find('.slider-block__prev-button').get(0),
                        nextEl: $sliderBlock.find('.slider-block__next-button').get(0)
                    }
                });
    
                $brands_thumbs.trigger('init@market:swiper', swiper);
            }
        };
    
        _private.initSwiper();
    });
    
    var PostsThumbsSlider = market_shop.PostsThumbsSlider = ComponentRegistry.register(function ($context) {
        return $context.select('.posts-thumbs_slider');
    }, function ($posts_thumbs) {
        var _private = {
            initSwiper: function () {
                var $sliderBlock = $posts_thumbs.closest('.slider-block');
    
                var swiper = new Swiper($posts_thumbs.get(0), {
                    cssMode: true,
                    slidesPerView: 'auto',
                    watchSlidesVisibility: true,
                    watchSlidesProgress: true,
                    navigation: {
                        prevEl: $sliderBlock.find('.slider-block__prev-button').get(0),
                        nextEl: $sliderBlock.find('.slider-block__next-button').get(0)
                    }
                });
    
                $(document).trigger('swiper_lazyload@market:global', swiper);
                $posts_thumbs.trigger('market:init.market:swiper', swiper);
            }
        };
    
        _private.initSwiper();
    });
    
    var FavoritePage = market_shop.FavoritePage = ComponentRegistry.register(function ($context) {
        return $context.select('.favorite-page');
    }, function ($page, self) {
        $.extend(self, {
            pageSet: $page.hasClass('favorite-page_recently-viewed') ? RecentlySet : FavoriteSet,
            initEventListeners: function () {
                $page.find('.favorite-page__r-categories').on('change', function () {
                    window.location = $(this).val();
                });
    
                var $products = $page.find('.favorite-page__products');
    
                $page.find('.favorite-page__r-clear-button').on('click', function () {
                    self.deleteAll();
                });
    
                $page.find('.favorite-page__r-recovery-button').on('click', function () {
                    self.recoveryAll();
                });
    
                $products.on('delete', function () {
                    self.deleteAll();
                });
                $products.on('recovery', function () {
                    self.recoveryAll();
                });
    
                $products.on('delete_product', function (e, $product) {
                    var product_id = $product.data('product_id');
                    self.pageSet.remove(product_id);
                });
    
                $products.on('recovery_product', function (e, $product) {
                    var product_id = $product.data('product_id');
                    self.pageSet.add(product_id);
                });
    
                $(document).on('shop_update_favorite@market:global', function () {
                    $products.find('.product-thumb').each(function () {
                        var product = Product($(this));
    
                        if (FavoriteSet.has(product.getProductId())) {
                            product.recovery();
                        } else {
                            product.delete();
                        }
                    });
                });
    
                $(document).on('shop_update_recently@market:global', function () {
                    $products.find('.product-thumb').each(function () {
                        var product = Product($(this));
    
                        if (RecentlySet.has(product.getProductId())) {
                            product.recovery();
                        } else {
                            product.delete();
                        }
                    });
                });
            },
    
            deleteAll: function () {
                var product_ids = $page.data('product_ids');
                product_ids.forEach(function (product_id) {
                    self.pageSet.remove(product_id);
                });
    
                $page.addClass('favorite-page_delete');
            },
    
            recoveryAll: function () {
                var product_ids = $page.data('product_ids');
                product_ids.forEach(function (product_id) {
                    self.pageSet.add(product_id);
                });
    
                $page.removeClass('favorite-page_delete');
            }
        });
    
        self.initEventListeners();
    });
    
    var AddImageSection = market_shop.AddImageSection = ComponentRegistry.register(function ($context) {
        return $context.select('.add-images-section');
    }, function ($block, self) {
        $.extend(self, {
            $file_field: $block.find('.js-file-field'),
            $files_wrapper: $block.find('.js-attached-files-section'),
            $errors_wrapper: $block.find('.js-errors-section'),
            max_post_size: $block.data('max_post_size'),
            max_file_size: $block.data('max_file_size'),
            max_files: $block.data('max_files'),
            templates: {
                file: $block.data('template_file'),
                error: $block.data('template_error')
            },
            locales: {
                files_limit: 'Вы можете загрузить макисмум ' + $block.data('max_files') + ' изображений.',
                file_type: 'Неподдерживаемый тип изображения. Разрешается только PNG, GIF и JPEG.',
                post_size: 'Общий вес изображений не может быть больше чем ' + (Math.floor($block.data('max_post_size') * 10 / (1024)) / 10) + 'KB.',
                file_size: 'Вес одного изображения не может быть больше чем ' + (Math.floor($block.data('max_file_size') * 10 / (1024)) / 10) + 'KB.'
            },
            post_size: 0,
            id_counter: 0,
            files_data: {},
            images_count: 0,
    
            initEventListeners: function () {
                var that = self,
                    $document = $(document);
    
                $block.data('controller', self);
    
                that.$file_field.on('change', function () {
                    addFiles(this.files);
                    that.$file_field.val('');
                });
    
                $block.on('click', '.js-show-textarea', function (event) {
                    event.preventDefault();
                    $(this).closest('.description-wrapper').addClass('is-extended');
                });
    
                $block.on('click', '.js-delete-file', function (event) {
                    event.preventDefault();
                    var $file = $(this).closest('.file-wrapper'),
                        file_id = '' + $file.data('file-id');
    
                    if (file_id && that.files_data[file_id]) {
                        var file_data = that.files_data[file_id];
                        that.post_size -= file_data.file.size;
                        delete that.files_data[file_id];
                        that.images_count -= 1;
                    }
    
                    $file.remove();
    
                    that.renderErrors();
                });
    
                $block.on('keyup change', '.js-textarea', function (event) {
                    var $textarea = $(this),
                        $file = $textarea.closest('.s-file-wrapper'),
                        file_id = '' + $file.data('file-id');
    
                    if (file_id && that.files_data[file_id]) {
                        var file = that.files_data[file_id];
                        file.desc = $textarea.val();
                    }
                });
    
                var timeout = null,
                    is_entered = false;
    
                $document.on('dragover', dragWatcher);
    
                function dragWatcher(event) {
                    var is_exist = $.contains(document, $block);
    
                    if (is_exist) {
                        onDrag(event);
                    } else {
                        $document.off('dragover', dragWatcher);
                    }
                }
    
                $document.on('drop', dropWatcher);
    
                function dropWatcher(event) {
                    var is_exist = $.contains(document, $block);
    
                    if (is_exist) {
                        onDrop(event);
                    } else {
                        $document.off('drop', dropWatcher);
                    }
                }
    
                $document.on('reset clear', resetWatcher);
    
                function resetWatcher(event) {
                    var is_exist = $.contains(document, $block);
    
                    if (is_exist) {
                        that.reset();
                    } else {
                        $document.off('reset clear', resetWatcher);
                    }
                }
    
                function onDrop(event) {
                    event.preventDefault();
    
                    var files = event.originalEvent.dataTransfer.files;
    
                    addFiles(files);
                    dropToggle(false);
                }
    
                function onDrag(event) {
                    event.preventDefault();
    
                    if (!timeout) {
                        if (!is_entered) {
                            is_entered = true;
                            dropToggle(true);
                        }
                    } else {
                        clearTimeout(timeout);
                    }
    
                    timeout = setTimeout(function () {
                        timeout = null;
                        is_entered = false;
                        dropToggle(false);
                    }, 100);
                }
    
                function dropToggle(show) {
                    var active_class = 'is-highlighted';
    
                    if (show) {
                        that.addClass(active_class);
                    } else {
                        that.removeClass(active_class);
                    }
                }
    
                function addFiles(files) {
                    var errors_types = [],
                        errors = [];
    
                    $.each(files, function (i, file) {
                        var response = that.addFile(file);
    
                        if (response.error) {
                            var error = response.error;
    
                            if (errors_types.indexOf(error.type) < 0) {
                                errors_types.push(error.type);
                                errors.push(error);
                            }
                        }
                    });
    
                    that.renderErrors(errors);
                }
            },
            addFile: function (file) {
                var that = this,
                    file_size = file.size;
    
                var image_type = /^image\/(png|jpe?g|gif)$/,
                    is_image = (file.type.match(image_type));
    
                if (!is_image) {
                    return {
                        error: {
                            text: that.locales['file_type'],
                            type: 'file_type'
                        }
                    };
                } else if (that.images_count >= that.max_files) {
                    return {
                        error: {
                            text: that.locales['files_limit'],
                            type: 'files_limit'
                        }
                    };
                } else if (file_size >= that.max_file_size) {
                    return {
                        error: {
                            text: that.locales['file_size'],
                            type: 'file_size'
                        }
                    };
                } else if (that.post_size + file_size >= that.max_file_size) {
                    return {
                        error: {
                            text: that.locales['post_size'],
                            type: 'post_size'
                        }
                    };
                } else {
                    that.post_size += file_size;
    
                    var file_id = that.id_counter, file_data = {
                        id: file_id,
                        file: file,
                        desc: ''
                    };
    
                    that.files_data[file_id] = file_data;
    
                    that.id_counter++;
                    that.images_count += 1;
    
                    render();
    
                    return file_data;
                }
    
                function render() {
                    var $template = $(that.templates['file']),
                        $image = $template.find('.image-wrapper');
    
                    $template.attr('data-file-id', file_id);
    
                    getImageUri().then(function (image_uri) {
                        $image.css('background-image', 'url(' + image_uri + ')');
                    });
    
                    that.$files_wrapper.append($template);
    
                    function getImageUri() {
                        var deferred = $.Deferred(),
                            reader = new FileReader();
    
                        reader.onload = function (event) {
                            deferred.resolve(event.target.result);
                        };
    
                        reader.readAsDataURL(file);
    
                        return deferred.promise();
                    }
                }
            },
    
            reset: function () {
                var that = this;
    
                that.post_size = 0;
                that.id_counter = 0;
                that.files_data = {};
    
                that.$files_wrapper.html('');
                that.$errors_wrapper.html('');
            },
            getSerializedArray: function () {
                var that = this,
                    result = [];
    
                var index = 0;
                var inputName = self.$file_field.attr('name');
    
                $.each(that.files_data, function (file_id, file_data) {
                    var file_name = inputName + '[' + index + ']',
                        desc_name = inputName + '_data[' + index + '][description]';
    
                    result.push({
                        name: file_name,
                        value: file_data.file
                    });
    
                    result.push({
                        name: desc_name,
                        value: file_data.desc
                    });
    
                    index++;
                });
    
                return result;
            },
            renderErrors: function (errors) {
                var that = this,
                    result = [];
    
                that.$errors_wrapper.html('');
    
                if (errors && errors.length) {
                    $.each(errors, function (i, error) {
                        if (error.text) {
                            var $error = $(that.templates['error'].replace('%text%', error.text));
                            $error.appendTo(that.$errors_wrapper);
                            result.push($error);
                        }
                    });
                }
    
                return result;
            }
        });
    
        self.initEventListeners();
    });
    
    var ProductReview = market_shop.ProductReview = ComponentRegistry.register(function ($context) {
        return $context.select('.product-review');
    }, function ($review, self) {
        var is_open_form = false;
        var id = $review.data('review_id');
        var $reviewFormInput = $('input[name="product_review_reply_form"]').first();
    
        $.extend(self, {
            initEventListeners: function () {
                $review.find('.product-review__reply-button:first').on('click', function () {
                    if (is_open_form) {
                        return;
                    }
    
                    is_open_form = true;
                    var $replies = $review.children('.product-review__replies');
                    var $reply = $('<div class="product-review__reply"></div>');
                    var $form = $($reviewFormInput.val());
                    $form.data('parent_id', id);
                    $form.on('close', function () {
                        $reply.remove();
                        is_open_form = false;
                        self.updateHasReplies();
                    });
    
                    $form.on('reply_added', function (e, data) {
                        is_open_form = false;
                        $reply.html(data.html);
                        $replies.append($reply);
    
                        Update($reply);
                    });
    
                    $form.on('reply_sent', function (e, data) {
                        setTimeout(function () {
                            is_open_form = false;
                            $reply.remove();
                        }, 5000);
                    });
    
                    $form.appendTo($reply);
                    $replies.prepend($reply);
                    self.updateHasReplies();
    
                    Update($review);
                });
            },
            updateHasReplies: function () {
                var $replies = $review.children('.product-review__replies').children();
                $review.toggleClass('product-review_has-replies', $replies.length > 0);
            }
        });
    
        self.initEventListeners();
    });
    
    var ProductReviewForm = market_shop.ProductReviewForm = ComponentRegistry.register(function ($context) {
        return $context.select('.product-review-form');
    }, function ($form, self) {
        $.extend(self, {
            initForm: function () {
                $form.find('.product-review-form__parent-id').val($form.data('parent_id'));
            },
            initEventListeners: function () {
                $form.on('success@market:ajax-form', function (e, response) {
                    var _response = response;
    
                    if (typeof _response !== 'object') {
                        var JSONResponse = JSON.parse(response);
    
                        if (JSONResponse['status']) {
                            _response = JSONResponse;
                        }
                    }
    
                    if (_response.status !== 'ok') {
                        return;
                    }
    
                    var $addReviewForm = $(e.target);
                    var isModerate = $addReviewForm.find('input[name="moderate"]').length > 0;
                    var isReply = $addReviewForm.find('input[name="parent_id"]').length > 0;
    
                    market.Analytics.reachGoal('add_review');
    
                    if (isModerate) {
                        $form.find('form').remove();
                        $form.find('.product-review-form__notice').show();
    
                        if (isReply) {
                            $form.trigger('reply_sent');
                        }
                    } else {
                        if (isReply) {
                            $form.trigger('reply_added', _response.data);
                        } else {
                            $form.trigger('review_added', _response.data);
                            ModalUtil.close();
                        }
                    }
                });
    
                $form.find('.product-review-form__close-button').on('click', function () {
                    $form.trigger('close');
                });
    
                $form.on('click', '.product-review-form__responsive-close-button', function () {
                    $form.trigger('close');
                });
            }
        });
    
        self.initForm();
        self.initEventListeners();
    });
    
    var ProductReviewAddButton = market_shop.ProductReviewAddButton = ComponentRegistry.register(function ($context) {
        return $context.select('.product-review-add-button');
    }, function ($button, self) {
        var $reviewForm = $('input[name="product_review_form"]').first().val();
    
        $.extend(self, {
            initEventListeners: function () {
                $button.on('click', function () {
                    ModalUtil.openContent($reviewForm, { title: market.config.language['write_a_review'] });
                });
            }
        });
    
        self.initEventListeners();
    });
    
    var ProductReviews = market_shop.ProductReviews = ComponentRegistry.register(function ($context) {
        return $context.select('.product-reviews');
    }, function ($reviews, self) {
        var filter_rate = null;
        var src_header = null;
        var isReviewsPlugin = $reviews.data('plugin') === 'reviews';
    
        $.extend(self, {
            initEventListeners: function () {
                $(document).on('review_added', self.handleReviewAdd);
    
                $reviews.on('filter', '.product-reviews-overview', function (e, rate) {
                    self.filterByRate(rate);
                });
    
                $reviews.on('reset', '.product-reviews-overview', function () {
                    self.resetFilter();
                });
            },
            destruct: function () {
                $(document).off('review_added', self.handleReviewAdd);
            },
            handleReviewAdd: function (e, data) {
                if ($reviews.data('page') > 1) {
                    return false;
                }
    
                var $review = $('<div class="product-reviews__review"></div>');
                var $reviewHtml = data.html;
    
                if (isReviewsPlugin) {
                    $reviewHtml = data;
                }
    
                $review.append($reviewHtml);
    
                $reviews.find('.product-reviews__reviews').prepend($review);
                self.updateCount();
                self.updateInfo();
                self.updateFilter();
                Update($reviews);
            },
            filterByRate: function (rate) {
                filter_rate = rate;
                self.setHeader('Отзывы с оценкой ' + rate);
                self.updateFilter();
            },
            resetFilter: function () {
                filter_rate = null;
                self.resetHeader();
                self.updateFilter();
            },
            updateFilter: function () {
                var $all_reviews = $reviews.find('.product-review').not('.product-review_reply').closest('.product-reviews__review');
                $all_reviews.removeClass('product-reviews__review_filtered');
    
                if (filter_rate) {
                    var $hidden_reviews = $reviews.find('.product-review:not(.product-review_reply):not(.product-review_rate_' + filter_rate + ')').closest('.product-reviews__review');
                    $hidden_reviews.addClass('product-reviews__review_filtered');
                }
    
                self.updateHidden();
            },
            updateHidden: function () {
                var $all_reviews = $reviews.find('.product-review').not('.product-review_reply').closest('.product-reviews__review');
                $all_reviews.removeClass('product-reviews__review_hidden');
    
                var $hidden_reviews = $all_reviews.not('.product-reviews__review_filtered').slice(5);
                $hidden_reviews.addClass('product-reviews__review_hidden');
    
                $reviews.toggleClass('product-reviews_has-hidden', $hidden_reviews.length > 0);
            },
            updateInfo: function () {
                $.ajax({
                    url: ''
                }).then(function (response) {
                    if (isReviewsPlugin) {
                        var JSONResponse = JSON.parse(response);
                        response = JSONResponse.data;
                    }
    
                    $reviews.find('.product-reviews__summary-container').replaceWith($(response).find('.product-reviews__summary-container').eq(0));
    
                    Update($reviews);
                });
            },
            updateCount: function () {
                var count = $reviews.find('.product-reviews__review').length;
                $reviews.toggleClass('product-reviews_empty', count === 0);
                $reviews.find('.product-reviews__count').text(count);
            },
            setHeader: function (header) {
                var $header = $reviews.find('.product-reviews__header');
    
                if (!src_header) {
                    src_header = $header.html();
                }
    
                $header.html(header);
            },
            resetHeader: function () {
                var $header = $reviews.find('.product-reviews__header');
    
                if (src_header) {
                    $header.html(src_header);
                    src_header = null;
                    self.updateCount();
                }
            }
        });
    
        self.initEventListeners();
    });
    
    var ProductReviewsOverview = market_shop.ProductReviewsOverview = ComponentRegistry.register(function ($context) {
        return $context.select('.product-reviews-overview');
    }, function ($overview, self) {
        $.extend(self, {
            initEventListeners: function () {
                $overview.find('.product-reviews-overview__rate-button').on('click', function () {
                    var $button = $(this);
                    var rate = $button.data('rate');
    
                    $overview.trigger('filter', [rate]);
                    $overview.addClass('product-reviews-overview_has-filter');
                });
    
                $overview.find('.product-reviews-overview__reset-button').on('click', function () {
                    $overview.trigger('reset');
                    $overview.removeClass('product-reviews-overview_has-filter');
                });
            }
        });
    
        self.initEventListeners();
    });
    
    var FavoriteCounter = market_shop.FavoriteCounter = ComponentRegistry.register(function ($context) {
        return $context.select('.favorite-counter');
    }, function ($counter, self) {
        $.extend(self, {
            initEventListeners: function () {
                var counter = Counter($counter);
    
                $(document).on('shop_update_favorite@market:global', function () {
                    var count = FavoriteSet.count();
    
                    counter.changeCount(count);
                });
            }
        });
    
        self.initEventListeners();
    });
    
    var RecentlyViewedCounter = market_shop.RecentlyViewedCounter = ComponentRegistry.register(function ($context) {
        return $context.select('.recently-viewed-counter');
    }, function ($counter, self) {
        $.extend(self, {
            initEventListeners: function () {
                var counter = Counter($counter);
    
                $(document).on('shop_update_recently@market:global', function () {
                    var count = RecentlySet.count();
    
                    counter.changeCount(count);
                });
            }
        });
    
        self.initEventListeners();
    });
    
    var CompareCounter = market_shop.CompareCounter = ComponentRegistry.register(function ($context) {
        return $context.select('.compare-counter');
    }, function ($counter, self) {
        $.extend(self, {
            initEventListeners: function () {
                var counter = Counter($counter);
    
                $(document).on('shop_update_compare@market:global', function () {
                    var count = CompareSet.count();
    
                    counter.changeCount(count);
                });
            }
        });
    
        self.initEventListeners();
    });
    
    var CartCounter = market_shop.CartCounter = ComponentRegistry.register(function ($context) {
        return $context.select('.cart-counter');
    }, function ($counter, self) {
        $.extend(self, {
            initEventListeners: function () {
                var counter = Counter($counter);
    
                $(document).on('shop_cart_add@market:global', function (e, response) {
                    if (response.status !== 'ok') {
                        return;
                    }
    
                    counter.changeCount(response.data.count);
                });
    
                $(document).on('shop_cart_update@market:global', function (e, response) {
                    counter.changeCount(response.data.count);
                });
    
                $(document).on('shop_cart_delete@market:global', function (e, response) {
                    counter.changeCount(response.data.count);
                });
    
                $(document).on('shop_cart_clear@market:global', function () {
                    counter.changeCount(0);
                });
            }
        });
    
        self.initEventListeners();
    });
    
    var CompareLink = market_shop.CompareLink = ComponentRegistry.register(function ($context) {
        return $context.select('.compare-link');
    }, function ($link, self) {
        var _private = {
            update: function (url) {
                $link.attr('href', url);
            }
        };
    
        $.extend(self, {
            initEventListeners: function () {
                $(document).on('shop_update_compare@market:global', function () {
                    var url = config.shop.compare_id_url.replace('{$id}', CompareSet.list().join(','));
                    _private.update(url);
                });
            }
        });
    
        if (config.shop.compare_url_variant === 'ids') {
            self.initEventListeners();
        }
    });
    
    var BrandReviewsContainer = market_shop.BrandReviewsContainer = ComponentRegistry.register(function ($context) {
        return $context.select('.brand-reviews-container');
    }, function ($container) {
        var $reviewForm = $('input[name="brand_review_form"]').val();
    
        var _private = {
            initEventListeners: function () {
                $container.find('.brand-reviews-container__review-button').on('click', function () {
                    ModalUtil.openContent($reviewForm, { title: market.config.language['write_a_review'] });
                });
            }
        };
    
        _private.initEventListeners();
    });
    
    var BrandReviewForm = market_shop.BrandReviewForm = ComponentRegistry.register(function ($context) {
        return $context.select('.brand-review-form');
    }, function ($form) {
        var _private = {
            initEventListeners: function () {
                $form.on('success@market:ajax-form', function (e, response) {
                    if (response.status !== 'ok') {
                        if (response.errors['captcha_refresh']) {
                            var msg = Object.keys(response.errors).map(function (name) {
                                return response.errors[name];
                            }).join(' ');
                            alert(msg);
                        }
    
                        return;
                    }
    
                    ModalUtil.close();
                    InfoPanelUtil.showMessage(response.data.msg);
                });
            }
        };
    
        _private.initEventListeners();
    });
    
    var BrandReviews = market_shop.BrandReviews = ComponentRegistry.register(function ($context) {
        return $context.select('.brand-reviews');
    }, function ($reviews) {
        var _private = {
            initEventListeners: function () {
                $reviews.find('.brand-reviews__more-button').on('click', function () {
                    $reviews.addClass('brand-reviews_show-all');
                });
            }
        };
    
        _private.initEventListeners();
    });
    
    var Buy1stepDecorator = market_shop.Buy1stepDecorator = ComponentRegistry.register(function ($context) {
        return $context.select('.buy1step-decorator');
    }, function ($container, self) {
        var _private = {
            getForm: function () {
                var $form = window.shop_buy1step_jquery($container.find('.buy1step-form').get(0));
                var form = $form.data('buy1step');
    
                return form;
            },
    
            getContactinfoStep: function () {
                var $step = window.shop_buy1step_jquery($container.find('.buy1step-step_contactinfo').get(0));
                var step = $step.data('buy1step');
    
                return step;
            },
    
            patchContactinfoStep: function () {
                var step = this.getContactinfoStep();
    
                if (step) {
                    if (!step.suggestions.dadata) {
                        return;
                    }
    
                    var suggestionsDadataSetCity = step.suggestions.dadata.setCity;
    
                    step.suggestions.dadata.setCity = function () {
                        suggestionsDadataSetCity.apply(step, arguments);
                        $container.find('select[name$="[address.shipping][region]"]').trigger('refresh');
                    }.bind(step);
                }
            },
    
            patchForm: function () {
                var form = _private.getForm();
    
                if (form) {
                    var renderInfoBlock = form.renderInfoBlock;
    
                    form.renderInfoBlock = function () {
                        renderInfoBlock.apply(form, arguments);
                        _private.decorateInfoBlock();
                        _private.decorateInfoWrapper();
                    }.bind(form);
    
                    var renderSteps = form.renderSteps;
    
                    form.renderSteps = function () {
                        renderSteps.apply(form, arguments);
                        _private.decorateInfoWrapper();
                    }.bind(form);
    
                    var observer = new MutationObserver(function () {
                        if (ResponsiveUtil.isTabletMax()) {
                            form.sticky.refresh();
                        }
                    });
    
                    observer.observe($container.find('.buy1step-form__steps-box').get(0), { childList: true, attributes: true, subtree: true });
                }
            },
    
            decorateBlock: function () {
                var $block = $('<div class="block"></div>');
                var $block_header = $('<div class="block__header"></div>');
                var $block_content = $('<div class="block__content"></div>');
                $block.append($block_header);
                $block.append($block_content);
    
                $block_header.append($container.find('.buy1step-page__header_h1'));
                $block_content.append($container.find('.buy1step-page').contents());
                $block.appendTo($container.find('.buy1step-page'));
            },
    
            decorateHeaders: function () {
                $container.find('.buy1step-page__header_h1').replaceWith($(market_shop.buy1step_checkout_page_header_html));
                $container.find('.buy1step-page__header_h2').addClass('title title_h1');
                $container.find('.buy1step-heading').removeClass('buy1step-heading').addClass('title title_h2');
            },
    
            decorateComment: function () {
                $container.find('.buy1step-step_comment .buy1step-comment-field__textarea').addClass('textarea textarea_fill').attr('placeholder', 'Комментарий');
            },
    
            decorateInfoBlock: function () {
                $container.find('.buy1step-form__step .buy1step-submit-button').attr('class', '').addClass('button button_size_l button_wide');
    
                var $info_block = $container.find('.buy1step-info');
                $info_block.find('.buy1step-heading').removeClass('buy1step-heading').addClass('title title_h2');
                $info_block.find('.buy1step-info__policy-box').addClass('link-decorator');
                $info_block.find('.buy1step-items-list__show-hidden-button, .buy1step-items-list__hide-hidden-button')
                    .wrapInner('<span class="pseudo-link"></span>');
                $info_block.find('.buy1step-submit-button').attr('class', '').addClass('button button_size_l button_fill');
            },
    
            decorateInfoWrapper: function () {
                if (ResponsiveUtil.isTabletMax()) {
                    var form = _private.getForm();
    
                    if ($container.find('.checkout-confirmation__buy1step-info-wrapper').length) {
                        $container.find('.checkout-confirmation__buy1step-info-wrapper').remove();
                    }
    
                    var $info_wrapper = $container.find('.buy1step-info').clone();
    
                    if ($info_wrapper.length > 0) {
                        $info_wrapper.find('.buy1step-info__submit-box, .buy1step-info__policy-box').remove();
                        $info_wrapper.addClass('checkout-confirmation__buy1step-info-wrapper');
    
                        var $comment = $container.find('.checkout-confirmation__comment-container');
                        $comment.after($info_wrapper.first());
                    }
    
                    if (form && form.sticky.sticky) {
                        form.sticky.sticky.destroy();
                    }
                }
            }
        };
    
        $.extend(self, {
            decorate: function () {
                _private.decorateBlock();
                _private.decorateHeaders();
                _private.decorateComment();
                _private.decorateInfoBlock();
                _private.decorateInfoWrapper();
            }
        });
    
        self.decorate();
    
        $(function () {
            setTimeout(function () {
                _private.patchForm();
                _private.patchContactinfoStep();
            });
        });
    
        $container.addClass('buy1step-decorator_js-is-init');
    });
    
    market_shop.FooterCurrencySelect = ComponentRegistry.register(function ($context) {
        return $context.select('.footer-currency-select');
    }, function ($select) {
        var _private = {
            initEventListeners: function () {
                $select.on('change', function () {
                    $.ajax({
                        url: config.shop.home_url + '?currency=' + _private.getValue(),
                        crossDomain: true
                    }).then(function () {
                        window.location.reload();
                    });
                });
            },
    
            getValue: function () {
                return $select.val();
            }
        };
    
        _private.initEventListeners();
    });
    
    var BrandsSearch = market_shop.BrandsSearch = ComponentRegistry.register(function ($context) {
        return $context.select('.brands-search');
    }, function ($block, self) {
        var $brandsList = $('.brands-list-block');
        var $searchField = $block.find('.brands-search__input');
        var $clearSearch = $block.find('.brands-search__clear');
        var $lettersBlock = $block.find('.brands-letters');
        var letterActiveClass = 'brands-letters__item_active';
        var brandNameBlockSelector = '';
    
        if ($brandsList.find('.brands-thumbs').length > 0) {
            brandNameBlockSelector = '.brand-thumb__name-container';
        } else {
            brandNameBlockSelector = '.list-rows__item-name';
        }
    
        var _private = {
            searchString: '',
            letter: '',
            initEventListeners: function () {
                $searchField.on('input', function () {
                    var value = this.value.toLowerCase();
    
                    if (value.length > 0) {
                        _private.searchString = value;
                        $clearSearch.show();
                    } else {
                        _private.searchString = '';
                        $clearSearch.hide();
                    }
    
                    _private.filterItems();
                });
    
                $clearSearch.on('click', function () {
                    $searchField.val('').trigger('input');
                    $(this).hide();
                });
    
                $lettersBlock.on('click', '.brands-letters__item', function () {
                    var $item = $(this);
    
                    if ($item.hasClass(letterActiveClass)) {
                        _private.letter = '';
                        $item.removeClass(letterActiveClass);
                    } else {
                        $lettersBlock.find('.brands-letters__item').removeClass(letterActiveClass);
                        $item.addClass(letterActiveClass);
                        _private.letter = $(this).data('letter');
                    }
    
                    _private.toggleResetLetterButton();
                    _private.filterItems();
                });
    
                $lettersBlock.on('click', '.brands-letters__reset', function () {
                    _private.letter = '';
                    $lettersBlock.find('.brands-letters__item').removeClass(letterActiveClass);
                    _private.toggleResetLetterButton();
                    _private.filterItems();
                });
            },
            filterItems: function () {
                _private.getItems().each(function () {
                    var $itemBlock = $(this);
                    var $itemNameBlock = $itemBlock.find(brandNameBlockSelector);
                    var itemName = $itemNameBlock.text().toLowerCase();
                    var itemLetter = $itemBlock.data('letter');
    
                    if (itemName.indexOf(_private.searchString) >= 0 && (itemLetter === _private.letter || _private.letter === '')) {
                        $itemBlock.show();
                    } else {
                        $itemBlock.hide();
                    }
                });
    
                _private.toggleNotFoundBlock();
            },
            toggleNotFoundBlock: function () {
                var hasShownItems = _private.getItems().filter(':visible').length > 0;
    
                if (hasShownItems) {
                    $brandsList.find('.brands-list-block__not-found').remove();
                } else if ($brandsList.find('.brands-list-block__not-found').length === 0) {
                    $brandsList.append('<div class="brands-list-block__not-found">Не найдено</div>');
                }
            },
            getItems: function () {
                return $brandsList.find('.brand-item');
            },
            toggleResetLetterButton: function () {
                var hasActiveLetter = $lettersBlock.find('.' + letterActiveClass).length;
    
                if (hasActiveLetter) {
                    _private.addResetLetterButton();
                } else {
                    _private.removeResetLetterButton();
                }
            },
            addResetLetterButton: function () {
                if ($block.find('.brands-letters__reset').length === 0) {
                    $block.find('.brands-letters__list').last().append(
                        '<li class="brands-letters__reset link">показать все</li>');
                }
            },
            removeResetLetterButton: function () {
                $block.find('.brands-letters__reset').remove();
            }
        };
    
        _private.initEventListeners();
    });
    
    var VariantsList = market_shop.VariantsList = ComponentRegistry.register(function ($context) {
        return $context.select('.variants-list');
    }, function ($list, self) {
        var _private = {
            initEventListeners: function () {
                $list.closest('.product').on('show_hidden', _private.hideItems);
            },
            hideItems: function () {
                if ($list.is(':visible') && $list.hasClass('variants-list_nowrap') && !$list.hasClass('variants-list_has-hidden')) {
                    var countWidth = 30;
                    var listBlockWidth = $list.outerWidth() - countWidth;
                    var $listItems = $list.find('.variants-list__item');
                    var commonWidth = 0;
                    var hiddenCount = 0;
    
                    $listItems.each(function () {
                        var $item = $(this);
                        var itemWidth = $item.outerWidth() + parseFloat($list.css('gap'));
                        commonWidth += itemWidth;
    
                        if (commonWidth > listBlockWidth) {
                            $item.addClass('variants-list__item_hidden');
                            hiddenCount += 1;
                        }
                    });
    
                    if (hiddenCount > 0) {
                        $list.addClass('variants-list_has-hidden').attr('data-count', '+' + hiddenCount);
                        $list.closest('.product').off('show_hidden', _private.hideItems);
                    }
                }
            }
        };
    
        _private.initEventListeners();
        _private.hideItems();
    });
    
    ComponentRegistry.register(function ($context) {
        return $context.find('.products-search');
    }, function ($this) {
        const $filterInputs = $this.find('.products-search__input input');
        const $products = $('.products');
        const $productsContent = $('.products__content');
        let products = market_shop.Products($products);
        const $form = $('.filters__form');
    
        $filterInputs.each(function () {
            const $input = $(this);
            const $clearBtn = $this.find('.products-search__header-input-close');
            const debouncedHandleFilterInput = DebounceUtil.debounce(handleFilterInput, 1000);
    
            $clearBtn.on('click', function () {
                $input.val('');
    
                if ($form.length) {
                    $form.trigger('submit');
                } else {
                    handleFilterInput($input);
                }
            });
    
            $input.on('input', debouncedHandleFilterInput);
        });
    
        function handleFilterInput(e, $input) {
            let $targetInput = $(e.target);
    
            if ($input) {
                $targetInput = $input;
            }
    
            const $targetInputName = $targetInput.prop('name');
    
            if ($form.length) {
                $form.trigger('submit');
    
                $(document).on('filters_filtration_complete@market:global', showTips);
            } else {
                replaceProducts();
            }
    
            function replaceProducts() {
                const url = new URL(window.location);
                const params = url.searchParams;
    
                $filterInputs.each(function () {
                    let $inputValue = $(this).val();
                    let $inputId = $(this).prop('name');
    
                    if ($inputValue) {
                        params.set($inputId, $inputValue);
                    } else {
                        params.delete($inputId);
                    }
                });
    
                url.search = params.toString();
                replaceOnlyProducts(url).then(showTips);
            }
    
            function replaceOnlyProducts(url) {
                $products.addClass('products_loading');
                let promise = products.fetchProducts(url, 1);
    
                promise.then(function (response) {
                    let $response_products = $(response).find('.products');
                    let $responseNotFoundBlock = $(response).find('.not-found-block');
                    const $notFoundBlock = $this.find('.not-found-block');
                    const $lazyLoadBlock = $products.find('.products__lazy-load');
                    const $pagination = $products.find('.products__pagination');
    
                    console.log($responseNotFoundBlock.length);
    
                    if ($response_products.length === 0 && $responseNotFoundBlock.length === 0) {
                        window.location.reload();
    
                        return;
                    }
    
                    if ($responseNotFoundBlock.length > 0) {
                        products.page = 1;
                        products.pages_count = 1;
                        products.products_count = 0;
                        products.pages_points = [];
                        $lazyLoadBlock.remove();
                        $pagination.remove();
    
                        $productsContent.html($responseNotFoundBlock);
                    }
    
                    if ($response_products.length > 0) {
                        let $newContent = $response_products.find('.products__content').contents();
    
                        products.page = $response_products.data('page');
                        products.pages_count = $response_products.data('pages_count');
                        products.products_count = $response_products.data('products_count');
                        products.pages_points = [];
    
                        let $contentBlock = $products.find('.products__main');
                        let $excludeAjaxBlocks = $contentBlock.find('[data-exclude-ajax]');
    
                        if ($excludeAjaxBlocks.length > 0) {
                            $excludeAjaxBlocks.each(function () {
                                let $block = $(this);
                                let blockId = $block.data('exclude-ajax');
                                let $newBlock = $newContent.find('[data-exclude-ajax=' + blockId + ']');
    
                                $newBlock.replaceWith($block);
                            });
                        }
    
                        const $newLazyLoadBlock = $response_products.find('.products__lazy-load');
                        const $newPagination = $response_products.find('.products__pagination');
    
                        $productsContent.html($newContent);
    
                        if ($lazyLoadBlock.length) {
                            $lazyLoadBlock.html($newLazyLoadBlock);
                        } else {
                            $contentBlock.append($newLazyLoadBlock);
                        }
    
                        if ($pagination.length) {
                            $pagination.html($newPagination);
                        } else {
                            $contentBlock.append($newPagination);
                        }
    
                        if ($notFoundBlock.length) {
                            $notFoundBlock.remove();
                        }
                    }
    
                    $(window).off('scroll.pages');
    
                    $products.removeClass('products_lazy-load_process');
                    $products.removeClass('products_lazy-load_done');
    
                    Update($products);
                    $(document).trigger('products_updated@market:global');
    
                    products.updateProductsCount();
    
                    $products.removeClass('products_loading');
                }, function (xhr, status) {
                    if (status === 'abort') {
                        return;
                    }
    
                    $products.removeClass('products_loading');
                });
    
                HistoryUtil.replaceState({}, '', url);
    
                return promise;
            }
    
            function showTips() {
                const text = PluralUtil.plural(products.products_count, [
                    'Найден ' + products.products_count + ' товар',
                    'Найдено ' + products.products_count + ' товара',
                    'Найдено ' + products.products_count + ' товаров'
                ]);
    
                const $hint = $(`.products-search__input-hint[data-id="${$targetInputName}"]`);
    
                $hint.text(text);
                $hint.addClass('products-search__input-hint_show');
                setTimeout(() => {
                    $hint.removeClass('products-search__input-hint_show');
                }, 3000);
    
                if ($form.length) {
                    const $input = $(`.products-search__input input[name="${$targetInputName}"]`);
    
                    if ($input.length) {
                        $input.focus();
                        $input[0].setSelectionRange($input[0].value.length, $input[0].value.length);
                    }
    
                    $(document).off('filters_filtration_complete@market:global', showTips);
                }
            }
        }
    });
    

    var FavoriteSet = CookieSet('shop_favorite', function (info) {
        $(document).trigger('shop_update_favorite@market:global', info);
    });

    var CompareSet = CookieSet('shop_compare', function (info) {
        $(document).trigger('shop_update_compare@market:global', info);
    });

    var RecentlySet = market.RecentlySet = CookieSet('shop_recently', function (info) {
        $(document).trigger('shop_update_recently@market:global', info);
    });

    market.RecentlySet.touch = function (product_id) {
        if (this.has(product_id)) {
            this.remove(product_id);
        }

        var current_list = this.list();
        current_list.unshift(product_id);
        current_list = current_list.slice(0, 50);
        this.store(current_list);
    };

    market.ResponsiveFilterAdapter = ComponentRegistry.register(function ($context) {
        return $context.select('.r-filters');
    }, function ($filter_block, self) {
        var $toggle = $filter_block.find('.js-r-filters__toggle'),
            $filter_form = $('.filters__form'),
            $filters = $('.sidebar-filters'), // Костыль, который исправлять себе дороже. Фильтры всегда должны быть в сайдбаре
            filters_opened_class = 'sidebar-filters_opened',
            $body = $('body'),
            $poppup_header = $('.sidebar-filters__header');
    
        $.extend(self, {
            initEventListeners: function () {
                $toggle.on('click', function (e) {
                    e.preventDefault();
    
                    if ($filters.hasClass(filters_opened_class)) {
                        self.close();
                    } else {
                        self.open();
                    }
                });
    
                $poppup_header.on('click', function () {
                    self.close();
                });
    
                $(document).on('filters_header@market:global', function () {
                    self.close();
                });
            },
            getFilterActiveParams: function () {
                return $filter_form.serializeArray();
            },
            open: function () {
                $body.addClass('r-popup-opened');
                $filters.addClass(filters_opened_class);
                market.ScrollLockUtil.lockPage();
                $(document).trigger('r-filters_opened@market:global');
            },
            close: function () {
                market.ScrollLockUtil.unlockPage();
                $body.removeClass('r-popup-opened');
                $filters.removeClass(filters_opened_class);
            }
        });
    
        TouchLockUtil.lock($filters.find('.filters__filters'));
        self.initEventListeners();
    });
    
    market_shop.ArrivedDecorator = ComponentRegistry.register(function ($context) {
        return $context.select('.arrived-decorator');
    }, function ($element) {
        var _private = {
            decorateInput: function () {
                var $inputs = $element.find('input:text');
                $inputs.each(function () {
                    var $input = $(this);
    
                    var $field = $input.closest('.plugin_arrived-field');
                    var $name = $field.find('.plugin_arrived-name');
                    var is_required = $name.hasClass('required');
    
                    $input.addClass('input-text').attr('placeholder', $name.text());
    
                    if (is_required) {
                        $input.addClass('input-text_required');
                    }
                });
            },
    
            decorateSubmitButton: function () {
                $element.find(':submit').addClass('button');
            },
    
            decorateSelect: function () {
                market.Select.create($element.find('select'), null, true);
            },
    
            decorateTerms: function () {
                var $terms = $element.find('[name="terms"]');
    
                if ($terms.length) {
                    var $label = $terms.closest('label');
                    var $field = $terms.closest('.plugin_arrived-field');
    
                    $label.addClass('arrived-decorator__terms-label');
                    $label.appendTo($field);
    
                    $label.find('a').addClass('pseudo-link');
    
                    market.Checkbox.create($terms);
                }
            },
    
            decorate: function () {
                $element.find('a').addClass('link');
                $element.find('.plugin_arrived-header').find('span').addClass('modal__title');
            }
        };
    
        _private.decorate();
        _private.decorateInput();
        _private.decorateSubmitButton();
        _private.decorateSelect();
        _private.decorateTerms();
        Update($element);
    });
    
    market.PricedownDecorator = (function () {
        var PricedownDecorator = ComponentRegistry.register(function ($context) {
            return $context.select('.pricedown-decorator');
        }, function ($decorator, self) {
            $.extend(self, {
                decorate: function () {
                    var $form = $decorator.find('form');
                    var $contentBox = $form.find('.plugin_pricedown-box');
                    var $header = $form.find('.plugin_pricedown-header');
                    var title = $header.find('span').text();
    
                    $form.addClass('form-decorator');
                    $header.remove();
                    $form.append($contentBox.html());
                    $contentBox.remove();
    
                    $form.find('input[type="text"]').each(function () {
                        var $input = $(this);
                        var $field = $input.closest('.plugin_pricedown-field');
                        var $name = $field.find('.plugin_pricedown-name');
                        var isRequired = $name.hasClass('required');
    
                        $input.attr('placeholder', $name.text());
    
                        if (isRequired) {
                            market.InputTextRequired($input);
                        }
                    });
    
                    market.ModalUtil.openContent($decorator, { classes: 'modal-pricedown', title: title });
                }
            });
    
            self.decorate();
        });
    
        if ($('.page_pricedown').length > 0) {
            market.ObserverUtil.observe('plugin_pricedown-popup', function ($node) {
                var $decorator = $('<div class="pricedown-decorator"></div>');
                $decorator.append($node);
    
                PricedownDecorator($decorator);
                $('.plugin_pricedown-overlay').remove();
            });
        }
    
        return PricedownDecorator;
    })();
    
    market_shop.OrderPage = ComponentRegistry.register(function ($context) {
        return $context.select('.order-page');
    }, function ($page) {
        var cart, ui, form, cart_urls, form_urls;
    
        var _private = {
            initWaOrderClass: function (classname, $wrapper) {
                var deferred = $.Deferred();
    
                if (window.waOrder && window.waOrder[classname]) {
                    deferred.resolve(window.waOrder[classname]);
                } else {
                    $wrapper.on('wa_order_' + classname + '_ready', function (e, instance) {
                        deferred.resolve(instance);
                    });
                }
    
                return deferred;
            },
    
            initCart: function () {
                return this.initWaOrderClass('cart', $('#js-order-cart'));
            },
    
            initEventsListeners: function () {
                $page.find('.order-page__clear-button').on('click', function () {
                    CartUtil.clear().then(function () {
                        window.location.reload();
                    });
                });
    
                $page.on('change', '.js-unit-quantity', function () {
                    var $field = $(this);
                    _private.changeQuantityFieldValue($field);
                });
            },
    
            initGlobalEventsListeners: function () {
                $(document).on('shop_cart_add_product_effects_off@market:global shop_cart_add_product@market:global', function () {
                    $(document).trigger('wa_order_product_added');
                });
            },
    
            initForm: function () {
                return this.initWaOrderClass('form', $('#js-order-checkout'));
            },
    
            initScope: function () {
                var deferred = $.Deferred();
    
                _private.initCart().then(function (instance) {
                    cart = instance;
                    ui = window.waOrder.ui;
    
                    _private.initForm().then(function (instance) {
                        form = instance;
    
                        cart_urls = {};
                        form_urls = {};
                        [{
                            scope: cart,
                            storage: cart_urls
                        }, {
                            scope: form,
                            storage: form_urls
                        }].forEach(function (item) {
                            if (item.scope.urls) {
                                $.each(item.scope.urls, function (name, value) {
                                    var url = LinkUtil.create(value).toString();
    
                                    item.storage[name] = url;
                                });
                            }
                        });
    
                        deferred.resolve();
                    });
                });
    
                return deferred;
            },
    
            initXhrEventListener: function () {
                $(document).ajaxSuccess(function (e, xhr, options) {
                    var url = LinkUtil.create(options.url).toString();
    
                    _private.handleXhrRequest(url, xhr);
                });
            },
    
            handleXhrRequest: function (url, xhr) {
                if (url === cart_urls.save) {
                    this.handleXhrCartSave(xhr);
                }
    
                if (url === cart_urls.add) {
                    console.log('add');
                }
            },
    
            handleXhrCartSave: function (xhr) {
                if (!xhr.responseJSON) {
                    return;
                }
    
                if ($page.data('is_enabled_refresh_shapes')) {
                    var $products = $page.find('.wa-products');
                    var items = xhr.responseJSON.data.cart.items;
                    $.each(items, function (id, item) {
                        if (item.type !== 'product') {
                            return;
                        }
    
                        var product = item;
    
                        var $product = $products.find('.wa-product[data-id="' + id + '"]');
    
                        if (!$product.length) {
                            return;
                        }
    
                        var $price = $product.find('.js-product-price');
                        $price.html(ui.formatPrice(product.price) + '/' + translate('item'));
                    });
                }
            },
            changeQuantityFieldValue: function ($field) {
                var $quantityFieldWrapper = $field.closest('.wa-field-wrapper');
                var $quantityField = $quantityFieldWrapper.find('.js-product-quantity');
                var fieldValue = $field.val();
                var fieldRatio = $field.data('ratio');
    
                $quantityField.val(fieldValue / fieldRatio);
                $quantityField.trigger('change');
            }
        };
    
        _private.initEventsListeners();
        _private.initGlobalEventsListeners();
        _private.initScope().then(function () {
            _private.initXhrEventListener();
        });
    });
    
    market.OrderCartDecorator = (function () {
        var OrderCartDecorator = ComponentRegistry.register(function ($context) {
            return $context.select('.order-cart-decorator');
        }, function ($decorator) {
            var cartItemsData = null;
            var $dataInput = $decorator.find('[name="cart_items_data"]');
    
            if ($decorator.find().length > 0) {
                cartItemsData = JSON.parse($dataInput.val());
            }
    
            var changeZeroPrice = function ($context) {
                if (market.config.shop.zero_price_text !== '' && market.config.shop.zero_price_text !== undefined) {
                    $context.find('.wa-price-total, .wa-product-price').each(function () {
                        var $priceBlock = $(this);
                        var priceValueArr = $priceBlock.text().split(' ').join('').replace(/,/g, '.').match(/\d+((.|,)\d+)?/g);
    
                        if (priceValueArr !== null) {
                            var priceValue = parseFloat(priceValueArr[0]);
    
                            if (priceValue === 0) {
                                if ($priceBlock.hasClass('wa-price-total')) {
                                    $priceBlock.html(market.config.shop.zero_price_text);
                                } else {
                                    $priceBlock.hide();
                                }
                            }
                        }
                    });
                }
            };
    
            var addUnitsItems = function ($context) {
                if (cartItemsData === null) {
                    return false;
                }
    
                var $cartItems = $context.find('.wa-product');
                $cartItems.each(function () {
                    var $item = $(this);
                    var itemId = $item.data('id');
                    var itemProps = cartItemsData[itemId];
    
                    if (window.market.config.commons['has_premium']) {
                        var $quantitySection = $item.find('.wa-quantity-cart-section');
                        var $fieldWrapper = $quantitySection.find('.wa-field-wrapper');
                        var $quantityField = $fieldWrapper.find('input.js-product-quantity');
                        var quantityVal = $quantityField.val();
    
                        $quantityField.attr('inputmode', 'decimal');
                        $quantityField.data('step', itemProps['step']);
    
                        if (cartItemsData[itemId]['unit_fields']) {
                            var unitFields = cartItemsData[itemId]['unit_fields'];
                            var $unitFields = $quantitySection.find('.js-unit-quantity');
                            var $togglesList = $quantitySection.find('.quantity-toggles__list');
                            var $togglesPrices = $quantitySection.find('.quantity-toggles__unit-prices');
    
                            if ($unitFields.length === 0) {
                                $quantityField.addClass('quantity-toggles__field quantity-toggles__field_active');
                                $quantityField.attr('data-field-id', 'stock');
                                $quantitySection.find('.wa-button').append('<span class="quantity-toggles__step">' + itemProps['step'] + '</span>');
    
                                $togglesList = $('<div class="cart-item__quantity-toggles quantity-toggles__list"></div>');
                                $togglesPrices = $quantitySection.find('.wa-section-footer').addClass('quantity-toggles__unit-prices');
    
                                var $quantityFieldToggle = $('<span class="quantity-toggles__item quantity-toggles__item_active" data-field-id="stock">' + itemProps['stock_unit']['name_short'] + '</span>');
                                var $quantityfieldPrice = $('<span class="quantity-toggles__unit-price quantity-toggles__unit-price_active" data-unit-id="stock">'
                                  + itemProps['price_html'] + ' ' + config.language['for'] + ' ' + itemProps['stock_unit']['name_short']
                                  + '</span>');
    
                                $togglesList.append($quantityFieldToggle);
                                $togglesPrices.append($quantityfieldPrice);
                                $quantitySection.find('.wa-section-body').after($togglesList);
                            }
    
                            $(Object.keys(unitFields)).each(function () {
                                var fieldId = this;
                                var fieldData = unitFields[fieldId];
                                var $unitField = $fieldWrapper.find('[data-field-id="' + fieldId + '"]');
                                var fieldVal = NumberUtil.formatNumber(quantityVal * fieldData['ratio']);
    
                                if ($unitField.length === 0) {
                                    $unitField = $('<input class="wa-field quantity-toggles__field js-unit-quantity" inputmode="decimal" data-field-id="' + fieldId + '"/>');
                                    $unitField.attr('data-ratio', fieldData['ratio']);
                                    $unitField.data('step', fieldData['step']);
    
                                    var $fieldToggle = $('<span class="quantity-toggles__item" data-field-id="' + fieldId + '">' + fieldData['unit']['name_short'] + '</span>');
                                    var $fieldPrice = $('<span class="quantity-toggles__unit-price" data-unit-id="' + fieldId + '">'
                                      + fieldData['price_html'] + ' ' + config.language['for'] + ' ' + fieldData['unit']['name_short']
                                      + '</span>');
    
                                    $fieldWrapper.append($unitField);
                                    $togglesList.append($fieldToggle);
                                    $togglesPrices.append($fieldPrice);
                                }
    
                                $unitField.val(fieldVal);
                            });
    
                            $quantitySection.addClass('quantity-toggles quantity-toggles_size_s');
                        }
                    }
                });
            };
    
            var renderFavoriteButton = function (productId) {
                var inFavorite = FavoriteSet.has(productId);
                var $favoriteButton = $('<span class="wa-action product-favorite" data-product_id="' + productId + '" title="">'
                  + '<svg class="svg-icon" width="20" height="20" stroke-width="2"><use xlink:href="' + config['commons']['svg']['symbols_sprite'] + '#favorite"></use></svg>'
                  + '</span>');
                $favoriteButton.toggleClass('product-favorite_active', inFavorite);
    
                market_shop.ProductFavorite($favoriteButton);
    
                return $favoriteButton;
            };
    
            var decorateProductActions = function ($product) {
                var productId = $product.data('product-id');
                var $columnPrice = $product.find('.wa-column-price');
                var $addActions = $('<div class="wa-add-actions"></div>');
                var $deleteIcon = $('<svg class="svg-icon" width="24" height="24"><use xlink:href="' + config['commons']['svg']['symbols_sprite'] + '#trash_alt"></use></svg>');
    
                $product.find('.wa-action').each(function () {
                    var $action = $(this);
    
                    if ($action.closest('.wa-column-details').length > 0 && $action.hasClass('js-delete-product')) {
                        $action.find('.wa-tooltip').html($deleteIcon);
                        $action.appendTo($addActions);
                    } else {
                        var $icon_box = $(config.commons.pseudo_link_icon_box_html);
                        $icon_box.addClass('pseudo-link-box_style_gray');
                        var $icon = $action.find('.wa-icon');
                        $icon.addClass('pseudo-link-box__icon');
                        $icon_box.find('.icon-box__icon').html($icon);
                        $icon_box.find('.pseudo-link-box__link').append($action.contents());
                        $action.append($icon_box);
                    }
                });
    
                var $favoriteButton = renderFavoriteButton(productId);
                $favoriteButton.prependTo($addActions);
    
                $columnPrice.append($addActions);
            };
    
            var decorate = function ($context) {
                market.Radio.create($context.find(':radio'));
                market.Checkbox.create($context.find(':checkbox'));
                market.Select.create($context.find('select'), null, true);
    
                $context.find('.wa-product[data-product-id]').each(function () {
                    var $product = $(this);
                    var $productBody = $product.find('.wa-product-body');
                    var $columns = $productBody.find('.wa-column-details, .wa-column-quantity, .wa-column-price');
                    var $main = $('<div class="wa-column-main"></div>');
                    var $image = $productBody.find('.wa-column-image img');
                    var image_src = $image.attr('src');
    
                    $main.append($columns);
                    $productBody.append($main);
                    decorateProductActions($product);
    
                    if (image_src === '/wa-apps/shop/img/image-dummy.png' && config['shop']['product_dummy_image']) {
                        $image.attr('src', config['shop']['product_dummy_image']);
                    }
                });
                $context
                    .find('.wa-coupon-section .wa-input')
                    .addClass('input-text');
                $context.find('.wa-error-text').addClass('error error_text');
                $context.find('.wa-product .wa-name').addClass('link link_style_hover');
                $context.find('.wa-cart-details .wa-button').addClass('button');
                $context.find('.wa-cart-details .wa-button.transparent').addClass('button_style_transparent');
                $context.find('.wa-column-content').append('<div class="order-cart-decorator__back-link-wrapper"><a class="order-cart-decorator__back-link link" href="javascript://">'
                  + '<svg class="svg-icon" width="10" height="10"><use xlink:href="' + config['commons']['svg']['symbols_sprite'] + '#dict-arrow-left"></use></svg>'
                  + config.language['back_to_shopping']
                  + '</a>'
                  + '</div>');
    
                if (config.commons['has_premium']) {
                    $context.addClass('order-cart-decorator_premium');
                }
    
                changeZeroPrice($decorator);
                addUnitsItems($decorator);
            };
    
            decorate($decorator);
    
            $decorator.on('wa_order_cart_reloaded', function () {
                decorate($decorator);
            }).on('wa_order_cart_rendered', function () {
                changeZeroPrice($decorator);
                addUnitsItems($decorator);
            });
    
            $decorator.addClass('order-cart-decorator_ready');
        });
    
        $(document).on('click', '.order-cart-decorator__back-link', function () {
            if (document.referrer.indexOf(window.location.host) !== -1) {
                window.history.go(-1);
            } else {
                window.location.href = '/';
            }
        });
    
        market.ObserverUtil.observe('order-cart-decorator__trigger', function ($node) {
            OrderCartDecorator($node.closest('.order-cart-decorator'));
        });
    
        return OrderCartDecorator;
    })();
    
    market.OrderFormDecorator = (function () {
        var OrderFormDecorator = ComponentRegistry.register(function ($context) {
            return $context.select('.order-form-decorator');
        }, function ($decorator) {
            var formDecorated = false;
    
            var changeZeroPrice = function ($context) {
                if (market.config.shop.zero_price_text !== '' && market.config.shop.zero_price_text !== undefined) {
                    $context.find('.wa-item-price .wa-price, .wa-item-total .wa-price').each(function () {
                        var $priceBlock = $(this);
                        var priceValueArr = $priceBlock.text().split(' ').join('').replace(/,/g, '.').match(/\d+((.|,)\d+)?/g);
    
                        if (priceValueArr !== null) {
                            var priceValue = parseFloat(priceValueArr[0]);
    
                            if (priceValue === 0) {
                                $priceBlock.html(market.config.shop.zero_price_text);
                            }
                        }
                    });
                }
            };
    
            var addProfileUrl = function ($context) {
                var $contactName = $context.find('.wa-contact-name');
    
                if ($contactName.length > 0) {
                    var name = $contactName.text();
                    var $profileLink = $('<a class="link" href="' + config.commons.profile_url + '" target="_blank">' + name + '</a>');
                    $contactName.html($profileLink);
                }
            };
    
            var decorate = function ($context) {
                market.Radio.create($context.find(':radio'));
                market.Checkbox.create($context.find(':checkbox'));
                /* NOVAPOSHTA FIX */
                var $filteredSelect = $context.find('select').not('[id^="np2_cities_"]').not('[id^="np2_wh_select"]').not('[id^="np2_street_select"]');
                market.Select.create($filteredSelect, null, true);
                $context
                    .find('input:not([type]), input[type="text"], input[type="phone"], input[type="tel"], input[type="email"], input[type="password"], input[type="url"]')
                    .addClass('input-text input-text_fill');
                $context.find('textarea').addClass('textarea textarea_fill');
                $context.find('a').addClass('link');
                $context.find('.wa-button').addClass('button');
                $context.find('.wa-submit-button').addClass('button_size_l button_fill');
                $context.find('.wa-header').addClass('title title_h3');
                $context.find('.wa-link').removeClass('link').addClass('pseudo-link');
                $context.find('.wa-error-text').addClass('error error_text');
                $context.find('.wa-phone').attr('type', 'tel');
    
                $context.find('.wa-type-wrapper').each(function () {
                    $(this).prepend($('<div class="wa-type-icon"></div>').append($(config.commons.svg.available)));
                });
    
                $context.find('.wa-method .wa-image-wrapper').each(function () {
                    var $body = $(this).next('.wa-method-body');
    
                    $body.prepend($(this));
                    $body.prepend($('<div class="wa-method-arrow"></div>').append($(config.commons.svg.available)));
                });
    
                let $comment_section = $context.find('.wa-comment-section');
                var $comment_link = $comment_section.find('.wa-link');
                $comment_link.text($comment_link.text().trim());
                $comment_section.addClass('is-opened');
    
                addProfileUrl($decorator);
                changeZeroPrice($decorator);
            };
    
            var decorateForm = function () {
                formDecorated = true;
                $('#wa-order-form-wrapper').data('ready').promise().then(function (form) {
                    if (window.waOrder.form && window.waOrder.form.sections) {
                        form = window.waOrder.form;
                    }
    
                    var form_sections = [];
    
                    if (form.sections) {
                        form_sections = form.sections;
                    }
    
                    for (var i in form_sections) {
                        (function () {
                            var index = i;
                            var section = form.sections[index];
                            var render = section.__proto__.render;
    
                            section.__proto__.render = function () {
                                var result = render.apply(this, arguments);
                                decorate($('#wa-step-' + index + '-section'));
    
                                return result;
                            };
    
                            if (index === 'shipping') {
                                var initPickupDialog = section.__proto__.initPickupDialog;
    
                                section.__proto__.initPickupDialog = function (options) {
                                    options.templates.map_details = options.templates.map_details.replace('wa-button', 'button');
    
                                    initPickupDialog.apply(this, arguments);
                                };
                            }
                        })();
                    }
    
                    window.waOrder.ui.Dialog = function (options) {
                        var that = this;
    
                        that.$wrapper = options.$wrapper;
                        that.$wrapper.data('dialog', that);
                        that.position = (options['position'] || false);
                        that.userPosition = (options['setPosition'] || false);
                        that.options = (options['options'] || false);
                        that.scroll_locked_class = 'is-scroll-locked';
                        that.height_limit = (options['height_limit'] || 640);
                        that.onBgClick = (options['onBgClick'] || false);
                        that.onOpen = (options['onOpen'] || function () {});
                        that.onClose = (options['onClose'] || function () {});
                        that.onResize = (options['onResize'] || false);
    
                        that.lock = function () {
    
                        };
    
                        that.resize = function () {
    
                        };
    
                        that.close = function (closeAll) {
                            if (typeof closeAll === 'undefined' && that.$wrapper.hasClass('wa-auth-dialog-wrapper')) {
                                closeAll = false;
                            } else if (typeof closeAll === 'undefined') {
                                closeAll = true;
                            }
    
                            market.ModalUtil.close(closeAll);
                        };
    
                        var modalClasses = ['order-modal'];
                        var $wrapper = $('<div class="order-ui-decorator order-dialog-decorator"></div>');
                        $wrapper.append(options.$wrapper);
                        $wrapper.on('click', '.js-close-dialog', function () {
                            that.close(true);
                        });
                        $wrapper.find('.wa-login-form-wrapper').each(function () {
                            modalClasses.push('order-modal_auth');
                            $wrapper.addClass('form-decorator login-page login-modal')
                                .data('form_size', 's')
                                .data('field_size', 's');
                        });
                        $wrapper.find('.wa-forgotpassword-form-wrapper').each(function () {
                            modalClasses.push(' order-modal_auth');
                            $wrapper.addClass('forgotpassword-page forgotpassword-modal')
                                .wrapInner('<div class="forgotpassword-page__form-decorator" data-size="s" data-field_size="s"></div>');
                        });
                        $wrapper.find('.wa-signup-form-wrapper').each(function () {
                            modalClasses.push('order-modal_auth');
                            $wrapper.addClass('form-decorator signup-page signup-modal')
                                .data('form_size', 's')
                                .data('field_size', 's');
                        });
                        $wrapper.find('.wa-confirm-dialog').each(function () {
                            modalClasses.push('order-modal_confirm');
                            $wrapper.addClass('order-confirm-modal');
                        });
                        $wrapper.find('.wa-channel-confirmation-dialog').each(function () {
                            modalClasses.push('order-modal_confirmation');
                            $wrapper.addClass('form-decorator order-confirm-modal order-confirm-modal_order-page')
                                .data('form_size', 's')
                                .data('field_size', 's');
                        });
                        $wrapper.find('.wa-product-edit-dialog').each(function () {
                            modalClasses.push('order-modal_product-edit');
                            $wrapper.addClass('order-product-edit-modal');
                        });
    
                        var onOpen = function () {
                            if (typeof that.onOpen === 'function') {
                                that.onOpen(that.$wrapper, that);
                            }
    
                            $('#wa-content-frontend-login-css, #wa-content-signup-css, #wa-content-frontend-forgotpassword-css, #wa-content-jquery-ui-js, #wa-content-jquery-ui-css').each(function () {
                                var $this = $(this);
                                $this.data('promise').then(function () {
                                    $this.remove();
                                });
                            });
                        };
    
                        var onClose = function () {
                            if (typeof that.onClose === 'function') {
                                that.onClose(that);
                            }
                        };
    
                        market.ModalUtil.openContent($wrapper, {
                            classes: modalClasses.join(' '),
                            beforeOpen: function ($modal) {
                                $modal.find('.modal__close').remove();
                            },
                            onOpen: onOpen,
                            onClose: onClose }
                        );
                    };
                });
    
                var shippingVariantId = $('[name="shipping[variant_id]"]').val();
                var detailsStepBlock = $('#wa-step-details-section');
    
                detailsStepBlock.attr('data-shipping-variant', shippingVariantId);
            };
    
            decorate($decorator);
    
            $(document).on('wa_order_form_ready', function () {
                if (formDecorated === false) {
                    decorateForm();
                }
            });
    
            $(document).ready(function () {
                if (formDecorated === false) {
                    decorateForm();
                }
            });
    
            $(document).on('wa_order_form_reloaded', function () {
                var shippingVariantId = $('[name="shipping[variant_id]"]').val();
                var detailsStepBlock = $('#wa-step-details-section');
    
                detailsStepBlock.attr('data-shipping-variant', shippingVariantId);
            });
    
            $(document).on('wa_order_form_changed', function () {
                var shippingVariantId = $('[name="shipping[variant_id]"]').val();
                var detailsStepBlock = $('#wa-step-details-section');
    
                detailsStepBlock.attr('data-shipping-variant', shippingVariantId);
            });
    
            $decorator.addClass('order-form-decorator_ready');
        });
    
        market.ObserverUtil.observe('order-form-decorator__trigger', function ($node) {
            OrderFormDecorator($node.closest('.order-form-decorator'));
        });
    
        return OrderFormDecorator;
    })();
    
    $(document).on('wa_order_form_ready.market', function () {
        window.market.order_is_ready = true;
    
        $(document).off('wa_order_form_ready.market');
    });
    
    market_shop.OrderDialogDecorator = ComponentRegistry.register(function ($context) {
        return $context.select('.order-dialog-decorator');
    }, function ($decorator) {
        $decorator.find('.wa-dialog-header').addClass('modal__header');
        $decorator.find('.wa-close-wrapper').addClass('modal__close');
        $decorator.find('.wa-header').addClass('title title_h3');
        $decorator.find('.wa-button').addClass('button');
        $decorator.find('a').addClass('link');
    });
    
    market_shop.OrderConfirmModal = ComponentRegistry.register(function ($context) {
        return $context.select('.order-confirm-modal');
    }, function ($modal) {
        $modal.find('.wa-button').addClass('button');
        $modal.find('.wa-button.blue').addClass('button_style_inverse');
    });
    
    market_shop.OrderProductEditModal = ComponentRegistry.register(function ($context) {
        return $context.select('.order-product-edit-modal');
    }, function ($modal) {
        $modal.find('.wa-product-description').wrapInner('<div class="content-decorator"></div>');
        var $form = $modal.find('.wa-cart-section form');
        var $details = $('<div class="wa-product-details"></div>').append(
            $modal.find('.wa-cart-section form').contents().not('.wa-product-image')
        );
        $form.append($details);
        market.Label.create($modal.find('label'));
        market.Radio.create($modal.find(':radio'));
        market.Checkbox.create($modal.find(':checkbox'));
        market.Select.create($modal.find('select'), null, true);
        $modal.find('.wa-button.large').addClass('button button_size_l');
        $modal.find('.stock-critical, .stock-low, .stock-high').each(function () {
            $(this).find('.wa-icon').append($(config.commons.svg.available));
        });
        $modal.find('.stock-none').each(function () {
            $(this).find('.wa-icon').append($(config.commons.svg.no_available));
        });
        $modal.find('.wa-stock .wa-text').each(function () {
            var $icon_box = $('<div class="icon-box"><div class="icon-box__icon"></div><div class="icon-box__content"></div></div>');
            $icon_box.find('.icon-box__icon').append($(this).find('.wa-icon'));
            $icon_box.find('.icon-box__content').append($(this).contents());
            $(this).append($icon_box);
        });
        Update($modal);
    });
    
    market_shop.OrderFreedelivery = ComponentRegistry.register(function ($context) {
        return $context.select('.order-page__plugins #freedelivery');
    }, function ($block) {
        var pluginDecoratorBlock = $('.freedelivery-decorator');
        $block.appendTo(pluginDecoratorBlock);
    });
    
    market_shop.ResponsiveComparePage = ComponentRegistry.register(function ($context) {
        return $context.select('.r-compare-page');
    }, function ($page) {
        var _private = {
            product_ids: $page.data('product_ids'),
            selected_type: $page.data('selected_type'),
    
            initSwiper: function () {
                $page.find('.r-compare-page__products-slider-container').each(function () {
                    var $slider = $(this);
                    var index = $(this).data('index');
                    $slider.addClass('r-compare-page__products-slider-container_swiper');
    
                    var swiper = new Swiper($slider.get(0), {
                        cssMode: true,
                        init: false,
                        wrapperClass: 'r-compare-page__products-slider',
                        slideClass: 'r-compare-page__product',
                        spaceBetween: 0,
                        slidesPerView: 1,
                        watchSlidesVisibility: true,
                        watchSlidesProgress: true,
                        initialSlide: index,
                        runCallbacksOnInit: true
                    });
    
                    swiper.on('slideChange', function () {
                        var slide = swiper.slides[swiper.activeIndex];
                        var features = $(slide).data('features');
    
                        $page.find('.r-compare-page__feature-value_index_' + index).each(function () {
                            var feature_code = $(this).data('feature_code');
                            $(this).html(features[feature_code]);
                        });
    
                        $page.find('.r-compare-page__slider-index_index_' + index).each(function () {
                            $(this).text((swiper.activeIndex + 1) + '/' + swiper.slides.length);
                        });
                    });
    
                    swiper.init();
    
                    $slider.trigger('init@market:swiper', swiper);
                });
            },
    
            updateFixedState: function () {
                var $header = $page.find('.r-compare-page__products-container');
                var $type_container = $page.find('.r-compare-page__type-container');
    
                if ($header.length === 0) {
                    return;
                }
    
                var is_fixed = $type_container.offset().top + $type_container.outerHeight() <= window.scrollY + 60;
    
                $page.toggleClass('r-compare-page_fixed', is_fixed);
            },
    
            handleScroll: function () {
                _private.updateFixedState();
            },
    
            initEventListeners: function () {
                $page.find('.r-compare-page__mode-control').on('change', function () {
                    $page.toggleClass('r-compare-page_same', $(this).prop('checked'));
                });
    
                $page.on('change', '.r-compare-control__type', function (e, data) {
                    _private.replace(e.target.value);
                });
    
                $page.on('click', '.r-compare-page__product-delete-button-container', function (e) {
                    var product_id = $(this).data('product_id');
                    CompareSet.remove(product_id);
    
                    if (_private.selected_type === 'all') {
                        _private.replace('');
                    } else {
                        var _product_ids = _private.product_ids.filter(function (id) {
                            return id !== product_id;
                        });
    
                        if (_product_ids.length === 0) {
                            _private.replace(config.shop.compare_url);
                        } else {
                            var url = config.shop.compare_id_url.replace('{$id}', _product_ids.join(','));
                            _private.replace(url);
                        }
                    }
                });
    
                $page.on('click', '.r-compare-page__delete-button', function () {
                    _private.product_ids.forEach(function (id) {
                        CompareSet.remove(id);
                    });
    
                    _private.replace(config.shop.compare_url);
                });
            },
    
            initGlobalEventListeners: function () {
                $(window).on('scroll', _private.handleScroll);
            },
    
            destroyGlobalEventListeners: function () {
                $(window).off('scroll', _private.handleScroll);
            },
    
            replace: function (url) {
                $page.addClass('r-compare-page_loading');
    
                $.ajax({
                    url: url
                }).then(function (response) {
                    var $new_compare_page = $(response).find('.r-compare-page');
                    $page.replaceWith($new_compare_page);
                    HistoryUtil.replaceState({}, '', url);
                    Update($new_compare_page.parent());
                }, function () {
                    $page.removeClass('r-compare-page_loading');
                });
            }
        };
    
        _private.initGlobalEventListeners();
        _private.initEventListeners();
        _private.initSwiper();
    
        return function () {
            _private.destroyGlobalEventListeners();
        };
    });
    
    market_shop.SpecpriceDecorator = ComponentRegistry.register(function ($context) {
        return $context.select('.page_specprice');
    }, function ($container) {
        var _private = {
            decorateModal: function () {
                var $modal = $container.find('.specprice-modal-form');
    
                var $checkbox = $modal.find(':checkbox');
                market.Checkbox.create($checkbox);
    
                var $form = $modal.find('.specprice-popup-right');
    
                var $inputs = $form.find('input:text, input[type="tel"], input[type="email"]');
                $inputs.each(function () {
                    var $input = $(this);
                    var $div = $input.closest('.specprice-field-div');
                    var is_required = $input.hasClass('required');
    
                    if (is_required) {
                        $div.removeClass('required-div');
                        $input.removeClass('required');
                        $input.addClass('input-text_required');
                    }
                });
    
                var $submit = $modal.find('#specprice-submit');
                $submit.closest('.text-center').removeClass('text-center');
            }
        };
    
        _private.decorateModal();
        Update($container);
    });
    
    market_shop.SmartfiltersDecorator = ComponentRegistry.register(function ($context) {
        return $context.select('.smartfilters-decorator');
    }, function ($container) {
        var _private = {
            prepareObserverUtil: function () {
                market.ObserverUtil.watchTag('LABEL');
            },
            handleObserveNode: function ($node) {
                var isDisabled = $node.hasClass('sf-label-disabled');
    
                var $filter = $node.closest('.filter');
    
                if (!$filter.length) {
                    return;
                }
    
                var $value = $node.closest('.filter__value');
    
                if (!$value.length) {
                    return;
                }
    
                var filter = $filter.data('market_shop_filter');
                var isLast = $value.is(':last-child');
                var isHidden = $node.css('display') === 'none';
    
                if (isDisabled) {
                    filter.disableValue($value);
                } else {
                    filter.enableValue($value);
                }
    
                $value.toggleClass('filter__value_sf-hidden', isDisabled && isHidden);
    
                if (isLast) {
                    _private.toggleFilterVisibility($filter.find('.filter__content'));
                }
            },
            observeDisabledFilters: function () {
                market.ObserverUtil.observeClass('sf-label-disabled', _private.handleObserveNode);
            },
            fakeObserveNode: function () {
                var $sf_label_disabled = $('.sf-label-disabled');
    
                if ($sf_label_disabled.length) {
                    $sf_label_disabled.each(function () {
                        _private.handleObserveNode($(this));
                    });
                }
            },
            toggleFilterVisibility: function ($filterContent) {
                var hasHiddenValues = $filterContent.find('.filter__value_sf-hidden').length > 0;
                $filterContent.toggleClass('filter__content_sf-hidden', hasHiddenValues);
            }
        };
    
        _private.prepareObserverUtil();
        _private.observeDisabledFilters();
        _private.fakeObserveNode();
        Update($container);
    });
    
    market_shop.SizetableDecorator = ComponentRegistry.register(function ($context) {
        return $context.select('.page_sizetable');
    }, function ($container) {
        var $button = $container.find('#size-table-button');
        var $data = $container.find('#size-table-data');
    
        if (!$button.length || !$data.length) {
            return;
        }
    
        var _private = {
            getContent: function () {
                return $data.html();
            },
    
            openModal: function () {
                var $content = $data.html();
                var $h1 = $content.find('h1');
                var title = '';
    
                if ($h1.length) {
                    title = $h1.html();
                    $h1.remove();
                }
    
                ModalUtil.openContent(this.getContent(), { title: title });
            }
        };
    
        $button.off('click').on('click', function (e) {
            _private.openModal();
    
            e.stopImmediatePropagation();
            e.preventDefault();
        });
    
        Update($container);
    });
    
    market_shop.Discount4reviewDecorator = ComponentRegistry.register(function ($context) {
        return $context.select('.discount4review-decorator');
    }, function ($container) {
        var _private = {
            decorateForm: function ($form) {
                if ($form.hasClass('discount4review-decorator__form_js-is-init')) {
                    return;
                }
    
                $form.addClass('discount4review-decorator__form');
    
                $(':submit', $form).addClass('button');
                $('textarea', $form).addClass('textarea');
                $(':text', $form).addClass('input-text').removeClass('bold');
    
                _private.decorateFormPhoto($form);
                _private.decorateFormRating($form);
    
                $form.addClass('discount4review-decorator__form_js-is-init');
            },
    
            decorateFormPhoto: function ($form) {
                var $photo = $('.userpic', $form);
    
                if ($photo.length) {
                    var $photo_box = $photo.parent();
    
                    if (!$photo_box.is('td')) {
                        $photo_box.addClass('discount4review-decorator__form-photo-box');
                    }
                }
            },
    
            decorateFormRating: function ($form) {
                var $rate = $('.review-rate', $form);
                var $input = $('.input-rate', $form);
                var $clear = $('.rate-clear', $form);
    
                if (!$rate.length || !$input.length) {
                    return;
                }
    
                if ($clear.length) {
                    $clear.remove();
                }
    
                $rate.hide();
    
                var $select = $('<div class="rating-select"><div class="rating-select__stars-container"><span class="rating-select__stars"><span class="rating-select__star" data-text="Ужасный" data-value="1"></span><span class="rating-select__star" data-text="Плохой" data-value="2"></span><span class="rating-select__star" data-text="Обычный" data-value="3"></span><span class="rating-select__star" data-text="Хороший" data-value="4"></span><span class="rating-select__star" data-text="Отличный" data-value="5"></span></span></div><div class="rating-select__value-container"><span class="rating-select__hover-value"></span><span class="rating-select__active-value"></span></div></div>');
                $input.addClass('rating-select__input');
    
                $select.append($input);
    
                $rate.after($select);
    
                Update($form);
            },
    
            decorateButtons: function () {
                $container.find('.discount4review-write-review-button').addClass('link');
            },
    
            decoratePopover: function ($popover) {
                if ($popover.hasClass('discount4review-decorator__popover_js-is-init')) {
                    return;
                }
    
                $popover.addClass('discount4review-decorator__popover');
    
                var $close = $popover.find('.close');
                var $fake_close = $('<div class="modal__close"><span class="image-box">' + config.commons.svg.cross + '</span></div>');
    
                $close.hide().after($fake_close);
                $fake_close.on('click', function () {
                    $close.trigger('click');
                });
    
                $popover.addClass('discount4review-decorator__popover_js-is-init');
            },
    
            observePopover: function () {
                market.ObserverUtil.observe('webui-popover', function ($node) {
                    var $form = $node.find('form');
    
                    if (!$form.length) {
                        return;
                    }
    
                    _private.decorateForm($form);
                    _private.decoratePopover($node);
                });
            },
    
            decorate: function () {
                $element.find('a').addClass('link');
            }
        };
    
        _private.decorateButtons();
        _private.observePopover();
        Update($container);
    });
    
    market_shop.ProductSlide = ComponentRegistry.register(function ($context) {
        return $context.select('.product-slide');
    }, function ($slide) {
        var _private = {
            setImageHeight: function () {
                if ($slide.hasClass('product-slide_size_s') && ResponsiveUtil.isDesktopMin()) {
                    var imageBlock = $slide.find('.product-slide__image-box');
                    var contentBlock = $slide.find('.product-slide__content');
    
                    imageBlock.css('height', contentBlock.height());
                }
            }
        };
    
        _private.setImageHeight();
    });
    
    market_shop.BestPriceDecorator = ComponentRegistry.register(function ($context) {
        return $context.select('.bestprice-decorator');
    }, function ($block, self) {
        $.extend(self, {
            initEventListeners: function () {
                $block.on('click', '.js-bestprice-button', function () {
                    self.openPopup($(this).data('url'));
                });
            },
            openPopup: function (url) {
                if (typeof url !== 'undefined' && typeof window.shop_bestprice !== 'undefined') {
                    ModalUtil.openAjax(url, function ($response) {
                        return $response.select('.i-bestprice');
                    }, {
                        classes: 'modal-bestprice',
                        beforeOpen: function ($modal) {
                            var $bestPriceForm = $modal.find('form');
    
                            if ($bestPriceForm.length > 0) {
                                $bestPriceForm.addClass('form-decorator bestprice-form');
                                market.FormDecorator($bestPriceForm);
                            }
                        }
                    });
                }
            }
        });
    
        self.initEventListeners();
    });
    

    var SeofiltersLinks = market_shop.SeofiltersLinks = ComponentRegistry.register(function ($context) {
        return $context.select('.seofilters-links');
    }, function ($seofilters_links) {
        var _private = {
            initSliders: function () {
                $seofilters_links.find('.seofilters-links__group').each(function () {
                    var $groupLinksBlock = $(this);
                    var $groupLinksSlider = $groupLinksBlock.find('.seofilters-links__slider');
    
                    new Swiper($groupLinksSlider.get(0), {
                        spaceBetween: 10,
                        slidesPerView: 'auto',
                        simulateTouch: true,
                        navigation: {
                            prevEl: $groupLinksBlock.find('.seofilters-links__prev').get(0),
                            nextEl: $groupLinksBlock.find('.seofilters-links__next').get(0)
                        }
                    });
                });
            }
        };
    
        _private.initSliders();
    });
    
    market.PnoticeDecorator = (function () {
        var PnoticeDecorator = ComponentRegistry.register(function ($context) {
            return $context.select('.pnotice-decorator');
        }, function ($decorator, self) {
            $.extend(self, {
                decorate: function () {
                    var $contentBox = $decorator.find('.pnotice__w');
                    var $form = $contentBox.find('form');
                    var $header = $contentBox.find('.pnotice__h');
                    var title = $header.text();
                    var $submit = $contentBox.find('.pnotice__button');
                    var $inputs = $form.find('input, select').not($submit);
                    var $inputsBlock = $('<div class="pnotice__fields"></div>');
    
                    $header.remove();
                    $contentBox.find('.pnotice__close').remove();
                    $contentBox.addClass('content-decorator');
                    $inputsBlock.append($inputs);
                    $submit.wrap('<div class="pnotice__button-block"></div>');
                    $form.find('.pnotice__button-block').before($inputsBlock);
    
                    $contentBox.find('.pnotice__buttonClose').addClass('button').on('click', market.ModalUtil.close);
    
                    market.ModalUtil.openContent($decorator, { classes: 'pnotice-modal', title: title });
                }
            });
    
            self.decorate();
        });
    
        if ($('.page_pnotice').length > 0) {
            market.ObserverUtil.observe('pnotice', function ($node) {
                var $decorator = $('<div class="pnotice-decorator"></div>');
                $decorator.append($node);
    
                PnoticeDecorator($decorator);
                $('.pnoticeW').remove();
                $('.page').removeClass('pnoticeOver');
            });
        }
    
        return PnoticeDecorator;
    })();
    
    var QueFloatingButton = market_shop.QueFloatingButton = ComponentRegistry.register(function ($context) {
        return $context.select('.que-floating-button');
    }, function ($button) {
        var _private = {
            initEventListeners: function () {
                $button.on('click', function () {
                    var $modalContent = $('#que-button-dialog-content').html();
                    _private.initModal($modalContent);
                });
            },
            initModal: function ($modalContent) {
                if (typeof $.queForm === 'function') {
                    market.ModalUtil.openContent($modalContent, {
                        classes: 'que-modal',
                        title: 'Задать вопрос о Товаре',
                        beforeOpen: function ($modal) {
                            $modal.find('form').addClass('form-decorator');
                        },
                        onOpen: function () {
                            var $modal = $('.que-modal');
                            $.queForm($modal.find('form'), function (response, dat) {
                                $modal.addClass('que-modal_success');
                                $modal.find('.modal__title').remove();
                                $modal.find('.modal__content').html('<div align="center"><div class="que-result-header">Спасибо за обращение!</div><div class="que-result-content">В ближайшее время мы ответим на Ваш вопрос</div></div>');
                                $('#que-button').remove();
    
                                window.setTimeout(market.ModalUtil.close, 5000);
                            });
                        }
                    });
                }
            }
        };
    
        if ($('.page_que').length > 0) {
            _private.initEventListeners();
        }
    });
    

    $(document).on('click', '[data-analytic-click]', function (e) {
        var $block = $(this);
        var goal = $block.data('analytic-click');

        if (goal !== undefined) {
            market.Analytics.reachGoal('goal');

            if ($block.is('a')) {
                e.preventDefault();
                var url = $block.attr('href');
                var target = $block.attr('target') || '_self';

                if (target) {
                    window.setTimeout(function () {
                        window.open(url, target);
                    });
                }
            }
        }
    });

    $(document).on('shop_seofilter.pre_init', function (e, data) {
        data.keep_page_number_param = true;
    });

    $(document).ajaxSuccess(function (e, xhr, options) {
        var url = LinkUtil.create(options.url).toString();
        var data_regions_url = LinkUtil.create(config.shop.data_regions_url).toString();

        if (url === data_regions_url) {
            $(document).trigger('shop:data_regions_success', arguments);
        }
    });

    $(document).ajaxSend(function (e, xhr, options) {
        var url = LinkUtil.create(options.url).toString();
        var data_regions_url = LinkUtil.create(config.shop.data_regions_url).toString();

        if (url === data_regions_url) {
            $(document).trigger('shop:data_regions_send', arguments);
        }
    });

    $(document).on('shop_cart_add_product@market:global', function (e, response) {
        if (response.status !== 'ok') {
            InfoPanelUtil.showMessage(response.errors);

            return;
        }

        var effect = config.shop.add_to_cart_effect;

        if (effect === 'info_panel') {
            InfoPanelUtil.showMessage('Товар добавлен в корзину');
        } else if (effect === 'modal' || effect === 'modal_cross') {
            var mobileParam = '';

            if (ResponsiveUtil.isTabletMax()) {
                mobileParam = '&mobile=1';
            }

            ModalUtil.openAjax(config.shop.cart_url + '?item_id=' + response.data.item_id + mobileParam, null, {
                title: config.language['added_to_cart']
            });
        } else if (effect === 'redirect') {
            window.location = config.shop.real_cart_url;
        } else {
            ModalUtil.close();
        }
    });

    $(document).on('shop_update_favorite@market:global', function (e, info) {
        if (info.action === 'add') {
            $.ajax({
                url: config.shop.search_url + '?list=favorite&product_id=' + info.value
            }).then(function (response) {
                var $response = $(response);

                var $panel = $response.find('.favorite-info-panel').add($response.filter('.favorite-info-panel'));
                InfoPanelUtil.open($panel);
            });
        }
    });

    $(document).on('shop_update_compare@market:global', function (e, info) {
        if (info.action === 'add') {
            $.ajax({
                url: config.shop.compare_url + '?product_id=' + info.value
            }).then(function (response) {
                var $response = $(response);

                var $panel = $response.select('.compare-info-panel');
                InfoPanelUtil.open($panel);
            });
        }
    });

    $(document).on('shop_cart_add@market:global', function () {
        market.Analytics.reachGoal('add_to_cart');
    });

    $(document).on('products_updated@market:global', function () {
        if (typeof $.autobadgeFrontend !== 'undefined') {
            $.autobadgeFrontend.reinit();
        }
    });
})(jQuery);
