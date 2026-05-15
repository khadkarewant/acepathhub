# Security

## Overview

Quizmania is a Core PHP + MySQL web application hardened to a moderate, production-ready security baseline while preserving the existing architecture. This document covers the threat model, all security controls implemented, the hardening scope and rules, endpoint-level details, known limitations, and vulnerability disclosure guidance.

---

## Threat Model

### In-scope attackers

1. Unauthenticated internet users attempting direct endpoint access
2. CSRF attacks targeting logged-in users/admins
3. SQL injection attempts through request inputs
4. Privilege escalation (horizontal/vertical) via weak authorization checks
5. Stored XSS through database-rendered fields (names, content, chat)
6. Session abuse, including fixation and hijacking attempts
7. Abuse of AJAX endpoints (chat send/delete, admin moderation actions)
8. Direct URL access to internal PHP files (`src/db/`, `src/inc/`, `src/security/`)

### Out of scope

- Server compromise / root access
- Direct database server access
- Network-level MITM attacks (assuming HTTPS is configured correctly)
- Infrastructure-layer DDoS mitigation

---

## Hardening Rules

Every DB-write action (`INSERT`, `UPDATE`, `DELETE`, status change, verification, assignment, cancellation, moderation action) must be:

1. POST-only
2. CSRF-protected with `csrf_verify()`
3. Authentication-checked (logged-in user)
4. Authorization-checked (role / privilege / ownership)
5. Server-side validated
6. Executed with a prepared statement
7. Followed by `header("Location: ..."); exit;` (or JSON response + `exit` for AJAX)

Destructive UI actions must use:

```php
<form method="POST">
  <?= csrf_input(); ?>
  <button type="submit">Action</button>
</form>
```

### Never

- Trigger destructive actions via GET
- Use `window.location.href` or JS redirects for state-changing actions
- Use `PHP_SELF` as a form action
- Include `public_bootstrap.php` in files that already include `src/db/session.php` (causes duplicate session/CSRF init)

---

## Security Controls Implemented

### 1. CSRF Protection

Module: `src/security/csrf.php`

- Token generation: `csrf_token()` using `random_bytes(32)`
- Form helper: `csrf_input()` renders a hidden input field
- Verification: `csrf_verify()` on all POST handlers, uses `hash_equals()` for timing-safe comparison
- AJAX endpoints receive the CSRF token in the request payload and verify server-side

Implementation note: `require_once("src/security/csrf.php")` is used throughout to prevent function redeclaration fatals when multiple includes converge.

### 2. POST-Only State Changes

All state-changing operations were converted from GET to POST + CSRF with safe redirect flow.

Converted endpoints:
`delete-question-pattern.php` · `delete-question-topic.php` · `cancel-purchase-product.php` · `course-status-change.php` · `product-status-change.php` · `assign-product.php` · `reset-user-password.php` · `question_update-query.php` · `mark-read-notification.php`

Updated caller pages:
`product-details.php` · `question-pattern-details.php` · `purchase-history.php` · `courses.php` · `assign-product.php` · `updatable-mcqs.php` · `notification.php` · `user-details.php`

### 3. SQL Injection Mitigation (Prepared Statements)

Critical handlers migrated to prepared statements:

- **Authentication:** `login.php` · `signup.php` · `change-password.php`
- **PIN system:** `set-pin.php` · `change-pin.php`
- **Admin actions:** `add-user.php` · `add-product.php` · `reset-user-password.php`
- **Moderation:** `verify-mcq.php` · `mcq-status-change.php` · `delete-mcq.php` · `admin-chat-reports.php` · `admin-mcq-reports.php`
- **Product/purchase:** `product-status-change.php` · `course-status-change.php` · `assign-product.php` · `cancel-purchase-product.php`
- **Profile updates:** `update-personal-info.php` · `update-contact.php` · `update-address.php`
- **Chat:** `chat-actions.php` (user lookup, access check, message ownership, fetch, delete)
- **Other:** `question_update-query.php` · `mark-read-notification.php` · `learn.php` (product_topics lookup for reset)

Additional protections applied alongside prepared statements: integer casting, allowlists for action/status values, and basic transaction safety where appropriate.

### 4. Authentication and Session Defenses

- Passwords stored with `password_hash()`, verified with `password_verify()`
- PIN storage upgraded from `md5()` to `password_hash()`
- Login throttling via `login_attempts` table
- Session regeneration on login
- Session cookie hardening in `src/db/session.php`:
  - `session.use_strict_mode = 1`
  - `session.use_only_cookies = 1`
  - `cookie_httponly = true`
  - `cookie_samesite = Lax`
  - `cookie_secure` set dynamically based on HTTPS detection
