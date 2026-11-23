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

## Upgrading from 2.1.x

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

## Upgrading from Indexhibit 0.7x (the `ndxz-studio` folder)

Older sites (0.70 - 0.73, up to 2012) have a single `ndxz-studio/` folder and no
`ndxzsite/`. That layout can't be upgraded in place. The installer imports the
old site into new tables instead, and leaves the old tables alone.

1. Back up your database and all files. Try it on a copy first - the site is
   down from step 2 until the import is done.
2. Upload `ndxzstudio/`, `ndxzsite/` and the new `index.php` (replacing the old
   one). Keep `ndxz-studio/` (the installer reads your database login and table
   prefix from it), `files/` and `.htaccess`.
3. Create `files/dimgs/` and make it, `files/gimgs/` and `ndxzsite/config/`
   writable.
4. Open `/ndxzstudio/install.php`. The form is filled in with your old database
   login and a new table prefix (`ndxzbt1_`). Add a site name, your name and
   email, submit, then click "Import".
5. Log in with `index1` / `exhibit` and change that in Preferences right away.
6. Check the site. Then delete `ndxzstudio/install.php`. When you are happy
   you can also delete `ndxz-studio/` and the old tables (prefix `ndxz_`).

What is imported: pages with their text, order, year, published and hidden
state, sections, images and captions (the files stay where they are), site name,
the header and footer text and the visitor statistics. The old
`<%variable%>` and `<plug:name />` tags are converted. Image titles and captions
are shown as before (the "titling" option is switched on, except for
`grow_no_title` and `no_thumbs_no_captions`).

The formats `grow` and `grow_no_title` become Visual Index,
`no_thumbs_w_captions` and `no_thumbs_no_captions` become No Thumbs, `slideshow`,
`over_and_over` and `thickbox` keep their name. Other old formats
(`backgrounded`, `deux_column`) are shown as Visual Index.

Links in the old style (`/index.php?/work/page/`) keep working, so bookmarks
and search engine results are not lost. The site's own links now look like
`/index.php/work/page/`. If your web server rewrites URLs (the `.htaccess` in
this package or the nginx rules in [WEBSERVER.md](WEBSERVER.md)), set
`MODREWRITE` to `true` in `ndxzsite/config/options.php` to get `/work/page/`.

### Your theme

Themes are not imported. After the import the site uses the `default` theme, and
the statistics and the menu setting of the old site are kept. To get your old
look back, port your theme into `ndxzsite/<name>/` (a template `index.php`
plus your CSS) and pick it on the Theme tab in the admin:

* The old tags are the same idea with other names: `<%variable%>` is
  `{{variable}}` (both work), `<plug:front_exhibit />` is
  `<plugin:page:exhibit />`, `<plug:front_index />` is
  `<plugin:index:load_index />`, `<plug:backgrounder ... />` is
  `<plugin:backgrounder />`, and the CSS and JavaScript of the formats come from
  `<last:page:css />` and `<last:page:javascript />`. `ndxzsite/default/index.php`
  is a complete example.
* If your CSS was written for the old menu (`#menu ul li.section-title`, one flat
  `<ul>` per section), use `<plugin:legacy_index:load_index />` instead of
  `<plugin:index:load_index />`. It writes the menu like Indexhibit 0.7x did
  (honouring the old chronological/sectional setting) and leaves out the
  section pages and the tag page that are new in 2.x.
* The exhibit formats write different HTML than the 0.7x ones (`#img-container`
  is still there, but the images and captions inside are not `<p>`s any more).
  CSS written for the old markup needs a few rules to match; add
  `format-{{format}}` to the `<body>` class of your template to be able to style
  each format separately.

### What is not imported

* Users and passwords - there is one login, see step 5.
* Everything in `ndxz-studio/site/`: themes, CSS, JavaScript, and your own
  exhibit formats and plugins (see "Your theme" above). Compare
  `ndxz-studio/site/plugin/` with `ndxzsite/plugin/` if you changed those.

The first time you open an exhibit in the admin, small system thumbnails are
created for its images. The image files themselves are not changed.

If the result looks wrong, drop the new tables (prefix `ndxzbt1_`), delete
`ndxzsite/config/config.php` and start again. To go back to the old site,
restore its `index.php`.

This was tested on PHP 8.5 with MariaDB in strict mode, using a v0.73 test
site (Visual Index, No Thumbs, Slideshow and Over and Over formats) and a real
0.70d site (30 pages, 364 images, 235,000 statistics rows, the Over and Over,
Thickbox and Grow formats). On the real site every published page showed the
same text and all of its images, the admin opened every exhibit with the right
number of images, and editing, publishing and uploading worked. With the old
theme ported by hand (using `legacy_index` and some CSS), screenshots of the 13
published pages before and after were identical down to the pixel on 8 of them;
on the other 5 a few letters sit a fraction of a pixel differently. The old site
kept working from the same database. Other 0.7x releases were not tested. If
their tables differ, the import can skip rows without telling you, so check the
site after importing.

## Fresh install

Upload the files, make `files/`, `files/gimgs/`, `files/dimgs/` and
`ndxzsite/config/` writable and open `/ndxzstudio/install.php`. The default
login is `index1` / `exhibit`, change it right away. Delete
`ndxzstudio/install.php` afterwards.
