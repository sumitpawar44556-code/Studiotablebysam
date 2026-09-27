# Studio Table by Sam — Demo Project

Simple static restaurant pages for a college major project. Includes a home page and a menu with Fast Food, Lunch and Dinner sections. Prices are set between Rs 30 and Rs 1000.

How to use:

Static: open `index.html` directly. The menu and admin pages use browser `localStorage` for this presentation version.

This static version does not provide shared server storage or secure authentication. Orders are stored only in the browser used for the presentation.

Dynamic features:
- Menu is served from `menu-data.js` by `server.js` and rendered client-side by `script.js`.
- Place orders by clicking `Add to order` — orders are posted to `/api/orders` and saved to `orders.json`.

Files:

- `index.html` — Home page
- `menu-data.js` — Menu data source
- `orders.json` — Stored orders
- `styles.css` — Styles for layout and responsiveness
- `script.js` — Renders menu data, handles search/filter, cart, ordering, and browser storage


Notes:

- SVGs and photos in `images/` are used by `menu-data.js`; update that file when changing menu images.
- Ensure the Node process can write `orders.json` when using `/api/orders`.
- Admin access uses HTTP Basic Auth. The default password is `college123`; set `ADMIN_PASSWORD` before deployment.
- The admin dashboard is available at `/admin` and CSV export at `/admin/orders.csv`.

These files prevent browsers from directly fetching `inc/` files. For extra safety, move `inc/` outside your webroot if possible.


