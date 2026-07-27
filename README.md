# TekuURL

**Short Links, Big Brain Energy.**

Premium SaaS link shortening platform with real-time analytics, QR codes, and multi-language support (ES/EN).

> Porque las URLs largas son para los debiles.

![TekuURL](https://i.imgur.com/mthjLNT.png)
![Dashboard](https://i.imgur.com/Cuy4ycl.png)

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

1. `git clone https://github.com/TekuStudio/TekuURL.git C:\xampp\htdocs\TekuURL`
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

## Deployment

### Shared Hosting
1. Upload all files to `public_html` via FTP
2. Create MySQL database and import `schema.sql` + `schema_v2.sql`
3. Update `config.php` with production DB credentials and `BASE_URL`
4. Set `RewriteBase /` in `.htaccess` if deploying to root
5. Ensure PHP 8.2+ and `mod_rewrite` are enabled

### VPS / Dedicated
1. Point domain to server, install Apache/Nginx + PHP 8.2+ + MySQL
2. Clone repo and configure virtual host to `public/`
3. Run database migrations
4. Set up SSL with Certbot (HTTPS required for redirects)
5. Configure cron for cleanup of expired links if needed

## License

**All Rights Reserved.** Source code is available for viewing and learning only.
See [LICENSE](LICENSE) for details.

---

Built with ❤️ and cafeína by TekuStudio.
