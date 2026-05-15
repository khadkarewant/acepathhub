# Quizmania

Quizmania is a **Core PHP + MySQL** learning platform focused on MCQ-based Lok Sewa (public service commission) exam preparation, practice, and product-driven course access.

**Live at:** [quizmania.org](https://quizmania.org)

This repository reflects security hardening work performed on a **live, user-facing application** with 60+ active users and 10,000+ questions, with changes introduced incrementally to preserve production stability.

It also serves as a **backend security hardening case study**, showing how a legacy-style PHP application can be systematically improved to reach a moderate, portfolio-ready baseline without rewriting it into a framework.

---

## Stack

- PHP 8.2 (Core PHP, no framework)
- MySQL
- Apache (cPanel-hosted)
- Server-rendered HTML/CSS/JS
- jQuery, Bootstrap, DataTables

---

## What the Application Does

- User authentication (login, signup, password/PIN management)
- Role-based access control (admin, teacher, agent, data_entry, student, biller)
- MCQ management (create, verify, moderate, draft, report, update)
- Exam engine (practice mode, timed exams, attempt tracking, results, leaderboard)
- Course and product management (create, assign, purchase, cancel)
- User profile management
- In-app discussion/chat system
- Notification system
- Agent and billing workflows
- Admin reporting and moderation tools
- Downloadable resources (syllabi, advertisements)

---

## Repository Structure

```text
quizmania/
├── .htaccess                    # HTTPS redirect, directory protection, internal file blocking
├── .gitignore
│
├── docs/
│   ├── HARDENING.md             # Detailed security hardening notes
│   ├── SECURITY.md              # Security model, threat model, disclosure guidance
│   └── CHANGELOG.md             # Change history for hardening work
│
├── src/
│   ├── api/
│   │   ├── data-check-api.php       # AJAX data validation endpoint
│   │   └── agent-data-check-api.php # Agent-specific AJAX endpoint
│   │
│   ├── css/
│   │   ├── bootstrap.css
│   │   ├── datatable.css
│   │   └── mcq.css
│   │
│   ├── db/
│   │   ├── db_conn.php          # Database connection — reads credentials from a config file outside the web root (gitignored)
│   │   ├── session.php          # Session bootstrap, token validation, single-device enforcement
│   │   └── privileges.php       # Role-based privilege flags
│   │
│   ├── img/                     # UI icons and branding assets (31 images)
│   │
│   ├── inc/
│   │   ├── header.php           # Navigation and layout header
│   │   ├── footer.php           # Page footer
│   │   └── links.php            # CSS/JS includes and meta tags
│   │
│   ├── js/
│   │   ├── jquery.js
│   │   ├── bootstrap.js
│   │   ├── datatable.js
│   │   ├── header.js
│   │   └── mcq.js
│   │
│   ├── link_pdf/                # Exam rules, advertisements, syllabi (PDFs)
│   ├── pdf/                     # Additional downloadable PDFs
│   ├── syllabus/                # Syllabus files
│   │
│   └── security/
│       ├── csrf.php             # CSRF token generation and verification
│       └── public_bootstrap.php # Session + CSRF init for public/unauthenticated pages
│
├── *.php                        # Application pages (113 PHP files in webroot)
└── README.md
```

---

## Page Inventory (113 PHP files)

### Public Pages (no login required)
`index.php` · `about.php` · `contact.php` · `login.php` · `signup.php` · `disclaimer.php` · `faq.php` · `downloads.php` · `payment-policies.php` · `privacy-policies.php` · `t_c.php`

### Authentication and Account
`login.php` · `logout.php` · `signup.php` · `change-password.php` · `set-pin.php` · `change-pin.php`

### Profile
`profile.php` · `update-personal-info.php` · `update-contact.php` · `update-address.php`

### Exam Engine
`exam.php` · `exam-guidelines.php` · `exam-attempt-query.php` · `exam-result.php` · `exam-summery.php` · `exam-history.php` · `practice.php` · `learn.php` · `leaderboard.php`

### MCQ and Content Management (Admin/Teacher/Data Entry)
`add-mcq.php` · `add-question.php` · `add-question-pattern.php` · `add-question-topic.php` · `add-refference-no.php` · `update-mcq.php` · `update-question.php` · `update-question-pattern.php` · `verify-mcq.php` · `mcq-details.php` · `mcq-status-change.php` · `delete-mcq.php` · `delete-question-pattern.php` · `delete-question-topic.php` · `unverified-mcqs.php` · `unverified-questions.php` · `updatable-mcqs.php` · `reported-questions.php` · `question-pattern-details.php` · `question_update-query.php` · `draft-mcqs.php` · `drafted-mcqs-details.php`

### Course and Topic Management
`add-course.php` · `add-subject.php` · `add-topic.php` · `courses.php` · `course.php` · `course-details.php` · `course-status-change.php` · `update-course.php` · `topics.php` · `topic-details.php` · `update-topic.php` · `subsets.php` · `subsets-manage.php` · `ajax-split-subsets.php`

### Product and Purchase
`add-product.php` · `products.php` · `product-details.php` · `product-status-change.php` · `update-product.php` · `assign-product.php` · `my-products.php` · `purchase-history.php` · `cancel-purchase-product.php`

### Billing and Agent
`sales-bill.php` · `add-credit.php` · `purchase-stats.php` · `agent-stat.php` · `agent-txn.php`

### User Management (Admin)
`add-user.php` · `users.php` · `user-details.php` · `user-stats.php` · `students.php` · `agents.php` · `reset-user-password.php`

### Chat and Discussion
`discussion.php` · `chat-actions.php` · `chat-report.php`

### Notifications
`notification.php` · `mark-read-notification.php`

### Admin Reports and Moderation
`admin-chat-reports.php` · `admin-mcq-reports.php` · `report-mcq.php`

### Dashboard and Utilities
`home.php` · `test-dashboard.php` · `admin-allowed-test-user.php`

### Shared Includes (inside `src/`)
`db_conn.php` · `session.php` · `privileges.php` · `csrf.php` · `public_bootstrap.php` · `header.php` · `footer.php` · `links.php`

### Disabled Modules (HTTP 410 Gone)
`a-assign-product.php` · `admin-allowed-user.php` · `block-user-chat.php` · `block-list.php` · `discussion-user-list.php` · `unblock.php`

---

## Architecture

### Request Flow

Every authenticated page follows this include chain:

```
src/db/db_conn.php      → database connection (mysqli)
src/db/session.php       → session config, login check, token validation, CSRF init
src/db/privileges.php    → role-based permission flags
src/inc/links.php        → CSS/JS assets, meta tags
src/inc/header.php       → navigation bar
[page content]
src/inc/footer.php       → footer
```

Public pages (login, signup) use `src/security/public_bootstrap.php` instead of `session.php` to initialize sessions and CSRF without requiring authentication.

### Authorization Model

Role-based access is handled by `privileges.php`, which sets boolean string flags (`"true"` / `"false"`) based on `$type` (user role). Six roles are defined: `admin`, `teacher`, `agent`, `data_entry`, `student`, `biller`. Individual pages check these flags before rendering content or processing actions.

### CSRF Protection

Provided by `src/security/csrf.php`:
- Token generation: `csrf_token()` using `random_bytes(32)`
- Form helper: `csrf_input()` renders a hidden input
- Verification: `csrf_verify()` on all POST handlers, uses `hash_equals()` for timing-safe comparison

### Session Security

Configured in `src/db/session.php`:
- `session.use_strict_mode = 1`
- `session.use_only_cookies = 1`
- `cookie_httponly = true`, `cookie_samesite = Lax`, `cookie_secure` set dynamically based on HTTPS detection
- Database-backed session token with single-device login enforcement
- Invalid token triggers forced logout

---

## Security Hardening Summary

This project was hardened with a production-aware security mindset. Key improvements include:

- **POST-only destructive actions** — removed all state-changing GET flows
- **CSRF protection** — applied to all forms and AJAX endpoints
- **Prepared statements** — added across critical auth, admin, moderation, purchase, and chat handlers
- **Session hardening** — strict cookie configuration, database token validation, single-device enforcement
- **Authentication hardening** — `password_hash()`/`password_verify()`, login throttling via `login_attempts` table, session regeneration on login
- **PIN security** — upgraded from `md5()` to `password_hash()`
- **XSS mitigation** — `htmlspecialchars()` output escaping on key pages rendering user-controlled data
- **Internal file access protection** — `.htaccess` rules block direct access to `src/db/`, `src/inc/`, `src/security/` while allowing `src/api/`
- **Attack surface reduction** — unused legacy endpoints disabled with `410 Gone`
- **Safe redirects** — removed JavaScript redirects, replaced with `header("Location: ..."); exit;`
- **Chat system hardening** — prepared statements, CSRF on send/delete, message length cap, permission-based delete

Detailed documentation: `docs/HARDENING.md`, `docs/SECURITY.md`, `docs/CHANGELOG.md`

---

## Known Limitations

This hardening does not currently implement:

- Content Security Policy (CSP)
- Global rate limiting across all endpoints
- Global security headers (`X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`)
- Centralized validation middleware
- Automated security testing (SAST/DAST)
- Full prepared-statement coverage on all remaining raw queries

---

## Why This Project Matters

This is not a framework demo. It is a **manual hardening exercise on a legacy Core PHP codebase serving real users**.

That means every protection was understood and implemented by hand: CSRF defenses, prepared statements, authorization checks, session protections, AJAX endpoint safety, output escaping, and infrastructure-level file blocking — none of which are automatically provided by a framework.

---

## Local Setup

### Requirements

- PHP 8.2+
- MySQL
- Apache with `mod_rewrite` enabled

### Database Configuration

This repository does **not** include real credentials.

In production, `src/db/db_conn.php` reads credentials from a config file located **outside the web root** (above `public_html`), so it is never web-accessible. This file is not part of the repository.

`src/db/db_conn.php` is gitignored — each environment (local, production) maintains its own copy.

For local setup, create your own `src/db/db_conn.php`. See `db_example.php` in the repo root for the expected config format:

```php
<?php
return [
    'host' => 'localhost',
    'user' => 'your_database_user',
    'pass' => 'your_database_password',
    'name' => 'your_database_name',
];
```

### Important Security Note

Do not commit: real database credentials, `.env` files, private config files, logs, or deployment-specific verification files.

---

## Documentation

- **Hardening details:** `docs/HARDENING.md`
- **Security model and disclosure guidance:** `docs/SECURITY.md`
- **Change history:** `docs/CHANGELOG.md`

---

## Author

**Rewant Khadka**

GitHub: [khadkarewant](https://github.com/khadkarewant)
