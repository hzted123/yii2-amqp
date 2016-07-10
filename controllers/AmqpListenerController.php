<?php

namespace hzted123\amqp\controllers;

use yii\console\Exception;
use yii\helpers\Inflector;
use yii\helpers\Json;
use yii\helpers\Console;
use PhpAmqpLib\Message\AMQPMessage;
use hzted123\amqp\components\AmqpInterpreter;
use hzted123\amqp\components\AmqpTrait;


/**
 * AMQP listener controller
 */
class AmqpListenerController extends \yii\console\Controller
{
    use AmqpTrait;

    public $defaultAction = 'run';

    /**
     * Interpreter classes for AMQP messages. This class will be used if interpreter class not set for queue.
     *
     * @var array
     */
    public $interpreters = [];

    /**
     * @inheritdoc
     */
    public function options($actionId)
    {
        return array_merge(
            parent::options($actionId),
            ['exchange', 'queue']
        );
    }

    /**
     * listener for queue
     *
     * @param string $routingKey
     * @param string $type
     */
    public function actionRun()
    {
        $this->amqp->listen($this->queue, [$this, 'callback']);
    }

    public function callback(AMQPMessage $msg)
    {
        $routingKey = $msg->delivery_info['routing_key'];
        $method = 'read' . Inflector::camelize($routingKey);
        if (!isset($this->interpreters[$this->queue])) {
            $interpreter = $this;
        } elseif (class_exists($this->interpreters[$this->queue])) {
            $interpreter = new $this->interpreters[$this->queue];
            if (!$interpreter instanceof AmqpInterpreter) {
                throw new Exception(sprintf("Class '%s' is not correct interpreter class.", $this->interpreters[$this->queue]));
            }
        } else {
            throw new Exception(sprintf("Interpreter class '%s' was not found.", $this->interpreters[$this->queue]));
        }

        if (method_exists($interpreter, $method)) {
            $info = [
                'exchange' => $msg->get('exchange'),
                'queue' => $this->queue,
                'routing_key' => $msg->get('routing_key'),
                'reply_to' => $msg->has('reply_to') ? $msg->get('reply_to') : null,
            ];
            try {
                $interpreter->$method(Json::decode($msg->body, true), $info);
            } catch (\Exception $exc){
                $error_info = "consumer fail:" . $exc->getMessage()
                    .PHP_EOL."info:".print_r($info, true)
                    .PHP_EOL."body:".PHP_EOL.print_r($msg->body, true);
                \Yii::warning($error_info, __METHOD__);
                $format = [Console::FG_RED];
                Console::stdout(Console::ansiFormat($error_info . PHP_EOL, $format));
                Console::stdout(Console::ansiFormat($exc->getTraceAsString() . PHP_EOL, $format));
            }

        } else {
            if (!isset($this->interpreters[$this->queue])) {
                $interpreter = new AmqpInterpreter();
            }
            $error_info = "Unknown routing key '$routingKey' for exchange '$this->queue'.";
            $error_info .= PHP_EOL .$msg->body;
            \Yii::warning($error_info , __METHOD__);

            $interpreter->log(
                sprintf("Unknown routing key '%s' for exchange '%s'.", $routingKey, $this->queue),
                $interpreter::MESSAGE_ERROR
            );
            // debug the message
            $interpreter->log(
                print_r(Json::decode($msg->body, true), true),
                $interpreter::MESSAGE_INFO
            );
        }
    }
}
