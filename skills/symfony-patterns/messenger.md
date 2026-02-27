# Events & Messenger

## Symfony Events — EventSubscriberInterface

Event subscribers are auto-tagged by `autoconfigure: true`. They decouple cross-cutting concerns (logging, notifications, cache invalidation) from core services:

```php
// src/EventSubscriber/OrderNotificationSubscriber.php
namespace App\EventSubscriber;

use App\Event\OrderPlacedEvent;
use App\Service\NotificationService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class OrderNotificationSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly NotificationService $notifications,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            OrderPlacedEvent::class => 'onOrderPlaced',
        ];
    }

    public function onOrderPlaced(OrderPlacedEvent $event): void
    {
        $this->notifications->sendOrderConfirmation($event->getOrder());
    }
}
```

Dispatch custom events via `EventDispatcherInterface`:

```php
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class OrderService
{
    public function __construct(
        private readonly EventDispatcherInterface $dispatcher,
    ) {}

    public function place(Order $order): void
    {
        // ... persist ...
        $this->dispatcher->dispatch(new OrderPlacedEvent($order));
    }
}
```

## Symfony Messenger — Async Processing

Messenger decouples work into messages handled synchronously or asynchronously via transports (AMQP, Redis, Doctrine, SQS).

**Message DTO** (immutable, no logic):

```php
// src/Message/SendInvoiceEmail.php
namespace App\Message;

use Symfony\Component\Messenger\Attribute\AsMessage;

#[AsMessage('async')]  // Routes to 'async' transport automatically (Symfony 7.2+)
final readonly class SendInvoiceEmail
{
    public function __construct(
        public int $orderId,
        public string $recipientEmail,
    ) {}
}
```

**Handler** (single responsibility, invokable):

```php
// src/MessageHandler/SendInvoiceEmailHandler.php
namespace App\MessageHandler;

use App\Message\SendInvoiceEmail;
use App\Service\InvoiceService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class SendInvoiceEmailHandler
{
    public function __construct(
        private readonly InvoiceService $invoices,
    ) {}

    public function __invoke(SendInvoiceEmail $message): void
    {
        $this->invoices->sendEmail($message->orderId, $message->recipientEmail);
    }
}
```

**Dispatch from a service** (fire-and-forget):

```php
use Symfony\Component\Messenger\MessageBusInterface;

final class OrderService
{
    public function __construct(
        private readonly MessageBusInterface $bus,
    ) {}

    public function place(Order $order): void
    {
        // ... persist ...
        $this->bus->dispatch(new SendInvoiceEmail($order->getId(), $order->getEmail()));
    }
}
```

**Transport config** (`config/packages/messenger.yaml`):

```yaml
framework:
    messenger:
        transports:
            async:
                dsn: '%env(MESSENGER_TRANSPORT_DSN)%'
                options:
                    use_notify: true
                    check_delayed_interval: 1000
                retry_strategy:
                    max_retries: 3
                    delay: 1000
                    multiplier: 2

        routing:
            # Fallback routing for messages without #[AsMessage]
            'App\Message\LegacyJob': async
```

Run the consumer worker:

```bash
bin/console messenger:consume async --time-limit=3600
```
