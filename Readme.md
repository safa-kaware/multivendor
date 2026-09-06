# MultiVendor 🛍️

A full-stack multi-vendor e-commerce platform built with PHP, MySQL, and Bootstrap 5 — where customers shop, vendors sell, and admins run the show.

**🔗 Live demo:** [http://lily.wuaze.com/](http://lily.wuaze.com/)

---

## ✨ What it does

Three roles, one platform:

- **Customers** browse, search, filter, add to cart, apply coupons, check out with Cash on Delivery or Stripe, track orders, save wishlists, and leave reviews.
- **Vendors** manage their own product catalog (including bulk CSV import/export), track their orders, and never see anyone else's data.
- **Admins** run the whole marketplace — approve vendors, manage categories and homepage banners, create discount coupons, review sales reports, and configure site-wide settings.

---

## 🚀 Core Features

### Storefront
- Dynamic homepage with a rotating **banner carousel** and featured products — all controlled from the admin panel, zero hardcoded content
- Product search with category, price, and keyword filters
- Product detail pages with **variations** (size, color, etc.) and live stock status
- SEO-friendly product URLs (`/product.php?slug=wireless-headphones`, not opaque IDs) plus an auto-generated `sitemap.xml`

### Shopping & Checkout
- Full cart with quantity updates and live totals
- **Coupon system** — percentage or fixed discounts, minimum order thresholds, usage limits, expiry dates — applied live in the cart
- **Two payment methods**: Cash on Delivery, and **Stripe** (test-mode card payments via an embedded Stripe Elements form — no page redirects)
- Wishlist, order history, and address management for every customer

### Vendor Panel
- Full product CRUD with image uploads
- **Bulk CSV import/export** — add dozens of products in one upload, or export your catalog to a spreadsheet
- Orders view scoped strictly to that vendor's own products (no cross-vendor data leaks)

### Admin Panel
- Dashboard with sales analytics and top products
- Manage products, categories, vendors, and customer orders
- **Banner manager** for the homepage hero carousel
- **Coupon manager** — create, toggle, and track usage
- **Site settings** — contact info, social links, SEO defaults, all editable without touching code
- **Sales reports** with date-range filtering and one-click **CSV export**

### Security
- Passwords hashed with `password_hash()` / `password_verify()` (bcrypt)
- Every database query uses **PDO prepared statements** — no SQL injection surface
- Role-based access control (customer / vendor / admin) enforced server-side on every page
- Stateless, HMAC-signed **Bearer token authentication** for the REST API

### Developer-Friendly Extras
- **REST API** (`/api/`) for products and orders, so a future mobile app could plug straight in
- `robots.txt` + dynamic `sitemap.xml` for search engine visibility

---

## 🛠 Tech Stack

| Layer | Tech |
|---|---|
| Backend | PHP 8.0, PDO |
| Database | MySQL |
| Frontend | Bootstrap 5, Bootstrap Icons |
| Payments | Stripe (test mode) |
| Auth | bcrypt password hashing, session-based (web) + HMAC token-based (API) |

---

## ⚙️ Setup

1. Clone this repo into your local server's web root (e.g. `htdocs/` for XAMPP)
2. Import `database/multivendor_db.sql` into MySQL
3. Copy `config/database.example.php` → `config/database.php` and fill in your real DB credentials
4. Copy `config/stripe_example.php` → `config/stripe.php` and add your own [Stripe test API keys](https://dashboard.stripe.com/test/apikeys)
5. Update `BASE_URL` in `config/app.php` to match your local URL
6. Visit the project in your browser — you're in

---

## 🔑 Demo Accounts

All three use the password **`Demo@123`**:

| Role | Email |
|---|---|
| Admin | `admin@demo.com` |
| Vendor | `vendor@demo.com` |
| Customer | `customer@demo.com` |

---

## 📌 Project Status

This started as a college assignment and grew into a genuinely full-featured marketplace — core storefront, cart/checkout, dual payment methods, coupon engine, vendor tools, admin control panel, reporting, and a working REST API are all complete and live. Multi-currency support, newsletter integration, and a live-chat/Q&A system are on the "someday" list.
