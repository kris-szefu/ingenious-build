# testing-strategy

## Goal
Clear, fast, layered tests that exercise the important behavior. Includes the TDD loop.

## When to use
Every behavior change (new feature or bug fix).

## TDD loop
1. Write a failing test at the right layer (see Layout below).
2. Implement the smallest change to pass.
3. Refactor with tests green.
4. Confirm placement follows `architecture.md` and `module-boundaries-guardrails.md`.

## Layout
- **`tests/Unit/<Module>/Domain/...`** — pure PHP, no framework, no DB. Cover:
  - Entity invariants (positive quantity/price, product line totals).
  - State machine (`draft → sending → sent-to-client`, illegal transitions throw).
- **`tests/Unit/<Module>/Application/...`** — use-case tests with in-memory fakes for repositories and Mockery mocks for the notification facade. Cover:
  - Create draft invoice (with and without lines).
  - Send: happy path, illegal status, missing/invalid lines, facade throws → no state change.
  - Delivery handling: transition from `sending` on event; ignore/log for other statuses.
- **`tests/Feature/<Module>/Http/...`** — full HTTP + DB via `RefreshDatabase`. Cover:
  - `View`, `Create`, `Send` endpoints — status codes, payload shape, validation errors.
  - End-to-end send flow with a fake notification driver, asserting status transitions and events.

## Rules
- Prefer in-memory repository fakes over mocking repository interfaces.
- Use `Event::fake()` and `Mail::fake()` where relevant to assert dispatch.
- One behavior per test. Descriptive names: `it_rejects_sending_when_invoice_is_not_draft`.
- No shared mutable state between tests. Use factories/seed data explicitly.
- `php artisan test` must be green before finishing.

## Output
A layered PHPUnit suite: domain invariants, use-case orchestration, HTTP contract.

