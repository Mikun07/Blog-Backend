# Blog Backend Project Overview

## What This Project Is

Blog Backend is a Laravel REST API for a full blog platform. It provides the backend system for authors to register, sign in, create posts, publish drafts, organize content with categories and tags, moderate comments submitted by readers, and support admin-level site management.

The project is currently a backend-only repository. It is designed to be consumed by a separate frontend client over HTTP.

```text
frontend client
    sends JSON requests with a Sanctum bearer token
    to Blog Backend
        which validates requests, enforces ownership, and persists data
        through Laravel Eloquent
        to MySQL
```

The frontend does not connect to the database directly. All business behavior, including authentication, post ownership, comment moderation, validation, and publishing state, is owned by the backend.

## The Problem It Solves

A blog platform needs more than basic post storage. Authors need a way to draft, publish, update, archive, and delete posts. Readers need a public content surface where they can browse published posts and submit comments. The system also needs to protect author-owned content so one authenticated user cannot edit or delete another author's work.

Blog Backend addresses this by modeling posts as owned resources with a publishing lifecycle, adding categories and tags for content discovery, supporting pending comments, and enforcing owner-only moderation on comments attached to a post.

## Who Uses It

The API supports three practical user groups and one elevated admin role.

| User Group | Capabilities |
| --- | --- |
| Guest reader | Browse published posts, filter posts, view approved comments, and submit a guest comment with name and email. |
| Authenticated reader | Use authenticated identity when interacting with protected endpoints. |
| Author | Register, log in, create posts, manage own posts, create categories and tags, and moderate comments on own posts. |
| Admin | View dashboard metrics, manage user roles, review all posts, update any post status, delete any post, review all comments, and moderate any comment. |

Protected author behavior is enforced through Sanctum authentication and ownership checks against the post owner. Admin behavior is enforced through the `admin` middleware, which checks the authenticated user's `role`.

## Core Domain Concepts

### Users

A user represents an account that can authenticate with email and password. Passwords are hashed through Laravel's hashing system, and authentication uses Laravel Sanctum personal access tokens. The API returns a bearer token after registration or login.

Each user has a `username`, which is unique in the expanded schema. Each user also has a `role` of `author` or `admin`. The system keeps compatibility with the original project by retaining a legacy `author` text field on blog posts, while also adding `user_id` ownership.

### Blogs

A blog post is the primary content record. It stores the title, slug, excerpt, content, cover image URL, author, category, tags, status, and publish timestamp.

The supported post statuses are:

| Status | Meaning |
| --- | --- |
| `draft` | The post exists but is not visible through the public blog listing. |
| `published` | The post is visible through public blog endpoints. |
| `archived` | The post is retained but removed from public discovery. |

Public listing and public show endpoints return only published posts. Authenticated authors can list their own posts across all statuses through `/api/me/blogs`.

### Categories

A category is a primary grouping for posts, such as `Engineering` or `Product Updates`. A post may belong to one category. Categories have a name, slug, optional description, timestamps, and a post count when listed through the API.

The blog creation endpoint can either receive an existing `category_id` or a new `category_name`. When a new category name is supplied, the backend creates the category using a slug generated from the name.

### Tags

Tags are reusable labels attached to posts through the `blog_tag` pivot table. A post may have multiple tags, and a tag may belong to multiple posts.

The blog creation and update endpoints accept a `tags` array. The backend normalizes tag names into slugs, creates missing tags, and syncs the post's tag assignments.

### Comments

Comments belong to one blog post and support moderation. Guest comments require `author_name`, `author_email`, and `content`. Authenticated users can submit comments using their account identity.

New comments are created with `pending` status. Only approved comments appear in the public comments list.

The supported comment statuses are:

| Status | Meaning |
| --- | --- |
| `pending` | The comment has been submitted but is not publicly visible. |
| `approved` | The comment is visible through public comment endpoints. |
| `rejected` | The comment was reviewed and rejected. |

The owner of the related post can approve, reject, or delete comments on that post. Admin users can moderate comments across the whole system.

## How the Backend Is Built

