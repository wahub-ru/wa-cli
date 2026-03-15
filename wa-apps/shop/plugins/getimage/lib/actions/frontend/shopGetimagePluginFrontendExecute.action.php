<?php

class shopGetimagePluginFrontendExecuteAction extends waViewAction
{
    public function execute()
    {
        // Получаем параметры из URL
        $width = waRequest::get('width', 300, 'int');
        $height = waRequest::get('height', 150, 'int');
        $bgColor = waRequest::get('bg', 'cccccc', 'string');
        $textColor = waRequest::get('color', '000000', 'string');
        $text = waRequest::get('text', "{$width}x{$height}", 'string');
        $fontSize = waRequest::get('size', 20, 'int');

        // Создаем изображение
        $image = imagecreatetruecolor($width, $height);

        // Преобразуем цвет фона из HEX в RGB
        list($r, $g, $b) = sscanf($bgColor, "%02x%02x%02x");
        $backgroundColor = imagecolorallocate($image, $r, $g, $b);

        // Заливаем фон
        imagefilledrectangle($image, 0, 0, $width, $height, $backgroundColor);

        // Преобразуем цвет текста из HEX в RGB
        list($tr, $tg, $tb) = sscanf($textColor, "%02x%02x%02x");
        $textColor = imagecolorallocate($image, $tr, $tg, $tb);

        // Путь к шрифту (можно использовать встроенный шрифт GD или загрузить свой)
        $font = 5; // Встроенный шрифт GD

        // Вычисляем положение текста
        $textWidth = imagefontwidth($font) * strlen($text);
        $textHeight = imagefontheight($font);
        $x = ($width - $textWidth) / 2;
        $y = ($height - $textHeight) / 2;

        // Добавляем текст на изображение
        imagestring($image, $font, $x, $y, $text, $textColor);

        // Отправляем заголовок Content-Type
        header('Content-Type: image/png');

        // Выводим изображение
        imagepng($image);

        // Освобождаем память
        imagedestroy($image);
        exit;
    }
}