<?php

namespace hzted123\amqp\components;

use yii\helpers\Console;


/**
 * AMQP interpreter class.
 *
 */
class AmqpInterpreter
{
    const MESSAGE_INFO = 0;
    const MESSAGE_ERROR = 1;

    /**
     * Logs info and error messages.
     *
     * @param $message
     * @param $type
     */
    public static function log($message, $type = self::MESSAGE_INFO) {
        $format = [$type == self::MESSAGE_ERROR ? Console::FG_RED : Console::FG_BLUE];
        Console::stdout(Console::ansiFormat($message . PHP_EOL, $format));
    }
}