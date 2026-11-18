# 🔐 Secure Login System

A hardened PHP/MySQL authentication flow with hashed passwords, session management, and brute-force protection.

## Features

| Feature | Detail |
|---|---|
| Password hashing | bcrypt, cost 12, auto-rehash on cost change |
| Brute-force protection | 5 attempts → 15-min lockout (by username + IP) |
| CSRF protection | Per-form token, rotated after each use |
| Session hardening | Regenerate on login, idle timeout, IP binding |
| Remember-me | Random token, SHA-256 stored, rotated on use |
| Security headers | CSP, X-Frame-Options, nosniff, Referrer-Policy |
| Input validation | Server-side; HTML5 on client for UX only |
| Password policy | Min 8 chars, upper, lower, digit, special |

## Setup

### 1. Import the database
```bash
mysql -u root -p < config/database.sql
```

### 2. Configure
Edit `config/config.php` or set environment variables:
```
DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS
APP_ENV=production
BASE_URL=https://yourdomain.com/secure-login/public
```

### 3. Point your web server
Point document root to `/secure-login/public` (or configure a virtual host).  
For XAMPP/WAMP, place the whole folder in `htdocs/`.

### 4. HTTPS
Uncomment the HTTPS redirect in `public/.htaccess` on a live server.

## Project Structure

```
secure-login/
├── assets/
│   ├── css/style.css
│   └── js/app.js
├── config/
│   ├── config.php        ← app settings
│   └── database.sql      ← schema
├── public/               ← web root
│   ├── bootstrap.php     ← autoloader, headers, session
│   ├── index.php
│   ├── login.php
│   ├── register.php
│   ├── dashboard.php
│   ├── logout.php
│   ├── forgot.php
│   └── .htaccess
└── src/
    ├── Auth/Auth.php       ← auth logic
    ├── DB/Database.php     ← PDO singleton
    └── Middleware/
        ├── CSRF.php
        └── Session.php
```

## Security notes

- Never commit real credentials — use environment variables in production.
- Enable HTTPS before deploying; cookies are marked `Secure`.
- The forgot-password flow logs the token for local dev; wire up an SMTP mailer for production.
- Consider adding rate limiting at the web-server level (nginx limit_req / Apache mod_ratelimit) as a second layer.
