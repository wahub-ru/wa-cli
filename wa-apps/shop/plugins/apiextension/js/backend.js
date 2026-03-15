(function($) {
    'use sctrict'
    $.pluginsBackend = {
        codeMirrorArr:'',
        init: function() {
            this.initCodeMirror();
            this.initTab();
            this.initBonusCategory();
        },
        initCodeMirror: function() {
            var that = this;
            that.CodeMirrorArr = {};
            $('.codemirror-area').each(function () {
                var th = $(this);
                var editor = CodeMirror.fromTextArea(this, {
                    mode: th.data('mode'),
                    tabMode: 'indent',
                    height: 'dynamic',
                    lineWrapping: true,
                    onChange: function(cm) {
                        th.val(cm.getValue());
                    }
                });
                that.CodeMirrorArr[th.data('object')] = editor;
            });

            $('.plugins__editTextarea').click(function(event) {
                event.preventDefault();
                var parent = $(this).parent();

                if(parent.find('.CodeMirror').length) {
                    parent.find('.CodeMirror').slideToggle();
                }

                return false;
            });
        },
        initTab: function() {
            var that = this;
            var pb = $('.plugins_apiextension');
            pb.find('.tab-content .block').hide().first().show();

            pb.find('.tabs>li>a').click(function (e) {
                e.preventDefault();
                var parent = $(this).closest('.tabs');
                parent.find('li').removeClass('selected');
                $(this).parent().addClass('selected');

                parent.next().find('.block').hide().eq($(this).parent().index()).show();

                $('.CodeMirror').each(function(i, el){
                    el.CodeMirror.refresh();
                });

                $('.CodeMirror').not('.CodeMirrorBlock .CodeMirror').hide();
            });
        },
        initBonusCategory: function() {
            const that = this;
            const bCateg = $('.apiextension-bonus-categ');
            const bCategAdd = $('.apiextension-bonus-categ-add');
            const bCategDelAll = $('.apiextension-bonus-categ-del-all');
            const bCategTable = $('.apiextension-bonus-categ-table');

            bCategAdd.click(function (e) {
                const id = bCateg.val();
                const name = bCateg.find('option:selected').data('name');
                if(id !== 'choose') {
                    bCategTable.prepend('' +
                        '<tr class="apiextension-bonus-categ-new">' +
                        '<td width="24%">'+name+'</td>' +
                        '<td>' +
                            '<input type="number" step="any" name="shop_plugins[bonus_by_category]['+id+'][bonus]" value="" placeholder="за отзыв"> ' +
                            '<input type="number" step="any" name="shop_plugins[bonus_by_category]['+id+'][bonus_photo]" value="" placeholder="с фото"> ' +
                            '<select name="shop_plugins[bonus_by_category]['+id+'][type]" style="max-width:150px;">' +
                                '<option value="number">число</option>' +
                                '<option value="percent">% от цена товара</option>' +
                                '<option value="percent_purchase">% от (цена - цена закупки)</option>' +
                            '</select> ' +
                            '<select name="shop_plugins[bonus_by_category]['+id+'][round]" style="max-width:150px;">' +
                                '<option value="round_no">Не округлять</option>' +
                                '<option value="round_up">Округлять вверх</option>' +
                                '<option value="round_down">Округлять вниз</option>' +
                            '</select>' +
                        '</td>' +
                        '<td>' +
                            '<div class="apiextension-bonus-categ-del"><i class="icon16 delete"></i></div>' +
                        '</td>' +
                        '</tr>');
                }
            });

            bCategDelAll.click(function (e) {
                if(confirm('Вы действительно хотите очистить все?')) {
                    bCategTable.empty();
                }
            });

            $('body').on('click', '.apiextension-bonus-categ-del', function() {
                if(confirm('Вы действительно хотите удалить категорию?')) {
                    $(this).closest('tr').remove();
                }
            });
        },
    }

    $.pluginsBackend.init();
})(jQuery);