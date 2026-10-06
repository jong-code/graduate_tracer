# Graduate Tracer security review — October 5, 2026

Target: `https://graduatetracer.nemsulc.site/`. Scope: public read-only HTTP
requests, TLS handshakes, local Laravel source review, dependency registry
audits, and isolated local regression tests. No production accounts were
created, no passwords were guessed, and no production records were changed.
The production application log contents were not inspected or retained.

## Findings and evidence

| Priority | Finding | Evidence and impact | Remediation status |
| --- | --- | --- | --- |
| High; potentially critical depending on log contents | Project files exposed through the public web server | Unauthenticated GET and HEAD requests returned `200` for `/composer.json`, `/composer.lock`, `/vendor/composer/installed.json`, and `/storage/logs/laravel.log`. The metadata endpoint returned real installed-package JSON. The log endpoint returned `text/plain`; its contents were intentionally not read. Public logs may disclose personal data, stack traces, paths, or credentials, depending on what the application logs. | Root and public Apache deny rules added locally. Hosting document root must be changed to `public/`; local changes have not been deployed. |
| High | Known vulnerable PHP dependencies on the deployed server | Public installed-package metadata lists Laravel `12.64.0`, Guzzle `7.14.2`, CommonMark `2.8.3`, and Flysystem `3.35.2`. Composer audit reported 19 advisories across these four packages. These include XSS, parser resource-exhaustion, URL/cookie scope, and path-normalization issues. Advisory severity does not establish that every issue is reachable in this application. | Installed local patches: Laravel `12.69.3`, Guzzle `7.15.5`, CommonMark `2.10.3`, Flysystem `3.36.0`. Updated lockfile audit reports no advisories. Production deployment required. |
| High package severity; application reachability unconfirmed | Vulnerable DOCX XML dependency | The local `docx-export` npm audit flagged `@xmldom/xmldom` for XML injection and resource-exhaustion advisories. A controlled DOCX template is used; an exploit through a public application route was not demonstrated. | Patched locally using `npm audit fix --ignore-scripts`; audit now reports zero vulnerabilities. Deploy updated `docx-export/package-lock.json` and install its dependencies. |
| Medium | Unrestricted survey checkbox values and repeat counts | Source accepted arbitrary elements in checkbox arrays and repeat groups without maximum counts. A verified graduate could submit unexpected choice keys or excessive rows, corrupting reports and increasing processing work. | Added choice allowlists, duplicate rejection, repeat limits, and bounded free-text lengths. Invalid choice/oversized-row tests pass. |
| Medium | Sensitive authenticated responses lack explicit no-store policy | Existing middleware did not forbid retention of survey, map, reward, and contact responses. This was verified in source; authenticated production response headers were not tested. | Added `Cache-Control: no-store, private` and `Pragma: no-cache` for authenticated responses. Regression test passes. |
| Low / hardening | Missing transport/browser restrictions | Live login response omitted HSTS and Permissions-Policy. Its CSP was only `upgrade-insecure-requests`. The response disclosed `PHP/8.3.33`. | Added HSTS for HTTPS, feature restrictions preserving first-party geolocation, and a CSP allowing the application's current CDN assets. Set `expose_php=Off` in Hostinger. Check whether Hostinger overrides CSP after deployment. |
| Low / hardening | CDN assets loaded without integrity verification | Bootstrap and Leaflet script/style tags lacked integrity attributes in source. | Added integrity hashes and `crossorigin="anonymous"` for pinned assets. CSP retains inline scripts/styles for compatibility; removing inline-script allowances requires a separate nonce/refactor change. |
| Low / residual privacy issue | Registration discloses account existence/status | Registration previously distinguished verified and unverified existing accounts; failed logins skipped password hashing when an email was absent. | Verification-status messaging unified; missing-account login now performs a bcrypt check. Registration still rejects an existing email distinctly from successful registration, so complete account-enumeration prevention is not claimed. |

Primary advisory references from the audit:

- Guzzle host canonicalization: https://github.com/advisories/GHSA-v5mv-p594-2x33
- Laravel debug-page XSS: https://github.com/advisories/GHSA-jh5r-qr3c-85q8
- CommonMark parser resource exhaustion: https://github.com/advisories/GHSA-3q6v-r5mr-hxv8
- Flysystem path normalization: https://github.com/advisories/GHSA-cxf4-7mrp-vvpr
- xmldom XML injection: https://github.com/advisories/GHSA-6gmq-8vp8-gcm6

