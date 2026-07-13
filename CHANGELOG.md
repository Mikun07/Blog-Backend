# Changelog

All notable project changes are documented here as a factual product timeline. This file does not rewrite or imply different Git commit dates; it summarizes the real project history in a reviewer-friendly shape.

This project will use semantic versioning once public releases begin.

## [Unreleased]

### Changed

- Refreshed README, deployment, security, project overview, engineering readiness, and OpenAPI documentation to match the current backend feature set.

## Product Timeline

CI/CD-only maintenance is tracked separately in the Maintenance Timeline section.

### 2026-07-13 - Backend Feature Expansion

Commit: `1a5ffa1`

#### Added

- Added admin user creation, suspension, reactivation, deletion guardrails, user details, and user history endpoints.
- Added admin post creation, post inspection, and expanded global moderation workflows.
- Added authenticated profile updates through `PATCH /api/auth/me`, including guarded password changes.
- Added post image uploads through `cover_image` with local public storage and cleanup on replace/delete.
- Added database notifications for newly published posts across active users.
- Added notification listing, unread count, mark-one-read, and mark-all-read endpoints.
- Added admin and blog seeders for repeatable local/demo data.
- Added Laravel-served API documentation at `/docs/api` and OpenAPI JSON at `/docs/api/openapi.json`.
- Added Docker and Docker Compose files for optional local development.
- Added Railway deployment notes and a Netlify warning for this backend repository.
- Added feature tests for admin management, image uploads, notifications, profile updates, API docs, and admin seeding.

#### Changed

- Expanded API docs and the OpenAPI contract to cover admin, notifications, profile updates, image uploads, and documentation routes.
- Updated suspended-user handling so suspended accounts cannot continue using protected routes.
- Updated local and deployment documentation for the current backend architecture.

#### Validation

- `C:\xampp\php\php.exe artisan test` passed with 52 tests and 243 assertions before the commit was pushed.

### 2026-07-13 - Route Stability Fixes

Commits: `d7a9a87`, `d7b157e`

#### Fixed

- Fixed route constant redeclaration behavior.
- Prevented accidental instantiation of route path helper classes.

### 2026-07-13 - Admin Management API

Commit: `2e990c6`

#### Added

- Added admin role support and admin-only middleware.
- Added admin dashboard metrics.
- Added admin user role management.
- Added global blog and comment moderation endpoints.
- Added feature and unit tests for admin access, role promotion, last-admin protection, and moderation flows.

#### Changed

- Updated documentation to describe admin workflows and authorization expectations.

### 2026-07-13 - Core Blog API Expansion

Commit: `0015330`

#### Added

- Added Sanctum-backed registration, login, logout, and current-user lookup.
- Added expanded blog publishing fields, categories, tags, and comments.
- Added public published-post listing and authenticated author workspace routes.
- Added Form Request validation for auth, blog, and comment payloads.
- Added owner checks for blog updates, blog deletion, comment moderation, and comment deletion.
- Added project README, API docs, deployment docs, security docs, engineering readiness docs, and license.
- Added Railway config-as-code through `railway.toml`.
- Added unit and feature tests for auth, publishing, ownership, comments, model contracts, and validation rules.

#### Changed

- Replaced weak custom token behavior with Laravel Sanctum personal access tokens.
- Replaced ad hoc blog CRUD behavior with validated REST-style blog routes.
- Configured CORS through `CORS_ALLOWED_ORIGINS`.
- Configured PHPUnit to use in-memory SQLite during tests.
- Replaced starter Laravel package metadata with project-specific Composer metadata.

#### Security

- Removed committed application key material from deployment config.
- Documented authentication, CORS, validation, ownership, and secret-rotation risks.

### 2023-11-25 - Initial Blog Persistence And Deployment Basics

Commits: `c7e2988`, `f6cc72f`, `206e09d`, `a08d009`, `7ad61de`, `9b43a36`, `63eea73`, `20227ec`

#### Added

- Added the initial blogs table migration.
- Added early Railway configuration.
- Added baseline database migration adjustments for users and blogs.

#### Changed

- Adjusted model casing and login response formatting.
- Iterated on the original blog/user schema.

### 2023-11-23 to 2023-11-24 - Laravel Project Bootstrap

Commits: `40415e9`, `2f242f8`, `ac42e78`, `c9f646b`

#### Added

- Added the initial Laravel application skeleton.
- Added the first README content.

## Maintenance Timeline

### 2026-07-13 - CI/CD Workflow Refresh

Commit: `774ca23`

#### Changed

- Updated GitHub Actions workflow dependencies to Node 24-compatible actions.

## Notes

- This changelog intentionally keeps CI/CD-only work separate from product feature work.
- Future product releases should add dated sections here instead of rewriting Git commit dates.
