# Service Container & DI

## Default Configuration (`config/services.yaml`)

Symfony's `_defaults` block activates autowiring and autoconfiguration project-wide. This eliminates manual service registration for the vast majority of classes:

```yaml
# config/services.yaml
services:
    _defaults:
        autowire: true       # Constructor arguments resolved by type-hint
        autoconfigure: true  # Tags applied automatically (commands, subscribers, etc.)
        public: false        # Services are private by default

    App\:
        resource: '../src/'
        exclude:
            - '../src/DependencyInjection/'
            - '../src/Entity/'
            - '../src/Kernel.php'
```

## Interface Binding

When multiple concrete classes implement the same interface, tell the container which one to inject:

```yaml
# config/services.yaml
services:
    # Bind a default implementation for an interface
    App\Service\PaymentProcessorInterface: '@App\Service\StripePaymentProcessor'

    # Bind different implementations per argument name
    App\Service\NotifierInterface $emailNotifier: '@App\Service\EmailNotifier'
    App\Service\NotifierInterface $smsNotifier:   '@App\Service\SmsNotifier'
```

## `#[Autowire]` Attribute (Symfony 6.1+)

Use `#[Autowire]` to inject scalar parameters, env vars, or specific services without touching `services.yaml`:

```php
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final class PaymentService
{
    public function __construct(
        private readonly PaymentGatewayInterface $gateway,

        // Inject a container parameter
        #[Autowire('%app.payment.currency%')]
        private readonly string $currency,

        // Inject an environment variable
        #[Autowire(env: 'STRIPE_SECRET_KEY')]
        private readonly string $stripeKey,

        // Inject a specific service by ID
        #[Autowire(service: 'cache.app')]
        private readonly CacheInterface $cache,
    ) {}
}
```

## Tagged Services

`autoconfigure: true` automatically tags classes that implement known interfaces (e.g., `EventSubscriberInterface`, `ConsoleCommandInterface`). For custom tags, use `#[AutoconfigureTag]`:

```php
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('app.report_generator')]
interface ReportGeneratorInterface {}
```

```yaml
# config/services.yaml
services:
    App\Reporting\ReportingService:
        arguments:
            $generators: !tagged_iterator app.report_generator
```
