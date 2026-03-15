<?php
return array(
	'hide_no_value' => array(
		'title' => 'Скрыть незаполненные поля',
    'control_type' => waHtmlControl::CHECKBOX,
		'value' => 0,
  ),
  'view_rq' => array(
		'title' => 'Показывать только обязательные для заполнения поля',
    'control_type' => waHtmlControl::CHECKBOX,
		'value' => 0,
		'description' => "Работает только для полей Физ. лиц (не работает для Компании)"
  ),
	'show_default_fields' => array(
		'title' => 'Показать стандартные основные поля (Имя, Фамилия, Телефон и т.д.)',
    'control_type' => waHtmlControl::CHECKBOX,
		'value' => 0,
  ),
  'hide_default_fields' => array(
		'title' => 'Скрыть стандартные дополнительные поля (Компания, Должность и т.д.)',
    'control_type' => waHtmlControl::CHECKBOX,
		'value' => 0,
  ),
  'hide_print' => array(
		'title' => 'Скрыть дополнительные поля на странице печати',
    'control_type' => waHtmlControl::CHECKBOX,
		'value' => 0,
  ),
);
