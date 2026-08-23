# Software Quality Attribute Review

This review records observable evidence from source inspection, live-database metadata, automated tests, request instrumentation, production asset compilation and responsive browser checks. Timings are local-development measurements and must not be presented as production benchmarks.

## Summary

| Attribute | Current rating | Evidence | Improvements made | Remaining note |
|---|---|---|---|---|
| Usability | Good | Clear primary navigation, meaningful Discovery filters/states, grounded assistant results, coherent cards and CTAs | Real footer routes; corrected modal behavior | Festival page hierarchy and placeholder support links need polish |
| Performance | Good | Core routes 0.10–0.77 s locally; pagination; eager loading; 3-query map; gzip-sized bundles are moderate | Community N+1 removed; calendar payload limited to 75 Festivals | Paginate Community; optimize multi-megabyte images |
| Maintainability | Acceptable | Strong Discovery contracts/services/repositories; shared components and page assets | Header query moved to composer; debug code removed | Large controllers/services/Blade/CSS and broad Pint drift remain |
| Reliability | Good | 53 tests/245 assertions; route smoke; graceful no-result/null-image/null-coordinate/AI fallback | Notifications 500 fixed; invalid empty modal image request fixed | Add automated tests outside Discovery and handle storage rollback |
| Security | Acceptable | CSRF, escaped Blade, validation, auth middleware, ignored `.env`, environment-backed secrets | Production guard on Festival test-email routes | Harden token claims, Festival validation, upload architecture and deployment config |
| Responsiveness | Good | Ten viewport widths across ten major pages; hamburger/card/footer/map behavior checked | Community/Festival/Recommendation mobile overflow fixed | Recheck with real mobile devices and dynamic OS font scaling |
| Accessibility | Acceptable | Semantic controls/labels in many flows, alt text, responsive menu attributes, map fallback | Added Community modal control labels | Focus, target size, modal focus management and heading hierarchy need systematic pass |
| Extensibility | Good | Interfaces, container binding, DTO/parser strategies, optional AI fallback, modular services | View composer follows shared presentation extension point | Non-Discovery modules need incremental boundaries, not a wholesale rewrite |

## A. Usability

Evidence:

- Shared header consistently exposes Home, Discovery, Community, Festival, Engagement, Profile and authentication state.
- Discovery users can search by keyword/location, filter by category/type, sort, paginate, switch to map, and recover from no results.
- The assistant provides contextual suggestions and explicitly reports when no database-backed match exists.
- Guest recommendations show an understandable fallback rather than writing synthetic activity.
- Community, Engagement and Profile have meaningful empty/guest states.

Problems and severity:

- Festival Calendar lacks a page-level `<h1>`: Low.
- Some support/social/newsletter controls are presentation-only: Low for prototype, Medium if advertised as complete.
- Several compact links and checkbox targets are below an ideal touch size: Medium accessibility/usability.

Safe improvements completed: footer Quick Links now navigate to implemented routes; Community photo viewer no longer triggers a bogus page-image request.

Verification: all major user journeys rendered; Discovery search/filter/sort/no-results/assistant journeys behaved correctly.

## B. Performance

Evidence:

- `/experiences` is paginated at nine records; 126 total experiences produced 14 pages.
- Discovery repositories eager-load category/type relationships.
- Lazy-loading prevention found no hidden relation loads on the main tested routes.
- Typical local route timings were 0.10–0.77 seconds.
- Current map volume is 90 coordinate-bearing records, including 22 Cultural Experience markers.

Problem found: authenticated Community executed 40 queries for 33 posts, including 33 repeated like-existence queries (High at scale). It now uses one `withExists` projection and measured seven queries for the same route.

Remaining concerns: Community retrieves all 33 posts; the map sends the whole current mappable set; several images exceed 1 MB; generated Engagement banner is 2.32 MB.

Verification: post-fix request query count, production Vite build, and browser route reloads passed.

## C. Maintainability

Evidence:

- Discovery has explicit repository interfaces, Eloquent implementations, services, DTOs and parser strategies.
- Interfaces are bound in `AppServiceProvider` and controllers use constructor injection.
- Reusable experience cards and shared header/footer reduce duplication.

