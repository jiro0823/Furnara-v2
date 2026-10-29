# Furnara Web System — Project Documentation

**Document purpose:** Provide a formal overview of Furnara's purpose, users, implemented capabilities, application structure, setup requirements, and operational considerations.

## 1. Project overview

Furnara is a web-based furniture storefront and order management system implemented in PHP and backed by MySQL or MariaDB. It combines public product discovery with customer account functions and administrative workflows. The system is intended to support the principal stages of a furniture purchase: browsing, cart management, checkout, fulfillment selection, order follow-up, and store-side order administration.

This repository contains the PHP application, browser assets, reusable interface components, image assets, and incremental SQL migrations. It does not contain a full initial database schema dump; deployment requires a compatible base Furnara database before the included migrations can be applied.

## 2. Objectives and scope

The system aims to:

- Present furniture products through a public storefront and catalog.
- Let customers create accounts, maintain profile information, and save a delivery location.
- Support cart-based order preparation and delivery or in-store pickup checkout.
- Give customers access to order history, order details, delivery progress, and post-purchase support workflows.
- Provide administrators with screens for managing products, discounts, orders, messages, refunds, and delivery operations.

The repository documents application functionality and local setup. Production hosting, a complete database bootstrap, payment gateway integration, and service-level guarantees are outside the scope of this codebase's documentation.

## 3. User roles

### Visitor

Visitors can access public storefront pages, browse product categories, and view product information. Account-dependent actions require authentication.

### Customer

Customers can register and sign in, manage profile details, save a delivery address and map coordinates, use a shopping cart, submit orders for delivery or pickup, and review their order history. The account workflow also includes order cancellation, tracking, product feedback, refund requests, and support messaging, subject to the applicable order state and database setup.

### Administrator

Administrative pages provide tools for product and discount management, order handling, delivery tracking, customer conversation management, store location updates, and refund request review. Access to these screens is expected to be restricted by the application's authentication and role checks.

## 4. Functional capabilities

### 4.1 Storefront and catalog

The public experience includes home/landing pages, furniture catalog browsing, and product detail pages. Shared navigation, home sections, and footer components keep common interface elements consistent. Product images and brand assets are stored in the repository's image directories.

### 4.2 Accounts and profile

Authentication pages provide registration, login, and logout. Customer profile pages expose account information and delivery location settings. The location workflow stores an address along with latitude and longitude for use during delivery checkout and order fulfillment.

### 4.3 Cart and inventory

Cart request handlers support adding, loading, updating, and removing items. Quantity changes are checked against available stock, and the cart workflow coordinates stock reservations through the shared order inventory logic and relevant database tables.

### 4.4 Checkout and fulfillment

Checkout prepares an order and finalizes it for one of two fulfillment paths:

- **Delivery:** uses a saved customer delivery location and records delivery details and payment status.
- **In-store pickup:** records pickup scheduling details and payment status.

The checked-in checkout interface currently describes cash on delivery for delivery orders and pay-at-store for pickup orders. The repository's checkout implementation records payment method/status; the presence of this workflow should not be interpreted as an online card-payment integration.

### 4.5 Customer order services

Customer pages include an order list, order detail, order cancellation, and delivery tracking. Related account workflows support product reviews, refund requests, and private messages with administrators. Some of these capabilities rely on the incremental migrations described below.

### 4.6 Administration

Administrative pages cover dashboard summaries, products, discounts, orders, customer messages, delivery tracking, and refund-related operations. API endpoints also support selected administrative actions such as saving a store location and updating refund requests.

## 5. Application architecture

Furnara is organized as a server-rendered PHP application. Page files render screens, API-style PHP endpoints handle asynchronous or form-based requests, shared components render reusable markup, and shared includes provide configuration and domain helpers. Browser-side JavaScript supports interactive cart, checkout, map, motion, and support-chat experiences.

### Directory map

| Directory | Contents |
| --- | --- |
| `pages/public/` | Public landing, catalog, product, and storefront pages |
| `pages/auth/` | Account registration, login, and logout |
| `pages/account/` | Customer profile, order history, order details, and tracking |
| `pages/admin/` | Administrator dashboards and management pages |
| `pages/orders/` | Checkout, order cancellation, and delivery tracking workflows |
| `api/account/` | Account-related handlers, including order feedback |
| `api/admin/` | Administrative request handlers |
| `api/cart/` | Cart and stock-aware cart operations |
| `api/chat/` | Customer/admin message operations |
| `api/orders/` | Order preparation, finalization, fulfillment, and update handlers |
| `components/` | Shared PHP markup components; see `components/README.md` |
| `includes/` | Database connection, URL helper, and order inventory functions |
| `assets/css/`, `assets/js/` | Front-end styling and browser-side behavior |
| `database/migrations/` | Incremental SQL schema migrations |
| `pictures/`, `logo/`, `uploads/` | Catalog, user-uploaded, and branding image assets |

