# Furnara Web System

Furnara is a PHP and MySQL furniture commerce web application. It provides a public storefront for browsing furniture and a connected set of customer and administrator tools for managing carts, checkout, orders, delivery, and store operations.

## Purpose

The system brings furniture discovery and order handling into one web application. Customers can browse the catalog, maintain an account, submit delivery or in-store pickup orders, and follow up on their purchases. Administrators can manage catalog and order activity through dedicated screens.

## Key capabilities

- Public home, catalog, and product detail pages with furniture imagery and category browsing.
- Customer registration, sign-in, profile, and saved delivery location.
- Shopping cart management, stock-aware quantity updates, and checkout preparation.
- Delivery and in-store pickup order workflows, including payment status and public order references.
- Customer order history, order detail, cancellation, delivery tracking, product feedback, and refund request flows.
- Customer-to-administrator support messaging.
- Administrator dashboards for products, discounts, orders, customer messages, delivery tracking, store location, and refund requests.
- Reusable PHP interface components and shared URL handling for root or subdirectory deployments.

## Technology

- PHP (procedural application with reusable PHP components)
- MySQL / MariaDB through `mysqli`
- HTML, CSS, and JavaScript
- Composer metadata is included; `composer.json` currently declares no third-party PHP packages.
- The interface references third-party browser libraries and map services where applicable; internet connectivity may be required for those resources.

## Requirements

- PHP with `mysqli` enabled
- MySQL or MariaDB
- A web server such as Apache (XAMPP) or PHP's built-in development server
- The Furnara database and its base tables. The repository contains incremental migrations, not a complete initial schema dump.

## Local setup

1. Place the project in your web server's document root, or open a terminal in the project directory.
2. Create/configure the `furnaradb` database and its base schema. Database credentials can be supplied through environment variables: `FURNARA_DB_HOST`, `FURNARA_DB_PORT`, `FURNARA_DB_NAME`, `FURNARA_DB_USER`, and `FURNARA_DB_PASSWORD`. Defaults are `127.0.0.1`, `3306`, `furnaradb`, `root`, and an empty password, respectively.
3. Apply the SQL files in `database/migrations/` to the configured database in chronological order. Apply each migration once, after the required base tables exist. Some migration files specify their dependency in a comment.
4. Start the local development server from the project directory:

   ```powershell
   php -S localhost:8000 router.php
   ```

5. Open [http://localhost:8000/](http://localhost:8000/) in a browser.

For XAMPP, start Apache and MySQL, configure the environment/database settings for the PHP process, and open the project through its local Apache URL. The URL helper in `includes/config.php` is intended to support both root and subdirectory installations.

## Project structure

| Path | Responsibility |
| --- | --- |
| `pages/public/` | Storefront home, catalog, product, and public user pages |
| `pages/auth/` | Registration, login, and logout |
| `pages/account/` | Customer profile, orders, order details, and tracking |
| `pages/admin/` | Administrative dashboards and management screens |
| `pages/orders/` | Checkout, cancellation, and order delivery workflows |
| `api/cart/`, `api/orders/`, `api/chat/`, `api/admin/` | Request handlers used by storefront and account interfaces |
| `components/` | Shared navigation, home page sections, footer, and support widget |
| `includes/` | Shared database/application configuration and order inventory helpers |
| `assets/css/`, `assets/js/` | Stylesheets and browser-side behavior |
| `database/migrations/` | Incremental database schema changes |
| `pictures/`, `logo/`, `uploads/` | Furniture, brand, and other image assets |

## Documentation

See [docs/PROJECT_DOCUMENTATION.md](docs/PROJECT_DOCUMENTATION.md) for the formal project description, user roles, feature workflows, architecture, setup guidance, and operational notes. Shared UI fragments are listed in [components/README.md](components/README.md).

## Development notes

- Use `app_url()` from `includes/config.php` when generating application-relative URLs.
- Keep reusable page fragments in `components/`; these are PHP markup fragments, not standalone documents.
- Database migrations are incremental and should be backed up and reviewed before applying to a production database.
- Do not commit production credentials or private customer data.
