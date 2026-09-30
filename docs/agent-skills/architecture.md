# architecture

## Goal
The module boundary map. Read this before designing packages, interfaces, or data flow. Enforcement lives in `module-boundaries-guardrails.md`.

## Repo shape
```
src/Modules/<Module>/
  Api/              # public contract of the module (facade interfaces, DTOs, events)
  Domain/           # entities, value objects, enums, domain events, domain services
  Application/      # use-cases / services, orchestration, ports (repository interfaces)
  Infrastructure/   # Eloquent models, repositories, drivers, service providers
  Presentation/     # HTTP controllers, routes, request/response shaping
```

## Dependency direction
- **Inside a module**: `Presentation → Application → Domain`. `Infrastructure` implements ports declared in `Application`/`Domain`.
- **Across modules**: module A may import **only** `Modules\B\Api\...`.
- **`Api`** is the versioned, minimal public surface: facade interfaces, DTOs, events. Reference: the existing `Modules\Notifications\Api`.

## Design steps for a new use-case
1. Sketch inputs/outputs of the use-case at the `Application` boundary.
2. Define required **ports** (repository interface, clock, notification facade, etc.) in `Application`/`Domain`.
3. Put the invariants and state transitions in `Domain`.
4. Implement adapters in `Infrastructure` (Eloquent repo, HTTP client, etc.).
5. Add the HTTP shell in `Presentation` (thin controller + FormRequest or explicit validator).
6. If the module exposes anything to other modules, define it in `Api` first.

## When boundaries shift
- Update `docs/architecture/*` to reflect the new map.
- If it is a major decision, add or update an ADR (see `adr-architecture-guardrails.md`).

