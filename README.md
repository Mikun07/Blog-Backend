# Blog Backend

Blog Backend is a Laravel API for a complete blog platform. It supports authenticated authors, published and draft posts, categories, tags, comments, and owner-only moderation.

The project is a portfolio backend intended to demonstrate backend API design, database modeling, validation, authentication, and deployment readiness.

## Features

- User registration, login, logout, and current user lookup
- Laravel Sanctum bearer token authentication
- Create, publish, archive, update, and delete blog posts
- Public published post listing with search, category, tag, and pagination filters
- Authenticated author dashboard listing for all own posts
- Category creation and listing
- Tag creation and listing
- Guest and authenticated comment submission
- Pending comment workflow
- Owner-only comment approval, rejection, and deletion
- Legacy endpoint compatibility for the original route names

## Technology Stack

- PHP 8.1 or newer
- Laravel 10
- Laravel Sanctum for API tokens
- MySQL for local and deployed application data
- PHPUnit for tests
- Vite for Laravel asset compilation
- Railway deployment configuration through `railway.toml`

## Architecture Overview

The application follows Laravel's MVC structure with Form Request validation.

- `routes/api.php` defines public, authenticated, and legacy API routes.
- `app/Http/Controllers/UserController.php` handles authentication.
- `app/Http/Controllers/BlogController.php` handles blog publishing, filtering, comments, and moderation.
- `app/Http/Controllers/CategoryController.php` handles category listing and creation.
- `app/Http/Controllers/TagController.php` handles tag listing and creation.
- `app/Http/Requests` contains request validation rules.
- `app/Models/User.php` represents application users.
- `app/Models/Blogs.php` represents blog posts.
- `app/Models/Category.php`, `app/Models/Tag.php`, and `app/Models/Comment.php` represent supporting blog domain concepts.
- `database/migrations` defines the application schema.

The current design is a modular Laravel backend. Validation is separated into Form Request classes, while authorization checks are still handled in controllers. A future design pass should move ownership rules into Laravel policies.

## Repository Structure

```text
app/
  Http/Controllers/      API controllers
  Http/Requests/         Request validation rules
  Models/                Eloquent models
config/                  Laravel configuration
database/
  factories/             Test factories
  migrations/            Versioned database schema
  seeders/               Seed entry point
docs/                    Project engineering documentation
routes/
  api.php                API route definitions
tests/                   PHPUnit test suites
```

## Requirements

- PHP 8.1 or newer
- Composer
- MySQL 8 or compatible database
- Node.js and npm, only needed when building frontend assets

## Environment Variables

Create `.env` from `.env.example` and update these values.

```env
APP_NAME="Blog Backend"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8000
CORS_ALLOWED_ORIGINS=http://localhost:3000,http://localhost:5173

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=blog_backend
DB_USERNAME=root
DB_PASSWORD=

LOG_CHANNEL=stack
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
```

Generate a local application key after creating `.env`.

```bash
php artisan key:generate
```

Never commit `.env`, database passwords, app keys, API tokens, or production credentials.

## Local Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
```

The API will be available at `http://127.0.0.1:8000/api`.

## Testing

Run the PHPUnit test suite.

```bash
vendor/bin/phpunit
```

Composer scripts are also available:

```bash
composer test
composer test:coverage
```

The test suite includes unit tests for model ownership and validation rules, plus feature tests for registration, publishing, owner-only edit and delete, comment moderation, guest comment validation, and invalid blog payloads.

Coverage is generated in CI on PHP 8.3 and uploaded as a `coverage.xml` artifact.

## API Documentation

API contracts are documented in [docs/API.md](docs/API.md).

## Deployment

Railway deployment notes are documented in [docs/DEPLOYMENT.md](docs/DEPLOYMENT.md). The repository includes `railway.toml` because Railway's current config-as-code system reads `railway.toml` or `railway.json`.

## Security Notes

Current security considerations are documented in [SECURITY.md](SECURITY.md).

## Engineering Readiness

The project has been filed against the engineering framework in [docs/ENGINEERING_READINESS.md](docs/ENGINEERING_READINESS.md). That report records the current state, known risks, stop rules, and recommended build order.

## Known Limitations

- Authorization should be moved into Laravel policies or gates.
- Existing legacy blog rows may need a backfill migration for `user_id`, `slug`, and publish status.
- Existing deployed databases created before this upgrade may need a manual content column conversion to `text`.
- Production logging, monitoring, backups, and incident handling are not yet complete.

## Future Improvements

- Expand API feature tests for authorization and moderation paths.
- Add OpenAPI documentation.
- Add rate-limit behavior tests for authentication routes.
- Add author profile endpoints.
- Add rich text or Markdown rendering support.
- Add CI quality gates for PHPUnit and Laravel Pint.

## License

This project is licensed under the [MIT License](LICENSE). Confirm the final copyright owner before publishing publicly.
