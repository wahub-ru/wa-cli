<?php

class shopSeonofcharPlugin extends shopPlugin
{
    public function backendCategoryDialog()
    {
        $view = wa()->getView();
        $view->assign('css', $this->getCss());
        $view->assign('js', $this->getJs());
        $view->assign('title_min_max', $this->getMinMax('title_min_max'));
        $view->assign('key_min_max', $this->getMinMax('key_min_max'));
        $view->assign('desc_min_max', $this->getMinMax('desc_min_max'));
        $html = $view->fetch($this->path . '/templates/category.html');
        return $html;
    }

    public function backendProduct()
    {
        $view = wa()->getView();
        $view->assign('css', $this->getCss());
        $view->assign('js', $this->getJs());
        $view->assign('title_min_max', $this->getMinMax('title_min_max'));
        $view->assign('key_min_max', $this->getMinMax('key_min_max'));
        $view->assign('desc_min_max', $this->getMinMax('desc_min_max'));
        $html = $view->fetch($this->path . '/templates/product.html');

        return array(
            'edit_descriptions' => $html,
        );
    }

    public function pageEdit()
    {
        $view = wa()->getView();
        $view->assign('css', $this->getCss());
        $view->assign('js', $this->getJs());
        $view->assign('title_min_max', $this->getMinMax('title_min_max'));
        $view->assign('key_min_max', $this->getMinMax('key_min_max'));
        $view->assign('desc_min_max', $this->getMinMax('desc_min_max'));
        $html = $view->fetch($this->path . '/templates/page.html');
        return $html;
    }

    protected function getMinMax($who)
    {
        $min_max = array();
        $_min_max = $this->getSettings($who);

        if (!empty($_min_max)) {
            if (preg_match('/^([0-9]+)-([0-9]+)$/', $_min_max, $matches)) {
                $min_max[0] = $matches[1];
                $min_max[1] = $matches[2];
            }
        }

        return $min_max;
    }

    protected function getCss()
    {
        return wa('shop')->getAppStaticUrl('shop', true) . 'plugins/seonofchar/css/seonofchar.min.css?v' . $this->getVersion() . $this->getBuild();
    }

    protected function getJs()
    {
        return wa('shop')->getAppStaticUrl('shop', true) . 'plugins/seonofchar/js/seonofchar.min.js?v' . $this->getVersion() . $this->getBuild();
    }

    protected function getBuild()
    {
        if (file_exists($this->path . '/lib/config/build.php')) {
            return '.' . include($this->path . '/lib/config/build.php');
        } else {
            return '';
        }
    }
}
