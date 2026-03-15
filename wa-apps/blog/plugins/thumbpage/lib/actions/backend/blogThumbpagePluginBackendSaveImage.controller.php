<?php

class blogThumbpagePluginBackendSaveImageController extends waJsonController
{

    public function execute()
    {
        $file = waRequest::file('thumbpage');
        $pageId = waRequest::post('page_id');
        if ($file->uploaded()) {
            $image_path = wa()->getDataPath('plugins/thumbpage/images/', 'blog');
            $path_info = pathinfo($file->name);
            $name = $this->uniqueName($image_path, $path_info['extension']);
            $app_settings_model = new waAppSettingsModel();
            try {
                $file->waImage()->save($image_path . $name);
                $this->response['preview'] = wa()->getDataUrl('plugins/thumbpage/images/' . $name, true, 'blog');
                $pageModel = new blogPageModel();
                $page = $pageModel->getById($pageId);
                if ($page['thumbpage']) {
                    @unlink($image_path . $page['thumbpage']);
                }
                $pageModel->updateById($pageId, array('thumbpage' => $name));
            } catch (Exception $e) {
                $this->setError($e->getMessage());
            }
        }
    }

    protected function uniqueName($path, $extension)
    {
        $alphabet = "abcdefghijklmnopqrstuwxyzABCDEFGHIJKLMNOPQRSTUWXYZ0123456789";
        do {
            $name = '';
            for ($i = 0; $i < 10; $i++) {
                $n = rand(0, strlen($alphabet) - 1);
                $name .= $alphabet{$n};
            }
            $name .= '.' . $extension;
        } while (file_exists($path . $name));
        return $name;
    }

}
