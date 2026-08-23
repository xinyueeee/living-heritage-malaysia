# Living Heritage Malaysia — Final Whole-System Audit

Audit date: 23 August 2026  
Scope: current `main` working tree at `b8c16f7099b11044610f499b9af422fbf460b7bb`, plus the safe uncommitted fixes listed below.

## Executive verdict

**READY WITH MINOR IMPROVEMENTS.** The prototype is functionally substantial, visually coherent, and stable in the tested environment. Discovery is a genuine hybrid Layered + MVC implementation. Other modules use Laravel MVC with selected services, but several controllers still combine application, data-access, and infrastructure responsibilities. Those are maintainability concerns rather than submission-blocking runtime defects.

Scores:

| Measure | Score | Basis |
|---|---:|---|
| Layered Architecture compliance | 7.4/10 | Discovery has services, contracts and repositories; other modules only partially use the same boundaries. |
| MVC compliance | 8.6/10 | Routes, controllers, Eloquent models and Blade views are consistently used; no database access remains in Blade after stabilization. |
| Overall architecture quality | 7.8/10 | Clear module separation and dependency injection in Discovery, with manageable but real cross-layer shortcuts elsewhere. |

## Repository and runtime state before modification

| Check | Result |
|---|---|
| Repository | `C:\Users\tanxi\Desktop\living-heritage-malaysia` |
| Branch | `main` |
| HEAD | `b8c16f7` — Polish Discovery assistant responsive layout |
| Origin synchronization | `main` matched `origin/main` (ahead 0, behind 0) |
| Working tree | Clean before the audit fixes |
| Merge conflicts | No unresolved `<<<<<<<` / `>>>>>>>` markers |
| PHP | 8.2.12 |
| Laravel | 12.64.0 |
| Node / npm | Node 24.18.0 / npm 11.16.0 |
| Composer | 2.10.2 |
| Database | PostgreSQL through the project’s Supabase connection |
| Environment | Local, debug enabled; mail transport `log`; optional Discovery AI disabled |
| Baseline tests | 53 passed, 245 assertions |
| Baseline Vite build | Passed, 117 modules transformed |
| Baseline Pint | Failed on widespread pre-existing style differences; a global automatic rewrite was intentionally not performed |

Important packages include Laravel 12, `firebase/php-jwt`, `resend/resend-php`, Supabase JS 2.111, Leaflet 1.9.4, Vite 7.3.6, Tailwind 4.3, Axios 1.18, PHPUnit 11.5 and Pint 1.24.

## Earlier design material compared with the code

The available proposal and design PDFs describe an early MVC/three-layer target and an earlier ERD. They are useful requirement history, but they are not a reliable representation of the final schema or runtime. Important changes include:

- Events were merged into `experiences`; `type_id` and dated Festival records now distinguish the content.
- MySQL/XAMPP proposal assumptions changed to PostgreSQL hosted by Supabase.
- Discovery gained repository contracts, services, activity tracking, a Leaflet map, and a contextual Cultural Discovery Assistant.
- Supabase Auth and Storage, optional AI parsing, and mail notification infrastructure became explicit integrations.
- No earlier architecture audit Markdown was found in the repository. The current assessment is therefore based on the actual code, live schema metadata, Git history, tests, and browser behavior.

## Actual architecture

The system is **hybrid Layered Architecture + Laravel MVC**, with unequal adoption across modules.

Normal Discovery flow:

`Route → Form Request → Controller → Application Service → Repository Contract → Eloquent Repository → Model → PostgreSQL → Blade/JSON`

Laravel-conventional flow still used elsewhere:

`Route → Controller → Service and/or Eloquent/Query Builder → Model/PostgreSQL → Blade/JSON`

This deviation is reasonable for small CRUD operations, but long controllers and direct infrastructure calls should be moved incrementally after submission rather than through a risky final-week rewrite.

## Module architecture compliance

