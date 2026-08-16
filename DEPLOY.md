# CI/CD — Deploy to aaPanel (50.6.250.5)

Automated deploy via **GitHub Actions + FTPS** on every push to `main`.

Document root: `/www/wwwroot/poomconnect.com` (FTP user is already chrooted there).

## Pipeline overview

| Workflow | Trigger | Action |
|----------|---------|--------|
| `ci.yml` | Push/PR to `main` or `develop` | PHP syntax lint + PHPUnit + structure check |
| `deploy.yml` | Push to `main` (or manual) | Composer install, then FTPS sync |

## 1. GitHub Secrets (required)

In **GitHub → Settings → Secrets and variables → Actions**, add:

| Secret | Value |
|--------|--------|
| `FTP_SERVER` | `50.6.250.5` |
| `FTP_USERNAME` | `poomconnect` |
| `FTP_PASSWORD` | FTP password from aaPanel (do not commit it) |

Direct link: [Add repository secrets](https://github.com/Splendidabbey/poomconnect/settings/secrets/actions)

Never put the FTP password in the repo, workflow file, or chat-committed docs.

## 2. Deploy

Every push to `main` runs lint, then uploads changed files over FTPS.

Manual deploy: **GitHub → Actions → Deploy to production → Run workflow**

The first deploy uploads the full tree (except excluded paths) and may take several minutes. Later deploys only sync changed files.

## 3. What is never overwritten

These stay on the server as-is:

- `.env` (encryption key and production settings)
- `config/database.local.php` (database credentials)
- User uploads under `uploads/slips/`, `uploads/events/`, `uploads/logos/`
- `seed.php` is not uploaded (delete the copy already on the server after initial setup)

## 4. Required once: fix server ownership

Most site files are currently owned by UID `501`, so FTP (user `www`) cannot create folders such as `api/mobile` or overwrite PHP files. That is what caused `550 Can't change directory to mobile`.

In **aaPanel → Terminal** as root, paste:

```bash
chown -R www:www /www/wwwroot/poomconnect.com
find /www/wwwroot/poomconnect.com -type d -exec chmod 755 {} \;
find /www/wwwroot/poomconnect.com -type f -exec chmod 644 {} \;
mkdir -p /www/wwwroot/poomconnect.com/api/mobile \
  /www/wwwroot/poomconnect.com/uploads/events/{og,banners,gallery}
chmod -R 775 /www/wwwroot/poomconnect.com/uploads
chmod 640 /www/wwwroot/poomconnect.com/.env 2>/dev/null || true
chmod 640 /www/wwwroot/poomconnect.com/config/database.local.php 2>/dev/null || true
```

Then re-run **Actions → Deploy to production**. Later you can use:

```bash
bash /www/wwwroot/poomconnect.com/deploy/aapanel-permissions.sh
```

When schema changes ship, run migrations on the server:

```bash
cd /www/wwwroot/poomconnect.com
php migrate.php
```

Confirm production `.env` exists and is not world-readable. Do not regenerate `APP_ENCRYPTION_KEY` after payment gateway credentials have been saved.

## 5. Pretty URLs (nginx)

On nginx, links stay as `.php` (`/login.php`) until rewrite works. Apache (local MAMP) still uses `/login`.

To make `/login` work, open **aaPanel → Website → poomconnect.com → Rewrite**, delete any previous rules, and paste **only**:

```nginx
if (!-e $request_filename) {
    rewrite ^/(.+)/?$ /$1.php last;
}
```

Save. If aaPanel shows a red nginx error, the rules were not applied — do not keep `location { }` blocks in Rewrite (aaPanel already wraps this file inside `location /`).

Then **Reload** nginx and open `/login`. When that works, set `PRETTY_URLS=true` in the server `.env`.

Full `location` rules belong in **Config**, not Rewrite: see `deploy/nginx-pretty-urls.conf`.

## 6. Troubleshooting

**Deploy fails on FTP login (530)**  
- Recheck `FTP_PASSWORD` in GitHub secrets  
- In aaPanel → FTP, allow all IPs (GitHub Actions uses changing runner IPs)

**Deploy fails with `550 Can't change directory` or `Permission denied`**  
- FTP cannot write folders owned by another user (UID 501). Run the chown commands in section 4, then re-run the workflow.

**Deploy fails on TLS / FTPS**  
- This host uses explicit FTPS on port 21. If TLS breaks after an aaPanel change, the workflow can drop `set ftp:ssl-force true`.

**500 after deploy**  
- Confirm `.env` and `config/database.local.php` still exist on the server  
- Check aaPanel → Logs → nginx / PHP error log

**Uploads not writable**  
- Re-run `deploy/aapanel-permissions.sh` as root (see section 4)

**Files missing after deploy**  
- Check the workflow exclude list. `.git`, tests, `seed.php`, and `database.sql` are intentionally not uploaded.

## Files

```
.github/workflows/ci.yml              # Lint / tests on PR and push
.github/workflows/deploy.yml          # FTPS deploy to 50.6.250.5
deploy/aapanel-permissions.sh         # Fix uploads/ ownership on aaPanel
deploy/post-deploy.sh                 # Optional permission pass if you have SSH
config/database.local.php             # Server-only secrets (gitignored)
```
