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

- PHP 8.1+ with the `sodium` and `pdo_mysql` extensions
- MySQL 5.7+ / MariaDB 10.3+
- [Composer](https://getcomposer.org)
- Apache or Nginx with mod_rewrite (optional)
- GD or fileinfo extension for uploads

## Installation

1. **Upload files** to your server (e.g. `/public_html/poomconnect/` or MAMP `htdocs/poomconnect/`)

2. **Install dependencies**
   ```bash
   composer install --no-dev --optimize-autoloader
   ```

3. **Create MySQL database**
   ```sql
   CREATE DATABASE poomconnect CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

4. **Import base schema**
   ```bash
   mysql -u root -p poomconnect < database.sql
   ```

5. **Configure environment**: copy `.env.example` to `.env` and fill in your DB credentials and a generated `APP_ENCRYPTION_KEY` (`php -r "echo base64_encode(random_bytes(32));"`) — this key encrypts payment gateway secrets at rest, so keep it safe and don't lose it.

6. **Apply the rest of the schema** (payment settings, rate limiting, and other tables managed outside `database.sql`):
   ```bash
   php migrate.php
   ```

7. **Run seed script once** (creates demo users, organization, and 3 events):
   ```
   https://yourdomain.com/seed.php
   ```

8. **Delete `seed.php`** after setup for security

9. **Set upload folder permissions** (writable by web server):
   ```bash
   chmod -R 755 uploads/
   ```

10. **Login** with demo credentials, then change the passwords immediately (these are documented publicly in this README):
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
3. Run `composer install`
4. Copy `.env.example` to `.env`; default DB credentials are `root` / `root`, host `localhost`
5. Import `database.sql` via phpMyAdmin or CLI, then run `php migrate.php`
6. Visit `http://localhost:8888/poomconnect/seed.php`
7. Open `http://localhost:8888/poomconnect/`

## Security Notes

- All database queries use PDO prepared statements
- Passwords hashed with `password_hash()` / verified with `password_verify()`
- Output escaped with `htmlspecialchars()`
- Upload validation: JPG, PNG, WEBP only, max 5MB
- CSRF tokens required on all state-changing POST requests (see `includes/security.php`)
- Rate limiting on login, signup, registration, and payment slip upload
- Payment gateway secrets (Stripe/Omise/PayPal/2C2P keys) encrypted at rest with `APP_ENCRYPTION_KEY`
- Schema changes are applied via `php migrate.php`, not automatically on every request
- Delete `seed.php` after initial setup
- Do not expose database errors to users in production

## Payments

PromptPay QR and bank transfer (manual, slip upload + admin approval) work out of the box. Stripe Checkout is also wired up for real card payments:

1. In `admin/payment-settings.php`, enable **Stripe**, add your publishable/secret keys, and set it as the default method.
2. Create a webhook endpoint in your Stripe dashboard pointing at `https://yourdomain.com/api/stripe-webhook.php`, listening for `checkout.session.completed`, and paste the resulting webhook signing secret into the same settings page.
3. Locally, use the [Stripe CLI](https://stripe.com/docs/stripe-cli) to forward events: `stripe listen --forward-to http://localhost:8888/poomconnect/api/stripe-webhook.php` (it prints a `whsec_...` secret to use for step 2).
4. Complete a checkout with a [Stripe test card](https://stripe.com/docs/testing) — the payment approves and a ticket issues automatically once the webhook fires.

Omise, PayPal, and 2C2P have settings UI but are not yet wired to real charges.

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
