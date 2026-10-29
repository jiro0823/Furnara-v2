# Furnara Web System

## Project layout

- `pages/public/` contains public storefront and information pages.
- `pages/auth/` contains login, registration, and logout pages.
- `pages/account/` contains customer profile, order, and tracking pages.
- `pages/admin/` contains administration screens.
- `pages/orders/` contains order workflow pages.
- `api/cart/` and `api/orders/` contain request handlers used by the interface.
- `includes/` contains shared application configuration. Reusable UI fragments live in `components/`.
- `assets/css/` contains stylesheets, including component styles in `assets/css/components/`.
- `pictures/`, `logo/`, and `uploads/` contain image assets and uploaded product images.

Page and API files contain their full implementation in these folders. Shared application URLs are generated through `app_url()` in `includes/config.php`, which keeps links working when the project is served from a subfolder.

## Database migrations

Before using account feedback, private order chat, and public order numbers, run the SQL files in `database/migrations/` once against the `furnaradb` database. In phpMyAdmin, select `furnaradb`, open the **Import** tab, and import each migration file.

## Run locally

From the project directory, start PHP's built-in server:

```powershell
php -S localhost:8000 router.php
```

Open `http://localhost:8000/` in a browser. The development router also keeps legacy top-level page URLs working. Configure the MySQL database connection in `includes/config.php` for your local environment.
Reusable PHP UI fragments and section IDs are documented in [components/README.md](components/README.md).
