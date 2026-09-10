# Studio Table by Sam — Demo Project

Simple static restaurant pages for a college major project. Includes a home page and a menu with Fast Food, Lunch and Dinner sections. Prices are set between Rs 30 and Rs 1000.

How to use (Static or XAMPP):

Static: open `index.html` directly in the browser. The menu uses embedded SVG placeholders.

XAMPP (recommended for dynamic features):

1. Place the `SUMIT` folder under your XAMPP `htdocs` directory (already at `c:\xampp\htdocs\SUMIT`).
2. Start Apache from the XAMPP control panel.
3. Open `http://localhost/SUMIT/index.html` in your browser.

Dynamic features:
- Menu is served from `menu.json` and rendered client-side by `script.js`.
- Place orders by clicking `Order` on an item — orders are posted to `orders.php` and saved to `orders.json`.

Files:

- `index.html` — Home page
- `menu.php` — Dynamic menu page (renders from `menu.json`)
- `menu.json` — Menu data source
- `orders.php` — Simple endpoint to receive orders (appends to `orders.json`)
- `orders.json` — Stored orders
- `styles.css` — Styles for layout and responsiveness
- `script.js` — Fetches `menu.json`, renders items, handles search/filter and ordering


Notes:

- SVGs in `images/` are placeholders; replace them with photos by uploading images and updating `menu.json` image paths.
- Ensure file write permissions for `orders.json` when using `orders.php`.
- Admin access: a simple session-based login protects `admin.php`. Default password is set in `inc/config.php` as `ADMIN_PASS` — change it before deploying.

- Admin access: a simple session-based login protects `admin.php`. Passwords are now stored hashed in `inc/pass.json`. On first run the file is created from the legacy `inc/config.php` value (default `college123`). Change the password from the admin UI at `/SUMIT/admin_change_pass.php`.

- HTTP Basic Auth: admin pages now require HTTP Basic Auth only (username ignored, provide the admin password as the password). Browser will prompt for credentials.
- CSRF protection: admin POST actions require a CSRF token embedded in forms; tokens are stored server-side in the session. Sessions are still used only for CSRF token storage.
- Secure cookies: session cookies are set with `httponly` and `samesite=Lax`; `secure` flag is set when the site is served over HTTPS.

Protection added for `inc/`:

- An `.htaccess` file was added to `inc/` to deny direct web access via Apache.
- A `web.config` file was added to `inc/` to deny access on IIS/Windows setups.

These files prevent browsers from directly fetching `inc/` files. For extra safety, move `inc/` outside your webroot if possible.


