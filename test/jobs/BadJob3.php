<?php
/**
 * Yiiq - background job queue manager for Yii
 *
 * This file contains bad job class #3.
 *
 * @author  Martin Stolz <herr.offizier@gmail.com>
 * @package yiiq.tests.jobs
 */

namespace Yiiq\test\jobs;

class BadJob3 extends \Yiiq\jobs\Payload
{
    public function run()
    {
        die('test');
    }
}
