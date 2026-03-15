<?php

class shopSeoredirect2PluginRedirectImportExcelController extends waLongActionController
{
    private $excel_factory;
    /** @var shopSeoredirect2Excel */
    private $excel;

    public function __construct()
    {
        $this->excel_factory = new shopSeoredirect2ExcelFactory();
    }

    protected function init()
    {
        $this->data['offset'] = 0;
        $this->data['count'] = 0;
        $this->data['timestamp'] = time();
        $this->data['errors'] = array();
        $this->data['changed'] = array();
        $this->data['added'] = array();
        $this->data['hint'] = 'Запуск обновления данных...';

        $file = waRequest::file('excelfile');

        if (!$this->validateFile($file, $errors)) {
            $this->data['errors'] = $errors;

            return;
        }

        $this->excel = $this->excel_factory->fromFile($file->tmp_name);

        if (!$this->validateTable($this->excel, $errors)) {
            $this->data['errors'] = $errors;

            return;
        }

        $dir = wa()->getTempPath('csv/upload/seoredirect2/');
        $path = "{$dir}/{$file->name}";
        $file->moveTo($path);
        $this->data['file'] = $path;
        $this->data['count'] = max(0, $this->getCountRows($this->excel) - 1);
    }

    protected function restore()
    {
        $this->excel = $this->excel_factory->fromFile($this->data['file']);
    }

    protected function step()
    {
        $this->data['hint'] = 'Применяем новые данные...';
        $redirects = $this->readRedirects();
        $this->insertRedirects($redirects);

        return true;
    }

    protected function isDone()
    {
        if ($this->data['count'] == 0) {
            return true;
        }

        return $this->data['offset'] >= $this->data['count'];
    }

    protected function finish($filename)
    {
        $this->data['hint'] = 'Готово!';
        $this->info();

        return true;
    }

    protected function info()
    {
        $response = $this->getInfo();
        $response['progress'] = empty($this->data['count']) ? 100
            : ($this->data['offset'] / $this->data['count']) * 100;
        $response['progress'] = sprintf('%0.3f%%', $response['progress']);

        $this->getResponse()->addHeader('Content-type', 'application/json');
        $this->getResponse()->sendHeaders();

        echo json_encode($response);
    }

    protected function readRedirects()
    {
        $chunk_size = 10;
        $columns = $this->getColumns();
        $redirects = array();

        for ($i = 0; $i < $chunk_size; $i++) {
            if ($this->data['offset'] >= $this->data['count']) {
                break;
            }

            $redirect = array();

            foreach ($columns as $position_column => $column) {
                $value = $this->excel->getCellValueByColumnAndRow($position_column, $this->data['offset'] + 2);
                $redirect[$column['code']] = $value;
            }

            $redirect = $this->handleRedirect($redirect);

            if (isset($redirect)) {
                $redirects[] = $redirect;
            }

            $this->data['offset'] = $this->data['offset'] + 1;
        }

        return $redirects;
    }

    protected function handleRedirect($row)
    {
        $line = $this->data['offset'] + 1;

        if ($this->checkRedirect($row, $line)) {
            return $this->changeRedirect($row);
        } else {
            return null;
        }
    }

    protected function checkRedirect($row, $line)
    {
        $names = array(
            'id' => 'Id',
            'domain' => 'Витрина',
            'url_from' => 'Редирект с',
            'url_to' => 'Редирект на',
            'code_http' => 'Код HTTP статуса',
            'status' => 'Активность',
            'param' => 'Учитывать GET-параметры в URL',
            'create_datetime' => 'Дата создания',
            'edit_datetime' => 'Дата обновления',
            'comment' => 'Комментарий',
        );

        $data = array(
            array('code_http', array(301, 302)),
            array('param', array(1, 0, '', 'да', 'нет')),
            array('status', array(1, 0, '', 'да', 'нет')),
        );

        foreach ($data as $item) {
            list($code, $variants) = $item;
            $value = mb_strtolower($row[$code]);

            if (!in_array($value, $variants)) {
                $this->data['errors'][] = "Неправильне значение в поле '{$names[$code]}'. Строка {$line} " . implode(";", $row);

                return false;
            }
        }

        return true;
    }

