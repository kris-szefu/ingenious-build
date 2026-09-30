# php-and-laravel-guardrails

## Goal
Enforce modern PHP style and prevent AI assistants from leaking Laravel "magic" into `Domain/` and `Application/`. Also prevent over-engineering.

## When to use
Every PHP change under `src/`, especially `Domain/` and `Application/`.

## Rule 1 — Typing is not optional
- `declare(strict_types=1);` at the top of every PHP file in `src/` and `app/`.
- Every parameter, property, and return has a declared type. No implicit `mixed`.
- Prefer union / intersection / `never` / backed enums over `mixed` or docblock-only types.
- Casting of request input happens in `Presentation/`, not "trusted" downstream.
- Prefer `final` classes and `readonly` properties with constructor property promotion.
- Use `enum` with backed values for closed sets (see `StatusEnum`).

## Rule 2 — No Laravel magic in Domain / Application
Banned in `Domain/` and `Application/` (allowed only in `Infrastructure/` or `Presentation/`):

- **Facades**: `Auth::`, `DB::`, `Log::`, `Event::`, `Mail::`, `Cache::`, `Config::`, `Route::`, etc.
- **Global helpers**: `now()`, `today()`, `config()`, `auth()`, `request()`, `session()`, `app()`, `resolve()`, `env()`, `cache()`, `logger()`, `dispatch()`, `event()`.
- **FormRequest**: validation lives in `Presentation/` (controllers / request classes), never in domain.
- **Eloquent model events / observers / global scopes** as a way to encode business rules. Business rules go into `Domain/` methods; persistence side-effects go into repository implementations in `Infrastructure/`.
- **`Illuminate\*` imports in `Domain/`** — enums, plain interfaces, and value objects only.
- **Static state / singletons / service location** inside domain or application services (no `app()->make(...)`).

Instead:
- Inject collaborators via the **constructor** (repositories, clocks, id generators, module-owned notifier ports, etc.).
- For "now": inject a `ClockInterface` (or pass `DateTimeImmutable`), don't call `now()`.
- For config values: inject typed config objects / primitives resolved in the ServiceProvider.
- For current user: pass user id / value object from the controller into the application service.
- For customer notifications: inject an Invoices-owned port (e.g. `CustomerNotifierInterface`); map to `Modules\Notifications\Api\*` only in an Infrastructure ACL adapter.
- For dispatching domain events: use a small `DomainEventDispatcherInterface` port; wire Laravel's dispatcher in the ServiceProvider.

## Rule 3 — Keep it simple
- Plain `final` class with a constructor > Factory/Builder/Strategy hierarchy.
- No interfaces with a single implementation unless they cross a module or layer boundary (repository, clock, customer notifier, domain-event dispatcher — yes; internal helper — no).
- No abstract base classes "just in case". Compose, don't inherit.
- No premature CQRS, mediators, or command buses. A method on an application service is enough.
- If a value object has no invariants, use a `readonly` DTO.

## Rule 4 — Naming & tooling
- PascalCase classes, camelCase methods, `snake_case` DB columns, `kebab-case` routes.
- One typed domain exception per invariant (`InvoiceCannotBeSent`, `InvalidProductLine`, `InvalidCustomer`, …).
- No `null` returns for "not found" domain reads — throw a typed domain exception.
- Run `vendor/bin/pint` before committing.

## Reviewer checklist (Domain/Application diffs)
- [ ] No `use Illuminate\Support\Facades\...`.
- [ ] No global helper calls (`now`, `config`, `auth`, `request`, `app`, `resolve`, `env`, `event`, `dispatch`, `logger`).
- [ ] No `extends Model`, no model event hooks, no `FormRequest`.
- [ ] Every file starts with `declare(strict_types=1);`.
- [ ] Dependencies are constructor-injected.
- [ ] No new interface / abstract class without ≥2 real implementations **or** a boundary reason.
- [ ] `vendor/bin/pint --test` clean.

## Related
- `laravel-conventions.md` — where the "magic" *is* allowed (Infrastructure, Presentation, ServiceProvider wiring).
- `module-boundaries-guardrails.md` — layer/module dependency direction.

