# 002 — Invoice domain model (entities + value objects)

## Story
As a domain modeler, I want invoice entities and value objects with enforced invariants, so that invalid data can never enter the domain.

## Addresses (README requirements)
- "Invoice ID: Auto-generated during creation."
- "Customer Name / Customer Email."
- "Invoice Product Lines, each with: Product Name, Quantity (integer, positive), Unit Price (integer, positive), Total Unit Price = Quantity × Unit Price."
- "Total Price: Sum of all Total Unit Prices."

## Scope
- In:
  - VOs: `InvoiceId`, `CustomerName`, `CustomerEmail`, `ProductName`, `Quantity`, `UnitPrice`.
  - Entity: `ProductLine` with `totalUnitPrice()`.
  - Entity: `Invoice` with `totalPrice()`, holds `StatusEnum`.
  - Domain exceptions: `InvalidProductLine`, `InvoiceNotFound`.
- Out:
  - State transitions (story 003).
  - Persistence (story 007).

## Acceptance criteria
- [x] `Quantity`/`UnitPrice` throw `InvalidProductLine` when ≤ 0.
- [x] `Invoice::totalPrice()` = Σ line totals.
- [x] All classes `final`, `readonly` where possible, `declare(strict_types=1)`.
- [x] No `Illuminate\*` imports in `Domain/`.

## Tests
- Unit `tests/Unit/Invoices/Domain/`:
  - Quantity/UnitPrice reject 0 and negatives.
  - ProductLine total = qty × price.
  - Invoice totalPrice sums lines; empty lines allowed → 0.

## Guardrails to run
- php-and-laravel-guardrails
- module-boundaries-guardrails

## Commit
`feat(invoices): model invoice domain`

## Status
done
