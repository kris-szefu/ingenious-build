# 012 — React to webhook delivered event → `sent-to-client`

## Story
As the invoice module, I want to react to the notification module's delivery webhook, so that an invoice automatically moves from `sending` to `sent-to-client`.

## Addresses (README requirements)
- "Upon successful delivery by the Dummy notification provider: The Notification Module triggers a `ResourceDeliveredEvent` via webhook."
- "The Invoice Module listens for and captures this event."
- "The Invoice Status is updated from `sending` to `sent-to-client`."
- "This transition requires that the invoice is currently in the `sending` status."

## Notes
- README says `ResourceDeliveredEvent`; the actual class in `Modules\Notifications\Api\Events\` is `WebhookDeliveredEvent`. Use the real class and mention the mismatch in the PR description.

## Scope
- In:
  - `Application/UseCases/MarkInvoiceDelivered` (loads → `markSentToClient()` → save).
  - `Infrastructure/Listeners/MarkInvoiceSentToClientListener` consuming `Modules\Notifications\Api\Events\WebhookDeliveredEvent`.
  - Correlate event → invoice id via `NotifyData` reference id.
  - Ignore + log for unknown invoice or non-`sending` state (no throw).
  - Register listener in `InvoiceServiceProvider::boot()`.
- Out:
  - Any changes to Notifications module beyond consuming its `Api`.

## Acceptance criteria
- [ ] Event for `sending` invoice → status becomes `sent-to-client`, persisted.
- [ ] Event for unknown invoice → logged, no throw, no DB write.
- [ ] Event for invoice in `draft` or `sent-to-client` → logged, no throw, no state change.
- [ ] Only `Modules\Notifications\Api\*` imported.

## Tests
- Unit (use-case):
  - Ignores unknown invoice.
  - Ignores wrong status.
  - Happy path transitions.
- Feature (end-to-end):
  - `POST /invoices/{id}/send` → dummy driver dispatches event → listener runs → status = `sent-to-client`.
  - Uses `Event::fake()` variant to assert listener wiring where useful.

## Guardrails to run
- module-boundaries-guardrails
- php-and-laravel-guardrails
- laravel-conventions

## Commit
`feat(invoices): react to webhook delivered event`

## Status
todo

