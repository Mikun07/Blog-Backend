# Blog Backend

Blog Backend is a Laravel API for a complete blog platform. It supports authenticated authors, published and draft posts, categories, tags, comments, and owner-only moderation.

The project is a portfolio backend intended to demonstrate backend API design, database modeling, validation, authentication, and deployment readiness.

## Features

- User registration, login, logout, and current user lookup
- Laravel Sanctum bearer token authentication
- Admin role for site-wide management
- Create, publish, archive, update, and delete blog posts
- Public published post listing with search, category, tag, and pagination filters
- Authenticated author dashboard listing for all own posts
- Category creation and listing
- Tag creation and listing
- Guest and authenticated comment submission
- Pending comment workflow
- Owner-only comment approval, rejection, and deletion
- Admin dashboard metrics, user role management, and global blog/comment moderation
- Admin user creation, suspension, management, admin post creation, post inspection, and user history review
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
- `app/Http/Controllers/AdminController.php` handles admin dashboards, role management, and global moderation.
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
CORS_ALLOWED_ORIGINS=http://localhost:3000,http://localhost:5173,http://127.0.0.1:3000,http://127.0.0.1:5173
ADMIN_NAME="Ayomikun Olaleye"
ADMIN_USERNAME=ayomikunolaleye
ADMIN_EMAIL=ayomikunolaleye@gmail.com
ADMIN_PASSWORD=

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

## Admin Account

The first admin account is provisioned by `Database\Seeders\AdminUserSeeder`. Set `ADMIN_PASSWORD` in `.env` or deployment environment variables before seeding. If the password contains special characters such as `#`, wrap it in quotes.

```env
ADMIN_EMAIL=ayomikunolaleye@gmail.com
ADMIN_PASSWORD="your-secure-admin-password"
```

Then run the seed workflow.

```bash
php artisan db:seed
```

## Local Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
```

The API will be available at `http://127.0.0.1:8000/api`.

## Optional Docker Setup

Docker is not required for Railway deployment, but it is available for local development when you want PHP and MySQL isolated from your machine.

```bash
docker compose up --build -d
docker compose exec app php artisan migrate
```

The API will be available at `http://127.0.0.1:8000/api`. The Compose MySQL service is exposed on `127.0.0.1:3307` with database `blog_backend`, username `blog_backend`, and password `blog_backend`.

To seed the first admin user, set `ADMIN_PASSWORD` in `.env` and run:

```bash
docker compose exec app php artisan db:seed
```

Stop the local Docker stack with:

```bash
docker compose down
```

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

API contracts are documented in [docs/API.md](docs/API.md). When the Laravel app is running, the API documentation page is available at `http://127.0.0.1:8000/docs/api`, and the raw OpenAPI JSON is available at `http://127.0.0.1:8000/docs/api/openapi.json`.

## Deployment

Railway deployment notes are documented in [docs/DEPLOYMENT.md](docs/DEPLOYMENT.md). The repository includes `railway.toml` because Railway's current config-as-code system reads `railway.toml` or `railway.json`.

This backend should not be deployed as a Netlify static site. Netlify settings such as `npm run build` with publish directory `dist` are for frontend apps; this Laravel API runs on PHP and should be hosted on Railway or another PHP-capable backend platform.

## Security Notes

Current security considerations are documented in [SECURITY.md](SECURITY.md).

## Engineering Readiness

The project has been filed against the engineering framework in [docs/ENGINEERING_READINESS.md](docs/ENGINEERING_READINESS.md). That report records the current state, known risks, stop rules, and recommended build order.

## Known Limitations

- Authorization should be moved into Laravel policies or gates.
- `ADMIN_PASSWORD` must be configured before seed data can provision the first admin account.
- Existing legacy blog rows may need a backfill migration for `user_id`, `slug`, and publish status.
- Existing deployed databases created before this upgrade may need a manual content column conversion to `text`.
- Production logging, monitoring, backups, and incident handling are not yet complete.

## Future Improvements

- Expand API feature tests for authorization and moderation paths.
- Keep OpenAPI documentation synchronized with API changes.
- Add rate-limit behavior tests for authentication routes.
- Add a first-admin setup command for interactive production provisioning.
- Add author profile endpoints.
- Add rich text or Markdown rendering support.
- Add CI quality gates for PHPUnit and Laravel Pint.

## License

This project is licensed under the [MIT License](LICENSE). Confirm the final copyright owner before publishing publicly.
