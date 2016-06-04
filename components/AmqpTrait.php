<?php

namespace hzted123\amqp\components;

use Yii;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPConnection;
use hzted123\amqp\components\Amqp;


/**
 * AMQP trait for controllers.
 *
 * @property Amqp $amqp AMQP object.
 * @property AMQPConnection $connection AMQP connection.
 * @property AMQPChannel $channel AMQP channel.
 * @since 2.0
 */
trait AmqpTrait
{
    /**
     * @var Amqp
     */
    protected $amqpContainer;

    /**
     * Listened exchange.
     *
     * @var string
     */
    public $exchange;

    /**
     * Listened Queue.
     *
     * @var string
     */
    public $queue;

    /**
     * Returns AMQP object.
     *
     * @return Amqp
     */
    public function getAmqp()
    {
        if (empty($this->amqpContainer)) {
            $this->amqpContainer = Yii::$app->amqp;
        }
        return $this->amqpContainer;
    }

    /**
     * Returns AMQP connection.
     *
     * @return AMQPConnection
     */
    public function getConnection()
    {
        return $this->amqp->getConnection();
    }

    /**
     * Returns AMQP channel.
     *
     * @param string $channel_id
     * @return AMQPChannel
     */
    public function getChannel($channel_id = null)
    {
        return $this->amqp->getChannel($channel_id);
    }

    /**
     * Sends message to the exchange.
     *
     * @param string $routing_key
     * @param string|array|AMQPMessage $message
     * @param string $exchange
     * @param string $type
     * @return void
     */
    public function send($routing_key, $message, $exchange = null, $type = Amqp::TYPE_TOPIC)
    {
        $this->amqp->send($exchange ?: $this->exchange, $routing_key, $message, $type);
    }

    /**
     * Sends message to the exchange and waits for answer.
     *
     * @param string $queue
     * @param string $exchange
     * @param string $routing_key
     * @param string|array|AMQPMessage $message
     * @param int $timeout
     * @return string
     */
    public function ask($queue, $exchange, $routing_key, $message, $timeout = 10)
    {
        return $this->amqp->ask($queue, $exchange, $routing_key, $message, $timeout);
    }
}
