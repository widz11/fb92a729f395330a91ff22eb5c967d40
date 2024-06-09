<?php

namespace Core\Foundation\Queue;

use Core\Foundation\Config\Config;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class RabbitMQ {
    protected AMQPChannel $channel;
    protected AMQPStreamConnection $connection;

    protected function connection() {
        $config = Config::get('queue.connections.rabbitmq');
        try {
            $this->connection = new AMQPStreamConnection(
                data_get($config, 'host'), 
                data_get($config, 'port'), 
                data_get($config, 'login'), 
                data_get($config, 'password')
            );
            $this->channel = $this->connection->channel();
        } catch(\Exception $e) {
            error_log('Connection queue failed: ' . $e->getMessage());
        }
    }

    protected function queue(string $queueName) {
        try {
            $this->channel->queue_declare($queueName, false, true, false, false);
        } catch(\Exception $e) {
            error_log('Queue declare failed: ' . $e->getMessage());
        }
    }

    protected function close() {
        try {
            $this->channel->close();
            $this->connection->close();
        } catch(\Exception $e) {
            error_log('Close connection queue failed: ' . $e->getMessage());
        }
    }

    public function publish(
        array $data = [], 
        string $queueName, 
        string $exchangeName = '',
        string $routingKey = ''
    ) {
        // Init connection
        $this->connection();
        // Init queue
        $this->queue($queueName);
        try {
            // Publish data
            $body = json_encode($data);
            $message = new AMQPMessage($body);
            $this->channel->basic_publish($message, $exchangeName, $routingKey);
        } catch (\Exception $e) {
            error_log('Queue failed publish: ' . $e->getMessage());
        }
        // Close connection
        $this->close();
    }
}