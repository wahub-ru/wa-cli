if (!window.shopSkCallbackRequest) {

    shopSkCallbackRequest = (function ($) {

        'use strict';

        var shopSkCallbackRequest = function (params) {

            this.init(params);

        };

        shopSkCallbackRequest.prototype = {

            _config: {
                container: ".js-skcallback-request",
                path_to_plugin: ""
            },

            init: function (params) {

                var that = this;

                that.params = $.extend({}, that._config, params);

                that.initElements();

                that.onChangeStatus();

                that.onDeleteRequest();

                that.onAddRequests();

                that.onCartShow();

                that.runFilters();

            },

            initElements: function () {
                var that = this,
                    elements = [];

                elements.container = $(that.params.container);

                elements.count = elements.container.find(".js-skcallback-request-count");

                elements.reload = elements.container.find(".js-skcallback-reload");

                elements.filters = elements.container.find(".js-skcallback-filters");
                elements.filtersPage = elements.container.find(".js-skcallback-filters-page");
                elements.filtersForm = elements.filters.find(".js-skcallback-filters-form");
                elements.filtersPeriodInput = elements.filters.find(".js-skcallback-filters-period-input");
                elements.filtersPeriodLink = elements.filters.find(".js-skcallback-filters-period");
                elements.filtersStatusInput = elements.filters.find(".js-skcallback-filters-status-input");
                elements.filtersStatusLink = elements.filters.find(".js-skcallback-filters-status");
                elements.filtersDates = elements.filters.find(".js-skcallback-filters-indate");

                elements.cartDialog = elements.container.find(".js-skcallback-dialog-cart");
                elements.cartDialogIndent = elements.cartDialog.find(".js-skcallback-dialog-cart-indent");
                elements.cartDialogClose = elements.cartDialog.find(".js-skcallback-dialog-cart-close");

                that.elements = elements;

            },

            onChangeStatus: function () {
                var that = this,
                    container = that.elements.container;

                container.on("click", ".js-skcallback-select .js-skcallback-select-link", function (e) {
                    var element = $(this);
                    element.closest(".js-skcallback-select").find(".js-skcallback-select-list").toggle();
                });

                container.on("click", ".js-skcallback-select .js-skcallback-select-list-link", function (e) {
                    var element = $(this),
                        row = element.closest(".js-skcallback-request-row"),
                        request_id = row.data("request-id"),
                        status_id = element.data("status-id"),
                        title = element.text();

                    row.attr("data-status-id", status_id);

                    element.closest(".js-skcallback-select").find(".js-skcallback-select-current-link").text(title);

                    element.closest(".js-skcallback-select-list").hide();

                    $.post(that.params.path_to_plugin + "?plugin=skcallback&action=changeStatusRequest", {
                        request_id: request_id,
                        status_id: status_id
                    }, function (resp) {
                    }, "json");


                });

                container.on("click", ".js-skcallback-select", function (e) {
                    e.stopPropagation();
                });

                $("body").on("click", function (e) {
                    if (!$(this).closest(".js-skcallback-select").size()) {
                        $(".js-skcallback-select-list").hide();
                    }
                });

            },

            onDeleteRequest: function () {
                var that = this,
                    container = that.elements.container;

                container.on("click", ".js-skcallback-request-delete", function (e) {

                    var element = $(this),
                        row = element.closest(".js-skcallback-request-row"),
                        request_id = element.data("request-id");

                    if (!confirm("Вы уверены?")) {
                        return false;
                    }

                    row.detach();

                    $.post(that.params.path_to_plugin + "?plugin=skcallback&action=deleteRequest", {request_id: request_id}, function (resp) {
                    }, "json");

                })

            },

            runFilters: function () {
                var that = this,
                    elements = that.elements;

                elements.filtersPeriodLink.on("click", function () {
                    var element = $(this),
                        period = element.data("period");

                    elements.filtersPeriodLink.removeClass("_active");
                    element.addClass("_active");

                    elements.filtersPeriodInput.val(period);

                    elements.filtersDates.val("");

                    that.reloadRequest();

                });

                elements.filtersStatusLink.on("click", function () {
                    var element = $(this),
                        status_id = element.data("status-id");

                    elements.filtersStatusLink.removeClass("_active");
                    element.addClass("_active");

                    elements.filtersStatusInput.val(status_id);

                    that.reloadRequest();

                });

                elements.filtersDates.on("change", function () {
                    var filling = 0;
                    elements.filtersDates.each(function () {
                        if ($(this).val()) filling++;
                    });

                    if (filling == 2) {
                        elements.filtersPeriodLink.removeClass("_active");
                        that.reloadRequest();
                    }

                });

            },

            reloadRequest: function () {
                var that = this,
                    elements = that.elements,
                    form = elements.filtersForm;

                elements.reload.addClass("_reload");
                $.post(that.params.path_to_plugin + "?plugin=skcallback&action=reloadRequests", form.serialize(), function (resp) {

                    if (resp.status == "fail") {
                        alert("При обновлении произошла ошибка");
                    } else if (resp.status == "ok") {
                        elements.reload.html(resp.data.content);
                        elements.count.text(resp.data.count);
                        elements.filtersPage.val("1");
                    }

                    elements.reload.removeClass("_reload");

                }, "json");

            },

            onAddRequests: function () {
                var that = this,
                    elements = that.elements,
                    form = elements.filtersForm;

                elements.container.on("click", ".js-skcallback-request-add", function () {
                    var elementAdd = $(this),
                        page = parseInt(elements.filtersPage.val());
                    page++;
                    elements.filtersPage.val(page);
                    $.post(that.params.path_to_plugin + "?plugin=skcallback&action=reloadRequests", form.serialize(), function (resp) {
                        if (resp.status == "fail") {
                            alert("При запросе произошла ошибка");
                        } else if (resp.status == "ok") {
                            var append = $(resp.data.content).find(".js-skcallback-request-tbody").html();
                            elements.container.find(".js-skcallback-request-tbody").append(append);
                            if (!resp.data.is_add) {
                                elementAdd.detach();
                            }
                        }

                    }, "json");
                });
            },

            onCartShow: function () {
                var that = this,
                    elements = that.elements;

                elements.container.on("click", ".js-skcallback-request-cart", function () {
                    var element = $(this),
                        request_id = element.data("request-id");

                    $.post(that.params.path_to_plugin + "?plugin=skcallback&action=getCartRequest", {request_id: request_id}, function (resp) {
                        if (resp.status == "fail") {
                            alert("При запросе произошла ошибка");
                        } else if (resp.status == "ok") {
                            elements.cartDialogIndent.html(resp.data.content);
                            elements.cartDialog.show();
                        }
                    }, "json")

                });

                elements.cartDialogClose.on("click", function () {
                    elements.cartDialog.hide();
                });

            }

        };

        return shopSkCallbackRequest;

    })(jQuery);
}