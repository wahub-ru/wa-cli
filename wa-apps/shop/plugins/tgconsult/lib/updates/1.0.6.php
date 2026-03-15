<?php
$model = new waModel();
$sqls = [
    "ALTER TABLE `shop_tgconsult_chat` 
       MODIFY `id` INT UNSIGNED NOT NULL AUTO_INCREMENT",
    "ALTER TABLE `shop_tgconsult_chat` 
       ADD PRIMARY KEY (`id`)",
    "ALTER TABLE `shop_tgconsult_message` 
       MODIFY `id` INT UNSIGNED NOT NULL AUTO_INCREMENT",
    "ALTER TABLE `shop_tgconsult_message` 
       ADD PRIMARY KEY (`id`)"
];
foreach ($sqls as $sql) {
    try { $model->exec($sql); } catch (Exception $e) {}
}
