<?php

$model = new waModel();

/* Настройки */
$data = array(
    array("name" => "title", "value" => 'Заказать обратный звонок'),
    array("name" => "button", "value" => 'Отправить'),
    array("name" => "text", "value" => ''),
    array("name" => "title_success", "value" => 'Ваша заявка отправлена'),
    array("name" => "text_success", "value" => 'В ближайшее время мы обязательно свяжемся с Вами!'),
    array("name" => "email_active", "value" => ''),
    array("name" => "email_list", "value" => ''),
    array("name" => "email_title", "value" => 'Заявка {$id} на обратный звонок'),
    array("name" => "email_content", "value" => '<p>Телефон: <strong>{$var_1}</strong></p><p>Email: <strong>{$var_2}</strong><span class="redactor-invisible-space"><br></span></p><p>Имя: <strong>{$var_3}</strong><span class="redactor-invisible-space"><br></span></p><p>Регион: <strong>{$region}</strong><span class="redactor-invisible-space"></span></p><p><span class="redactor-invisible-space">Город: <strong>{$city}</strong><span class="redactor-invisible-space"><br></span></span></p><p><span class="redactor-invisible-space"><span class="redactor-invisible-space">Ссылка: <strong>{$url}</strong><span class="redactor-invisible-space"></span></span></span></p>'),
    array("name" => "sms_active", "value" => ''),
    array("name" => "sms_list", "value" => ''),
    array("name" => "sms_content", "value" => 'Заявка {$id} на обратный звонок. Телефон: {$var_1}.'),
    array("name" => "push_active", "value" => ''),
    array("name" => "push_title", "value" => 'Заявка {$id} на обратный звонок'),
    array("name" => "push_content", "value" => 'Телефон: {$var_1}. Имя: {$var_3}.'),
    array("name" => "yandex_number", "value" => ''),
    array("name" => "yandex_open", "value" => ''),
    array("name" => "yandex_send", "value" => ''),
    array("name" => "yandex_error", "value" => ''),
    array("name" => "goggle_open_category", "value" => ''),
    array("name" => "goggle_open_action", "value" => ''),
    array("name" => "goggle_send_category", "value" => ''),
    array("name" => "goggle_send_action", "value" => ''),
    array("name" => "goggle_error_category", "value" => ''),
    array("name" => "goggle_error_action", "value" => ''),
);

foreach($data as $item){
    $model->query("REPLACE `shop_skcallback_defines` (`name`, `value`) VALUES ('{$item["name"]}', '{$item["value"]}')");
}

/* Статусы */
$data = array(
    array("id"=> 1, "title" => "Новый", "color" => "#008800", "starter" => 1),
    array("id"=> 2, "title" => "В работе", "color" => "#800080", "starter" => 0),
    array("id"=> 3, "title" => "Не отвечает", "color" => "#cc0000", "starter" => 0),
    array("id"=> 4, "title" => "Перезвонить", "color" => "#ff8000", "starter" => 0),
    array("id"=> 5, "title" => "Обработан", "color" => "#aaaaaa", "starter" => 0),
);

foreach($data as $item){
    $model->query("REPLACE `shop_skcallback_status` (`id`, `title`, `color`, `starter`) VALUES ({$item["id"]}, '{$item["title"]}', '{$item["color"]}', {$item["starter"]})");
}

/* Типы полей */
$data = array(
    array("id" => 1, "name" => "phone", "title" => "Номер телефона", "placeholder" => "+7(###)###-##-##",  "is_require" => 1, "is_additional" => 1),
    array("id" => 2, "name" => "email", "title" => "Email", "placeholder" => "", "is_require" => 1, "is_additional" => 0),
    array("id" => 3, "name" => "name", "title" => "Имя клиента", "placeholder" => "", "is_require" => 1, "is_additional" => 0),
    array("id" => 4, "name" => "text", "title" => "Однострочное поле", "placeholder" => "", "is_require" => 1, "is_additional" => 0),
    array("id" => 5, "name" => "textarea", "title" => "Многострочное поле", "placeholder" => "", "is_require" => 1, "is_additional" => 0),
    array("id" => 6, "name" => "string", "title" => "Произвольный текст", "placeholder" => "Введите текст", "is_require" => 0, "is_additional" => 1),
    array("id" => 7, "name" => "checkbox", "title" => "Чекбокс", "placeholder" => "Введите фразу переключателя", "is_require" => 1, "is_additional" => 1),
    array("id" => 8, "name" => "slider", "title" => "Слайдер (время)", "placeholder" => "", "is_require" => 0, "is_additional" => 0),
);

foreach($data as $item){
    $model->query("REPLACE `shop_skcallback_controls_type` (`id`, `name`, `title`, `placeholder`, `is_require`, `is_additional`) VALUES ({$item["id"]}, '{$item["name"]}', '{$item["title"]}', '{$item["placeholder"]}', {$item["is_require"]}, {$item["is_additional"]})");
}

/* Дефолтные поля */
$data = array(
    array("type_id" => 1, "title" => "Номер телефона", "additional" => "+7(###)###-##-##", "require" => 1, "sort" => 0),
    array("type_id" => 2, "title" => "Email", "additional" => "", "require" => 0, "sort" => 1),
    array("type_id" => 3, "title" => "Ваше имя", "additional" => "", "require" => 0, "sort" => 2),
    array("type_id" => 5, "title" => "Комментарий", "additional" => "", "require" => 0, "sort" => 3),
    array("type_id" => 7, "title" => "Персональные данные", "additional" => "Я соглашаюсь на обработку персональных данных", "require" => 1, "sort" => 4),
);

foreach($data as $item){
    $model->query("REPLACE `shop_skcallback_controls` (`type_id`, `title`, `additional`, `require`, `sort`) VALUES ({$item["type_id"]}, '{$item["title"]}', '{$item["additional"]}', {$item["require"]}, {$item["sort"]})");
}
