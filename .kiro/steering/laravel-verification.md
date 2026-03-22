---
inclusion: always
---

# Laravel Verification Loop

Source: adapted from affaan-m/everything-claude-code (MIT)

Run before PRs, after major refactors, and pre-deploy.

## Phase 1: Environment

```bash
php -v
composer --version
php artisan --version
composer validate
composer dump-autoload -o
```

- Confirm `.env` exists with required keys
- Confirm `APP_DEBUG=false` for production

## Phase 2: Lint & Static Analysis

```bash
vendor/bin/pint --test
vendor/bin/phpstan analyse
```

## Phase 3: Tests & Coverage

```bash
php artisan test
# CI with coverage:
XDEBUG_MODE=coverage php artisan test --coverage
```

Target: 80%+ coverage on unit + feature tests.

## Phase 4: Security

```bash
composer audit
```

## Phase 5: Migrations

```bash
php artisan migrate --pretend
php artisan migrate:status
```

- Review destructive migrations carefully
- Ensure `down()` methods exist and rollbacks are safe

## Phase 6: Build & Deploy Readiness

```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Phase 7: Queue & Scheduler

```bash
php artisan schedule:list
php artisan queue:failed
```

## Quick Verification Report

After running all phases, produce:

```
VERIFICATION REPORT
===================
Lint:        [PASS/FAIL]
Static:      [PASS/FAIL]
Tests:       [PASS/FAIL] (X/Y passed, Z% coverage)
Security:    [PASS/FAIL]
Migrations:  [PASS/FAIL]
Build:       [PASS/FAIL]

Overall: [READY / NOT READY] for PR/deploy
```
