<?php
$mem = new Memcached();
$mem->addServer('127.0.0.1', 11211);

$mem->set('test_key', 'Hello Memcached!', 60);
echo $mem->get('test_key');