# 009 — GET /invoices/{id} endpoint

## Story
As an API client, I want to fetch an invoice by id, so that I can display it.

## Addresses (README requirements)
- "Required Endpoints: 1. View Invoice: Retrieve invoice data in the format above."

## Scope
- In:
  - `ViewInvoiceController` (invokable) → calls `GetInvoice`.
  - Maps `InvoiceNotFound` to `404` via an exception handler / renderable exception.
- Out:
  - Anything else.

## Acceptance criteria
- [ ] `200` returns the documented payload shape (matches README fields).
- [ ] `404` when unknown id.

## Tests
- Feature: happy path + 404.

## Guardrails to run
- laravel-conventions

## Commit
`feat(invoices): view endpoint`

## Status
todo