### Request and data flow

1. A browser requests a PHP page under `pages/` or a URL handled by `router.php`.
2. Application pages and handlers load shared configuration from `includes/config.php` where needed.
3. The configuration initializes the PHP session, derives the application base URL, and opens a `mysqli` connection using environment variables or documented defaults.
4. Page handlers read or update MySQL data and render HTML or return JSON responses.
5. Browser JavaScript calls relevant handlers for interactive features such as cart updates, checkout, map selection, and messaging.

## 6. Technology and dependencies

- **Server:** PHP with the `mysqli` extension.
- **Database:** MySQL or MariaDB.
- **Client:** HTML, CSS, and JavaScript.
- **Local web server:** Apache/XAMPP or PHP's built-in development server.
- **Composer:** `composer.json` is present but currently declares no third-party PHP packages.
- **External browser resources:** Map and UI features may load third-party browser libraries or map services; those features may need network access.

## 7. Installation and configuration

### Prerequisites

1. Install PHP with `mysqli` enabled and MySQL or MariaDB.
2. Ensure a compatible Furnara base database schema is available. This project checkout does not include a complete initial schema export.
3. Make the project available under a local web server document root or run the PHP development server.

### Database configuration

`includes/config.php` reads the following environment variables:

| Variable | Default | Description |
| --- | --- | --- |
| `FURNARA_DB_HOST` | `127.0.0.1` | Database server hostname |
| `FURNARA_DB_PORT` | `3306` | Database server port |
| `FURNARA_DB_NAME` | `furnaradb` | Database name |
| `FURNARA_DB_USER` | `root` | Database username |
| `FURNARA_DB_PASSWORD` | empty | Database password |

For development, configure these variables in the environment used by Apache/PHP or the shell launching the built-in server. Production deployments should use a dedicated database account and keep credentials outside version control.

### Apply migrations

Run the SQL files in `database/migrations/` against the configured database, in chronological order, once the required base tables exist. The migration set adds furniture attributes, customer feedback/refund tables, order details and chat, stock reservations, delivery map locations, and order payment fields. Follow dependency comments in the SQL files, take a database backup before applying changes, and do not apply a migration repeatedly unless it is explicitly designed to be rerunnable.

### Start the development server

From the project root:

```powershell
php -S localhost:8000 router.php
```

Then open `http://localhost:8000/`. In XAMPP, start Apache and MySQL and access the project through the local Apache host. The `app_url()` helper in `includes/config.php` builds URLs relative to the project path to support subdirectory hosting.

## 8. Database and migration notes

The application expects existing tables for core entities such as users, furniture, carts, orders, delivery/pickup payment records, and tracking. The committed migration files are supplementary changes, not a substitute for the initial schema. Confirm that the base schema and application version are compatible before deployment. For production migration, use a backup and a controlled change process.

## 9. Operational and security considerations

- Use HTTPS and secure server configuration when deploying outside a local development environment.
- Set database credentials through the runtime environment and use a least-privilege database user.
- Restrict administrative routes to authorized administrator accounts and verify role enforcement in the deployed environment.
- Back up the database and uploaded assets as part of the deployment's recovery process.
- Review file upload handling, session configuration, and database migrations against the needs of the target deployment.
- Avoid committing secrets, customer records, or production-only configuration.

## 10. Maintenance guidance

- Place reusable PHP markup in `components/` and update `components/README.md` when adding shared components.
- Use `app_url()` for application-relative links and asset paths.
- Keep schema changes as chronological SQL migrations in `database/migrations/`, documenting ordering dependencies in each file.
- Keep API request/response behavior aligned with the browser code that calls each handler.
- When adding or changing a workflow, update this document and the main README so the feature description stays accurate.

## 11. Known repository boundaries

- A full database bootstrap/schema dump is not included in this repository snapshot.
- The included migration scripts expect a compatible pre-existing schema.
- The checkout/payment flow documents cash on delivery and payment at pickup; no online card processor integration is established by the current application flow.
- Third-party map/browser resources may require external network access.
