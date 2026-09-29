# module-boundaries-guardrails

## Goal
Enforce the layout described in `architecture.md`. Pure checklist, no prose.

## When to use
Before implementation and on every diff.

## Checklist
- [ ] Every new/moved file sits in the correct namespace (`Api` / `Domain` / `Application` / `Infrastructure` / `Presentation`).
- [ ] No cross-module `use` outside `Modules\<Other>\Api\...`.
- [ ] `Domain/` has **no** imports from `Infrastructure`, `Presentation`, or `Illuminate\*` (enums / plain interfaces / value objects only).
- [ ] `Application/` depends only on ports it declares itself, not on framework contracts.
- [ ] No controller talks to Eloquent directly — it calls an `Application` service or the module `Api`.
- [ ] Outbound side effects (mail, HTTP, DB, queue) go through ports implemented in `Infrastructure`.
- [ ] Business rules and state transitions live in `Domain`, not in controllers, listeners, or Eloquent models.
- [ ] Domain events dispatched from `Domain`/`Application`; framework wiring in a `ServiceProvider`.
- [ ] Listeners for another module's events live in the *consuming* module's `Infrastructure`/`Application` and consume only `Api\Events\*`.

## Output
Pass/fail per item; concrete file references for failures.

