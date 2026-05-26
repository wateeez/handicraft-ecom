# Application Overview — Handicraft E-Commerce Platform

A full-featured Laravel e-commerce platform built for selling handicraft products. It has a public-facing storefront for customers and a comprehensive admin backend for operations staff.

---

## Tech Stack

| Layer | Technology |
|---|---|
| Framework | Laravel 11 (PHP) |
| Frontend Styling | Tailwind CSS |
| Frontend Interactivity | Alpine.js |
| Asset Bundling | Vite |
| Database | MySQL |
| Payment Gateway | PayPal (Sandbox & Live) |
| Media Storage | Local disk or AWS S3 |
| Email | SMTP / Mailgun / SES / Resend |
| Job Queue | Laravel Queues (async delivery auto-marking) |

---

## Application Sections

### 1. Public Frontend (Storefront)
### 2. Admin Backend (Operations & Management)
### 3. REST API (PayPal payment processing)

---

## Frontend Features

### Home Page & Product Discovery
- Displays all products with a category sidebar for filtering.
- **Filter tabs**: New Arrivals, Featured, Recommended, On Sale, Discounted, Most Sold.
- **Category + Subcategory filtering** — desktop shows a flyout hover menu; mobile shows an expandable inline list.
- **Product search** via search bar in the navigation.
- Products support a **carousel priority** field so admins can control display order.

### Product Detail Page (`/products/{slug}`)
- Shows product name, price, discount price (with % badge), SKU, dimensions, weight, description, and long description.
- **Image zoom** on hover — a magnified side-panel appears using native JavaScript without any library.
- **Image gallery** — thumbnail strip to switch between multiple product images.
- **Related products** shown from the same category.
- **Add to Cart** button (if "Order Now" is enabled on the product).
- **Inquiry form** — customers can send a message directly from the product page without adding to cart.
- **WhatsApp inquiry button** — opens WhatsApp with a pre-filled configurable message template containing the product name, SKU, price, and URL.

### Shopping Cart (`/cart`)
- Session-based cart (no login required for guests).
- Shows product image, name, dimensions, weight, price, quantity controls, and line totals.
- Supports discount prices (strikethrough original price).
- Minimum quantity enforced per product.
- Remove individual items or proceed to checkout.

### Checkout (`/checkout`)
- Collects: full name, email, phone number, shipping address, city, zip code, country.
- **Dynamic shipping calculation** — when a country is selected, available shipping rates are fetched via AJAX and displayed as radio options (provider name, zone, cost).
- Supports two checkout flows:
  - **Standard checkout** from the cart.
  - **Inquiry-linked checkout** via a secure token URL sent by admin — pre-fills the customer's details and items from an existing inquiry order.
- **PayPal payment** — integrates using PayPal's JS SDK. The order amount is always read server-side (never from the browser) to prevent tampering.
- On successful payment, the order is created, marked as paid, and a confirmation page is shown.

### Blog (`/blog`, `/blog/{slug}`)
- Paginated blog post listing with featured image, excerpt, author, published date, and reading time.
- Full-text search across title, content, and excerpt.
- Individual post pages with full content and related posts.
- **SEO-optimised**: custom meta title, meta description, meta keywords, Open Graph tags, Twitter card tags, and JSON-LD structured data per post.

### Shipping Policy Page (`/shipping-policy`)
- Displays the shipping policy text that is managed from the admin site settings.

### Authentication
- Admin login at `/login` with email/password. Redirects to the admin dashboard on success.
- "Remember Me" and "Forgot Password" supported.
- Logout invalidates the session and redirects to the homepage.

### Maintenance Mode
- When enabled by a super admin, the public storefront shows a "We'll be back soon" page.
- Admin panel remains fully accessible during maintenance.

---

## Admin Backend Features

All admin routes are protected by authentication. Role-based middleware restricts certain sections to users with specific permissions.

### Dashboard (`/admin`)
- Shows total product count, total category count, and total inquiry count.
- **Maintenance mode toggle** — requires password confirmation before toggling; uses Laravel Cache to store the state.

### Product Management (`/admin/products`)
- Full CRUD for products.
- **Fields**: Name, SKU, price, discount price, stock, minimum order quantity, material, category, subcategory, dimensions (L×W×H in cm), weight (kg), main image, secondary image, gallery images, long description.
- **Carousel flags**: New Arrival, Featured, Recommended, On Sale — each toggleable independently with a carousel priority number.
- **"Order Now" toggle** — controls whether the Add to Cart button appears on the storefront.
- **Auto-generated URL slug** from the product name (with uniqueness check).
- **Bulk CSV Import** — admins upload a CSV to create or update products in batch; a downloadable template CSV is provided.
- **Bulk CSV Export** — exports all products to CSV.
- **Bulk Image Upload** — upload a ZIP of images; images are matched to products by SKU.
- Search by product name or SKU; filter by category.

