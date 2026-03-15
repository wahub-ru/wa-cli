<?php


class shopSeoredirect2ExcelConfig
{
	public function getRedirectColumns()
	{
		return array(
			array(
				'code' => 'id',
				'title' => 'Id',
			),
			array(
				'code' => 'domain',
				'title' => 'Витрина',
			),
			array(
				'code' => 'url_from',
				'title' => 'Редирект с',
			),
			array(
				'code' => 'url_to',
				'title' => 'Редирект на',
			),
			array(
				'code' => 'code_http',
				'title' => 'Код ответа',
			),
			array(
				'code' => 'status',
				'title' => 'Статус',
			),
			array(
				'code' => 'param',
				'title' => 'Учитывать GET-параметры в URL',
			),
			array(
				'code' => 'create_datetime',
				'title' => 'Дата создания',
			),
			array(
				'code' => 'edit_datetime',
				'title' => 'Дата обновления',
			),
			array(
				'code' => 'comment',
				'title' => 'Комментарий',
			),
		);
	}
	
	public function getErrorColumns()
	{
		return array(
			array(
				'code' => 'domain',
				'title' => 'Витрина',
			),
			array(
				'code' => 'url',
				'title' => 'URL страницы',
			),
			array(
				'code' => 'http_referer',
				'title' => 'Источник',
			),
			array(
				'code' => 'views',
				'title' => 'Кол-во переходов',
			),
			array(
				'code' => 'create_datetime',
				'title' => 'Дата обнаружения',
			),
			array(
				'code' => 'edit_datetime',
				'title' => 'Дата последнего перехода',
			),
		);
	}
}
