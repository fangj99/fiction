<?php

return [
    'timeZone' => 'Asia/Shanghai',
    'language' => 'zh-CN',
    'vendorPath' => dirname(dirname(__DIR__)).'/vendor',
    'components' => [
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
        ],
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'log' => [
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                    'prefix' => function ($message) {
                        return '';//去掉消息返回的[IP address][User ID][Session ID][Severity Level]
                    },
                ],
            ],
        ],
    ],
];
