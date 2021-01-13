<?php

return [
    // 数据库
    'db' => [
        'host' => '127.0.0.1',
        'dbname' => 'joyboo',
        'username' => 'root',
        'password' => '0987abc123',
    ],

    // redis
    'redis' => [
        'host' => '127.0.0.1',
        'timeout' => 5,
        'db' => 13,
    ],

    // 日志
    'log' => [
        'level' => 'debug',
        'type' => 'file',
        'path' => ROOT_PATH . 'logs/',
    ],

    // http_server
    'http_server' => [
        'host' => '127.0.0.1',
        'port' => 9505,
        'config_set' => [], // set其他设置，可重写原有的参数
    ],
];