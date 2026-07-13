# Engineering Readiness Report

## Project Summary

Blog Backend is a Laravel 10 API for a full blog platform. It supports author authentication, post publishing workflows, categories, tags, comments, owner-only moderation, and admin-level site management.

## Problem Definition

Target users need a backend service that lets authors manage blog content and lets readers browse published posts, filter content, and submit comments. The backend must protect author-owned content, validate input, and expose predictable API contracts for a frontend client.

## Stakeholders

- Authors: register, sign in, draft, publish, update, archive, and delete posts.
- Admins: manage user roles, review all posts, moderate all comments, and inspect dashboard metrics.
- Readers: browse published posts and submit comments.
- Frontend client: consumes authentication, blog, category, tag, and comment APIs.
- Repository reviewer: evaluates setup quality, architecture decisions, and test coverage.
- Deployment operator: configures environments, database access, logs, and rollbacks.

## Functional Requirements

| ID | Requirement | Current Status |
| --- | --- | --- |
| FR-001 | Users can register with name, username, email, and password. | Implemented |
| FR-002 | Users can log in and receive a bearer token. | Implemented with Sanctum |
| FR-003 | Authenticated users can retrieve their account. | Implemented |
| FR-004 | Authenticated authors can create draft or published posts. | Implemented |
| FR-005 | Public users can list and view published posts. | Implemented |
| FR-006 | Authors can list their own posts across all statuses. | Implemented |
| FR-007 | Authors can update and delete their own posts. | Implemented |
| FR-008 | Posts can be assigned categories and tags. | Implemented |
| FR-009 | Readers can submit comments. | Implemented |
| FR-010 | Authors can approve, reject, or delete comments on their posts. | Implemented |
| FR-011 | Admins can view dashboard metrics. | Implemented |
| FR-012 | Admins can manage user roles. | Implemented |
| FR-013 | Admins can moderate all posts and comments. | Implemented |

## Non-Functional Requirements

| ID | Requirement | Current Status |
| --- | --- | --- |
| NFR-001 | Authentication must use verifiable tokens. | Satisfied with Sanctum |
| NFR-002 | API inputs must be validated before persistence. | Mostly satisfied with Form Requests |
| NFR-003 | Passwords must be hashed at rest. | Satisfied |
| NFR-004 | Protected operations must enforce ownership. | Implemented in controllers |
| NFR-005 | API behavior must be covered by automated tests. | Partially satisfied |
| NFR-006 | Deployment configuration must be reproducible. | Improved by `railway.toml` |
| NFR-007 | Secrets must not be committed. | Improved, previous app key requires rotation |

## Architecture Assessment

Current architecture: Laravel MVC with controllers, Form Requests, Eloquent models, routes, and migrations.

Decision rationale:

- A modular Laravel monolith is appropriate for this project size.
- Microservices are not justified because the domain is cohesive and deployment should remain low-complexity.
- Sanctum fits the API authentication requirement without introducing a separate identity service.

Architecture risks:

- Ownership checks still live in controllers instead of policies.
- Admin access checks live in middleware and controller logic instead of policies.
- The legacy `Blogs` model name is plural because it comes from the original project.
- Existing legacy rows may need a backfill for `user_id`, `slug`, `status`, and `published_at`.

Recommended next architecture step:

Move blog and comment ownership checks into Laravel policies and introduce API resources for consistent response formatting.

## Database Assessment

Current entities:

- `users`: author identity and login credentials.
- `users.role`: author or admin access level.
- `blogs`: post content, slug, status, publish date, owner, category, and legacy author field.
- `categories`: blog category taxonomy.
- `tags`: reusable tag taxonomy.
- `blog_tag`: many-to-many blog tag assignments.
- `comments`: reader comments with moderation status.

Database risks:

- Fresh databases now use a text column for blog content.
- Legacy blog rows may not have new ownership and publishing fields populated.
- Existing deployed databases created before this upgrade may need a manual content column conversion to `text`.
- A data migration should backfill slugs and user ownership before production use.

