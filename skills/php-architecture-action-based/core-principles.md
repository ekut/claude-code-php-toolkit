# Action-Based Core Principles

## One Class = One Action

Each class handles exactly one use case. The class name describes what it does: `CreateOrderAction`, `ListProductsAction`, `CancelSubscriptionAction`. No multi-method controllers.

## `__invoke()` as Entry Point

Every action class is invokable. This integrates cleanly with PHP framework routing (both Laravel and Symfony support invokable controllers) and keeps the API surface minimal.

## Flat or Use-Case Directory Structure

Actions are organized by domain concept, not by technical layer. All code for "create order" lives near each other, making navigation intuitive.

## No God Services

Instead of a massive `OrderService` with 20 methods, each use case is its own class. This eliminates the "where does this method go?" problem and makes code ownership clear.
