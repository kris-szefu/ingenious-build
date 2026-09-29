# 014 — Final quality pass

## Story
As the author, I want a clean lint + green test run, so that the submission is ready for review.

## Addresses (README requirements)
- "Tests: Core invoice logic must be covered by tests."
- Evaluation criteria overall.

## Scope
- In:
  - Run `vendor/bin/pint`.
  - Run `php artisan test`.
  - Re-run guardrail checklists across the diff.
- Out:
  - New features.

## Acceptance criteria
- [ ] `vendor/bin/pint --test` clean.
- [ ] `php artisan test` green.
- [ ] Guardrail checklists pass.

## Commit
`chore(quality): pint and final test pass`

## Status
todo

