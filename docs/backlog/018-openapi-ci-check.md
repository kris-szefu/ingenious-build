# 018 — OpenAPI CI: lint + regenerate-diff guard

## Story
As a maintainer, I want CI to fail on a malformed `openapi.yaml` **and** on any
drift between the committed generated DTOs and the current spec, so that the
contract and the code cannot diverge silently.

## Addresses (README requirements)
- None directly. Enforces the decision in [ADR 0001](../adr/0001-openapi-strategy.md).

## Depends on
- 017 (spec + Jane config + generated dir must exist).

## Scope
- In:
  - `composer` script `openapi:lint` running
    `npx --yes @stoplight/spectral-cli lint docs/api/openapi.yaml`.
  - `composer` script `openapi:verify` that runs `openapi:generate` and then
    `git diff --exit-code src/Modules/Invoices/Presentation/Http/Generated docs/api`
    — non-zero exit if regeneration produced any change.
  - `docs/api/.spectral.yaml` extending `spectral:oas` with project rules:
    every operation has `operationId`; every response has an `example`; every
    4xx/5xx response uses the `Problem` schema.
  - Document `composer openapi:lint` and `composer openapi:verify` in
    `README.md` under "API contract".
- Out:
  - Hosting the spec (GitHub Pages, S3).
  - Contract-testing frameworks beyond the PHPUnit tests added in 017.

## Acceptance criteria
- [ ] `composer openapi:lint` exits non-zero on an intentionally broken spec.
- [ ] `composer openapi:verify` exits non-zero if the committed
      `Generated/` output does not match a fresh regeneration.
- [ ] `docs/api/.spectral.yaml` committed.
- [ ] `README.md` documents both commands.

## Tests
- Manual: introduce a broken spec change → `openapi:lint` fails → revert.
- Manual: edit `openapi.yaml` without regenerating → `openapi:verify` fails → revert.

## Guardrails to run
- adr-architecture-guardrails

## Commit
`chore(invoices): lint openapi and enforce regeneration in ci`

## Status
todo

