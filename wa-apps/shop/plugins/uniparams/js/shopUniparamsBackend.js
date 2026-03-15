var getthelist = function(li_id = undefined) {
    $.ajax({
        type: 'POST',
        url: '?plugin=uniparams&action=getlist'
    }).done(function(data){
        $('.uniparams-thelist').html(data);
        $('.uniparams-thelist li').each(function(index){
            $(this).attr('id', 'li_' + (index));
        });
        $('.uniparams-thelist').sortable({
            axis: 'y',
            helper: "clone",
            update: function (event, ui) {
                var data = $(this).sortable('serialize');
                $.ajax({
                    data: data,
                    type: 'POST',
                    url: '?plugin=uniparams&action=updatelist'
                });
                var totalli = $('.uniparams-thelist li').length - 1;
                $('.uniparams-thelist li').each(function(index){
                    $(this).attr('id', 'li_' + (index));
                }); 
            }
        });
        $(".uniparams-workarea").html('<div class="block "><i class="icon16 loading"></i>Loading...</div>');
        getitems(li_id);
    });
};
var getitems = function(li_id = undefined) {
    $('.uniparams-thelist li').removeClass('selected');
    if (li_id == undefined) {
        $('.uniparams-thelist li[id="li_0"]').addClass('selected');
        li_id = $('.uniparams-thelist li[id="li_0"]').attr('data-id');
    } else {
        $('.uniparams-thelist li[data-id="' + li_id + '"]').addClass('selected');
    }

    $.get("?plugin=uniparams&action=getitems&list_id="+li_id, function(result) {

        $(".uniparams-workarea").html(result);

        $('.uniparams-items_list li').each(function(index){
            $(this).attr('id', 'li_' + (index));
        });
        $('.uniparams-items_list').sortable({
            axis: 'y',
            helper: "clone",
            update: function (event, ui) {
                var data = $(this).sortable('serialize');
                data += '&list_id='+li_id;
                $.ajax({
                    data: data,
                    type: 'POST',
                    url: '?plugin=uniparams&action=updateitemspos'
                });
                $('.uniparams-items_list li').each(function(index){
                    $(this).attr('id', 'li_' + (index));
                });
            }
        });
    });
};

