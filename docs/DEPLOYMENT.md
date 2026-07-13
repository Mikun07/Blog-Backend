# Deployment Documentation

## Deployment Target

The current deployment target is Railway.

Railway's current config-as-code system reads `railway.toml` or `railway.json` by default. This repository uses `railway.toml` for deployment configuration.

## Netlify Is Not The Backend Host

Do not deploy this Laravel API backend as a Netlify static site. Netlify's default frontend settings often use:

```text
Build command: npm run build
Publish directory: dist
```

That configuration fails for this repository because Laravel Vite builds frontend assets into `public/build`, not `dist`. Even if the publish directory is changed, Netlify static hosting will not run the PHP Laravel API or serve `/api/*` routes.

Use Railway for this backend. If a separate frontend is hosted on Netlify, configure Netlify from the frontend repository instead, and point that frontend's API base URL to the Railway backend URL.

## Deployment Architecture

Recommended Railway services:

- App service: runs the Laravel API.
- MySQL database service: stores users and blog posts.

The project does not currently require queue workers or scheduled jobs because `QUEUE_CONNECTION=sync` and no scheduled tasks are defined.

## Required Railway Variables

Set these variables in Railway.

```env
APP_NAME="Blog Backend"
APP_ENV=production
APP_KEY=base64:replace-with-generated-key
APP_DEBUG=false
APP_URL=https://replace-with-railway-domain
CORS_ALLOWED_ORIGINS=https://replace-with-frontend-domain
ADMIN_NAME="Ayomikun Olaleye"
ADMIN_USERNAME=ayomikunolaleye
ADMIN_EMAIL=ayomikunolaleye@gmail.com
ADMIN_PASSWORD=replace-with-secure-admin-password

DB_CONNECTION=mysql
DB_HOST=replace-with-railway-mysql-host
DB_PORT=3306
DB_DATABASE=replace-with-database
DB_USERNAME=replace-with-username
DB_PASSWORD=replace-with-password

LOG_CHANNEL=stderr
LOG_LEVEL=warning
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
```

Generate `APP_KEY` locally with:

```bash
php artisan key:generate --show
```

Do not commit the generated value.

`ADMIN_PASSWORD` is required if the admin seed workflow is used. Wrap values that contain special characters such as `#` in quotes.

## Build Strategy

Railway should detect the Laravel application and build it with Railpack. The repository includes:

```toml
[build]
builder = "RAILPACK"
```

No custom `startCommand` is set so Railway can use its Laravel runtime defaults.

The Docker files in this repository are for optional local development only. The current Railway deployment path should continue to use Railpack rather than a Docker image unless the deployment architecture changes.

## Uploaded Media Storage

Post cover image uploads currently use Laravel's `public` filesystem disk and are exposed through `/storage/...` URLs. For local development, run:

```bash
php artisan storage:link
```

Before relying on uploads in production, configure durable storage such as object storage or a persistent platform volume. The default application filesystem should not be treated as durable production media storage on ephemeral hosts.

## Migration Strategy

The `railway.toml` file runs migrations before each deploy:

```toml
preDeployCommand = "php artisan migrate --force"
```

This is acceptable for the current small project. For larger releases, review migrations before deployment and avoid destructive schema changes without backups.

## Health Check

The health check path is:

```text
/api/health
```

The endpoint returns a small JSON response and does not depend on database access.

## Logging

For Railway, use stderr logging:

```env
LOG_CHANNEL=stderr
LOG_LEVEL=warning
```

Application logs should not depend on persistent local disk storage in production.

## Rollback Strategy

Rollback should use Railway deployment history.

Before rolling back:

- Check whether the failed deployment ran database migrations.
- Confirm whether rollback requires a database restore or forward fix.
- Review application logs for the failure cause.

## Deployment Readiness Status

Current status: suitable for portfolio deployment after environment variables are configured and database migrations are reviewed.

Blockers:

- No production monitoring or alerting has been defined.
- Database backup and restore procedure has not been tested.
- Existing data may need a one-time backfill for post slugs, ownership, and publish status.
- Uploaded media storage needs a durable production strategy before image uploads are treated as permanent content.

The application can be deployed for portfolio demonstration after these risks are accepted and documented.

## Sources

- Railway Config as Code reference: https://docs.railway.com/config-as-code/reference
- Railway Laravel deployment guide: https://docs.railway.com/guides/laravel
