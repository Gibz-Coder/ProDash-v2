---
inclusion: always
---

# Security Guidelines

Source: adapted from affaan-m/everything-claude-code (MIT)

## Mandatory Checks Before Any Commit

- [ ] No hardcoded secrets (API keys, passwords, tokens)
- [ ] All user inputs validated via Form Requests
- [ ] SQL injection prevention (Eloquent/query builder, no raw SQL unless parameterized)
- [ ] XSS prevention — Blade `{{ }}` escapes by default; use `{!! !!}` only for trusted, sanitized HTML
- [ ] CSRF protection enabled (`VerifyCsrfToken` middleware active)
- [ ] Authentication/authorization verified (policies, gates, `auth:sanctum`)
- [ ] Rate limiting on auth and write endpoints
- [ ] Error messages don't leak sensitive data

## Secret Management

- NEVER hardcode secrets in source code
- ALWAYS use `.env` and config files; never commit `.env`
- Validate required secrets are present at startup
- Rotate any secrets that may have been exposed
- Use encrypted casts for sensitive columns: `'api_token' => 'encrypted'`

## Authentication & Authorization

- API auth via Laravel Sanctum
- Use policies for model-level authorization (`$this->authorize('update', $model)`)
- Enforce `auth:sanctum` middleware on all protected routes
- Prefer short-lived tokens with refresh flows for sensitive data
- Revoke tokens on logout and compromised accounts
- Regenerate sessions on login and privilege changes

## Password Security

- Hash with `Hash::make()`, never store plaintext
- Enforce strong rules: `Password::min(12)->letters()->mixedCase()->numbers()->symbols()`

## Rate Limiting

```php
RateLimiter::for('login', function (Request $request) {
    return [
        Limit::perMinute(5)->by($request->ip()),
        Limit::perMinute(5)->by(strtolower($request->input('email'))),
    ];
});
```

## File Uploads

- Validate MIME type, extension, and size
- Store uploads outside the public path
- Never trust client-provided filenames

## Security Headers

Add via middleware: `Content-Security-Policy`, `Strict-Transport-Security`, `X-Frame-Options: DENY`, `X-Content-Type-Options: nosniff`, `Referrer-Policy: no-referrer`

## CORS

- Restrict `allowed_origins` in `config/cors.php` — no wildcards for authenticated routes

## Logging

- Never log passwords, tokens, or sensitive PII
- Redact sensitive fields in structured logs

## Dependency Security

- Run `composer audit` regularly
- Update promptly on CVEs

## Security Response Protocol

If a security issue is found:

1. STOP immediately
2. Fix CRITICAL issues before continuing
3. Rotate any exposed secrets
4. Review entire codebase for similar issues