### Category & Subcategory Management (`/admin/categories`)
- Create, edit, and delete top-level categories (name, slug, description).
- Create, edit, and delete subcategories nested under a parent category.
- Products can be assigned to both a category and an optional subcategory.

### Client Management (`/admin/clients`)
- A client is a buyer profile — separate from admin users.
- Each client gets an auto-generated **Buyer ID** (e.g., `BYR-00001`).
- **Fields**: Name, email, phone, company name, address, city, state, zip code, country, notes.
- Search by name, email, buyer ID, or phone.
- Client profile shows their full order history.
- Soft deletes (clients are not permanently removed).
- Creating/updating clients is logged in the audit log.

### Order Management (`/admin/orders`)

This is the core **Order Management System (OMS)**.

#### Order Types
- **Inquiry** — created from the frontend inquiry form (no payment yet).
- **Order** — a confirmed, paid purchase.

#### Order Lifecycle (Status Workflow)
```
Unprocessed → Quotation Sent → Processed → Dispatched → Delivered
                                                       ↘ Cancelled
```
- Admins can only move forward in the workflow (no skipping) unless they have the `override_order_status` permission.
- Cancellation is available from most statuses.

#### Order Fields
- Order number (auto-generated, e.g., `ORD-2026-00001`)
- Client, assigned shipping provider, tracking number
- Line items (product, quantity, unit price, weight, per-item discount)
- Order-level discount (percentage or fixed amount)
- Shipping cost (calculated or manually entered)
- Grand total
- Payment status (`is_paid` flag + `financial_locked_at` timestamp)
- Delivery period days, expected delivery date
- Notes, cancellation reason
- Client snapshot (JSON copy of client data frozen at payment)

#### Admin Can
- Create orders manually (select client, products, shipping provider).
- Calculate shipping cost on-the-fly within the order form.
- Update order status with email notifications sent to the client at key transitions.
- Mark an order as paid.
- Cancel an order with a reason.
- **Merge orders** — combine multiple orders from the same client into a single consolidated order.
- Filter list by type, status, date range, or search by order number / client name.

#### Automated Delivery
- A Laravel queued job (`MarkOrderDeliveredJob`) can be scheduled to auto-mark dispatched orders as "Delivered" after the expected delivery date passes.

### Inquiry Management (`/admin/inquiries`)
- Lists all customer product inquiries.
- Admins can **reply** to inquiries (reply is stored but email sending can be configured).
- Admins can **send a checkout link** — generates a unique secure token and a checkout URL that is shown to the admin to forward to the customer. The link pre-fills the customer's items for a guided checkout experience.

### Invoice Management (`/admin/invoices`)
- Invoices are linked to orders.
- **Status lifecycle**: Draft → Issued → Voided.
- Only one active invoice per order at a time; existing active invoices must be voided before a new one can be generated.
- **Generate PDF** — invoice PDFs are generated on-demand and stored on disk; the path is saved in the database.
- **Download PDF** — re-generates if the file is missing.
- **Void** — requires a void reason (minimum 5 characters); only users with `void_invoices` permission can void.
- Invoices snapshot the client details and full financial breakdown at generation time so historical records remain accurate.
- Invoice numbers are sequential (e.g., `INV-2026-00001`).

### Shipping Management (`/admin/shipping`)

Three interconnected configuration areas:

#### Shipping Zones
- A zone groups countries together (e.g., "Zone 1: US, CA, DE").
- Countries are stored as a JSON array.
- **CSV import/export** for bulk zone-to-country mapping.
- Template CSV downloadable for reference.

#### Shipping Providers
- A provider is a courier service (e.g., DHL, FedEx).
- Can be marked active or inactive.
- Each provider can have rates defined per zone and weight range.

#### Shipping Rates
- A rate links a **provider**, a **zone**, a **weight range** (min-max kg), and a **price**.
- The shipping service matches a customer's destination country → finds matching zone → finds the rate matching the total cart weight → returns the price.
- **CSV import/export** for bulk rate management.
- Template CSV downloadable for reference.

### Blog Management (`/admin/blog`)
- Full CRUD for blog posts.
- **Fields**: Title, content (rich text), excerpt, featured image, SEO fields (meta title, meta description, meta keywords), publish status, published date, priority.
- Draft vs. published state — published posts appear on the frontend; drafts are hidden.
- Posts listed with search by title or content, and filter by published/draft status.

