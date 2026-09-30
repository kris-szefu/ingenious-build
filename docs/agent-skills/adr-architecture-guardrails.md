# adr-architecture-guardrails

## Goal
Make decisions explicit. Enforce ADR creation/updates for major choices and keep `docs/architecture/` in sync.

## When to use
Before implementation, during review, and before merge — whenever multiple valid options exist or boundaries shift.

## Producing the decision (decision mapping)
When multiple options are viable:
1. State the decision in one sentence.
2. List candidate options (2–4).
3. Score trade-offs briefly against constraints (from README, `architecture.md`).
4. Pick one and list consequences (what becomes easier / harder).
5. If the decision is architectural, promote it to an ADR under `docs/adr/NNNN-*.md`.

## Guardrail workflow
- Classify the change: is there a major architectural decision?
  Examples: persistence choice, sync vs. queued sending, event contract shape, module split, new outbound port.
- If **yes**: create a new ADR or update an existing one with rationale.
- Check whether architecture artifacts changed (boundaries, flows, interfaces, package structure, dependency direction).
- If architecture changed: update the impacted files under `docs/architecture/`.
- Record a short `Decision & Docs Impact` note in the change summary.

## Acceptance criteria
- Major architectural decisions always have ADR action (`new` or `updated`) with justification.
- Architecture-impacting changes always update impacted `docs/architecture/*.md`.
- `ADR not needed` / `Architecture docs not needed` are allowed only with explicit justification.
- Missing `Decision & Docs Impact` note fails the guardrail.

## Output
Pass/fail report and required ADR / architecture-doc actions before merge.

