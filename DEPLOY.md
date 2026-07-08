# CI/CD — Deploy to VPS (50.6.250.5)

Automated deploy via **GitHub Actions** on every push to `main`.

## Pipeline overview

| Workflow | Trigger | Action |
|----------|---------|--------|
| `ci.yml` | Push/PR to `main` or `develop` | PHP syntax lint + structure check |
| `deploy.yml` | Push to `main` (or manual) | Rsync to VPS + post-deploy permissions |

## 1. One-time VPS setup

SSH into your server:

```bash
ssh root@50.6.250.5
```

Upload or clone the project, then run:

```bash
cd /var/www/poomconnect   # or wherever you put the files first time
bash deploy/server-setup.sh
```

Optional environment overrides:

```bash
DEPLOY_PATH=/var/www/poomconnect \
DEPLOY_USER=deploy \
DOMAIN=yourdomain.com \
bash deploy/server-setup.sh
```

Import the database (first time only):

```bash
mysql -u poomconnect_user -p poomconnect < /var/www/poomconnect/database.sql
```

Visit `https://yourdomain.com/seed.php` once, then delete `seed.php` on the server.

## 2. SSH key for GitHub Actions

On your **local machine**:

```bash
ssh-keygen -t ed25519 -C "github-actions-poomconnect" -f ~/.ssh/poomconnect_deploy -N ""
```

Add the **public** key to the VPS:

```bash
ssh-copy-id -i ~/.ssh/poomconnect_deploy.pub deploy@50.6.250.5
```

Test:

```bash
ssh -i ~/.ssh/poomconnect_deploy deploy@50.6.250.5 "echo OK"
```

Give deploy user write access to the web root:

```bash
ssh root@50.6.250.5
chown -R deploy:www-data /var/www/poomconnect
chmod -R g+w /var/www/poomconnect
```

## 3. GitHub repository setup

```bash
cd /Applications/MAMP/htdocs/poomconnect
git init
git add .
git commit -m "Initial commit with CI/CD"
git branch -M main
git remote add origin https://github.com/YOUR_USER/poomconnect.git
git push -u origin main
```

## 4. GitHub Secrets

In **GitHub → Repository → Settings → Secrets and variables → Actions**, add:

| Secret | Value | Example |
|--------|-------|---------|
| `VPS_HOST` | Server IP | `50.6.250.5` |
| `VPS_USER` | SSH deploy user | `deploy` |
| `VPS_DEPLOY_PATH` | App root on server | `/var/www/poomconnect` |
| `VPS_SSH_KEY` | Full private key contents | Contents of `~/.ssh/poomconnect_deploy` |

To copy the private key:

```bash
cat ~/.ssh/poomconnect_deploy
```

Paste the entire output including `-----BEGIN` and `-----END` lines.

### Optional: GitHub Environment

Create a **production** environment in GitHub (Settings → Environments) to require approval before deploy.

## 5. Deploy

Every push to `main` runs CI then deploys automatically.

Manual deploy:

**GitHub → Actions → Deploy to VPS → Run workflow**

## 6. Production config

Database credentials live in **`config/database.local.php`** on the server only (gitignored).

Local MAMP still uses defaults (`root`/`root`) unless you create `database.local.php` locally.

Set production URL in `config/app.php`:

```php
define('APP_URL', 'https://yourdomain.com');
```

## 7. Troubleshooting

**Deploy fails on SSH**
- Verify `VPS_SSH_KEY`, `VPS_HOST`, `VPS_USER`
- Ensure deploy user can write to `VPS_DEPLOY_PATH`

**500 after deploy**
- Check `config/database.local.php` exists on server
- Check PHP-FPM logs: `tail -f /var/log/nginx/error.log`

**Uploads not working**
- Re-run post-deploy: `DEPLOY_PATH=/var/www/poomconnect bash deploy/post-deploy.sh`

**Rsync deletes nothing important**
- User uploads in `uploads/` are excluded from sync delete patterns

## Files

```
.github/workflows/ci.yml      # Lint on PR/push
.github/workflows/deploy.yml  # Deploy to 50.6.250.5
deploy/server-setup.sh        # One-time VPS bootstrap
deploy/post-deploy.sh         # Permissions after each deploy
config/database.local.php     # Server-only secrets (gitignored)
```
