# 001 — Scaffold Invoices module

## Story
As a developer, I want the Invoices module folder skeleton and service provider wired into Laravel, so that later stories can drop code into the correct layers without boilerplate churn.

## Addresses (README requirements)
- "Preferred Approach: Domain-Driven Design (DDD) is preferred for this project."
- Implicitly enables: "Required Endpoints: View / Create / Send Invoice."

## Scope
- In:
  - Create `src/Modules/Invoices/{Api,Domain,Application,Infrastructure,Presentation}` folders.
  - Add `Infrastructure/Providers/InvoiceServiceProvider` (empty `register`/`boot`).
  - Load `Presentation/routes.php` under `api` prefix from the provider.
  - Register provider in `bootstrap/providers.php`.
- Out:
  - Any domain code, endpoints, or persistence.

## Acceptance criteria
- [x] `php artisan route:list` runs without errors.
- [x] Provider registered; app boots.
- [x] Folder layout matches `docs/agent-skills/architecture.md`.

## Tests
- Unit: none.
- Feature: smoke — app boots (existing tests remain green).

## Guardrails to run
- module-boundaries-guardrails
- laravel-conventions

## Commit
`chore(invoices): scaffold module skeleton`

## Status
done
