Yii2 amqp consumer tool
=======================
A tool to amqp consumer.
It can declare exchange and queue with config.
And Keep consumer alive with cronjobs.

This extension is based on [this](https://github.com/webtoucher/yii2-amqp).
Thanks [webtoucher](https://github.com/webtoucher/).

But with a few change:

- consumer setting by queue, not route or exchange
- all exchange and queue setting in config
- add listener-manage controller

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist hzted123/yii2-amqp "*"
```

or add

```
"hzted123/yii2-amqp": "*"
```

to the require section of your `composer.json` file.


Usage
-----

Once the extension is installed, simply use it in your code by  :

Add the following in your console config:

```php
return [
    ...
    'components' => [
        ...
        'amqp' => [
            'class' => 'hzted123\amqp\components\Amqp',
            'host' => '******',
            'port' => 5672,
            'user' => '******',
            'password' => '******',
            'vhost' => '/',
            'exchange_configs' => [
                'exchange_name' => [
                    'options' => ['type' => 'topic', 'passive' => false, 'auto_delete' => false, 'durable' => true ],
                ],
                ... ...
            ],
            'queue_configs' => [
                'queue_name'    =>  [
                    'options' => ['passive' => false, 'auto_delete' => false, 'durable' => true, 'exclusive' => false],
                    'arguments' => ['x-max-length' => ['I', 1000000], 'x-max-length-bytes' => ['I', 300485760]],
                    'binds' => ['exchange_name' => 'route']
                ],
                ... ... 
            ],
        ],
        ...
        'controllerMap' => [
            'cron' => [
                'class' => 'mitalcoi\cronjobs\CronController',
                'interpreterPath' => '/usr/bin/php',
                'logsDir' => '/data/logs/cron',
                'logFileName' => '%L/php-%C.%A.%D.log',
                'bootstrapScript' => (dirname(dirname(__FILE__)) .'/yii',
                'cronJobs' =>[
                    'listener-manage/keep' => [
                        'cron'      => '* * * * *',
                    ]
                ],
            ]
            ...
            'listener' => [
                'class' => 'hzted123\amqp\controllers\AmqpListenerController',
                'interpreters' => [
                    'queue_name' => '@app\components\DemoEventInterpreter', // interpreters for each queue
                ],
            ]
            ...
        ],
        ...
        'listener-manage' => [      //consumer keeper
            'class' => 'hzted123\amqp\controllers\ListenerManageController',
            'configs' => [
                ['queue' => 'queue_name', 'count' => 2]      // Keeping the number of consumers
            ]
        ],
```

Add messages interpreter class `@app/components/DemoEventInterpreter` with your handlers for different routing keys:

```php
<?php

namespace app\components;

use hzted123\amqp\components\AmqpInterpreter;


class DemoEventInterpreter extends AmqpInterpreter
{
    /**
     * Interprets AMQP message with routing key 'hello_world'.
     *
     * @param array $message
     */
    public function readHelloWorld($message)
    {
        // todo: write message handler
        $this->log(print_r($message, true));
    }
}
```

## Usage

Just run command 

```bash
$ php yii listener-manage keep
```

to start all consumers , or like this

```bash
* * * * * php yii listener-manage keep
```

Run command

```bash
$ php yii listener-manage kill
```

to kill all consumers, when you deploy new code.

Run command

```bash
$ php yii listener --queue=queue_name
```

to listen a queue on selected queue.

Also you can create controllers for your needs. Just use for your web controllers class
`hzted123\amqp\controllers\AmqpConsoleController` instead of `yii\web\Controller` and for your console controllers
class `hzted123\amqp\controllers\AmqpConsoleController` instead of `yii\console\Controller`. AMQP connection will be
available with property `connection`. AMQP channel will be available with property `channel`.