if (!window.shopSkCallback) {

    shopSkCallback = (function ($) {

        'use strict';

        var shopSkCallback = function (params) {

            this.init(params);

        };

        shopSkCallback.prototype = {

            _config: {
                container: '.js-sk-callback',
                block: '.js-sk-callback-block',
                urlSave: '/skcallback/',
                yandexId: '',
                yandexOpen: '',
                yandexSend: '',
                yandexError: '',
                googleOpenCategory: '',
                googleOpenAction: '',
                googleSendCategory: '',
                googleSendAction: '',
                googleErrorCategory: '',
                googleErrorAction: ''
            },

            init: function (params) {

                var that = this;

                that.params = $.extend({}, that._config, params);

                that.initElements();

                if(!that.elements.container.size()){
                    return false;
                }

                that.runSliders();

                that.runMask();

                that.onEventOpen();

                that.onSubmit();

                that.onClose();

            },

            initElements: function(){
                var that = this,
                    elements = {};

                elements.container = $(that.params.container);
                elements.block = $(that.params.block);
                elements.sliders = elements.container.find(".js-sk-callback-slider");
                elements.form = elements.container.find(".js-sk-callback-form");
                elements.pole = elements.container.find(".js-sk-callback__pole");
                elements.mask = elements.container.find(".js-sk-callback-maskedinput");
                elements.preload = elements.container.find(".js-sk-callback-preloader");

                elements.paramsRegion = elements.container.find(".js-sk-callback-params-region");
                elements.paramsCity = elements.container.find(".js-sk-callback-params-city");

                elements.captchaRefresh =  elements.container.find(".wa-captcha-refresh");

                that.elements = elements;

            },

            runSliders: function(){
                var that = this;

                that.elements.sliders.each(function(){

                    var element = $(this),
                        pole = element.closest(".js-sk-callback-pole-slider"),
                        minInput = pole.find(".js-sk-callback-input-min"),
                        maxInput = pole.find(".js-sk-callback-input-max"),
                        minText = pole.find(".js-sk-callback-value-min"),
                        maxText = pole.find(".js-sk-callback-value-max"),
                        min = parseInt(element.data("min")),
                        max = parseInt(element.data("max"));

                    if(!min || !max){
                        return true;
                    }

                    element.slider({
                      range: true,
                      min: min,
                      max: max,
                      values: [ min, max ],
                      create: function(){
                          element.find(".ui-slider-handle:first").text(min);
                          element.find(".ui-slider-handle:last").text(max);
                      },
                      slide: function( event, ui ) {
                          minInput.val(ui.values[0]);
                          maxInput.val(ui.values[1]);
                          minText.text(ui.values[0]);
                          maxText.text(ui.values[1]);
                          element.find(".ui-slider-handle:first").text(ui.values[0]);
                          element.find(".ui-slider-handle:last").text(ui.values[1]);
                      }

                    });

                });

            },

            runMask: function(){
                var that = this;

                that.elements.mask.each(function(){
                    var element = $(this),
                        reg = /#/g,
                        mask = element.data("mask").replace(reg, "9");

                    if(mask){
                        element.skmask(mask);
                    }

                })

            },

            onEventOpen: function(){
                var that = this,
                    elements = that.elements;

                elements.block.on("event-open", function(){
                    that.sendEventYandex(that.params.yandexOpen);
                    that.sendEventGoogle(that.params.googleOpenCategory, that.params.googleOpenAction)
                })

            },

            sendEventYandex: function(target){
                var that = this;

                if(that.params.yandexId && target){
                    if(typeof window['yaCounter' + that.params.yandexId] !== "undefined"){
                        var counter = window['yaCounter' + that.params.yandexId];
                        if(typeof counter !== "undefined" && typeof counter.reachGoal !== "undefined"){
                            counter.reachGoal(target);
                        }
                    }
                }
            },

            sendEventGoogle: function(category, action){
                var that = this;

                if(category && action){
                    if(typeof ga !== "undefined"){
                        ga('send', 'event', category, action);
                    }
                }
            },

            onSubmit: function(){
                var that = this,
                    elements = that.elements,
                    process = false;

                that.elements.form.on("submit", function(e){
                    e.preventDefault();

                    if(process) return false;
                    process = true;

                    elements.preload.show();

                    that.sendEventYandex(that.params.yandexSend);
                    that.sendEventGoogle(that.params.googleSendCategory, that.params.googleSendAction);

                    $.post(that.params.urlSave, that.elements.form.serialize() + "&check=1", function(resp){

                        elements.pole.removeClass("_error");

                        if(resp.status == "fail"){

                            $.each(resp.errors, function (id, error) {
                                var pole = elements.pole.filter("[data-id='" + id + "']");

                                if(pole.size()){
                                    pole.addClass("_error");
                                    pole.find(".js-sk-callback-error").text(error);
                                }
                            });

                            if(elements.captchaRefresh.size()){
                                elements.captchaRefresh.click();
                            }

                        }else if(resp.status == "ok"){

                            that.elements.container.html(resp.data.content);

                        }else{
                            alert("Произошла неизвестная ошибка");

                            that.sendEventYandex(that.params.yandexError);
                            that.sendEventGoogle(that.params.googleErrorCategory, that.params.googleErrorAction);
                        }

                        elements.preload.hide();

                        process = false;

                    }, "json")


                })
            },

            onClose: function(){
                var that = this;

                that.elements.container.on("click", ".js-sk-callback__close", function(){
                    that.elements.block.trigger("run-close")
                });
            }


        };

        return shopSkCallback;

    })(jQuery);

}

