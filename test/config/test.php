<?php
/**
 * Yiiq - background job queue manager for Yii
 *
 * This file contains test config.
 *
 * @author  Martin Stolz <herr.offizier@gmail.com>
 * @package yiiq.tests.config
 */

require_once __DIR__.'/token.php';
require_once __DIR__.'/../../vendor/autoload.php';

return array(
    'basePath' => __DIR__.'/..',
    'extensionPath' => __DIR__.'/../..',

    'aliases' => array(
        'vendor' => __DIR__.'/../../vendor',
    ),

    'preload' => array(
        'log',
    ),

    'components' => array(
        'log'=>array(
            'class'=>'CLogRouter',
        ),

        'redis' => array(
            'class' => 'vendor.codemix.yiiredis.ARedisConnection',
            'hostname' => 'localhost',
            'port' => 6379,
            'database' => 2,
            'prefix' => 'yiiqtest:'.TEST_TOKEN.':',
        ),

        'yiiq' => array(
            'class' => '\Yiiq\Yiiq',
            'name' => 'YiiqTest_'.TEST_TOKEN,
            'faultIntervals' => [1, 1, 1],
        ),
    ),

    'commandMap' => array(
        'yiiq' => array(
            'class' => '\Yiiq\commands\Main',
        ),
        'yiiqWorker' => array(
            'class' => '\Yiiq\commands\Worker',
        ),
    ),
);
