# Proposal vs Final Implementation

The proposal/design PDFs are incomplete historical documents. This comparison uses them as requirement history and treats the current repository and live PostgreSQL schema as implementation truth.

## Discovery / Experience module

| Proposed function | Final status | Implemented? | Implementation location | Notes |
|---|---|---:|---|---|
| Keyword search by experience name/cultural words | Complete | Yes | `ExperienceIndexRequest`, `ExperienceDiscoveryService`, `EloquentExperienceRepository`, `experiences/index.blade.php` | Validated, paginated and tested |
| Manual state/city/location search | Complete | Yes | Same Discovery flow | Text-based only; no GPS/Near Me |
| Category filtering | Complete | Yes | Repository relation filter + Discovery form | Uses live `category_id` relationship |
| Experience type filtering | Added later | Yes | Discovery request/service/repository | Supports Cultural Experience/Festival split inside one table |
| Experience listing | Complete | Yes | `ExperienceController@index`, repository, Blade cards | Related type/category eager-loaded |
| Newest/oldest sorting | Complete | Yes | Request + repository | Browser-tested |
| Pagination | Complete | Yes | Eloquent pagination | Nine per page; query parameters preserved |
| Experience details | Complete/shared | Yes | `ExperienceController@show`, `experiences/show.blade.php` | Includes recent-view tracking and map data |
| Personalized recommendations from interests/activity | Complete | Yes | `PersonalizedRecommendationService`, recommendations view | Falls back honestly when user/activity data is unavailable |
| Featured/popular fallback | Complete | Yes | Recommendation service and Home preview | No invented database records |
| Recent browsing activity recording | Complete | Yes | `UserDiscoveryActivityService`, activity repository, view/search history models | Used by recommendations |
| Recent activity display | Partial | Partial | Recommendation page activity panels | No standalone full browsing-history management page in Discovery |
| Clear recent activity | Not implemented | No | — | Remains a documented gap; assistant context reset is a different feature |
| Save favourites | Implemented by shared/Profile module | Yes | `SavedExperienceController`, `SavedExperienceService` | Correctly not duplicated inside Discovery |
| Search/result notifications and errors | Complete | Yes | Form Request messages, Blade states, service fallbacks | Includes invalid/no-result/recommendation-unavailable states |
| Leaflet experience map | Added later | Yes | `ExperienceController@map`, repository, `experience-map.js` | Uses `latitude`/`longitude`; null records excluded safely |
| Cultural Discovery Assistant V1/V2 | Added later | Yes | assistant controller, context/service/parser strategies, JS component | Database-grounded; optional AI with deterministic fallback |

## Whole-system proposal consistency

| Earlier assumption / proposed capability | Final status | Implemented? | Final location | Documentation correction needed |
|---|---|---:|---|---|
| Laravel MVC / generic three-layer design | Evolved | Yes | Application-wide; strongest in Discovery | Describe as hybrid Layered + MVC, not plain MVC and not uniformly repository-based |
| MySQL/XAMPP persistence | Replaced | No | PostgreSQL hosted through Supabase connection | Update technology stack and deployment diagram |
| Separate Events module/table | Replaced | No | `experiences.type_id = 2` and dates | Remove Event model/controller/table assumptions |
| Cultural Experiences | Complete | Yes | `experiences.type_id = 1` | 51 current records during audit |
| Festivals | Complete | Yes | `experiences.type_id = 2` | 75 current records, all dated during audit |
| Supabase Authentication | Added/evolved | Yes | Supabase JS + `AuthController` JWT/JWKS sync | Show Auth as infrastructure integration |
| User profile/interests/photos | Implemented | Yes | Profile controllers, Requests and services | Architecture is partially layered |
| Festival calendar/alerts/reminders/email | Implemented | Yes | Festival controllers, command, mail class and views | Events are Experience rows; test routes local-only after audit |
| Community posts/images/likes/saves | Implemented | Yes | Community controllers/services/models/views | Supabase Storage is called directly in controller; note deviation |
| Engagement passport/stamps/achievements/history | Implemented | Yes | Engagement controller/services/models/views | Controller remains a maintainability hotspot |
| Notifications | Implemented | Yes | Notification controller/model/view and shared bell | Authenticated page stabilized in this audit |
| Map/coordinates | Added later | Yes | Discovery map + Leaflet/OpenStreetMap | Include external tile service in architecture |
| Optional generative AI | Added later | Yes, optional | `LlmDiscoveryIntentParser` behind fallback interface | Current local configuration has AI disabled; functionality remains via rules |
| Tourism Malaysia scraping/import workflow | Development tooling added later | Yes | console/import scripts and review artifacts where present | It is content-pipeline tooling, not a browser user module; keep separate in assessment scope |

## Architecture change narrative for the assessment

The early design established Laravel MVC and separation between presentation, application logic and data. During implementation, Discovery formalized those boundaries using injected services, repository contracts, Eloquent implementations, DTOs and parser strategies. The rest of the team retained pragmatic Laravel MVC and introduced services only around selected workflows. The final architecture is therefore a real hybrid, not a perfectly uniform textbook stack.

This is defensible because:

- Laravel controllers, Form Requests, Blade and Eloquent preserve MVC roles.
- Services isolate substantial recommendation, assistant, profile and engagement rules.
- Discovery repositories control reusable search/activity queries and improve testability.
- Simple CRUD is allowed to use Eloquent directly where another abstraction would add little value.
- External services are visible infrastructure seams, even where Auth/Storage gateways are still a recommended improvement.

## Documentation items that must be updated

1. Replace MySQL-specific diagrams with PostgreSQL/Supabase.
2. Remove the separate Event entity/module and document `experience_type` plus dates on `experiences`.
3. Use the generated final architecture diagram instead of an idealized uniform repository flow.
4. Add map, activity tracking, contextual assistant, optional AI fallback and Supabase integrations to final use cases/components.
5. State that clear browsing history is not implemented and that favourites belong to the shared/Profile implementation.
6. Ensure UI screenshots match the final Home, Discovery, Recommendations, Map, Community, Festival, Profile and Engagement pages.
7. Do not claim uniform service/repository adoption across every teammate module.
