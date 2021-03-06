<?php
/**
 * Yiiq - background job queue manager for Yii
 *
 * This file contains job runner class.
 *
 * @author  Martin Stolz <herr.offizier@gmail.com>
 * @package yiiq.commands
 */

namespace Yiiq\jobs;

use Yiiq\Yiiq;
use Yiiq\base\Component;

/**
 * Job runner class.
 *
 * @author  Martin Stolz <herr.offizier@gmail.com>
 */
class Runner extends Component
{
    /**
     * @var Job
     */
    protected $job = null;

    public function __construct(Yiiq $owner, Job $job)
    {
        parent::__construct($owner);
        $this->job = $job;
    }

    public function run()
    {
        $job = $this->job;

        // Try to fork process.
        $childPid = pcntl_fork();

        // Force reconnect to redis for parent and child due to bug in PhpRedis
        // (https://github.com/nicolasff/phpredis/issues/474).
        \Yii::app()->redis->getClient(true);

        if ($childPid > 0) {
            return $childPid;
        } elseif ($childPid < 0) {
            // If we're failed to fork process, restore job and exit.
            \Yii::app()->yiiq->restore($job->id);

            return;
        }

        // We are child - get our pid.
        $childPid = posix_getpid();
        
        $metadata = $job->metadata;
        $status = $job->status;
        
        $this->owner->setProcessTitle(
            'job',
            $metadata->queue,
            'executing '.$metadata->id.' ('.$metadata->class.')'
        );

        \Yii::trace('Starting job '.$metadata->queue.':'.$job->id.' ('.$metadata->class.')...');

        $status->markAsStarted($childPid);

        $payload = $job->payload;
        $result = $payload->execute($metadata->args);

        if ($metadata->type === Yiiq::TYPE_REPEATABLE) {
            $status->markAsStopped();
        } else {
            $metadata->delete();
            $job->result->save($result);
            $status->markAsCompleted();
        }

        \Yii::trace('Job '.$metadata->queue.':'.$job->id.' done.');

        exit(0);
    }
}
