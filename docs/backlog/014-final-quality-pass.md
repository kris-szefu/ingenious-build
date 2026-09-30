# 014 — Final quality pass

## Story
As the author, I want a clean lint + green suite and a short set of known polish fixes,
so that the submission is ready for review without leftover dead code or unmapped domain errors.

## Addresses (README requirements)
- "Tests: Core invoice logic must be covered by tests."
- Evaluation criteria: architecture, testing strategy, send/deliver when things go wrong.

## Scope
- In:
  - Run Pint + full test suite.
  - Remove dead qty/price loop in `Invoice::ensureCanBeSent()` (invariants live in `Quantity` / `UnitPrice`).
  - Fix `CustomerName` to store the trimmed value it validates.
  - Align `CustomerName` / `CustomerEmail` failures with domain exceptions (not bare `InvalidArgumentException`) and map them in `bootstrap/app.php` like other domain errors.
  - Harden concurrent double-send: conditional persist or row lock so only one `draft → sending` wins (second request → 422, no second notify). Document the chosen approach in a short comment or ADR note.
  - Optional polish: create endpoint returns `201` + body without a second `GetInvoiceHandler` round-trip; add `Location: /api/invoices/{id}`. Keep `202` on send.
- Out:
  - OpenAPI (017/018), state-machine library spike (015).
  - Strict DDD purity refactor (019).
  - Queued notifications.

## Acceptance criteria
- [x] `vendor/bin/pint --test` clean.
- [x] `./vendor/bin/phpunit` (or `php artisan test`) green; baseline not regressing (73 tests, +8 net vs. pre-014 baseline of 65).
- [x] `ensureCanBeSent()` no longer contains the unreachable `quantity/unitPrice <= 0` loop.
- [x] `CustomerName` persists trimmed input.
- [x] Invalid customer name/email raise a domain exception (`InvalidCustomer`) mapped to HTTP `422` for API requests.
- [x] Concurrent/double send cannot notify twice for the same invoice — enforced by `InvoiceRepositoryInterface::updateLocked()` (pessimistic row lock in the Eloquent adapter); losing request's `ensureCanBeSent()` guard fires before any notification. Sequential double-send covered by `SendInvoiceEndpointTest::returns_422_when_invoice_is_not_in_draft` (asserts `FakeDriver::$sent` has exactly one entry after two send calls).
- [x] Create response built without re-fetch (`InvoiceView::fromDomain(Invoice)` in-memory); `Location: /api/invoices/{id}` header present (`CreateInvoiceEndpointTest::creates_invoice_with_lines`).
- [x] `SUBMISSION.md` baseline updated (65 → 73 tests).

## Tests
- Unit:
  - `CustomerNameTest` — trims whitespace, rejects empty/whitespace-only with `InvalidCustomer`.
  - `CustomerEmailTest` — trims whitespace, rejects invalid formats with `InvalidCustomer`.
  - `Invoice::ensureCanBeSent` still rejects empty lines / non-draft (no dead path tests for qty ≤ 0).
- Feature / unit:
  - Sequential double-send: second call returns `422` and does not invoke the notification driver (existing `SendInvoiceEndpointTest::returns_422_when_invoice_is_not_in_draft`).
  - Create endpoint `Location` header assertion.

## Guardrails to run
- module-boundaries-guardrails
- php-and-laravel-guardrails
- laravel-conventions

## Commit
`chore(quality): pint, domain polish, and send race guard`

## Status
done
