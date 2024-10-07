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

    public function __construct(protected array $config, protected string $channel)
    {
        $config['dr_msg_cb'] = $this->deliveryReportCallback(...);
        $this->context = (new RdKafkaConnectionFactory($config))->createContext();
        $this->topic = $this->context->createTopic($channel);
        $this->delegate = $this->context->createProducer();
        $r = new ReflectionObject($this->delegate);
        $producerProperty = $r->getProperty('producer');
        $producerProperty->setAccessible(true);

        $this->vendorProducer = $producerProperty->getValue($this->delegate);
        $producerProperty->setAccessible(false);
    }

    #[\Override]
    public function setDeliverySuccessCallback(callable $callback): void
    {
        $this->deliverySuccessCallback = $callback;
    }

    #[\Override]
    public function send(Message $message): void
    {
        $kafkaMessage = $this->context->createMessage(
            body: $message->getBody(),
            properties: $message->getProperties(),
            headers: $message->getHeaders()
        );
        $kafkaMessage->setKey($message->getKey());

        $this->delegate->send(destination: $this->topic, message: $kafkaMessage);
    }

    #[\Override]
    public function poll(int $timeoutMs): void
    {
        $this->vendorProducer->poll($timeoutMs);
    }

    private function deliveryReportCallback(VendorProducer $kafka, VendorMessage $message): void
    {
        $payload = json_decode($message->payload, true);
        $id = $payload['properties']['id'];

        if ($message->err) {
            var_dump("Message with id " . $id . " failed to be delivered");
        } else {
            echo "Successfully dispatched event with id " . $id . ".\n";
            if ($this->deliverySuccessCallback !== null) {
                ($this->deliverySuccessCallback)($id);
            }
        }
    }
}