## Protections verified

- HTTP redirects to HTTPS (`301`). HTTPS certificate validation succeeded
  through curl's normal validation. A separate handshake negotiated TLS 1.3;
  a TLS 1.0 attempt was rejected by the client/server negotiation. This does
  not constitute a complete server cipher-suite inventory.
- Certificate: Let's Encrypt, subject `graduatetracer.nemsulc.site`, expiry
  November 11, 2026, 00:50:30 UTC. Monitor automatic renewal.
- Session cookie: Secure, HttpOnly, SameSite=Lax. The XSRF cookie is Secure
  and SameSite=Lax; its lack of HttpOnly is expected for browser CSRF tooling.
- Live `/.env` and `/.git/config` requests returned `403`.
- Unauthenticated `/admin/users` and `/admin/map/locations` requests
  redirected to login. Local tests reject graduate requests for staff
  records and exports with `403`.
- TRACE, PUT, and DELETE on `/login` returned `405`.
- Source uses role middleware, CSRF-protected web routes, password hashing,
  authenticated-user IDs for graduate survey access, parameterized queries,
  escaped Blade output, signed verification links, and login throttling.
- Local password-reset tests validate expiration, wrong-token rejection,
  single use, remember-token rotation, resend cooldown, and rejection of
  sessions containing the old password hash.

## Deployment actions, in order

1. Immediately deny public access to the exposed project directories and
   files. Set the domain document root to the Laravel `public/` directory.
   If the hosting panel cannot do this, place application files outside
   `public_html` and expose only the contents of `public/`, updating the
   front controller's paths to the private application directory.
2. Deploy both Apache rule files and the source changes. The root rules are
   defense in depth and must not replace existing custom hosting routing
   without checking how the server reaches `public/index.php`.
3. Review private copies of access/application logs for access to exposed
   files. Restrict those copies. If they contain secrets, rotate the affected
   credentials; use a controlled process for `APP_KEY` because encrypted
   database fields depend on it. Assess personal-data exposure if present.
4. Install the updated Composer lockfile using
   `composer install --no-dev --prefer-dist --optimize-autoloader`. In
   `docx-export`, run `npm ci --omit=dev --ignore-scripts`.
5. Apply the production settings in `SECURITY.md`, including
   `APP_ENV=production`, `APP_DEBUG=false`, Secure cookies, and
   `FILESYSTEM_LOCAL_SERVE=false`. Clear/rebuild Laravel caches. Configure
   PHP `expose_php=Off`. Inspect the actual response CSP after deployment.
6. Repeat status-only checks for the exposed paths; require `403` or `404`.
   Confirm security headers, logins, survey submission, preview/export, map
   markers, reward workflows, and intended faculty access in staging.

## Validation and limits

- Security regression suite: 12 tests, 59 assertions pass after installing
  patched PHP dependencies. Blade templates compile; changed PHP files pass
  syntax checks; Composer configuration is valid; diff whitespace check passes.
- Composer lock audit: no known advisories after update. DOCX npm audit:
  zero vulnerabilities after update. Root frontend npm audit could not run
  because that project has no lockfile; no conclusion about its dependencies
  is made.
- The wider unit suite has five unrelated failures: shared faculty routes,
  graduate survey scope, name formatting (two tests), and name search. Those
  implementations are absent from the active files and appear in local
  OneDrive `*-LAPTOP-*` conflict copies. They were not restored automatically.
  Conflict files are now ignored by Git, but still exist locally and must be
  excluded from deployment archives. Composer warns about their duplicate
  class definitions during optimized autoload generation.
- Private local file serving is disabled because this application does not
  generate temporary local file URLs/uploads.
- The local environment is development (`APP_ENV=local`, `APP_DEBUG=true`).
  This is not evidence that the deployed server has debug enabled.
- No production authenticated account assessment was performed. No claim
  is made about complete IDOR coverage, database permissions, hosting-panel
  access, production MFA, full XSS/SQL injection coverage, backups, or breach
  history. Passing these checks does not establish that the site is secure.
- Fixes are local. Live file exposure and old dependency versions remain
  until the hosting configuration and deployment are updated.
