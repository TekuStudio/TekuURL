# TekuURL

**Short Links, Big Brain Energy.**

Premium SaaS link shortening platform with real-time analytics, QR codes, and multi-language support (ES/EN).

> Porque las URLs largas son para los debiles.

## Features

- **Link Shortening** — Custom slugs, auto-generate, title & tags
- **Real-time Analytics** — Clicks over time, countries, devices, browsers, referrers
- **Per-link Stats** — Dive into any link's performance
- **QR Codes** — Auto-generated on creation
- **Link Expiration** — Set expiry dates, auto-deactivate
- **Password-Protected Links** — Lock sensitive URLs
- **Tags & Organization** — Categorize links with custom tags
- **CSV/JSON Export** — Download your data
- **Multi-language** — English & Spanish
- **Admin Panel** — Separate login, user/link management, audit log
- **Plans & Quotas** — Free, Pro, Enterprise tiers
- **Security** — CSRF tokens, rate limiting, session hardening, audit trails

## Tech Stack

- **Backend:** PHP 8.2+
- **Database:** MySQL / MariaDB
- **Frontend:** Vanilla CSS (neo brutalist), JavaScript vanilla
- **Charts:** Chart.js 4
- **QR:** api.qrserver.com (no Composer needed)

## Quick Start

1. Clone to `C:\xampp\htdocs\TekuURL\`
2. Import `schema.sql` and `schema_v2.sql` into MySQL database `tekuurl`
3. Configure `config.php` (DB credentials, Stripe keys — placeholders included)
4. Start Apache + MySQL from XAMPP
5. Visit `http://localhost/TekuURL/`

### Default Accounts

| Role | Email | Password |
|------|-------|----------|
| User | `test@test.com` | `password` |
| Admin | `admin@tekuurl.com` | `admin123` |

### Admin Access

Separate login page: `http://localhost/TekuURL/admin_login.php`

## Project Structure

```
TekuURL/
├── admin/              # Admin panel (separate session)
├── css/                # Neo brutalist design system
├── js/                 # Custom cursor + interactions
├── lang/               # en.php, es.php translations
├── views/              # Layout header/footer, 404
├── uploads/qrcodes/    # Generated QR images
├── config.php          # DB, auth, helpers, security
├── lang.php            # Session init + security headers
├── schema.sql          # Core tables
├── schema_v2.sql       # Extended features
└── *pages*.php         # Dashboard, links, analytics, etc.
```

## License

**All Rights Reserved.** Source code is available for viewing and learning only.
See [LICENSE](LICENSE) for details.

---

Built with ❤️ and cafeína by TuStudio.
