# Controllers

Controllers handle HTTP: parse request, call services, return response. Keep them thin — no business logic, no direct repository calls.

## Standard Controller

```php
// src/Controller/OrderController.php
namespace App\Controller;

use App\Service\OrderService;
use App\Form\CreateOrderType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/orders', name: 'order_')]
final class OrderController extends AbstractController
{
    public function __construct(
        private readonly OrderService $orderService,
    ) {}

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(): Response
    {
        return $this->render('order/list.html.twig', [
            'orders' => $this->orderService->findForCurrentUser($this->getUser()),
        ]);
    }

    #[Route('/new', name: 'create', methods: ['GET', 'POST'])]
    public function create(Request $request): Response
    {
        $form = $this->createForm(CreateOrderType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $order = $this->orderService->create($form->getData(), $this->getUser());
            $this->addFlash('success', 'Order placed.');

            return $this->redirectToRoute('order_list');
        }

        return $this->render('order/create.html.twig', ['form' => $form]);
    }
}
```

## JSON API Controller

Use action-level injection for dependencies only needed in specific actions:

```php
#[Route('/api/products', name: 'api_product_')]
final class ProductApiController extends AbstractController
{
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(ProductRepository $repository): JsonResponse
    {
        $products = $repository->findActive();

        return $this->json(array_map(
            fn ($p) => ['id' => $p->getId(), 'name' => $p->getName(), 'price' => $p->getPrice()],
            $products,
        ));
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Product $product): JsonResponse  // EntityValueResolver auto-fetches by {id}
    {
        return $this->json(['id' => $product->getId(), 'name' => $product->getName()]);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(
        #[MapRequestPayload] CreateProductData $data,
        ProductService $service,
    ): JsonResponse {
        $product = $service->create($data);

        return $this->json(['id' => $product->getId()], Response::HTTP_CREATED);
    }
}
```

## `#[MapEntity]` and `#[MapRequestPayload]`

`#[MapEntity]` fetches an entity by any field (not only `{id}`). `#[MapRequestPayload]` (Symfony 6.3+) validates and deserializes the request body into a typed DTO — no manual `$request->toArray()` needed:

```php
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;

// Fetch Product by slug instead of id
#[Route('/api/products/{slug}', name: 'api_product_show_by_slug', methods: ['GET'])]
public function showBySlug(
    #[MapEntity(mapping: ['slug' => 'slug'])] Product $product,
): JsonResponse {
    return $this->json(['id' => $product->getId(), 'name' => $product->getName()]);
}

// Deserialize + validate JSON body into a DTO; returns 422 automatically on constraint violations
#[Route('/api/orders', name: 'api_order_create', methods: ['POST'])]
public function createOrder(
    #[MapRequestPayload] CreateOrderData $data,
    OrderService $service,
): JsonResponse {
    $order = $service->create($data, $this->getUser());

    return $this->json(['id' => $order->getId()], Response::HTTP_CREATED);
}
```

`MapRequestPayload` runs the Symfony Validator against the DTO's constraints automatically.