| Module | Current flow | Target flow | Compliance | Problem | Severity | Recommendation |
|---|---|---|---|---|---|---|
| Discovery | Routes → Requests → `ExperienceController` / `DiscoveryAssistantController` → Discovery services → repository contracts → Eloquent repositories → models → PostgreSQL → Blade/JSON | Same | Strong | `PersonalizedRecommendationService` (704 lines) and repository (301 lines) are growing | Medium | Split only when adding new behavior; preserve tested contracts now |
| Authentication | Route → `AuthController` → Supabase JWKS/HTTP + `User` → session → Blade | Controller → auth infrastructure service → model | Partial | External authentication verification lives in the controller; expected issuer/audience checks are not explicit | Medium | Extract an auth gateway and tighten token claim validation after submission |
| Profile | Routes → Form Requests/controllers → `ProfileService` / `ProfileAchievementsService` and some direct Eloquent/DB → Blade | Requests → controller → services → repositories/models | Partial | `ProfileController` contains direct queries; storage is handled directly inside `ProfileService` | Medium | Keep current behavior; introduce a profile repository/storage gateway when the module changes |
| Community | Routes → controller → `SavedPostService` / engagement service plus Eloquent and direct Supabase Storage HTTP → Blade/JS | Requests → controller → services → repository/storage gateway | Partial | Controller mixes validation, upload infrastructure and persistence; feed is unpaginated | Medium | Add a Form Request and upload service later; paginate when feed size requires it |
| Engagement & Rewards | Routes → 522-line controller → two services + Eloquent → Blade | Controller → cohesive services → repository/model | Partial | Calculations and queries remain controller-heavy | Medium | Extract one workflow at a time after submission with characterization tests |
| Festival Alert | Routes → `AlertController` / `CalendarController` / `NotificationController` → Query Builder/Eloquent/Mail → Blade/JSON | Requests → controllers → alert/calendar services → repository/model | Basic MVC | Inline validation, direct query builder and mail dispatch; limited tests | Medium | First add tests, then service-wrap calendar/reminder/email flows |
| Notifications | Route → controller → Notification model → Blade | Acceptable small MVC flow | Acceptable | The page had a broken Vite entry and returned 500 | High, fixed | Redundant entry removed; shared imported CSS remains |
| Shared layout | Layout → header/footer partials; `HeaderComposer` supplies notification count | View composer/component → partial | Strong after fix | Header previously queried the database from Blade | Medium, fixed | Keep shared data provisioning outside templates |

## Layers present

| Layer | Status | Evidence |
|---|---|---|
| Presentation | Present | Blade layouts/pages/partials/components; modular CSS and page JavaScript |
| MVC request/controller | Present | Named routes, middleware, controllers, five Form Requests |
| Business/application | Partially strong | Rich Discovery services; Profile, Community and Engagement have selected services; Festival/Auth remain controller-heavy |
| Data access | Partially present | Two Discovery repository contracts and two Eloquent implementations, bound in `AppServiceProvider` |
| Domain/persistence | Present | Eloquent models with custom table/key mappings, relationships and casts |
| Database | Present | 47 migration files reported as applied and a live PostgreSQL/Supabase schema |
| Infrastructure | Present | Supabase Auth/JWKS, Storage HTTP, Leaflet/OpenStreetMap, Laravel Mail/Resend package, optional AI endpoint |

## Safe stabilization fixes made

1. Fixed authenticated `/notifications` HTTP 500 by removing a redundant `@vite('resources/css/festival.css')`; `app.css` already imports the stylesheet.
2. Replaced the shared header’s database query with `HeaderComposer`.
3. Replaced Community’s per-card `isLikedBy()` queries with one correlated `withExists` field; corrected `User::likes()` foreign/local keys.
4. Scoped Festival test-email routes to the local environment.
5. Restricted `/calendar/events` to dated Festival records (`type_id = 2`).
6. Removed Calendar debug `console.log` statements.
7. Prevented the Community photo modal from issuing an empty `src` request; added button labels.
8. Fixed Community mobile hero overflow, Festival grid min-width overflow, and the 320px recommendation heading.
9. Connected shared footer Quick Links to the real Community, Festival and Engagement routes.

