# Action-Based Cross-Cutting Concerns

- **Logging** — handlers log significant business events. Actions log HTTP-level concerns (if needed). Use middleware for request/response logging
- **Authentication/Authorization** — middleware handles auth before the action is called. Actions or handlers may check specific permissions via a policy/voter
- **Validation** — input validation in form requests or dedicated input classes. Business validation in handlers. This two-layer approach keeps actions thin
- **Error handling** — handlers throw domain exceptions. Actions or global exception handlers translate to HTTP responses. Responders (in ADR) can handle error formatting
