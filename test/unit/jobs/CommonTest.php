<?php
/**
 * Yiiq - background job queue manager for Yii
 *
 * This file contains common tests for jobs.
 *
 * @author  Martin Stolz <herr.offizier@gmail.com>
 * @package yiiq.tests.unit.jobs
 */

namespace Yiiq\test\unit\jobs;

use Yiiq\test\cases\Job;

class CommonTest extends Job
{
    /**
     * @dataProvider startParametersProvider
     */
    public function testJobStates($queue, $threads)
    {
        $this->assertNotContains($this->getBaseProcessTitle(), $this->exec('ps aux'));
        $this->startYiiq($queue, $threads);

        $id = \Yii::app()->yiiq->enqueueJob('\Yiiq\test\jobs\WaitJob', ['sleep' => 2], $queue);

        usleep(1500000);

        $this->assertFalse(\Yii::app()->yiiq->isFailed($id));
        $this->assertFalse(\Yii::app()->yiiq->isCompleted($id));
        $this->assertTrue(\Yii::app()->yiiq->isExecuting($id));

        usleep(2500000);

        $this->assertFalse(\Yii::app()->yiiq->isFailed($id));
        $this->assertTrue(\Yii::app()->yiiq->isCompleted($id));
        $this->assertFalse(\Yii::app()->yiiq->isExecuting($id));

        $this->stopYiiq();
    }

    /**
     * @dataProvider startParametersProvider
     */
    public function testResultSaving($queue, $threads)
    {
        $this->assertNotContains($this->getBaseProcessTitle(), $this->exec('ps aux'));
        $this->startYiiq($queue, $threads);

        $result = rand();
        $id = \Yii::app()->yiiq->enqueueJob('\Yiiq\test\jobs\ReturnJob', ['result' => $result], $queue);

        $this->waitForJobs($threads, 1);

        $size = filesize($this->getLogPath());
        $this->assertEquals(0, $size);
        $this->assertFalse(\Yii::app()->yiiq->isFailed($id));
        $this->assertTrue(\Yii::app()->yiiq->isCompleted($id));
        $this->assertFalse(\Yii::app()->yiiq->isExecuting($id));
        $this->assertEquals($result, \Yii::app()->yiiq->getJobResult($id));
        $this->assertEquals($result, \Yii::app()->yiiq->getJobResult($id, true));
        $this->assertNull(\Yii::app()->yiiq->getJobResult($id));

        $this->stopYiiq();
    }
}