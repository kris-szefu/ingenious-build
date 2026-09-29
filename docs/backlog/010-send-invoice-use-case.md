# 010 — Send-invoice use-case (with NotificationFacade)

## Story
As the invoice module, I want a use-case that emails the customer and moves the invoice to `sending`, so that the send workflow is captured in one testable place.

## Addresses (README requirements)
- "Send an email notification to the customer using the `NotificationFacade`."
- "Change the Invoice Status to `sending` after sending the notification."
- "An invoice can only be sent if it is in `draft` status."
- "To be sent, an invoice must contain product lines with both quantity and unit price as positive integers greater than zero."

## Scope
- In:
  - `Application/UseCases/SendInvoice` injects `Modules\Notifications\Api\NotificationFacadeInterface`.
  - Order of operations: load → domain guards → build `NotifyData` (invoice id as reference id) → call facade → transition to `sending` → save.
  - On facade throw → invoice stays `draft`, exception bubbles.
- Out:
  - HTTP shell (story 011); delivery listener (story 012).

## Acceptance criteria
- [ ] Facade invoked exactly once on happy path.
- [ ] Status persisted as `sending` only after facade returns.
- [ ] Facade throw → no persisted state change.
- [ ] Invalid status / invalid lines → typed domain exceptions, facade not called.

## Tests
- Unit (Mockery facade):
  - Happy path transitions and calls facade.
  - Not-draft → `InvoiceCannotBeSent`, facade not called.
  - Missing/invalid lines → `InvoiceCannotBeSent`, facade not called.
  - Facade throws → invoice remains `draft` in repo.

## Guardrails to run
- php-and-laravel-guardrails
- module-boundaries-guardrails (only `Modules\Notifications\Api\*` imported)

## Commit
`feat(invoices): send use-case with notification facade`

## Status
todo

