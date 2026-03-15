<?php

class shopTgconsultPluginBackendUploadPhotoController extends waJsonController
{
    public function execute()
    {
        try {
            // 1) файл пришёл?
            if (empty($_FILES['file']) || !empty($_FILES['file']['error'])) {
                $err = !empty($_FILES['file']['error']) ? (string)$_FILES['file']['error'] : 'Файл не получен';
                $this->response = ['ok' => false, 'error' => $err];
                return;
            }

            $file = $_FILES['file'];

            // 2) это изображение? MIME на некоторых конфигурациях бывает "кривым" — подстрахуемся getimagesize
            $mime = (string) waFiles::getMimeType($file['tmp_name'], $file['name']);
            $gi = @getimagesize($file['tmp_name']);
            if (!$gi || empty($gi[2])) {
                $this->response = ['ok' => false, 'error' => 'Недопустимый формат (mime='.$mime.')'];
                return;
            }

            // 3) нормализуем расширение по IMAGETYPE_*
            switch ($gi[2]) {
                case IMAGETYPE_JPEG: $ext = 'jpg'; break;
                case IMAGETYPE_PNG:  $ext = 'png'; break;
                case IMAGETYPE_GIF:  $ext = 'gif'; break;
                case IMAGETYPE_WEBP: $ext = 'webp'; break;
                default:
                    $this->response = ['ok' => false, 'error' => 'Неподдерживаемый тип изображения'];
                    return;
            }

            // 4) папка (создаём при необходимости)
            $dir = wa()->getDataPath('plugins/tgconsult/manager/', true, 'shop'); // wa-data/public/shop/plugins/tgconsult/manager/
            if (!file_exists($dir) && !waFiles::create($dir)) {
                $this->response = ['ok' => false, 'error' => 'Не удалось создать папку'];
                return;
            }

            // 5) имя файла — без waUtils
            $rand = function($len = 8) {
                if (function_exists('random_bytes')) {
                    return substr(bin2hex(random_bytes(16)), 0, $len);
                }
                return substr(sha1(uniqid('', true) . mt_rand()), 0, $len);
            };
            $name = 'manager-' . date('Ymd-His') . '-' . $rand(8) . '.' . $ext;
            $path = $dir . $name;

            // 6) сохраняем
            if (!move_uploaded_file($file['tmp_name'], $path)) {
                waLog::log('move_uploaded_file failed: tmp='.$file['tmp_name'].' -> '.$path, 'tgconsult.log');
                $this->response = ['ok' => false, 'error' => 'Не удалось сохранить файл'];
                return;
            }

            // 7) публичный URL
            $url = wa()->getDataUrl('plugins/tgconsult/manager/'.$name, true, 'shop', true);

            // 8) сразу сохраним URL в настройках плагина
            $plugin   = wa('shop')->getPlugin('tgconsult');
            $settings = $plugin->getSettings();
            $settings['manager_photo'] = $url;
            $plugin->saveSettings($settings);

            $this->response = ['ok' => true, 'url' => $url];
        } catch (Exception $e) {
            waLog::log('upload exception: '.$e->getMessage().' @'.$e->getFile().':'.$e->getLine(), 'tgconsult.log');
            $this->response = ['ok' => false, 'error' => 'Exception: '.$e->getMessage()];
        }
    }
}