if(typeof jQuery.fn.skmask === "undefined"){
    !function(a){"function"==typeof define&&define.amd?define(["jquery"],a):a("object"==typeof exports?require("jquery"):jQuery)}(function(a){var b,c=navigator.userAgent,d=/iphone/i.test(c),e=/chrome/i.test(c),f=/android/i.test(c);a.skmask={definitions:{9:"[0-9]",a:"[A-Za-z]","*":"[A-Za-z0-9]"},autoclear:!0,dataName:"rawMaskFn",placeholder:"_"},a.fn.extend({caret:function(a,b){var c;if(0!==this.length&&!this.is(":hidden"))return"number"==typeof a?(b="number"==typeof b?b:a,this.each(function(){this.setSelectionRange?this.setSelectionRange(a,b):this.createTextRange&&(c=this.createTextRange(),c.collapse(!0),c.moveEnd("character",b),c.moveStart("character",a),c.select())})):(this[0].setSelectionRange?(a=this[0].selectionStart,b=this[0].selectionEnd):document.selection&&document.selection.createRange&&(c=document.selection.createRange(),a=0-c.duplicate().moveStart("character",-1e5),b=a+c.text.length),{begin:a,end:b})},unmask:function(){return this.trigger("unmask")},skmask:function(c,g){var h,i,j,k,l,m,n,o;if(!c&&this.length>0){h=a(this[0]);var p=h.data(a.skmask.dataName);return p?p():void 0}return g=a.extend({autoclear:a.skmask.autoclear,placeholder:a.skmask.placeholder,completed:null},g),i=a.skmask.definitions,j=[],k=n=c.length,l=null,a.each(c.split(""),function(a,b){"?"==b?(n--,k=a):i[b]?(j.push(new RegExp(i[b])),null===l&&(l=j.length-1),k>a&&(m=j.length-1)):j.push(null)}),this.trigger("unskmask").each(function(){function h(){if(g.completed){for(var a=l;m>=a;a++)if(j[a]&&C[a]===p(a))return;g.completed.call(B)}}function p(a){return g.placeholder.charAt(a<g.placeholder.length?a:0)}function q(a){for(;++a<n&&!j[a];);return a}function r(a){for(;--a>=0&&!j[a];);return a}function s(a,b){var c,d;if(!(0>a)){for(c=a,d=q(b);n>c;c++)if(j[c]){if(!(n>d&&j[c].test(C[d])))break;C[c]=C[d],C[d]=p(d),d=q(d)}z(),B.caret(Math.max(l,a))}}function t(a){var b,c,d,e;for(b=a,c=p(a);n>b;b++)if(j[b]){if(d=q(b),e=C[b],C[b]=c,!(n>d&&j[d].test(e)))break;c=e}}function u(){var a=B.val(),b=B.caret();if(o&&o.length&&o.length>a.length){for(A(!0);b.begin>0&&!j[b.begin-1];)b.begin--;if(0===b.begin)for(;b.begin<l&&!j[b.begin];)b.begin++;B.caret(b.begin,b.begin)}else{for(A(!0);b.begin<n&&!j[b.begin];)b.begin++;B.caret(b.begin,b.begin)}h()}function v(){A(),B.val()!=E&&B.change()}function w(a){if(!B.prop("readonly")){var b,c,e,f=a.which||a.keyCode;o=B.val(),8===f||46===f||d&&127===f?(b=B.caret(),c=b.begin,e=b.end,e-c===0&&(c=46!==f?r(c):e=q(c-1),e=46===f?q(e):e),y(c,e),s(c,e-1),a.preventDefault()):13===f?v.call(this,a):27===f&&(B.val(E),B.caret(0,A()),a.preventDefault())}}function x(b){if(!B.prop("readonly")){var c,d,e,g=b.which||b.keyCode,i=B.caret();if(!(b.ctrlKey||b.altKey||b.metaKey||32>g)&&g&&13!==g){if(i.end-i.begin!==0&&(y(i.begin,i.end),s(i.begin,i.end-1)),c=q(i.begin-1),n>c&&(d=String.fromCharCode(g),j[c].test(d))){if(t(c),C[c]=d,z(),e=q(c),f){var k=function(){a.proxy(a.fn.caret,B,e)()};setTimeout(k,0)}else B.caret(e);i.begin<=m&&h()}b.preventDefault()}}}function y(a,b){var c;for(c=a;b>c&&n>c;c++)j[c]&&(C[c]=p(c))}function z(){B.val(C.join(""))}function A(a){var b,c,d,e=B.val(),f=-1;for(b=0,d=0;n>b;b++)if(j[b]){for(C[b]=p(b);d++<e.length;)if(c=e.charAt(d-1),j[b].test(c)){C[b]=c,f=b;break}if(d>e.length){y(b+1,n);break}}else C[b]===e.charAt(d)&&d++,k>b&&(f=b);return a?z():k>f+1?g.autoclear||C.join("")===D?(B.val()&&B.val(""),y(0,n)):z():(z(),B.val(B.val().substring(0,f+1))),k?b:l}var B=a(this),C=a.map(c.split(""),function(a,b){return"?"!=a?i[a]?p(b):a:void 0}),D=C.join(""),E=B.val();B.data(a.skmask.dataName,function(){return a.map(C,function(a,b){return j[b]&&a!=p(b)?a:null}).join("")}),B.one("unskmask",function(){B.off(".skmask").removeData(a.skmask.dataName)}).on("focus.skmask",function(){if(!B.prop("readonly")){clearTimeout(b);var a;E=B.val(),a=A(),b=setTimeout(function(){B.get(0)===document.activeElement&&(z(),a==c.replace("?","").length?B.caret(0,a):B.caret(a))},10)}}).on("blur.skmask",v).on("keydown.skmask",w).on("keypress.skmask",x).on("input.skmask paste.skmask",function(){B.prop("readonly")||setTimeout(function(){var a=A(!0);B.caret(a),h()},0)}),e&&f&&B.off("input.skmask").on("input.skmask",u),A()})}})});
}