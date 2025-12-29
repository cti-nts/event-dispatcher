<?php

declare(strict_types=1);

namespace Infrastructure\Messaging\Adapter\EnqueueRdkafka;

use Application\Messaging\Message;
use Application\Messaging\Producer as ApplicationProducer;
use Closure;
use Enqueue\RdKafka\RdKafkaConnectionFactory;
use Enqueue\RdKafka\RdKafkaContext;
use Enqueue\RdKafka\RdKafkaProducer;
use Enqueue\RdKafka\RdKafkaTopic;
use RdKafka\Message as VendorMessage;
use RdKafka\Producer as VendorProducer;
use ReflectionObject;

class Producer implements ApplicationProducer
{
    protected RdKafkaContext $context;

    protected RdKafkaProducer $delegate;

    protected RdKafkaTopic $topic;

    private ?Closure $deliverySuccessCallback = null;

    private readonly VendorProducer $vendorProducer;

    /** @var array<string, string> Map of message keys to event IDs */
    private array $pendingDeliveries = [];

    public function __construct(protected readonly array $config, protected readonly string $channel)
    {
        $config['dr_msg_cb'] = $this->deliveryReportCallback(...);

        $this->context = (new RdKafkaConnectionFactory($config))->createContext();
        $this->topic = $this->context->createTopic($channel);
        $this->delegate = $this->context->createProducer();

        $producerProperty = (new ReflectionObject($this->delegate))->getProperty('producer');

        $this->vendorProducer = $producerProperty->getValue($this->delegate);
    }

    public function setDeliverySuccessCallback(callable $callback): void
    {
        $this->deliverySuccessCallback = $callback;
    }

    public function send(Message $message): void
    {
        $kafkaMessage = $this->context->createMessage(
            body: $message->getBody(),
            properties: $message->getProperties(),
            headers: $message->getHeaders()
        );
        $kafkaMessage->setKey($message->getKey());

        // Store the event ID mapped to the message key for delivery callback
        $eventId = $message->getProperty('id');
        if ($eventId !== null) {
            $this->pendingDeliveries[$message->getKey()] = (string)$eventId;
        }

        $this->delegate->send(destination: $this->topic, message: $kafkaMessage);
    }

    public function poll(int $timeoutMs): void
    {
        $this->vendorProducer->poll($timeoutMs);
    }

    private function deliveryReportCallback(VendorProducer $kafka, VendorMessage $message): void
    {
        if ($message->key === null || !isset($this->pendingDeliveries[$message->key])) {
            return;
        }

        $id = $this->pendingDeliveries[$message->key];

        if ($message->err) {
            error_log("Failed to deliver event {$id} to Kafka: error code {$message->err}");
            unset($this->pendingDeliveries[$message->key]);
            return;
        }

        unset($this->pendingDeliveries[$message->key]);
        echo "Successfully dispatched event with id " . $id . "\n";

        if ($this->deliverySuccessCallback !== null) {
            ($this->deliverySuccessCallback)($id);
        }
    }
}
