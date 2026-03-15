if (!window.shopSkPluginSettings) {

    var shopSkPluginSettings = (function ($) {

        'use strict';

        var shopSkPluginSettings = function (params) {

            this.init(params);

        };

        shopSkPluginSettings.prototype = {

            _config: {

                container: ".js-sk-main",
                successMsg: "<span class='success'><i style='vertical-align:middle' class='icon16 yes'></i>Сохранено</span>",
                errorMsg: "<span class='error'><i style='vertical-align:middle' class='icon16 no'></i>Допущены ошибки</span>"

            },

            init: function (params) {

                var that = this;

                that.params = $.extend({}, that._config, params);

                that.initElements();

                that.initTabs();

                that.onFormAddField();

                that.onFormDeleteField();

                that.onFormSave();

                that.toggleTitle();

                that.addStatusesField();

                that.toggleStatusTitle();

                that.deleteStatus();

            },

            initElements: function () {

                var that = this,
                    elements = {};

                elements.container = $(that.params.container);
                elements.tabs = elements.container.find(".js-sk-tabs");
                elements.tabsHeaders = elements.container.find(".js-sk-tabs-header");
                elements.tabsItems = elements.container.find(".js-sk-tabs-item");

                elements.form = elements.container.find(".js-sk-form");
                elements.formStatus = elements.container.find(".js-sk-form-status");

                elements.statuses = elements.container.find(".js-sk-statuses");
                elements.statusesBody = elements.container.find(".js-sk-statuses-tbody");
                elements.statusesAddLink = elements.container.find(".js-sk-statuses-add-link");

                elements.generateForm = elements.container.find(".js-sk-generate-form");
                elements.generateBody = elements.container.find(".js-sk-generate-form-body");
                elements.generateFormAddSelect = elements.generateForm.find(".js-sk-generate-form-add-select");
                elements.generateFormAddLink = elements.generateForm.find(".js-sk-generate-form-add-link");
                elements.generateFormDelete = elements.generateForm.find(".js-sk-generate-form-delete");

                that.elements = elements;

            },

            initTabs: function () {
                var that = this,
                    elements = that.elements;


                if (typeof elements.tabs === "undefined" || !elements.tabs.length) {
                    return false;
                }

                elements.tabsHeaders.on("click", function () {
                    var element = $(this),
                        tabName = element.data("tab"),
                        content = elements.tabsItems.filter("[data-tab='" + tabName + "']");

                    if (element.hasClass("selected")) {
                        return false;
                    }

                    elements.tabsHeaders.removeClass("selected");
                    elements.tabsItems.removeClass("selected");

                    element.addClass("selected");
                    content.addClass("selected");

                })
            },

            onFormSave: function () {
                var that = this,
                    elements = that.elements,
                    timeout = null;

                elements.form.on("submit", function (e) {
                    e.preventDefault();
                    var form = $(this);

                    $.post("?plugin=skcallback&action=formSave", form.serialize(), function (resp) {
                        if (resp.status == "fail") {
                            if (typeof(resp.errors.fields) !== "undefined") {
                                $.each(resp.errors.fields, function (id, field) {
                                    elements.form.find(".js-sk-generate-form-tr[data-row-id='" + id + "']").addClass("error");
                                });
                                that.elements.formStatus.html(that.params.errorMsg).show();
                            }
                        } else if (resp.status == "ok") {
                            elements.form.find(".js-sk-generate-form-tr.error").removeClass("error");
                            that.elements.formStatus.html(that.params.successMsg).show();
                            if (timeout) {
                                clearTimeout(timeout);
                            }
                            timeout = setTimeout(function () {
                                that.elements.formStatus.hide()
                            }, 3000);
                        }
                    }, "json")

                });

            },

            onFormAddField: function () {
                var that = this,
                    elements = that.elements;

                elements.generateFormAddLink.on("click", function () {
                    var type_id = parseInt(elements.generateFormAddSelect.val());

                    if (!type_id) {
                        alert("Выберите поле для добавления");
                        return false;
                    }

                    that.params.max_id++;

                    $.post("?plugin=skcallback&action=fieldAdd", {
                        type_id: type_id,
                        max_id: that.params.max_id
                    }, function (resp) {

                        if (resp.status == "fail") {

                            alert(resp.errors[0]);

                        } else if (resp.status == "ok") {

                            elements.generateBody.append(resp.data.content)

                        } else {
                            alert("неизвестная ошибка");
                        }

                    }, "json")

                });

            },

            onFormDeleteField: function () {
                var that = this,
                    elements = that.elements;

                elements.generateForm.on("click", ".js-sk-generate-form-delete", function () {
                    var element = $(this),
                        control_id = element.data("id");

                    if (!confirm("Вы уверены?")) {
                        return false;
                    }
                    element.closest(".js-sk-generate-form-tr").detach();
                    $.post("?plugin=skcallback&action=fieldDelete", {control_id: control_id}, function (resp) {

                    }, "json")

                });
            },

            toggleTitle: function () {
                var that = this,
                    elements = that.elements;

                elements.generateForm.on("click", ".js-sk-generate-form-name", function () {
                    var element = $(this);

                    element.find("a").hide();
                    element.find("input").attr("type", "text");
                });

            },

            addStatusesField: function () {
                var that = this,
                    elements = that.elements;

                elements.statusesAddLink.on("click", function () {

                    that.params.status_max_id++;

                    $.post("?plugin=skcallback&action=fieldStatusesAdd", {max_id: that.params.status_max_id}, function (resp) {

                        if (resp.status == "fail") {

                            alert(resp.errors[0]);

                        } else if (resp.status == "ok") {

                            elements.statusesBody.append(resp.data.content)

                        } else {
                            alert("неизвестная ошибка");
                        }

                    }, "json")

                })

            },

            toggleStatusTitle: function () {
                var that = this,
                    elements = that.elements;

                elements.statuses.on("click", ".js-skcallback-title", function () {
                    var element = $(this);

                    element.find("a").hide();
                    element.find("input").attr("type", "text");
                });

            },

            deleteStatus: function () {
                var that = this,
                    elements = that.elements;

                elements.statuses.on("click", ".js-skcallback-status-delete", function () {
                    var element = $(this),
                        status_id = element.data("id");

                    if (!confirm("Вы уверены?")) {
                        return false;
                    }
                    element.closest(".js-skcallback-status-row").detach();
                    $.post("?plugin=skcallback&action=fieldStatusDelete", {status_id: status_id}, function (resp) {
                    }, "json")

                });
            }

        };

        return shopSkPluginSettings;

    })(jQuery);

}
