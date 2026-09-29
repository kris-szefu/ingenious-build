# 007 — Eloquent persistence adapter

## Story
As the module, I want a real database-backed repository, so that invoices persist across requests.

## Addresses (README requirements)
- Enabler for all three endpoints.
- "Invoice ID: Auto-generated during creation."

## Scope
- In:
  - Inspect existing migrations (`create_invoices_table`, `create_invoice_product_lines_table`); align or add a follow-up migration if fields (e.g. `status`, `customer_email`) are missing.
  - `Infrastructure/Eloquent/InvoiceModel`, `InvoiceProductLineModel` with `$fillable` and `casts()` for `StatusEnum`.
  - `Infrastructure/Repositories/EloquentInvoiceRepository` mapping model ↔ domain entity, writes in `DB::transaction()`.
  - `Infrastructure/Ids/UuidGenerator` implementing `IdGeneratorInterface`.
  - Bind ports in `InvoiceServiceProvider::register()`.
- Out:
  - Controllers (stories 008/009/011).

## Acceptance criteria
- [ ] Round-trip save + load reconstructs identical domain state.
- [ ] Repository never returns null; throws `InvoiceNotFound`.
- [ ] Eloquent models stay inside `Infrastructure/` and are not referenced by other layers.

## Tests
- Feature `tests/Feature/Invoices/Infrastructure/`:
  - Save + get with `RefreshDatabase`.
  - Not-found throws domain exception.

## Guardrails to run
- laravel-conventions
- module-boundaries-guardrails

## Commit
`feat(invoices): eloquent persistence adapter`

## Status
todo

