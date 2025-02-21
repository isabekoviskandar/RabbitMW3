<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use PhpAmqpLib\Connection\AMQPStreamConnection;

class ConsumeRabbitMQMessages extends Command
{
    protected $signature = 'rabbitmq:consume';
    protected $description = 'Consume messages from RabbitMQ';

    public function handle()
    {
        $connection = new AMQPStreamConnection(
            env('RABBITMQ_HOST'),
            env('RABBITMQ_PORT'),
            env('RABBITMQ_USER'),
            env('RABBITMQ_PASSWORD'),
            env('RABBITMQ_VHOST')
        );

        $channel = $connection->channel();
        
        $channel->queue_declare(
            'my_queue',
            false,
            true,
            false,
            false
        );

        $this->info(' [*] Waiting for messages. To exit press CTRL+C');

        $callback = function ($msg) {
            $data = json_decode($msg->body, true);
            $this->info(" [x] Received message: " . $msg->body);
            
            // Process your message here
            // For example, you could dispatch a job or trigger an event
            
            $msg->ack(); // Acknowledge the message
        };

        $channel->basic_qos(null, 1, null);
        $channel->basic_consume(
            'my_queue',
            '',
            false,
            false,
            false,
            false,
            $callback
        );

        while ($channel->is_consuming()) {
            $channel->wait();
        }

        $channel->close();
        $connection->close();
    }
}