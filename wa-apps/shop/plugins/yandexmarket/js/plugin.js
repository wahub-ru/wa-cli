/**
 * {literal}
 *
 * @names plugin_yandexmarket*
 * @property shipping_options
 * @method plugin_yandexmarketInit
 * @method plugin_yandexmarketAction
 * @method plugin_yandexmarketBlur
 * @method plugin_yandexmarketCampaign
 * @method plugin_yandexmarketOutlets
 */
if (typeof($) !== 'undefined') {
    $.plugin_yandexmarket = {
        options: {
            null: null,
            loading: 'Загрузка... <i class="fas fa-spinner fa-spin text-gray"><i>'
        },

        tab: null,
        tail: '',
        $container: null,
        tabs: {},

        /**
         * Init section
         *
         */
        init: function () {
            /* init settings */
            this.$container = $('#s-yandexmarket-plugin-settings');

            var $tabs = this.$container.find('> ul.tabs > li:not(.no-tab) > a');
            var self = this;
            var regexp = new RegExp('^#?/?yandexmarket/');
            $tabs.each(function () {
                var $a = $(this);
                var tab = $a.attr('href').replace(regexp, '').replace(/\/.*$/, '');
                self.tabs[tab] = $a.data('alias') || tab;
            });

            $tabs.click(function () {
                return self.clickHandler(this);
            });
            this.initClick(this.$container.find('div.tab-content'));
            this.dispatch(window.location.href);
        },

        /**
         * @param {HTMLAnchorElement} a
         */
        clickHandler: function (a) {
            $.shop.trace('plugin_yandexmarket.clickHandler', a);
            this.dispatch(a.href);
            return true;
        },

        clickAction: function(a) {
            var path = this.parsePath(a.href.replace(/^[^#]*#\/yandexmarket\/*/, ''));
            var method = $.shop.getMethod(path.method.split('/'), this, 'action');
            if (method && method.name) {
                method.params.unshift(a);
                this[method.name].apply(this, method.params);
            }
            return false;
        },

        dispatch: function (href) {
            var path = this.parsePath(href.replace(/^[^#]*#\/yandexmarket\/*/, ''));
            $.shop.trace('dispatch', [href, path]);
            this.showTab(path);

        },

        parsePath: function (path) {
            path = path.replace(/^.*#\/yandexmarket/, '');
            var parsed = {
                href: null,
                tab: path.replace(/\/.*$/, '') || '',
                tail: path.replace(/^[^\/]+\//, '').replace(/[\w_\-]+=.*$/, '').replace(/\/$/, '') || null,
                method: path,
                raw: path
            };
            if (this.tabs[parsed.tab] !== parsed.tab) {
                parsed.href = '';
                parsed.tail = !!parsed.tail ? (parsed.tab + '/' + parsed.tail) : parsed.tab;

                parsed.tab = this.tabs[''];
                parsed.method = parsed.tab + '/' + parsed.method;

            } else {
                parsed.href = parsed.tab + '/';
            }

            return parsed;
        },

        showTab: function (path) {
            var $tab = this.$container.find(s = 'div.tab-content[data-tab*="_' + path.tab + '_"]');
            this.$container.find('div.tab-content').hide();
            this.$container.find('> ul.tabs li.selected').removeClass('selected');


            if ($tab.length) {
                $tab.show();

                this.$container.find('> ul.tabs > li:not(.no-tab) > a[href="#/yandexmarket/' + path.href + '"]').parent().addClass('selected');

                this.tab = path.tab;

                var method = path.tab + 'Tab';
                if (typeof(this[method]) === 'function') {
                    this[method].apply(this, [path]);
                }
                this.tabAction(path);
                return true;
            } else {
                $.shop.error('Tab not found', tab);
            }
        },


        tabAction: function (path) {
            var method = $.shop.getMethod(path.method.split('/'), this, 'click');
            if (method && method.name) {
                this[method.name].apply(this, method.params);
            }
        },


        campaignsTab: function (path) {
            var $tab_content = this.$container.find('div.tab-content[data-tab*="_campaigns_"]');
            var $campaigns = this.$container.find('#s-settings-plugin-yandexmarket');
            if (!path) {
                $tab_content.find('h1:first').hide();
                $tab_content.find('.js-campaigns').hide();
                $campaigns.hide();
            } else {
                $campaigns.show();
                $tab_content.find('.js-campaigns').show();

                $('#s-settings-plugin-yandexmarket-content').html(this.options.loading).hide();
                $tab_content.find('h1.js-bread-crumbs:not(:first)').remove();
                $tab_content.find('h1:first').show();
            }
        },

        initClick: function ($scope) {
            var self = this;
            $scope.find('a[href*="#/yandexmarket/"]:not(.js-action)').click(function () {
                return self.clickHandler(this);
            });
            $scope.find('a[href*="#/yandexmarket/"].js-action').click(function () {
                return self.clickAction(this);
            });
        },

        clickCampaignsCampaign: function (campaign_id) {
            this.campaignsTab(false);
            var url = '?plugin=yandexmarket&module=settings&action=campaign&campaign_id=' + campaign_id;
            $('#s-settings-plugin-yandexmarket-content').show().html(this.options.loading).load(url, function () {
                $.plugin_yandexmarket.initClick($('#s-settings-plugin-yandexmarket-content'));
            });
        },

        actionCampaignsReload: function () {
            var $tab_content = this.$container.find('div.tab-content[data-tab="_campaigns_"]');
            var self = this;
            $.shop.trace('clickCampaignsReload', [$tab_content.length,$tab_content.data('url'), $tab_content]);
            $tab_content.html(this.options.loading).load($tab_content.data('url'), function () {
                self.initClick(self.$container.find('div.tab-content[data-tab="_campaigns_"]'));
            });
            return false;
        },

        clickCampaignsOutlets: function (campaign_id) {
            this.campaignsTab(false);
            var url = '?plugin=yandexmarket&module=settings&action=outlets&campaign_id=' + campaign_id;
            $('#s-settings-plugin-yandexmarket-content').show().html(this.options.loading).load(url, function () {
                $.plugin_yandexmarket.initClick($('#s-settings-plugin-yandexmarket-content'));
            });
        },
        actionCampaignsSettle: function (a, campaign_id) {
            var name = $(a).parents('tr').find('.js-campaign-name').text();
            var self = this;
            $.waDialog({
                html: $('#s-settings-plugin-yandexmarket-settle-dialog').clone()[0],
                onOpen: function ($d, dialog) {
                    $d.find('.state-error-hint').hide();
                    $d.find(':input[name="campaign_id"]').val(campaign_id);
                    $d.find('.js-campaign-name').text(name);

                    $d.find('form').on('submit', function() {
                        var form = $(this);
                        var data = form.serialize();
                        var submit_section = form.find('.dialog-footer > .dialog-buttons');
                        var cancel_button = submit_section.find('.js-close-dialog').hide();
                        var submit_button = submit_section.find(':submit').attr('disabled', true);
                        submit_button.after('<i class="fas fa-spinner fa-spin text-gray"/>');

                        $.post(form.attr('action'), data, function (r) {
                            submit_button.find('.fa-spinner').remove();

                            if (r.status !== 'ok' && r.errors) {
                                submit_button.attr('disabled', false);
                                cancel_button.show();
                                $d.find('.state-error-hint').show().text(r.errors[0]);
                            } else {
                                try {
                                    self.actionCampaignsReload();
                                    dialog.close();
                                } catch (e) {
                                    $.shop.error(e.gmessage, e);
                                }
                            }
                        }, 'json');
                        return false;
                    });
                },
            });
            return false;
        }
    };
} else {
    //
}
/**
 * {/literal}
 */
