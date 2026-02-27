# Security

## Firewall Configuration

Use a single main firewall for the entire application. Multiple firewalls are needed only when mixing stateless APIs with stateful sessions in the same app:

```yaml
# config/packages/security.yaml
security:
    password_hashers:
        App\Entity\User:
            algorithm: auto

    providers:
        app_user_provider:
            entity:
                class: App\Entity\User
                property: email

    firewalls:
        dev:
            pattern: ^/(_(profiler|wdt)|css|images|js)/
            security: false

        main:
            lazy: true
            provider: app_user_provider
            form_login:
                login_path: app_login
                check_path: app_login
            logout:
                path: app_logout
            remember_me:
                secret: '%kernel.secret%'

    access_control:
        - { path: ^/admin, roles: ROLE_ADMIN }
        - { path: ^/api,   roles: IS_AUTHENTICATED_FULLY }
```

## `#[IsGranted]` Attribute

Use `#[IsGranted]` on controller methods instead of inline `$this->denyAccessUnlessGranted()` calls — it's declarative and enforced before the action runs:

```php
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/orders/{id}/cancel', name: 'order_cancel', methods: ['POST'])]
#[IsGranted('ROLE_USER')]
#[IsGranted('cancel', subject: 'order')]  // delegates to OrderVoter
public function cancel(Order $order, OrderService $service): Response
{
    $service->cancel($order);
    $this->addFlash('success', 'Order cancelled.');

    return $this->redirectToRoute('order_list');
}
```

## Voters vs `#[Security]` Expressions

Use a custom **Voter** (`VoterInterface`) for any non-trivial authorization logic (object ownership, complex role combinations, audit logging). Use the `#[Security]` expression attribute only for simple, stateless checks that fit in one line:

```php
// Simple expression — OK for #[Security]
#[Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_EDITOR')")]
public function edit(Article $article): Response { /* ... */ }
```

```php
// Complex logic (ownership check, status check) — use a Voter
// src/Security/Voter/OrderVoter.php
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class OrderVoter extends Voter
{
    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, ['view', 'cancel'], true)
            && $subject instanceof Order;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        return match ($attribute) {
            'view'   => $subject->getOwner() === $user,
            'cancel' => $subject->getOwner() === $user && $subject->isCancellable(),
            default  => false,
        };
    }
}
```