No database data, schema, migration, approval state, recommendation scoring, scraper behavior, or external account was changed.

## Functional and UX audit

| Area | Result | Evidence / remaining note |
|---|---|---|
| Home | Pass | Clear hero, search action, quick navigation, experience/festival previews, responsive footer |
| Discovery listing | Pass | Keyword, manual location, category, sort, no-results state and 14-page pagination exercised |
| Recommendations | Pass | Guest fallback is read-only; feature/unit tests cover authenticated scoring and failure states |
| Map | Pass | `type=1` showed 22 markers; null/invalid/zero coordinates are excluded safely |
| Assistant | Pass | Grounded database results, no-match message, follow-up context and reset endpoint work; rule fallback operates with AI disabled |
| Experience detail | Pass | Existing record loads; missing image uses neutral fallback |
| Login/Auth UI | Pass | Google/Supabase sign-in page loads; no register route exists by design |
| Profile | Pass | Guest state is graceful; authenticated pages returned 200 in request-level smoke tests |
| Festival calendar | Pass after fix | Calendar loads dated Festival data; production excludes email test routes |
| Notifications | Pass after fix | Authenticated page no longer depends on an unconfigured Vite entry |
| Community | Pass after fix | Feed loads, like state no longer N+1, modal has no empty image request, mobile hero fits |
| Engagement/Rewards | Pass | Guest and authenticated dashboard/passport/achievement/history routes smoke-tested |
| Shared navigation | Pass | Active states, responsive hamburger, auth controls, logo and footer present across major pages |

Discovery query tests produced these real results during the audit:

- Keyword `Putra`: 2 results.
- Location `Putrajaya`: 3 results.
- Culinary category: 1 result.
- Oldest sort: selected and returned correctly ordered results.
- Nonsense keyword: zero results with “No matching experiences found”.
- Assistant query “Heritage in Putrajaya”: grounded cards for Putra Mosque and Moroccan Pavilion Putrajaya.

## Responsive audit

Major pages were inspected at 1920, 1440, 1280, 1024, 768, 430, 390, 375, 360 and 320 CSS pixels:

`/`, `/login`, `/profile`, `/experiences`, `/recommendations`, `/experiences/map`, `/experiences/82`, `/festival/calendar`, `/community`, `/engagement`.

The shared navigation changes to its mobile menu without dropping primary functionality. Card grids, footer columns, map container, assistant, forms and content remain usable. The three confirmed overflow defects were fixed and retested. At the 320px Community emulation, browser metrics retain a 2px fractional scroll-width difference caused by the desktop scrollbar calculation; `scrollX` remains zero and no element crosses the viewport, so there is no user-scrollable horizontal overflow. A remaining accessibility improvement is to enlarge several small text-link and checkbox hit areas toward a 44×44 CSS-pixel target; this was not changed globally because it would alter teammate layouts.

## Performance evidence

These are local-development measurements, not production benchmarks.

| Route | SQL queries | Typical response time | HTML/JSON size | Finding |
|---|---:|---:|---:|---|
| `/` | 7 | 0.54–0.64 s | 44 KB | Good for local/live DB |
| `/experiences` | 10 | 0.74–0.77 s | 110 KB | Paginated; within proposal’s 3 s goal |
| `/recommendations` | 5 | 0.49–0.54 s | 29 KB | Within proposal’s 5 s goal |
| `/experiences/map` | 3 | 0.40–0.42 s | 80 KB | 90 mappable records across types; acceptable at current scale |
| `/community` guest | 5 | 0.48–0.54 s | 165 KB | Loads all 33 posts; future pagination advised |
| `/community` authenticated, before fix | 40 | — | — | 33 repeated like-existence queries confirmed |
| `/community` authenticated, after fix | 7 | — | — | Like state fetched in the feed query |
| `/engagement` guest | 0 | 0.10–0.12 s | Small | Guest presentation only |
| `/festival/calendar` | 0 | 0.10–0.12 s | Small | Data fetched separately |
| `/calendar/events` | 1 | 0.28–0.31 s | Reduced to 75 dated Festival records | Query now excludes 51 undated Cultural Experiences |