### Site Settings (`/admin/settings`)
- Manage global site configuration:
  - **Site Name** — displayed in browser titles and the navbar when no logo is set.
  - **WhatsApp Number** — used for the WhatsApp inquiry button on product pages.
  - **WhatsApp Message Template** — configurable with placeholders: `{product_name}`, `{sku}`, `{price}`, `{url}`.
  - **Shipping Policy** — free-text content rendered on the `/shipping-policy` page.
  - **Navbar Logo** — image uploaded and displayed in the site header.
  - **Footer Logo** — image displayed in the site footer.
  - **Favicon** — uploaded and served as the browser tab icon.
  - **Footer QR Code** — image shown in the footer (e.g., a WhatsApp QR).
  - **Footer Contact Info** — address, phone, email, business hours.
- All settings are cached using Laravel's Cache for performance.

### Admin User Management (`/admin/users`)
- Requires the `manage_users` permission.
- Create, view, edit, and soft-delete admin users.
- **Assign/revoke roles** from a user's profile.
- **Toggle active/inactive** status — inactive users cannot log in.
- **Reset passwords** from within the admin panel.
- Search by name or email; filter by role and status.
- Last login timestamp and IP address are recorded.

### Role & Permission Management (`/admin/roles`)
- Requires the `manage_roles` permission.
- Create and edit roles with a display name, system name (lowercase, underscores only), and description.
- **Assign permissions** to roles via a checklist.
- `super_admin` role is protected — it cannot be edited or deleted.
- View role details including how many users hold the role and what permissions it carries.

---

## Permissions Reference

| Permission | Description |
|---|---|
| `manage_users` | Create/edit/delete admin users |
| `manage_roles` | Create/edit roles and permissions |
| `manage_products` | Create/edit/delete products |
| `view_products` | Read-only access to products |
| `manage_categories` | Create/edit/delete categories |
| `view_categories` | Read-only access to categories |
| `manage_inquiries` | Reply to and manage inquiries |
| `view_inquiries` | Read-only access to inquiries |
| `manage_shipping` | Manage shipping zones, providers, rates |
| `view_shipping` | Read-only access to shipping settings |
| `manage_settings` | Edit site settings |
| `view_dashboard` | Access the admin dashboard |
| `manage_orders` | Create, edit, update order status |
| `override_order_status` | Skip steps in the order status workflow |
| `manage_invoices` | Generate and issue invoices |
| `void_invoices` | Void issued invoices |
| `merge_orders` | Merge multiple orders into one |
| `manage_clients` | Create and edit client profiles |
| `view_audit_logs` | Read-only access to the audit trail |

---

## Services

### `OrderService`
Central service for creating and managing orders. Handles:
- Creating orders and inquiry records in a single database transaction.
- Adding/removing/updating order line items.
- Recalculating all totals (subtotal, item discounts, order discount, shipping, grand total, total weight).
- Merging multiple orders into one.
- Updating order status and triggering email notifications.
- Locking financial data when an order is paid.

### `ShippingService`
Calculates applicable shipping rates for a cart:
1. Finds the shipping zone that contains the destination country (supports country names and ISO codes).
2. Calculates the total chargeable weight (uses volumetric weight if higher than actual weight for applicable providers).
3. Matches available rates by zone and weight.
4. Returns a list of provider options with pricing details.

### `InvoiceService`
Manages the full invoice lifecycle:
- Generates invoice numbers sequentially (`INV-YYYY-NNNNN`).
- Builds a snapshot of client data and financial breakdown.
- Generates a PDF of the invoice.
- Issues (finalises) a draft invoice.
- Voids an issued invoice with a reason; logs the action.

### `NotificationService`
Sends email notifications to clients at order status transitions:
- **Quotation Sent** — includes a checkout link for inquiry orders.
- **Processed** — order confirmation with total.
- **Dispatched** — includes tracking number and carrier name.
- **Delivered** — delivery confirmation.

Notification attempts are recorded in the `order_notifications` table (status: sent/failed, error message if failed).

### `AuditLogService`
Records every significant action taken on an order or invoice:
- Who performed the action (user).
- What changed (old values → new values as JSON).
- IP address of the request.
- Timestamp.

Actions tracked include: order created, status changed, item added/removed/updated, discount applied, shipping updated, invoice generated/issued/voided, payment recorded.

---

## API Endpoints

### PayPal Payment API (`/api`)

| Method | Route | Description |
|---|---|---|
| `POST` | `/api/paypal/orders` | Creates a PayPal order using the server-side grand total (never client-supplied). |
| `POST` | `/api/paypal/orders/{orderId}/capture` | Captures the PayPal payment; marks the order as paid and generates an invoice. |
| `POST` | `/api/orders/{order}/cancel-pending` | Cancels a pending unpaid order. |

