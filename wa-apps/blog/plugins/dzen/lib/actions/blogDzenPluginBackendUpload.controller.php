<?php

class blogDzenPluginBackendUploadController extends waJsonController
{
    public function execute()
    {
        $file = waRequest::file('file');
        if (!$file || !$file->uploaded()) {
            $this->errors = _w('Failed to upload file.');
            return;
        }

        $ext = strtolower((string) $file->extension);
        $types = array(
            'jpg'  => array('dir' => 'enclosure', 'mime' => 'image/jpeg'),
            'jpeg' => array('dir' => 'enclosure', 'mime' => 'image/jpeg'),
            'png'  => array('dir' => 'enclosure', 'mime' => 'image/png'),
            'gif'  => array('dir' => 'enclosure', 'mime' => 'image/gif'),
            'mp4'  => array('dir' => 'media', 'mime' => 'video/mp4'),
        );

        if (!isset($types[$ext])) {
            $this->errors = sprintf(_w('Files with extensions %s are allowed only.'), '*.jpg, *.jpeg, *.png, *.gif, *.mp4');
            return;
        }

        $target = $types[$ext];
        $path = wa()->getDataPath('plugins/dzen/'.$target['dir'], true, 'blog', true);
        if (!is_writable($path)) {
            $this->errors = _w('Insufficient write permissions.');
            return;
        }

        $base_name = preg_replace('/[^a-zA-Z0-9_\-\.]+/', '-', $file->name);
        $base_name = trim($base_name, '-');
        if ($base_name === '') {
            $base_name = 'enclosure.'. $ext;
        }

        $name = $base_name;
        $i = 1;
        while (file_exists($path.'/'.$name)) {
            $dot = strrpos($base_name, '.');
            if ($dot !== false) {
                $name = substr($base_name, 0, $dot).'-'.$i.'.'.substr($base_name, $dot + 1);
            } else {
                $name = $base_name.'-'.$i;
            }
            $i++;
        }

        if (!$file->moveTo($path, $name)) {
            $this->errors = _w('Failed to upload file.');
            return;
        }

        $url = wa()->getDataUrl('plugins/dzen/'.$target['dir'].'/'.$name, true, 'blog', true);
        $this->response = array('url' => $url, 'mime' => $target['mime']);
    }
}