The production build’s largest generated JavaScript files are approximately 216 KB (Supabase, 57 KB gzip) and 165 KB (application, 48 KB gzip). The largest generated CSS files are 96 KB (application, 18 KB gzip) and 44 KB (engagement, 8 KB gzip). The 2.32 MB Engagement banner and several 0.5–2.6 MB source images are the main asset-size concern; optimize source images after the demonstration if visual quality can be preserved.

## Database performance

The live database contained 126 experiences (51 Cultural Experiences and 75 dated Festivals). Important existing indexes cover experience category/type/status/date/coordinates, user activity history, favourites, saves, achievements, interests and passport joins.

Report-only index candidates as data grows:

- `notification(user_id, is_read)` or `notification(user_id, scheduled_at)`.
- `alert(user_id, category_id)`, ideally unique if duplicates are not valid.
- `post(created_at)` and `post(user_id)`.
- unique/indexed `post_like(user_id, post_id)`.
- unique `completed_experience(user_id, experience_id)` to protect `firstOrCreate` under concurrency.
- review indexes by user and experience before reviews become populated.
- PostgreSQL full-text or trigram indexes for `ILIKE` name/location search only when table growth demonstrates need.

No index migration was created because current data volume and timings do not show a severe problem.

## Reliability and security

Strengths:

- CSRF protection is used for state-changing Blade/JavaScript requests.
- Blade output uses escaped `{{ }}`; no `{!! !!}` rendering was found.
- Discovery and profile inputs use Form Requests; other mutation endpoints validate inline.
- Route model binding and explicit ownership filters are used in several user-owned actions.
- `.env` is not tracked; only `.env.example` is committed.
- Supabase and AI credentials are read from environment-backed configuration, not hardcoded.
- Null images/coordinates and unavailable AI are handled without crashing normal Discovery pages.
- All 53 automated tests passed before stabilization, and lazy-loading prevention found no hidden relation loads on the main smoke routes.

Remaining risks:

| Issue | Severity | Recommended handling |
|---|---|---|
| Supabase token verification does not explicitly enforce expected issuer/audience in `AuthController` | Medium | Harden with tests after submission; do not rewrite auth immediately |
| Community uploads call Supabase Storage directly from the controller and can leave an uploaded file if DB insert later fails | Medium | Extract a storage gateway and compensating delete in a future iteration |
| Community feed has no pagination | Medium at future scale | Add pagination with an approved UI change |
| Alert `category_ids.*` lacks an `exists:category,category_id` rule and writes are not transactional | Medium | Add validation and transaction with Festival-owner review |
| Reminder endpoint does not explicitly enforce Festival `type_id` | Medium | Validate event type with Festival-owner review |
| Production environment must disable debug, choose a real mailer and set HTTPS/session security | High deployment configuration | Complete deployment checklist; current `.env` is correctly local |
| Automated coverage is concentrated in Discovery | Medium | Add Festival, Auth, Community and Engagement feature tests before long-term maintenance |

No intrusive penetration testing was performed.

## Maintainability findings

High-value design strengths are clear controller injection in Discovery, repository contracts bound in the container, DTOs, page-specific JavaScript, reusable experience cards, and centralized shared layout.

The main maintainability hotspots are:

- `PersonalizedRecommendationService` — 704 lines.
- `EngagementController` — 522 lines.
- `CulturalDiscoveryAssistantService` — 307 lines.
- `EloquentExperienceRepository` — 301 lines.
- `resources/views/community/create.blade.php` — 1,037 lines.
- `resources/css/engagement.css` — 3,182 lines.
- `resources/css/community.css` — 2,101 lines.
- Global Pint reports broad pre-existing formatting drift.

These should not be rewritten immediately. Add tests around each workflow, then extract cohesive policies/components incrementally.

## Accessibility findings

Positive evidence includes semantic forms, many labels and alt texts, responsive nav state, keyboard-capable native controls, map list/fallback content, and escaped assistive text. This audit added labels to the Community modal controls and removed its invalid empty image request.