The backend runs on PHP and Laravel 10. It uses Laravel's MVC structure with Eloquent models, Form Request validation classes, controllers, migrations, and PHPUnit tests.

```text
Client to Laravel Routes to Controller to Form Request Validation to Eloquent Model to MySQL
```

The route layer is defined in `routes/api.php`. Public routes expose published blogs, categories, tags, health checks, and comment submission. Protected routes use `auth:sanctum` and include current user lookup, logout, author blog management, category and tag creation, comment moderation, and legacy route compatibility.

The main controllers are:

| Controller | Responsibility |
| --- | --- |
| `AdminController` | Dashboard metrics, user role management, global blog moderation, and global comment moderation. |
| `UserController` | Registration, login, logout, and current user lookup. |
| `BlogController` | Public blog listing, post publishing, owner-only updates and deletion, comments, and moderation. |
| `CategoryController` | Category listing and category creation. |
| `TagController` | Tag listing and tag creation. |

Request validation lives in `app/Http/Requests`. This keeps controller methods focused on orchestration rather than hand-written input checks. The request classes cover registration, login, post creation, post updates, and comments.

The data model is defined with Eloquent models:

| Model | Purpose |
| --- | --- |
| `User` | Authenticated account and post owner. |
| `Blogs` | Blog post record. The plural class name is retained from the original project for compatibility. |
| `Category` | Post taxonomy. |
| `Tag` | Many-to-many post labels. |
| `Comment` | Reader feedback with moderation state. |

The project remains a modular monolith. This is appropriate for the current scope because the blog domain is cohesive and does not require separate deployable services.

## API Surface

The API is mounted under `/api`.

Primary endpoint groups:

| Area | Example Endpoints |
| --- | --- |
| Health | `GET /api/health` |
| Authentication | `POST /api/auth/register`, `POST /api/auth/login`, `POST /api/auth/logout`, `GET /api/auth/me` |
| Admin | `GET /api/admin/dashboard`, `GET /api/admin/users`, `PATCH /api/admin/users/{id}/role`, `GET /api/admin/blogs`, `PATCH /api/admin/blogs/{id}/status`, `GET /api/admin/comments` |
| Blogs | `GET /api/blogs`, `GET /api/blogs/{slug-or-id}`, `POST /api/blogs`, `PATCH /api/blogs/{id}`, `DELETE /api/blogs/{id}` |
| Author workspace | `GET /api/me/blogs` |
| Categories | `GET /api/categories`, `POST /api/categories` |
| Tags | `GET /api/tags`, `POST /api/tags` |
| Comments | `GET /api/blogs/{id}/comments`, `POST /api/blogs/{id}/comments`, `PATCH /api/comments/{id}`, `DELETE /api/comments/{id}` |

The repository also keeps the original route names, such as `/api/addBlog`, `/api/listBlogs`, `/api/editBlog`, and `/api/deleteBlog`, so older clients have a migration path.

The complete endpoint documentation is maintained in `docs/API.md`.

## Security Model

Authentication uses Laravel Sanctum bearer tokens. Registration and login issue personal access tokens. Protected endpoints require `Authorization: Bearer <token>`.

Passwords are stored as hashes. User serialization hides password and remember token fields. Comment serialization hides `author_email` so reader email addresses are not exposed through public responses.

Request validation is enforced through Form Request classes and Laravel validation. Blog ownership is enforced by comparing the authenticated user against the post's `user_id` and, for legacy records, the post's `author` field. Admin routes require both Sanctum authentication and the `admin` middleware.

CORS is configured through `CORS_ALLOWED_ORIGINS`, which allows development and production frontend origins to be controlled through environment variables rather than hardcoded in application logic.

Current security gaps are documented rather than hidden:

- Authorization checks should be moved from controllers into Laravel policies.
- First-admin setup should be moved into a controlled setup command or deployment seed.
- Existing legacy rows may need a data backfill for ownership, slugs, and publishing fields.
- Rate-limit thresholds should be reviewed after real traffic patterns are known.
- Production monitoring, alerting, and incident response procedures are not complete.

## Testing and Continuous Integration

