(/** @param {JQueryStatic} $ */ function ($) {
    var market = window.market;
    var config = market.config;

    var translate = market.translate = function (string) {
        if (config['language'][string]) {
            return config['language'][string];
        }

        return string;
    };

    market.requestNextFrame = (function () {
        let callbackQueue = [];
        let messageName = 'zero-timeout-message';

        window.addEventListener('message', function (e) {
            if (e.source === window && e.data === messageName) {
                e.stopPropagation();

                if (callbackQueue.length > 0) {
                    callbackQueue.shift()();
                }
            }
        }, true);

        return function (fn) {
            callbackQueue.push(fn);
            window.postMessage(messageName, '*');
        };
    })();

    $.fn.select = function (selector) {
        var $this = $(this);
    
        var $find_result = $this.find(selector);
        var $filter_result = $this.filter(selector);
    
        if ($find_result.length > 0 && $filter_result.length > 0) {
            return $filter_result.add($filter_result);
        } else if ($filter_result.length > 0) {
            return $filter_result;
        } else if ($find_result.length > 0) {
            return $find_result;
        } else {
            return $();
        }
    };
    
    $.fn.replace = function ($element) {
        if (this.length === 0 || $element.length === 0) {
            return;
        }
    
        this.each(function () {
            var $fragment = $(document.createDocumentFragment());
            $fragment.append($element);
    
            if (this.parentNode) {
                this.parentNode.replaceChild($fragment.get(0), this);
            }
        });
    };
    
    $.fn.onAnimationEnd = function (selector, callback) {
        var $this = $(this);
    
        if (callback === undefined) {
            callback = selector;
            selector = undefined;
        }
    
        var handle = function (e) {
            if (e.currentTarget === e.target) {
                if (typeof callback === 'function') {
                    callback.apply(this, arguments);
                }
            }
        };
    
        if (selector) {
            $this.on('animationend', selector, handle);
        } else {
            $this.on('animationend', handle);
        }
    };
    
    $.fn.cssAnimation = function (class_name) {
        $(this).each(function () {
            var $element = $(this);
            var data = {
                current_animation_class: null
            };
    
            var handleAnimationEnd = function (e) {
                if (e.target !== e.currentTarget) {
                    return;
                }
    
                var data = $element.data('market_animation');
                $element.removeClass(data.current_animation_class);
                $element.removeClass(data.animation_class);
            };
    
            $element.on('animationend', handleAnimationEnd);
    
            data.animation_class = class_name;
    
            $element.data('market_animation', data);
        });
    };
    
    $.fn.runCssAnimation = function (animation_class_name) {
        $(this).each(function () {
            var $element = $(this);
            var data = $element.data('market_animation');
    
            if (!data) {
                return;
            }
    
            if (data.current_animation_class) {
                $element.removeClass(data.current_animation_class);
            }
    
            data.current_animation_class = animation_class_name;
            $element.addClass(data.animation_class);
            // $element.offset();
            $element.addClass(data.current_animation_class);
    
            $element.data('market_animation', data);
        });
    };
    
    $.fn.zoom = (function () {
        var Zoom = function ($img, options) {
            var that = this;
            that.$img = $img;
            that.options = options;
            that.options.$container = that.options.$container || $();
    
            var last_x = 0;
            var last_y = 0;
            var zoom_rate = 1;
    
            var move_request_id = null;
    
            var handleMouseMove = function (e) {
                var x = e.offsetX;
                var y = e.offsetY;
                cancelAnimationFrame(move_request_id);
                move_request_id = requestAnimationFrame(function () {
                    updatePosition(x, y);
                }, that.$wrapper[0]);
            };
    
            var handleMouseWheel = function (e) {
                e.preventDefault();
                var is_selected_width = that.$zoom_img.width() / that.$zoom_img.height() < that.$container.width() / that.$container.height();
    
                that.$zoom_img.width('');
                that.$zoom_img.height('');
                var width = that.$zoom_img.width();
                var height = that.$zoom_img.height();
                zoom_rate += -Math.sign(e.originalEvent.deltaY) * 0.1;
    
                if (is_selected_width) {
                    that.$zoom_img.width(width * zoom_rate);
                } else {
                    that.$zoom_img.height(height * zoom_rate);
                }
    
                zoom_rate = that.$zoom_img.width() / width;
    
                updatePosition(last_x, last_y);
            };
    
            var handleMouseOut = function () {
                cancelAnimationFrame(move_request_id);
                that.$container.detach();
                that.$wrapper.removeClass('zoom_active');
            };
    
            var updatePosition = function (offset_x, offset_y) {
                that.$wrapper.addClass('zoom_active');
                that.$zone.css('height', that.$container.height() * rate);
                that.options.$container.html(that.$container);
                var rate = $img.width() / that.$zoom_img.width();
                last_x = offset_x;
                last_y = offset_y;
    
                that.$zone.css('width', that.$container.width() * rate);
                that.$zone.css('height', that.$container.height() * rate);
    
                var x = offset_x - that.$zone.width() / 2;
                var y = offset_y - that.$zone.height() / 2;
                that.$container.scrollTop(y / rate);
                that.$container.scrollLeft(x / rate);
                that.$zone.css('top', that.$container.scrollTop() * rate);
                that.$zone.css('left', that.$container.scrollLeft() * rate);
            };
    
            var updateSize = function () {
                if (!that.$img.get(0).complete) {
                    that.$img.one('load', updateSize);
    
                    return;
                }
    
                that.$wrapper.css('width', that.$img.width());
                that.$wrapper.css('height', that.$img.height());
            };
    
            var initEventListeners = function () {
                that.$img.on('mousemove', handleMouseMove);
                that.$img.on('wheel', handleMouseWheel);
                that.$img.on('mouseout', handleMouseOut);
                $(window).on('resize', updateSize);
            };
    
            var destroyEventListeners = function () {
                that.$img.off('mousemove', handleMouseMove);
                that.$img.off('wheel', handleMouseWheel);
                that.$img.off('mouseout', handleMouseOut);
                $(window).off('resize', updateSize);
            };
    
            var initDom = function () {
                var block_class = that.options.block_class || 'zoom';
                that.$img.wrap('<div></div>');
                that.$wrapper = that.$img.parent();
                that.$zone = $('<div></div>');
                that.$wrapper.append(that.$zone);
                that.$zoom_img = $('<img src="" alt="" />');
                that.$zoom_img.attr('src', that.$img.data('zoom_src'));
                that.$container = $('<div></div>');
                that.$container.append(that.$zoom_img);
    
                that.$wrapper.addClass(block_class);
                that.$wrapper.addClass(block_class);
                that.$img.addClass(block_class + '__img');
                that.$zone.addClass(block_class + '__zone');
                that.$zoom_img.addClass(block_class + '__zoom-img');
                that.$container.addClass(block_class + '__container');
    
                updateSize();
            };
    
            var destroyDom = function () {
                that.$zone.remove();
                that.$img.unwrap();
                that.$img.removeClass('zoom-img');
            };
    
            var init = function () {
                initDom();
                initEventListeners();
            };
    
            that.destroy = function () {
                destroyEventListeners();
                destroyDom();
            };
    
            init();
        };
    
        return function (options) {
            var $this = this;
    
            $this.each(function () {
                var $this = $(this);
                var instance = $this.get(0).zoomInstance;
    
                if (instance) {
                    instance.destroy();
                }
    
                $this.get(0).zoomInstance = new Zoom($this, options);
            });
    
            if ($this.length === 1) {
                return $this.get(0).zoomInstnace;
            }
    
            return null;
        };
    })();
    
    var Update = market.Update = (function () {
        return function ($context) {
            $context = $context || $(document.body);
    
            $(document).trigger('update@market:global', [$context]);
        };
    })();
    
    var OnUpdate = market.OnUpdate = function (callback) {
        $(document).on('update@market:global', function (e, $context) {
            callback($context);
        });
    };
    
    var ComponentRegistry = market.ComponentRegistry = (function () {
        var all_elements = [];
    
        OnUpdate(function () {
            all_elements.forEach(function (element) {
                if (!document.body.contains(element)) {
                    var $element = $(element);
                    $element.trigger('destruct@market:global');
                    all_elements.splice(all_elements.indexOf(element), 1);
                }
            });
        });
    
        var _private = {
            hasInstance: function ($element, construct) {
                return _private.getInstance($element, construct) !== undefined;
            },
            getInstance: function ($element, construct) {
                var element = $element.get(0);
                element.market_components = element.market_components || new Map();
    
                return element.market_components.get(construct);
            },
            setInstance: function ($element, constructor, instance) {
                var element = $element.get(0);
                element.market_components = element.market_components || new Map();
    
                element.market_components.set(constructor, instance);
            }
        };
    
        return {
            register: function (find, construct, type) {
                var getInstance = function ($element) {
                    if ($element.length !== 1) {
                        console.error('not valid count elements: ' + $element.length);
    
                        return;
                    }
    
                    if (_private.hasInstance($element, construct)) {
                        return _private.getInstance($element, construct);
                    }
    
                    var element = $element.get(0);
                    var instance = {};
                    _private.setInstance($element, construct, instance);
                    all_elements.push(element);
                    var destructor = construct($element, instance);
    
                    if (typeof destructor === 'function' || typeof instance.destruct === 'function') {
                        $element.on('destruct@market:global', function () {
                            if (!document.body.contains(element)) {
                                if (typeof destructor === 'function') {
                                    destructor();
                                }
    
                                if (typeof instance.destruct === 'function') {
                                    instance.destruct();
                                }
                            }
                        });
                    }
    
                    return instance;
                };
    
                if (typeof find === 'function') {
                    OnUpdate(function ($context) {
                        market.requestNextFrame(function () {
                            find($context).each(function (i, elem) {
                                market.requestNextFrame(function () {
                                    getInstance($(elem));
                                });
                            });
                        });
                    });
                }
    
                return getInstance;
            }
        };
    })();
    
    market.BuildObserverUtil = function (tagnames, types) {
        var _private = {
            tagnames: tagnames,
    
            types: types,
    
            listeners: {
                nodes: new Map(),
                classes: new Map()
            },
    
            isListeningTarget: function (target) {
                if (!_private.tagnames) {
                    return true;
                }
    
                return _private.tagnames.indexOf(target.tagName) !== -1;
            },
    
            isListeningMutationType: function (type) {
                if (!_private.types) {
                    return true;
                }
    
                return _private.types.indexOf(type) !== -1;
            },
    
            isListeningMutation: function (mutation) {
                return this.isListeningMutationType(mutation.type) && this.isListeningTarget(mutation.target);
            },
    
            callObserver: function (classname, callback, node) {
                callback($(node));
            },
    
            compareNodeClassname: function (node, classname, override_classname) {
                var result = false;
    
                if (node.classList) {
                    result = node.classList.contains(classname);
                } else {
                    result = $(node).hasClass(classname);
                }
    
                if (!result && override_classname) {
                    result = new RegExp('\\b' + classname + '\\b').test(override_classname);
                }
    
                return result;
            },
    
            handleNodeMutation: function (node, listeners, override_classname) {
                var self = this;
    
                listeners.forEach(function (callback, classname) {
                    var is_current_node = self.compareNodeClassname(node, classname, override_classname);
    
                    if (is_current_node) {
                        _private.callObserver(classname, callback, node);
                    }
                });
            },
    
            handleMutation: function (mutation) {
                if (!_private.isListeningMutation(mutation)) {
                    return;
                }
    
                if (mutation.type === 'childList') {
                    let listeners = _private.listeners.nodes;
    
                    for (var i = 0; i < mutation.addedNodes.length; i++) {
                        var node = mutation.addedNodes[i];
    
                        if (node.nodeType === 1) {
                            _private.handleNodeMutation(node, listeners);
                        }
                    }
                } else if (mutation.type === 'attributes') {
                    if (mutation.attributeName === 'class') {
                        let listeners = _private.listeners.classes;
    
                        _private.handleNodeMutation(mutation.target, listeners, mutation.oldValue);
                    }
                }
            },
    
            handleMutations: function (mutations) {
                mutations.forEach(_private.handleMutation);
            }
        };
    
        return {
            inited: false,
    
            init: function () {
                var observer = new MutationObserver(_private.handleMutations);
    
                observer.observe(document, {
                    subtree: true,
                    childList: true,
                    attributes: true,
                    attributeOldValue: true
                });
    
                this.inited = true;
            },
    
            watchTag: function (tagname) {
                if (_private.tagnames.indexOf(tagname) !== -1) {
                    return;
                }
    
                _private.tagnames.push(tagname);
            },
    
            watchType: function (type) {
                if (_private.types.indexOf(type) !== -1) {
                    return;
                }
    
                _private.types.push(type);
            },
    
            observe: function (class_name, callback) {
                if (!this.inited) {
                    this.init();
                }
    
                _private.listeners.nodes.set(class_name, callback);
            },
    
            observeClass: function (class_name, callback) {
                if (!this.inited) {
                    this.init();
                }
    
                this.watchType('attributes');
                _private.listeners.classes.set(class_name, callback);
            },
    
            observeComponent: function (class_name, component) {
                this.observe(class_name, function ($node) {
                    component($node);
                });
            }
        };
    };
    
    market.ObserverUtil = market.BuildObserverUtil(['DIV', 'SPAN', 'A', 'BODY', 'SECTION'], ['childList']);
    
    market.HeaderOverlay = ComponentRegistry.register(function ($context) {
        return $context.select('.header-overlay');
    }, function ($header_overlay) {
        var _private = {
            initGlobalEventListeners: function () {
                $(document).on('open@market:horizontal-catalog', '.horizontal-catalog', function () {
                    if ($(this).data('is_overlay_enabled')) {
                        _private.closeExclude('horizontal-catalog');
    
                        _private.enable();
                    }
                });
    
                $(document).on('close@market:horizontal-catalog', '.horizontal-catalog', function () {
                    _private.disable();
                });
    
                $(document).on('open@market:catalog-dropdown', '.catalog-dropdown', function () {
                    if ($(this).data('is_overlay_enabled')) {
                        _private.closeExclude('catalog-dropdown');
    
                        _private.enable();
                    }
                });
    
                $(document).on('close@market:catalog-dropdown', '.catalog-dropdown', function () {
                    _private.disable();
                });
    
                $(document).on('open@market:brands-dropdown', '.brands-dropdown', function () {
                    _private.closeExclude('brands-dropdown');
    
                    _private.enable();
                });
    
                $(document).on('close@market:brands-dropdown', '.brands-dropdown', function () {
                    _private.disable();
                });
    
                $(document).on('open@market:header-bottom-bar-links', '.header-bottom-bar-links', function () {
                    if ($(this).data('is_overlay_enabled')) {
                        _private.closeExclude('header-bottom-bar-links');
    
                        _private.enable();
                    }
                });
    
                $(document).on('close@market:header-bottom-bar-links', '.header-bottom-bar-links', function () {
                    _private.disable();
                });
    
                $(document).on('open@market:sidebar-catalog', '.sidebar-catalog', function () {
                    if ($(this).data('is_overlay_enabled')) {
                        _private.closeExclude('sidebar-catalog');
    
                        _private.enable();
                    }
                });
    
                $(document).on('close@market:sidebar-catalog', '.sidebar-catalog', function () {
                    _private.disable();
                });
    
                $(document).on('close@market:header-bottom-bar-links', '.header-bottom-bar-links', function () {
                    _private.disable();
                });
    
                $(document).on('open@market:r-header-menu', '.r-header-menu', function () {
                    _private.enable();
                });
    
                $(document).on('close@market:r-header-menu', '.r-header-menu', function () {
                    _private.disable();
                });
    
                $(document).on('open@market:brand-alphabet', '.brand-alphabet', function () {
                    _private.enable();
                });
    
                $(document).on('close@market:brand-alphabet', '.brand-alphabet', function () {
                    _private.disable();
                });
    
                $(document).on('open@market:r-header-contacts', '.r-header-contacts', function () {
                    _private.enable();
                });
    
                $(document).on('close@market:r-header-contacts', '.r-header-contacts', function () {
                    _private.disable();
                });
            },
    
            isEnabled: function () {
                return $header_overlay.hasClass('header-overlay_enabled');
            },
    
            enable: function () {
                if (_private.isEnabled()) {
                    return;
                }
    
                $header_overlay.addClass('header-overlay_enabled');
            },
    
            disable: function () {
                if (!_private.isEnabled()) {
                    return;
                }
    
                $header_overlay.removeClass('header-overlay_enabled');
                $header_overlay.trigger('disable@market:header-overlay');
            },
    
            closeExclude: function (exclude) {
                if (exclude !== 'catalog-dropdown') {
                    _private.closeCatalogDropdown();
                }
    
                if (exclude !== 'horizontal-catalog') {
                    _private.closeHorizontalCatalog();
                }
    
                if (exclude !== 'brands-dropdown') {
                    _private.closeBrandsDropdown();
                }
    
                if (exclude !== 'header-bottom-bar-links') {
                    _private.closeHeaderBottomBarLinks();
                }
    
                if (exclude !== 'sidebar-catalog') {
                    _private.closeSidebarCatalog();
                }
    
                if (exclude !== 'brand-alphabet') {
                    _private.closeBrandAlphabet();
                }
            },
    
            closeCatalogDropdown: function () {
                $('.catalog-dropdown').each(function () {
                    var dropdown = market.CatalogDropdown($(this));
                    dropdown.close();
                });
            },
    
            closeHorizontalCatalog: function () {
                $('.horizontal-catalog').each(function () {
                    var catalog = market.HorizontalCatalog($(this));
                    catalog.close();
                });
            },
    
            closeBrandsDropdown: function () {
                $('.brands-dropdown').each(function () {
                    var dropdown = market.BrandsDropdown($(this));
                    dropdown.close();
                });
            },
    
            closeHeaderBottomBarLinks: function () {
                $('.header-bottom-bar-links').each(function () {
                    var links = market.HeaderBottomBarLinks($(this));
                    links.closeDropdowns();
                });
            },
    
            closeSidebarCatalog: function () {
                $('.sidebar-catalog').each(function () {
                    var catalog = market.SidebarCatalog($(this));
                    catalog.close();
                });
            },
    
            closeBrandAlphabet: function () {
                $('.sidebar-catalog').each(function () {
                    var alphabet = market.BrandAlphabet($(this));
                    alphabet.close();
                });
            }
        };
    
        _private.initGlobalEventListeners();
    });
    
    (function () {
        let observer = new IntersectionObserver(function (entries, observer) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) {
                    return;
                }
    
                let image = entry.target;
                let $image = $(image);
    
                if ($image.data('srcset')) {
                    $image.attr('srcset', $image.data('srcset'));
                }
    
                $image.attr('src', $image.data('src'));
                let $picture = $image.parent('picture');
    
                if ($picture.length) {
                    $picture.find('source').each(function () {
                        let $source = $(this);
                        $source.attr('srcset', $source.data('srcset'));
                    });
                }
    
                if (image.complete) {
                    $image.removeClass('lazy-image');
                } else {
                    $image.one('load', function () {
                        $image.addClass('lazy-image_ready');
                    });
                }
    
                observer.unobserve($image.get(0));
            });
        }, {
            threshold: 0.01
        });
    
        market.LazyImage = ComponentRegistry.register(function ($context) {
            return $context.select('.lazy-image');
        }, function ($image) {
            observer.observe($image.get(0));
        });
    })();
    
    market.LazyImageProvider = ComponentRegistry.register(function ($context) {
        return $context.select('.lazy-image-provider');
    }, function ($provider) {
        var provider = $provider[0];
    
        var $image = $('<img/>');
        $.each(provider.attributes, function () {
            var name = this.name;
    
            if (this.name === 'data-width') {
                name = 'width';
            }
    
            if (this.name === 'data-height') {
                name = 'height';
            }
    
            $image.attr(name, this.value);
        });
        $image.removeClass('lazy-image-provider').addClass('lazy-image');
        $image.attr('alt', $(provider).data('alt'));
    
        $provider.replaceWith($image);
    
        $(document).trigger('lazy_image_provide@market:global', [$image]);
    });
    
    var MatchMedia = market.MatchMedia = function (media_query) {
        var matchMedia = window.matchMedia;
        var is_supported = (typeof matchMedia === 'function');
    
        if (is_supported && media_query) {
            return matchMedia(media_query).matches;
        } else {
            return false;
        }
    };
    
    market.Responsive = (function () {
        var Responsive = ComponentRegistry.register(function ($context) {
            return $();
        }, function ($responsive) {
            var _private = {
                initGlobalEventListeners: function () {
                    $(window).on('resize', _private.update);
                },
    
                destroyGlobalEventListeners: function () {
                    $(window).off('resize', _private.update);
                },
    
                update: function () {
                    if (_private.isRender()) {
                        _private.render();
                    }
                },
    
                isRender: function () {
                    return (ResponsiveUtil.isMobileMax() && $responsive.hasClass('responsive_mobile-max'))
                      || (ResponsiveUtil.isTabletMax() && $responsive.hasClass('responsive_tablet-max'))
                      || (ResponsiveUtil.isTabletMinMax() && $responsive.hasClass('responsive_tablet-min-max'))
                      || (ResponsiveUtil.isDesktopMin() && $responsive.hasClass('responsive_desktop-min'));
                },
    
                render: function () {
                    var div = document.createElement('div');
                    div.innerHTML = $responsive.data('content');
    
                    var fragment = document.createDocumentFragment();
                    div.childNodes.forEach(function (node) {
                        fragment.appendChild(node);
                    });
                    var responsive = $responsive.get(0);
                    var parent = responsive.parentNode;
                    parent.replaceChild(fragment, responsive);
    
                    $(function () {
                        Update($(parent));
                    });
                }
            };
    
            if (_private.isRender()) {
                _private.render();
    
                return;
            }
    
            _private.initGlobalEventListeners();
    
            return function () {
                _private.destroyGlobalEventListeners();
            };
        });
    
        market.ObserverUtil.observeComponent('responsive', Responsive);
    
        return Responsive;
    })();
    
    var ResponsiveUtil = market.ResponsiveUtil = (function () {
        var is_mobile_max = null;
        var is_tablet_max = null;
        var is_tablet_min_max = null;
        var is_desktop_min = null;
        var is_desktop_l_min = null;
        var reloading = false;
    
        var update = function () {
            is_mobile_max = MatchMedia('(max-width: 767px)');
            is_tablet_max = MatchMedia('(max-width: 1023px)');
            is_tablet_min_max = MatchMedia('(min-width: 768px) and (max-width: 1023px)');
            is_desktop_min = MatchMedia('(min-width: 1024px)');
            is_desktop_l_min = MatchMedia('(min-width: 1200px)');
            window.bodyWidth = document.documentElement.offsetWidth;
            $.cookie('is_mobile', is_tablet_max);
    
            if (!reloading && is_tablet_max != config['commons']['is_mobile'] && config['commons']['is_cookie']) {
                reloading = true;
    
                if (window.location.host !== 'webcache.googleusercontent.com') {
                    window.location.reload();
                }
            }
        };
    
        $(window).on('resize', update);
        update();
    
        return {
            isMobileMax: function () {
                return is_mobile_max;
            },
            isTabletMax: function () {
                return is_tablet_max;
            },
            isTabletMinMax: function () {
                return is_tablet_min_max;
            },
            isDesktopMin: function () {
                return is_desktop_min;
            },
            isDesktopLargeMin: function () {
                return is_desktop_l_min;
            },
            hasNativeSelect: function () {
                return (window.navigator.userAgent.match(/(iPad|iPhone|iPod)/i)
                  && !window.navigator.userAgent.match(/(Windows\sPhone)/i))
                    ? true
                    : false;
            },
            isIE: function () {
                return (window.navigator.userAgent.match(/(?:msie|trident)/i)) ? true : false;
            }
        };
    })();
    
    (function () {
        if (ResponsiveUtil.isIE()) {
            var toggle = DOMTokenList.prototype.toggle;
    
            DOMTokenList.prototype.toggle = function (className, enable) {
                if (enable === undefined) {
                    toggle.apply(this, arguments);
                } else {
                    enable ? this.add(className) : this.remove(enable);
                }
            };
        }
    })();
    
    market.FormDecorator = (function () {
        var FormDecorator = ComponentRegistry.register(function () {
            return $('.form-decorator');
        }, function ($form_decorator, self) {
            var _private = {
                form_size: $form_decorator.data('form_size'),
                field_size: $form_decorator.data('field_size'),
                visible: null,
    
                decorateParagraphs: function ($field) {
                    var $content = $field.find('.form-field__value');
                    var $p = $content.children('p');
    
                    if ($p.length > 1) {
                        var $subfields = $('<p class="form-field__subfields"></p>');
                        var $subfield = $('<div class="form-field__subfield"></div>');
    
                        $p.each(function () {
                            $subfield.clone().append($(this).contents()).appendTo($subfields);
                        });
    
                        $p.remove();
                        $content.append($subfields);
                    } else if ($p.length > 0) {
                        $p.contents().unwrap();
                        $p.remove();
                    }
                },
    
                decorateLabels: function ($field) {
                    var $content = $field.find('.form-field__value');
                    var $labels = $content.children('label');
    
                    $labels.each(function () {
                        var $label = $(this);
    
                        if (!$label.get(0).control && $label.find('input').length === 0) {
                            $($label.contents()).unwrap();
                        }
                    });
    
                    $labels = $content.children('label');
    
                    if ($labels.length > 0) {
                        var $_labels = $('<div class="form-field__labels"></div>');
                        var $label = $('<div class="form-field__label"></div>');
    
                        $labels.each(function () {
                            $label.clone().append($(this)).appendTo($_labels);
                        });
    
                        $content.prepend($_labels);
                    }
                },
    
                decorateSubFields: function ($field) {
                    var $content = $field.find('.form-field__value');
                    var $fields = $content.find('.field').add($content.children('span:not([class]):not([id])'));
    
                    if ($fields.length > 0) {
                        $field.addClass('form-field_complex');
    
                        $fields.parent().each(function () {
                            var $container = $(this);
                            var $subfields = $('<p class="form-field__subfields"></p>');
                            var $subfield = $('<div class="form-field__subfield"></div>');
    
                            $container.find('.field').add($content.children('span:not([class]):not([id])')).each(function () {
                                var $field = $(this);
                                $field.addClass('form-field');
                                var $name = $field.children('span');
                                var name = $name.text();
    
                                $name.addClass('form-field__name');
    
                                if (name.substring(-1) !== ':') {
                                    $name.text(name.concat(':'));
                                }
    
                                var $content_container = $('<div class="form-field__value"></div>');
                                $content_container.append($field.contents());
                                $field.append($name);
                                $field.append($content_container);
                                $subfield.clone().append($field).appendTo($subfields);
                            });
    
                            $container.append($subfields);
                        });
                    }
                },
    
                wrapLabels: function () {
                    var $labels = $form_decorator.find('label').filter(function () {
                        return $(this).parents('.wa-field').length === 0;
                    });
    
                    $labels.wrap('<div class="wa-field"></div>');
                    $labels.wrap('<div class="wa-value"></div>');
                },
    
                getWaFields: function () {
                    return $form_decorator.find('.wa-field').filter(function () {
                        return $(this).parents('.wa-field').length === 0;
                    });
                },
    
                decorateWaFields: function () {
                    var $all_fields = _private.getWaFields();
    
                    var $form = $all_fields.parent();
    
                    $form.each(function () {
                        var $form = $(this);
                        $form.addClass('form');
    
                        if (_private.form_size) {
                            $form.addClass('form_size_' + _private.form_size);
                        }
    
                        var $fields = $form.children($all_fields);
                        var $main_fields_container = $('<div class="form__fields"></div>');
                        var $submit_fields_container = $('<div class="form__fields"></div>');
                        var $name = $();
    
                        $fields.each(function () {
                            var $field = $(this);
    
                            var is_only_name = $field.children().length === 1 && $field.children().is('.wa-name');
    
                            if (is_only_name) {
                                $name = $field.children().detach();
                                $field.remove();
    
                                return;
                            }
    
                            if ($name.length > 0) {
                                $field.prepend($name);
                                $name = $();
                            }
    
                            $field.addClass('form__field');
                            var is_submit = $field.has('.wa-submit').length > 0;
                            var is_error = $field.has('.wa-value.wa-errormsg').length > 0;
    
                            if (is_submit || is_error) {
                                $field.appendTo($submit_fields_container);
                            } else {
                                $field.appendTo($main_fields_container);
                            }
                        });
    
                        var is_empty_submit = $submit_fields_container.children().length === 0;
    
                        $('<div class="form__group"></div>').append($main_fields_container).appendTo($form);
    
                        if (!is_empty_submit) {
                            $('<div class="form__group"></div>').append($submit_fields_container).appendTo($form);
                        }
                    });
    
                    $all_fields.each(function () {
                        var $field = $(this);
    
                        _private.decorateWaField($field);
                    });
                },
    
                decorateWaField: function ($field) {
                    var is_submit = $field.has('.wa-submit').length > 0;
                    var is_error = $field.has('.wa-value.wa-errormsg').length > 0;
                    var is_checkbox = $field.has('input[type="hidden"] + input[type="checkbox"]').length > 0;
    
                    if (is_submit || is_error) {
                        _private.decorateFormRow($field);
                    } else {
                        $field.addClass('form-field');
    
                        if (_private.field_size) {
                            $field.addClass('form-field_size_' + _private.field_size);
                        }
    
                        if (is_checkbox) {
                            _private.decorateCheckboxField($field);
                        } else {
                            _private.decorateFormField($field);
                        }
                    }
                },
    
                decorateFormRow: function ($field) {
                    $field.addClass('form-row');
    
                    if (_private.field_size) {
                        $field.addClass('form-row_size_' + _private.field_size);
                    }
    
                    $field.find('.wa-value.wa-errormsg').addClass('error error_text');
                },
    
                decorateCheckboxField: function ($field) {
                    $field.prepend('<div class="form-field__name-container"></div>');
                    var $content = $field.find('.wa-value');
                    var $label = $field.find('.wa-name');
                    $content.append($label);
                    $content.addClass('form-field__value');
    
                    if (!$content.find('label:first').parent().is($content)) {
                        $content.wrapInner('<label></label>');
                    }
                },
    
                decorateFormField: (function () {
                    return function ($field) {
                        var $label = $field.find('.wa-name');
                        $label.each(function () {
                            var $label = $(this);
                            var label_text = $label.text();
    
                            if (label_text.length > 0 && label_text.substring(-1) !== ':') {
                                label_text = label_text.trim().concat(':');
                                $label.text(label_text);
                            }
    
                            $label.addClass('form-field__name');
                        });
    
                        var $value = $field.find('.wa-value');
                        $value.addClass('form-field__value');
    
                        $field.find('.wa-captcha, .wa-captcha-section').addClass('captcha-decorator').each(function () {
                            CaptchaDecorator($(this));
                        });
    
                        var $error = $field.find('.wa-error-msg, .errormsg');
                        $error.addClass('form-field__error-container');
    
                        _private.decorateParagraphs($field);
                        _private.decorateLabels($field);
                        _private.decorateSubFields($field);
                    };
                })()
            };
    
            $.extend(self, {
                initFields: function () {
                    _private.wrapLabels();
                    _private.decorateWaFields();
                    self.initWaFieldsAddress();
                    self.initWaFieldBirthday();
                },
    
                initWaFieldsAddress: function () {
                    $form_decorator.find('.wa-field-address').each(function () {
                        var $field = $(this);
    
                        var initRegionField = function () {
                            var $region_fields = $field.find('.wa-field-address-region');
    
                            $region_fields.each(function () {
                                var $region_field = $(this);
                                var $select = $region_field.find('select');
                                var $wrapper = $('<div></div>');
                                $select.replaceWith($wrapper);
                                $wrapper.append($select);
                                var id = $select.attr('id');
                                var $input = $region_field.find('#' + id + '-input');
    
                                var updateVisibility = function () {
                                    var is_input = $input.is(':visible');
    
                                    if (is_input) {
                                        $wrapper.hide();
                                    } else {
                                        $wrapper.show();
                                    }
                                };
    
                                $field.find('.wa-field-address-country').on('change', function () {
                                    updateVisibility();
                                    var $loading = $region_field.find('.loading');
                                    $loading.addClass('form-field__spinner-container');
                                    var $spinner = $(config.commons.svg.spinner);
                                    $spinner.find('.svg-icon').width(24);
                                    $spinner.find('.svg-icon').height(24);
                                    $loading.append($spinner);
                                });
    
                                $(document).on('shop:data_regions_send', function () {
                                    updateVisibility();
                                });
    
                                $(document).on('shop:data_regions_success', function () {
                                    updateVisibility();
                                });
                            });
                        };
    
                        initRegionField();
                    });
    
                    $form_decorator.find('.wa-field-region').each(function () {
                        var $region_field = $(this);
                        var $select = $region_field.find('select');
                        $select.wrap('<div></div>');
                        var $wrapper = $select.parent();
                        var id = $select.attr('id');
                        var $input = $region_field.find('#' + id + '-input');
    
                        var updateVisibility = function () {
                            var is_input = $input.is(':visible');
    
                            if (is_input) {
                                $wrapper.hide();
                            } else {
                                $wrapper.show();
                            }
                        };
    
                        $form_decorator.find('.wa-field-country').on('change', function () {
                            updateVisibility();
                            $region_field.find('.loading').append(config.commons.svg.spinner);
                        });
    
                        $(document).on('shop:data_regions_send', function () {
                            updateVisibility();
                        });
    
                        $(document).on('shop:data_regions_success', function () {
                            updateVisibility();
                        });
    
                        updateVisibility();
                    });
                },
    
                initWaFieldBirthday: function () {
                    $form_decorator.find('.wa-field-birthday').each(function () {
                        $(this).addClass('form-field_birthday');
                        $(this).find('input').attr('placeholder', translate('Год'));
                    });
                },
    
                initInputText: function () {
                    var $inputs = $form_decorator.find('input:not([type]), input[type="text"], input[type="password"], input[type="url"], input[type="tel"], input[type="email"]');
                    $inputs.addClass('input-text');
                    $inputs.filter('.wa-error, .error').addClass('input-text_error');
    
                    $inputs.each(function () {
                        var $input = $(this);
                        var $field = $input.closest('.field');
                        var placeholder = '';
    
                        if ($field.length) {
                            placeholder = $field.children('span, .form-field__name').text();
                        } else {
                            $field = $input.closest('.wa-field');
    
                            if ($field.length) {
                                placeholder = $field.find('.wa-name').text();
                            }
                        }
    
                        placeholder = placeholder.trim();
    
                        if (placeholder.substring(-1) === ':') {
                            placeholder = placeholder.substring(0, placeholder.length - 1);
                        }
    
                        placeholder = placeholder.trim();
    
                        if (placeholder && $input.closest('.form-field_birthday').length === 0) {
                            $input.attr('placeholder', placeholder);
                        }
                    });
                },
    
                initTextarea: function () {
                    var $textareas = $form_decorator.find('textarea');
                    $textareas.addClass('textarea');
                    $textareas.filter('.wa-error, .error').addClass('textarea_error');
    
                    $textareas.each(function () {
                        var $textarea = $(this);
                        var $field = $textarea.closest('.field');
                        var placeholder = '';
    
                        if ($field.length) {
                            placeholder = $field.children('span, .form-field__name').text();
                        } else {
                            $field = $textarea.closest('.wa-field');
    
                            if ($field.length) {
                                placeholder = $field.find('.wa-name').text();
                            }
                        }
    
                        placeholder = placeholder.trim();
    
                        if (placeholder.substring(-1) === ':') {
                            placeholder = placeholder.substring(0, placeholder.length - 1);
                        }
    
                        placeholder = placeholder.trim();
    
                        if (placeholder) {
                            $textarea.attr('placeholder', placeholder);
                        }
                    });
                },
    
                initInputFile: function () {
                    market.InputFile.create($form_decorator.find('input[type="file"]'));
                },
                initSelect: function () {
                    var $select = $form_decorator.find('select');
                    /* NOVAPOSHTA FIX */
                    var $filteredSelect = $select.not('[id^="np2_cities_"]').not('[id^="np2_wh_select"]').not('[id^="np2_street_select"]');
                    market.Select.create($filteredSelect);
                },
                initCheckbox: function () {
                    market.Checkbox.create($form_decorator.find('input[type="checkbox"]'));
                },
                initRadio: function () {
                    market.Radio.create($form_decorator.find('input[type="radio"]'));
                },
                initButton: function () {
                    var $buttons = $form_decorator.find('button, input[type="button"], input[type="submit"], input[type="reset"]');
    
                    $buttons.addClass('button');
                    $buttons.filter('.transparent').addClass('button_style_transparent');
                },
                initLinks: function () {
                    $form_decorator.find('a').addClass('link');
                },
    
                initPhoneField: function () {
                    $form_decorator.find('.wa-field-phone input, .wa-phone').attr('type', 'tel');
                }
            });
    
            self.initFields();
            self.initLinks();
            self.initInputText();
            self.initTextarea();
            self.initInputFile();
            self.initSelect();
            self.initCheckbox();
            self.initRadio();
            self.initButton();
            self.initPhoneField();
    
            $form_decorator.addClass('form-decorator_js-is-init');
        });
    
        market.ObserverUtil.observe('form-decorator__trigger', function ($node) {
            FormDecorator($node.closest('.form-decorator'));
        });
    
        return FormDecorator;
    })();
    
    market.ContentDecorator = (function () {
        var ContentDecorator = ComponentRegistry.register(function ($context) {
            return $context.select('.content-decorator');
        }, function ($content_decorator, self) {
            $.extend(self, {
                initForm: function () {
                    self.getForm().each(function () {
                        var $form = $(this);
                        $form.addClass('form-decorator');
    
                        market.FormDecorator($form);
                    });
                },
                getForm: function () {
                    return $content_decorator.find('form');
                }
            });
    
            self.initForm();
        });
    
        market.ObserverUtil.observe('content-decorator__trigger', function ($node) {
            ContentDecorator($node.closest('.content-decorator'));
        });
    
        return ContentDecorator;
    })();
    
    market.Radio = {
        create: function ($radio) {
            $radio.each(function () {
                var $control = $(this);
                var radioHtml = config.commons.radio_html;
    
                if ($control.hasClass('radio__control') || !$control.is('input')) {
                    return;
                }
    
                if ($control.closest('label').length > 0) {
                    radioHtml = radioHtml.replace(/label/, 'span');
                }
    
                var $radio = $(radioHtml);
    
                $control.replace($radio);
                $control.addClass('radio__control');
                $radio.find('.radio__control').replaceWith($control);
            });
        }
    };
    
    market.Checkbox = {
        create: function ($checkbox, options = {}) {
            $checkbox.each(function () {
                var $control = $(this);
                var checkboxHtml = config.commons.checkbox_html;
    
                if ($control.hasClass('checkbox__control') || !$control.is('input')) {
                    return;
                }
    
                if ($control.closest('label').length > 0) {
                    checkboxHtml = checkboxHtml.replace(/label/, 'span');
                }
    
                var $checkbox = $(checkboxHtml);
    
                $control.replace($checkbox);
                $control.addClass('checkbox__control');
                $checkbox.find('.checkbox__control').replaceWith($control);
            });
        }
    };
    
    market.CheckboxDecorator = (function () {
        var CheckboxDecorator = ComponentRegistry.register(function ($context) {
            return $context.select('.checkbox-decorator');
        }, function ($control) {
            market.Checkbox.create($control);
            $control.removeClass('checkbox-decorator');
        });
    
        market.ObserverUtil.observeComponent('checkbox-decorator', CheckboxDecorator);
    
        return CheckboxDecorator;
    })();
    
    market.Select = ComponentRegistry.register(function ($context) {
        return $context.select('.select:not(select)');
    }, function ($select) {
        var _private = {
            $select: $select.find('.select__control'),
            tree: null,
            has_change: false,
            request_update: false,
            visible: false,
            is_clicked: false,
    
            initObserver: function () {
                var observerCreate = new MutationObserver(function () {
                    _private.buildTree();
                });
                observerCreate.observe(_private.$select.get(0), {
                    subtree: true,
                    childList: true
                });
                var observerUpdate = new MutationObserver(function (entities) {
                    var has_update = false;
    
                    entities.forEach(function (entity) {
                        if (entity.attributeName !== 'id') {
                            has_update = true;
                        }
                    });
    
                    if (!has_update) {
                        return;
                    }
    
                    _private.updateTree();
                });
                observerUpdate.observe(_private.$select.get(0), {
                    subtree: true,
                    attributes: true,
                    characterData: true
                });
            },
    
            handleMouseUp: function () {
                _private.is_clicked = false;
            },
    
            handleResize: (function () {
                let requestUpdate = false;
    
                return function () {
                    if (requestUpdate) {
                        return;
                    }
    
                    requestUpdate = true;
                    market.requestNextFrame(function () {
                        requestUpdate = false;
                        _private.updateWidth();
                    }, $select[0]);
                };
            })(),
    
            initEventListeners: function () {
                _private.$select.on('refresh', function (e) {
                    _private.has_change = true;
                    _private.updateTree();
                });
    
                _private.$select.on('change', function (e) {
                    _private.updateTree();
    
                    if (_private.isOpen()) {
                        e.preventDefault();
                        e.stopPropagation();
                        e.stopImmediatePropagation();
    
                        _private.has_change = true;
                    }
                });
    
                $select.on('mousedown', function () {
                    _private.is_clicked = true;
                });
    
                $(document).on('mouseup', _private.handleMouseUp);
    
                $(window).on('resize', _private.handleResize);
    
                _private.$select.on('blur', function (e) {
                    if (_private.is_clicked) {
                        e.preventDefault();
                        _private.is_clicked = false;
                        _private.$select.focus();
    
                        return;
                    }
    
                    _private.close();
                });
    
                _private.$select.on('keydown', function (e) {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                    }
    
                    if (e.key === 'Enter') {
                        _private.toggle();
                    }
    
                    if (e.key === ' ') {
                        _private.open();
                    }
                });
    
                $select.on('click', '.select__option', function () {
                    var node = $(this).get(0)['market_select@node'];
    
                    if (!node || node.disabled || (node.optgroup && node.optgroup.disabled)) {
                        return;
                    }
    
                    $(node.control).prop('selected', true);
                    $(node.control).trigger('change');
    
                    _private.close();
                });
    
                $select.on('click', '.select__box', function () {
                    if (_private.isOpen()) {
                        _private.close();
                    } else {
                        _private.open();
                    }
                });
    
                $select.on('animationstart', function (e) {
                    if (e.originalEvent.animationName === 'market_select_appear') {
                        _private.updateTree();
                    }
                });
    
                $(window).on('resize', function () {
                    _private.updateTree();
                });
    
                $select.find('.select__dropdown').cssAnimation('select__dropdown_animated');
            },
    
            destroyEventListeners: function () {
                $(document).on('mouseup', _private.handleMouseUp);
    
                $(window).on('resize', _private.handleResize);
            },
    
            getSelected: function () {
                var $options = _private.$select.find('option');
                var $option = $();
    
                $options.each(function () {
                    if ($(this).prop('selected')) {
                        $option = $(this);
                    }
                });
    
                if ($option.length > 0) {
                    return $option.get(0)['market_select@node'];
                } else {
                    return null;
                }
            },
    
            isOpen: function () {
                return $select.hasClass('select_open');
            },
    
            open: function () {
                if (_private.isOpen()) {
                    return;
                }
    
                if (ResponsiveUtil.hasNativeSelect()) {
                    return;
                }
    
                $select.addClass('select_open');
    
                var $dropdown = $select.find('.select__dropdown');
                $dropdown.removeClass('select__dropdown_up');
    
                if ($dropdown.get(0).getBoundingClientRect().bottom > window.innerHeight) {
                    $dropdown.addClass('select__dropdown_up');
                }
    
                $dropdown.runCssAnimation('select__dropdown_open-animation');
                _private.scrollToSelected();
            },
    
            close: function () {
                if (!_private.isOpen()) {
                    return;
                }
    
                $select.removeClass('select_open');
                $select.find('.select__dropdown').runCssAnimation('select__dropdown_close-animation');
    
                if (_private.has_change) {
                    _private.$select.trigger('change');
                    _private.has_change = false;
                }
            },
    
            toggle: function () {
                if (_private.isOpen()) {
                    _private.close();
                } else {
                    _private.open();
                }
            },
    
            updateBox: function () {
                var node = _private.getSelected();
    
                if (!node) {
                    return;
                }
    
                var $box = $select.find('.select__box');
                var $content = $box.find('.select__content');
    
                $content.html(node.label);
            },
    
            updateWidth: function () {
                var $dropdown = $select.find('.select__dropdown');
                var is_open = $select.hasClass('select_open');
                $select.removeClass('select_init');
                $select.removeClass('select_open');
    
                $dropdown.get(0).style.width = '';
                $select.get(0).style.width = '';
    
                var body_width = window.bodyWidth;
                var width = Math.max($dropdown.outerWidth(), $select.outerWidth());
                var overflow = $dropdown.get(0).getBoundingClientRect().right + 5 - body_width;
    
                if (overflow > 0) {
                    width -= overflow;
                }
    
                $dropdown.outerWidth(width);
                $select.outerWidth(width + 1);
                $select.addClass('select_init');
    
                if (is_open) {
                    $select.addClass('select_open');
                }
            },
    
            buildTree: function () {
                _private.tree = [];
                _private.$select.children().each(function (i) {
                    var $control = $(this);
    
                    if ($control.is('option')) {
                        _private.tree[i] = _private.buildNodeFromOption($control);
                    } else if ($control.is('optgroup')) {
                        _private.tree[i] = _private.buildNodeFromOptgroup($control);
    
                        $control.children().each(function (j) {
                            var $option = $(this);
                            _private.tree[i].options[j] = _private.buildNodeFromOption($option, _private.tree[i]);
                        });
                    }
                });
    
                _private.createDropdown();
                _private.updateBox();
                _private.updateWidth();
            },
    
            buildNodeFromOption: function ($option, optgroup) {
                var option = $option.get(0);
                var node = {
                    label: $option.data('label') || $option.html(),
                    value: $option.val(),
                    selected: $option.prop('selected'),
                    disabled: $option.prop('disabled'),
                    control: option,
                    hidden: $option.css('display') === 'none',
                    optgroup: optgroup
                };
                option['market_select@node'] = node;
                _private.createElementByOptionNode(node);
    
                return node;
            },
    
            buildNodeFromOptgroup: function ($optgroup) {
                var optgroup = $optgroup.get(0);
                var node = {
                    label: $optgroup.attr('label'),
                    options: [],
                    disabled: $optgroup.prop('disabled'),
                    control: optgroup,
                    hidden: window.getComputedStyle(optgroup).display === 'none'
                };
                optgroup['market_select@node'] = node;
                _private.createElementByOptgroupNode(node);
    
                return node;
            },
    
            createDropdown: function () {
                $select.find('.select__dropdown').each(function () {
                    var $dropdown = $(this);
                    $dropdown.empty();
                    var $fragment = $('<div></div>');
    
                    _private.tree.forEach(function (node) {
                        $fragment.append(node.element);
    
                        if (node.options) {
                            var $optgroup = $(node.element);
    
                            node.options.forEach(function (node) {
                                $optgroup.append(node.element);
                            });
                        }
                    });
    
                    $dropdown.append($fragment.contents());
                });
            },
    
            createElementByOptgroupNode: function (node) {
                var optgroup = document.createElement('span');
                optgroup.classList.add('select__optgroup');
                node.element = optgroup;
                optgroup['market_select@node'] = node;
    
                var label = document.createElement('span');
                label.classList.add('select__optgroup-label');
                label.innerHTML = node.label;
                optgroup.appendChild(label);
    
                optgroup.classList.toggle('select__optgroup_disabled', node.disabled);
                optgroup.classList.toggle('select__optgroup_hidden', node.hidden);
    
                return $(optgroup);
            },
    
            createElementByOptionNode: function (node) {
                var option = document.createElement('span');
                option.classList.add('select__option');
                option.classList.toggle('select__option_disabled', node.disabled);
                option.classList.toggle('select__option_selected', node.selected);
                option.classList.toggle('select__option_hidden', node.hidden);
                option['market_select@node'] = node;
    
                option.dataset['value'] = node.value;
                option.innerHTML = node.label;
    
                var $option = $(option);
                node.element = $option;
    
                return $option;
            },
    
            updateTree: function () {
                if (_private.request_update) {
                    return;
                }
    
                _private.request_update = true;
                market.requestNextFrame(function () {
                    _private.request_update = false;
                    var is_updated = false;
    
                    _private.tree.forEach(function (node) {
                        if (node.options) {
                            is_updated = _private.updateOptgroupNode(node) || is_updated;
    
                            node.options.forEach(function (node) {
                                is_updated = _private.updateOptionNode(node) || is_updated;
                            });
                        } else {
                            is_updated = _private.updateOptionNode(node) || is_updated;
                        }
                    });
    
                    var visible = $select.is(':visible');
    
                    if (visible !== _private.visible) {
                        is_updated = true;
                        _private.visible = visible;
                    }
    
                    if (is_updated) {
                        _private.updateBox();
                        _private.updateWidth();
    
                        if (_private.isOpen()) {
                            _private.scrollToSelected();
                        }
                    }
    
                    if ($select.prop('disabled')) {
                        _private.close();
                    }
                }, $select[0]);
            },
    
            scrollToSelected: function () {
                var selected = _private.getSelected();
    
                if (selected) {
                    var element = selected.element.get(0);
                    var dropdown = $select.find('.select__dropdown').get(0);
    
                    if (dropdown.scrollTop > element.offsetTop) {
                        dropdown.scrollTop = element.offsetTop;
                    } else if (dropdown.scrollTop + dropdown.clientHeight < element.offsetTop + element.offsetHeight) {
                        dropdown.scrollTop = element.offsetTop + element.offsetHeight - dropdown.clientHeight;
                    }
                }
            },
    
            isChangedOptionByNode: function (node) {
                var $option = $(node.control);
    
                return (
                    node.label !== ($option.data('label') || $option.html())
                    || node.value !== $option.val()
                    || node.disabled !== $option.prop('disabled')
                    || node.selected !== $option.prop('selected')
                    || node.hidden !== window.getComputedStyle($option.get(0)).display === 'none'
                );
            },
    
            updateOptionNode: function (node) {
                if (_private.isChangedOptionByNode(node)) {
                    var $option = $(node.control);
                    var $element = $(node.element);
    
                    node.label = $option.data('label') || $option.html();
                    node.value = $option.val();
                    node.disabled = $option.prop('disabled');
                    node.selected = $option.prop('selected');
                    node.hidden = window.getComputedStyle($option.get(0)).display === 'none';
    
                    $element.html(node.label);
                    $element.toggleClass('select__option_disabled', node.disabled);
                    $element.toggleClass('select__option_selected', node.selected);
                    $element.toggleClass('select__option_hidden', node.hidden);
    
                    return true;
                }
    
                return false;
            },
    
            isChangedOptgroupByNode: function (node) {
                var $optgroup = $(node.control);
    
                return (
                    node.label !== $optgroup.html()
                    || node.disabled !== $optgroup.prop('disabled')
                    || node.hidden !== window.getComputedStyle($optgroup.get(0)).display === 'none'
                );
            },
    
            updateOptgroupNode: function (node) {
                if (_private.isChangedOptgroupByNode(node)) {
                    var $optgroup = $(node.control);
                    var $element = $(node.element);
    
                    node.label = $optgroup.attr('label');
                    node.disabled = $optgroup.prop('disabled');
                    node.hidden = window.getComputedStyle($optgroup.get(0)).display === 'none';
    
                    var $label = $element.find('.select__optgroup-label');
                    $label.html(node.label);
                    $optgroup.toggleClass('select__optgroup_disabled', node.disabled);
                    $optgroup.toggleClass('select__optgroup_hidden', node.hidden);
    
                    return true;
                }
    
                return false;
            }
        };
    
        _private.initEventListeners();
        _private.initObserver();
        _private.buildTree();
    
        return function () {
            _private.destroyEventListeners();
        };
    });
    
    market.Select.create = function ($select, size, fill, classes) {
        $select.each(function () {
            var $control = $(this);
    
            if ($control.hasClass('select__control') || !$control.is('select')) {
                return;
            }
    
            var $select = $(config.commons.select_html);
            $select.addClass(classes);
            $control.replace($select);
            $control.addClass('select__control');
            $select.find('.select__control').replaceWith($control);
    
            if (size) {
                $select.addClass('select_size_' + size);
            }
    
            if (fill) {
                $select.addClass('select_fill');
            }
    
            market.Select($select);
        });
    };
    
    market.SelectDecorator = (function () {
        var SelectDecorator = ComponentRegistry.register(function ($context) {
            return $context.select('.select-decorator');
        }, function ($control) {
            market.Select.create($control, $control.data('size'), $control.data('fill'), $control.data('classes'));
            $control.removeClass('select-decorator');
        });
    
        market.ObserverUtil.observeComponent('select-decorator', SelectDecorator);
    
        return SelectDecorator;
    })();
    
    market.InputFile = ComponentRegistry.register(function ($context) {
        return $context.select('.input-file:not(input)');
    }, function ($input_file, self) {
        var _private = {
            initEventListeners: function () {
                $input_file.find('.input-file__button').on('click', function () {
                    $input_file.find('.input-file__control').trigger('click');
                });
    
                $input_file.find('.input-file__control').on('change', function () {
                    _private.updateState();
                });
            },
    
            updateState: function () {
                var $input = $input_file.find('.input-file__control');
                var $file_box = $input_file.find('.input-file__file-box');
                var files = $input.get(0).files;
    
                if (files.length === 0) {
                    $file_box.text(translate('Файл не выбран'));
                } else if (files.length === 1) {
                    $file_box.text(files[0].name);
                } else {
                    $file_box.text(translate('Число файлов') + ': ' + files.length);
                }
            }
        };
    
        _private.initEventListeners();
        _private.updateState();
    });
    
    market.InputFile.create = function ($input) {
        $input.each(function () {
            var $control = $(this);
    
            if ($control.hasClass('input-file__control') || $control.hasClass('js-file-field') || !$control.is('input[type="file"]')) {
                return;
            }
    
            var $input = $(config.commons.input_file_html);
            $control.replace($input);
            $control.addClass('input-file__control');
            $input.find('.input-file__control').replaceWith($control);
            market.Update($input);
        });
    };
    
    market.RequiredPlaceholder = function ($input, wrap) {
        if (!wrap) {
            wrap = false;
        }
    
        $input.each(function () {
            var $control = $(this);
    
            var _private = {
                getPlaceholder: function () {
                    return $control.attr('placeholder');
                },
    
                setPlaceholder: function (placeholder) {
                    $control.attr('placeholder', placeholder);
                },
    
                resetPlaceholder: function () {
                    this.setPlaceholder('');
                },
    
                getPlaceholderNode: function () {
                    return $control.parent().find('.required-placeholder');
                },
    
                toggle: function () {
                    var $node = _private.getPlaceholderNode();
    
                    $node.toggle(!$control.val().length);
                },
    
                init: function () {
                    if (!$input.hasClass('suggestions-input') && !!this.getPlaceholder()) {
                        var value = this.getPlaceholder();
    
                        if (/\*$/.test(value)) {
                            value = value.substr(0, value.length - 1).trim();
                        }
    
                        if (wrap) {
                            $control.wrap($('<div class="required-placeholder__wrapper"/>'));
                        }
    
                        var $node = $('<span class="required-placeholder"/>');
                        $node.append(value);
                        $node.append($('<span class="required-placeholder__mark"/>').html('*'));
    
                        if (wrap) {
                            $node.addClass('required-placeholder_wrapped');
                        }
    
                        $control.after($node);
    
                        _private.resetPlaceholder();
    
                        $control.on('input', _private.toggle);
                        _private.toggle();
                    }
                }
            };
    
            _private.init();
        });
    };
    
    market.InputTextRequired = ComponentRegistry.register(function ($context) {
        return $context.select('.input-text_required');
    }, function ($input) {
        market.RequiredPlaceholder($input, true);
    });
    
    var CaptchaDecorator = market.CaptchaDecorator = ComponentRegistry.register(function ($context) {
        return $context.select('.captcha-decorator');
    }, function ($decorator, self) {
        $.extend(self, {
            init: function () {
                $decorator.find('a').addClass('pseudo-link');
                $decorator.find(':text').addClass('input-text');
            },
            refresh: function () {
                $decorator.find('.wa-captcha-refresh').trigger('click');
            }
        });
    
        self.init();
    });
    
    market.Analytics = (function () {
        var analytics = config.commons.analytics;
    
        var _private = {
            getGoal: function (goal) {
                return analytics.goals[goal];
            },
    
            ymReachGoal: function (goal_id) {
                var counter = window['yaCounter' + analytics.ym_counter];
                var goal = _private.getGoal(goal_id);
    
                if (counter && goal) {
                    counter.reachGoal(goal.ym);
                }
            },
    
            gaReachGoal: function (goal_id) {
                var ga = window.ga;
                var goal = _private.getGoal(goal_id);
    
                if (ga && goal) {
                    ga('send', 'event', goal.ga_category, goal.ga_action);
                }
            }
        };
    
        return {
            reachGoal: function (goal_id) {
                _private.ymReachGoal(goal_id);
                _private.gaReachGoal(goal_id);
            }
        };
    })();
    
    market.LoginPage = (function () {
        var LoginPage = ComponentRegistry.register(function ($context) {
            return $context.select('.login-page');
        }, function ($page) {
            var _private = {
                isModal: function () {
                    return $page.hasClass('login-modal');
                },
    
                isOnetime: function () {
                    var $onetime_button = $page.find('.wa-request-onetime-password-button');
    
                    return $onetime_button.length;
                },
    
                initType: function () {
                    if (this.isOnetime()) {
                        $page.addClass('login-page_onetime');
                    } else {
                        $page.addClass('login-page_general');
                    }
                },
    
                initButtons: function () {
                    var $submit_container = $page.find('.wa-submit, .wa-login-form-actions');
                    $page.find('.wa-js-new-button-place :submit').addClass('button_wide');
                    $submit_container.find(':submit').addClass('button_fill');
    
                    $submit_container.contents().filter(function () {
                        return this.nodeType === 3;
                    }).remove();
    
                    var $nav_buttons = $submit_container.find('a');
                    $nav_buttons = $nav_buttons.add($page.find('.wa-login-forgotpassword-url'));
                    $nav_buttons.removeClass('link').addClass('button button_style_transparent');
    
                    if (_private.isModal()) {
                        $nav_buttons.filter(function () {
                            return this.dataset.type === 'signup';
                        }).removeClass('button_style_transparent').addClass('button_fill button_style_light');
                    }
    
                    if (_private.isOnetime()) {
                        var $onetime_button_wrapper = $page.find('.wa-request-onetime-password-button-wrapper');
                        var $onetime_button = $onetime_button_wrapper.find('.wa-request-onetime-password-button');
                        $onetime_button.removeClass('button_fill');
                        $onetime_button.addClass('button_wide');
    
                        $nav_buttons = $nav_buttons.add($onetime_button_wrapper);
                    }
    
                    if (!_private.isOnetime() && ResponsiveUtil.isTabletMax()) {
                        $nav_buttons.removeClass('button_style_transparent').addClass('button_style_light');
                    }
    
                    var $nav_buttons_container = $('<div class="login-page__nav-buttons"></div>');
                    $nav_buttons_container.append($nav_buttons);
                    $nav_buttons_container.appendTo($submit_container);
    
                    if (!_private.isOnetime() && ResponsiveUtil.isDesktopMin() && !_private.isModal()) {
                        var $submit = $submit_container.find(':submit');
                        $submit.removeClass('button_fill');
                        $submit.addClass('button_wide');
                        $nav_buttons_container.prepend($submit);
                    }
    
                    $page.find('.wa-buttons-wrapper').each(function () {
                        var $wrapper = $(this);
                        $wrapper.show();
                        $wrapper.append($nav_buttons_container);
                    });
    
                    $page.find('.wa-signup-url').remove();
    
                    if (_private.isOnetime()) {
                        var $buttons_wrapper = $page.find('.wa-buttons-wrapper');
                        market.ObserverUtil.observe('wa-login-submit', function ($submit) {
                            if (!$submit.closest('.wa-buttons-wrapper').is($buttons_wrapper) || $submit.closest('.login-page__nav-buttons').length) {
                                return;
                            }
    
                            $nav_buttons_container.prepend($submit);
                        });
                    }
                },
    
                initAjaxForm: function () {
                    var $form = $page.find('form');
    
                    $form.each(function () {
                        var $form = $(this);
    
                        $form.addClass('ajax-form');
                        market.AjaxForm($form);
                    });
    
                    $form.on('success@market:ajax-form', function (e, response) {
                        if (response.status) {
                            return;
                        }
    
                        var $new_page = $(response).find('.login-page');
                        $page.replaceWith($new_page);
                        market.Update($new_page.parent());
                    });
                },
    
                initErrors: function () {
                    var request_id = setInterval(function () {
                        if (window.WaFrontendLogin) {
                            var showErrors = window.WaFrontendLogin.prototype.showErrors;
    
                            window.WaFrontendLogin.prototype.showErrors = function () {
                                showErrors.apply(this, arguments);
                                $page.find('.form-field__content-container .wa-error-msg').addClass('form-field__error-container');
                                $page.find('.wa-uncaught-errors .wa-error-msg').addClass('error error_text');
                            };
    
                            clearInterval(request_id);
                        }
                    });
                }
            };
    
            _private.initType();
            _private.initButtons();
            _private.initAjaxForm();
            _private.initErrors();
            $page.addClass('login-page_js-is-init');
        });
    
        market.ObserverUtil.observe('login-page__trigger', function ($node) {
            $node.closest('login-page').each(function () {
                LoginPage($(this));
            });
        });
    
        return LoginPage;
    })();
    
    market.SignupPage = (function () {
        var SignupPage = ComponentRegistry.register(function ($context) {
            return $context.select('.signup-page');
        }, function ($page) {
            var _private = {
                initButtons: function () {
                    var $submit_container = $page.find('.wa-submit, .wa-signup-form-actions');
                    $submit_container.find(':submit').addClass('button_wide');
                    $submit_container.contents().filter(function () {
                        return this.nodeType === 3;
                    }).remove();
    
                    var $login_button = $submit_container.find('a').removeClass('link').addClass('button button_style_transparent signup-page__login-button');
                    $login_button.text(translate('Вход на сайт'));
    
                    $page.find('.wa-buttons-wrapper').each(function () {
                        var $wrapper = $(this);
                        $wrapper.append($login_button);
                    });
    
                    $page.find('.wa-login-url').remove();
                },
    
                initFields: function () {
                    var $fields = $page.find('.wa-field');
    
                    $fields.each(function () {
                        var $field = $(this);
                        var is_required = !!$field.data('is-required');
                        var $input = $field.find('.input-text');
    
                        if (is_required) {
                            $input.addClass('input-text_required');
                        }
                    });
                },
    
                initWaSignup: function () {
                    if (window.WaSignup) {
                        var getFormInput = window.WaSignup.prototype.getFormInput;
    
                        window.WaSignup.prototype.getFormInput = function () {
                            var $input = getFormInput.apply(this, arguments);
    
                            if ($input.hasClass('checkbox__control')) {
                                $input = $input.parents('.label');
                            }
    
                            if ($input.hasClass('radio__control')) {
                                $input = $input.parents('.label');
                            }
    
                            return $input;
                        };
                    }
                },
    
                initForm: function () {
                    var $form = $page.find('form');
    
                    $form.each(function () {
                        var $form = $(this);
                        $form.addClass('ajax-form');
                        market.AjaxForm($form);
                    });
    
                    $form.on('success@market:ajax-form', function (e, response) {
                        if (response.status) {
                            return;
                        }
    
                        var $new_page = $(response).find('.signup-page');
                        $page.replaceWith($new_page);
                        market.Update($new_page.parent());
                    });
                },
    
                initErrors: function () {
                    var request_id = setInterval(function () {
                        if (window.WaSignup) {
                            var showErrors = window.WaSignup.prototype.showErrors;
    
                            window.WaSignup.prototype.showErrors = function () {
                                showErrors.apply(this, arguments);
                                $page.find('.form-field__content-container .wa-error-msg').addClass('form-field__error-container');
                                $page.find('.wa-uncaught-errors .wa-error-msg').addClass('error error_text');
                            };
    
                            clearInterval(request_id);
                        }
                    });
                },
                initXhrEventListener: function () {
                    $(document).ajaxSuccess(function (e, xhr, options) {
                        if (options.url === '/signup/' && xhr.responseJSON.status !== 'fail' && !xhr.responseJSON.errors) {
                            market.Analytics.reachGoal('user_reg');
                        }
                    });
                }
            };
    
            $page.addClass('signup-page_js-is-init');
            _private.initButtons();
            _private.initFields();
            _private.initWaSignup();
            _private.initErrors();
            // _private.initForm();
        });
    
        market.ObserverUtil.observe('signup-page__trigger', function ($node) {
            $node.closest('signup-page').each(function () {
                SignupPage($(this));
            });
        });
    
        return SignupPage;
    })();
    
    market.ForgotpasswordPage = (function () {
        var ForgotpasswordPage = ComponentRegistry.register(function ($context) {
            return $context.find('.forgotpassword-page');
        }, function ($page) {
            $page.addClass('forgotpassword-page_js-is-init');
            var $decorator = $page.find('.forgotpassword-page__form-decorator');
            $decorator.addClass('form-decorator');
            var $field = $('<div class="wa-field"><div class="wa-value"></div></div>');
    
            $page.find('.wa-forgotpassword-button').each(function () {
                var $button = $(this);
                $button.find(':submit').addClass('wa-submit');
                $field.find('.wa-value').append($button);
            });
    
            $page.find('.wa-edit-login-link-wrapper').each(function () {
                var $wrapper = $(this);
                $field.find('.wa-value').append($wrapper);
            });
    
            $page.find('.wa-field-login').after($field);
    
            var interval_id = setInterval(function () {
                if (window.WaFrontendForgotPassword) {
                    var showErrors = window.WaFrontendForgotPassword.prototype.showErrors;
    
                    window.WaFrontendForgotPassword.prototype.showErrors = function () {
                        showErrors.apply(this, arguments);
                        $page.find('.form-field__content-container .wa-error-msg').addClass('form-field__error-container');
                        $page.find('.wa-uncaught-errors .wa-error-msg').addClass('error error_text');
                    };
    
                    clearInterval(interval_id);
                }
            }, 100);
    
            var interval_password_id = setInterval(function () {
                if (window.WaFrontendSetPassword) {
                    var showErrors = window.WaFrontendSetPassword.prototype.showErrors;
    
                    window.WaFrontendSetPassword.prototype.showErrors = function () {
                        showErrors.apply(this, arguments);
                        $page.find('.form-field__content-container .wa-error-msg').addClass('form-field__error-container');
                        $page.find('.wa-uncaught-errors .wa-error-msg').addClass('error error_text');
                    };
    
                    clearInterval(interval_password_id);
                }
            }, 100);
    
            Update($decorator);
        });
    
        market.ObserverUtil.observe('forgotpassword-page__trigger', function ($node) {
            $node.closest('.forgotpassword-page').each(function () {
                ForgotpasswordPage($(this));
            });
        });
    
        return ForgotpasswordPage;
    })();
    

    $(document).on('swiper_lazyload@market:global', function (e, swiper) {
        swiper.on('lazyImageReady', function (swiper, slideEl, imageEl) {
            var image = $(slideEl);
            var $image_preloader = image.next('.image__preloader');

            if ($image_preloader.length) {
                $image_preloader.remove();
            }
        });

        swiper.on('slideChange', function () {
            var swiper = this;
            var currentSlide = swiper.slides[swiper.activeIndex];
            var $image = $(currentSlide).find('.lazy-image');

            if ($image.length > 0) {
                market.LazyImage($image);

                if (swiper.params.autoHeight) {
                    $(document).on('lazy_image_completed@market:global', function () {
                        swiper.updateAutoHeight();
                    });
                }
            }
        });
    });

    var QueryUtil = market.QueryUtil = (function () {
        return {
            parse: function (url) {
                var anchor = document.createElement('a');
                anchor.href = url;
                var query = anchor.search.substring(1);
    
                if (query.length === 0) {
                    return [];
                }
    
                var vars = query.split('&');
                var result = [];
    
                for (var i = 0; i < vars.length; i++) {
                    var pair = vars[i].split('=');
                    result.push({
                        name: decodeURIComponent(pair[0]),
                        value: decodeURIComponent(pair[1])
                    });
                }
    
                return result;
            },
            clear: function (url) {
                return url.split('?')[0];
            },
            serialize: function (params) {
                return $.param(params);
            }
        };
    })();
    
    var MetaThemeColorUtil = market.MetaThemeColorUtil = (function () {
        return {
            setColor: function (color) {
                $('meta[name="theme-color"]').attr('content', color);
            },
            resetColor: function () {
                $('meta[name="theme-color"]').attr('content', '#FFFFFF');
            }
        };
    })();
    
    var ScrollUtil = market.ScrollUtil = (function () {
        return {
            scrollToTop: function (position) {
                position = position || 0;
                var current_position = ScrollUtil.getScrollTop();
    
                if (current_position <= position) {
                    return;
                }
    
                ScrollUtil.scrollTo(position);
            },
            scrollToBottom: function (position) {
                position = position || 0;
                var current_position = ScrollUtil.getScrollTop();
    
                if (position <= current_position) {
                    return;
                }
    
                ScrollUtil.scrollTo(position);
            },
            scrollTo: function (position) {
                var $body = $('html, body');
                position = position || 0;
    
                var current_position = ScrollUtil.getScrollTop();
    
                var $topOffsetBlock = $('[data-top-offset-block]');
    
                if ($topOffsetBlock.length > 0) {
                    position -= $topOffsetBlock.height();
                }
    
                if (Math.abs(current_position - position) > 400) {
                    $body.scrollTop(position + Math.sign(current_position - position) * 400);
                }
    
                $body.animate({
                    scrollTop: position
                }, 300, 'swing', function () {
                    $body.scrollTop(position);
                });
            },
            scrollToBlock: function ($block) {
                $block.trigger('scrollTo@market');
                this.scrollTo($block.offset().top);
            },
            scrollByHash: function (hash) {
                if (!hash && window.location.hash) {
                    hash = window.location.hash;
                }
    
                if (!hash) {
                    return;
                }
    
                var $block = $(hash);
    
                market.HistoryUtil.disableScrollRestoration();
    
                if ($block.length) {
                    this.scrollToBlock($block);
                }
            },
            getScrollTop: function () {
                return $(window).scrollTop();
            }
        };
    })();
    
    var FlyUtil = market.FlyUtil = (function () {
        return {
            flyImage: function ($image, $target, css) {
                if (!css) {
                    css = {};
                }
    
                var deferred = $.Deferred();
    
                var $fly_image = $image.clone().addClass('image-flying');
                $fly_image.css($.extend({
                    position: 'absolute',
                    left: $image.offset().left,
                    top: $image.offset().top,
                    width: $image.width(),
                    height: $image.height(),
                    zIndex: 10
                }, css));
                $fly_image.appendTo(document.body);
    
                $fly_image.animate({
                    left: $target.offset().left,
                    top: $target.offset().top,
                    height: 0,
                    width: 0,
                    opacity: 0
                }, 1000, function () {
                    $fly_image.remove();
                    deferred.resolve();
                });
    
                return deferred;
            }
        };
    })();
    
    var InfoPanelUtil = market.InfoPanelUtil = (function () {
        var close_timeout_id = null;
    
        return {
            showMessage: function (message) {
                var $panel = $(config.commons.info_panel_html);
                $panel.find('.info-panel__content-container').html(message);
    
                InfoPanelUtil.open($panel, true);
            },
            open: function ($content) {
                clearTimeout(close_timeout_id);
    
                var $container = $('.custom-panel-container');
                var container = InfoPanelContainer($container);
                container.setContent($content);
                container.open();
    
                close_timeout_id = setTimeout(function () {
                    InfoPanelUtil.close();
                }, 5000);
            },
            close: function () {
                clearTimeout(close_timeout_id);
    
                var $container = $('.custom-panel-container');
                var container = InfoPanelContainer($container);
                container.close();
            }
        };
    })();
    
    var CartUtil = market.CartUtil = (function () {
        return {
            addByData: function (data) {
                var _data = [];
                $(data).each(function () {
                    var entry = this;
    
                    if (entry.name === 'quantity') {
                        var value = entry.value;
                        entry.value = NumberUtil.formatNumber(value);
                    }
    
                    _data.push(entry);
                });
    
                var ajax = $.ajax({
                    url: config.shop.cart_add_url,
                    method: 'post',
                    data: _data,
                    dataType: 'json'
                });
    
                ajax.then(function () {
                    $(document).trigger('shop_cart_add@market:global', arguments);
                });
    
                return ajax;
            },
            addProductBySkuId: function (sku_id, quantity, services) {
                var formattedQuantity = NumberUtil.formatNumber(quantity);
    
                var ajax = CartUtil.addByData($.extend({
                    sku_id: sku_id,
                    quantity: formattedQuantity,
                    html: true
                }, CartUtil.formatServices(services)));
    
                ajax.then(function () {
                    $(document).trigger('shop_cart_add_product@market:global', arguments);
                });
    
                return ajax;
            },
            addProduct: function (product_id, quantity, services) {
                var formattedQuantity = NumberUtil.formatNumber(quantity);
    
                var ajax = CartUtil.addByData($.extend({
                    product_id: product_id,
                    quantity: formattedQuantity,
                    html: true
                }, CartUtil.formatServices(services)));
    
                ajax.then(function () {
                    $(document).trigger('shop_cart_add_product@market:global', arguments);
                });
    
                return ajax;
            },
            addProductByFeatures: function (product_id, features, quantity, services) {
                var formattedQuantity = NumberUtil.formatNumber(quantity);
    
                var ajax = CartUtil.addByData($.extend({
                    product_id: product_id,
                    features: features,
                    quantity: formattedQuantity,
                    html: true
                }, CartUtil.formatServices(services)));
    
                ajax.then(function () {
                    $(document).trigger('shop_cart_add_product@market:global', arguments);
                });
    
                return ajax;
            },
            addService: function (parent_id, service_id, service_variant_id) {
                var ajax = CartUtil.addByData({
                    parent_id: parent_id,
                    service_id: service_id,
                    service_variant_id: service_variant_id,
                    html: true
                });
    
                ajax.then(function () {
                    $(document).trigger('shop_cart_add_service@market:global', arguments);
                });
    
                return ajax;
            },
            updateItem: function (item_id, quantity) {
                var formattedQuantity = NumberUtil.formatNumber(quantity);
    
                var ajax = $.ajax({
                    url: config.shop.cart_save_url,
                    method: 'post',
                    data: {
                        id: item_id,
                        quantity: formattedQuantity,
                        html: true
                    },
                    dataType: 'json'
                });
    
                ajax.then(function () {
                    $(document).trigger('shop_cart_update@market:global', arguments);
                });
    
                return ajax;
            },
            updateService: function (item_id, service_variant_id) {
                var ajax = $.ajax({
                    url: config.shop.cart_save_url,
                    method: 'post',
                    data: {
                        id: item_id,
                        service_variant_id: service_variant_id,
                        html: true
                    },
                    dataType: 'json'
                });
    
                ajax.then(function () {
                    $(document).trigger('shop_cart_update@market:global', arguments);
                });
    
                return ajax;
            },
            deleteItem: function (item_id) {
                var ajax = $.ajax({
                    url: config.shop.cart_delete_url,
                    method: 'post',
                    data: {
                        id: item_id,
                        html: true
                    },
                    dataType: 'json'
                });
    
                ajax.then(function () {
                    $(document).trigger('shop_cart_delete@market:global', arguments);
                });
    
                return ajax;
            },
            clear: function () {
                var ajax = $.ajax({
                    url: config.shop.cart_url + '?clear=1',
                    method: 'post',
                    dataType: 'json'
                });
    
                ajax.then(function () {
                    $(document).trigger('shop_cart_clear@market:global', arguments);
                });
    
                return ajax;
            },
            formatServices: function (services) {
                services = services || [];
                var data = {};
    
                data.services = services.map(function (service) {
                    return service.id;
                });
    
                data.service_variants = services.reduce(function (variants, service) {
                    variants[service.id] = service.variant_id;
    
                    return variants;
                }, {});
    
                return data;
            }
        };
    })();
    
    market.InfoPanel = ComponentRegistry.register(function ($context) {
        return $context.select('.info-panel');
    }, function ($panel) {
        $panel.find('.info-panel__close-button').on('click', function () {
            var $container = $(this).closest('.info-panel-container');
            $container.each(function () {
                var container = InfoPanelContainer($(this));
                container.close();
            });
            $panel.trigger('close@market:info-panel');
        });
    });
    
    var InfoPanelContainer = market.InfoPanelContainer = ComponentRegistry.register(function ($context) {
        return $context.select('.info-panel-container');
    }, function ($panel, self) {
        $.extend(self, {
            open: function () {
                $panel.removeClass('info-panel-container_close');
                $panel.offset();
                $panel.addClass('info-panel-container_open');
            },
            close: function () {
                $panel.removeClass('info-panel-container_open');
                $panel.offset();
                $panel.addClass('info-panel-container_close');
            },
            setContent: function ($content) {
                $panel.html($content);
                market.Update($panel);
            }
        });
    });
    
    market.PluralUtil = (function () {
        return {
            plural: function (n, forms) {
                if (parseInt(n / 10) % 10 !== 1 && 1 <= n % 10 && n % 10 <= 4) {
                    if (n % 10 === 1) {
                        return forms[0];
                    } else {
                        return forms[1];
                    }
                } else {
                    return forms[2];
                }
            },
            getPluralValue: function (localField, n) {
                var localConfigField = config.language['plurals'][localField];
    
                if (typeof localConfigField !== 'undefined') {
                    return this.plural(n, localConfigField.split(','));
                }
            }
        };
    })();
    
    market.DebounceUtil = (function () {
        return {
            debounce(callback, timeoutMs) {
                let timer;
    
                return function (...args) {
                    clearTimeout(timer);
                    timer = setTimeout(() => {
                        callback(...args);
                    }, timeoutMs);
                };
            }
        };
    })();
    
    var PageSeparatorBuilder = market.PageSeparatorBuilder = (function () {
        return {
            create: function (text) {
                var $page_separator = $('<div class="page-separator"></div>');
                var $page_separator_inner = $('<span class="page-separator__inner"></span>');
                $page_separator.append($page_separator_inner);
                $page_separator_inner.text(text);
    
                return $page_separator;
            }
        };
    })();
    
    market.CookieSet = function (cookie, update_callback) {
        var _private = {
            is_stored: false,
            state: null
        };
    
        var self = {
            list: function () {
                if (!_private.is_stored) {
                    var cookie_raw = $.cookie(cookie) || '';
    
                    if (cookie_raw.length === 0) {
                        _private.state = [];
                    } else {
                        _private.state = cookie_raw.split(',');
                    }
    
                    _private.is_stored = true;
                }
    
                return _private.state;
            },
            count: function () {
                return self.list().length;
            },
            store: function (list) {
                if (list.length === 0) {
                    $.removeCookie(cookie, {
                        expires: 31,
                        path: config.commons.wa_url
                    });
                } else {
                    $.cookie(cookie, list.join(','), {
                        expires: 31,
                        path: config.commons.wa_url
                    });
                }
    
                _private.is_stored = false;
            },
            has: function (value) {
                value = value.toString();
                var list = self.list();
                var result = list.indexOf(value) !== -1;
    
                return result;
            },
            add: function (value) {
                value = value.toString();
    
                if (self.has(value)) {
                    return;
                }
    
                var list = self.list();
                list.push(value);
                self.store(list);
                self.update({ action: 'add', value: value });
            },
            remove: function (value) {
                value = value.toString();
    
                if (!self.has(value)) {
                    return;
                }
    
                var list = self.list();
                list.splice(list.indexOf(value), 1);
                self.store(list);
                self.update({ action: 'remove', value: value });
            },
            update: function (info) {
                if (typeof update_callback === 'function') {
                    update_callback(info);
                }
            }
        };
    
        return self;
    };
    
    var TouchLockUtil = market.TouchLockUtil = (function () {
        return {
            lock: function ($element) {
                if (!$element.length) {
                    return;
                }
    
                var element = $element.get(0);
                var _private = {
                    last_touch_y: null,
                    handleTouchStart: function (e) {
                        if (!ResponsiveUtil.isTabletMax()) {
                            return;
                        }
    
                        if (e.targetTouches.length !== 1) {
                            return;
                        }
    
                        _private.last_touch_y = e.targetTouches[0].clientY;
                    },
                    handleTouchMove: function (e) {
                        if (!ResponsiveUtil.isTabletMax()) {
                            return;
                        }
    
                        if (e.targetTouches.length !== 1) {
                            return;
                        }
    
                        var wrap = this;
                        var diff_y = e.targetTouches[0].clientY - _private.last_touch_y;
    
                        if (wrap.scrollTop === 0 && diff_y > 0) {
                            e.preventDefault();
                        }
    
                        if (wrap.scrollHeight - wrap.scrollTop <= wrap.clientHeight && diff_y < 0) {
                            e.preventDefault();
                        }
                    }
                };
    
                element.addEventListener('touchmove', _private.handleTouchMove, { passive: false });
                element.addEventListener('touchstart', _private.handleTouchStart, { passive: false });
            }
        };
    })();
    
    var ScrollLockUtil = market.ScrollLockUtil = (function () {
        return {
            lock: function ($element) {
                $element.data('scroll-locked-backup', $element.css('overflow'));
                $element.data('is-scroll-locked', true);
                $element.css('overflow', 'hidden');
            },
    
            unlock: function ($element) {
                $element.css('overflow', $element.data('scroll-locked-backup'));
                $element.data('is-scroll-locked', '');
                $element.data('scroll-locked-backup', null);
            },
    
            lockPage: function () {
                this.lock($('html'));
            },
    
            unlockPage: function () {
                this.unlock($('html'));
            }
    
        };
    })();
    
    var ModalUtil = market.ModalUtil = (function () {
        function addWrapper(isLoading) {
            var $wrapper = $('.modal-wrapper');
    
            if ($wrapper.length === 0) {
                $wrapper = $('<div class="modal-wrapper"></div>');
                $('body').append($wrapper);
                ScrollLockUtil.lockPage();
                $wrapper.on('touchstart mousedown', function (e) {
                    if (e.target === this) {
                        ModalUtil.close();
                    }
                });
            }
    
            if (isLoading) {
                $wrapper.addClass('modal-wrapper_loading');
            }
    
            return $wrapper;
        }
    
        return {
            close: function (closeAll) {
                if (typeof closeAll === 'undefined') {
                    closeAll = true;
                }
    
                $('.modal').each(function () {
                    var $modal = $(this);
                    var onClose = null;
                    var modal = Modal($modal);
    
                    if (typeof $modal.data('onClose') === 'function') {
                        onClose = $modal.data('onClose');
                    }
    
                    modal.close();
                    $modal.on('animationend', function (e) {
                        $modal.remove();
                    });
    
                    if (typeof onClose === 'function') {
                        onClose();
                    }
                });
    
                if (closeAll) {
                    window.setTimeout(function () {
                        $('.modal-wrapper').remove();
                        ScrollLockUtil.unlockPage();
                    }, 200);
                }
            },
            open: function ($modal, onOpen) {
                if ($modal.length === 0) {
                    return false;
                }
    
                var openTimeout = $('.modal').length > 0 ? 200 : 0;
    
                this.close(false);
    
                var $wrapper = addWrapper();
                var $observerTrigger = $('<div class="body-children-observer-trigger"></div>');
                var modal = Modal($modal);
    
                window.setTimeout(() => {
                    $wrapper.append($modal);
                    market.Update($wrapper);
                    $wrapper.removeClass('modal-wrapper_loading');
                    modal.show();
                    $('body').append($observerTrigger);
    
                    if (typeof onOpen === 'function') {
                        onOpen($modal);
                    }
    
                    $observerTrigger.remove();
                }, openTimeout);
            },
            openContent: function ($content, _options) {
                if (typeof _options !== 'object') {
                    _options = {};
                }
    
                var options = {
                    title: _options.title || undefined,
                    classes: _options.classes || '',
                    contentClasses: _options.contentClasses || '',
                    beforeOpen: _options.beforeOpen || undefined,
                    onOpen: _options.onOpen || undefined,
                    onClose: _options.onClose || undefined,
                    isContentNoHidden: _options.isContentNoHidden || false
                };
    
                var $modal = $(market.config.commons.modal_tpl);
                $modal.addClass(options.classes);
                $modal.find('.modal__content').addClass(options.contentClasses).toggleClass('modal__content_no-hidden', options.isContentNoHidden).html($content);
    
                var $title = $modal.find('.modal__title').first();
    
                if (typeof options.title !== 'undefined' && options.title !== null && options.title !== '') {
                    $title.html(options.title);
                } else if ($title.text() === '') {
                    $title.remove();
                }
    
                var onOpen = null;
    
                if (typeof options.beforeOpen === 'function') {
                    options.beforeOpen($modal);
                }
    
                $(document).trigger('opening@market:modal', $modal);
    
                if (typeof options.onOpen === 'function') {
                    onOpen = options.onOpen;
                }
    
                this.open($modal, onOpen);
                $(document).trigger('opened@market:modal', $modal);
    
                if (typeof options.onClose === 'function') {
                    $modal.data('onClose', options.onClose);
                }
    
                $(document).trigger('closed@market:modal', $modal);
            },
            openAjax: function (ajaxUrl, selectContent, _options) {
                if (typeof _options !== 'object') {
                    _options = {};
                }
    
                var options = {
                    title: _options.title || undefined,
                    classes: _options.classes || '',
                    contentClasses: _options.contentClasses || '',
                    beforeOpen: _options.beforeOpen || undefined,
                    onOpen: _options.onOpen || undefined,
                    onClose: _options.onClose || undefined,
                    isContentNoHidden: _options.isContentNoHidden || false
                };
    
                var params = QueryUtil.parse(ajaxUrl);
                params.push({
                    name: 'ajax',
                    value: 1
                });
    
                var url = QueryUtil.clear(ajaxUrl) + '?' + QueryUtil.serialize(params);
                var $wrapper = addWrapper(true);
    
                $.get(url, function (response) {
                    var $response = $(response);
                    var $content = null;
    
                    if (typeof selectContent === 'function') {
                        $content = selectContent($response);
                    } else {
                        $content = $response.find('.ajax-content');
    
                        if ($content.length === 0) {
                            $content = $response;
                        }
                    }
    
                    if ($content.length === 0) {
                        console.error('No content found');
    
                        return false;
                    }
    
                    ModalUtil.openContent($content, options);
                });
            }
        };
    })();
    
    var HistoryUtil = market.HistoryUtil = (function () {
        if (window.history) {
            window.history.scrollRestoration = 'auto';
        }
    
        return {
            replaceState: function (data, title, url) {
                if (window.history && window.history.replaceState) {
                    window.history.replaceState(data, title, url);
                }
            },
            isReload: function () {
                if (window.performance && window.performance.getEntriesByType('navigation').length > 0) {
                    return window.performance.getEntriesByType('navigation')[0].type === 'reload';
                } else {
                    return window.performance.navigation.type === 1;
                }
            },
            disableScrollRestoration: function () {
                if (window.history) {
                    window.history.scrollRestoration = 'manual';
                }
            }
        };
    })();
    
    var LinkUtil = market.LinkUtil = (function () {
        return {
            create: function (url) {
                var anchor = document.createElement('a');
                anchor.href = url;
    
                return anchor;
            }
        };
    })();
    
    var NumberUtil = market.NumberUtil = (function () {
        function roundNumber(num, scale) {
            if (!String(num).includes('e')) {
                return +(Math.round(num + 'e+' + scale) + 'e-' + scale);
            } else {
                var parts = String(num).split('e'),
                    sig = '';
    
                if (+parts[1] + scale > 0) {
                    sig = '+';
                }
    
                return +(Math.round(+parts[0] + 'e' + sig + (+parts[1] + scale)) + 'e-' + scale);
            }
        }
    
        return {
            validateNumber: function (type, value) {
                value = (typeof value === 'string' ? value : '' + value);
    
                var result = value;
    
                switch (type) {
                    case 'float':
                        var float_value = parseFloat(value);
    
                        if (float_value >= 0) {
                            result = float_value.toFixed(3) * 1;
                        }
    
                        break;
    
                    case 'number': {
                        let white_list = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '.', ','],
                            letters_array = [],
                            divider_exist = false;
    
                        $.each(value.split(''), function (i, letter) {
                            if (letter === '.' || letter === ',') {
                                letter = '.';
    
                                if (!divider_exist) {
                                    divider_exist = true;
                                    letters_array.push(letter);
                                }
                            } else {
                                if (white_list.indexOf(letter) >= 0) {
                                    letters_array.push(letter);
                                }
                            }
                        });
    
                        result = letters_array.join('');
                        break;
                    }
    
                    case 'integer': {
                        let white_list = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'],
                            letters_array = [];
    
                        $.each(value.split(''), function (i, letter) {
                            if (white_list.indexOf(letter) >= 0) {
                                letters_array.push(letter);
                            }
                        });
    
                        result = letters_array.join('');
                        break;
                    }
    
                    default:
                        break;
                }
    
                return result;
            },
            formatNumber: function (number) {
                var float_num = parseFloat(number),
                    result = parseFloat(float_num.toFixed(3));
    
                if (float_num < 1) {
                    if (typeof number !== 'string') {
                        number = String(number);
                    }
    
                    var split_num = number.split('.');
    
                    if (split_num.length === 2) {
                        var decimal_part = split_num[1].replace(/^0+/, '');
                        number = roundNumber(float_num, split_num[1].length - decimal_part.length + 3).toFixed(8);
                    }
                } else {
                    number = roundNumber(float_num, 3).toFixed(8);
                }
    
                var parts = number.split('.');
    
                if (parts.length === 2) {
                    var tail = parts[1],
                        result2 = [],
                        result3 = [parts[0]];
    
                    if (tail.length) {
                        for (var i = 0; i < tail.length; i++) {
                            var letter = tail[tail.length - (i + 1)];
    
                            if (letter !== '0' || result2.length) {
                                result2.push(letter);
                            }
                        }
    
                        if (result2.length) {
                            result3.push(result2.reverse().join(''));
                        }
                    }
    
                    result = parseFloat(result3.join('.'));
                }
    
                return result;
            }
        };
    })();
    
    var Dropdown = market.Dropdown = ComponentRegistry.register(function ($context) {
        return $context.select('.dropdown');
    }, function ($dropdown, self) {
        var event = 'mouseenter';
    
        if ($dropdown.hasClass('dropdown_click')) {
            event = 'click';
        }
    
        if (('ontouchstart' in window)) {
            event = 'touchstart';
        }
    
        var $dropdownContent = $dropdown.find('.dropdown__dropdown').first();
        var _private = {
            leave_timeout_id: null,
    
            initGlobalEventListeners: function () {
                $(document).on('click', _private.handleDocumentClick);
            },
    
            destroyGlobalEventListeners: function () {
                $(document).off('click', _private.handleDocumentClick);
            },
    
            initEventListeners: function () {
                $dropdown.on(event, function (e) {
                    if ((e.type === 'touchstart' || (e.type === 'click' && self.isClicking())) && self.isOpen() && $(e.target).closest($dropdownContent).length === 0) {
                        clearTimeout(_private.leave_timeout_id);
                        self.close();
                    } else {
                        clearTimeout(_private.leave_timeout_id);
                        self.open();
                    }
                });
    
                if (!self.isClicking()) {
                    $dropdown.on('mouseleave', function () {
                        _private.leave_timeout_id = setTimeout(function () {
                            self.close();
                            ScrollLockUtil.unlockPage();
                        }, config['commons']['catalog_onleave_timeout']);
                    });
                }
    
                $dropdownContent.cssAnimation('dropdown__dropdown_animated');
            },
    
            handleDocumentClick: function (e) {
                if ($(e.target).closest($dropdown).length === 0) {
                    clearTimeout(_private.leave_timeout_id);
                    self.close();
                }
            }
        };
    
        $.extend(self, {
            isOpen: function () {
                return $dropdown.hasClass('dropdown_open');
            },
    
            open: function () {
                if (self.isOpen()) {
                    return;
                }
    
                $dropdown.addClass('dropdown_open');
                $dropdownContent.runCssAnimation('dropdown__dropdown_open-animation');
                $dropdown.trigger('open@market:dropdown');
            },
    
            close: function () {
                if (!self.isOpen()) {
                    return;
                }
    
                $dropdown.removeClass('dropdown_open');
                $dropdownContent.runCssAnimation('dropdown__dropdown_close-animation');
                $dropdown.trigger('close@market:dropdown');
            },
    
            isClicking: function () {
                return $dropdown.hasClass('dropdown_click');
            }
        });
    
        _private.initGlobalEventListeners();
        _private.initEventListeners();
    
        return function () {
            _private.destroyGlobalEventListeners();
        };
    });
    
    market.Counter = ComponentRegistry.register(function ($context) {
        return $context.select('.counter');
    }, function ($self, self) {
        $.extend(self, {
            changeCount: function (count) {
                $self.text(count);
                $self.toggleClass('counter_empty', count === 0);
            }
        });
    });
    
    market.ToggleBlock = ComponentRegistry.register(function ($context) {
        return $context.select('.toggle-block');
    }, function ($block, self) {
        var _private = {
            initButton: function () {
                var $button = _private.getButton();
    
                if ($button.length === 0) {
                    return;
                }
    
                var buttonDefaultContent = $button.data('default');
                var buttonActiveContent = $button.data('active');
    
                if (!buttonDefaultContent) {
                    buttonDefaultContent = $button.html();
                }
    
                $button.on('click', function () {
                    $block.toggleClass('toggle-block_active');
    
                    if (buttonActiveContent) {
                        if ($block.hasClass('toggle-block_active')) {
                            $button.html(buttonActiveContent);
                        } else {
                            $button.html(buttonDefaultContent);
                        }
                    }
    
                    $block.trigger('toggle');
                });
            },
            getButton: function () {
                return $block.find('.toggle-block__button').filter(function () {
                    return $(this).closest('.toggle-block').is($block);
                });
            }
        };
    
        _private.initButton();
    });
    
    market.AccordionBlock = ComponentRegistry.register(function ($context) {
        return $context.select('.accordion-block');
    }, function ($block, self) {
        var _private = {
            initEventListeners: function () {
                _private.getButton().on('click', function (e) {
                    if ($(e.target).closest('[data-stop]').length === 0) {
                        e.preventDefault();
                        self.toggle();
    
                        return false;
                    }
                });
            },
    
            getButton: function () {
                return $block.find('.accordion-block__button').filter(function () {
                    return $(this).closest('.accordion-block').is($block);
                });
            },
    
            getContent: function () {
                return $block.find('.accordion-block__content').filter(function () {
                    return $(this).closest('.accordion-block').is($block);
                });
            }
        };
    
        $.extend(self, {
            isOpen: function () {
                return $block.hasClass('accordion-block_open');
            },
    
            open: function () {
                _private.getContent().stop(true, true).animate({
                    height: 'show'
                }, 300, function () {
                    $block.removeClass('accordion-block_in-process-open');
                    $block.offset();
                });
    
                $block.removeClass('accordion-block_in-process-close');
                $block.offset();
                $block.addClass('accordion-block_open');
                $block.addClass('accordion-block_in-process-open');
            },
    
            close: function () {
                _private.getContent().stop(true, true).animate({
                    height: 'hide'
                }, 300, function () {
                    $block.removeClass('accordion-block_in-process-close');
                    $block.offset();
                });
    
                $block.removeClass('accordion-block_in-process-open');
                $block.offset();
                $block.removeClass('accordion-block_open');
                $block.addClass('accordion-block_in-process-close');
            },
    
            toggle: function () {
                var is_open = self.isOpen();
    
                if (is_open) {
                    self.close();
                } else {
                    self.open();
                }
            }
        });
    
        _private.initEventListeners();
    });
    
    var SlideDownUtil = market.SlideDownUtil = (function () {
        return {
            show: function ($element) {
                var promise = $.Deferred();
                $element.stop(true, true);
    
                $element.offset();
                $element.animate({
                    height: 'show'
                }, 300, function () {
                    promise.resolve();
                });
    
                return promise;
            },
    
            hide: function ($element) {
                var promise = $.Deferred();
                $element.stop(true, true);
    
                $element.offset();
                $element.animate({
                    height: 'hide'
                }, 300, function () {
                    promise.resolve();
                });
    
                return promise;
            },
    
            toggle: function ($element) {
                if ($element.is(':visible')) {
                    return SlideDownUtil.hide($element);
                } else {
                    return SlideDownUtil.show($element);
                }
            }
        };
    })();
    
    market.RangeSlider = ComponentRegistry.register(function ($context) {
        return $context.select('.range-slider');
    }, function ($range_slider, self) {
        $.extend(self, {
            initSlider: function () {
                var $min = self.getMin();
                var $max = self.getMax();
                var $slider = self.getSlider();
                var min = self.getMinBound();
                var max = self.getMaxBound();
                var current_min = self.handleNumber($min.val(), min, [min, max]);
                var current_max = self.handleNumber($max.val(), max, [current_min, max]);
                var step = 1;
    
                if ($range_slider.data('step')) {
                    step = parseFloat($range_slider.data('step'));
                } else {
                    var diff = max - min;
    
                    if (Math.round(min) != min || Math.round(max) != max) {
                        step = diff / 10;
                        var tmp = 0;
    
                        while (step < 1) {
                            step *= 10;
                            tmp += 1;
                        }
    
                        step = Math.pow(10, -tmp);
                        tmp = Math.round(100000 * Math.abs(Math.round(min) - min)) / 100000;
    
                        if (tmp && tmp < step) {
                            step = tmp;
                        }
    
                        tmp = Math.round(100000 * Math.abs(Math.round(max) - max)) / 100000;
    
                        if (tmp && tmp < step) {
                            step = tmp;
                        }
                    }
                }
    
                $slider.html('');
    
                $slider.slider({
                    range: true,
                    classes: {
                        'ui-slider': 'range-slider__slider',
                        'ui-slider-handle': 'range-slider__slider-handle',
                        'ui-slider-range': 'range-slider__slider-range'
                    },
                    min: min,
                    max: max,
                    values: [current_min, current_max],
                    step: step,
                    slide: function (e, ui) {
                        if (ui.values[0] === min) {
                            $min.val('');
                        } else {
                            $min.val(ui.values[0]);
                        }
    
                        if (ui.values[1] === max) {
                            $max.val('');
                        } else {
                            $max.val(ui.values[1]);
                        }
                    },
                    stop: function (e, ui) {
                        if (ui.handleIndex === 0) {
                            $min.trigger('change');
                        } else if (ui.handleIndex === 1) {
                            $max.trigger('change');
                        }
                    }
                });
            },
            initEventListeners: function () {
                var $min = self.getMin();
                var $max = self.getMax();
                var $slider = self.getSlider();
                var min = self.getMinBound();
                var max = self.getMaxBound();
    
                $min.on('input', function () {
                    var values = $slider.slider('values');
    
                    values[0] = self.handleNumber($(this).val(), min, [min, values[1]]);
                    $slider.slider('values', values);
                });
    
                $min.on('focus', function () {
                    if ($min.val() === '') {
                        $min.val(min);
                    }
                });
    
                $min.on('blur change', function () {
                    var values = $slider.slider('values');
    
                    var value = self.handleNumber($(this).val(), min, [min, values[1]]);
    
                    if (value === min) {
                        $min.val('');
                    } else {
                        $min.val(value);
                    }
    
                    values[0] = value;
                    $slider.slider('values', values);
                });
    
                $max.on('input', function () {
                    var values = $slider.slider('values');
    
                    values[1] = self.handleNumber($(this).val(), max, [values[0], max]);
                    $slider.slider('values', values);
                });
    
                $max.on('focus', function () {
                    if ($max.val() === '') {
                        $max.val(max);
                    }
                });
    
                $max.on('blur change', function () {
                    var values = $slider.slider('values');
    
                    var value = self.handleNumber($(this).val(), max, [values[0], max]);
    
                    if (value === max) {
                        $max.val('');
                    } else {
                        $max.val(value);
                    }
    
                    values[1] = value;
                    $slider.slider('values', values);
                });
            },
            getSlider: function () {
                return $range_slider.find('.range-slider__slider');
            },
            getMin: function () {
                return $range_slider.find('.range-slider__input_min');
            },
            getMax: function () {
                return $range_slider.find('.range-slider__input_max');
            },
            getMinBound: function () {
                return +$range_slider.data('min');
            },
            getMaxBound: function () {
                return +$range_slider.data('max');
            },
            handleNumber: function (number, def, range) {
                if (number === '') {
                    number = NaN;
                }
    
                number = +number;
    
                if (isNaN(number)) {
                    return def;
                }
    
                number = Math.max(range[0], Math.min(range[1], number));
    
                return number;
            }
        });
    
        self.initSlider();
        self.initEventListeners();
    });
    
    market.ViewportControl = ComponentRegistry.register(function ($context) {
        return $context.select('.viewport-control');
    }, function ($viewport_control, self) {
        var $window = $(window);
        var is_watch = false;
    
        $.extend(self, {
            initEventListeners: function () {
                $(window).on('scroll', self.handleView);
            },
            destruct: function () {
                $(window).off('scroll', self.handleView);
            },
            handleView: function () {
                if (!is_watch) {
                    return;
                }
    
                var height = $window.height();
                var scroll_top = $window.scrollTop();
                var position = $viewport_control.position();
    
                if (position.top < scroll_top + height) {
                    $viewport_control.trigger('view');
                } else if (position.top - 500 < scroll_top + height) {
                    $viewport_control.trigger('pre_view');
                }
            },
            unwatch: function () {
                is_watch = false;
            },
            watch: function () {
                is_watch = true;
                self.handleView();
            }
        });
    
        self.initEventListeners();
    
        setTimeout(function () {
            self.handleView();
        }, 0);
    });
    
    market.LazyLoad = ComponentRegistry.register(function ($context) {
        return $context.select('.lazy-load');
    }, function ($container, self) {
        var _private = {
            page: 1,
            count_pages: $container.data('count_pages'),
            is_watch: false,
            current_xhr: null,
    
            initGlobalEventListeners: function () {
                $(window).on('scroll', _private.handleScroll);
            },
    
            destroyGlobalEventListeners: function () {
                $(window).off('scroll', _private.handleScroll);
            },
    
            handleScroll: function () {
                _private.checkScroll();
            },
    
            checkScroll: function () {
                if (!_private.is_watch) {
                    return;
                }
    
                var $window = $(window);
    
                var height = $window.height();
                var scroll_top = $window.scrollTop();
                var position = $container.position().top + $container.height();
    
                if (position - 500 < scroll_top + height) {
                    self.unwatch();
    
                    _private.fetchNextPage().then(function () {
                        var is_last_page = _private.page === _private.count_pages;
    
                        if (!is_last_page) {
                            self.watch();
                        } else {
                            $container.trigger('done@market:lazy-load', arguments);
                        }
                    }, function () {
                        self.watch();
                    });
                }
            },
    
            fetchNextPage: function () {
                if (_private.current_xhr) {
                    _private.current_xhr.abort();
                    _private.current_xhr = null;
                }
    
                var next_page = _private.page + 1;
                var url = window.location.toString();
    
                var params = QueryUtil.parse(url);
                params.push({
                    name: '_',
                    value: Date.now()
                });
    
                if (next_page) {
                    params = params.filter(function (param) {
                        return param.name !== 'page';
                    });
    
                    params.push({
                        name: 'page',
                        value: next_page
                    });
                }
    
                var anchor = document.createElement('a');
                anchor.href = url;
                anchor.search = QueryUtil.serialize(params);
    
                $container.trigger('loading@market:lazy-load', arguments);
    
                var request = $.ajax({
                    url: anchor.toString()
                });
    
                request.then(function () {
                    _private.page = next_page;
                    $container.trigger('loaded@market:lazy-load', arguments);
                }, function () {
                    $container.trigger('error@market:lazy-load', arguments);
                });
    
                return request;
            }
        };
    
        $.extend(self, {
            getPage: function () {
                return _private.page;
            },
    
            getCountPages: function () {
                return _private.count_pages;
            },
    
            watch: function () {
                _private.is_watch = true;
                _private.checkScroll();
            },
    
            unwatch: function () {
                _private.is_watch = false;
            }
        });
    
        _private.initGlobalEventListeners();
    
        return function () {
            _private.destroyGlobalEventListeners();
        };
    });
    
    market.AuthAdapter = ComponentRegistry.register(function ($context) {
        return $context.select('.auth-adapter');
    }, function ($auth_adapter, self) {
        $.extend(self, {
            initEventListeners: function () {
                $auth_adapter.on('click', function (e) {
                    e.preventDefault();
                    var url = $(this).attr('href');
    
                    if (!url) {
                        return;
                    }
    
                    var left = (screen.width - 600) / 2;
                    var top = (screen.height - 500) / 2;
    
                    window.open(
                        url,
                        'oauth',
                        'width=600,height=500,left=' + left + ',top=' + top + ',status=no,toolbar=no,menubar=no'
                    );
                });
            }
        });
    
        self.initEventListeners();
    });
    
    market.LoginLink = ComponentRegistry.register(function ($context) {
        return $context.select('.login-link');
    }, function ($login_link, self) {
        $.extend(self, {
            initEventListeners: function () {
                $login_link.on('click', function (e) {
                    if (!ResponsiveUtil.isDesktopMin()) {
                        return;
                    }
    
                    e.preventDefault();
    
                    ModalUtil.openAjax(config.commons.login_url + '?modal=1', function (data) {
                        return $(data).find('.login-page');
                    }, {
                        isContentNoHidden: true
                    });
                });
            }
        });
    
        self.initEventListeners();
    });
    
    market.SignupLink = ComponentRegistry.register(function ($context) {
        return $context.select('.signup-link');
    }, function ($signup_link, self) {
        $.extend(self, {
            initEventListeners: function () {
                $signup_link.on('click', function (e) {
                    if (!ResponsiveUtil.isDesktopMin()) {
                        return;
                    }
    
                    e.preventDefault();
    
                    ModalUtil.openAjax(config.commons.signup_url + '?modal=1', function (data) {
                        return $(data).find('.signup-page').get(0).outerHTML;
                    });
                });
            }
        });
    
        self.initEventListeners();
    });
    
    var Tabs = market.Tabs = ComponentRegistry.register(function ($context) {
        return $context.select('.tabs');
    }, function ($tabs, self) {
        $.extend(self, {
            initEventListeners: function () {
                if ($tabs.hasClass('tabs_links')) {
                    return;
                }
    
                $tabs.on('click', '.tabs__tab', function () {
                    var $tabs = self.getTabs();
                    var $tab = $(this);
                    self.selectTab($tabs.index($tab));
                });
            },
            selectTab: function (index) {
                if (self.getIndex() === index) {
                    return;
                }
    
                var $all_tabs = self.getTabs();
                var $tab = $all_tabs.eq(index);
    
                $all_tabs.removeClass('tabs__tab_selected');
                $tab.addClass('tabs__tab_selected');
    
                $tabs.trigger('select_tab');
            },
            getIndex: function () {
                return self.getTabs().filter('.tabs__tab_selected').index();
            },
            getTabs: function () {
                return $tabs.find('.tabs__tab');
            }
        });
    
        self.initEventListeners();
    });
    
    market.Blocks = ComponentRegistry.register(function ($context) {
        return $context.select('.blocks-content');
    }, function ($blocks, self) {
        $.extend(self, {
            initEventListeners: function () {
                $(document).on('click', 'a', function () {
                    if (!ResponsiveUtil.isDesktopMin()) {
                        return;
                    }
    
                    if (this.toString() !== window.location.toString() || !this.hash) {
                        return;
                    }
    
                    var index = self.getBlockBySlug(this.hash.substr(1));
    
                    if (index === -1) {
                        return;
                    }
    
                    self.selectBlock(index);
                    self.scrollTo(index);
                });
    
                $(window).on('hashchange', function () {
                    if (!ResponsiveUtil.isDesktopMin()) {
                        return;
                    }
    
                    if (!window.location.hash) {
                        return;
                    }
    
                    var index = self.getBlockBySlug(window.location.hash.substr(1));
    
                    if (index === -1) {
                        return;
                    }
    
                    self.selectBlock(index);
                    self.scrollTo(index);
                });
            },
            initBlocks: function () {
                self.getBlocks().each(function () {
                    var $block = $(this);
                    var index = $block.index();
    
                    if (window.location.hash === '#' + $block.data('slug')) {
                        if (!HistoryUtil.isReload()) {
                            self.selectBlock(index);
                            self.scrollTo(index);
                        }
    
                        return false;
                    }
                });
            },
            getBlocks: function () {
                return $blocks.find('.blocks-content__item');
            },
            getBlock: function (index) {
                var $blocks = self.getBlocks();
                var $block = $blocks.eq(index);
    
                return $block;
            },
            selectBlock: function (index) {
                var $block = self.getBlock(index);
    
                var slug = $block.data('slug') || '';
    
                if (slug) {
                    if (index === 0) {
                        HistoryUtil.replaceState(null, null, window.location.pathname);
                    } else {
                        HistoryUtil.replaceState(null, null, '#' + slug);
                    }
                }
            },
            scrollTo: function (index) {
                var $block = self.getBlock(index);
    
                HistoryUtil.disableScrollRestoration();
                var scroll_top = $block.offset().top;
                ScrollUtil.scrollTo(scroll_top - 30);
            },
            getBlockBySlug: function (slug) {
                var result = -1;
    
                self.getBlocks().each(function () {
                    var $block = $(this);
                    var index = $block.index();
                    var content_slug = $block.data('slug') || '';
    
                    if (slug === content_slug) {
                        result = index;
    
                        return false;
                    }
                });
    
                return result;
            }
        });
    
        self.initBlocks();
        self.initEventListeners();
    });
    
    market.ContentTabs = ComponentRegistry.register(function ($context) {
        return $context.select('.content-tabs');
    }, function ($content_tabs, self) {
        if ($content_tabs.hasClass('.content-tabs_decorate')) {
            return;
        }
    
        $.extend(self, {
            initEventListeners: function () {
                $content_tabs.find('.content-tabs__tabs').on('select_tab', function () {
                    var $tabs = $(this);
                    var tabs = Tabs($tabs);
                    self.selectTab(tabs.getIndex());
                });
    
                $(document).on('click', 'a', function () {
                    if (!ResponsiveUtil.isDesktopMin()) {
                        return;
                    }
    
                    if (this.toString() !== window.location.toString() || !this.hash) {
                        return;
                    }
    
                    var index = self.getTabBySlug(this.hash.substr(1));
    
                    if (index === -1) {
                        return;
                    }
    
                    self.selectTab(index);
                    self.scrollTo();
                });
    
                $(window).on('hashchange', function () {
                    if (!ResponsiveUtil.isDesktopMin()) {
                        return;
                    }
    
                    if (!window.location.hash) {
                        return;
                    }
    
                    var index = self.getTabBySlug(window.location.hash.substr(1));
    
                    if (index === -1) {
                        return;
                    }
    
                    self.selectTab(index);
                    self.scrollTo();
                });
            },
            initTabs: function () {
                self.getContents().each(function () {
                    var $content = $(this);
                    var index = $content.index();
    
                    if (window.location.hash === '#' + $content.data('slug')) {
                        if (index !== 0) {
                            self.getTabsInstance().selectTab(index);
                        }
    
                        if (!HistoryUtil.isReload()) {
                            self.scrollTo();
                        }
    
                        return false;
                    }
                });
            },
            selectTab: function (index) {
                self.getTabsInstance().selectTab(index);
                var $contents = self.getContents();
                var $content = $contents.eq(index);
    
                var slug = $content.data('slug') || '';
    
                if (slug) {
                    if (index === 0) {
                        HistoryUtil.replaceState(null, null, window.location.pathname);
                    } else {
                        HistoryUtil.replaceState(null, null, '#' + slug);
                    }
                }
    
                $contents.removeClass('content-tabs__content_selected');
                $content.addClass('content-tabs__content_selected');
            },
            getTabBySlug: function (slug) {
                var result = -1;
    
                self.getContents().each(function () {
                    var $content = $(this);
                    var index = $content.index();
                    var content_slug = $content.data('slug') || '';
    
                    if (slug === content_slug) {
                        result = index;
    
                        return false;
                    }
                });
    
                return result;
            },
            scrollTo: function () {
                HistoryUtil.disableScrollRestoration();
                var scroll_top = $content_tabs.offset().top;
                ScrollUtil.scrollTo(scroll_top - 30);
            },
            getTabsInstance: function () {
                return Tabs($content_tabs.find('.content-tabs__tabs'));
            },
            getContents: function () {
                return $content_tabs.find('.content-tabs__content');
            }
        });
    
        self.initEventListeners();
        self.initTabs();
    });
    
    market.MyProfile = ComponentRegistry.register(function ($context) {
        return $context.select('.my-profile');
    }, function ($my_profile, self) {
        $.extend(self, {
            initDom: function () {
                self.initPasswordField();
                self.initPhotoField();
            },
            initPasswordField: function () {
                var $field = $('<div class="my-profile__change-password-box wa-field field">'
                  + '<div class="field__label field__label_empty"></div>'
                  + '<div class="field__content"></div>'
                  + '</div>');
                $field.find('.field__content').append(
                    $('<span class="my-profile__change-password-button pseudo-link">' + translate('Сменить пароль') + '</span>'));
    
                $my_profile.find('.wa-field-password').addClass('my-profile__password-field').before($field);
            },
            initPhotoField: function () {
                var $field = $my_profile.find('.wa-field-photo');
                var $field_value = $field.find('.wa-value');
                var $photo_input = $('<span class="my-profile-photo-input">'
                  + '<span class="my-profile-photo-input__photo-box">'
                  + '<span class="my-profile-photo-input__delete-button">'
                  + '</span>'
                  + '</span>'
                  + '<span class="my-profile-photo-input__input-box">'
                  + '</span>'
                  + '</span>');
                var $trash_svg = $(config.commons.svg.trash);
                $trash_svg.addClass('my-profile-photo-input__delete-icon');
                $photo_input.find('.my-profile-photo-input__delete-button').append($trash_svg);
    
                var $photo_box = $photo_input.find('.my-profile-photo-input__photo-box');
                var $input_box = $photo_input.find('.my-profile-photo-input__input-box');
                $photo_box.append($field_value.find('img, input[type="hidden"]'));
                $input_box.append($field_value.contents());
                var $default_photo = $photo_box.find('img:last');
                var $user_photo = $photo_box.find('img:first').not($default_photo);
                var $user_photo_input = $photo_box.find('input');
                $default_photo.addClass('my-profile-photo-input__default-photo');
                $user_photo.addClass('my-profile-photo-input__user-photo');
                $user_photo_input.addClass('my-profile-photo-input__user-photo-input');
    
                $field.addClass('my-profile__photo-field');
                $field_value.append($photo_input);
    
                MyProfilePhotoInput($photo_input);
            },
            initEventListeners: function () {
                $my_profile.find('.my-profile__edit-button').on('click', function () {
                    $my_profile.addClass('my-profile_edit');
                    $my_profile.removeClass('my-profile_success');
                    $my_profile.removeClass('my-profile_change-password');
                });
    
                $my_profile.find('.my-profile__cancel-button').on('click', function () {
                    $my_profile.removeClass('my-profile_edit');
                });
    
                $my_profile.find('.my-profile__change-password-button').on('click', function () {
                    $my_profile.addClass('my-profile_change-password');
                });
            }
        });
    
        self.initDom();
        self.initEventListeners();
    });
    
    var MyProfilePhotoInput = market.MyProfilePhotoInput = ComponentRegistry.register(function ($context) {
        return $context.select('.my-profile-photo-input');
    }, function ($photo_input, self) {
        $.extend(self, {
            initEventListeners: function () {
                var $photo_box = self.getPhotoBox();
                var $user_photo = $photo_box.find('img:first');
                var $default_photo = $photo_box.find('img:last');
                var $user_photo_input = $photo_box.find('input');
                var $delete_button = $photo_input.find('.my-profile-photo-input__delete-button');
    
                $photo_input.toggleClass('my-profile-photo-input_empty', $user_photo.is($default_photo));
    
                $delete_button.on('click', function () {
                    $user_photo_input.val('');
                    $photo_input.addClass('my-profile-photo-input_empty');
                });
            },
            getPhotoBox: function () {
                return $photo_input.find('.my-profile-photo-input__photo-box');
            },
            getFileBox: function () {
                return $photo_input.find('.my-profile-photo-input__photo-box');
            }
        });
    
        self.initEventListeners();
    });
    
    var AjaxForm = market.AjaxForm = ComponentRegistry.register(function ($context) {
        return $context.select('.ajax-form');
    }, function ($form, self) {
        var $submit_input = $();
    
        $.extend(self, {
            initEventListeners: function () {
                $form.on('click', function (e) {
                    var $target = $(e.target);
    
                    if (!$target.is(':submit')) {
                        return;
                    }
    
                    $submit_input = $('<input class="ajax-form__submit-input" type="hidden" />');
                    $submit_input.attr('name', $target.attr('name'));
                    $submit_input.val($target.val());
    
                    $form.append($submit_input);
                });
    
                $form.on('submit', function (e) {
                    e.preventDefault();
    
                    var custom_e = $.Event('before_submit@market:ajax-form');
    
                    $form.trigger(custom_e);
    
                    if (!custom_e.isDefaultPrevented()) {
                        self.submit();
                    }
                });
            },
            submit: function () {
                var form_data = $form.serialize();
                var url = LinkUtil.create($form.attr('action') || '').toString();
                var xhr = (function () {
                    var $xhr = $.ajaxSettings.xhr();
                    var setRequestHeader = $xhr.setRequestHeader;
    
                    $xhr.setRequestHeader = function (name, value) {
                        if (name == 'X-Requested-With' && $form.data('remove-header') !== undefined) return;
                        setRequestHeader.call(this, name, value);
                    };
    
                    return $xhr;
                })();
    
                var ajaxParams = {
                    url: url,
                    method: $form.attr('method') || 'get',
                    data: form_data,
                    xhr: function () {
                        return xhr;
                    },
                    complete: function () {
                        $form.trigger('complete@market:ajax-form', arguments);
                    }
                };
    
                var $fileInputs = $form.find('input[type="file"]');
    
                if ($fileInputs.length > 0) {
                    var fields_data = $form.serializeArray();
                    form_data = new FormData();
    
                    $.each(fields_data, function () {
                        var field = $(this)[0];
                        form_data.append(field.name, field.value);
                    });
    
                    $fileInputs.each(function () {
                        var $fileInput = $(this);
                        var $imageSection = $fileInput.closest('.add-images-section');
    
                        if ($imageSection.length > 0) {
                            var controller = $imageSection.data('controller'),
                                data = controller.getSerializedArray();
    
                            $.each(data, function (i, file_data) {
                                form_data.append(file_data.name, file_data.value);
                            });
                        } else {
                            if ($fileInput[0].files.length > 0) {
                                $($fileInput[0].files).each(function (i, file) {
                                    form_data.append($fileInput.attr('name'), file);
                                });
                            }
                        }
                    });
    
                    ajaxParams.processData = false;
                    ajaxParams.contentType = false;
                    ajaxParams.data = form_data;
                }
    
                $submit_input.remove();
    
                $form.addClass('loading');
    
                return $.ajax(ajaxParams).then(function (response) {
                    var responseUrl = xhr.responseURL || xhr.getResponseHeader('X-Request-URL');
                    responseUrl = responseUrl && LinkUtil.create(responseUrl).toString();
    
                    if (responseUrl && responseUrl !== url) {
                        window.location = responseUrl;
                    } else {
                        $form.trigger('success@market:ajax-form', arguments);
                    }
    
                    $form.removeClass('loading');
                }, function () {
                    $form.trigger('error@market:ajax-form', arguments);
                    $form.removeClass('loading');
                });
            }
        });
    
        self.initEventListeners();
    });
    
    var Modal = market.Modal = ComponentRegistry.register(function ($context) {
        return $context.select('.modal');
    }, function ($modal, self) {
        var _private = {
            initEventListeners: function () {
                $modal.find('.modal__close').on('click', function () {
                    ModalUtil.close();
                });
                /* $modal.closest('.modal-wrapper').on('click', function (e) {
                    if (e.target === this) {
                        ModalUtil.close();
                    }
                }); */
            }
        };
    
        $.extend(self, {
            show: function () {
                $modal.addClass('modal_opened');
            },
            close: function () {
                $modal.removeClass('modal_opened').addClass('modal_closing');
            }
        });
    
        _private.initEventListeners();
    });
    
    var ModalButtonContent = market.ModalButtonContent = ComponentRegistry.register(function ($context) {
        return $context.select('.modal-content-button');
    }, function ($button) {
        var _private = {
            initEventListeners: function (e) {
                $button.on('click', _private.openModal);
            },
            openModal: function (e) {
                e.preventDefault();
                var $content = $button.data('modal-content');
    
                if (typeof $content !== 'undefined') {
                    var title = $button.data('modal-title');
                    var classes = $button.data('modal-classes');
                    var contentClasses = $button.data('modal-content-classes');
                    var isNoDecorateContent = $button.data('modal-no-decorate');
    
                    market.ModalUtil.openContent($content, {
                        title: title || undefined,
                        classes: classes || '',
                        contentClasses: contentClasses || '',
                        beforeOpen: function ($modal) {
                            if (typeof isNoDecorateContent === 'undefined') {
                                var $modalContent = $modal.find('.modal__content');
                                $modalContent.addClass('content-decorator');
                                market.ContentDecorator($modalContent);
                            }
                        }
                    });
                }
            }
        };
    
        _private.initEventListeners();
    });
    
    var OpenPopupGallery = market.OpenPopupGallery = ComponentRegistry.register(function ($context) {
        return $context.select('.open-popup-gallery');
    }, function ($block, self) {
        $.extend(self, {
            initPopUp: function () {
                var $items = $block.find('.open-popup-gallery__item');
    
                $items.on('click', function (e) {
                    e.preventDefault();
                    var $gallery = null;
                    var $item = $(this);
    
                    if ($block.data('gallery')) {
                        $gallery = $($block.data('gallery'));
                    } else if (typeof market.config['commons']['popup_gallery_tpl'] !== 'undefined') {
                        $gallery = self.createGallery($items);
    
                        if ($gallery) {
                            $block.data('gallery', $gallery);
                        }
                    }
    
                    if ($gallery) {
                        var initialSlide = $items.index($item);
                        $gallery.find('.popup-gallery__images').data('initial_slide', initialSlide);
    
                        ModalUtil.openContent($gallery, {
                            classes: 'popup-gallery-modal',
                            onOpen: function () {
                                PopupGallery($gallery).selectSlide(initialSlide, 0);
                            }
                        });
                    }
                });
            },
            createGallery: function ($items) {
                var $gallery = null;
                var $galleryTpl = $('<div>' + market.config['commons']['popup_gallery_tpl'] + '</div>');
                var $galleryImagesWrapper = $galleryTpl.find('.popup-gallery__images-wrapper');
                var $galleryImageBlock = $galleryImagesWrapper.find('.popup-gallery__image');
    
                var $imagesTmp = $('<div></div>');
    
                $items.each(function () {
                    var $item = $(this);
                    var imageSrc = $item.data('popup-src');
                    var imageSrcset = $item.data('popup-srcset');
                    var imageAlt = $item.data('popup-alt');
    
                    if (typeof imageSrc === 'undefined') {
                        if ($item.is('a')) {
                            imageSrc = $item.attr('href');
                        } else if ($item.is('img')) {
                            imageSrc = $item.attr('src');
    
                            if (typeof imageSrcset === 'undefined') {
                                imageSrc = $item.attr('srcset');
                            }
                        }
                    }
    
                    if (typeof imageAlt === 'undefined' && $item.is('img')) {
                        imageAlt = $item.attr('alt');
                    }
    
                    if (imageSrc) {
                        var $itemImageBlock = $galleryImageBlock.clone();
                        var $itemImage = $itemImageBlock.find('img');
                        $itemImage.attr('src', imageSrc);
    
                        if (imageSrcset) {
                            $itemImage.attr('srcset', imageSrcset);
                        }
    
                        if (imageAlt) {
                            $itemImage.attr('alt', imageAlt);
                        }
    
                        if ($item.data('image_id')) {
                            $itemImageBlock.data('image_id', $item.data('image_id'));
                            $itemImage.data('image_id', $item.data('image_id'));
                        }
    
                        $imagesTmp.append($itemImageBlock);
                    }
                });
    
                var $images = $imagesTmp.children();
    
                if ($images.length > 0) {
                    $galleryImagesWrapper.html($imagesTmp.children());
                    $gallery = $($galleryTpl.html());
    
                    if ($block.data('class')) {
                        $gallery.addClass($block.data('class'));
                    }
    
                    if (typeof $block.data('pagination') !== 'undefined' && $block.data('pagination') === false) {
                        $gallery.find('.popup-gallery__pagination').remove();
                    }
    
                    if (typeof $block.data('options') !== 'undefined') {
                        $gallery.data('swiper-options', $block.data('options'));
                    }
                }
    
                $imagesTmp.remove();
                $galleryTpl.remove();
    
                return $gallery;
            }
        });
    
        self.initPopUp();
    });
    
    var PopupGallery = market.PopupGallery = ComponentRegistry.register(function ($context) {
        return $context.select('.popup-gallery');
    }, function ($popup_gallery, self) {
        var _private = {
            initSwiper: function () {
                var $images = $popup_gallery.find('.popup-gallery__images');
                var initial_slide = 0;
    
                if (typeof $images.data('initial_slide') !== 'undefined') {
                    initial_slide = $images.data('initial_slide');
                }
    
                $images.addClass('popup-gallery__images_swiper-init');
    
                var swiperOptions = {
                    cssMode: true,
                    wrapperClass: 'popup-gallery__images-wrapper',
                    slideClass: 'popup-gallery__image',
                    initialSlide: initial_slide,
                    navigation: {
                        prevEl: $popup_gallery.find('.popup-gallery__arrow_prev').get(0),
                        nextEl: $popup_gallery.find('.popup-gallery__arrow_next').get(0),
                        disabledClass: 'popup-gallery__arrow_disabled'
                    },
                    pagination: {
                        el: $popup_gallery.find('.gallery-pagination').get(0),
                        bulletClass: 'gallery-pagination__bullet',
                        bulletActiveClass: 'gallery-pagination__bullet_active',
                        dynamicBullets: true,
                        clickable: true
                    }
                };
    
                var dataOptions = $popup_gallery.data('swiper-options');
    
                if (typeof dataOptions === 'object') {
                    $.extend(swiperOptions, dataOptions);
                }
    
                _private.swiper = new Swiper($images.get(0), swiperOptions);
            }
        };
    
        $.extend(self, {
            selectSlide: function (index, speed) {
                if (typeof _private.swiper !== 'undefined' && index) {
                    var _speed = 300;
    
                    if (typeof speed === 'number') {
                        _speed = speed;
                    }
    
                    _private.swiper.slideTo(index, _speed);
                }
            }
        });
    
        _private.initSwiper();
    });
    
    market.Slider = ComponentRegistry.register(function ($context) {
        return $context.select('.slider');
    }, function ($slider) {
        var _private = {
            effect: $slider.data('effect'),
            autoplay: $slider.data('autoplay'),
            autoplay_delay: $slider.data('autoplay_delay'),
            autoheight: $slider.data('autoheight'),
            is_products: $slider.data('is-products'),
            image_size: $slider.data('image_size'),
    
            initSwiper: function () {
                if ($slider.find('.slider__slide').length <= 1) {
                    return;
                }
    
                var images = $slider.find('.slide__background-image.swiper-lazy');
    
                if (images.length > 0) {
                    if (market.MatchMedia('(max-width: 1023px)')) {
                        var size = _private.image_size;
    
                        if (market.MatchMedia('(max-width: 425px)')) {
                            var dpr = 1;
    
                            if (typeof window !== 'undefined' && window.devicePixelRatio > 1) {
                                dpr = 2;
                            }
    
                            size = 500 * dpr;
                        }
    
                        images.each(function () {
                            var image = $(this);
                            var src = $(image).data('src');
                            var newSrc = src.replace(/^(.*\/*\.)(.*)(\..*)$/, '$1' + size + '$3');
    
                            $(image).attr('data-src', newSrc);
                        });
                    }
                }
    
                var autoplay = false;
    
                if (_private.autoplay) {
                    autoplay = {
                        delay: _private.autoplay_delay * 1000
                    };
                }
    
                var autoheight = false;
    
                if (_private.autoheight) {
                    autoheight = true;
                }
    
                var swiper = new Swiper($slider.find('.slider__slider').get(0), {
                    cssMode: _private.effect === 'slide',
                    loop: true,
                    effect: _private.effect,
                    autoplay: autoplay,
                    parallax: true,
                    speed: 1000,
                    autoHeight: autoheight,
                    pagination: {
                        el: '.slider__dots',
                        type: 'bullets',
                        clickable: true,
                        bulletClass: 'slider__dot',
                        bulletActiveClass: 'slider__dot_active',
                        modifierClass: 'slider__dots_'
                    },
                    navigation: {
                        prevEl: $slider.find('.slider__prev-arrow').get(0),
                        nextEl: $slider.find('.slider__next-arrow').get(0)
                    },
                    lazy: {
                        loadPrevNext: autoheight || _private.is_products,
                        loadPrevNextAmount: 1,
                        elementClass: 'swiper-lazy'
                    }
                });
    
                $(document).trigger('swiper_lazyload@market:global', swiper);
    
                swiper.on('slideChange', function () {
                    var $slide = $(swiper.slides[swiper.activeIndex]).find('.slide');
                    var color = $slide.css('color');
                    var background_color = $slide.css('backgroundColor');
                    $slider.css('color', color);
                    $slider.css('backgroundColor', background_color);
                    Update($slider);
                });
            }
        };
    
        _private.initSwiper();
    });
    
    market.RatingSelect = ComponentRegistry.register(function ($context) {
        return $context.select('.rating-select');
    }, function ($select, self) {
        $.extend(self, {
            initStars: function () {
                var value = self.getValue();
    
                if (!value) {
                    return;
                }
    
                $select.find('.rating-select__star').each(function () {
                    var $star = $(this);
    
                    if (+$star.data('value') === +value) {
                        self.selectStar($star);
                    }
                });
            },
            initEventListeners: function () {
                $select.find('.rating-select__stars').on('mouseenter', function () {
                    $select.addClass('rating-select_hover');
                });
    
                $select.find('.rating-select__stars').on('mouseleave', function () {
                    $select.removeClass('rating-select_hover');
                });
    
                $select.find('.rating-select__star').on('mouseenter', function () {
                    var $star = $(this);
                    var $stars = $star.siblings();
                    var $hover_stars = $star.prevAll().add($star);
                    $stars.removeClass('rating-select__star_hover');
                    $hover_stars.addClass('rating-select__star_hover');
                    self.setHoverValueText($star.data('text'));
                });
    
                $select.find('.rating-select__star').on('click', function () {
                    var $star = $(this);
                    self.selectStar($star);
                });
            },
            selectStar: function ($star) {
                var $stars = $star.siblings();
                var $active_stars = $star.prevAll().add($star);
                $stars.removeClass('rating-select__star_active');
                $active_stars.addClass('rating-select__star_active');
                self.setActiveValueText($star.data('text'));
                self.setValue($star.data('value'));
            },
            getValue: function () {
                return $select.find('.rating-select__input').val();
            },
            setValue: function (value) {
                return $select.find('.rating-select__input').val(value);
            },
            setHoverValueText: function (text) {
                $select.find('.rating-select__hover-value').text(text);
            },
            setActiveValueText: function (text) {
                $select.find('.rating-select__active-value').text(text);
            }
        });
    
        self.initStars();
        self.initEventListeners();
    });
    
    market.FormField = ComponentRegistry.register(function ($context) {
        return $context.select('.form-field');
    }, function ($field, self) {
        var _private = {
            name: $field.data('field_name'),
    
            isRequired: function () {
                return $field.hasClass('wa-required');
            },
    
            workupField: function () {
                if (this.isRequired()) {
                    $field.addClass('form-field_required');
    
                    Update($field);
                }
            },
    
            initGlobalEventListeners: function () {
                $(document).on('success@market:ajax-form', '.ajax-form', _private.handleAjaxFormSuccess);
            },
    
            destroyGlobalEventListeners: function () {
                $(document).off('success@market:ajax-form', '.ajax-form', _private.handleAjaxFormSuccess);
            },
    
            handleAjaxFormSuccess: function (e, response) {
                var $form = $(this);
    
                var _response = response;
    
                if (typeof _response !== 'object') {
                    var JSONResponse = JSON.parse(response);
    
                    if (JSONResponse['status']) {
                        _response = JSONResponse;
                    }
                }
    
                if ($field.closest($form).length === 0) {
                    return;
                }
    
                if (_response.status !== 'fail') {
                    self.setError('');
    
                    return;
                }
    
                var error = null;
    
                if (_response.errors) {
                    if (_response.errors.length) {
                        var error_entry = _response.errors.find(function (error) {
                            return !!error[self.getName()];
                        });
    
                        if (error_entry) {
                            error = error_entry[self.getName()];
                        }
                    } else {
                        error = _response.errors[self.getName()];
                    }
                }
    
                self.setError(error);
            }
        };
    
        $.extend(self, {
            getName: function () {
                return _private.name;
            },
    
            setError: function (error) {
                $field.find('.form-field__error-container').remove();
                var $error = $('<div class="form-field__error-container"></div>');
                var $text = $field.find('.input-text');
                var $textarea = $field.find('.textarea');
                $text.removeClass('input-text_error');
                $textarea.removeClass('textarea_error');
    
                if (error) {
                    $error.html(error);
                    $field.find('.form-field__value').append($error);
                    $text.addClass('input-text_error');
                    $textarea.addClass('textarea_error');
                }
            }
        });
    
        _private.initGlobalEventListeners();
        _private.workupField();
    
        return function () {
            _private.destroyGlobalEventListeners();
        };
    });
    
    market.Breadcrumbs = ComponentRegistry.register(function ($context) {
        return $context.select('.breadcrumbs');
    }, function ($breadcrumbs, self) {
        var _private = {
            breadCrumbsData: null,
            initData: function () {
                var $dataInput = $breadcrumbs.find('[name="breadcrumbs_data"]');
    
                if ($dataInput.length > 0) {
                    _private.breadCrumbsData = JSON.parse($dataInput.val());
                    $dataInput.remove();
                }
            }
        };
    
        $.extend(self, {
            initBrothers: function () {
                if (_private.breadCrumbsData === null) {
                    return;
                }
    
                $breadcrumbs.find('.breadcrumbs__brothers-button').each(function () {
                    var $breadcrumb = $(this).closest('.breadcrumbs__item');
                    var index = $breadcrumb.index();
                    var breadcrumb = _private.breadCrumbsData.breadcrumbs[index];
    
                    if (index === _private.breadCrumbsData.breadcrumbs.length) {
                        breadcrumb = _private.breadCrumbsData.current_page_item;
                    }
    
                    if (!breadcrumb) {
                        return;
                    }
    
                    var $dropdown = $('<div class="breadcrumbs__brothers-dropdown"></div>');
    
                    breadcrumb.brothers.forEach(function (brother) {
                        var $brother = $('<a class="breadcrumbs__brother link"></a>');
                        $brother.text(brother.name);
                        $brother.attr('href', brother.frontend_url);
                        $dropdown.append($brother);
                    });
    
                    $breadcrumb.append($dropdown);
                });
            },
            initEventListeners: function () {
                if (_private.breadCrumbsData === null) {
                    return;
                }
    
                $breadcrumbs.find('.breadcrumbs__brothers-button').on('click', function () {
                    var $breadcrumb = $(this).closest('.breadcrumbs__item');
                    $breadcrumb.addClass('breadcrumbs__item_show-brothers');
                });
    
                $(document).on('click', function (e) {
                    $breadcrumbs.find('.breadcrumbs__item').each(function () {
                        var $breadcrumb = $(this);
    
                        if ($(e.target).closest($breadcrumb).length === 0) {
                            $breadcrumb.removeClass('breadcrumbs__item_show-brothers');
                        }
                    });
                });
    
                if (_private.breadCrumbsData === null || !_private.breadCrumbsData.show_subcategories_on_hover || ResponsiveUtil.isTabletMax()) {
                    return;
                }
    
                $breadcrumbs.find('.breadcrumbs__item:has(.breadcrumbs__brothers-dropdown)').each(function () {
                    var timeout_id = null;
                    var $breadcrumb = $(this);
    
                    $(this).on('mouseenter', function () {
                        clearTimeout(timeout_id);
                        $breadcrumb.addClass('breadcrumbs__item_show-brothers');
                        $breadcrumb.siblings().removeClass('breadcrumbs__item_show-brothers');
                    }).on('mouseleave', function () {
                        timeout_id = setTimeout(function () {
                            $breadcrumb.removeClass('breadcrumbs__item_show-brothers');
                        }, 500);
                    });
                });
            }
        });
    
        _private.initData();
    
        self.initBrothers();
        self.initEventListeners();
    });
    
    market.UpButton = ComponentRegistry.register(function ($context) {
        return $context.select('.up-button');
    }, function ($button, self) {
        var request_id = null;
    
        var updateState = function () {
            var $header = $('.header');
            $button.toggleClass('up-button_show', $(window).scrollTop() > $header.outerHeight());
        };
    
        $.extend(self, {
            updateState: function () {
                cancelAnimationFrame(request_id);
                request_id = requestAnimationFrame(updateState);
            }
        });
    
        $(window).on('scroll', function () {
            self.updateState();
        });
    
        $button.on('click', function () {
            ScrollUtil.scrollTo(0);
        });
    
        self.updateState();
    });
    
    market.CookiePanel = ComponentRegistry.register(function ($context) {
        return $context.select('.cookie-panel');
    }, function ($panel) {
        $panel.find('.cookie-panel__close-button').on('click', function () {
            var $container = $(this).closest('.info-panel-container');
            var container = InfoPanelContainer($container);
            container.close();
            $.cookie('cookie_agree', 1, {
                expires: 31,
                path: config.commons.wa_url
            });
        });
    });
    
    market.LoginModal = ComponentRegistry.register(function ($context) {
        return $context.select('.login-modal');
    }, function ($modal) {
        var _private = {
            isOnetime: function () {
                var $onetime_button = $modal.find('.wa-request-onetime-password-button');
    
                return $onetime_button.length;
            },
    
            initType: function () {
                if (this.isOnetime()) {
                    $modal.addClass('login-modal_onetime');
                } else {
                    $modal.addClass('login-modal_general');
                }
            },
    
            initNavButtons: function () {
                var $nav_buttons = $modal.find('.login-page__nav-buttons');
                $nav_buttons.addClass('login-modal__nav-buttons');
                $nav_buttons.find('a').removeClass('button_style_transparent').addClass('button_style_light login-modal__nav-button');
    
                if (_private.isOnetime()) {
                    var $onetime_button_wrapper = $modal.find('.wa-request-onetime-password-button-wrapper');
                    $onetime_button_wrapper.addClass('login-modal__nav-button login-modal__nav-button_fake');
                    var $onetime_button = $onetime_button_wrapper.find('.wa-request-onetime-password-button');
                    $onetime_button.addClass('button_style_light');
    
                    $modal.find('.wa-login-submit').addClass('login-modal__nav-button button_style_light');
                } else {
                    // $modal.find('.modal__content').append($nav_buttons);
    
                    $modal.find(':submit').each(function (i) {
                        var $button = $(this);
                        var $submit_container = $('<div class="wa-submit"></div>');
                        $button.after($submit_container);
                        var $submit_line = $('<div class="login-modal__submit-line"></div>');
                        var $submit_line_button = $('<div class="login-modal__submit-line-button"></div>');
    
                        $modal.find('.wa-field-remember-me').eq(i).each(function () {
                            var $checkbox = $(this);
                            var $submit_line_checkbox = $('<div class="login-modal__submit-line-checkbox"></div>');
                            $submit_line_checkbox.append($checkbox).appendTo($submit_line);
                        });
    
                        $submit_line_button.append($button).appendTo($submit_line);
                        $submit_line.appendTo($submit_container);
                    });
                }
            },
    
            initForm: function () {
                var $form = $modal.find('form');
                $form.attr('action', $form.attr('action') + '?modal=1');
            }
        };
    
        _private.initType();
        _private.initNavButtons();
        _private.initForm();
    });
    
    market.SignupModal = ComponentRegistry.register(function ($context) {
        return $context.select('.signup-modal');
    }, function ($modal) {
        var _private = {
            initButton: function () {
                var $login_button = $modal.find('.signup-page__login-button').removeClass('signup-page__login-button button_style_transparent')
                    .addClass('signup-modal__login-button button_style_light button_wide');
                $login_button.parent().prepend($login_button);
            }
        };
    
        _private.initButton();
    });
    
    market.HeaderBottomBarLinks = ComponentRegistry.register(function ($context) {
        return $context.select('.header-bottom-bar-links');
    }, function ($links, self) {
        var _private = {
            initEventListeners: function () {
                $links.on('open@market:dropdown', '.dropdown', function () {
                    var $dropdown = $(this);
                    var $active_dropdown = $links.find('.dropdown_open').not($dropdown);
                    $active_dropdown.each(function () {
                        var dropdown = Dropdown($(this));
                        dropdown.close();
                    });
                    $dropdown.children('.button').addClass('button_active');
                    $links.trigger('open@market:header-bottom-bar-links');
                });
    
                $links.on('close@market:dropdown', '.dropdown', function () {
                    var $dropdown = $(this);
                    $dropdown.children('.button').removeClass('button_active');
                    $links.trigger('close@market:header-bottom-bar-links');
                });
            }
        };
    
        $.extend(self, {
            closeDropdowns: function () {
                $links.find('.dropdown').each(function () {
                    var dropdown = Dropdown($(this));
                    dropdown.close();
                });
            }
        });
    
        _private.initEventListeners();
    });
    
    market.ListTabs = ComponentRegistry.register(function ($context) {
        return $context.select('.list-tabs');
    }, function ($tabs_list) {
        var _private = {
            tab_open: 'list-tabs__item_opened',
    
            initEventListeners: function () {
                $tabs_list.on('click', '.js-list-tabs__toggle', function (e) {
                    e.preventDefault();
                    var $item = $(this).closest('.list-tabs__item');
    
                    if ($item.hasClass(_private.tab_open)) {
                        $item.removeClass(_private.tab_open);
                    } else {
                        $item.addClass(_private.tab_open);
                    }
                });
            }
        };
    
        _private.initEventListeners();
    });
    
    market.YtVideo = ComponentRegistry.register(function ($context) {
        return $context.select('.yt-video');
    }, function ($videoBlock) {
        var _private = {
            setupVideo: function () {
                var link = $videoBlock.find('.yt-video__link');
                var media = $videoBlock.find('.yt-video__media');
                var button = $videoBlock.find('.yt-video__button');
                var id = $videoBlock.data('id');
    
                if (!id) {
                    id = _private.parseMediaURL(media);
                }
    
                $videoBlock.on('click', function () {
                    var iframe = _private.createIframe(id);
    
                    link.remove();
                    button.remove();
                    $videoBlock.append(iframe);
                    $videoBlock.addClass('yt-video--loaded');
                    $videoBlock.off('click');
                });
    
                $videoBlock.on('pause stop', function (e) {
                    var action = e.type + 'Video';
                    var $iframe = $videoBlock.find('iframe');
    
                    if ($iframe.length > 0) {
                        $iframe[0].contentWindow.postMessage('{"event":"command","func":"' + action + '","args":""}', '*');
                    }
                });
    
                link.removeAttr('href');
                $videoBlock.addClass('yt-video--enabled');
            },
    
            parseMediaURL: function (media) {
                var url = media.attr('src');
                var regexp;
    
                if (url.indexOf('maxresdefault') >= 0) {
                    regexp = /https:\/\/i\.ytimg\.com\/vi\/([a-zA-Z0-9_-]+)\/maxresdefault\.jpg/i;
                } else {
                    regexp = /https:\/\/i\.ytimg\.com\/vi\/([a-zA-Z0-9_-]+)\/hqdefault\.jpg/i;
                }
    
                var match = url.match(regexp);
    
                return match[1];
            },
    
            createIframe: function (id) {
                var iframe = $('<iframe></iframe>');
    
                iframe.attr('allowfullscreen', '');
                iframe.attr('allow', 'autoplay');
                iframe.attr('src', _private.generateURL(id));
                iframe.addClass('yt-video__media');
    
                return iframe;
            },
    
            generateURL: function (id) {
                var query = '?rel=0&showinfo=0&autoplay=1&enablejsapi=1';
    
                return 'https://www.youtube.com/embed/' + id + query;
            }
        };
    
        _private.setupVideo();
    });
    
    market.Countdown = ComponentRegistry.register(function ($context) {
        return $context.select('.countdown');
    }, function ($countdown) {
        function createItem(value, text) {
            return $('<div class="countdown__item"></div>')
                .append($('<div class="countdown__item-value"></div>').text(value))
                .append($('<div class="countdown__item-text"></div>').text(text));
        }
    
        var _private = {
            datetime: $countdown.data('datetime'),
    
            initCountdown: function () {
                $countdown.countdown(_private.datetime, function (e) {
                    if (e.type === 'finish') {
                        $countdown.remove();
    
                        return;
                    }
    
                    let $container = $('<div></div>');
    
                    let ignoreZero = true;
                    let days = e.strftime('%D');
    
                    if (days > 0) {
                        ignoreZero = false;
                        $container.append(createItem(days, market.PluralUtil.getPluralValue('days', days)));
                    }
    
                    let hours = e.strftime('%H');
    
                    if (hours > 0 || !ignoreZero) {
                        ignoreZero = false;
                        $container.append(createItem(hours, market.PluralUtil.getPluralValue('hours', hours)));
                    }
    
                    let minutes = e.strftime('%M');
    
                    if (minutes > 0 || !ignoreZero) {
                        ignoreZero = false;
                        $container.append(createItem(minutes, market.PluralUtil.getPluralValue('minutes', minutes)));
                    }
    
                    let seconds = e.strftime('%S');
    
                    if (seconds > 0 || !ignoreZero) {
                        $container.append(createItem(seconds, market.PluralUtil.getPluralValue('seconds', seconds)));
                    }
    
                    $countdown.html($container.html());
    
                    // $days.toggleClass('countdown__countdown-field_hidden', parseInt(days) <= 0);
                    // $days.find('.countdown__countdown-value').text(days);
                    // $days.find('.countdown__countdown-name').text(market.PluralUtil.getPluralValue('days', days));
                    //
                    // $hours.find('.countdown__countdown-value').text(hours);
                    // $hours.find('.countdown__countdown-name').text(market.PluralUtil.getPluralValue('hours', hours));
                    //
                    // $minutes.find('.countdown__countdown-value').text(minutes);
                    // $minutes.find('.countdown__countdown-name').text(market.PluralUtil.getPluralValue('minutes', minutes));
                    //
                    // $seconds.find('.countdown__countdown-value').text(seconds);
                    // $seconds.find('.countdown__countdown-name').text(market.PluralUtil.getPluralValue('seconds', seconds));
                });
            }
        };
    
        _private.initCountdown();
        $countdown.addClass('countdown_js-is-init');
    });
    
    market.YaShare2 = ComponentRegistry.register(function ($context) {
        return $context.select('.ya-share2');
    }, function ($ya_share) {
        if ($ya_share.hasClass('ya-share2_inited') || window.Ya === undefined || (window.Ya !== undefined && window.Ya.share2 === undefined)) {
            return;
        }
    
        window.Ya.share2($ya_share.get(0));
    });
    
    market.BannerPanel = ComponentRegistry.register(function ($context) {
        return $context.select('.banner-panel');
    }, function ($panel) {
        var _private = {
            banner_hash: $panel.data('banner_hash'),
    
            initEventListeners: function () {
                $panel.on('close@market:info-panel', function () {
                    $.cookie('banner_hash', _private.banner_hash);
                    $panel.remove();
                });
            }
        };
    
        _private.initEventListeners();
    });
    
    market.MailerDecorator = ComponentRegistry.register(function ($context) {
        return $context.select('.mailer-decorator');
    }, function ($decorator) {
        var _private = {
            initFields: function () {
                var $inline_field = $('<span class="inline-field"><span class="inline-field__content"></span><span class="inline-field__policy r-hidden-desktop"></span><span class="inline-field__button"></span></span>');
    
                $decorator.find('[name="subscriber[email]"]').each(function () {
                    var $email = $(this);
                    $email.addClass('mailer-decorator__email-input');
                    $email.addClass('input-text').removeClass('wa-fill');
                    var $field = $email.closest('.wa-field');
                    $field.replace($inline_field);
                    $inline_field.find('.inline-field__content').append($email);
                });
    
                $decorator.find('.wa-submit').each(function () {
                    var $button = $(this);
                    var $source_field = $button.closest('.wa-field');
                    var $field = $source_field.clone();
                    $source_field.addClass('mailer-decorator__r-button-container');
                    $inline_field.find('.inline-field__button').append($field);
                });
    
                var $error = $decorator.find('.wa-errormsg');
                $error.addClass('mailer-decorator__error');
                $error.closest('.form__field').addClass('mailer-decorator__error-field');
    
                market.Update($decorator);
            }
        };
    
        _private.initFields();
    
        $decorator.addClass('mailer-decorator_js-is-init');
    });
    
    market.MailerPostDecorator = ComponentRegistry.register(function ($context) {
        return $context.select('.mailer-post-decorator');
    }, function ($decorator) {
        var _private = {
            initFields: function () {
                var $inline_field = $('<span class="inline-field"><span class="inline-field__content"></span><span class="inline-field__button"></span></span>');
    
                $decorator.find('[name="subscriber[email]"]').each(function () {
                    var $email = $(this);
                    $email.addClass('input-text').removeClass('wa-fill');
                    var $field = $email.closest('.wa-field');
                    $field.replace($inline_field);
                    $inline_field.find('.inline-field__content').append($email);
                });
    
                $decorator.find('.wa-submit').each(function () {
                    var $button = $(this);
                    var $source_field = $button.closest('.wa-field');
                    var $field = $source_field.clone();
                    $source_field.addClass('mailer-post-decorator__r-button-container');
                    $inline_field.find('.inline-field__button').append($field);
                });
    
                market.Update($decorator);
            }
        };
    
        _private.initFields();
    
        $decorator.addClass('mailer-post-decorator_js-is-init');
    });
    
    var SocialBlock = market.SocialBlock = ComponentRegistry.register(function ($context) {
        return $context.select('.social-block');
    }, function ($block, self) {
        $.extend(self, {
            initEventListeners: function () {
                var $tabs = $block.find('.social-block__tab');
                var $contents = $block.find('.social-block__content');
    
                $tabs.on('click', function () {
                    var $tab = $(this);
                    var $content = $contents.eq($tab.index());
    
                    $tabs.removeClass('social-block__tab_active');
                    $contents.removeClass('social-block__content_active');
                    $tab.addClass('social-block__tab_active');
                    $content.addClass('social-block__content_active');
                });
            }
        });
    
        self.initEventListeners();
    });
    
    market.Tooltip = ComponentRegistry.register(function ($context) {
        return $context.select('.tooltip');
    }, function ($tooltip, self) {
        $.extend(self, {
            initEventListeners: function () {
                $(document).on('r-filters_opened@market:global', function () {
                    self.setStyles();
                });
            },
            setStyles: function () {
                var tooltipContent = $tooltip.find('.tooltip__content');
    
                if (tooltipContent.hasClass('tooltip__content_show-m')) {
                    var tooltipContentWidth = tooltipContent.outerWidth();
                    var position = $tooltip.offset().left;
    
                    if (tooltipContentWidth / 2 > position) {
                        tooltipContent.addClass('tooltip__content_align-right');
                    }
                }
            }
        });
    
        self.initEventListeners();
        self.setStyles();
    });
    
    market.Share = ComponentRegistry.register(function ($context) {
        return $context.select('.share');
    }, function ($share) {
        const _private = {
            initEventListeners: function () {
                $share.find('.share__link_copy').on('click', function () {
                    var $link = $(this);
                    var $linkText = $link.find('.share__link-text');
                    navigator.clipboard.writeText(location.href).then(function () {
                        $linkText.text(config.language['link_is_copied']);
                    }).catch(function () {
                        $linkText.text('Fail');
                    });
                });
    
                $share.closest('.dropdown').on('close@market:dropdown', function () {
                    $share.find('.share__link_copy .share__link-text').text(config.language['copy_link']);
                });
            }
        };
    
        _private.initEventListeners();
    });
    

    var CatalogDropdownButton = market.CatalogDropdownButton = ComponentRegistry.register(function ($context) {
        return $context.select('.catalog-dropdown-button');
    }, function ($button) {
        var _private = {
            trigger_type: $button.data('trigger_type'),
            is_in_middle_bar: $button.hasClass('header-middle-bar-shop-catalog-button'),
    
            initGlobalEventListeners: function () {
                $(document).on('click', _private.handleDocumentClick);
                $(document).on('open@market:catalog-dropdown', _private.handleOpenDropdown);
                $(document).on('close@market:catalog-dropdown', _private.handleCloseDropdown);
            },
    
            destroyGlobalEventListeners: function () {
                $(document).off('click', _private.handleDocumentClick);
                $(document).off('open@market:catalog-dropdown', _private.handleOpenDropdown);
                $(document).off('close@market:catalog-dropdown', _private.handleCloseDropdown);
            },
    
            initEventListeners: function () {
                var $catalogDropdown = $('.header__dropdown-bar').find('.catalog-dropdown');
    
                if (_private.trigger_type === 'click') {
                    $button.on('click', function () {
                        $catalogDropdown.each(function () {
                            var dropdown = CatalogDropdown($(this));
    
                            if (dropdown.isOpen()) {
                                dropdown.close(true);
                            } else {
                                _private.toggleDropdownBarOrder(true);
                                dropdown.open();
                            }
                        });
                    });
                } else if (_private.trigger_type === 'hover') {
                    $button.on('mouseenter', function () {
                        $catalogDropdown.each(function () {
                            var dropdown = CatalogDropdown($(this));
                            _private.toggleDropdownBarOrder(true);
                            dropdown.open();
                        });
                    });
    
                    $button.on('mouseleave', function () {
                        $catalogDropdown.each(function () {
                            var dropdown = CatalogDropdown($(this));
                            dropdown.leave();
                        });
                    });
                }
            },
    
            handleDocumentClick: function (e) {
                if ($(e.target).closest($button).length !== 0) {
                    return;
                }
    
                $('.header__dropdown-bar').find('.catalog-dropdown').each(function () {
                    var dropdown = CatalogDropdown($(this));
                    dropdown.close();
                });
            },
    
            toggleDropdownBarOrder: function (toToggle) {
                if (_private.is_in_middle_bar) {
                    $('.header__dropdown-bar').toggleClass('header__dropdown-bar_upper', toToggle);
                }
            },
    
            handleOpenDropdown: function () {
                $button.addClass('button_active');
            },
    
            handleCloseDropdown: function () {
                $button.removeClass('button_active');
                _private.toggleDropdownBarOrder(false);
            }
        };
    
        _private.initEventListeners();
        _private.initGlobalEventListeners();
    
        return function () {
            _private.destroyGlobalEventListeners();
        };
    });
    
    var CatalogDropdown = market.CatalogDropdown = ComponentRegistry.register(function ($context) {
        return $context.select('.catalog-dropdown');
    }, function ($dropdown, self) {
        var _private = {
            trigger_type: $dropdown.data('trigger_type'),
            leave_timeout_id: null,
            force_closing: false,
            initEventListeners: function () {
                $dropdown.on('click', function (e) {
                    e.stopPropagation();
                });
    
                $dropdown.on('mouseenter', function () {
                    self.open();
                });
    
                if (_private.trigger_type !== 'click' && config['commons']['header_variant'] != '4') {
                    $dropdown.on('mouseleave', function () {
                        self.leave();
                    });
                }
    
                $dropdown.onAnimationEnd(function () {
                    $dropdown.removeClass('catalog-dropdown_animated');
                    _private.force_closing = false;
                });
            }
        };
    
        $.extend(self, {
            isOpen: function () {
                return $dropdown.hasClass('catalog-dropdown_open');
            },
    
            open: function () {
                clearTimeout(_private.leave_timeout_id);
    
                if (self.isOpen() || ($dropdown.hasClass('catalog-dropdown_animated') && _private.force_closing)) {
                    return;
                }
    
                $dropdown.addClass('catalog-dropdown_open catalog-dropdown_animated');
    
                $dropdown.trigger('open@market:catalog-dropdown');
            },
    
            leave: function () {
                clearTimeout(_private.leave_timeout_id);
                _private.leave_timeout_id = setTimeout(function () {
                    self.close();
                }, config['commons']['catalog_onleave_timeout']);
            },
    
            close: function (force) {
                if (force === undefined) {
                    force = false;
                }
    
                clearTimeout(_private.leave_timeout_id);
    
                if (!self.isOpen()) {
                    return;
                }
    
                _private.force_closing = force;
    
                $dropdown.removeClass('catalog-dropdown_open');
                $dropdown.addClass('catalog-dropdown_animated');
    
                $dropdown.trigger('close@market:catalog-dropdown');
            }
        });
    
        _private.initEventListeners();
    });
    
    var HorizontalCatalog = market.HorizontalCatalog = ComponentRegistry.register(function ($context) {
        return $context.select('.horizontal-catalog');
    }, function ($catalog, self) {
        var _private = {
            enter_timeout_id: null,
            leave_timeout_id: null,
    
            initEventListeners: function () {
                $catalog.on('mouseenter', function () {
                    clearTimeout(_private.leave_timeout_id);
                    $catalog.trigger('enter@market:horizontal-catalog');
                });
    
                $catalog.on('mouseleave', function () {
                    self.leave();
                });
            }
        };
    
        $.extend(self, {
            isOpen: function () {
                return $catalog.hasClass('horizontal-catalog_open');
            },
    
            openCategory: function (category_id) {
                var $category = $catalog.find('.horizontal-catalog__category').filter(function () {
                    return $(this).data('category_id') === category_id;
                });
    
                if ($category.length === 0) {
                    return;
                }
    
                $catalog.find('.horizontal-catalog__category_active').each(function () {
                    var $active_category = $(this);
                    $active_category.removeClass('horizontal-catalog__category_active');
                    $catalog.trigger('close_category@market:horizontal-catalog', $active_category.data('category_id'));
                });
    
                $category.addClass('horizontal-catalog__category_active');
                self.open();
                $catalog.trigger('open_category@market:horizontal-catalog', category_id);
            },
    
            open: function () {
                clearTimeout(_private.leave_timeout_id);
                clearTimeout(_private.enter_timeout_id);
    
                if (self.isOpen()) {
                    return;
                }
    
                _private.enter_timeout_id = setTimeout(function () {
                    $catalog.removeClass('horizontal-catalog_close');
                    $catalog.offset();
                    $catalog.addClass('horizontal-catalog_open');
                    $catalog.trigger('open@market:horizontal-catalog');
                }, config['commons']['catalog_onenter_timeout']);
            },
    
            leave: function () {
                clearTimeout(_private.leave_timeout_id);
                clearTimeout(_private.enter_timeout_id);
    
                if (!self.isOpen()) {
                    self.close(true);
                }
    
                _private.leave_timeout_id = setTimeout(function () {
                    self.close();
                }, config['commons']['catalog_onleave_timeout']);
            },
    
            close: function (check) {
                check = check || false;
    
                clearTimeout(_private.leave_timeout_id);
    
                if (!self.isOpen() && !check) {
                    return;
                }
    
                $catalog.find('.horizontal-catalog__category_active').each(function () {
                    var $active_category = $(this);
                    $catalog.trigger('close_category@market:horizontal-catalog', $active_category.data('category_id'));
                });
    
                $catalog.removeClass('horizontal-catalog_open');
                $catalog.offset();
                $catalog.addClass('horizontal-catalog_close');
                $catalog.trigger('close@market:horizontal-catalog');
            }
        });
    
        _private.initEventListeners();
    });
    
    var CatalogCategoryButton = market.CatalogCategoryButton = ComponentRegistry.register(function ($context) {
        return $context.select('.catalog-category-button');
    }, function ($button) {
        var _private = {
            showCatalog: function (category_id) {
                $('.horizontal-catalog').each(function () {
                    var catalog = HorizontalCatalog($(this));
                    catalog.openCategory(category_id);
                });
            },
    
            leaveCatalog: function () {
                $('.horizontal-catalog').each(function () {
                    var catalog = HorizontalCatalog($(this));
                    catalog.leave();
                });
            },
    
            handleMouseEnter: function () {
                this.showCatalog($button.data('category_id'));
            },
    
            handleMouseLeave: function () {
                this.leaveCatalog();
            },
    
            initEventListeners: function () {
                $button.on('mouseenter', this.handleMouseEnter.bind(this));
    
                $button.on('mouseleave', this.handleMouseLeave.bind(this));
            },
    
            initGlobalEventListeners: function () {
                $(document).on('open_category@market:horizontal-catalog', '.horizontal-catalog', _private.handleOpenCategory);
                $(document).on('close_category@market:horizontal-catalog', '.horizontal-catalog', _private.handleCloseCategory);
            },
    
            destroyGlobalEventListeners: function () {
                $(document).off('open_category@market:horizontal-catalog', '.horizontal-catalog', _private.handleOpenCategory);
                $(document).off('close_category@market:horizontal-catalog', '.horizontal-catalog', _private.handleCloseCategory);
            },
    
            handleOpenCategory: function (e, category_id) {
                if (category_id !== $button.data('category_id')) {
                    return;
                }
    
                $button.addClass('button_active');
                $button.trigger('open_category@market:catalog-category-button');
            },
    
            handleCloseCategory: function (e, category_id) {
                if (category_id !== $button.data('category_id')) {
                    return;
                }
    
                $button.removeClass('button_active');
                $button.trigger('close_category@market:catalog-category-button');
            }
        };
    
        _private.initEventListeners();
        _private.initGlobalEventListeners();
    
        return function () {
            _private.destroyGlobalEventListeners();
        };
    });
    
    var CatalogExtend = market.CatalogExtend = ComponentRegistry.register(function ($context) {
        return $context.select('.catalog-extend');
    }, function ($catalog, self) {
        var _private = {
            initEventListeners: function () {
                $catalog.on('open_category@market:catalog-list', '.catalog-list', function (e, category_id) {
                    _private.openCategory(category_id);
                });
    
                $catalog.on('close@market:catalog-list', '.catalog-list', function () {
                    self.close();
                });
    
                var $columns = $catalog.find('.catalog-extend__columns-container');
    
                $columns.on('mouseenter', function () {
                    var category_id = $(this).find('.catalog-extend__columns_active').data('category_id');
    
                    $catalog.find('.catalog-list').each(function () {
                        var list = CatalogList($(this));
                        list.openCategory(category_id);
                    });
                });
    
                $columns.on('mouseleave', function () {
                    $catalog.find('.catalog-list').each(function () {
                        var list = CatalogList($(this));
                        list.leave();
                    });
                });
    
                $catalog.onAnimationEnd('.catalog-extend__columns-container', function () {
                    $catalog.removeClass('catalog-extend_animated');
                });
            },
    
            openCategory: function (category_id) {
                var $all_columns = $catalog.find('.catalog-extend__columns');
    
                var $columns = $catalog.find('.catalog-extend__columns').filter(function () {
                    return $(this).data('category_id') === category_id;
                });
    
                if ($columns.length === 0) {
                    self.close();
    
                    return;
                }
    
                $all_columns.removeClass('catalog-extend__columns_active');
                self.open();
                $columns.addClass('catalog-extend__columns_active');
            }
        };
    
        $.extend(self, {
            isOpen: function () {
                return $catalog.hasClass('catalog-extend_open');
            },
    
            open: function () {
                if (self.isOpen()) {
                    return;
                }
    
                $catalog.addClass('catalog-extend_open catalog-extend_animated');
                $catalog.trigger('open@market:catalog-extend');
            },
    
            close: function () {
                if (!self.isOpen()) {
                    return;
                }
    
                $catalog.removeClass('catalog-extend_open');
                $catalog.addClass('catalog-extend_animated');
                $catalog.trigger('close@market:catalog-extend');
            }
        });
    
        _private.initEventListeners();
    });
    
    var CatalogTree = market.CatalogTree = ComponentRegistry.register(function ($context) {
        return $context.select('.catalog-tree');
    }, function ($catalog, self) {
        var _private = {
            request_id: null,
            timeout_id: null,
            initEventListeners: function () {
                $catalog.children('.catalog-list').on('open_category@market:catalog-list', function () {
                    cancelAnimationFrame(_private.request_id);
                    clearTimeout(_private.timeout_id);
                    self.open();
                });
    
                $catalog.children('.catalog-list').on('close@market:catalog-list', function () {
                    _private.request_id = requestAnimationFrame(function () {
                        self.close();
                    });
                });
    
                $catalog.children('.catalog-list').on('close_category@market:catalog-list', function () {
                    _private.timeout_id = setTimeout(function () {
                        self.close();
                    }, 500);
                });
            }
        };
    
        $.extend(self, {
            isOpen: function () {
                return $catalog.hasClass('catalog-tree_open');
            },
    
            open: function () {
                if (self.isOpen()) {
                    return;
                }
    
                $catalog.addClass('catalog-tree_open');
                $catalog.trigger('open@market:catalog-tree');
            },
    
            close: function () {
                if (!self.isOpen()) {
                    return;
                }
    
                $catalog.removeClass('catalog-tree_open');
    
                $catalog.trigger('close@market:catalog-tree');
            }
        });
    
        _private.initEventListeners();
    });
    
    var CatalogList = market.CatalogList = ComponentRegistry.register(function ($context) {
        return $context.select('.catalog-list');
    }, function ($list, self) {
        var _private = {
            leave_timeout_id: null,
    
            initEventListeners: function () {
                var $items = $list.children('.catalog-list__item');
    
                $items.on('mouseenter', function () {
                    var $item = $(this);
                    self.openCategory($item.data('category_id'));
                });
    
                $items.on('open_category@market:catalog-list', function (e) {
                    e.stopPropagation();
                });
    
                $items.on('close_category@market:catalog-list', function (e) {
                    e.stopPropagation();
                });
    
                $items.on('close@market:catalog-list', function (e) {
                    e.stopPropagation();
                });
    
                $list.on('mouseleave', function () {
                    self.leave();
                });
    
                $(document).on('open@market:catalog-dropdown', '.catalog-dropdown', function () {
                    if ($list.data('show_first')) {
                        self.openCategory($list.children('.catalog-list__item:first-child').data('category_id'));
                    }
                });
    
                $items.find('> .catalog-list__children-container')
                    .cssAnimation('catalog-list__children-container_animated');
            }
        };
    
        $.extend(self, {
            openCategory: function (category_id) {
                clearTimeout(_private.leave_timeout_id);
    
                var $items = $list.children('.catalog-list__item');
    
                var $item = $items.filter(function () {
                    return $(this).data('category_id') === category_id;
                });
    
                var $active_items = $list.find('> .catalog-list__item_active').not($item);
                $active_items.removeClass('catalog-list__item_active');
                $active_items.find('> .catalog-list__children-container').runCssAnimation('catalog-list__children-container_close-animation');
                $active_items.filter('.catalog-list__item_has-children').each(function () {
                    $list.trigger('close_category@market:catalog-list', $(this).data('category_id'));
                });
    
                if ($item.hasClass('catalog-list__item_active')) {
                    return;
                }
    
                $item.addClass('catalog-list__item_active');
    
                if (!$item.hasClass('catalog-list__item_has-children')) {
                    var $category_children = $('.catalog-extend_open');
                    $category_children.removeClass('catalog-extend_open');
    
                    return;
                }
    
                $item.find('> .catalog-list__children-container').runCssAnimation('catalog-list__children-container_open-animation');
    
                $list.trigger('open_category@market:catalog-list', category_id);
            },
    
            leave: function () {
                clearTimeout(_private.leave_timeout_id);
    
                _private.leave_timeout_id = setTimeout(function () {
                    self.close();
                }, config['commons']['catalog_onleave_timeout']);
            },
    
            close: function () {
                clearTimeout(_private.leave_timeout_id);
    
                var $items = $list.children('.catalog-list__item');
                var $active_items = $items.filter('.catalog-list__item_active');
                $active_items.removeClass('catalog-list__item_active');
                $active_items.find('> .catalog-list__children-container').runCssAnimation('catalog-list__children-container_close-animation');
                $list.trigger('close@market:catalog-list');
            }
        });
    
        _private.initEventListeners();
    });
    
    var SidebarCatalog = market.SidebarCatalog = ComponentRegistry.register(function ($context) {
        return $context.select('.sidebar-catalog');
    }, function ($catalog, self) {
        var _private = {
            initEventListeners: function () {
                $catalog.on('open@market:catalog-extend', '.catalog-extend', function () {
                    self.open();
                });
    
                $catalog.on('close@market:catalog-extend', '.catalog-extend', function () {
                    self.close();
                });
    
                $catalog.on('open@market:catalog-tree', '.catalog-tree', function () {
                    self.open();
                });
    
                $catalog.on('close@market:catalog-tree', '.catalog-tree', function () {
                    self.close();
                });
                $catalog.cssAnimation('sidebar-catalog_animated');
            },
    
            isOpen: function () {
                return $catalog.hasClass('sidebar-catalog_open');
            }
        };
    
        $.extend(self, {
            open: function () {
                if (_private.isOpen()) {
                    return;
                }
    
                $catalog.addClass('sidebar-catalog_open');
                $catalog.offset();
                $catalog.trigger('open@market:sidebar-catalog');
            },
    
            close: function () {
                if (!_private.isOpen()) {
                    return;
                }
    
                $catalog.find('.catalog-extend').each(function () {
                    var catalog = CatalogExtend($(this));
                    catalog.close();
                });
    
                $catalog.find('.catalog-tree').each(function () {
                    var catalog = CatalogTree($(this));
                    catalog.close();
                });
    
                $catalog.removeClass('sidebar-catalog_open');
                $catalog.runCssAnimation('sidebar-catalog_close-animation');
                $catalog.trigger('close@market:sidebar-catalog');
            }
        });
    
        _private.initEventListeners();
    });
    
    var HeaderBottomBarAltSearch = market.HeaderBottomBarAltSearch = ComponentRegistry.register(function ($context) {
        return $context.select('.header-bottom-bar-alt-search');
    }, function ($search, self) {
        var _private = {
            autocomplete: $search.data('autocomplete'),
    
            initAutocomplete: function () {
                if (!_private.autocomplete) {
                    return;
                }
    
                var $input = $search.find('.header-bottom-bar-alt-search__input');
    
                $input.searchAutocomplete({
                    source: function (request, returnResponse) {
                        $.ajax({
                            url: config.shop.search_url,
                            data: {
                                query: request.term,
                                json: '1'
                            },
                            dataType: 'json'
                        }).then(function (response) {
                            response.products.forEach(function (product) {
                                product.label = product.name;
                            });
    
                            returnResponse(response.products.slice(0, 5));
                        });
                    },
                    select: function (e, ui) {
                        e.preventDefault();
    
                        window.location = ui.item.url;
                    }
                });
            }
        };
    
        $.extend(self, {
            initEventListeners: function () {
                $search.find('.header-bottom-bar-alt-search__button').on('click', function (e) {
                    if ($search.hasClass('header-bottom-bar-alt-search_open')) {
                        $search.find('.searchpro__field-button').trigger('click');
    
                        return;
                    }
    
                    e.preventDefault();
                    $search.addClass('header-bottom-bar-alt-search_open');
                    $search.runCssAnimation('header-bottom-bar-alt-search_open-animation');
    
                    setTimeout(function () {
                        $search.find('.header-bottom-bar-alt-search__input').trigger('focus');
                    }, 200);
                });
    
                $search.cssAnimation('header-bottom-bar-alt-search_animated');
    
                $search.find('.header-bottom-bar-alt-search__close-button').on('click', function () {
                    $search.removeClass('header-bottom-bar-alt-search_open');
                    $search.runCssAnimation('header-bottom-bar-alt-search_close-animation');
                });
            }
        });
    
        _private.initAutocomplete();
    
        self.initEventListeners();
    });
    
    var SearchProField = market.SearchProField = ComponentRegistry.register(function ($context) {
        return $context.select('.js-searchpro__field');
    }, function ($field, self) {
        $.extend(self, {
            insertData: function () {
                if (config['shop']['search_query']) {
                    var $input = $field.find('input.searchpro__field-input');
                    $input.val(config['shop']['search_query']);
                }
    
                if (config['shop']['path'].length > 0) {
                    config['shop']['path'].forEach(function (item) {
                        var $category = $field.find('li.js-searchpro__field-category[data-id="' + item + '"]');
    
                        if ($category.length) {
                            $category.trigger('click');
    
                            return false;
                        }
                    });
                }
            }
        });
        self.insertData();
    });
    
    var HeaderMiddleBarShopCart = market.HeaderMiddleBarShopCart = ComponentRegistry.register(function ($context) {
        return $context.select('.header-middle-bar-shop-cart');
    }, function ($cart, self) {
        $.extend(self, {
            initEventListeners: function () {
                $(document).on('shop_cart_add@market:global', function (e, response) {
                    if (response.status !== 'ok') {
                        return;
                    }
    
                    self.changeCount(response.data.count);
                    self.changeTotal(response.data.total);
                });
    
                $(document).on('shop_cart_update@market:global', function (e, response) {
                    self.changeCount(response.data.count);
                    self.changeTotal(response.data.total);
                });
    
                $(document).on('shop_cart_delete@market:global', function (e, response) {
                    self.changeCount(response.data.count);
                    self.changeTotal(response.data.total);
                });
    
                $(document).on('shop_cart_clear@market:global', function () {
                    self.changeCount(0);
                    self.changeTotal(0);
                });
            },
            changeCount: function (count) {
                $cart.find('.cart-counter').text(count);
    
                $cart.toggleClass('header-middle-bar-shop-cart_empty', count === 0);
            },
            changeTotal: function (total) {
                $cart.find('.header-middle-bar-shop-cart__total').html(total);
            }
        });
    
        self.initEventListeners();
    });
    
    market.HeaderFloating = ComponentRegistry.register(function ($context) {
        return $context.select('.header-floating');
    }, function ($header_floating) {
        let _private = {
            height: $header_floating.height(),
            startY: $('.index__header').height(),
            $catalog: null,
            catalogType: null,
            init: function () {
                $header_floating.toggleClass('header-floating_visible', window.scrollY > this.startY);
                _private.$catalog = $('.catalog-extend_fixed-width').get(0);
    
                if (_private.$catalog !== null) {
                    _private.catalogType = 'extend';
                }
    
                if (_private.$catalog === undefined) {
                    _private.$catalog = $('.catalog-tree_fixed-width').get(0);
                    _private.catalogType = 'tree';
                }
    
                if (_private.$catalog === undefined) {
                    _private.$catalog = $('.catalog-flat').get(0);
                    _private.catalogType = 'flat';
                }
    
                let $catalogClone = $(_private.$catalog).clone();
                $catalogClone.css('max-height', 'calc(100vh - ' + this.height + 'px)');
                const catalogDropdown = $header_floating.find('.header-floating__dropdown .catalog-dropdown');
                catalogDropdown.append($catalogClone);
                Update(catalogDropdown.children());
            },
            initGlobalEventsListeners: function () {
                let self = this;
                let $dropdownBtn = $header_floating.find('.header-floating__dropdown-btn');
    
                $(window).on('scroll', function () {
                    $header_floating.toggleClass('header-floating_visible', window.scrollY > self.startY);
                    let dropdown = CatalogDropdown($header_floating.find('.catalog-dropdown'));
    
                    if (_private.catalogType === 'tree' || _private.catalogType === 'extend') {
                        if (dropdown.isOpen()) {
                            dropdown.close(true);
                        }
                    }
                });
    
                $(document).on('click', function (e) {
                    if ($(e.target).closest($dropdownBtn).length !== 0) {
                        return;
                    }
    
                    $header_floating.find('.catalog-dropdown').each(function () {
                        let dropdown = CatalogDropdown($(this));
                        dropdown.close();
                        _private.unlockPage();
                        $dropdownBtn.find('.burger-icon').removeClass('burger-icon_active');
                    });
                });
            },
    
            initEventListeners: function () {
                let $dropdownBtn = $header_floating.find('.header-floating__dropdown-btn');
                let $catalogDropdown = $header_floating.find('.catalog-dropdown');
                let $dropdownBar = $header_floating.find('.header-dropdown-bar');
                let trigger_type = $dropdownBtn.data('trigger_type');
    
                if (trigger_type === 'click') {
                    $dropdownBtn.on('click', function () {
                        $catalogDropdown.each(function () {
                            let dropdown = CatalogDropdown($(this));
    
                            if (dropdown.isOpen()) {
                                dropdown.close(true);
                                _private.unlockPage();
                                $dropdownBtn.find('.burger-icon').removeClass('burger-icon_active');
                            } else {
                                dropdown.open();
                                _private.lockPage();
                                $dropdownBtn.find('.burger-icon').addClass('burger-icon_active');
                            }
                        });
                    });
                } else if (trigger_type === 'hover') {
                    $dropdownBtn.on('mouseenter', function () {
                        $catalogDropdown.each(function () {
                            let dropdown = CatalogDropdown($(this));
                            dropdown.open();
                            _private.lockPage();
                            /* $dropdownBtn.find('.burger-icon').addClass('burger-icon_active'); */
                        });
                    });
    
                    /*
                    $dropdownBtn.on('mouseleave', function () {
                            $catalogDropdown.each(function () {
                                    let dropdown = CatalogDropdown($(this));
                                    dropdown.leave();
                                    $dropdownBtn.find('.burger-icon').removeClass('burger-icon_active');
                            });
                    })
                     */
                }
    
                if (
                    _private.catalogType === 'tree' || _private.catalogType === 'extend'
                    || (trigger_type === 'hover' && _private.catalogType === 'flat')
                ) {
                    $dropdownBar.on('mouseleave', function () {
                        $catalogDropdown.each(function () {
                            let dropdown = CatalogDropdown($(this));
                            dropdown.leave();
                            _private.unlockPage();
                            $dropdownBtn.find('.burger-icon').removeClass('burger-icon_active');
                        });
                    });
                }
            },
    
            lockPage: function () {
                if (_private.catalogType === 'flat') {
                    ScrollLockUtil.lockPage();
                }
            },
    
            unlockPage: function () {
                if (_private.catalogType === 'flat') {
                    ScrollLockUtil.unlockPage();
                }
            }
        };
    
        if (ResponsiveUtil.isDesktopMin()) {
            _private.init();
            _private.initGlobalEventsListeners();
            _private.initEventListeners();
        }
    });
    
    var InputSearch = market.InputSearch = ComponentRegistry.register(function ($context) {
        return $context.select('.input-search');
    }, function ($search_input) {
        var _private = {
            autocomplete: $search_input.data('autocomplete'),
    
            initAutocomplete: function () {
                if (!_private.autocomplete) {
                    return;
                }
    
                var $input = $search_input.find('.input-search__input');
    
                $input.searchAutocomplete({
                    source: function (request, returnResponse) {
                        $.ajax({
                            url: config.shop.search_url,
                            data: {
                                query: request.term,
                                json: '1'
                            },
                            dataType: 'json'
                        }).then(function (response) {
                            response.products.forEach(function (product) {
                                product.label = product.name;
                            });
    
                            returnResponse(response.products.slice(0, 5));
                        });
                    },
                    select: function (e, ui) {
                        e.preventDefault();
    
                        window.location = ui.item.url;
                    },
                    classes: {
                        'ui-autocomplete': 'autocomplete autocomplete_search'
                    },
                    appendTo: $('.header, .r-search-form')
                });
            }
        };
    
        _private.initAutocomplete();
    });
    
    var CartPopup = market.CartPopup = ComponentRegistry.register(function ($context) {
        return $context.select('.cart-popup');
    }, function ($popup, self) {
        var _private = {
            initScrollbar: function () {
                var $items_container = $popup.find('.cart-popup__items-container');
                var items_container = $items_container.get(0);
            },
            initEventListeners: function () {
                $popup.on('delete', '.cart-popup-item', function () {
                    self.refresh();
                });
    
                $popup.find('.cart-popup__close').on('click', function () {
                    $popup.trigger('close');
                });
            }
        };
    
        $.extend(self, {
            refresh: function () {
                $.ajax({
                    url: config.shop.cart_url + '?popup=1'
                }).then(function (response) {
                    var $response = $(response);
                    var $new_popup = $response.find('.cart-popup').add($response.filter('.cart-popup'));
                    $popup.replaceWith($new_popup);
                    Update($new_popup.parent());
                });
            }
        });
    
        _private.initScrollbar();
        _private.initEventListeners();
    });
    
    var CartPopupItem = market.CartPopupItem = ComponentRegistry.register(function ($context) {
        return $context.select('.cart-popup-item');
    }, function ($item) {
        var _private = {
            item_id: $item.data('item_id'),
            initEventListeners: function () {
                $item.find('.cart-popup-item__delete-button').on('click', function () {
                    CartUtil.deleteItem(_private.item_id).then(function () {
                        $item.trigger('delete');
                    });
                });
            }
        };
    
        _private.initEventListeners();
    });
    
    var CartPopupContainer = market.CartPopupContainer = ComponentRegistry.register(function ($context) {
        return $context.select('.cart-popup-container');
    }, function ($container, self) {
        var _private = {
            loaded: false,
            isOpen: function () {
                return $container.hasClass('cart-popup-container_open');
            },
            initEventListeners: function () {
                var leave_timeout_id = null;
                var enter_timeout_id = null;
    
                $container.on('mouseenter', function () {
                    clearTimeout(leave_timeout_id);
                    clearTimeout(enter_timeout_id);
    
                    if (_private.loaded) {
                        return;
                    }
    
                    enter_timeout_id = setTimeout(function () {
                        self.load().then(function () {
                            clearTimeout(leave_timeout_id);
                            clearTimeout(enter_timeout_id);
    
                            self.open();
                        });
                    }, config['commons']['cart_onenter_timeout']);
                });
    
                $container.on('mouseleave', function () {
                    clearTimeout(leave_timeout_id);
                    clearTimeout(enter_timeout_id);
    
                    leave_timeout_id = setTimeout(function () {
                        self.close();
                    }, config['commons']['cart_onleave_timeout']);
                });
    
                $container.on('close', '.cart-popup', function () {
                    self.close();
                });
    
                $(document).on('click', _private.handleDocumentClick);
            },
            handleDocumentClick: function (e) {
                if ($(e.target).closest($container).length === 0) {
                    self.close();
                }
            }
        };
    
        $.extend(self, {
            destruct: function () {
                $(document).off('click', _private.handleDocumentClick);
            },
            load: function () {
                _private.loaded = true;
    
                var request = $.ajax({
                    url: config.shop.cart_url + '?popup=1'
                });
    
                request.then(function (response) {
                    var $response = $(response);
                    var $popup = $response.select('.cart-popup');
                    $container.find('.cart-popup-container__popup').html($popup);
                    Update($container);
                });
    
                return request;
            },
            open: function () {
                $container.removeClass('cart-popup-container_close');
                $container.offset();
                $container.addClass('cart-popup-container_open');
            },
            close: function () {
                _private.loaded = false;
                $container.removeClass('cart-popup-container_open');
                $container.offset();
                $container.addClass('cart-popup-container_close');
            }
        });
    
        _private.initEventListeners();
    });
    
    var BrandAlphabet = market.BrandAlphabet = ComponentRegistry.register(function ($context) {
        return $context.select('.brand-alphabet');
    }, function ($block, self) {
        var _private = {
            initEventListeners: function () {
                var $lettersButtons = _private.getLettersButtons();
                $lettersButtons.on('mouseenter', function () {
                    var $button = $(this);
    
                    $lettersButtons.removeClass('brand-alphabet-list__item_active');
                    $button.addClass('brand-alphabet-list__item_active');
                    $block.addClass('brand-alphabet_opened');
                    $block.trigger('open@market:brand-alphabet');
                });
    
                $lettersButtons.on('mouseleave', function () {
                    self.close();
                    $block.trigger('close@market:brand-alphabet');
                });
            },
            getLettersButtons: function () {
                return $block.find('.brand-alphabet-list__item_letter');
            }
        };
    
        $.extend(self, {
            close: function () {
                var $lettersButtons = _private.getLettersButtons();
                $block.removeClass('brand-alphabet_opened');
                $lettersButtons.removeClass('brand-alphabet-list__item_active');
            }
        });
        _private.initEventListeners();
    });
    
    var BrandsDropdown = market.BrandsDropdown = ComponentRegistry.register(function ($context) {
        return $context.select('.brands-dropdown');
    }, function ($dropdown, self) {
        var _private = {
            enter_timeout_id: null,
            leave_timeout_id: null,
    
            initEventListeners: function () {
                $dropdown.on('mouseenter', function () {
                    clearTimeout(_private.leave_timeout_id);
                });
    
                $dropdown.on('mouseleave', function () {
                    self.leave();
                });
    
                $dropdown.onAnimationEnd(function () {
                    $dropdown.removeClass('brands-dropdown_animated');
                });
            }
        };
    
        $.extend(self, {
            isOpen: function () {
                return $dropdown.hasClass('brands-dropdown_open');
            },
    
            open: function () {
                clearTimeout(_private.leave_timeout_id);
                clearTimeout(_private.enter_timeout_id);
    
                if (self.isOpen()) {
                    return;
                }
    
                _private.enter_timeout_id = setTimeout(function () {
                    $dropdown.addClass('brands-dropdown_open brands-dropdown_animated');
                    $dropdown.trigger('open@market:brands-dropdown');
                }, config['commons']['catalog_onenter_timeout']);
            },
    
            leave: function () {
                clearTimeout(_private.leave_timeout_id);
                clearTimeout(_private.enter_timeout_id);
    
                _private.leave_timeout_id = setTimeout(function () {
                    self.close();
                }, config['commons']['catalog_onleave_timeout']);
            },
    
            close: function () {
                clearTimeout(_private.leave_timeout_id);
    
                if (!self.isOpen()) {
                    return;
                }
    
                $dropdown.removeClass('brands-dropdown_open');
                $dropdown.addClass('brands-dropdown_animated');
                $dropdown.trigger('close@market:brands-dropdown');
            }
        });
    
        _private.initEventListeners();
    });
    
    var BrandsDropdownButton = market.BrandsDropdownButton = ComponentRegistry.register(function ($context) {
        return $context.select('.brands-dropdown-button');
    }, function ($button, self) {
        var _private = {
            initEventListeners: function () {
                $button.on('mouseenter', function () {
                    $('.brands-dropdown').each(function () {
                        var dropdown = BrandsDropdown($(this));
                        dropdown.open();
                    });
                });
    
                $button.on('mouseleave', function () {
                    $('.brands-dropdown').each(function () {
                        var dropdown = BrandsDropdown($(this));
                        dropdown.leave();
                    });
                });
            },
    
            initGlobalEventListeners: function () {
                $(document).on('open@market:brands-dropdown', '.brands-dropdown', _private.handleOpen);
                $(document).on('close@market:brands-dropdown', '.brands-dropdown', _private.handleClose);
            },
    
            destroyGlobalEventListeners: function () {
                $(document).off('open@market:brands-dropdown', '.brands-dropdown', _private.handleOpen);
                $(document).off('close@market:brands-dropdown', '.brands-dropdown', _private.handleClose);
            },
    
            handleOpen: function () {
                $button.addClass('button_active');
                $button.trigger('open_dropdown@market:brands-dropdown-button');
            },
    
            handleClose: function () {
                $button.removeClass('button_active');
                $button.trigger('close_dropdown@market:brands-dropdown-button');
            }
        };
    
        $.extend(self, {
            closeDropdown: function () {
                $('.brands-dropdown').each(function () {
                    var dropdown = BrandsDropdown($(this));
                    dropdown.close();
                });
            }
        });
    
        _private.initEventListeners();
        _private.initGlobalEventListeners();
    
        return function () {
            _private.destroyGlobalEventListeners();
        };
    });
    
    market.HideLinksMenu = ComponentRegistry.register(function ($context) {
        return $context.select('.hide-links-menu');
    }, function ($menu) {
        var links = [];
        var $hiddenLinksBlock = null;
        var $more = $menu.find('.hide-links-menu__more');
        var menuId = $menu.data('more-id');
        var $moreList = $('.hide-links-menu-more-list[data-more-id="' + menuId + '"]');
    
        var _private = {
            isInited: false,
            hiddenLinksIndexes: [],
            initEventListeners: function () {
                $(window).on('resize', _private.toggleItems);
    
                $menu.on('mouseenter', _private.addHiddenItems);
    
                $(document).on('ajax_success@market:hidden_links_block', function (e, response) {
                    var $_hiddenLinksBlock = $(response).find('.hide-links-menu-more-list[data-more-id="' + menuId + '"]');
    
                    if ($_hiddenLinksBlock.length > 0) {
                        $hiddenLinksBlock = $_hiddenLinksBlock;
                    }
    
                    $menu.off('mouseenter', _private.addHiddenItems);
                });
            },
            init: function () {
                if ($moreList.length > 0) {
                    $menu.find('.hide-links-menu__item').each(function () {
                        var $item = $(this);
                        var itemRightPos = $item.position().left + $item.width();
                        var $itemLink = $item.find('.hide-links-menu__link');
    
                        links.push({
                            $item: $item,
                            pos: itemRightPos,
                            $itemHtml: $item.clone(),
                            linkAttrs: {
                                content: $itemLink.html(),
                                href: $itemLink.attr('href'),
                                target: $itemLink.attr('target')
                            }
                        });
                    });
    
                    _private.isInited = true;
    
                    if (links.length > 0) {
                        _private.toggleItems();
                        _private.initEventListeners();
                    }
                }
            },
            getHiddenBlockItems: function () {
                var promise = $.Deferred();
    
                if ($hiddenLinksBlock === null) {
                    if (_private.xhr) {
                        promise.reject();
    
                        return promise;
                    }
    
                    _private.xhr = $.ajax({
                        url: '/search/?get_ajax_blocks=hidden_links_block',
                        method: 'GET',
                        success: function (response) {
                            $(document).trigger('ajax_success@market:hidden_links_block', response);
    
                            promise.resolve();
                        },
                        error: function () {
                            InfoPanelUtil.showMessage('Error');
                            promise.reject();
                        },
                        finally: function () {
                            _private.xhr = null;
                        }
                    });
                } else {
                    promise.resolve();
                }
    
                return promise;
            },
            addHiddenItems: function () {
                if (_private.hiddenLinksIndexes.length > 0) {
                    _private.getHiddenBlockItems().then(function () {
                        if ($hiddenLinksBlock !== null) {
                            var $hiddenBlockLinks = $hiddenLinksBlock.children();
    
                            var $hiddenLinksTmpContainer = $('<div></div>');
    
                            _private.hiddenLinksIndexes.forEach(function (index) {
                                var $hiddenLink = $($hiddenBlockLinks[index]).clone();
                                $hiddenLink.addClass('hide-links-menu__more-item');
                                $hiddenLinksTmpContainer.append($hiddenLink);
                            });
    
                            $moreList.prepend($hiddenLinksTmpContainer.html());
                            $hiddenLinksTmpContainer.remove();
                        }
                    });
                }
            },
            toggleItems: function () {
                if (_private.isInited) {
                    $menu.removeClass('hide-links-menu_inited');
                    var menuWidth = $menu.outerWidth();
                    var moreWidth = $more.outerWidth();
    
                    var $moreContainer = $more.closest('.hide-links-menu__more-container');
                    $moreList = $('.hide-links-menu-more-list[data-more-id="' + menuId + '"]');
    
                    var linksWidth = 0;
                    var isOverflow = false;
    
                    $moreList.find('.hide-links-menu__more-item').remove();
    
                    var moreListHasContent = $moreList.children().length > 0;
    
                    _private.hiddenLinksIndexes = [];
    
                    links.forEach(function (link, i) {
                        linksWidth = link.pos;
    
                        if ((!isOverflow && linksWidth > menuWidth) || isOverflow) {
                            if (!isOverflow) {
                                isOverflow = true;
                                var lastVisibleLinkIndex = i - 1;
                                var lastVisibleLink = links[lastVisibleLinkIndex];
    
                                if ((linksWidth - link.pos + moreWidth) > menuWidth) {
                                    lastVisibleLink.$item.remove();
                                    _private.hiddenLinksIndexes.push(lastVisibleLinkIndex);
                                }
                            }
    
                            link.$item.remove();
                            _private.hiddenLinksIndexes.push(i);
                        } else if (!$menu.find('.hide-links-menu__item')[i]) {
                            $moreContainer.before(link.$itemHtml);
                        }
                    });
    
                    if ($hiddenLinksBlock !== null) {
                        _private.addHiddenItems();
                    }
    
                    $moreContainer.toggleClass('hide-links-menu__more-container_hide', _private.hiddenLinksIndexes.length === 0 && !moreListHasContent);
    
                    $menu.addClass('hide-links-menu_inited');
                    Update($menu);
                    Update($moreList);
                }
            }
        };
    
        _private.init();
    });
    
    market.HeaderTopBarLinks = ComponentRegistry.register(function ($context) {
        return $context.select('.header-top-bar-links');
    }, function ($menu) {
        let _private = {
            container: null,
            headerContacts: null,
            headerTopBarContainer: null,
            linksList: [],
            moreBtn: null,
            lastItem: null,
            lastItemKey: null,
            lastHiddenItem: null,
            lastHiddenItemKey: null,
            dropdownItems: [],
            countHiddenItems: null,
            init: function () {
                _private.container = _private.getContainer();
                _private.linksList = _private.getLinksList();
                _private.headerContacts = $(document).find('.header-top-bar__container-contacts');
                _private.headerTopBarContainer = $(document).find('.header-top-bar__container');
                _private.moreBtn = $(_private.linksList[_private.linksList.length - 1]);
                _private.dropdownItems = _private.moreBtn.find('.dropdown-links');
                _private.lastItem = _private.linksList[_private.linksList.length - 2];
                _private.lastItemKey = _private.countHiddenItems = $(_private.lastItem).data().key;
    
                _private.compareContainer();
                _private.initEventListeners();
            },
            initEventListeners: function () {
                $(window).on('resize', _private.compareContainer);
            },
            getContainer: function () {
                return $menu.parent();
            },
            getLinksList: function () {
                return $menu.find('.header-top-bar-links__item');
            },
            compareContainer: function () {
                if ($menu.width() > 0 && $menu.width() >= _private.container.width()) {
                    _private.hideItems();
                } else {
                    _private.showItems();
                }
            },
            hideItems: function () {
                $(_private.lastItem).hide();
                _private.dropdownItems.find(`.dropdown-links__item[data-key=${_private.lastItemKey}]`).show();
                _private.changeLastItem();
                _private.compareContainer();
            },
            showItems: function () {
                $(_private.lastHiddenItem).show();
    
                if (_private.lastItemKey <= _private.countHiddenItems && $menu.width() + _private.headerContacts.width() < _private.headerTopBarContainer.width()) {
                    _private.changeLastItem(true);
                    _private.dropdownItems.find(`.dropdown-links__item[data-key=${_private.lastHiddenItemKey}]`).hide();
                    _private.showItems();
    
                    if (_private.lastItemKey > _private.countHiddenItems) {
                        _private.lastItemKey = _private.countHiddenItems;
    
                        if (_private.dropdownItems.find(`.dropdown-links__item`).length - 1 === _private.countHiddenItems) {
                            _private.moreBtn.hide();
                        }
                    }
                } else {
                    $(_private.lastHiddenItem).hide();
                }
            },
            changeLastItem: function (isIncrease = false) {
                if (_private.lastItemKey >= 0 && _private.lastItemKey <= _private.countHiddenItems) {
                    if (isIncrease) {
                        _private.increaseLastItemKey();
                    } else {
                        _private.decreaseLastItemKey();
                    }
                }
            },
            increaseLastItemKey: function () {
                _private.lastItemKey++;
    
                if (_private.lastItemKey > _private.countHiddenItems) {
                    _private.lastHiddenItem = _private.lastHiddenItemKey = null;
                } else {
                    _private.lastItem = $menu.find(`.header-top-bar-links__item[data-key=${_private.lastItemKey}]`);
                    _private.setLastHiddenItem();
                }
            },
            decreaseLastItemKey: function () {
                if (_private.lastItemKey === _private.countHiddenItems) {
                    _private.moreBtn.show();
                }
    
                _private.setLastHiddenItem();
                _private.lastItemKey--;
                _private.lastItem = $menu.find(`.header-top-bar-links__item[data-key=${_private.lastItemKey}]`);
            },
            setLastHiddenItem: function () {
                _private.lastHiddenItem = _private.lastItem;
                _private.lastHiddenItemKey = _private.lastItemKey;
            }
        };
    
        _private.init();
    });
    
    market.CitySelectDecorator = (function () {
        if (typeof shop_cityselect !== 'undefined') {
            var openPopup = function () {
                if (typeof window.shop_cityselect !== 'undefined') {
                    ModalUtil.openAjax(window.shop_cityselect.url + 'shop_cityselect/change_city', function ($response) {
                        return $response.select('#cityselect__change');
                    }, {
                        classes: 'cityselect-modal',
                        beforeOpen: function ($modal) {
                            var $content = $modal.find('#cityselect__change');
    
                            if ($content.length > 0) {
                                $content.addClass('content-decorator');
                                market.ContentDecorator($content);
                            }
    
                            $modal.find('.i-cityselect__set_city ').on('cityselect__set_city', function (e, cityData) {
                                ModalUtil.close();
                            });
                        },
                        onOpen: function () {
                            window.shop_cityselect.initChangeInput();
                        }
                    });
                }
            };
    
            var decorateNotifierOnMobile = function () {
                var $notifier = $('.b-cityselect__notifier');
    
                if ($notifier.length > 0) {
                    var $header = $('.r-header');
    
                    $notifier.appendTo($header);
                }
            };
    
            var handleClickOnButton = function (e) {
                e.preventDefault();
                e.stopImmediatePropagation();
                openPopup();
    
                return false;
            };
    
            $('.i-cityselect__city_change').on('click', handleClickOnButton);
            $('.js-dp-city-select').on('click', handleClickOnButton);
    
            if (market.ResponsiveUtil.isTabletMax()) {
                decorateNotifierOnMobile();
            }
    
            return {
                openPopup: openPopup,
                decorateNotifierOnMobile: decorateNotifierOnMobile
            };
        }
    })();
    

    /* Responsive scripts */
    market.ResponsiveHeaderMenuAdapter = ComponentRegistry.register(function ($context) {
        return $context.select('.js-r-header-menu');
    }, function ($menu, self) {
        var $dropdown = $menu.find('.js-r-header-menu__dropdown'),
            dropdown_open_class = 'r-header-menu__dropdown_open',
            $menu_toggle = $menu.find('.js-r-header-menu__tiggle'),
            $close = $menu.find('.js-r-header-menu__dropdown-close');
    
        $.extend(self, {
            initEventListeners: function () {
                $menu_toggle.on('click', function (e) {
                    e.preventDefault();
                    var is_catalog = $menu.hasClass('js-r-header-menu_catalog');
    
                    if ($dropdown.hasClass(dropdown_open_class)) {
                        self.close();
                    } else {
                        if (is_catalog) {
                            $menu.find('.js-r-header-catalog__toggle').trigger('click');
                        }
    
                        self.open();
                    }
                });
                $close.on('click', function () {
                    $menu.find('.js-r-subdropdown__header-close').trigger('click');
                    self.close();
                });
                $(document).on('click', function (e) {
                    if (market.MatchMedia('only screen and (max-width: 1023px)') && $(e.target).closest('.header-overlay').length > 0) {
                        self.close();
                    }
                });
            },
            open: function () {
                if (this.isOpen()) {
                    return;
                }
    
                $menu.trigger('open@market:r-header-menu');
                $dropdown.show();
                window.setTimeout(function () {
                    $dropdown.addClass(dropdown_open_class);
                }, 50);
                ScrollLockUtil.lockPage();
            },
            close: function () {
                if (!this.isOpen()) {
                    return;
                }
    
                $menu.trigger('close@market:r-header-menu');
                $dropdown.removeClass(dropdown_open_class);
                window.setTimeout(function () {
                    $dropdown.hide();
                }, 50);
                ScrollLockUtil.unlockPage();
            },
            isOpen: function () {
                return $dropdown.hasClass(dropdown_open_class);
            }
        });
    
        self.initEventListeners();
    });
    
    market.ResponsiveHeaderSearchAdapter = ComponentRegistry.register(function ($context) {
        return $context.select('.js-r-header__list-item-link_search-toggle');
    }, function ($search_toggle, self) {
        var $header = $search_toggle.closest('.js-r-header'),
            search_form_open_class = 'r-search-form_open';
    
        $.extend(self, {
            initEventListeners: function () {
                $search_toggle.on('click', function (e) {
                    e.preventDefault();
                    self.open();
                });
    
                $header.on('click', '.js-r-search-form__close', function () {
                    self.close();
                });
    
                $header.on('click', '.r-search-form__button', function () {
                    $header.find('.searchpro__field-button').trigger('click');
                });
            },
            getSearchForm: function () {
                var promise = $.Deferred();
    
                var $searchFrom = $header.find('.js-r-search-form');
    
                if ($searchFrom.length === 0) {
                    $searchFrom = $($search_toggle.data('search-form-tpl'));
    
                    $header.append($searchFrom);
                    $search_toggle.removeAttr('data-search-form-tpl');
                    Update($searchFrom);
                }
    
                return promise.resolve($searchFrom);
            },
            open: function () {
                self.getSearchForm().then(function ($searchFrom) {
                    $searchFrom.addClass(search_form_open_class);
    
                    $search_toggle.trigger('opened@market:js-r-header__list-item-link_search-toggle');
                });
            },
            close: function () {
                self.getSearchForm().then(function ($searchFrom) {
                    $searchFrom.removeClass(search_form_open_class);
    
                    $search_toggle.trigger('closed@market:js-r-header__list-item-link_search-toggle');
                });
            }
        });
    
        self.initEventListeners();
    });
    
    market.ResponsiveHeaderContacts = ComponentRegistry.register(function ($context) {
        return $context.select('.r-header-contacts');
    }, function ($contacts, self) {
        var $header = $contacts.closest('.r-header'),
            $toggleBtn = $header.find('.r-header-contacts-btn'),
            open_class = 'r-header-contacts_open',
            header_open_class = 'r-header_over',
            btn_open_class = 'r-header-contacts-btn_active';
    
        var _private = {
            initEventListeners: function () {
                $toggleBtn.on('click', function () {
                    _private.toggle();
                });
    
                $header.on('open@market:r-header-menu', '.js-r-header-menu', _private.close);
            },
            toggle: function () {
                if (_private.isOpen()) {
                    _private.close();
                } else {
                    _private.open();
                }
            },
            open: function () {
                if (_private.isOpen()) {
                    return;
                }
    
                $contacts.trigger('open@market:r-header-contacts');
                $contacts.show();
                $header.addClass(header_open_class);
                $toggleBtn.addClass(btn_open_class);
                window.setTimeout(function () {
                    $contacts.addClass(open_class);
                }, 50);
                ScrollLockUtil.lockPage();
            },
            close: function () {
                if (!_private.isOpen()) {
                    return;
                }
    
                $contacts.trigger('close@market:r-header-contacts');
                $contacts.removeClass(open_class);
                window.setTimeout(function () {
                    $contacts.hide();
                    $toggleBtn.removeClass(btn_open_class);
                    $header.removeClass(header_open_class);
                }, 50);
                ScrollLockUtil.unlockPage();
            },
            isOpen: function () {
                return $contacts.hasClass(open_class);
            }
        };
    
        _private.initEventListeners();
    });
    
    market.ResponsiveHeaderCatalogAdapter = ComponentRegistry.register(function ($context) {
        return $context.select('.js-r-header-catalog__toggle');
    }, function ($catalog_toggle, self) {
        var $dropdown = $catalog_toggle.closest('.js-r-header-menu__dropdown'),
            open_class = 'r-subdropdown_open',
            backId = 0;
    
        $.extend(self, {
            categories: {
                0: {
                    name: 'Каталог товаров'
                }
            },
            initEventListeners: function () {
                $catalog_toggle.on('click', function (e) {
                    e.preventDefault();
                    self.open();
                });
                $(document).on('click', '.js-r-subdropdown__header-close', function () {
                    self.close();
                });
                $(document).on('click', '.js-r-header-menu__catalog .list-rows__item', function (e) {
                    var $catalog_item = $(this),
                        itemUrl = $catalog_item.attr('href'),
                        itemName = $catalog_item.find('.list-rows__item-name').text(),
                        $list_item = $catalog_item.parent(),
                        $parentItem = self.getParentItem(),
                        $children = $catalog_item.next('ul'),
                        category_id = $children.data('category-id'),
                        parent_id = $catalog_item.closest('ul').data('category-id');
    
                    if ($children.length > 0) {
                        e.preventDefault();
                        self.setCategoryObject(category_id, parent_id, itemName);
                        backId = category_id;
                        self.toggleBackBtn();
                        $catalog_item.addClass('list-rows__item_opened');
                        $list_item.siblings().not($parentItem).hide();
                        self.getSubdropdown().addClass('r-subdropdown_category');
                        self.getSubdropdownTitle().text(itemName);
                        $parentItem.find('.list-rows__item').attr('href', itemUrl);
                    }
                });
                $(document).on('click', '.js-r-subdropdown__header-back', function () {
                    if (backId !== 0) {
                        var $catalog_item = self.getSubdropdown().find('.list-rows__item[data-category_id=' + backId + ']'),
                            $list_item = $catalog_item.parent(),
                            $parentItem = self.getParentItem();
    
                        backId = self.categories[backId]['parent'];
    
                        if (backId === 0) {
                            self.getSubdropdown().removeClass('r-subdropdown_category');
                            self.toggleBackBtn();
                        }
    
                        $catalog_item.removeClass('list-rows__item_opened');
                        $list_item.siblings().not($parentItem).show();
                        self.getSubdropdownTitle().text(self.categories[backId]['name']);
                    } else {
                        self.close();
                    }
    
                    return false;
                });
                $('.js-r-header-menu').on('close@market:r-header-menu', self.close);
            },
            setCategoryObject: function (id, parentId, itemName) {
                if (self.categories[id] === undefined) {
                    self.categories[id] = {
                        current: id,
                        parent: parentId,
                        name: itemName
                    };
                }
            },
            getSubdropdown: function () {
                return $dropdown.find('.js-r-header-menu__catalog');
            },
            getSubdropdownTitle: function () {
                return self.getSubdropdown().find('.r-subdropdown__header-title');
            },
            getParentItem: function () {
                return self.getSubdropdown().find('.r-header-menu__dropdown-list_parent');
            },
            catalogIsMain: function () {
                return self.getSubdropdown().hasClass('r-subdropdown_catalog');
            },
            toggleBackBtn: function (bool) {
                if (self.catalogIsMain()) {
                    if (backId !== 0 || bool) {
                        self.getSubdropdown().find('.r-subdropdown__header-back').removeClass('r-subdropdown__header-back_hide');
                    } else {
                        self.getSubdropdown().find('.r-subdropdown__header-back').addClass(
                            'r-subdropdown__header-back_hide');
                    }
                }
            },
            open: function () {
                $catalog_toggle.trigger('opened@market:js-r-header-menu__catalog');
                self.getSubdropdown().addClass(open_class);
                MetaThemeColorUtil.setColor('#fafafa');
            },
            close: function () {
                $catalog_toggle.trigger('closed@market:js-r-header-menu__catalog');
                self.getSubdropdown().removeClass(open_class);
                market.MetaThemeColorUtil.resetColor();
            }
        });
    
        self.initEventListeners();
    });
    
    market.ResponsiveHeaderBrandsAdapter = ComponentRegistry.register(function ($context) {
        return $context.select('.js-r-header-brands__toggle');
    }, function ($brands_toggle, self) {
        var $dropdown = $brands_toggle.closest('.js-r-header-menu__dropdown'),
            open_class = 'r-subdropdown_open';
    
        $.extend(self, {
            initEventListeners: function () {
                $brands_toggle.on('click', function (e) {
                    e.preventDefault();
                    self.open();
                });
                $(document).on('click', '.js-r-subdropdown__header-close', function () {
                    self.close();
                });
                $(document).on('click', '.js-r-subdropdown__header-back', function () {
                    self.close();
    
                    return false;
                });
            },
            getSubdropdown: function () {
                return $dropdown.find('.js-r-header-menu__brands');
            },
            open: function () {
                self.getSubdropdown().addClass(open_class);
                MetaThemeColorUtil.setColor('#fafafa');
            },
            close: function () {
                self.getSubdropdown().removeClass(open_class);
                market.MetaThemeColorUtil.resetColor();
            }
        });
    
        self.initEventListeners();
    });
    
    market.ResponsiveHeaderLinksAdapter = ComponentRegistry.register(function ($context) {
        return $context.select('.js-r-header-links__toggle');
    }, function ($links_toggle) {
        let _private = {
            dataId: $links_toggle.data('id'),
            $dropdown: $links_toggle.closest('.js-r-header-menu__dropdown'),
            open_class: 'r-subdropdown_open',
    
            initEventListeners: function () {
                $links_toggle.on('click', function (e) {
                    e.preventDefault();
                    _private.open();
                });
                $(document).on('click', '.js-r-subdropdown__header-close', function () {
                    _private.close();
                });
                $(document).on('click', '.js-r-subdropdown__header-back', function () {
                    _private.close();
    
                    return false;
                });
            },
            getSubdropdown: function () {
                return _private.$dropdown.find(`.js-r-header-menu__links[data-id="${_private.dataId}"]`);
            },
            open: function () {
                _private.getSubdropdown().addClass(_private.open_class);
                MetaThemeColorUtil.setColor('#fafafa');
            },
            close: function () {
                _private.getSubdropdown().removeClass(_private.open_class);
                market.MetaThemeColorUtil.resetColor();
            }
        };
    
        _private.initEventListeners();
    });
    
    market.FixedBar = ComponentRegistry.register(function ($context) {
        return $context.select('.fixed-bar');
    }, function ($fixedBar, self) {
        var $header = $('.r-header');
    
        var _private = {
            initCatalogBtn: function () {
                var $catalogBtn = $fixedBar.find('.fixed-bar__item_catalog');
                var $headerMenu = $header.find('.js-r-header-menu');
    
                if ($catalogBtn.length > 0 && $headerMenu.length > 0) {
                    var $headerMenuToggler = $headerMenu.find('.js-r-header-menu__tiggle');
                    var $headerCatalogToggler = $headerMenu.find('.js-r-header-catalog__toggle');
                    var $headerMenuCloseBtn = $headerMenu.find('.js-r-header-menu__dropdown-close');
                    var headerMenuIsCatalog = $headerMenu.hasClass('js-r-header-menu_catalog');
                    var ResponsiveHeaderCatalogAdapter = market.ResponsiveHeaderCatalogAdapter($headerCatalogToggler);
    
                    $catalogBtn.on('click', function () {
                        var isActive = $catalogBtn.hasClass('fixed-bar__item_active');
    
                        if (!isActive) {
                            if (!headerMenuIsCatalog) {
                                _private.decorateResponsiveMenu(true);
                            }
    
                            $headerMenuToggler.trigger('click');
                            ResponsiveHeaderCatalogAdapter.toggleBackBtn();
                        } else {
                            $headerMenuCloseBtn.trigger('click');
                        }
                    });
    
                    $headerCatalogToggler.on('opened@market:js-r-header-menu__catalog', function () {
                        $catalogBtn.addClass('fixed-bar__item_active');
                    });
    
                    $headerCatalogToggler.on('closed@market:js-r-header-menu__catalog', function () {
                        window.setTimeout(function () {
                            if (!headerMenuIsCatalog) {
                                ResponsiveHeaderCatalogAdapter.toggleBackBtn(true);
                                _private.decorateResponsiveMenu(false);
                            }
    
                            $catalogBtn.removeClass('fixed-bar__item_active');
                        }, 50);
                    });
                }
            },
            decorateResponsiveMenu: function (bool) {
                var $headerMenu = $header.find('.js-r-header-menu');
    
                $headerMenu.toggleClass('js-r-header-menu_catalog', bool);
                $headerMenu.find('.r-header-menu__dropdown').toggleClass('r-header-menu__dropdown_catalog', bool);
                $headerMenu.find('.r-subdropdown').toggleClass('r-subdropdown_catalog', bool);
            },
            initSearchBtn: function () {
                var $searchToggle = $header.find('.js-r-header__list-item-link_search-toggle');
    
                if ($searchToggle.length > 0) {
                    var $searchItem = $fixedBar.find('.fixed-bar__item_search');
                    var $searchBlock = $searchToggle;
                    var searchComponent = market.ResponsiveHeaderSearchAdapter($searchToggle);
    
                    $searchItem.on('click', function () {
                        var is_active = $(this).hasClass('fixed-bar__item_active');
    
                        if (is_active) {
                            searchComponent.close();
                        } else {
                            searchComponent.open();
                        }
                    });
    
                    $searchBlock.on('opened@market:js-r-header__list-item-link_search-toggle closed@market:js-r-header__list-item-link_search-toggle', function (e) {
                        var isOpened = e.type = 'opened@js-r-header__list-item-link_search-toggle';
    
                        $searchItem.toggleClass('fixed-bar__item_active', isOpened);
                    });
                }
            },
            initBtns: function () {
                _private.initCatalogBtn();
                _private.initSearchBtn();
            }
        };
    
        _private.initBtns();
    });
    
    market.ResponsiveSelectLinksAdapter = ComponentRegistry.register(function ($context) {
        return $context.select('select.js-r-select-links');
    }, function ($select_links, self) {
        $.extend(self, {
            initEventListeners: function () {
                $select_links.on('change', function (e) {
                    e.preventDefault();
                    var url = $(this).val();
    
                    if (url) {
                        window.location.href = url;
                    }
                });
            }
        });
    
        self.initEventListeners();
    });
    
    market.ResponsiveTabs = ComponentRegistry.register(function ($context) {
        return $context.select('.responsive-tabs');
    }, function ($container, self) {
        var _private = {
            initTabs: function () {
                var $tabs = $container.find('.responsive-tabs__tab-container');
    
                $tabs.each(function () {
                    var $tab = $(this);
                    var index = $tab.index();
    
                    if (window.location.hash === '#' + $tab.data('slug')) {
                        if (index !== 0) {
                            self.openTab(index);
                        }
    
                        if (!HistoryUtil.isReload()) {
                            _private.scrollTo(index);
                        }
    
                        return false;
                    }
                });
            },
    
            initEventListeners: function () {
                $container.find('.responsive-tabs__tab-header-container').on('click', function () {
                    var $tab = $(this).closest('.responsive-tabs__tab-container');
                    self.toggleTab($tab.index());
                    _private.scrollTo($tab.index());
                });
            },
    
            initGlobalEventListeners: function () {
                $(document).on('click', 'a', function () {
                    if (!ResponsiveUtil.isTabletMax()) {
                        return;
                    }
    
                    if (this.toString() !== window.location.toString() || !this.hash) {
                        return;
                    }
    
                    var index = _private.getTabBySlug(this.hash.substr(1));
    
                    if (index === -1) {
                        return;
                    }
    
                    self.openTab(index);
                    _private.scrollTo(index);
                });
    
                $(window).on('hashchange', function () {
                    if (!ResponsiveUtil.isTabletMax()) {
                        return;
                    }
    
                    if (!window.location.hash) {
                        return;
                    }
    
                    var index = _private.getTabBySlug(window.location.hash.substr(1));
    
                    if (index === -1) {
                        return;
                    }
    
                    self.openTab(index);
                    _private.scrollTo(index);
                });
            },
    
            getTabBySlug: function (slug) {
                var $tabs = $container.find('.responsive-tabs__tab-container');
                var result = -1;
    
                $tabs.each(function () {
                    var $tab = $(this);
                    var index = $tab.index();
                    var content_slug = $tab.data('slug') || '';
    
                    if (slug === content_slug) {
                        result = index;
    
                        return false;
                    }
                });
    
                return result;
            },
    
            scrollTo: function (index) {
                var $tabs = $container.find('.responsive-tabs__tab-container');
                var $tab = $tabs.eq(index);
                ScrollUtil.scrollTo($tab.offset().top);
            }
        };
    
        $.extend(self, {
            isOpen: function (index) {
                var $tabs = $container.find('.responsive-tabs__tab-container');
                var $tab = $tabs.eq(index);
    
                return $tab.hasClass('responsive-tabs__tab-container_selected');
            },
    
            toggleTab: function (index) {
                if (self.isOpen(index)) {
                    self.closeTab(index);
                } else {
                    self.openTab(index);
                }
            },
    
            openTab: function (index) {
                var $tabs = $container.find('.responsive-tabs__tab-container');
                var $tab = $tabs.eq(index);
                var $active_tabs = $tabs.filter('.responsive-tabs__tab-container_selected').not($tab);
                $active_tabs.removeClass('responsive-tabs__tab-container_selected');
                $tab.addClass('responsive-tabs__tab-container_selected');
                $tab.get(0).offsetTop;
    
                var slug = $tab.data('slug') || '';
    
                if (slug) {
                    if (index === 0) {
                        HistoryUtil.replaceState(null, null, window.location.pathname);
                    } else {
                        HistoryUtil.replaceState(null, null, '#' + slug);
                    }
                }
            },
    
            closeTab: function (index) {
                var $tabs = $container.find('.responsive-tabs__tab-container');
                var $tab = $tabs.eq(index);
    
                return $tab.removeClass('responsive-tabs__tab-container_selected');
            }
        });
    
        _private.initTabs();
        _private.initEventListeners();
        _private.initGlobalEventListeners();
    });
    
    market.ResponsiveSocialButton = ComponentRegistry.register(function ($context) {
        return $context.select('.responsive-social-button');
    }, function ($button) {
        var _private = {
            modal: $button.data('modal'),
    
            initEventListeners: function () {
                $button.on('click', function () {
                    if (window.Ya === undefined || (window.Ya !== undefined && window.Ya.share2 === undefined)) {
                        var script1 = $.getScript('https://yastatic.net/es5-shims/0.0.2/es5-shims.min.js');
                        var script2 = $.getScript('https://yastatic.net/share2/share.js');
    
                        if (config.commons.ya_share_source !== 'yastatic') {
                            script1 = $.getScript('https://cdn.jsdelivr.net/npm/yandex-share2/share.js');
                            script2 = true;
                        }
    
                        $.when(
                            script1,
                            script2,
                            $.Deferred(function (deferred) {
                                $(deferred.resolve);
                            })).done(function () {
                            _private.openModal();
                        });
                    } else {
                        _private.openModal();
                    }
                });
            },
            openModal: function () {
                ModalUtil.openContent(_private.modal, { isContentNoHidden: true });
            }
        };
    
        _private.initEventListeners();
    });
    
    market.ResponsiveRegionButton = ComponentRegistry.register(function ($context) {
        return $context.select('.r-region-button');
    }, function ($button) {
        var _private = {
            initEventListeners: function () {
                $button.on('click', function () {
                    $('.r-regions-decorator .shop-regions__trigger-show-window:first').trigger('click');
                });
            }
        };
    
        _private.initEventListeners();
    });
    

    $('[data-scroll-to]').on('click', function (e) {
        e.preventDefault();
        var targetSelector = $(this).attr('data-scroll-to');
        var $targetEl = $('[data-scroll-target="' + targetSelector + '"]');

        if ($targetEl.length === 0) {
            $targetEl = $(targetSelector);
        }

        if ($targetEl.length) {
            ScrollUtil.scrollToBlock($targetEl);
        }
    });

    $.widget('ui.autocomplete', $.ui.autocomplete, {
        _create: function () {
            $(this.element).data('market_autocomplete', this);
            this._superApply(arguments);
        },
        classes: {
            'ui-autocomplete': 'autocomplete'
        },
        _resizeMenu: function () {
            this.menu.element.outerWidth(this.element.outerWidth());
        }
    });

    $.datepicker.dpDiv.addClass('datepicker');
    $.datepicker.setDefaults({
        closeText: 'Закрыть',
        prevText: '&#x3C;Пред',
        nextText: 'След&#x3E;',
        currentText: 'Сегодня',
        monthNames: ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь',
            'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'],
        monthNamesShort: ['Янв', 'Фев', 'Мар', 'Апр', 'Май', 'Июн',
            'Июл', 'Авг', 'Сен', 'Окт', 'Ноя', 'Дек'],
        dayNames: ['воскресенье', 'понедельник', 'вторник', 'среда', 'четверг', 'пятница', 'суббота'],
        dayNamesShort: ['вск', 'пнд', 'втр', 'срд', 'чтв', 'птн', 'сбт'],
        dayNamesMin: ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'],
        weekHeader: 'Нед',
        dateFormat: 'dd.mm.yy',
        firstDay: 1,
        isRTL: false,
        showMonthAfterYear: false,
        yearSuffix: ''
    });

    var updateDatepicker = $.datepicker.__proto__._updateDatepicker;

    $.datepicker.__proto__._updateDatepicker = function () {
        updateDatepicker.apply(this, arguments);
        var $prev_arrow = $(config.commons.svg.arrow_left);
        $prev_arrow.addClass('datepicker__arrow-icon');
        var $next_arrow = $(config.commons.svg.arrow_right);
        $next_arrow.addClass('datepicker__arrow-icon');
        $.datepicker.dpDiv.find('.ui-datepicker-prev').addClass('image-box image-box_fill').html($prev_arrow);
        $.datepicker.dpDiv.find('.ui-datepicker-next').addClass('image-box image-box_fill').html($next_arrow);
    };

    $.extend($.ui.autocomplete.prototype.options, {
        position: {
            my: 'left top+5',
            at: 'left bottom',
            collision: 'none'
        },
        open: function () {
            var autocomplete = $(this).data('market_autocomplete');
            autocomplete.menu.activeMenu.removeClass('autocomplete_close');
            autocomplete.menu.activeMenu.offset();
            autocomplete.menu.activeMenu.addClass('autocomplete_open');
        },
        close: function () {
            var autocomplete = $(this).data('market_autocomplete');
            autocomplete.menu.activeMenu.removeClass('autocomplete_open');
            autocomplete.menu.activeMenu.offset();
            autocomplete.menu.activeMenu.addClass('autocomplete_close');
        }
    });

    $.extend($.ui.autocomplete.prototype.options.classes, {
        'ui-autocomplete': 'autocomplete'
    });

    $.widget('custom.searchAutocomplete', $.ui.autocomplete, {
        _renderItem: function (ul, item) {
            var $image_container = $('<div class="input-search-item__image-container"></div>');
            $image_container.append(item.image);

            var $name_container = $('<div class="input-search-item__name-container"></div>');
            $name_container.append(item.name);

            var $price_container = $('<div class="input-search-item__price-container"></div>');
            $price_container.append(item.price);

            var $info_container = $('<div class="input-search-item__info-container"></div>');
            $info_container.append($name_container);
            $info_container.append($price_container);

            var $item = $('<div class="input-search-item"></div>');
            $item.append($image_container);
            $item.append($info_container);

            return $('<li>')
                .append($item)
                .appendTo(ul);
        }
    });

    if (typeof window.shopRegions !== 'undefined' && !window.shopRegions.current_region_id) {
        $('.r-region-button .list-rows__item-name, .shop-regions__link').text(market.config.language.choose);
    }

    $(function () {
        market.requestNextFrame(market.Update);
    });
})(jQuery);
