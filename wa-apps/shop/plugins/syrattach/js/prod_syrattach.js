-(function ($) {

    const l10n = {
        'GB': 'GB',
        'MB': 'MB',
        'KB': 'KB',
        'bytes': 'bytes',
        'of ': 'of'
    };

    function localize(str) {
        return l10n[str] ?? str;
    }

    function filesize(bytes) {
        if (typeof bytes === 'string') {
            bytes = parseInt(bytes);
        }

        if (typeof bytes !== 'number') {
            return '';
        }
        if (bytes >= 1000000000) {
            return `${(bytes / 1000000000).toFixed(2)} ${localize('GB')}`;
        }
        if (bytes >= 1000000) {
            return `${(bytes / 1000000).toFixed(2)} ${localize('MB')}`;
        }

        if (bytes > 1000)
            return `${(bytes / 1000).toFixed(2)} ${localize('KB')}`;

        return `${bytes} ${localize('bytes')}`;
    }

    const DescriptionEditor = {
        props: {value: String, fileId: Number},
        data() {
            return {
                description: this.value,
                saving: false
            }
        },
        methods: {
            saveDescription() {
                this.saving = true;
                $.post(
                    '?plugin=syrattach&module=attachments&action=descriptionsave',
                    {
                        id: this.fileId,
                        data: {description: this.description}
                    }
                ).done(() => {
                    this.$emit('input', this.description)
                }).always(() => this.saving = false);
            }
        }
    };

    const DescriptionField = {
        props: {value: String, fileId: Number},
        data() {
            return {
                description: this.value,
                edit_mode: false
            }
        },
        components: {DescriptionEditor},
    };

    const UploadingFile = {
        props: {
            productId: Number,
            file: Object,
            maxSize: Number
        },
        data() {
            return {
                uploading: false,
                uploaded: null,
                total_size: null,
                errors: null
            }
        },
        filters: {localize, filesize},
        mounted() {
            const uploadFile = file => {
                const formData = new FormData();
                formData.append('syrattach_product_id', this.productId);
                formData.append('files', file);

                const vm = this;

                return $.ajax({
                    xhr() {
                        const xhr = new window.XMLHttpRequest();
                        xhr.upload.addEventListener("progress", event => {
                            if (event.lengthComputable) {
                                vm.total_size = event.total;
                                vm.uploaded = event.loaded;
                                // progressbar update
                                // instance.set({ percentage: percent });
                            }
                        }, false);
                        return xhr;
                    },
                    url: '?plugin=syrattach&module=attachments&action=upload',
                    data: formData,
                    cache: false,
                    contentType: false,
                    processData: false,
                    type: 'POST'
                })
            };

            if(this.maxSize && this.file.size >= this.maxSize) {
                this.errors = [localize('Size of %name% exceeds maximum upload size limit').replace('%name%', this.file.name)];
                return;
            }

            this.uploading = true;
            uploadFile(this.file)
                .done(r => {
                    if (r.files && r.files.length && r.files[0].id) {
                        this.$emit('upload-complete', {id: this.file.id, file: r.files[0]})
                    } else if(r.files && r.files.length && r.files[0].error) {
                        this.errors=[localize("Upload error" + ( r.files[0].error.length ? ": " + r.files[0].error : ""))];
                    } else if (r.status && r.status === 'fail') {
                        this.errors=[localize("Upload error" + ( r.error && r.error.length ? ": " + r.error : ""))];
                    }
                })
                .fail(r => {
                    this.uploading = false;
                    this.errors = [localize('Server error: ') + (r.status ?? '')+' '+(r.statusText ?? '')];
                    return false;
                }).always(() => this.uploading = false)
        }
    };

    const UploadSection = {
        props: {productId: Number},
        data() {
            return {
                is_over: false,
                uploads: []
            }
        },
        components: {UploadingFile},
        filters: {filesize},
        methods: {
            uploadFiles(event, type) {
                let files = [];
                this.is_over = false;
                if (event instanceof DragEvent || type === 'drop') files = event.dataTransfer.files;
                else if (event instanceof Event || type === 'input') files = event.target.files;

                for (let i = 0; i < files.length; i++) {
                    files[i].id = Math.random();
                    this.uploads.push(files[i]);
                }

                event.target.value = null;
            },
            removeFileFromListById(id) {
                const idx = this.uploads.findIndex(f => f.id === id);
                if (idx > -1) this.uploads.splice(idx, 1);
            },
            uploadComplete(event) {
                this.removeFileFromListById(event.id);
                this.$emit('add-file', event.file);
            },
        }
    };

    function Section(options) {
        this.$wrapper = options['$wrapper'];
        this.files = options.files;
        if (options.l10n && (typeof options.l10n === 'object')) Object.assign(l10n, options.l10n);
        this.component_templates = options.component_templates;
        this.initVue();
    }

    Section.prototype.initVue = function () {
        const $view_section = this.$wrapper.find('.js-product-attachments-section');
        const that = this;
        DescriptionField.template = this.component_templates['description-field'];
        DescriptionEditor.template = this.component_templates['description-editor'];
        UploadSection.template = this.component_templates['upload-section'];
        UploadingFile.template = this.component_templates['uploading-file'];

        return new Vue({
            el: $view_section[0],
            data: {
                files: this.files
            },
            components: {DescriptionField, UploadSection},
            created() {
                $view_section.css('visibility', '')
            },
            mounted() {
                that.$wrapper.trigger("section_mounted", ["attachments", that]);
            },
            filters: {filesize},
            methods: {
                confirmDelete(id) {
                    const removeFileFromListById = id => {
                        const idx = this.files.findIndex(f => f.id === id);
                        if (idx > -1) this.files.splice(idx, 1);
                    };

                    let allow_close = true;

                    $.waDialog({
                        html: that.component_templates['delete-dialog'],
                        onOpen($dialog, dialog) {
                            const dialog_body = $dialog.find('.dialog-body');
                            const section = dialog_body.find('.js-vue-node-wrapper');
                            new Vue({
                                el: dialog_body[0],
                                created() {
                                    section.css('visibility', '');
                                },
                                mounted() {
                                    dialog.resize();
                                },
                                data() {
                                    return {
                                        id: id,
                                        dialog: dialog,
                                        deleting: false
                                    }
                                },
                                methods: {
                                    deleteFile() {
                                        this.deleting = true;
                                        $.post("?plugin=syrattach&module=attachments&action=delete", {id: this.id})
                                            .done(r => {
                                                this.deleting = false;
                                                if (r && r.status) {
                                                    if (r.status === 'ok') {
                                                        removeFileFromListById(this.id);
                                                        this.$nextTick(() => dialog.close());
                                                    }
                                                }
                                            })
                                            .always(() => {
                                                this.deleting = false
                                            });
                                    }
                                },
                                watch: {
                                    deleting(value) {
                                        allow_close = !value;
                                    }
                                }
                            })
                        },
                        onClose() {
                            return allow_close;
                        }
                    });
                }
            },
            watch: {
                files() {
                    const $menu_item = $("#s-syrattach-plugin-menuitem");
                    const $counter = $menu_item.find('.count');
                    if(this.files.length) {
                        if($counter.length) $counter.text(this.files.length);
                        else $(`<span class="count">${this.files.length}</span>`).appendTo($menu_item.find('a'));
                    } else if($counter.length) $counter.remove();
                }
            }
        });
    }

    $.wa_shop_products.init.initProductAttachmentsSection = function (options) {
        return new Section(options)
    }
})(jQuery)
