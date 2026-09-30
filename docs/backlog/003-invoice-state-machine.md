# 003 — Invoice state machine

## Story
As the domain, I want explicit state transitions with typed exceptions, so that illegal moves are impossible regardless of caller.

## Addresses (README requirements)
- "An invoice can only be created in `draft` status."
- "An invoice can only be sent if it is in `draft` status."
- "An invoice can only be marked as `sent-to-client` if its current status is `sending`."
- "To be sent, an invoice must contain product lines with both quantity and unit price as positive integers greater than zero."

## Scope
- In:
  - `Invoice::send()` → guards status = draft, ≥1 line, all lines valid → transitions to `sending`.
  - `Invoice::markSentToClient()` → guards status = sending → transitions to `sent-to-client`.
  - Exceptions: `InvoiceCannotBeSent`, `InvoiceCannotBeMarkedSent`.
- Out:
  - Notification calls, persistence (later stories).

## Acceptance criteria
- [ ] Illegal transitions throw typed domain exceptions.
- [ ] Invoice constructed only via a factory method that forces `draft`.

## Tests
- Unit:
  - `it_transitions_draft_to_sending`
  - `it_rejects_send_when_not_draft`
  - `it_rejects_send_when_no_product_lines`
  - `it_rejects_send_when_a_line_is_invalid` (guarded by VOs, keep a redundancy check)
  - `it_marks_sent_to_client_only_from_sending`
  - `it_rejects_mark_sent_to_client_from_draft_or_sent`

## Guardrails to run
- php-and-laravel-guardrails

## Commit
`feat(invoices): invoice state machine`

## Status
done
