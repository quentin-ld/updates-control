---
name: architect
description: >-
  Full dev cycle. Planning tier for clarify and plan; worker tier after
  approval for autonomous execution, tiered lint, thread rotation, and
  feedback fixes. Start every change here.
---

# Architect

Single skill, two model tiers:

- **Planning tier** (Phases 1–3): clarify → research → plan → task file
- **Worker tier** (Phases 4–5): execute → lint → feedback fixes

Reply in US English regardless of input language.

## Phase 1 — Clarify

If requirements are unclear, ask **one grouped message**. Skip if already clear.

## Phase 2 — Research

Read only what you need:

1. Relevant `inc/` and `assets/src/` files
2. `.agents/notes/` for the same slug
3. `workflow.md` for commands

**Reference docs** (never load whole files — grep one section; see `AGENTS.md`).

## Phase 3 — Plan (planning tier)

Create `.agents/tasks/YYYY-MM-DD-<type>-<slug>.md`.

```markdown
---
type: <type>
slug: <kebab-case>
date: YYYY-MM-DD
status: planning
review_required: yes | no
risk: []
planning_model_tier: planning
worker_model_tier: worker
---

## Goal
## Context
## Tasks
- [ ] 1. ...

## Session checkpoint
## Log
## Feedback
```

Set `review_required: yes` and `risk:` per `AGENTS.md` when REST, SQL, auth, export, multisite, user input, or new admin UI is involved.

Show a **~10-line summary**. End with: "Ready to start, or would you like to adjust anything?"

**Ambiguous SQL/auth/REST design:** Tell the user to re-run planning on **audit** tier before coding.

## Phase 4 — Execute (worker tier)

On approval, run all tasks in order.

### Lint tiers

| Task touches | Lint |
|--------------|------|
| REST, SQL, auth, sanitization, PHP logic, JS/React | **Immediate** after task: `composer run verify:php` and/or `npm run lint` + `npm run lint:css` |
| Comments, docs, pure CSS, no new surface | **Batch** every up to **5** tasks |
| All tasks done | `npm run test:all` |

### Thread rotation

After **3 tasks** or **~20 turns**, update `## Session checkpoint` and tell the user: new **worker** tier thread + `/resume` + this task file.

Stop only for uncovered design decisions, frozen docs, version bumps, or major scope creep.

On completion: `status: done`, full test gate, ask user to test.

## Phase 5 — Feedback (worker tier)

Fix reported issues; append to `## Feedback`.

When stable:

- `review_required: yes` → "Open `/reviewer` on **audit** tier (planning tier OK only for low-risk optional review)."
- Else → "Review optional; ship when satisfied."

## Implementation reflexes

ABSPATH guard · `updatronix_` hooks · REST `current_user_can()` · `$wpdb->prepare()` · sanitize in / escape out · docblocks on public surfaces · never reword existing i18n strings.