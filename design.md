# Design System

This guide describes the styles currently loaded by the application and where they are used. The active shared theme is defined in `resources/css/app.css`; it is included by both `resources/views/layouts/app.blade.php` (storefront) and `resources/views/admin/layout.blade.php` (admin). Tailwind utility classes are used throughout Blade templates.

## Visual Direction

The interface combines a warm, paper-like background with terracotta actions, dark umber text, and restrained botanical/metallic accents. The storefront uses serif display typography and product imagery for a crafted feel. The admin is denser and more utilitarian, with the same shared tokens, a deep umber sidebar, compact controls, tables, and status badges.

## Typography

| Role | Font | Where it is used |
|---|---|---|
| Sans-serif / interface | Manrope, with system sans-serif fallbacks | Default body and UI text across storefront and admin. Loaded from Google Fonts in both shared layouts. |
| Serif / display | Noto Serif, with Georgia/Cambria fallbacks | Storefront headings and brand name; selected admin page titles, dashboard figures, and product/category headings via `font-serif`. |
| Email/PDF fallback | Arial/Helvetica and system sans-serif | Standalone email templates and invoice PDF, which use inline styles for compatibility with email clients and PDF rendering. |


The active font aliases are declared in `resources/css/app.css` (`--font-sans` and `--font-serif`). `tailwind.config.js` still lists Outfit and Playfair Display, but those values do not match the fonts loaded by the current layouts and should be treated as stale configuration, not as the active font specification. The maintenance and error layouts also retain older font declarations; see [Exceptions and Legacy Styles](#exceptions-and-legacy-styles).

## Color Palette

The active shared values below are CSS custom properties in `resources/css/app.css`. They use OKLCH notation. The aliases become Tailwind classes such as `bg-background`, `text-foreground`, `bg-primary`, and `border-border`.

| Token | Light theme | Dark theme | Purpose and common use |
|---|---|---|---|
| Background | `oklch(0.985 0.008 75)` | `oklch(0.18 0.015 40)` | Main page canvas (`bg-background`) in both shared layouts. |
| Foreground | `oklch(0.22 0.02 40)` | `oklch(0.96 0.01 75)` | Main text (`text-foreground`) and the dark umber/light text contrast pair. |
| Primary | `oklch(0.45 0.13 32)` | `oklch(0.70 0.14 35)` | Terracotta action and emphasis color (`bg-primary`, `text-primary`, focus rings). Used by buttons, selected filters, links, and highlights. |
| Secondary | `oklch(0.95 0.015 80)` | `oklch(0.28 0.02 40)` | Quiet surfaces and secondary controls. |
| Muted | `oklch(0.95 0.012 75)` | `oklch(0.28 0.02 40)` | Low-emphasis surfaces, placeholders, disabled and supporting content. |
| Accent | `oklch(0.90 0.04 80)` | `oklch(0.35 0.05 40)` | Saffron-tinted emphasis and active/hover surfaces. |
| Border / input | `oklch(0.90 0.015 70)` / `oklch(0.92 0.015 70)` | translucent white borders / inputs | Dividers, cards, form fields, and control boundaries. |
| Destructive | `oklch(0.55 0.22 27)` | `oklch(0.70 0.19 22)` | Error, delete, and destructive actions. |
| Sidebar | `oklch(0.20 0.02 40)` | `oklch(0.22 0.02 40)` | Deep umber admin navigation background, with light text. |

Chart tokens are also defined in `app.css`: terracotta, forest green, saffron, teal, and ruby. They are available for data visualization; the chart palette does not define a separate application-wide brand color.

### Where the palette appears

- Storefront page surfaces and typography use the shared background, foreground, card, border, and primary tokens through `resources/views/layouts/app.blade.php`, `resources/views/frontend/home.blade.php`, and the product/cart/blog views.
- The product catalog and product detail views use cream/paper surfaces, terracotta emphasis, serif titles, product imagery, and hover treatments. See `resources/views/frontend/home.blade.php`, `resources/views/frontend/products/show.blade.php`, and `resources/views/partials/product-carousel-card.blade.php`.
- The admin shell uses the shared page tokens and the dark sidebar. Its dashboard cards, selected navigation, notices, and focus states use the primary and muted tokens. See `resources/views/admin/layout.blade.php` and `resources/views/admin/dashboard.blade.php`.
- Order statuses use semantic badge colors defined by `Order::STATUS_COLORS` in `app/Models/Order.php`; badges are displayed in the admin order list/detail and client order history. These status colors are independent of the primary theme token.
- A few older admin templates still use named aliases and literal cream/beige/gold/green colors. They are compatibility styling and are not an exact match for the newer OKLCH theme; see the warning below.

## Surfaces, Shape, and Interaction

- The shared CSS radius base is `0.625rem` (10px), with small, medium, large, and extra-large radius aliases. Views also use Tailwind `rounded-lg`, `rounded-xl`, and `rounded-2xl` classes according to component size.
- Cards and panels typically use a card/background surface, a subtle border, and a restrained shadow. Repeated catalog cards may use a lift on hover; avoid applying that treatment to every control.
- Inputs use the shared input/border colors and a visible focus ring. `*:focus-visible` in `resources/css/app.css` adds a 2px outline using the ring token.
- The admin sidebar and content region use `custom-scroll` and `bg-paper`; for example, the main admin content in `resources/views/admin/layout.blade.php`.
- Motion helpers defined in `app.css` include `card-lift`, `shimmer-sweep`, `animate-fade-in-up`, `animate-scale-in`, `animate-slide-in-right`, `stagger-item`, `animate-float-up`, `shimmer`, and `skeleton-pulse`. Current examples include dashboard cards/modals, admin page entry/notices, and storefront product cards. Apply motion selectively and retain reduced visual noise in dense admin workflows.
- `bg-paper` provides a subtle layered warm texture; `text-gradient-warm`, `divider-ornament`, `dot-pattern`, and `glass` are additional opt-in decorative utilities. Use them only where presentational context benefits; the functional admin screens generally rely on solid surfaces.
- Print styles in `app.css` support invoices and other printable content via `.no-print` and `.print-only`.

## Dark Theme

A `.dark` token set and dark variants for selected utilities are defined in `resources/css/app.css`. The CSS supports a dark theme when a `.dark` class is applied to an ancestor. The shared layouts do not currently expose an obvious theme toggle, so treat dark-mode tokens as available styling support rather than assuming users can switch themes in the UI.

## Source of Truth and Legacy Styles

- For shared storefront/admin fonts, colors, radii, and CSS helpers, use `resources/css/app.css` and the shared layout files as the source of truth.
- `tailwind.config.js` contains older palette aliases (`truffle-*`, `cream`, `beige`, `gold`, and `green-premium`) and older font names (Outfit and Playfair Display). The active CSS theme also maps several compatibility class names, but some map to different colors than the legacy config suggests. In particular, do not assume `green-premium` or `truffle-*` means the hex value in `tailwind.config.js`; prefer semantic classes such as `bg-primary`, `text-foreground`, and `border-border` for new work.
- Older templates include literal values such as `#F5F2EA` and `#E8E2D2`; these are still used in places including legacy admin forms, product placeholders, and the maintenance page. Prefer the shared tokens for new UI so the palette stays consistent.
- `resources/views/maintenance.blade.php` uses Outfit/Playfair-era styling and hard-coded cream/green colors. `resources/views/errors/layout.blade.php` uses Nunito. Email templates and invoice PDFs intentionally use email/PDF-safe system fonts and several retain their own inline color palettes.
- `resources/views/welcome.blade.php` is the stock Laravel welcome page and uses its own compiled styles; it is not representative of the storefront or admin design system.

## Key Files

| File | Responsibility |
|---|---|
| `resources/css/app.css` | Active shared theme tokens, Tailwind theme aliases, base styles, paper texture, motion utilities, focus/selection, and print rules. |
| `resources/views/layouts/app.blade.php` | Storefront shell, Google Fonts loading, header/footer surfaces, and serif heading rules. |
| `resources/views/admin/layout.blade.php` | Admin shell, Manrope loading, sidebar, page surface, and shared admin navigation. |
| `tailwind.config.js` | Legacy Tailwind palette/font declarations; review against `app.css` before changing or relying on these values. |
| `app/Models/Order.php` | Semantic status labels/colors used for order-status badges. |
