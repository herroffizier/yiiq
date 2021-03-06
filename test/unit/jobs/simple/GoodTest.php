<?php
/**
 * Yiiq - background job queue manager for Yii
 *
 * This file contains tests for good simple jobs.
 *
 * @author  Martin Stolz <herr.offizier@gmail.com>
 * @package yiiq.tests.unit.jobs.simple
 */

namespace Yiiq\test\unit\jobs\simple;

use Yiiq\test\cases\Job;

class GoodTest extends Job
{
    /**
     * @dataProvider startParametersProvider
     */
    public function testSimpleJob($queue, $threads)
    {
        $logPath    = $this->getLogPath();
        $goodFile   = 'goodjob_'.TEST_TOKEN;
        $goodPath   = $this->getRuntimePath().'/'.$goodFile;

        $this->startYiiq($queue, $threads);

        $this->assertFalse(file_exists($goodPath));
        $job = \Yii::app()->yiiq->enqueue('\Yiiq\test\jobs\GoodJob', ['file' => $goodFile], $queue);

        $this->waitForJobs($threads, 1);

        $this->assertEquals(0, filesize($logPath));
        $this->assertTrue(file_exists($goodPath));
        $this->assertTrue($job->status->isCompleted);
        $this->assertFalse($job->status->isFailed);
        $this->assertFalse($job->status->isExecuting);

        $this->assertTrue(\Yii::app()->yiiq->health->check(false));
        $this->stopYiiq();
    }

    /**
     * @dataProvider startParametersProvider
     */
    public function testManySimpleJobsAfterStart($queue, $threads)
    {
        $procTitle  = $this->getBaseProcessTitle();
        $logPath    = $this->getLogPath();
        $goodFile   = 'goodjob_'.TEST_TOKEN.'_';
        $goodPath   = $this->getRuntimePath().'/'.$goodFile;

        $this->assertNotContains($procTitle, $this->exec('ps aux'));
        $this->startYiiq($queue, $threads);

        $jobs = [];
        for ($i = 1; $i < 4; $i++) {
            $this->assertFalse(file_exists($goodPath.$i));
            $jobs[$i] = \Yii::app()->yiiq->enqueue('\Yiiq\test\jobs\GoodJob', ['file' => $goodFile.$i], $queue);
            $this->assertFalse($jobs[$i]->status->isCompleted);
            $this->assertFalse($jobs[$i]->status->isFailed);
        }

        $this->waitForJobs($threads, 4);

        $this->assertEquals(0, filesize($logPath));
        for ($i = 1; $i < 4; $i++) {
            $this->assertTrue(file_exists($goodPath.$i));
            $this->assertTrue($jobs[$i]->status->isCompleted);
            $this->assertFalse($jobs[$i]->status->isFailed);
            $this->assertFalse($jobs[$i]->status->isExecuting);
        }

        $this->assertTrue(\Yii::app()->yiiq->health->check(false));
        $this->stopYiiq();
    }

    /**
     * @dataProvider startParametersProvider
     */
    public function testManySimpleJobsBeforeStart($queue, $threads)
    {
        $procTitle  = $this->getBaseProcessTitle();
        $logPath    = $this->getLogPath();
        $goodFile   = 'goodjob_'.TEST_TOKEN.'_';
        $goodPath   = $this->getRuntimePath().'/'.$goodFile;

        $this->assertNotContains($procTitle, $this->exec('ps aux'));

        $jobs = [];
        for ($i = 1; $i < 4; $i++) {
            $this->assertFalse(file_exists($goodPath.$i));
            $jobs[$i] = \Yii::app()->yiiq->enqueue('\Yiiq\test\jobs\GoodJob', ['file' => $goodFile.$i], $queue);
            $this->assertFalse($jobs[$i]->status->isCompleted);
            $this->assertFalse($jobs[$i]->status->isFailed);
        }

        $this->startYiiq($queue, $threads);

        $this->waitForJobs($threads, 4);
        usleep(self::TIME_TO_START);

        $this->assertEquals(0, filesize($logPath));
        for ($i = 1; $i < 4; $i++) {
            $this->assertTrue(file_exists($goodPath.$i));
            $this->assertTrue($jobs[$i]->status->isCompleted);
            $this->assertFalse($jobs[$i]->status->isFailed);
            $this->assertFalse($jobs[$i]->status->isExecuting);
        }

        $this->assertTrue(\Yii::app()->yiiq->health->check(false));
        $this->stopYiiq();
    }
}
