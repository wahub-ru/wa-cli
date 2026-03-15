<?php
/*
 * @link https://warslab.ru/
 * @author waResearchLab
 * @Copyright (c) 2021 waResearchLab
 */
interface shopHidsetPluginCliTaskInterface
{
    public function getCommand(): string;

    public function getFormData();
    public function run($params = null);
    public function getDescription(): string;
}