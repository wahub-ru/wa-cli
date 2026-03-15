(function($) {
  'use sctrict'
  $.backendAdditionalLinks = {
    init: function(options) {
      const that = this;

      setTimeout(() => {
        that.additionalLinks = options.additionalLinks;
        that.spriteUrl = options.spriteUrl;
        that.links = $('.js-additional-links');
        that.linksWrapper = that.links.find('.js-additional-links-wrapper');
        that.linksAdd = that.links.find('.js-additional-links-add');

        that.sortable();
        that.add();
        that.edit();
        that.delete();
        that.dialogCategoryFormSave();
      }, 200);
    },

    sortable: function() {
      const that = this;
      that.linksWrapper.sortable({
        handle: '.js-additional-links-move-toggle',
      });
    },

    add: function() {
      const that = this;

      that.linksAdd.on('click', function () {
        const self = $(this);
        $.waDialog({
          html: that.getDialog('Добавление новой ссылки'),
          onOpen: that.getOnOpen(self, 'add'),
        });
      });
    },

    edit: function() {
      const that = this;
      that.linksWrapper.on('click', '.js-additional-links-edit', function () {
        const self = $(this);
        $.waDialog({
          html: that.getDialog('Редактирование ссылки'),
          onOpen: that.getOnOpen(self, 'edit'),
        });

      });
    },

    delete: function() {
      const that = this;
      that.linksWrapper.on('click', '.js-additional-links-delete', function () {
        const parent = $(this).parent();
        const id = parent.data('id');

        parent.remove();
        that.additionalLinks = that.additionalLinks.filter(e => Number(e.id) != Number(id));
      });
    },

    getOnOpen: function(element, type) {
      const that = this;
      const id = element.parent().data('id');

      return function(dialog, instance) {
        const name = dialog.find('.js-additional-links-name');
        const link = dialog.find('.js-additional-links-link');

        // edit
        if (type === 'edit') {
          const links = that.additionalLinks.filter(e => Number(e.id) === Number(id))[0] ?? [];
          name.val(links.name ?? '');
          link.val(links.url ?? '');
        }

        name.add(link).on('focus', function () {
          $(this).removeClass('state-error');
        });

        dialog.on('click', '.js-cancel', function (event) {
          event.preventDefault();
          instance.close();
        });

        dialog.on("click", ".js-save", function (event) {
          event.preventDefault();
          let error = false;
          const buttons = that.linksWrapper.find('.button');
          const idNew = checkId(buttons, buttons.length);
          const nameVal = name.val();
          const linkVal = link.val();

          if (!nameVal) {
            error = true;
            name.addClass('state-error');
          }

          if (!linkVal) {
            error = true;
            link.addClass('state-error');
          }

          if (!error) {
            if (type === 'add') {
              that.additionalLinks.push({
                id: idNew,
                name: nameVal,
                url: linkVal,
              });
              that.linksWrapper.append(`
                <div class="button" type="button" data-id="${idNew}">
                  <span class="s-icon icon size-14 js-additional-links-move-toggle">
                    <svg><use xlink:href='${that.spriteUrl}#grip'></use></svg>
                  </span>
                  <button type="button" class="js-additional-links-edit" title="Редактировать">${nameVal}</button>
                  <span class="icon js-additional-links-delete" title="Удалить">
                    <i class="fas fa-times"></i>
                  </span>
                </div>`);
            } else {
              element.text(nameVal);
              that.additionalLinks = that.additionalLinks.map(e => {
                if (Number(e.id) === Number(id)) {
                  return {
                    id: e.id,
                    name: nameVal,
                    url: linkVal,
                  }
                }
                return e;
              });

            }
            instance.close();
          }

          function checkId(buttons, count) {
            let idNew = count;
            let check = false;

            buttons.each(function() {
              if ($(this).data('id') === idNew) {
                check = true;
              }
            })

            if (check) {
              return checkId(buttons, count + 1)
            }
            return idNew;
          }
        });
      }
    },

    getDialog: function(title) {
      const that = this;
      return `
        <div class="dialog additional-dialog">
          <div class="dialog-background">
          </div>
          <div class="dialog-body">
            <a href="#" class="dialog-close js-close-dialog">
              <i class="fas fa-times"></i>
            </a>
            <header class="dialog-header">
              <h2>${title}</h2>
            </header>
            <div class="dialog-content">
              <h5 class="s-section-title">Название ссылки <span class="text-red">*</span></h5>
              <input type="text" value="" class="full-width js-additional-links-name">
              
              <h5 class="s-section-title">Ссылка <span class="text-red">*</span></h5>
              <input type="text" value="" class="full-width js-additional-links-link">
            </div>
            <div class="dialog-footer">
              <button class="button blue js-save" type="button">Сохранить</button>
              <button class="button gray js-cancel" type="button">Отмена</button>
            </div>
          </div>
        </div>`;
    },

    dialogCategoryFormSave: function() {
      const that = this;
      const dialog = that.links.closest('.wa-dialog');
      const dialogObject = dialog.data('dialog');

      if (dialogObject) {
        dialogObject.options.vue_ready.then(function () {
          const categoryForm = dialog.find('.s-category-form');

          categoryForm.on('wa_save', function (event) {
            const links = [];

            if (that.additionalLinks) {
              that.linksWrapper.find('.button').each(function () {
                const id = $(this).data('id');
                const link = that.additionalLinks.filter(e => Number(e.id) === Number(id))[0] ?? '';
                if (link) {
                  links.push(link);
                }
              })
            }

            event.form_data['apiextension_additional_links'] = links;
            event.form_data['apiextension_additional_links_ui2'] = true;
          });
        });
      }
    },
  }
})(jQuery);