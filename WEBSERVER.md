# Web server setup and hardening

Indexhibit itself is plain PHP and does not depend on a particular web server.
This file collects optional server-level rules that make an installation
harder to abuse. Pick the section for your server and adapt the paths.

Everything here is optional, Indexhibit works without it. The rules only
restrict access to things a visitor should never need.

## What should be reachable

Only these PHP files are meant to be requested directly by a browser:

| File | Purpose |
| --- | --- |
| `index.php` | the public website |
| `ndxzstudio/index.php` | the admin (studio) |
| `ndxzsite/plugin/ajax.php` | front-end AJAX (statistics plugin) |
| `ndxzstudio/install.php` | installer, only needed once - **delete it after installing** |

Everything else is included by these files. In particular:

* `files/` holds uploads and must never execute scripts
* `ndxzsite/config/` holds `config.php` with the database password
* `ndxzsite/cache/` holds generated pages (also of password protected exhibits)
* `ndxzstudio/upgrade/` is only run from inside the admin

## Requirements

* PHP 8.2 or newer
* MySQL or MariaDB
* PHP extensions: `mysqli`, `gd`, `mbstring`, `json`
* Optional: `intl` (localized month/day names, english is used without it),
  `curl` (YouTube/Vimeo thumbnails), `exif` (auto rotation of uploaded photos)

Writable by PHP: `files/`, `files/gimgs/`, `files/dimgs/`, `ndxzsite/cache/`,
and `ndxzsite/config/` during installation only.

## Apache

Indexhibit ships an `htaccess` file in the root which you rename to
`.htaccess` to get clean URLs. Below is the same file with the hardening
rules added. It works for installs in the web root and in a subdirectory.

```apache
RewriteEngine on

# Deny dotfiles (.git, .env, ...) but allow .well-known (Let's Encrypt)
RewriteRule (^|/)\.(?!well-known/) - [F,L]

# Deny config, cache and upgrade scripts
RewriteRule ^ndxzsite/(config|cache)/ - [F,L]
RewriteRule ^ndxzstudio/upgrade/ - [F,L]

# Never execute scripts inside the upload directory
RewriteRule ^files/.*\.(php[0-9]?|phtml|phar|pl|py|cgi|sh)$ - [F,L,NC]

# Only allow the known PHP entry points
# (remove ndxzstudio/install from the list once installed)
RewriteCond $1 !^(index|ndxzstudio/index|ndxzstudio/install|ndxzsite/plugin/ajax)\.php$
RewriteRule ^(.*\.(php[0-9]?|phtml|phar))$ - [F,L,NC]

# Clean URLs (the original Indexhibit rules)
RewriteCond %{REQUEST_FILENAME} -f [OR]
RewriteCond %{REQUEST_FILENAME} -d
RewriteRule ^(.+) - [PT,L]
RewriteRule ^(.+) index.php

Options -Indexes

# Optional security headers
<IfModule mod_headers.c>
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set Referrer-Policy "strict-origin-when-cross-origin"
</IfModule>
```

If you can't use rewrite rules, you can also drop this `.htaccess` into
`files/` to stop uploads from being executed:

```apache
<FilesMatch "\.(?i:php[0-9]?|phtml|phar|pl|py|cgi|sh)$">
    Require all denied
</FilesMatch>
```

And this one into `ndxzsite/config/` and `ndxzsite/cache/`:

```apache
Require all denied
```

## nginx

Adjust `root`, `server_name` and the PHP-FPM socket. For an install in a
subdirectory prefix the locations with that directory.

```nginx
server {
    listen 80;
    server_name example.com;
    root /var/www/indexhibit;
    index index.php index.html;

    client_max_body_size 32m;

    # Deny dotfiles but allow .well-known (Let's Encrypt)
    location ~ /\.(?!well-known/) { deny all; }

    # Deny config, cache and upgrade scripts
    location ^~ /ndxzsite/config/   { deny all; }
    location ^~ /ndxzsite/cache/    { deny all; }
    location ^~ /ndxzstudio/upgrade/ { deny all; }

    # Never execute scripts inside the upload directory
    location ~* ^/files/.*\.(php[0-9]?|phtml|phar|pl|py|cgi|sh)$ { deny all; }

    # Only the known PHP entry points go to PHP
    # (remove ndxzstudio/install from the list once installed)
    location ~ ^/(index|ndxzstudio/index|ndxzstudio/install|ndxzsite/plugin/ajax)\.php$ {
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
    }

    # Any other PHP file is denied
    location ~* \.(php[0-9]?|phtml|phar)$ { deny all; }

    # Clean URLs (same as the Apache htaccess)
    location / {
        try_files $uri $uri/ /index.php?$args;
    }

    # Optional security headers
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;
}
```

## PHP settings

Recommended `php.ini` (or `.user.ini` on shared hosting) values:

```ini
display_errors = Off
log_errors = On
expose_php = Off

; uploads - raise if you upload large images or videos
upload_max_filesize = 32M
post_max_size = 32M
```

## HTTPS

Run the site over HTTPS. Indexhibit marks its login cookies `Secure`
automatically when the request comes in over HTTPS. If PHP sits behind a
reverse proxy that terminates TLS, make sure the proxy passes `HTTPS=on`
to PHP (for example `fastcgi_param HTTPS on;`), otherwise the cookies are
sent without the `Secure` flag.
