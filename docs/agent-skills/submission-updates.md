# submission-updates

## Goal
Keep [`/SUBMISSION.md`](../../SUBMISSION.md) — the reviewer-facing summary at the repo root — in sync with what the codebase actually ships. The root `README.md` is the recruiter's task description and is **off-limits** for edits; `SUBMISSION.md` is the only place the candidate's story lives at the top level.

## When to use
Trigger a `SUBMISSION.md` review after **any** of these:

- A backlog task flips to `done` (add / adjust the delivered-scope bullet, remove from "deferred").
- A backlog task moves to `blocked` or `skipped` (move into "deferred" with the reason).
- A new ADR lands, or an existing ADR changes status (update the design-highlights + reviewer-notes sections and any deep links).
- Cross-module boundaries change (Invoices ↔ Notifications), or a new module appears.
- HTTP surface changes (route added / removed / status code changed).
- Run or test procedure changes (`start.sh`, docker-compose ports, composer scripts, php version, test command).
- A reviewer-visible naming caveat or fixture note is discovered.
- Test count / baseline noticeably shifts (dozens of tests added or removed).

If none of the above apply, do **not** touch `SUBMISSION.md`.

## Workflow
1. Skim the diff about to be committed and answer: does it change anything a reviewer would spot in `SUBMISSION.md`? If no, stop.
2. Open `SUBMISSION.md` and walk its sections top-to-bottom:
   - **How to run** — still accurate? Ports, env vars, scripts?
   - **Endpoints** — table still complete and correct?
   - **How to test** — command still runs? Baseline test count still matches (round to nearest ten if you don't want to churn on every commit)?
   - **What is delivered** — add / remove / re-order bullets to mirror `docs/BACKLOG.md`. Match commit subjects verbatim.
   - **Deliberately deferred** — moved-to-done items removed; newly-deferred items added with a one-line rationale.
   - **Design highlights** — new invariant / new port / new policy worth flagging?
   - **Reviewer notes** — new caveats, mismatches, fixture behaviour?
   - **Where to look** — new file worth pointing at?
3. Keep the file **skimmable**. Prefer bullets and short cross-links to ADRs / architecture docs. Do not restate reasoning that already lives in an ADR — link instead.
4. Stage `SUBMISSION.md` in the **same commit** as the triggering change. Do not commit a docs-only "sync SUBMISSION" catch-up unless nothing else is changing.

## Rules
- **Do not edit `/README.md`.** That file is the recruiter's task description, verbatim.
- `SUBMISSION.md` never duplicates an ADR — it links.
- `SUBMISSION.md` never duplicates `docs/BACKLOG.md` beyond the short "delivered" list; the dashboard is the source of truth.
- Every deep link uses a relative path (`./docs/...`, `./src/...`) so the file works from a fresh clone and on GitHub.
- If a section becomes stale, prefer **delete + rewrite short** over patch-in-place. `SUBMISSION.md` is a landing page, not a changelog.
- If a section drifts more than once in a short window, the underlying content probably belongs in an ADR or architecture doc, not in `SUBMISSION.md`. Move it and link.

## Output
A commit whose diff includes the triggering change **and** the `SUBMISSION.md` sync. Reviewers opening `SUBMISSION.md` at any commit on the submission branch see a truthful summary of that commit.

