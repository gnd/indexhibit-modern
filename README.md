# Indexhibit

Indexhibit is an archetypal portfolio CMS for everybody.

https://www.indexhibit.org

Indexhibit is a registered trademark of Jeffery Vaska and Daniel Eatock.

## About this version (2.2.0)

Indexhibit stopped being developed after 2.1.6. This version keeps it
running on current PHP and fixes a number of security problems, while staying
a drop-in replacement.

* Runs on PHP 8.2 - 8.5
* Passwords are stored with `password_hash()`. Old md5 passwords keep working
  and are converted automatically on the next login.
* Random login tokens and password reset links instead of md5 based ones.
  Login cookies are `HttpOnly`, `SameSite=Lax` and `Secure` on HTTPS.
* CSRF protection for all admin forms and AJAX requests
* Fixes for SQL injection, path traversal, arbitrary file read/write,
  XSS and an exposed `phpinfo()`
* Raw PHP in templates and content is no longer executed (`eval()` was removed,
  together with the `$default['parsing']` option)
* The admin no longer loads news from `api.indexhibit.org`
* Works without the `intl` extension

See [WEBSERVER.md](WEBSERVER.md) for requirements and optional Apache/nginx
and PHP hardening.

## Upgrading an existing site

1. Back up your database and your site files.
2. Replace the Indexhibit files with these, but keep your own:
   * `ndxzsite/config/config.php`
   * `files/` (your uploads)
   * custom themes in `ndxzsite/` and any extra plugins in `ndxzsite/plugin/`
   * `.htaccess` (compare it with [WEBSERVER.md](WEBSERVER.md))
3. Delete `ndxzstudio/install.php`, unless you are doing a fresh install.
4. Log in to `/ndxzstudio/`. Everybody is logged out once, because of the new
   login tokens.
5. Click "Update to version 2.2.0 - click here" on the main page of the admin.
   This makes the password column big enough for the new password hashes.

Things that behave differently:

* Password reset links sent before the upgrade no longer work, request a new one.
* If you enabled `$default['parsing']` to run PHP inside templates, that PHP
  is now stripped from the output.
* Third-party admin plugins that send POST requests without jQuery need to
  include the CSRF token (`ndxz_csrf_field()` in forms, or the `X-CSRF-Token`
  header with the value of the `ndxz_csrf` JavaScript variable).

## Fresh install

Upload the files, make `files/`, `files/gimgs/`, `files/dimgs/` and
`ndxzsite/config/` writable and open `/ndxzstudio/install.php`. The default
login is `index1` / `exhibit`, change it right away. Delete
`ndxzstudio/install.php` afterwards.
