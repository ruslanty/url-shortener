<?php

declare(strict_types=1);

namespace ShortURL\Adapter\Messaging;

use DateTimeImmutable;
use JsonException;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;
use Ramsey\Uuid\Uuid;
use RuntimeException;
use ShortURL\Application\Port\ShortLinkRepository;
use ShortURL\Domain\ClickEvent;
use ShortURL\Domain\ShortLink;
use Throwable;
use UnexpectedValueException;

final readonly class ClickWorker
{
    public const string QUEUE = 'shortlink.clicks.v1';

    public function __construct(
        private ShortLinkRepository $links,
        private AMQPChannel $channel,
    ) {
        $this->channel->queue_declare(self::QUEUE, false, true, false, false);
        $this->channel->confirm_select();
    }

    public function runForever(): never
    {
        while (true) {
            try {
                if (! $this->runOneCycle()) {
                    usleep(250_000);
                }
            } catch (Throwable $exception) {
                fwrite(STDERR, 'Worker failed: ' . $exception->getMessage() . PHP_EOL);

                usleep(1_000_000);
            }
        }
    }

    /**
     * @throws \Throwable
     * @throws \JsonException
     */
    public function runOneCycle(): bool
    {
        $worked = $this->publishOnePendingEvent();

        return $this->consumeOneMessage() || $worked;
    }

    private function publishOnePendingEvent(): bool
    {
        $event = $this->links->nextPendingEvent();

        if (! $event instanceof ClickEvent) {
            return false;
        }

        try {
            $body = json_encode([
                'eventId' => $event->id,
                'code' => $event->code,
                'occurredAt' => $event->occurredAt->format(DateTimeImmutable::ATOM),
            ], JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException(
                'Couldn\'t encode the event.',
                $exception->getCode(),
                previous: $exception,
            );
        }

        $message = new AMQPMessage($body, [
            'content_type' => 'application/json',
            'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
        ]);

        $this->channel->basic_publish($message, '', self::QUEUE);
        $this->channel->wait_for_pending_acks(5.0);

        $this->links->markPublished($event->id);

        return true;
    }

    /**
     * @throws \DateMalformedStringException
     * @throws \Throwable
     * @throws \JsonException
     */
    private function consumeOneMessage(): bool
    {
        $message = $this->channel->basic_get(self::QUEUE);

        if (! $message instanceof AMQPMessage) {
            return false;
        }

        try {
            $payload = json_decode($message->getBody(), true, 512, JSON_THROW_ON_ERROR);

            $hasValidPayloadShape = is_array($payload)
                && is_string($payload['eventId'] ?? null)
                && is_string($payload['code'] ?? null)
                && is_string($payload['occurredAt'] ?? null);

            if (! $hasValidPayloadShape) {
                throw new UnexpectedValueException('Missing required fields.');
            }

            $isValidEvent = Uuid::isValid($payload['eventId'])
                && ShortLink::hasValidCode($payload['code']);

            if (! $isValidEvent) {
                throw new UnexpectedValueException('Invalid ID or short code.');
            }

            $event = new ClickEvent(
                $payload['eventId'],
                $payload['code'],
                new DateTimeImmutable($payload['occurredAt']),
            );
        } catch (Throwable $exception) {
            $this->channel->basic_nack($message->getDeliveryTag());

            fwrite(STDERR, 'Invalid event: ' . $exception->getMessage() . PHP_EOL);

            return true;
        }

        try {
            $this->links->recordClick($event);

            $this->channel->basic_ack($message->getDeliveryTag());
        } catch (Throwable $exception) {
            $this->channel->basic_nack($message->getDeliveryTag(), false, true);

            throw $exception;
        }

        return true;
    }
}
