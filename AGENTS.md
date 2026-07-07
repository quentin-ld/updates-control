# Updatronix — Agent Instructions

## Communication

Messages may arrive in French or English. **Always reply in US English.** WordPress prose style: `.agents/docs/wordpress-documentation-style-guide-consolidated.md` (lookup sections only — never load the full file).

## Project Facts

| | |
|---|---|
| Slug & text domain | `updatronix` |
| Bootstrap | `updatronix.php` → `Updatronix_Bootstrap::init` |
| Version | `UPDATRONIX_VERSION` in `updatronix.php` |
| PHP / WP | 8.1+ (target 8.1–8.4) · 6.2+ |
| REST | `updatronix/v1` |
| Public hook | `do_action( 'updatronix_after_log', $log_id, $data )` |
| Code | `inc/classes/` · `inc/admin/` · `inc/settings/` · `assets/src/` → `assets/build/` · `languages/` |
| Storage | `{prefix}updatronix_logs` · `updatronix_settings` (JSON, autoloaded) · `updatronix_update_logger_state` (no autoload) · native `auto_update_*` options |
| Build & test | `workflow.md` |

## Workflow — Delegate, Don’t Micromanage

You describe the outcome. The agent plans, implements, lints, logs, and fixes from your test feedback. **You approve the plan once, then test.**

| Step | You | Agent |
|------|-----|-------|
| 1 | `/architect` + describe change | Clarify → plan → create task file |
| 2 | “go” / adjust plan | Execute all tasks autonomously |
| 3 | Test in Local | Fix issues from your notes |
| 4 | Done testing | `/reviewer` if required (see below) |
| 5 | Release ready | `/release` after explicit version authorization |

## Model Tiers

Skills define **what** to do. **Model tier** defines **which capability level** to use per thread. Tier names are abstract — map them to whatever models you run locally.

| Tier | Use when | Typical skills / phase |
|------|----------|-------------------------|
| **Planning** | Answer not yet in the task file — clarify, design, trade-offs, ambiguous scope | `/architect` Phase 1–3 (plan) · low-risk `/reviewer` |
| **Worker** | Task file is the contract — implement, lint, fix, rotate threads | `/architect` Phase 4–5 (execute) · `/resume` · `/release` |
| **Audit** | Judge only — no implementation; security and integration gates | `/reviewer` when required · `/security` always |

### Recommended models

| Tier | Recommended | Why |
|------|-------------|-----|
| **Planning** | DeepSeek Pro V4 · Claude Opus | Strong reasoning, design, trade-off analysis |
| **Worker** | DeepSeek Pro Flash | Fast, cheap, reliable tool calling — bulk of the work |
| **Audit** | Claude Opus · DeepSeek Pro V4 | Thorough review, security analysis, no hallucinations on gates |

**Rules**

- **Planning** before the plan is approved; switch to **worker** after you say `go`.
- **Worker** for all `/resume` rotation threads.
- **Audit** for `/security` always. For `/reviewer`: **audit** when `review_required: yes` or `risk` includes `rest`, `sql`, `auth`, `export`, or `multisite`; otherwise planning tier is enough.
- Ambiguous SQL/auth/REST **design** during planning: stop and re-run planning on **audit** tier before coding.

Record the tier used in review/security note frontmatter (`model_tier: planning | worker | audit`), not vendor SKUs.

## Token Optimization

Every token costs. These rules keep context lean and runs fast.

**Read strategy:** grep first → read only needed sections → never re-read a file you just wrote.

**Lint economy:**

| Task touches | Lint |
|--------------|------|
| REST, SQL, auth, sanitization, PHP logic, JS/React | **Immediate** after task |
| Comments, docs, pure CSS, no new surface | **Batch** every up to **5** tasks |
| All tasks done | `npm run test:all` |

**Skip irrelevant commands:**

- No `composer run make:pot` unless i18n strings changed
- No `npm run build` unless `assets/src/` changed
- No `npm run lint:css` unless SCSS changed
- No `composer run verify:php` unless PHP changed

**Reference docs:** grep one `## Section` only — never load whole `.agents/docs/` mirrors.

## Thread Rotation

Chat history is not memory. The **task file** is.

Rotate to a **new thread + `/resume`** when any trigger fires:

- **3 tasks** completed in one thread (worker tier)
- **~20 agent turns** in one thread
- End of your work day or before a long break
- Context feels stale (agent repeats questions or forgets decisions)

Before rotating, update `## Session checkpoint` (last task done, files touched, open decisions, review required).

In the new thread: `/resume` + `@.agents/tasks/YYYY-MM-DD-<type>-<slug>.md` on a **worker** tier model.

## Reference Docs — Available, Not Mandatory

Large mirrors live in `.agents/docs/`. **Never read an entire mirror file.**

| File | Size | How to use |
|------|------|------------|
| `docs-library.md` | ~46k lines | Grep or read **one** `## Section` (~200 lines max). Curated content is **above** `<!-- updatronix:handbook-mirror:start -->` — prefer that region. |
| `wordpress-documentation-style-guide-consolidated.md` | ~19k lines | Grep TOC / `Source:` URL / one section when writing user-facing prose. |
| `wordpress-native-updates-reference.md` | Frozen | Cite only; never edit without owner authorization. |

Default: read **`inc/` source** and **`workflow.md`** first. Open docs only when the API or style rule is unclear.

## Task File — External Memory

Path: `.agents/tasks/YYYY-MM-DD-<type>-<slug>.md`

Sections: `Goal` · `Context` · `Tasks` · `Session checkpoint` · `Log` · `Feedback`

Frontmatter may include `review_required`, `risk`, and `model_tier` hints for hand-off.

The agent creates and maintains this file. Templates in `.agents/templates/` are reference only.

Deliverables per feature: **one task file** + **one review note** (when required) at `.agents/notes/YYYY-MM-DD-review-<slug>.md`.

## Review — Required vs Optional

**`/reviewer` required** when the change touches any of:

- REST routes, Ajax handlers, or export/download flows
- SQL, custom tables, transients, or option schema
- Capabilities, auth, multisite, or role checks
- User input (forms, email, file paths, redirects)
- Admin UI with new interactive controls (a11y surface)

**Optional** (agent may skip if you agree): comments/PHPDoc only, pure SCSS cosmetics, typo/copy wrapped in i18n with no logic change.

When required, the architect must set `review_required: yes` in task frontmatter and say so at hand-off.

## Build & Lint Reference

| Command | When |
|---------|------|
| `composer run verify:php` | PHP changes (CS Fixer + PHPStan + unit tests) |
| `npm run lint` / `npm run lint:css` | JS / SCSS |
| `npm run test:all` | End of dev cycle |
| `composer run lint:pcp` | Pre-release |
| `composer run make:pot` | i18n strings added/changed/removed |
| `npm run build` | Production assets |

## Hard Rules

**i18n** — Never reword strings inside `__()`, `_e()`, `_n()`, `_x()`, `esc_html__()`, `esc_attr__()`. Text domain stays `updatronix`. Intentional string change: flag in chat + changelog entry + wait for confirmation.

**Frozen** — `.agents/docs/wordpress-native-updates-reference.md` is read-only without owner authorization.

**Version** — Never bump `UPDATRONIX_VERSION`, headers, `Stable tag:`, or package versions without explicit owner authorization in the current conversation.

**Files** — Never delete `.agents/tasks/` or `.agents/notes/` without owner confirmation. Stay in task scope.

Human playbook: `.agents/HOW_TO_USE.md`.
