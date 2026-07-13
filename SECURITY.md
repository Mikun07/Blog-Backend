# Security Policy

## Supported Status

This project is currently a portfolio backend. It has token authentication, request validation, ownership checks, admin-only routes, and restricted CORS configuration, but it still needs additional hardening before handling sensitive production traffic.

## Current Security Limitations

- Authorization decisions are implemented in controllers rather than policies.
- Admin access depends on the `role` column and should be backed by a controlled first-admin setup process.
- Existing legacy blog rows may not have `user_id`, `slug`, `status`, or `published_at` populated until backfilled.
- Blog ownership keeps the legacy `author` column for compatibility while also adding `user_id`.
- Existing deployed databases created before this upgrade may need a manual content column conversion to `text`.
- Authentication routes use an explicit rate limit, but production thresholds should be reviewed after traffic patterns are known.

## Secret Handling

Never commit:

- `.env` files
- `APP_KEY`
- database credentials
- API tokens
- private certificates
- production URLs that expose private infrastructure

The previous `app.yaml` contained an application key. If that value was ever used outside local testing, rotate it in the target environment.

## Recommended Security Roadmap

1. Move blog, comment, and admin authorization into Laravel policies.
2. Add a controlled first-admin setup command or seed workflow.
3. Add a data backfill migration for existing blog rows.
4. Add a deployment migration plan for databases that already ran the old blog schema.
5. Add automated tests for unauthorized access, invalid payloads, rate limiting, and owner-only operations.
6. Add dependency scanning to CI.

## Reporting Security Issues

For a private portfolio project, report security issues directly to the maintainer. Do not publish exploit details publicly until the issue is reviewed.
