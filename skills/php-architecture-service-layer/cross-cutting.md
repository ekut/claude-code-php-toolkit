# Service-Oriented Cross-Cutting Concerns

- **Logging** — services log key operations (order created, payment failed) using PSR-3. Controllers log request-level info. Repositories do not log
- **Authentication/Authorization** — handled in middleware or controller guards before reaching the service layer. Services may accept a user/context object for permission checks
- **Validation** — input validation in the controller layer (form requests, validators). Business validation in services (stock check, status transitions). Entities validate data types via PHP type system
- **Error handling** — services throw domain-specific exceptions (`InsufficientStockException`). Controllers catch and translate to HTTP responses. Use a global exception handler for unhandled cases
