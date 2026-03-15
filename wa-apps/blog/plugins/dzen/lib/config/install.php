<?php

$model = new waModel();

try {
    $model->query('SELECT post_id FROM blog_dzen_post WHERE 0');
} catch (Exception $e) {
    return;
}

$sql = "SELECT post_id, name, value
        FROM blog_post_params
        WHERE name LIKE 'dzen\\_%'";
$rows = $model->query($sql)->fetchAll();
if (!$rows) {
    return;
}

$fields = array(
    'publication_mode',
    'publication_format',
    'indexing',
    'comments',
    'guid',
    'pdalink',
    'description',
    'authors',
    'author_contact_id',
    'enclosure_url',
    'media_content_url',
    'media_thumbnail_url',
    'media_rating',
    'content_category',
);

$by_post = array();
foreach ($rows as $row) {
    $post_id = (int) $row['post_id'];
    if ($post_id <= 0) {
        continue;
    }

    $field = substr($row['name'], strlen('dzen_'));
    if (!in_array($field, $fields, true)) {
        continue;
    }

    if (!isset($by_post[$post_id])) {
        $by_post[$post_id] = array('post_id' => $post_id);
    }

    $by_post[$post_id][$field] = (string) $row['value'];
}

if (!$by_post) {
    return;
}

$dzen_model = new blogDzenPluginPostModel();
foreach ($by_post as $post_id => $data) {
    $dzen_model->saveByPostId($post_id, $data);
}
