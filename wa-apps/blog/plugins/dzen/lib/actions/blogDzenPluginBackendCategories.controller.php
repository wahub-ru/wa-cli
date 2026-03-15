<?php

class blogDzenPluginBackendCategoriesController extends waController
{
    public function execute()
    {
        $limit = 50;
        $query = waRequest::request('q', '', waRequest::TYPE_STRING_TRIM);

        $plugin = waSystem::getInstance()->getPlugin('dzen');
        $categories = preg_split('/[\r\n]+/', (string) $plugin->getSettings('content_categories', ''), -1, PREG_SPLIT_NO_EMPTY);
        $categories = array_values(array_unique(array_filter(array_map('trim', $categories), 'strlen')));

        if ($query !== '') {
            $q = mb_strtolower($query);
            $categories = array_values(array_filter($categories, function ($item) use ($q) {
                return mb_strpos(mb_strtolower($item), $q) !== false;
            }));
        }

        $categories = array_slice($categories, 0, $limit);
        foreach ($categories as &$category) {
            $category = htmlspecialchars($category);
        }
        unset($category);

        echo implode("
", $categories);
    }
}
