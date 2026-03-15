/*
 * @link https://warslab.ru/
 * @author waResearchLab
 * @Copyright (c) 2023 waResearchLab
 */
let settings = Vue.createApp({
    data() {
        return {
            settings: {$settings},
            sets: {$sets},
            pluginSets: {$plugin_sets},
            li: 'free',
            liFree: 'settings',
            repairs: {$repairs},
            handlers: {$handlers},
            cHandler: false,
            handlerFilter: '',
            repairFormData: [],
            dimensions: {$dimensions},
            dimEdit: false,
            newDim: false,
            newUnit: false,
            newIcon: '',
            premium: {$premium},
            currentDim: false,
            css: {$css},
            clis: {$clis},
            isCloud: {$isCloud},
            onAddIcon: false,
            current_action: false,
            actionResult: false,
            runActions: false,
            defaultValues: false,
            errorText: false,
            doneText: false
        }
    },
    mounted: function() {
        this.setLi('free');
    },
    methods: {
        runRepairAction: function (action) {
            this.current_action = action;
            if (this.repairs[action].addon) {
                $.post('?plugin=hidset&action=runCliTask', { 'task': action, 'data':this.repairFormData}, r => {
                    if (r.status == 'ok') {
                        this.actionResult = '<b>' + action + '</b>: выполнено успешно!';
                    } else {
                        this.actionResult = false;
                        this.setText(r.errors);
                    }
                    this.repairFormData = false;
                    this.current_action = false;
                });
                $.post('?plugin=hidset&action=getDimensions', r => {
                    this.dimensions = r.data;
                    this.currentDim = Object.keys(this.dimensions)[0];
                });
            } else {
                $.get('{$app_url}?module=repair&action=' + action, r => {
                    this.actionResult = '<b>' + action + '</b>: ' + r;
                    this.current_action = false;
                });
            }
        },
        saveSettings: function (type) {
            this.runActions = true;
            $.post('{$wa_app_url}?plugin=hidset&action=saveSettings', { 'shop': this.settings, 'plugins': this.pluginSets, 'type': type }, r => {
                if (r.status == 'ok') {
                    this.settings = r.data.shop;
                    this.pluginSets = r.data.plugins;
                    this.setText('Сохранено успешно', 'done');
                } else {
                    this.setText(r.errors);
                }
                this.runActions = false;
            });
        },
        restoreDefaultSettings: function (set_type = 'base') {
            $.post('{$app_url}?plugin=hidset&action=restoreDefaultSettings', { 'type': this.defaultValues, 'set_type': set_type}, r => {
                if (r.status === 'ok') {
                    this.settings = r.data.shop;
                    this.pluginSets = r.data.plugins;
                    this.defaultValues = false;
                    this.setText('Восстановление успешно завершено', 'done');
                } else {
                    this.setText(r.errors);
                }
            });
        },
        saveDim: function () {
            this.runActions = true;
            $.post('{$wa_app_url}?plugin=hidset&action=saveDim', { 'dim': this.dimEdit, 'type': this.currentDim }, r => {
                if (r.status === 'ok') {
                    this.dimensions = r.data;
                    this.dimEdit = false;
                } else {
                    this.setText(r.errors);
                }
                this.runActions = false;
            });
        },
        restoreDefaultDim: function () {
            $.post('?plugin=hidset&action=restoreDefaultDimension', { 'type': this.currentDim}, r => {
                if (r.status == 'ok') {
                    this.dimensions = r.data;
                    this.setText('Восстановление успешно завершено', 'done');
                } else {
                    this.setText(r.errors);
                }
            });
        },
        onEditDim: function (dim) {
            if (dim === 'new') {

            } else {
                this.dimEdit = JSON.parse(JSON.stringify(this.dimensions[dim]));
            }
        },
        addUnit: function () {
            this.dimEdit.units[this.newUnit.unit] = { 'name': this.newUnit.name, 'multiplier': this.newUnit.multiplier};
            this.newUnit = false;
        },
        addDim: function () {
            this.currentDim = this.newDim.id;
            let units = { [this.newDim.base_unit]: { 'name': 'Название', 'multiplier': 1} };
            this.dimEdit = {
                'base_unit': this.newDim.base_unit,
                'name': this.newDim.name,
                'units': units };
            this.newDim = false;
        },
        delDim: function () {
            this.runActions = true;
            $.post('?plugin=hidset&action=delDim', { 'id': this.currentDim}, r => {
                if (r.status == 'ok') {
                    this.dimensions = r.data;
                    this.currentDim = Object.keys(this.dimensions)[0];
                    this.setText('Удалено успешно', 'done');
                    this.setLi('dimensions');
                } else {
                    this.setText(r.errors);
                }
                this.runActions = false;
            });
        },
        delUnit: function (unit) {
            let units = { };
            Object.keys(this.dimEdit.units).forEach(key => {
                if (key !== unit) units[key] = this.dimEdit.units[key];
            });
            this.dimEdit.units = units;
        },
        getLiClass: function (li) {
            if (li === this.li) return 'selected';
            else return '';
        },
        getLiFreeClass: function (li) {
            if (li === this.liFree) return 'selected';
            else return '';
        },
        getLinkClass: function (type) {
            let linkClass = '';
            if (type === 'repair') {
                if (this.current_action !== false) linkClass = 'disabled';
            } else if (type === 'addUnit') {
                if (!this.newUnit.unit.length || !this.newUnit.name.length || !this.newUnit.multiplier) linkClass = 'disabled';
            } else if (type === 'newDim') {
                if (!this.newDim.id.length || !this.newDim.name.length) linkClass = 'disabled';
            } else if (type === 'addIcon') {
                if (!this.newIcon.length) return 'disabled';
            }
            return linkClass;
        },
        getIconClass: function (iClass) {
            return 'icon16 ' + iClass;
        },
        getCliTaskClass: function (idx){
            let tclass = 'cli-task';
            if (this.clis[idx].addon) tclass += ' addon';
            return tclass;
        },
        delIcon: function (set, idx) {
            Vue.delete(this.settings[set], idx);
        },
        setFormData: function(c, command) {
            this.repairFormData = { 'action':command, 'data':c.formData};
        },
        checkFormData: function (c) {
            if (
                !c.hasOwnProperty('formData') ||
                c.formData === false
            ) return true;
            return false;
        },
        getIconSettings: function () {
            let maxLength = Math.max(this.settings.order_state_icons.length, this.settings.order_action_icons.length, this.settings.customers_filter_icons.length, this.settings.type_icons.length);
            if (this.settings.order_state_icons.length === maxLength) return this.settings.order_state_icons;
            else if (this.settings.order_action_icons.length === maxLength) return this.settings.order_action_icons;
            else if (this.settings.customers_filter_icons.length === maxLength) return this.settings.customers_filter_icons;
            else if (this.settings.type_icons.length === maxLength) return this.settings.type_icons;
        },
        setHandler: function (idx, hdx) {
            this.cHandler = {
                app_id: this.handlers[idx].app_id,
                app_name: this.handlers[idx].name,
                handler: this.handlers[idx].handlers[hdx].handler,
                items: this.handlers[idx].handlers[hdx].items
            };
            $('html, body').animate({ scrollTop: 0}, 100);
        },
        getHandlerClass: function (app, handler) {
            let hClass = 'hidset-handler';
            if (this.cHandler !== false && this.cHandler.app_id === app && this.cHandler.handler === handler) hClass += ' selected';
            return hClass;
        },
        getHandlerHref: function () {
            let part = this.cHandler.handler;
            let aPart = part.split('.');
            if (aPart.length > 1 && (aPart[0].indexOf('controller_after') === 0 || aPart[0].indexOf('controller_before') === 0)) {
                part = (aPart[0].indexOf('controller_after') === 0 ? 'controller_after' : 'controller_before') + '.*';
            }
            return 'https://developers.webasyst.ru/hooks/' + this.cHandler.app_id + '/' + part + '/';
        },
        checkHandler: function (value) {
            if (!this.handlerFilter.trim().length) return true;
            return !!value.includes(this.handlerFilter.trim());
        },
        copyToClipboard: function (strData) {
            var $temp = $("<input>");
            $("body").append($temp);
            $temp.val(strData).select();
            document.execCommand("copy");
            $temp.remove();
        },
        setLi: function (li) {
            if (li === this.li) return;
            this.defaultValues = false;
            this.actionResult = false;
            this.dimEdit = false;
            this.newUnit = false;
            this.newDim = false;
            this.cHandler = false;
            this.handled = '';
            this.currentDim = Object.keys(this.dimensions)[0];
            this.li = li;
            this.liFree = 'settings';
        },
        setText: function (texts, type='error', timeOut = 3000) {
            if (Array.isArray(texts)) {
                text = texts.join('; ');
            } else {
                text = texts;
            }
            if (type === 'error') {
                this.errorText = text;
            } else {
                this.doneText = text;
            }
            setTimeout(() => {
                this.errorText = '';
                this.doneText = '';
            }, timeOut);
        }
    }
});
{include './actionButton.js'}
{include './wowBanner.js'}
settings.component('actionButton', actionButton);
settings.component('wowBanner', wowBanner);
settingsView = settings.mount('div#hidset-settings');