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
- [ ] `vendor/bin/pint --test` clean.
- [ ] `./vendor/bin/phpunit` (or `php artisan test`) green; baseline not regressing.
- [ ] `ensureCanBeSent()` no longer contains the unreachable `quantity/unitPrice <= 0` loop.
- [ ] `CustomerName` persists trimmed input.
- [ ] Invalid customer name/email raise a domain exception mapped to HTTP `422` for API requests.
- [ ] Concurrent/double send cannot notify twice for the same invoice (test covers the losing request).
- [ ] (Optional) Create response built without re-fetch; `Location` header present.
- [ ] `SUBMISSION.md` / architecture notes updated only if behaviour or HTTP contract changed.

## Tests
- Unit:
  - `CustomerName` / `CustomerEmail` domain exception cases.
  - `Invoice::ensureCanBeSent` still rejects empty lines / non-draft (no dead path tests for qty ≤ 0).
- Feature / unit:
  - Concurrent or sequential double-send: second call does not call notification facade; invoice stays `sending`.

## Guardrails to run
- module-boundaries-guardrails
- php-and-laravel-guardrails
- laravel-conventions

## Commit
`chore(quality): pint, domain polish, and send race guard`

## Status
todo
