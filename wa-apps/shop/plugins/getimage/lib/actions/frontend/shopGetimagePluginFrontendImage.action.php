<?php

class shopGetimagePluginFrontendImageAction extends waViewAction
{
    public function execute()
    {
        // Получаем настройки плагина
        $plugin = wa()->getPlugin('getimage');
        $settings = $plugin->getSettings();
        $defaultSize = $settings['default_font_size'] ?? 20;

        // Получаем параметры из URL
        $width = waRequest::get('width', 300, 'int');
        $height = waRequest::get('height', 150, 'int');
        $bgColor = waRequest::get('bg', 'cccccc', 'string');
        $textColor = waRequest::get('color', '000000', 'string');
        $text = waRequest::get('text', "{$width}x{$height}", 'string');
//        $fontSize = waRequest::get('size', 32, 'int');
        $fontSize = waRequest::get('size', $defaultSize, 'int');

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

        // Путь к шрифту
        $fontPath = wa()->getAppPath('plugins/getimage/fonts/roboto/Roboto-Bold.ttf', 'shop');

        // Получаем метрики текста
        $textBox = imagettfbbox($fontSize, 0, $fontPath, $text);

        // Вычисляем ширину и высоту текста
        $textWidth = $textBox[2] - $textBox[0]; // Ширина текста
        $textHeight = $textBox[1] - $textBox[7]; // Высота текста (с учетом baseline)

        // Вычисляем координаты для центрирования текста
        $x = ($width - $textWidth) / 2; // Центрирование по горизонтали
        $y = ($height - $textHeight) / 2 + $textHeight; // Центрирование по вертикали

        // Добавляем текст на изображение
        imagettftext($image, $fontSize, 0, $x, $y, $textColor, $fontPath, $text);

        // Отправляем заголовок Content-Type
        header('Content-Type: image/png');

        // Выводим изображение
        imagepng($image);

        // Освобождаем память
        imagedestroy($image);
        exit;
    }
}