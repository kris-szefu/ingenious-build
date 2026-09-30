# 011 — POST /invoices/{id}/send endpoint

## Story
As an API client, I want to trigger sending over HTTP, so that the send workflow is exposed.

## Addresses (README requirements)
- "Required Endpoints: 3. Send Invoice: Handle the sending of an invoice."

## Scope
- In:
  - `SendInvoiceController` (invokable) + optional `SendInvoiceRequest`.
  - `202` on success; `404` missing; `422` on illegal state / invalid lines (mapped from domain exceptions).
- Out:
  - Delivery handling (story 012).

## Acceptance criteria
- [x] Happy path returns `202` and persists `sending`.
- [x] Non-draft → `422` and no state change.
- [x] Missing invoice → `404`.
- [x] Uses fake notification driver in tests.

## Tests
- Feature:
  - End-to-end send with faked driver — asserts status = `sending` and facade dispatched.
  - Illegal state → `422`.
  - Missing id → `404`.

## Guardrails to run
- laravel-conventions
- module-boundaries-guardrails

## Commit
`feat(invoices): send endpoint`

## Status
done

