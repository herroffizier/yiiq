# Yiiq

[![Build Status](https://travis-ci.org/herroffizier/yiiq.svg?branch=master)](https://travis-ci.org/herroffizier/yiiq) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/herroffizier/yiiq/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/herroffizier/yiiq/?branch=master) [![Code Coverage](https://scrutinizer-ci.com/g/herroffizier/yiiq/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/herroffizier/yiiq/?branch=master) [![Code Climate](https://codeclimate.com/github/herroffizier/yiiq/badges/gpa.svg)](https://codeclimate.com/github/herroffizier/yiiq)

**Yiiq** is a powerful [Redis](http://redis.io/)-backed multithreaded background job manager for Yii Framework designed with stability and simplicity in mind.

To run a job just wrap it in class and type: 
```php
Yii::app()->yiiq->enqueue('\MyJob');
```
And it's done!

## Table of contents

* [Features](#user-content-features)
* [Requirements](#user-content-requirements)
* [Installation](#user-content-installation)
* [Usage](#user-content-usage)
    * [Creating jobs](#user-content-creating-jobs)
        * [Simple job](#user-content-simple-job)
        * [Scheduled job](#user-content-scheduled-job)
        * [Repeatable job](#user-content-repeatable-job)

## Features

* **Stability**<br>If job crashed, daemon stays alive. If server crashed, daemon recovers its state with all unfinished jobs.
* **Multithreading**<br>You can run as many processes as you need.
* **Queueing**<br>Jobs may be grouped in different queues which handled by different processes.
* **Clarity**<br>You can track your job status at every point of time.
* **Scheduling**<br>Jobs may be executed at certain time or immediately. Once or many times.
* **Feedback**<br>Jobs may return result back to the extension.

## Requirements

* Unix platform
* PHP >= 5.4
* Redis
* pcntl extension
* Yii Framework >= 1.1.14
* [YiiRedis](https://github.com/phpnode/YiiRedis)

## Installation

Following steps are described assuming the fact that you have default Yii application layout, which contains two config files: ```main.php``` and ```console.php``` for web and console applications accordingly. **Yiiq** consists of two main parts too: framework extension and daemon. Extension queues jobs and daemon executes them. In view of the fact that daemon acts as console application, it will use ```console.php``` config file, whilst extension will use both files depending on application type in which it's being used. So pay attention to the fact that **Yiiq** and **YiiRedis** extensions must be included in both files.

At first, install **Yiiq** via Composer:

```
composer require herroffizier/yiiq:dev-master
```
At this point make sure that ```vendor/autoload.php``` is included in both config files.

Now we need to set up extension. Add it to your ```components``` array (must be added in both files also):

```php
'components' => array(

    // ...

    'yiiq' => array(
        'class' => '\Yiiq\Yiiq',
        // Name to identify daemon in process list (optional)
        'name' => 'Yiiq test instance',
    ),

    // ...

),
```
Note, that **YiiRedis** extension must be loaded in both files too!

Finally, add following commands to ```commandMap``` in ```console.php```:

```php
'commandMap' => array(

    // ...

    // Control Yiiq command
    'yiiq' => array(
        'class' => '\Yiiq\commands\Main',
    ),
        
    // Daemon Yiiq command
    'yiiqWorker' => array(
        'class' => '\Yiiq\commands\Worker',
    ),

    // ...

),
```

Now it's time to run daemon. Remember, daemon is responsible for job executing, therefore it must be runnign all the time. Type following command in ```protected``` folder of your application.

```
./yiic yiiq start --log=yiiq.log
```
```log``` parameter is optional but it highly recommended for first run at least.

Run ```./yiic yiiq status``` to check if daemon started correctly. You should see something like this:

```
All processes (28235) are alive. Everything looks good.
```

If daemon is not running refer to ```application.log``` and ```yiiq.log``` (both stored in ```runtime``` folder) for details.

## Usage

### Creating jobs

To create a job you should extend ```\Yiiq\jobs\Payload``` class and implement it's ```run()``` method:

```php
class YiiqDummyJob extends \Yiiq\jobs\Payload
{
    /**
     * Time to wait before exit.
     *
     * @var integer
     */
    public $sleep = 10;

    /**
     * This method should contain all the job logic.
     *
     * @return {mixed} all returned data will be saved in Redis 
     *                 (for non-repeatable jobs)
     */
    public function run()
    {
        Yii::trace(
            'Started dummy job '.$this->queue.':'.$this->id
            .' (sleep for '.$this->sleep.'s).'
        );
        sleep($this->sleep);
        Yii::trace('Job '.$this->queue.':'.$this->id.' completed.');
    }

}
```

In fact there are three types of jobs in **Yiiq**: simple, scheduled and repeatable. First one executed immediately, second one will execute at certain time, and third one will run infinitely in accordance with specified interval. 

#### Simple job

To add a simple job you may use one of following calls. As you already know, this job will be executed as soon as possible and only once. 

```php
// Add YiiqDummyJob with default arguments to default queue.

// Via arguments:
$job = Yii::app()->yiiq->enqueue('\YiiqDummyJob');

// Via method chaining:
$job = Yii::app()->yiiq->
    create('\YiiqDummyJob')->
    enqueue();

// Add YiiqDummyJob with customized arguments to default queue.

// Via arguments:
$job = Yii::app()->yiiq->enqueue(
    '\YiiqDummyJob', 
    ['sleep' => 5]
);

// Via method chaining:
$job = Yii::app()->yiiq->
    create('\YiiqDummyJob')->
    withArgs(['sleep' => 5])->
    enqueue();

// Add YiiqDummyJob with customized arguments to 'custom' queue.

// Via arguments:
$job = Yii::app()->yiiq->enqueue(
    '\YiiqDummyJob', 
    ['sleep' => 5], 
    'custom'
);

// Via method chaining:
$job = Yii::app()->yiiq->
    create('\YiiqDummyJob')->
    into('custom')->
    withArgs(['sleep' => 5])->
    enqueue();
```

#### Scheduled job

To schedule a job at certain time, you must specify time or interval:

```php
// Run job at certain time.

// Via arguments:
$job = Yii::app()->yiiq->enqueueAt(
    time() + 60, 
    'YiiqDummyJob'
);

// Via method chaining:
$job = Yii::app()->yiiq->
    create('\YiiqDummyJob')->
    runAt(time() + 60)->
    enqueue();

// Run job after 60 seconds. In fact exactly the same as above.

// Via arguments:
$job = Yii::app()->yiiq->enqueueAfter(
    60, 
    '\YiiqDummyJob'
);

// Via method chaining:
$job = Yii::app()->yiiq->
    create('\YiiqDummyJob')->
    runAt(time() + 60)->
    enqueue();
```

#### Repeatable job

To create a repeatable job, you may use following code:

```php
// Run job each 300 seconds.

// Via arguments:
$job = Yii::app()->yiiq->enqueueEach(
    300, 
    '\YiiqDummyJob'
);

// Via method chaining:
$job = Yii::app()->yiiq->
    create('\YiiqDummyJob')->
    runEach(300)->
    enqueue();
```
Note that repeatable job cannot return any data back to **Yiiq**.