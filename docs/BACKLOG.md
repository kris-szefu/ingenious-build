# BACKLOG

Single source of truth for task status. Update this file **in the same commit** that changes a task's status.

Legend: `todo` · `done` · `blocked` · `skipped`

## Active sprint — Invoices module

| # | Task | File | Commit | Status | Notes |
|---|------|------|--------|--------|-------|
| 001 | Scaffold Invoices module | [001](./backlog/001-scaffold-invoices-module.md) | `chore(invoices): scaffold module skeleton` | done | |
| 002 | Invoice domain model | [002](./backlog/002-invoice-domain-model.md) | `feat(invoices): model invoice domain` | done | |
| 003 | Invoice state machine | [003](./backlog/003-invoice-state-machine.md) | `feat(invoices): invoice state machine` | done | |
| 004 | Application ports | [004](./backlog/004-application-ports.md) | `feat(invoices): application ports` | done | |
| 005 | Create-invoice use-case | [005](./backlog/005-create-invoice-use-case.md) | `feat(invoices): create-invoice use-case` | done | |
| 006 | View-invoice use-case | [006](./backlog/006-view-invoice-use-case.md) | `feat(invoices): view-invoice use-case` | done | |
| 007 | Eloquent persistence adapter | [007](./backlog/007-eloquent-persistence.md) | `feat(invoices): eloquent persistence adapter` | done | |
| 008 | POST /invoices | [008](./backlog/008-http-create-endpoint.md) | `feat(invoices): create endpoint` | todo | |
| 009 | GET /invoices/{id} | [009](./backlog/009-http-view-endpoint.md) | `feat(invoices): view endpoint` | todo | |
| 010 | Send-invoice use-case | [010](./backlog/010-send-invoice-use-case.md) | `feat(invoices): send use-case with notification facade` | todo | |
| 011 | POST /invoices/{id}/send | [011](./backlog/011-http-send-endpoint.md) | `feat(invoices): send endpoint` | todo | |
| 012 | Webhook delivered listener | [012](./backlog/012-delivery-event-listener.md) | `feat(invoices): react to webhook delivered event` | todo | |
| 013 | ADR + architecture docs | [013](./backlog/013-adr-and-architecture-docs.md) | `docs(invoices): add ADR and update architecture docs` | todo | |
| 014 | Final quality pass | [014](./backlog/014-final-quality-pass.md) | `chore(quality): pint and final test pass` | todo | |
| 015 | Spike: external state-machine library | [015](./backlog/015-spike-state-machine-library.md) | `docs(invoices): spike external state-machine options (ADR)` | todo | Deferred follow-up to 003 |
| 016 | Spike: OpenAPI strategy | [016](./backlog/016-spike-openapi-strategy.md) | `docs(invoices): spike openapi strategy (ADR)` | todo | Decide contract-first vs annotation vs skip |

## Progress
- Total: 16
- Done: 7
- Blocked: 0