    protected function changeRedirect($redirect)
    {
        $fields = array('param', 'status');
        $values = array(
            0 => 0,
            1 => 1,
            '' => 0,
            'да' => 1,
            'нет' => 0,
        );

        foreach ($fields as $field) {
            $value = mb_strtolower($redirect[$field]);
            $redirect[$field] = $values[$value];
        }

        return $redirect;
    }

    protected function setChanged($redirects)
    {
        $this->data['changed'][] = $redirects;
    }

    public function insertRedirects($redirects)
    {
        $redirect_model = new shopSeoredirect2RedirectModel();
        $last = $redirect_model->getLastSort();
        $datetime = date('Y-m-d H:i:s');

        foreach ($redirects as $key => $redirect) {
            $redirect['create_datetime'] = $datetime;
            $redirect['edit_datetime'] = $datetime;
            $this->insertRedirect($redirect, $last, $key);
        }

        if ($this->isDone()) {
            $redirect_model->newSorting();
        }

        return true;
    }

    protected function insertRedirect($redirect, $last, $key)
    {
        $redirect_model = new shopSeoredirect2RedirectModel();
        $domain = ifset($redirect['domain']);
        $url = ifset($redirect['url_from']);
        $hash = $redirect_model->getHashByDomainAndUrl($domain, $url);

        $redirect['hash'] = $hash;
        $redirect['type'] = shopSeoredirect2RedirectModel::getType($url);
        $redirect['sort'] = $last + $key;

        $old_redirect = $redirect_model->getById($redirect['id']);

        if ($old_redirect) {
            if (!$this->equalRedirects($old_redirect, $redirect)) {
                $this->setChanged($this->getDiff($old_redirect, $redirect));
                $redirect_model->updateById($redirect['id'], $redirect);
            }
        } else {
            unset($redirect['id']);
            $this->data['added'][] = $redirect;
            $redirect_model->addRedirect($redirect);
        }

    }

    protected function getInfo()
    {
        $interval = 0;

        if (!empty($this->data['timestamp'])) {
            $interval = time() - $this->data['timestamp'];
        }

        $info = array(
            'time' => sprintf('%d:%02d:%02d', floor($interval / 3600), floor($interval / 60) % 60, $interval % 60),
            'processId' => $this->processId,
            'ready' => $this->isDone(),
            'offset' => $this->data['offset'],
            'count' => $this->data['count'],
            'hint' => $this->data['hint'],
            'changed' => $this->data['changed'],
            'added' => $this->data['added'],
            'errors' => $this->data['errors'],
        );
        $info['progress'] = empty($this->data['count']) ? 100 : ($this->data['offset'] / $this->data['count']) * 100;
        $info['progress'] = sprintf('%0.3f%%', $info['progress']);

        return $info;
    }

    protected function validateFile(waRequestFileIterator $file, &$errors)
    {
        $errors = array();

        if (!in_array(mb_strtolower($file->extension), array('xls', 'xlsx'))) {
            $errors[] = 'Неверный формат';

            return false;
        }

        return true;
    }

    protected function validateTable(shopSeoredirect2Excel $excel, &$errors)
    {
        $errors = array();
        $count_columns = $excel->getCountColumns();

        if ($count_columns != count($this->getColumns())) {
            $errors[] = 'Неправильное количество колонок';

            return false;
        }

        return true;
    }

    protected function getColumns()
    {
        $excel_config = new shopSeoredirect2ExcelConfig();

        return $excel_config->getRedirectColumns();
    }

    protected function getCountRows(shopSeoredirect2Excel $excel)
    {
        return $excel->getCountRows();
    }

    protected function equalRedirects($redirect1, $redirect2)
    {
        foreach (array(
                     'domain',
                     'url_from',
                     'url_to',
                     'param',
                     'code_http',
                     'status',
                     'comment',
                 ) as $field) {
            if (!($redirect1[$field] == $redirect2[$field])) {
                return false;
            }
        }

        return true;
    }

    protected function getDiff($redirect1, $redirect2)
    {
        $redirect = $redirect1;

        foreach (array(
                     'domain',
                     'url_from',
                     'url_to',
                     'code_http',
                     'comment',
                 ) as $field) {
            if (!($redirect1[$field] == $redirect2[$field])) {
                $redirect[$field] = "<span style='color: red;'>{$redirect1[$field]}</span><br><span style='color: green;'>{$redirect2[$field]}</span>";
            }
        }

        $redirect['param'] = $redirect2['param'];
        $redirect['status'] = $redirect2['status'];

        return $redirect;
    }
}