$(document).ready(function() {
    // lists (sidebar)
    $(document).on('click', '.uniparams-add_list', function(e) {
        e.preventDefault();

        let dialog = `
            <div class="uniparams-newlist dialog-content" style="display: none;">
                <div class="dialog-content-indent">
                    <h1>Новый список</h1>
                    <form>
                        <div class="block form">
                            <div class="field-group">
                                <div class="field">
                                    <div class="name">Название<br><span class="hint">title</span></div>
                                    <div class="value"><input type="text" class="bold" name="title"/></div>
                                </div>
                                <div class="field">
                                    <div class="name">Код вывода <br><span class="hint">id</span></div>
                                    <div class="value">{shopUniparamsPlugin::getList("<input type="text" name="list_key"/>")}
                                        <em class="errormsg uniparams-err1"></em>
                                    </div>
                                </div>
                                <div class="field">
                                    <div class="name">Описание <br><span class="hint">description</span></div>
                                    <div class="value">
                                        <textarea name="ldescription"></textarea>
                                    </div>
                                </div>
                                <br>
                                <h6 class="heading">Доп. поля для списка</h6> 
                                <div class="field-group uniparams-list-params">
                                </div>
                                <div class="buttons">
                                    <a href="#" class="button blue uniparams-add-list-param">Добавить поле</a>
                                </div>
                            </div>
                            <em class="errormsg uniparams-err3"></em>
                            <hr>
                            <h2>Настройка карточки</h2>
                            <h6 class="heading">Список полей для карточки</h6>
                            <div class="field-group uniparams-list-fields">
                            </div>
                            <a href="#" class="button blue uniparams-add-list-field">Добавить поле</a>
                            <em class="errormsg uniparams-err2"></em>
                        </div>
                    </form>
                </div>
                <div class="dialog-buttons block">
                    <input type="submit" value="Сохранить" class="button green uniparams-create_list" />
                    <button class="button gray cancel">Закрыть</button>
                </div>
            </div>
            `;
        $(dialog).waDialog({
            'esc': true,
            'onCancel': function () {
                $('div.dialog').remove();
                return false;
            },
            onClose: function() {
                $('div.dialog').remove();
            }
        });
        $('.uniparams-newlist input[name="list_key"]').translit('watch', '.uniparams-newlist input[name="title"]', true, true);
    });
    // Add new field list popup
    $(document).on('click', ".uniparams-add-list-field", function(e) {
        e.preventDefault();

        let total_element = $(".uniparams-list-fields").find('.field').length;
        if (!total_element) {
            let head = `
                <div class="field">
                    <div class="uniparams-minitab field-head">Название</div>
                    <div class="uniparams-minitab field-head">Ключ</div>
                    <div class="uniparams-minitab field-head">Тип</div>
                    <div class="uniparams-minitab field-head">Описание</div>
                </div>
            `;
            $(head).appendTo($(".uniparams-list-fields"));
        }

        var lastid = $(document).find(".uniparams-list-fields .uniparams-selects:last").attr("id");
        var lastlast = 0;
        if (lastid) {
            lastlast = lastid.split("_");
            lastlast = lastlast[1];
        }
        var nextindex = Number(lastlast) + 1;

        var max = 50;
        if (total_element < max ) {
            let field = `
                <div class="field uniparams-selects">
                    <div class="uniparams-minitab">
                        <input type="hidden" name="field_id[]" value="0"/>
                        <input type="text" placeholder="Name" name="name[]"/>
                    </div>
                    <div class="uniparams-minitab">
                        <input type="text" placeholder="Keyname" name="keyname[]"/>
                    </div>
                    <div class="uniparams-minitab">
                        <select name="type[]">
                            <option value="text">Однострочное текстовое поле (Input)</option>
                            <option value="textarea">Многострочное текстовое поле (Textarea)</option>
                            <option value="image">Изображение</option>
                        </select>
                    </div>
                    <div class="uniparams-minitab">
                        <textarea placeholder="Description" name="description[]"></textarea>
                    </div>
                    <a href="#" class="uniparams-delete-list-field" title="Удалить поле">
                        <i class="icon16 delete "></i>
                    </a>
                </div>
            `;
            var newst = $(field).appendTo($(".uniparams-list-fields"));
            $(newst).attr("id", "selects_" + nextindex);
        }
    });
    // Remove field list popup
    $(document).on('click', '.uniparams-delete-list-field', function(e){
        e.preventDefault();
        $(this).parents(".uniparams-selects").remove();
        let total_element = $(".uniparams-list-fields").find('.field').length;
        if (total_element == 1) {
            $(document).find('.uniparams-list-fields .field').remove();
        }
    });
    // Add new param list popup
    $(document).on('click', ".uniparams-add-list-param", function(e) {
        e.preventDefault();
        var total_element = $(".uniparams-list-params").find('.field').length;
        var lastid = $(document).find(".uniparams-list-params .field:last").attr("index");
        var lastlast = 0;
        if (lastid) {
            lastlast = lastid.split("_");
            lastlast = lastlast[1];
        }
        var nextindex = Number(lastlast) + 1;

        if (!total_element) {
            let head = `
                <div class="field"> 
                    <div class="uniparams-minitab field-head">Ключ</div>
                    <div class="uniparams-minitab field-head">Тип</div>
                    <div class="uniparams-minitab field-head">Содержание</div>
                </div>
            `;
            $(head).appendTo($(".uniparams-list-params"));
        }


        var max = 50;
        if (total_element < max ) {
            let param = `
                    <div class="field">
                        <div class="uniparams-minitab">
                            <input type="hidden" name="param_id[]"/>
                            <input type="text" placeholder="Keyname" name="lpkeyname[]"/>
                        </div>
                        <div class="uniparams-minitab">
                            <select name="lptype[]">
                                <option value="text">Однострочное текстовое поле (Input)</option>
                                <option value="textarea">Многострочное текстовое поле (Textarea)</option>
                                <option value="image">Изображение</option>
                            </select>
                        </div>
                        <div class="uniparams-minitab">
                            <input type="text" placeholder="Value" name="lpparam[]"/>
                            
                        </div>
                        <a href="#" class="uniparams-delete-list-param" title="Удалить поле">
                            <i class="icon16 delete "></i>
                        </a>
                    </div>
            `;
            var newst = $(param).appendTo($(".uniparams-list-params"));
            $(newst).attr("index", "param_"+nextindex);
            $(newst).find(".uniparams-minitab input").val("");
            $(newst).find(".uniparams-minitab textarea").val("");
            $(newst).find(".uniparams-minitab input[name='param_id[]']").val("0");
        }
    });
    // Remove param list popup
    $(document).on('click', '.uniparams-delete-list-param', function(e){
        e.preventDefault();
        $(this).parents(".field").remove();
    });
    // create list
    $(document).on('click', '.uniparams-create_list', function(e) {
        e.preventDefault();

        var form_data = new FormData();
        ev_fine = true;
        $('.uniparams-newlist').find('.errormsg').html('');
        $(".uniparams-newlist :input").each(function(index) {
            that = $(this);
            if (that.attr('type') == 'file' && that.prop('files')[0]) {
                var name = that.prop('files')[0].name;
                var ext = name.split('.').pop().toLowerCase();
                if (jQuery.inArray(ext, ['gif','png','jpg','jpeg']) == -1) {
                    alert("Invalid Image File");
                    ev_fine = false;
                }
                var oFReader = new FileReader();
                oFReader.readAsDataURL(that.prop('files')[0]);
                var f = that.prop('files')[0];
                var fsize = f.size||f.fileSize;
                if (fsize > 20971520) {
                    alert("Image File Size is very big");
                    ev_fine = false;
                }
                let back_name = that.parents('.field').find(':input[name="lpkeyname[]"]').val();
                form_data.append(back_name, that.prop('files')[0], "temp_name."+ext);
            } else {
                form_data.append(that.attr("name"), that.val());
            }
        });
        if (ev_fine) {
            $.ajax({
                data: form_data,
                method: "POST",
                contentType: false,
                cache: false,
                processData: false,
                url: '?plugin=uniparams&action=addlist'
            }).done(function (data) {
                if (data.status == "ok") {
                    $('.uniparams-newlist').trigger('close');
                    $('.uniparams-newlist').remove();
                    getthelist(data.data.list_id);
                } else {
                    if (data.errors.id)
                        $('.uniparams-err1').html('').html(data.errors.id);
                    else if (data.errors.id2)
                        $('.uniparams-err2').html('').html(data.errors.id2);
                    else if (data.errors.id3)
                        $('.uniparams-err3').html('').html(data.errors.id3);
                    else if (data.errors.id4)
                        $('.uniparams-err3').html('').html(data.errors.id4);
                }
            });
        }
    });
    // edit/save list
    $(document).on('click', '.uniparams-save_list', function(e) {
        e.preventDefault();

        var form_data = new FormData();
        ev_fine = true;
        $('.uniparams-oldlist').find('.errormsg').html('');
        $(".uniparams-oldlist :input").each(function(index) {
            that = $(this);
            if (that.attr('type') == 'file' && that.prop('files')[0]) {
                var name = that.prop('files')[0].name;
                var ext = name.split('.').pop().toLowerCase();
                if (jQuery.inArray(ext, ['gif','png','jpg','jpeg']) == -1) {
                    alert("Invalid Image File");
                    ev_fine = false;
                }
                var oFReader = new FileReader();
                oFReader.readAsDataURL(that.prop('files')[0]);
                var f = that.prop('files')[0];
                var fsize = f.size||f.fileSize;
                if (fsize > 20971520) {
                    alert("Image File Size is very big");
                    ev_fine = false;
                }
                let back_name = that.parents('.field').find(':input[name="lpkeyname[]"]').val();
                form_data.append(back_name, that.prop('files')[0], "temp_name."+ext);
            } else {
                form_data.append(that.attr("name"), that.val());
            }
        });
        if (ev_fine) {
            $.ajax({
                data: form_data,
                method: "POST",
                contentType: false,
                cache: false,
                processData: false,
                url: '?plugin=uniparams&action=savelist'
            }).done(function (data) {
                if (data.status == "ok") {
                    $('.uniparams-oldlist').trigger('close');
                    $('.uniparams-oldlist').remove();
                    getthelist(data.data.list_id);
                } else {
                    if (data.errors.id)
                        $('.uniparams-err1').html('').html(data.errors.id);
                    else if (data.errors.id2)
                        $('.uniparams-err2').html('').html(data.errors.id2);
                    else if (data.errors.id3)
                        $('.uniparams-err3').html('').html(data.errors.id3);
                    else if (data.errors.id4)
                        $('.uniparams-err3').html('').html(data.errors.id4);
                }
            });
        }
    });
    // delete list
    $(document).on('click', '.uniparams-remove_list', function(e) {
        e.preventDefault();
        var data = $(document).find('.uniparams-oldlist form').serialize();
        $.ajax({
            data: data,
            type: 'POST',
            url: '?plugin=uniparams&action=removelist'
        }).done(function(){
            getthelist();
        });
        $('.uniparams-oldlist').trigger('close');
        $('.uniparams-oldlist').remove();
    });
    // select/open list
    $(document).on('click', '.uniparams-thelist li', function(e) {
        e.preventDefault();
        getitems($(this).attr('data-id'));
    });
    // popup edit list
    $(document).on('click', '.uniparams-list-settings', function(e) {
        e.preventDefault();
        var instance = $("<div class=\"load block align-center triple-padded\"><i class=\"icon16 loading\"></i>[`Loading...`]</div>")
            .waDialog({
                onClose: function () {
                    $('div.dialog.load').remove();
                }
            });
        var list_id = $(this).parents('li').attr('data-id');
        $.ajax({
            data: {'list_id': list_id},
            type: 'POST',
            url: '?plugin=uniparams&action=getlistsettings'
        }).done(function(html){
            $(instance).trigger('close');
            $(html).waDialog({
                'esc': true,
                onCancel: function() {
                    $(this).trigger('close');
                    $('div.dialog').remove();
                    $('.uniparams-oldlist').remove();
                    return false;
                },
                onSubmit: function(d) {
                    return false;
                },
                onClose: function() {
                    $('div.dialog').remove();
                }
            }); 
        });
    });
    // change input type list params
    $(document).on('change', ':input[name="lptype[]"]', function(e) {
       e.preventDefault();

       let type = $(this).val();
       that = $(this);

       if (type == 'text') {
           that.parents('.field').find(':input[name="lpparam[]"]').parent()
               .html('<input type="text" placeholder="Value" name="lpparam[]"/>');
       } else if (type == 'image') {
           that.parents('.field').find(':input[name="lpparam[]"]').parent()
               .html('<img class="uniparams-item-img" src=""><input class="uniparams-item-img" type="file" name="lpparam[]" accept="image/*" value="" />');
       } else if (type == 'textarea') {
           that.parents('.field').find(':input[name="lpparam[]"]').parent()
               .html('<textarea name="lpparam[]"></textarea></i>');
       }
    });
    // preview image params list
    $(document).on('change', '.uniparams-list-params input[type="file"]', function () {
        let that = $(this);
        var oFReader = new FileReader();
        oFReader.onload = function (e) {
            that.parent().find('img').attr('src', e.target.result);
        };
        oFReader.readAsDataURL($(this).prop('files')[0]);
    });

    // items
    // new-item
    $(document).on('click', '.uniparams-add-item', function(e) {
        e.preventDefault();
        $(".uniparams-new-item").show();
    });
    $(document).on('click', '.uniparams-cancel-new-param', function(e) {
        e.preventDefault();
        $(".uniparams-new-item").hide();
    });
    $(document).on('click', '.uniparams-add-new-item', function(e) {
        e.preventDefault();

        $('.uniparams-new-item').find('.errormsg').html('');
        var form_data = new FormData();
        let par = $(this);
        ev_fine = true;
        $(".uniparams-new-item :input").each(function(index) {
            that = $(this);
            if ($(this).attr('type') == 'file' && that.prop('files')[0]) {
                var name = that.prop('files')[0].name;
                var ext = name.split('.').pop().toLowerCase();
                if (jQuery.inArray(ext, ['gif','png','jpg','jpeg']) == -1) {
                    alert("Invalid Image File");
                    ev_fine = false;
                }
                var oFReader = new FileReader();
                oFReader.readAsDataURL(that.prop('files')[0]);
                var f = that.prop('files')[0];
                var fsize = f.size||f.fileSize;
                if (fsize > 20971520) {
                    alert("Image File Size is very big");
                    ev_fine = false;
                }
                form_data.append(that.attr("name"), that.prop('files')[0], "temp_name."+ext);
            } else {
                form_data.append(that.attr("name"), that.val());
            }
        });
        if (ev_fine) {
            $.ajax({
                data: form_data,
                method: "POST",
                contentType: false,
                cache: false,
                processData: false,
                url: '?plugin=uniparams&action=additem'
            }).done(function (data) {
                if (data.status == "ok") {
                    getitems(data.data.list_id);
                } else {
                    if (data.errors.id)
                        par.parents('.uniparams-itemdiv').find('.uniparams-err01').html('').html(data.errors.id);
                }
            });
        }
    });
    // preview image
    $(document).on('change', '.uniparams-itemdiv input[type="file"]', function () {
        let that = $(this);
        var oFReader = new FileReader();
        oFReader.onload = function (e) {
            that.parents('.value').find('.uniparams-item-img-preview').attr('src', e.target.result).show();
        };
        oFReader.readAsDataURL(that.prop('files')[0]);
    });
    // delete item
    $(document).on('click', '.uniparams-delete-item', function(e) {
        e.preventDefault();
        var r = confirm("You're sure you want to delete the item?");
        if (r == true) {
            itemid = $(this).parents("form.uniparams-itemdiv").attr("data-id");
            $.ajax({
                data: {item_id: itemid},
                type: 'POST',
                url: '?plugin=uniparams&action=deleteitem'
            }).done(function (data) {
                getitems(data.data.list_id);
            });
        }
    });
    // edit item
    $(document).on('click', '.uniparams-edit-vals-item', function(e) {
        e.preventDefault();

        var that = $(this),
            parent = that.closest('.uniparams-itemdiv');

        parent.find(".span-value").hide();
        parent.find(".uniparams-edit-vals-item").hide();
        parent.find(".uniparams-item-img").hide();
        parent.find(".show-edit").show();
        parent.find(":input:not([type=hidden])").show();
        parent.find(".uniparams-save-edits-div").show();
    });
    // cancel item changes
    $(document).on('click', '.uniparams-cancel-edit-item', function(e) {
        e.preventDefault();

        var that = $(this),
            parent = that.closest('.uniparams-itemdiv');

        parent.find(".span-value").show();
        parent.find(".uniparams-edit-vals-item").show();
        parent.find(".uniparams-item-img").show();
        parent.find(".show-edit").hide();
        parent.find(":input:not([type=hidden]):not([type=checkbox])").hide();
        parent.find(".uniparams-save-edits-div").hide();
    });
    // save item changes
    $(document).on('click', '.uniparams-save-edit-item', function(e) {
        e.preventDefault();

        var form_data = new FormData();
        form_data.append("_csrf", Cookies("_csrf"));
        ev_fine = true;

        let later = $(this);
        later.closest('.uniparams-itemdiv').find('.errormsg').html('');
        let parent = later.closest(".uniparams-itemdiv");
        let inputs = $(parent).find(":input");

        $.each(inputs, function(index, that) {
            that = $(that);
            if (that.attr('type') == 'file' && that.prop('files')[0]) {
                var name = that.prop('files')[0].name;
                var ext = name.split('.').pop().toLowerCase();
                if (jQuery.inArray(ext, ['gif','png','jpg','jpeg']) == -1) {
                    alert("Invalid Image File");
                    ev_fine = false;
                }
                var oFReader = new FileReader();
                oFReader.readAsDataURL(that.prop('files')[0]);
                var f = that.prop('files')[0];
                var fsize = f.size||f.fileSize;
                if (fsize > 20971520) {
                    alert("Image File Size is very big");
                    ev_fine = false;
                }
                form_data.append(that.attr("name"), that.prop('files')[0], "temp_name."+ext);
            } else {
                form_data.append(that.attr("name"), that.val());
            }
        });
        if (ev_fine) {
            $.ajax({
                data: form_data,
                method: "POST",
                contentType: false,
                cache: false,
                processData: false,
                url: '?plugin=uniparams&action=edititem'
            }).done(function (data) {
                if (data.status == "ok") {
                    parent.find(":input").each(function(index) {
                        that = $(this);
                        if (that.attr('type') == 'file' && that.prop('files')[0]) {
                            that.parents('.value').find('.uniparams-item-img').attr('src', that.parents('.value').find('.uniparams-item-img-preview').attr('src'));
                        } else {
                            that.parents('.value').find('.span-value').text(that.val());
                        }
                    });

                    parent.find(".uniparams-edit-vals-item").show();
                    parent.find("span").show();
                    parent.find(".uniparams-item-img").show();
                    parent.find(".show-edit").hide();
                    parent.find(":input:not([type=hidden]):not([type=checkbox])").hide();
                    parent.find(".uniparams-save-edits-div").hide();
                } else {
                    if (data.errors.id)
                        parent.find('.uniparams-err01').html('').html(data.errors.id);
                }
            });

        }
    });

    $(document).on('change', '.uniparams-item-enabled', function(e) {
        that = $(this);
        if (that.is(':checked')) {
            $.ajax({
                data: { item_id: that.parents('.uniparams-itemdiv').attr('data-id'),
                    status: 1},
                type: 'POST',
                url: '?plugin=uniparams&action=updateitemstatus'
            }).done(function(data) { 
                that.closest('.uniparams-itemdiv').removeClass('is-disabled').addClass('is-enabled');
            });
        } else {
            $.ajax({
                data: { item_id: that.parents('.uniparams-itemdiv').attr('data-id'),
                    status: 0},
                type: 'POST',
                url: '?plugin=uniparams&action=updateitemstatus'
            }).done(function(data) {
                that.closest('.uniparams-itemdiv').removeClass('is-enabled').addClass('is-disabled');
            });
        }
    });

    $(document).on('click', '.uniparams-copy-to-cp', function (e) {
        e.preventDefault();
        var that = $(this);
        var $temp = $("<input>");
        $("body").append($temp);

        inputv = that.parents('div').find('input[name="list_key"]').val();
        let txt = "{shopUniparamsPlugin::getList(\"" + inputv + "\")}";

        $temp.val(txt).select();
        document.execCommand("copy");

        $temp.remove();
        that.addClass('is-copy');
        setTimeout(function(){
           that.removeClass('is-copy');
        },2000);

    });

    getthelist();
});