(function ($) {
    $.productTips = {
        runTab: function () {
            var $tab_content = $('.tab-content', '#s-product-profile-tabs')
                .append('<div class="block s-tab-block bordered-left bordered-right bordered-bottom" data-tab="tipslog" id="s-plugin-tipslog-tab"></div>');

            $('#s-product-profile-tabs').on('open', '.s-tab-block[data-tab="tipslog"]', function () {
                if (!$(this).data('loaded')) {
                    $(this).load(
                        '?plugin=tips&module=product&action=log&product_id=' + $.product.getId(),
                        function () {
                            $('h2 i.loading', $tab_content).hide();
                        }
                    ).data('loaded', 1);
                }
            });

            $tab_content.on('click', '.pagination .menu-h a', function () {
                $('h2 i.loading', $tab_content).show();
                $('#s-tips-actions-log-container').load(
                    $(this).attr('href') + ' #s-tips-actions-log',
                    function () {
                        $('h2 i.loading', $tab_content).hide();
                    }
                );
                return false;
            });
        }
    };
})(jQuery);
