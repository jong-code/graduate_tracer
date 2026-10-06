# Security and production deployment

## Required production settings

Use these values in the server's `.env` file. Never upload or commit that
file, and never reuse the development `APP_KEY` in another environment.

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://graduatetracer.nemsulc.site
LOG_LEVEL=warning
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax
SESSION_ENCRYPT=true
FILESYSTEM_LOCAL_SERVE=false
```

The web server document root must point to this project's `public/`
directory, not the project root. This is urgent: the October 5, 2026 review
confirmed public `200` responses for `composer.json`, `composer.lock`,
`vendor/composer/installed.json`, and `storage/logs/laravel.log`. The root
`.htaccess` blocks these as defense in depth, but it is not a substitute for
the correct document root. Disable PHP version disclosure (`expose_php=Off`)
in Hostinger's PHP configuration.

After every deployment run:

```sh
composer install --no-dev --prefer-dist --optimize-autoloader
php artisan optimize:clear
php artisan optimize
php artisan migrate --force
composer audit --locked --no-dev
```

## Deployment exclusions

Do not upload `.env`, `.git`, tests, database exports, logs, editor files,
OneDrive conflict copies (`*-LAPTOP-*`), or development dependencies. The
repository currently contains local OneDrive conflict copies; remove them
from the deployment package after confirming they are not needed.

## Operational controls

- Use unique admin accounts and long generated passwords. Enable MFA at the
  hosting panel and email provider even though application MFA is not yet
  implemented.
- Review audit logs and failed-login/rate-limit events regularly.
- Back up the database encrypted, restrict access to the backup, and test a
  restore at least quarterly.
- Rotate `APP_KEY` only with a planned migration: encrypted database fields
  depend on it. Keep old keys temporarily in `APP_PREVIOUS_KEYS` during a
  controlled rotation.
- Re-run Composer and npm audits before each release and at least monthly.