Remaining improvements are consistent visible focus styling across all modules, larger touch targets for compact text links/checkboxes, an explicit page-level `<h1>` for Festival Calendar, clearer disabled semantics for placeholder support/social links, and a fully verified focus trap/return behavior for modal dialogs.

## Ownership inference

Formal module ownership was not found in repository documentation. Git authorship suggests, but does not prove:

| Module | Likely owner(s) inferred from recent commits |
|---|---|
| Discovery / Assistant | `xinyueeee` (Tan Xin Yue) |
| Festival Alert | `xianluan` |
| Engagement & Rewards | Tan Xin Yi / `XY-tan` |
| Community / Profile | `yanhsii`, `yannqi218` |
| Experience map/details contributions | Earlier commits include `Jingting16` |

Cross-module fixes in this audit were limited to shared/system-breaking behavior and obvious query or responsive defects. Ownership should be confirmed by the team before larger refactors.

## Final prototype scores

| Area | Score | Honest limitation |
|---|---:|---|
| Functional completeness | 8.3/10 | Broad feature set; some proposal items and placeholder links remain incomplete |
| Usability | 8.1/10 | Clear core journeys and feedback; Festival and touch-target polish can improve |
| Performance | 8.2/10 | Good local timings and corrected N+1; Community needs future pagination/assets optimization |
| Reliability | 8.1/10 | Major routes and fallbacks work; non-Discovery test depth is limited |
| Maintainability | 6.9/10 | Strong Discovery structure but several giant/controller-heavy teammate modules |
| Responsive UI | 8.4/10 | Tested across ten widths; confirmed overflow defects corrected |
| Security/basic robustness | 7.4/10 | Standard Laravel protections and secret handling; auth claim and upload workflow hardening remain |
| Visual polish | 8.2/10 | Cohesive branding and strong main pages; some module-level inconsistency persists |
| Testing confidence | 7.0/10 | 53 green tests, but coverage is Discovery-heavy |
| Architecture quality | 7.8/10 | Genuine hybrid architecture with partial adoption outside Discovery |

## Submission priorities

### Must fix before submission

- Use production environment settings: `APP_DEBUG=false`, HTTPS, secure session/cookie settings, production mail transport and correct Supabase redirect URLs.
- Ensure the demonstration account and required external services are available without exposing credentials.
- Keep the fixed Notifications page, test-route environment guard and generated assets in the reviewed submission state.

### Should fix if safe

- Add one feature smoke test each for authenticated Notifications, Community, Festival Calendar, and Engagement.
- Add Festival category existence validation and Festival-only reminder validation with the module owner.
- Optimize the 2.32 MB Engagement banner and other multi-megabyte images without visual regression.
- Add a Festival Calendar `<h1>` and normalize touch/focus styling.

### Nice to have after submission

- Paginate Community.
- Introduce Auth/Storage gateways and repositories for non-Discovery modules.
- Break large services/controllers/views/CSS files into cohesive units.
- Add only evidence-driven database indexes as production data grows.

### Do not touch before submission

- Do not rewrite the recommendation scoring or assistant orchestration.
- Do not replace the working authentication flow without dedicated claim/integration tests.
- Do not redesign teammate pages or run global automatic formatting.
- Do not change the final database schema merely to make the layers look uniform.

## Final verification summary

- Laravel tests: **53 passed, 245 assertions**.
- Existing Tourism Malaysia scraper unit tests: **6 passed** (no scraper behavior changed).
- Blade compilation: **passed**.
- Vite production build: **passed**.
- `git diff --check`: **passed**.
- Browser console: **no relevant error/warning entries** after fixes.
- Major public routes: **HTTP 200**.
- Authenticated request-level smoke routes: **HTTP 200**, including Notifications after the fix.
- Pint: **project-wide `--test` still reports pre-existing formatting differences**; no risky global rewrite was applied.

The diagram source is `docs/final-architecture-diagram.mmd`; the rendered assessment image is `docs/final-architecture-diagram.png`.
