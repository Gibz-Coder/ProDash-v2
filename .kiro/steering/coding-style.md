---
inclusion: always
---

# Coding Style

Source: adapted from affaan-m/everything-claude-code (MIT)

## PHP Conventions

- `declare(strict_types=1)` in ALL PHP files
- Use typed properties and return types everywhere
- Prefer `final` classes for services, actions, and policies
- No `dd()`, `dump()`, or `var_dump()` in committed code
- Formatting via Laravel Pint (PSR-12), max line length: 120 characters
- No emojis in code or comments

## Immutability

ALWAYS create new objects, NEVER mutate existing ones:

- Avoid mutating model instances in services; prefer create/update through repositories or query builders
- Immutable data prevents hidden side effects and makes debugging easier

## File Organization

- High cohesion, low coupling
- 200–400 lines typical, 800 max per file
- Extract utilities from large modules
- Organize by feature/domain, not by type

## Error Handling

- Handle errors explicitly at every level
- Throw domain exceptions in services
- Map exceptions to HTTP responses in `bootstrap/app.php` via `withExceptions`
- Never expose internal errors to clients
- Never silently swallow errors

## Input Validation

- Validate ALL user input at system boundaries using Form Requests
- Transform input to DTOs for business logic
- Never trust request payloads for derived fields
- Fail fast with clear error messages

## Code Quality Checklist

Before marking work complete:

- [ ] Code is readable and well-named
- [ ] Functions are small (<50 lines)
- [ ] Files are focused (<800 lines)
- [ ] No deep nesting (>4 levels)
- [ ] Proper error handling
- [ ] No hardcoded values (use constants or config)
- [ ] No `dd()` / `dump()` left in code
