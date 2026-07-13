# Changelog

All notable project changes will be documented in this file.

This project uses semantic versioning once public releases begin.

## [Unreleased]

### Added

- Added project-specific README documentation.
- Added API documentation in `docs/API.md`.
- Added Railway deployment documentation in `docs/DEPLOYMENT.md`.
- Added engineering readiness report in `docs/ENGINEERING_READINESS.md`.
- Added security policy in `SECURITY.md`.
- Added Railway config-as-code file in `railway.toml`.
- Added Sanctum-backed registration, login, logout, and current user endpoints.
- Added full blog publishing fields, categories, tags, and comments.
- Added admin role support, admin-only middleware, dashboard metrics, user role management, and global moderation endpoints.
- Added feature tests for registration, publishing, and guest comment validation.
- Added feature tests for owner-only blog changes, comment moderation, and invalid blog payloads.
- Added feature tests for admin dashboard access, role promotion, last-admin protection, blog status moderation, and comment moderation.
- Added unit tests for blog ownership, model contracts, hidden comment fields, and request validation rules.
- Added a CI coverage job that uploads PHPUnit Clover coverage.

### Changed

- Replaced starter Laravel package metadata with project-specific Composer metadata.
- Updated `.env.example` to use the Blog Backend project name and local API URL.
- Configured PHPUnit to use in-memory SQLite during tests.
- Replaced stale `railway.yml` contents with a note pointing to `railway.toml`.
- Removed the committed application key value from `app.yaml`.
- Replaced weak custom token behavior with Sanctum personal access tokens.
- Replaced ad hoc blog CRUD behavior with validated REST-style blog routes.
- Restricted CORS configuration through `CORS_ALLOWED_ORIGINS`.
- Added Composer scripts for test and coverage commands.

### Security

- Documented current authentication, CORS, validation, and ownership risks.
- Marked the previous committed app key as requiring rotation if it was used outside local testing.
- Added owner checks for blog updates, blog deletion, comment moderation, and comment deletion.
