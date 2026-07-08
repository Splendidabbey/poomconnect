# Poom Connect

**Meet. Connect. Belong.**

Production-ready PHP + MySQL website for Poom Connect — an event-based social matching platform.

## Features

- Premium dark landing page with glassmorphism UI
- Event discovery and registration
- PromptPay payment slip upload
- Organizer payment approval and QR ticketing
- Live event control with round pairing
- Admin dashboard
- PDO prepared statements, password hashing, session auth

## Requirements

- PHP 8.1+
- MySQL 5.7+ / MariaDB 10.3+
- Apache or Nginx with mod_rewrite (optional)
- GD or fileinfo extension for uploads

## Installation

1. **Upload files** to your server (e.g. `/public_html/poomconnect/` or MAMP `htdocs/poomconnect/`)

2. **Create MySQL database**
   ```sql
   CREATE DATABASE poomconnect CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

3. **Import schema**
   ```bash
   mysql -u root -p poomconnect < database.sql
   ```

4. **Edit database credentials** in `config/database.php`:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'poomconnect');
   define('DB_USER', 'your_user');
   define('DB_PASS', 'your_password');
   ```

5. **Run seed script once** (creates demo users, organization, and 3 events):
   ```
   https://yourdomain.com/seed.php
   ```

6. **Delete `seed.php`** after setup for security

7. **Set upload folder permissions** (writable by web server):
   ```bash
   chmod -R 755 uploads/
   ```

8. **Login** with demo credentials:
   - **Admin:** admin@poomconnect.com / admin123
   - **Organizer:** organizer@poomconnect.com / organizer123

## Folder Structure

```
/
├── index.php              # Homepage
├── events.php             # Events listing
├── event.php              # Single event
├── register.php           # Participant registration
├── pay.php                # Payment upload
├── ticket.php             # QR ticket
├── login.php / logout.php
├── config/                # Database & app config
├── includes/              # Shared PHP modules
├── assets/                # CSS, JS, images
├── uploads/               # Slips, event covers, logos
├── organizer/             # Organizer dashboard
├── admin/                 # Admin dashboard
└── api/                   # AJAX endpoints
```

## MAMP Local Setup

1. Place project in `/Applications/MAMP/htdocs/poomconnect/`
2. Start MAMP (Apache + MySQL)
3. Default DB credentials in `config/database.php`: `root` / `root`
4. Import `database.sql` via phpMyAdmin or CLI
5. Visit `http://localhost:8888/poomconnect/seed.php`
6. Open `http://localhost:8888/poomconnect/`

## Security Notes

- All database queries use PDO prepared statements
- Passwords hashed with `password_hash()` / verified with `password_verify()`
- Output escaped with `htmlspecialchars()`
- Upload validation: JPG, PNG, WEBP only, max 5MB
- Delete `seed.php` after initial setup
- Do not expose database errors to users in production

## Demo Flow

1. Browse events on homepage or `/events.php`
2. Register for an event → upload payment slip
3. Login as organizer → approve payment in Payments
4. Participant receives QR ticket at `/ticket.php`
5. Organizer checks in participant via QR token
6. Start live event → manage rounds from Live Event page

## License

Proprietary — Poom Connect

## CI/CD (VPS deploy)

Production server: **50.6.250.5**

GitHub Actions deploys automatically on push to `main`. Full setup guide:

**[DEPLOY.md](DEPLOY.md)**

Quick summary:
1. Run `deploy/server-setup.sh` on the VPS once
2. Add GitHub secrets: `VPS_HOST`, `VPS_USER`, `VPS_DEPLOY_PATH`, `VPS_SSH_KEY`
3. Push to `main` → CI lint → rsync to VPS