---

## Database Models

| Model | Table | Description |
|---|---|---|
| `User` | `users` | Admin users with roles. Tracks last login time and IP. |
| `Role` | `roles` | Named roles (e.g., super_admin, admin, editor). |
| `Permission` | `permissions` | Granular permissions assigned to roles. |
| `Category` | `categories` | Top-level product categories. |
| `SubCategory` | `sub_categories` | Sub-categories nested under a category. |
| `Product` | `products` | Products with pricing, dimensions, images, and carousel flags. |
| `Client` | `clients` | Customer buyer profiles with auto-generated Buyer IDs. Soft-deleted. |
| `Order` | `orders` | Unified model for both inquiries and confirmed orders. Soft-deleted. |
| `OrderItem` | `order_items` | Line items inside an order with per-item discount support. |
| `OrderAuditLog` | `order_audit_logs` | Immutable audit trail for every order/invoice action. |
| `OrderNotification` | `order_notifications` | Log of email notifications sent for each order event. |
| `Invoice` | `invoices` | Invoice records linked to orders. Stores client and financial snapshots. Soft-deleted. |
| `ShippingZone` | `shipping_zones` | Groups of countries mapped to a region. |
| `ShippingProvider` | `shipping_providers` | Courier/carrier services. |
| `ShippingRate` | `shipping_rates` | Price per weight range per provider per zone. |
| `PaymentMethod` | `payment_methods` | Configurable list of payment gateways (stores API config as JSON). |
| `SiteSetting` | `site_settings` | Key-value store for all site configuration. Cached for performance. |
| `BlogPost` | `blog_posts` | Blog articles with full SEO field support. |
| `Inquiry` | `inquiries` | Legacy model for product inquiries (core system uses unified `Order` type). |

---

## Middleware

| Alias | Class | Purpose |
|---|---|---|
| `auth` | Laravel built-in | Requires authenticated session. |
| `role` | `CheckRole` | Requires the user to hold a specific role. |
| `permission` | `CheckPermission` | Requires a specific permission (checked via role). |
| `super_admin` | `IsSuperAdmin` | Restricts access to users with the `super_admin` role. |
| `admin` | `IsAdmin` | Restricts access to admin-level users. |
| `viewer.readonly` | `ViewerReadOnly` | Allows viewer roles to access the admin area in read-only mode. |
| *(global)* | `CheckMaintenanceMode` | Intercepts all public frontend requests when maintenance mode is active; serves the maintenance page instead. |

---

## Email Notifications

Automated emails are sent to clients at the following order events:

| Event | Template | Trigger |
|---|---|---|
| Quotation Sent | `quotation-sent.blade.php` | Admin moves order to "Quotation Sent" status |
| Order Processed | `order-processed.blade.php` | Admin moves order to "Processed" status |
| Order Dispatched | `order-dispatched.blade.php` | Admin moves order to "Dispatched" (includes tracking info) |
| Order Delivered | `order-delivered.blade.php` | Admin marks as "Delivered" or automated job fires |

---

## Automated Jobs

### `MarkOrderDeliveredJob`
A queued background job that automatically transitions dispatched orders to "Delivered" once the expected delivery date has passed. This job can be scheduled via Laravel's command scheduler.

---

## Seeding & Default Data

Running `php artisan db:seed` populates:
- **Roles**: `super_admin`, `admin`, `editor`, `viewer`, `operations_staff`, `finance`
- **Permissions**: All permissions listed above
- **Demo Users**:
  - `admin@ecom.com` — Super Admin
  - `admin.user@ecom.com` — Admin
  - `editor@ecom.com` — Editor
  - `viewer@ecom.com` — Viewer
- **Default Site Setting**: Site name set to "Handicraft Nepal NP"

---

## Key Design Decisions

- **Unified Order/Inquiry model**: Both inquiries and paid orders share the `orders` table, distinguished by a `type` field (`inquiry` or `order`). This simplifies the workflow where inquiries can be converted to real orders without data migration.
- **Client Snapshots**: When an order is paid, the client's address and financial totals are frozen into JSON columns (`client_snapshot`, `financial_snapshot`). This ensures invoices and records remain accurate even if the client profile is later updated.
- **Server-side PayPal amounts**: The PayPal integration always reads the order total from the database — never from the frontend request — preventing price manipulation.
- **Immutable Audit Logs**: The `order_audit_logs` table has no `updated_at` column; entries are write-once. This guarantees a tamper-evident paper trail.
- **Cached Site Settings**: All site settings are cached using `Cache::rememberForever` and invalidated only when a setting is updated, keeping the overhead of reading configuration minimal on every request.
