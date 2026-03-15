# apiextension
Плагин для shop script 8. Расширение апи магазина

<p>Загрузите папку в \wa-apps\shop\plugins\.<br />
В файле \wa-config\apps\shop\plugins.php пропишите «'apiextension' => true,».<br />
Сбросьте кэш, плагин установлен.</p>

<p><b>Полный список апи:</b></p>


<p>
  <b>shopApiextensionPlugin::affiliateBonus($contact_id)</b> - количество бонусов авторизованного пользователя
</p>

<p>
  <b>shopApiextensionPlugin::reviewsCount($product_ids)</b> - количество отзывов для товаров
</p>

<p>
  <b>shopApiextensionPlugin::categoryProducts($category_id, $limit)</b> - товары категории, в фильтрации товаров участвуют все гет параметры фильтра и пагинации
</p>

<p>
  <b>shopApiextensionPlugin::productImages($product_ids)</b> - фото для товаров
</p>

<p>
  <b>shopApiextensionPlugin::filtersForCategory($category_id)</b> - активный фильтр товаров для категории
</p>

<p>
<b>Дополнительные поля для отзывов</b> - в форме добавления отзыва нужно добавить поля
input c name=apiextension_experience,apiextension_dignity,apiextension_limitations,apiextension_recommend.<br />
После этого в новых отзывах будут доступные переменные
$review.apiextension_experience, $review.apiextension_dignity, $review.apiextension_limitations, $review.apiextension_recommend
</p>

<p>
<b>Голосвание в отзывах</b> - апи рест - apiextension/reviews/vote/ на добавление или удаление голосования<br />
принимает параметры _csrf, review_id, apiextension_reviews_vote = array('type'  => 'like' || 'dislike', 'value' => 1 || 0)<br />
<b>shopApiextensionPlugin::getReviewsVote($reviewIds, $contactId)</b> - получить текущее голосование для клиента
</p>

<p>
<b>shopApiextensionPlugin::getProductsForReviewBonus()</b> - товары за которые можно получить бонус за отзыв<br />
Бонусы за отзыв о товара можно получить только когда заказ в статусе completed, при отмене заказа, баллы списываются у клиента. Если клиенту был начислен бонус за отзыв и потом удалить отзыв в административной панели, то будут навсегда списаны баллы за отзыв у клиента, заявка на получение бонусов снова будет активна, если не вышел срок.
</p>

<p>
<b>shopApiextensionPlugin::getBonusReviewForProduct($product)</b> - поулчить массив бонусов за конкретный товар для динамического расчета
</p>

<p>
<b>shopApiextensionPlugin::getTagsByCategory($categoryId)</b> - теги товаров текущей категории, так же можно настроить кеширование тегов, <a href="https://developers.webasyst.ru/docs/features/cache/" target="_blank">инструкция от webasyst</a>
</p>

<p>
 <b>shopApiextensionPlugin::pagination($params)</b> - пагинация без ссылок (аналог wa_pagination), $params=array("total" => $pages_count, "attrs" =>["class" => "pagin"])
</p>

<p>
 <b>shopApiextensionPlugin::getThemeSettings($theme_id, $app, $values_only)</b> - получить настройки темы приложения
</p>

<p>
 <b>shopApiextensionPlugin::getProductFromPromos($promo_id)</b> - получить товары из промо маркетинга
</p>

<p>
 <b>shopApiextensionPlugin::getSearchFilters($featuresIds)</b> - фильтр для поиска
</p>

<p>
<b>Модерация отзывов</b> - достаточно включить настройках плагина и будет модерация отзывов, редактирование полей и удаление отзыва полностью
</p>

<p>
<b>Дополнительные ссылки в категориях</b> - включаемая опция, дает возможность добавлять дополнительные ссылки у категории и сохранять их в дополнительных параметрах категории (Только для UI2.0) - $category.params.apiextension_additional_links
</p>

<p>
<b>Умный фильтр</b> - апи рест - apiextension/category/smartfilters/ получить фильтры для блокировки<br />
принимает параметры post category_id ,filters, request_data<br />
</p>