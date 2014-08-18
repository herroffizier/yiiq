Yiiq
====

**Yiiq** is an extension for Yii Framework, which provides a simple way to schedule, group and run jobs in background. It uses [Redis](http://redis.io/) as a storage.

Features
--------

* **Queues.** Jobs may be grouped in queues. This approach helps to prioritize and isolate jobs.
* **Job types.** Job may be one of three types: simple, scheduled or repeatable. Simple job executes once as soon as daemon has free resources. Scheduled job executes once at defined time. Repeatable job executes infinitely with defined interval.
* **Job status.** Each job has an unique id and extension provides information on job execution status.
* **Job results.** If a job returns some result, it will be automatically saved to Redis and can be accessed via extension.
* **Failure handling.** Daemon handles job failures. Once a job failed, daemon will run it again until job failure counter exceeds limit.
* **Self-checking.** Extension checks its storage for dead workers, failed jobs and so on to keep storage size as small as possible.

Requirements
------------

* Unix platform
* Redis
* PHP >= 5.4
* pcntl extension
* Yii Framework >= 1.1.14
* [YiiRedis](https://github.com/phpnode/YiiRedis)

Installation
------------

Install **Yiiq** via Composer:

```
composer require herroffizier/yiiq:dev-master
```

Add following commands to ```commandMap``` in ```console.php```:

```php
'commandMap' => array(

    // ...

    // Control Yiiq command
    'yiiq' => array(
        'class' => 'vendor.herroffizier.yiiq.commands.YiiqCommand',
    ),
    // Daemon Yiiq command
    'yiiqWorker' => array(
        'class' => 'vendor.herroffizier.yiiq.commands.YiiqWorkerCommand',
    ),

    // ...

),
```

Add extension to your ```components``` array (must be in ```main.php``` and ```console.php```):

```php
'components' => array(

    // ...

    'yiiq' => array(
        'class' => 'vendor.herroffizier.yiiq.components.Yiiq',
        // Name to identify daemon in process list (optional)
        'name' => 'Yiiq test instance',
    ),

    // ...

),
```

Finally, run daemon:

```
./yiic yiiq start --log=yiiq.log
```

Run ```ps aux | grep Yiiq``` to check if daemon started correctly. You should see string containing something like this:

```
Yiiq [Yiiq test instance] worker@default: no new jobs (0 of 5 threads busy)
```

If daemon is not running refer to ```application.log``` and ```yiiq.log``` (both in ```runtime``` folder) for details.

Usage
-----

To create a job you should create class that extends ```YiiqBaseJob``` and implements it's ```run()``` method. Take a look at example:

```php
class YiiqDummyJob extends YiiqBaseJob
{
    /**
     * Time to wait before exit.
     *
     * @var integer
     */
    public $sleep = 10;

    /**
     * This method should contain all job logic.
     *
     * @return {mixed} all returned data will be saved in redis (for non-repetable jobs)
     */
    public function run()
    {
        Yii::trace('Started dummy job '.$this->queue.':'.$this->jobId.' (sleep for '.$this->sleep.'s).');
        sleep($this->sleep);
        Yii::trace('Job '.$this->queue.':'.$this->jobId.' completed.');
    }

}
```

To add a job to queue you may use one of following calls:

```php
// Add YiiqDummyJob with default arguments to default queue.
Yii::app()->yiiq->enqueueJob('YiiqDummyJob');

// Add YiiqDummyJob with customized arguments to default queue.
Yii::app()->yiiq->enqueueJob('YiiqDummyJob', ['sleep' => 5]);

/ Add YiiqDummyJob with customized arguments to custom queue.
Yii::app()->yiiq->enqueueJob('YiiqDummyJob', ['sleep' => 5], 'custom');
```