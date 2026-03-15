(function($) {
    $(document).ready(function() {
        var menuLoaded = false;
        var loading = false;

        $('#catalog-toggle').on('click', function(e) {
            e.preventDefault();

            var $menuContainer = $('#catalog-menu-container');
            var $toggle = $(this);

            if (!menuLoaded) {
                if (loading) return;

                loading = true;
                $toggle.addClass('loading');

                // Проверяем, есть ли закешированное меню в localStorage
                var cachedMenu = localStorage.getItem('catalogFullMenu');
                var cachedTimestamp = localStorage.getItem('catalogFullMenuTimestamp');

                if (cachedMenu && cachedTimestamp && (Date.now() - cachedTimestamp < 86400000)) {
                    // Используем кешированное меню
                    renderMenu(JSON.parse(cachedMenu));
                    menuLoaded = true;
                    loading = false;
                    $toggle.removeClass('loading');
                } else {
                    // Загружаем меню через AJAX
                    $.ajax({
                        url: wa_url + '?plugin=catalogmenu&action=getFullMenu',
                        dataType: 'json',
                        success: function(response) {
                            if (response.status === 'ok') {
                                renderMenu(response.data);
                                menuLoaded = true;

                                // Сохраняем в localStorage
                                localStorage.setItem('catalogFullMenu', JSON.stringify(response.data));
                                localStorage.setItem('catalogFullMenuTimestamp', Date.now());
                            }
                        },
                        complete: function() {
                            loading = false;
                            $toggle.removeClass('loading');
                        }
                    });
                }
            }

            $menuContainer.toggleClass('expanded');
        });

        function renderMenu(menuData) {
            var $menuContainer = $('#catalog-menu-container');
            var $topLevel = $menuContainer.find('.top-level-menu');

            // Добавляем подкатегории
            $topLevel.find('li').each(function() {
                var $li = $(this);
                var categoryId = $li.data('category-id');

                var category = findCategoryInMenu(menuData, categoryId);
                if (category && category.childs.length > 0) {
                    var $submenu = $('<ul class="submenu"></ul>');

                    $.each(category.childs, function(i, child) {
                        $submenu.append(
                            '<li><a href="' + child.url + '">' + child.name + '</a></li>'
                        );
                    });

                    $li.append($submenu);
                }
            });
        }

        function findCategoryInMenu(menu, categoryId) {
            for (var i = 0; i < menu.length; i++) {
                if (menu[i].id == categoryId) {
                    return menu[i];
                }

                if (menu[i].childs && menu[i].childs.length > 0) {
                    var found = findCategoryInMenu(menu[i].childs, categoryId);
                    if (found) return found;
                }
            }

            return null;
        }
    });
})(jQuery);