Recommended next database step:

Backfill existing blog rows and confirm any previously deployed database uses text storage for blog content.

## Backend Assessment

Backend strengths:

- Sanctum tokens replace the original custom token format.
- Form Request classes validate registration, login, blog creation, blog updates, and comments.
- Public and authenticated routes are separated.
- Owner checks protect post updates, post deletion, comment moderation, and comment deletion.
- Admin middleware protects site-wide management routes.
- Legacy route names remain available for older clients.

Backend risks:

- Admin bootstrap process is manual because there is no first-admin setup command yet.
- Authentication rate limits need automated tests and production threshold review.
- Response formatting is controller-driven rather than resource-driven.
- Comment submission supports guests but moderation workflows need broader test coverage.

Recommended next backend step:

Add policies, API resources, rate limiting, and more feature tests.

## Security Assessment

Current security status: improved but not production complete.

High-priority remaining risks:

- Authorization should move from controllers to policies.
- Admin bootstrap should be controlled through a seed, command, or documented deployment step.
- Authentication rate-limit thresholds should be reviewed before production use.
- Existing deployment secrets should be reviewed and rotated where needed.
- Existing data should be backfilled before a public deployment.

Recommended security concept: defense in depth.

Authentication, request validation, ownership checks, database constraints, CORS restrictions, rate limiting, and deployment secret management should work together rather than relying on one control.

## Quality Assessment

Current tests:

- Unit tests for model ownership and validation rules.
- Application smoke tests.
- Feature test for registration and publishing.
- Feature test for guest comment identity validation.
- Feature tests for owner-only update and delete behavior.
- Feature test for comment moderation.
- Feature test for invalid blog payload validation.
- Feature tests for admin dashboard access, role promotion, last-admin protection, global blog moderation, and global comment moderation.

Quality gaps:

- No unit tests for policy classes yet because policies have not been introduced.
- No category and tag endpoint tests yet.
- No non-owner comment moderation test yet.
- No test for first-admin setup because no setup workflow exists yet.
- No CORS or rate-limit behavior tests yet.
- Coverage is collected in CI, but no minimum coverage threshold is enforced yet.

Recommended quality gate:

Before deployment, require:

- `composer install`
- `composer test`
- `composer test:coverage`
- `vendor/bin/pint --test`
- dependency audit where available

## DevOps Assessment

Improvements made:

- Added `railway.toml`.
- Documented Railway variables and deployment steps.
- Added a pre-deploy migration command.
- Added CORS origin configuration.

Operational gaps:

- No backup and restore test.
- No alerting or monitoring thresholds.
- No rollback drill.

## GitHub Readiness Assessment

Improvements made:

- Project-specific README.
- API documentation.
- Deployment documentation.
- Security documentation.
- Engineering readiness documentation.
- Project-specific changelog and license.

Remaining gaps before public portfolio sharing:

- Add more meaningful API tests.
- Add repository topics and a concise GitHub description.
- Link to the frontend repository if it exists.
- Confirm the final license copyright owner.

## Stop Rules

Do not treat this project as production ready until:

- Existing blog rows are backfilled.
- At least one admin user exists through a controlled setup process.
- Existing deployed database schemas are reviewed for content column type.
- Authorization policies are implemented.
- CI runs tests successfully.
- Secrets have been reviewed and rotated where needed.
- Deployment variables are configured outside version control.

## Recommended Build Order

1. Add policies for blog, comment, and admin authorization.
2. Add a first-admin setup command or seed workflow.
3. Backfill existing blog rows.
4. Review deployed database column types.
5. Add API resources for response consistency.
6. Add category, tag, non-owner moderation, and rate-limit behavior tests.
7. Add a coverage threshold once the first CI coverage result is reviewed.
8. Run a Railway deployment rehearsal.
9. Prepare interview notes that explain the auth, admin, data model, and moderation decisions.
