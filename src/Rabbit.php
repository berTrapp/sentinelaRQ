<?php

namespace App;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Channel\AMQPChannel;

class Rabbit
{
    private AMQPStreamConnection $connection;
    private AMQPChannel $channel;
    private string $queue;

    public function __construct(array $config)
    {
        $rabbit = $config['rabbitmq'];

        $this->connection = new AMQPStreamConnection(
            $rabbit['host'],
            $rabbit['port'],
            $rabbit['user'],
            $rabbit['pass'],
            $rabbit['vhost']
        );

        $this->channel = $this->connection->channel();
        $this->queue = $config['queue'];
        $this->channel->queue_declare($this->queue, false, true, false, false);
    }

    public function publish(array $data): void
    {
        $message = new AMQPMessage(
            json_encode($data),
            [
                'content_type' => 'application/json',
                'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT
            ]
        );

        $this->channel->basic_publish($message, '', $this->queue);
    }

    public function consume(callable $callback): void
    {
        $this->channel->basic_qos(null, 1, null);

        $this->channel->basic_consume($this->queue, '', false, false, false, false, $callback);
        
        while ($this->channel->is_consuming()) {
            $this->channel->wait();
        }
    }

    public function close(): void
    {
        $this->channel->close();
        $this->connection->close();
    }

}