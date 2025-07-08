# Chris & Kara Wedding – WordPress Site Repository

This repository contains the source code and configuration for the **ChrisAndKaraWedding** WordPress site. It is designed for local development using Docker and Composer to manage plugins and themes.

---

## 📁 Project Structure

```
ChrisAndKaraWedding/
├── wp-admin/              # Core WordPress admin files (untracked)
├── wp-content/            # Site-specific content
│   ├── mu-plugins/        # Must-use plugins (custom site utilities)
│   ├── plugins/           # Plugins managed via Composer
│   ├── themes/            # Custom or third-party themes
│   ├── uploads/           # Media uploads (untracked)
│   └── index.php
├── wp-includes/           # Core WordPress includes (untracked)
├── composer.json          # Plugin/theme dependencies
├── composer.lock          # Composer lock file (optional to track)
├── docker-compose.yml     # Shared in parent directory
├── index.php              # WordPress front controller
├── wp-config.php          # Site-specific config (tracked)
├── wp-config-docker.php   # Docker-specific overrides
├── .gitignore             # Defines tracked/untracked behavior
└── ...
```

---

## 📦 What is Tracked vs. Untracked?

### ✅ Tracked Files
- `composer.json`
- `wp-config*.php`
- Custom plugins/themes in `wp-content/plugins/` and `wp-content/themes/`
- Specific `mu-plugins` files: `crt-plugin.php`, `parsedown.php`
- `.gitignore`, `index.php`, and key PHP bootstrap files

### ❌ Ignored Files (from `.gitignore`)
- Core WordPress directories: `wp-admin/`, `wp-includes/`
- All non-custom plugin/theme files
- Uploads: `wp-content/uploads/`
- Cache, upgrade, backups: `wp-content/cache/`, `wp-content/upgrade/`
- Common system files: `.DS_Store`, `Thumbs.db`
- Dependency directories: `vendor/`, `node_modules/`
- Temporary logs or debug files

---

## 🔧 Local Development with Docker

This project is designed to run via Docker Compose. You must clone the full Docker setup from the parent `WordPressDocker/` directory.

### Setup Instructions

1. Ensure the following structure:
    ```
    ~/Developer/
    ├── ChrisReedTech/
    ├── ChrisAndKaraWedding/
    └── WordPressDocker/
    ```

2. From `WordPressDocker/`, start all services:
    ```sh
    docker compose up -d
    ```

3. Shut down all services:
    ```sh
    docker compose down
    ```

4. To reset DB volumes:
    ```sh
    docker compose down -v
    ```

5. Make sure your `/etc/hosts` includes:
    ```
    127.0.0.1 chrisandkarawedding.local
    ```

---

## 🧩 Plugin Management via Composer (One-Off Docker Commands)

Plugins are managed using [wpackagist](https://wpackagist.org) via Composer.  
No Composer install is required on your host machine.

### Composer Commands

**Install all plugins from composer.json:**
```sh
docker run --rm -v $(pwd):/app -w /app composer:latest install
```

**Update plugins to the latest versions:**
```sh
docker run --rm -v $(pwd):/app -w /app composer:latest sh -c "git config --global --add safe.directory /app && composer update"
```

**Require (add) a new plugin:**
```sh
docker run --rm -v $(pwd):/app -w /app composer:latest require wpackagist-plugin/PLUGIN-SLUG:*
```
_Replace `PLUGIN-SLUG` with your desired plugin (e.g., `wp-sweep`)._

> The Composer config installs plugins into `wp-content/plugins/{plugin}`.

---

## 🔄 Using WP-CLI in a One-Off Docker Container

You can run WP-CLI commands without installing WP-CLI locally.

**List all plugins:**
```sh
docker run --rm -v $(pwd):/var/www/html -w /var/www/html --network=host wordpress:cli wp plugin list
```

**Search-replace URLs (local → production):**
```sh
docker run --rm -v $(pwd):/var/www/html -w /var/www/html --network=host wordpress:cli wp search-replace 'https://chrisandkarawedding.local' 'https://www.chrisandkara.wedding' --skip-columns=guid --allow-root
```

> ⚠️ Always back up your DB before running search-replace commands.

---

## ✅ Best Practices

- ✅ Do **not** commit your `.env` file; use `.env.example` for sharing defaults.
- ✅ Keep all third-party code managed via Composer for consistency and portability.
- ✅ Commit only custom themes, plugins, and code—never core WordPress or uploads.
- ✅ Use `wp-config-docker.php` to keep local overrides isolated.

---

## 🧪 Customizations

- `functions.php` in a custom theme or `mu-plugins/crt-plugin.php` may contain site-specific behavior such as:
  - Custom post title logic
  - Post revision limits
  - Theme support changes
  - Media handling

---

## 📝 License

This codebase is maintained for private use. No license is granted for reuse or redistribution.

---

## 👤 Author

Chris Reed  
[https://www.chrisreedtech.com](https://www.chrisreedtech.com)
