# Testing

## Functional Tests with `WebTestCase`

Hard-code URLs in functional tests — do **not** use `$router->generate()`. This ensures route renames surface as test failures rather than silently passing:

```php
// tests/Controller/OrderControllerTest.php
namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class OrderControllerTest extends WebTestCase
{
    #[DataProvider('providePublicUrls')]
    public function testPublicPagesLoad(string $url): void
    {
        $client = static::createClient();
        $client->request('GET', $url);

        self::assertResponseIsSuccessful();
    }

    public static function providePublicUrls(): iterable
    {
        yield 'home'     => ['/'];
        yield 'products' => ['/products'];
    }

    public function testCreateOrderRequiresAuth(): void
    {
        $client = static::createClient();
        $client->request('GET', '/orders/new');

        self::assertResponseRedirects('/login');
    }
}
```

## Service Tests with `KernelTestCase`

Use `KernelTestCase` to test services in isolation without making HTTP requests:

```php
// tests/Service/OrderServiceTest.php
namespace App\Tests\Service;

use App\Service\OrderService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class OrderServiceTest extends KernelTestCase
{
    private OrderService $orderService;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->orderService = static::getContainer()->get(OrderService::class);
    }

    public function testCreatePersistsOrder(): void
    {
        $data = new CreateOrderData();
        $data->quantity = 3;

        $order = $this->orderService->create($data, $this->createMock(User::class));

        self::assertNotNull($order->getId());
    }
}
```