Problems:

- Uneven architecture across team modules: Medium.
- 522-line Engagement controller, 704-line recommendation service, 1,037-line Community creation view and very large CSS files: Medium.
- Inline validation and direct HTTP/data access in controllers: Medium.
- Project-wide Pint drift: Low runtime / Medium collaboration cost.

Safe improvement: header data is provided by a dedicated composer; no database query remains in the shared Blade template.

Verification: container resolution, Blade compilation, PHP syntax and the full test suite passed.

## D. Reliability

Evidence:

- Null experience images use a neutral fallback; broken remote images can fall back in the card UI.
- Map JavaScript rejects null, non-numeric and `(0,0)` coordinates rather than crashing.
- Missing category/type relations are rendered defensively.
- Optional AI is disabled safely and the rule parser remains available.
- No-match search and empty recommendation/community states are user-readable.

Problem found: `/notifications` returned HTTP 500 because a Blade page requested a Vite entry not declared in `vite.config.js` (High). The CSS was already imported through `app.css`; the redundant directive was removed.

Remaining concern: Community uploads occur before the database transaction and lack a compensating delete if persistence fails (Medium).

Verification: Notifications returned 200 in authenticated request-level smoke; all 53 tests passed.

## E. Security

Evidence:

- No `.env` file is tracked.
- No raw unescaped Blade output was found.
- State-changing forms/fetch calls include CSRF protection.
- Upload validation restricts image types and 10 MB per file.
- Credentials come from `config/services.php` and environment variables.

Problem found: authenticated Festival test routes could send real emails in non-local environments (High abuse risk). They are now registered only when `app()->isLocal()`; a production route-list check confirms absence.

Remaining problems:

- Auth JWKS verification should explicitly validate expected issuer/audience: Medium.
- Alert category IDs should be constrained with `exists`; reminder IDs should be Festival-only: Medium.
- Supabase service-role upload calls should be isolated behind a service with timeout/failure cleanup: Medium.
- Local `APP_DEBUG=true` and `mail=log` must not be deployed: deployment-critical configuration.

Verification: no committed secret detected, production test routes absent, and normal auth-protected routes remain guarded.

## F. Responsiveness

Evidence: `/`, login, profile, Discovery, recommendations, map, detail, Festival, Community and Engagement were tested at 1920, 1440, 1280, 1024, 768, 430, 390, 375, 360 and 320 pixels.

Problems found:

- Community hero action overflowed mobile widths: Medium.
- Festival grid exceeded the viewport at 375px and below because grid children retained intrinsic widths: Medium.
- Recommendation heading visually exceeded its container at 320px: Low.

Fixes: stacked the Community hero; used zero-minimum grid tracks/children; reduced and safely wrapped the smallest recommendation heading.

Verification: no user-scrollable horizontal overflow on the retested major pages. Community at 320px retains a 2px fractional document metric from desktop-scrollbar emulation, but `scrollX` remains zero and no element exceeds the viewport.

## G. Accessibility

Evidence: most form controls have labels, images have fallback alt/presentation treatment, native buttons/links are used, navigation exposes state, and map/list text remains available.

Problems:

- Photo viewer buttons lacked accessible names: Medium, fixed.
- Modal focus trap/return behavior is not implemented: Medium.
- Festival Calendar has no `<h1>`: Low.
- Compact checkboxes and text links do not consistently reach 44×44 px: Medium.
- Placeholder links marked only with `aria-disabled` remain focusable: Low.

Verification: browser interaction and console checks passed; this was a lightweight audit, not a full screen-reader/WCAG conformance test.

## H. Extensibility

Evidence:

- Discovery intent parsing can switch between rule and LLM implementations behind one interface.
- The fallback parser prevents provider failure from becoming application failure.
- Repository contracts make Discovery data access replaceable/testable.
- Service container bindings centralize implementations.
- Shared view composer, layouts and components provide Laravel-native extension points.

Problem: other modules do not consistently expose repository or infrastructure boundaries, increasing change coupling (Medium).

Recommended path: add boundaries only when a module changes, starting with tests and the external Storage/Auth seams. Do not force repositories around simple, stable Eloquent CRUD solely for diagram purity.
