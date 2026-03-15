(function ($) {
  $.product_syrattachments = {

    /**
     * {Number}
     */
    product_id: 0,

    tail: null,

    counter: $("li.syrattachments span.hint"),

    progressbar: {
      element: null,
      update(value, max) {
        if (!value && value !== 0) this.element.removeAttribute('value');
        else this.element.setAttribute('value', value)
        if (!max) this.element.removeAttribute('max');
        else this.element.setAttribute('max', max);
      },
      show() {
        this.update();
        $(this.element).show();
        return this;
      },
      hide() {
        $(this.element).hide();
        this.update();
        return this;
      }
    },

    /**
     * {Object}
     */
    options: {},

    errors: [],

    init(options) {

      $.shop.trace('$.product_syrattachments.init', 'Init');

      this.options = options;
      this.product_id = parseInt(this.options.product_id, 10) || 0;
      const that = this;

      const tab = $("#s-product-edit-menu .syrattachments");
      tab.find(".hint").text(options.count || (options.attachments && options.attachments.length) || 0);
      $("#s-product-edit-forms .s-product-form.syrattachments").addClass('ajax');

      this.initAttachmentsList(options);
      this.initAttachDeleteAction();
      this.initProgressBar();
      this.initListEditable();

      // Init dropzone
      const dropzone = document.getElementById('s-plugin-syrattach-fileupload');
      const preventDefaults = e => {
        e.preventDefault();
        e.stopPropagation();
      };
      const hightlight = () => dropzone.classList.add('highlighted');
      const unhightlight = () => dropzone.classList.remove('highlighted');

      /**
       * @param {FileList} files
       * @returns {*}
       */
      function uploadFiles(files) {
        const formData = new FormData();
        formData.append('syrattach_product_id', that.product_id);
        for (const fileData of files) formData.append('files[]', fileData);

        that.progressbar.show();
        $.ajax({
          type: 'POST',
          url: '?plugin=syrattach&module=attachments&action=upload',
          data: formData,
          cache: false,
          contentType: false,
          processData: false,
          xhr() {
            const myXHR = $.ajaxSettings.xhr();
            if (myXHR.upload) {
              myXHR.upload.addEventListener('progress', e => {
                if (e.lengthComputable) {
                  that.progressbar.update(e.loaded, e.total)
                  console.log(e);
                  // $progress.attr({value: e.loaded, max: e.total});
                }
              });

            }
            return myXHR;
          },
          fail(e) {
            return false;
          },
          success(r) {
            if (r && r.files && Array.isArray(r.files)) {
              if (!that.options.attachments || !Array.isArray(that.options.attachments))
                that.options.attachments = [];
              r.files.forEach(a => {
                if (a.error)
                  that.errors.push((a.name ? a.name + ': ' : '') + a.error);
                else
                  that.options.attachments.push(a);

              });
              //todo displayErrors
              that.initAttachmentsList(that.options);
            }
          }
        }).always(() => {
          $.product_syrattachments.counter.text(that.options.attachments.length || ' ');
          document.querySelector('#s-plugin-syrattach-fileupload input[type=file]').value = null;
          that.progressbar.hide();
        })

      }

      ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => dropzone.addEventListener(eventName, preventDefaults, false));
      ['dragenter', 'dragover'].forEach(eventName => dropzone.addEventListener(eventName, hightlight, false));
      ['dragleave', 'drop'].forEach(eventName => dropzone.addEventListener(eventName, unhightlight, false));
      dropzone.addEventListener('drop', e => {
        const dt = e.dataTransfer;
        const files = dt.files;
        uploadFiles(files);
      }, false);

      dropzone.querySelector('input[type=file]').onchange = function () {
        uploadFiles(this.files)
      };

      // $.product.editTabSyrattachmentsBlur = function (path) {
      //     $("#s-plugin-syrattach-fileupload").fileupload('destroy');
      // };

      $.product.editTabSyrattachmentsAction = function (path) {
        $.shop.trace('$.product_syrattachments.tail', $.product_syrattachments.tail);
        if ($.product_syrattachments.tail !== null) {
          var url = '?plugin=syrattach&module=attachments&id=' + path.id;
          if (path.tail) {
            url += '&param[]=' + path.tail;
          }

          $.get(url, function (html) {
            $("#s-product-edit-forms .s-product-form.syrattachments").html(html);
          });
        }
        $.product_syrattachments.tail = path.tail;
      };
    },

    initAttachmentsList(options) {
      this.attachments_list = $(options.attachments_list || '#s-plugin-syrattach-product-files-list');
      this.attachments_list.html(tmpl('template-syrattach-attachments', {
        attachments: options.attachments,
        formatFileSize: $.wa.util.formatFileSize,
        placeholder: options.placeholder
      }));
    },

    initProgressBar() {
      this.progressbar.element = document.getElementById('s-plugin-syrattach-upload-progress');
    },

    initAttachDeleteAction() {
      $("#s-plugin-syrattach-product-files-list").on("click", ".s-plugin-syrattach-delete-action", function () {
        $.shop.trace("Delete Action", $(this));
        const id = $(this).data('id');
        const list_item = $(this).closest("li");
        $.post(
          "?plugin=syrattach&module=attachments&action=delete",
          {'id': id},
          function (data) {
            if (data.status === 'ok') {
              list_item.slideUp(500, function () {
                list_item.remove();
                // noinspection EqualityComparisonWithCoercionJS
                const idx = $.product_syrattachments.options.attachments.findIndex(a => a.id == id);
                if (idx >= 0)
                  $.product_syrattachments.options.attachments.splice(idx, 1);
                const cnt = $.product_syrattachments.options.attachments.length;
                $.product_syrattachments.counter.text(cnt ? cnt : ' ');
              });
            }
          }, 'json');
      });
    },

    initListEditable() {
      this.attachments_list.off('click', '.editable').on('click', '.editable', function () {
        $(this).inlineEditable({
          inputType: 'textarea',
          makeReadableBy: ['esc'],
          updateBy: ['ctrl+enter'],
          placeholderClass: 'gray',
          placeholder: $.product_syrattachments.options.placeholder,
          minSize: {
            height: 40
          },
          allowEmpty: true,
          beforeMakeEditable: function (input) {
            var self = $(this);

            input.css({
              'font-size': self.css('font-size'),
              'line-height': self.css('line-height')
            }).width(
              //self.parents('li:first').find('img').width()
              '95%'
            );

            var button_id = this.id + '-button';
            var button = $('#' + button_id);
            if (!button.length) {
              input.after('<br><input type="button" id="' + button_id + '" value="' + $_('Save') + '"> <em class="hint" id="' + this.id + '-hint">Ctrl+Enter</em>');
              $('#' + button_id).click(function () {
                self.trigger('readable');
              });
            }
            $('#' + this.id + '-hint').show();
            button.show();
          },
          afterBackReadable: function (input, data) {
            var self = $(this);
            var attachment_id = parseInt(self.parents('li:first').attr('data-attachment-id'), 10);
            var value = $(input).val();
            var prefix = '#' + this.id + '-';

            $(prefix + 'button').hide();
            $(prefix + 'hint').hide();
            if (data.changed) {
              $.products.jsonPost('?plugin=syrattach&module=attachments&action=descriptionsave', {
                id: attachment_id,
                data: {
                  description: value
                }
              });
            }
          }
        }).trigger('editable');
      });
    },
  };

  const syrattachupload = $("#s-plugin-syrattach-fileupload");

  function errorDialog(text) {
    $(`<div class="s-plugin-syrattach-dialog__error"><form><div class="dialog-content"><header><h2>${$_('Errors')}</h2></header><section><p>${text}</p></section></div><div class="dialog-buttons"><button class="cancel button">Закрыть</button></div></form></div>`)
      .waDialog({
        width: '550px',
        height: '150px',
        onClose() {
          $(this).remove();
        }
      })
  }

})(jQuery);
