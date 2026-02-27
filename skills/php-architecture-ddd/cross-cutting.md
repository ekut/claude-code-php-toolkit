# DDD Cross-Cutting Concerns

- **Logging** — infrastructure adapters log; domain layer raises events instead of logging directly
- **Authentication/Authorization** — handled in UserInterface or Application layer via middleware/guards, never in Domain
- **Validation** — domain objects self-validate in constructors and methods (Value Objects reject invalid state). Application-level validation (format, permissions) happens in Command/Query handlers or middleware
- **Error handling** — domain throws domain-specific exceptions (`OrderAlreadySubmittedException`). Application layer catches and translates them. Infrastructure layer handles technical errors (connection lost, timeout)
