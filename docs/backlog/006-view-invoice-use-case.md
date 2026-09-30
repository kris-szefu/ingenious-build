# 006 — View-invoice use-case + read DTO

## Story
As an API client, I want to fetch an invoice with all fields the README lists, so that I can display it.

## Addresses (README requirements)
- "View Invoice: Retrieve invoice data in the format above."
- Fields: id, status, customer name/email, product lines with `Total Unit Price`, `Total Price`.

## Scope
- In:
  - `Application/UseCases/GetInvoice` returns an `InvoiceView` read DTO.
  - Throws `InvoiceNotFound` if missing.
- Out:
  - HTTP shell (story 009).

## Acceptance criteria
- [x] DTO shape includes `id`, `status`, `customer_name`, `customer_email`, `product_lines[]` (with `product_name`, `quantity`, `unit_price`, `total_unit_price`), and `total_price`.
- [x] Missing invoice → `InvoiceNotFound`.

## Tests
- Unit:
  - Returns DTO with expected fields and totals.
  - Throws when unknown id.

## Guardrails to run
- php-and-laravel-guardrails

## Commit
`feat(invoices): view-invoice use-case`

## Status
done