The project uses PHPUnit. The test configuration in `phpunit.xml` defines separate Unit and Feature test suites and uses in-memory SQLite during tests.

Current automated tests cover:

| Test Area | Coverage |
| --- | --- |
| Unit model tests | Blog ownership, status constants, fillable blog fields, and hidden comment email fields. |
| Unit request tests | Validation rule definitions for register, login, blog creation, blog update, and comments. |
| Feature API tests | Registration, publishing, public blog listing, owner-only update and delete, non-owner rejection, comment moderation, guest comment validation, and invalid blog payload validation. |
| Admin feature tests | Admin dashboard access, role promotion, last-admin protection, global blog status changes, and global comment moderation. |
| Smoke tests | Root application response coverage retained from the Laravel application layout. |

GitHub Actions runs the test suite on PHP 8.1, 8.2, and 8.3. A separate coverage job runs on PHP 8.3 with Xdebug enabled, generates Clover coverage as `coverage.xml`, uploads the artifact, and writes a statement coverage summary into the GitHub Actions job summary.

The current executable test distribution is:

| Test Type | Test Methods | Percentage of Test Suite |
| --- | ---: | ---: |
| Unit tests | 12 | 48.00% |
| Feature tests | 13 | 52.00% |
| Total | 25 | 100% |

Measured by PHP lines in this repository, tests currently represent 23.75 percent of the combined application and test PHP code:

| Code Area | Lines | Percentage |
| --- | ---: | ---: |
| Application PHP | 1,339 | 76.25% |
| Test PHP | 417 | 23.75% |
| Total measured PHP | 1,756 | 100% |

The project currently collects code coverage in CI but does not enforce a minimum coverage threshold. The next quality step is to inspect the first stable CI coverage result and then set a realistic threshold.

## Deployment and Operations

The deployment target is Railway. Deployment configuration lives in `railway.toml`.

```text
Railway app service
    runs Laravel
    executes php artisan migrate --force before deploy
    exposes /api/health for health checks
    connects to a managed MySQL database
```

The health check path is `/api/health`, which returns a small JSON response and does not depend on database access.

Operational configuration is documented in `docs/DEPLOYMENT.md`, including required Railway variables, migration behavior, logging recommendations, rollback notes, and production readiness gaps.

## Project Metrics

The following figures describe the repository at the time this overview was drafted.

| Metric | Value |
| --- | --- |
| Application PHP files | 34 |
| Lines of application PHP | 1,339 |
| Test files | 7 |
| Lines of test PHP | 417 |
| Automated test methods | 25 |
| Unit test methods | 12, 48.00% of test suite |
| Feature test methods | 13, 52.00% of test suite |
| Test PHP share | 23.75% of measured PHP code |
| API route declarations | 40 |
| Runtime package entries | 5 |
| Development package entries | 7 |

These metrics describe the codebase itself. They will change as new policies, resources, tests, and deployment hardening are added.

## Current Known Limitations

The project is a strong portfolio backend, but it is not finished as a production system.

Known limitations:

- Blog and comment authorization should be moved into Laravel policies.
- Admin authorization should be moved into policies or dedicated authorization classes.
- First-admin setup should be implemented through a command, seed, or documented deployment workflow.
- API responses should be normalized through Laravel API resources.
- Existing deployed databases may need a backfill for `user_id`, `slug`, `status`, and `published_at`.
- Previously deployed databases should be checked to confirm that blog content uses text storage.
- Category and tag endpoint tests should be added.
- Non-owner comment moderation tests should be added.
- CORS and authentication rate-limit behavior should be tested.
- Production monitoring, alerting, backup restore testing, and rollback drills are not complete.

## Where to Look for More Detail

The documentation set includes:

- `README.md` for setup, features, and repository orientation.
- `docs/API.md` for API contracts.
- `docs/DEPLOYMENT.md` for Railway deployment and operations.
- `docs/ENGINEERING_READINESS.md` for framework-based readiness assessment.
- `SECURITY.md` for security limitations and roadmap.
- `CHANGELOG.md` for project-level release notes.