- Database-backed session token with single-device login enforcement
- Invalid token triggers forced logout

### 5. Output Escaping (XSS Mitigation)

Pages rendering user-controlled or DB-controlled data use `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`.

Escaping applied on: `profile.php` · `users.php` · `user-details.php`

Additional sanitization: `admin-mcq-reports.php` strips tags from question output before escaping to prevent stored HTML from rendering.

`PHP_SELF` removed from form actions across profile update handlers.

### 6. Internal File Access Protection

`.htaccess` rules block direct web access to internal PHP files:

```apache
RewriteRule ^src/api/.*\.php$ - [L]
RewriteRule ^src/(?!api/).*\.php$ - [F,L]
```

Allowed: `src/api/*.php` (AJAX endpoints)

Blocked: `src/db/` · `src/inc/` · `src/security/` · all other `src/` PHP files

Additional `.htaccess` protections: directory listing disabled, dotfiles blocked, sensitive file extensions blocked (`.env`, `.sql`, `.log`, `.bak`, etc.), HTTPS enforced via redirect.

### 7. Chat System Hardening

Files hardened: `chat-actions.php` · `discussion.php`

- Raw SQL converted to prepared statements for user lookup, student access check, message ownership, fetch, and delete
- CSRF required for POST send/delete actions
- Delete permissions unified using privilege flags (`$delete_any_chat`, `$delete_own_chat`)
- `reply_to` NULL handling fixed
- Message length capped at 1000 characters
- AJAX delete request updated to send CSRF token from the chat form
- JSON fail handler added for debugging

### 8. Attack Surface Reduction

Unused endpoints disabled with `http_response_code(410); exit('Gone');`:

`a-assign-product.php` · `admin-allowed-user.php` · `block-user-chat.php` · `block-list.php` · `discussion-user-list.php` · `unblock.php`

Associated UI entry points removed or commented.

---

## Bugs Fixed During Hardening

- **Purchase cancellation status mismatch:** DB enum is `active/inactive/expired` but code was setting `cancelled` — fixed to `inactive`
- **Unsafe redirects:** JS redirects (`echo "<script>window.location.href='...'"`) replaced with `header("Location: ..."); exit;`
- **Output buffering misuse:** `ob_start()` removed where it masked output-before-header bugs
- **Notification mark-read:** permission flag mismatch corrected so mark-read works on live
- **Chat delete 403:** CSRF token was missing from AJAX delete payload — fixed by sending token from chat form
- **`learn.php` display bug:** `$product` undefined warning replaced with `$products[0]['course_name'] ?? ''`

---

## Verification Checklist

### UI testing confirmed

- Product live/draft toggle works
- Course live/draft toggle works
- MCQ moderation works (verify, status change, delete)
- Purchase cancellation sets status to `inactive`
- Product assignment works; duplicate transaction numbers rejected
- Chat fetch/send/delete works with CSRF
- Admin reports resolve/delete works via CSRF-protected AJAX
- Notifications mark-read works via POST + CSRF
- MCQ "need upgrade" Yes/No works via POST + CSRF

### Network inspection confirmed

- POST is used for all state-changing actions
- CSRF tokens are included on all state-changing requests, including AJAX
- Remaining GET endpoints are view-only or navigation-only

---

## Known Limitations

This project does not currently implement:

- Content Security Policy (CSP)
- Global rate limiting across all endpoints
- Global security headers (`X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`)
- Centralized validation middleware
- Automated security testing (SAST/DAST)
- Full prepared-statement coverage on all remaining raw queries
- Ownership/access checks on `learn.php` reset product action
- Logout via POST + CSRF

The `learn.php` reset delete query uses an `IN (...)` list built from topic IDs sourced from the database. This is acceptable for the current baseline but should be converted to a fully prepared dynamic placeholder query with explicit ownership checks.

---

## Future Improvements

1. Complete prepared-statement coverage on remaining raw queries
2. Add ownership/access checks on `learn.php` reset action
3. Add global security headers (`nosniff`, `SAMEORIGIN`, `strict-origin-when-cross-origin`; HSTS after full HTTPS confirmation)
4. Roll out CSP in Report-Only mode, fix violations, then enforce
5. Centralize authorization and validation helpers
6. Add security logging around admin actions and repeated login failures
7. Run lightweight scanning (OWASP ZAP baseline) and document findings
8. Convert logout to POST + CSRF

---

## Reporting a Vulnerability

If you discover a security vulnerability:

1. Do **not** open a public issue
2. Report privately with reproduction steps, affected endpoint(s), and impact
3. Include: request sample (method + params), expected vs actual behavior, severity estimate

For responsible disclosure, contact the project maintainer via the repository contact method or listed email, if available.
