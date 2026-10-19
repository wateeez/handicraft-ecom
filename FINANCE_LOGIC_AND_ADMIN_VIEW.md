# Finance Logic, Admin Screens and Processing Flow

*What the backend calculates, which of those numbers an admin can actually see, and how an order moves through the process from checkout to payout. Written from the code and the live database on 19 Oct 2026. It describes how things work today and doesn't propose changes.*

This is a companion to `FINANCE_CALCULATION_REPORT.md` (which focuses on problems found). This file is the "how it all works and who sees what" reference.

---

## Part 1 — The big picture

```
 CUSTOMER / ADMIN                BACKEND                               ADMIN SCREENS
 ────────────────                ───────                               ─────────────
 Order is created   ─────►  Order totals (subtotal, discounts,   ──►  Order page (totals)
                             shipping, grand total)

 Customer pays via PayPal ─┐
 Admin clicks "Mark paid" ─┼─► Payment (money in)                 ──►  Order page → Payments box
 Admin records payment ────┘   + Invoice + Ledger + Audit              Finance → Receipts / Recent payments
   (then confirms it)                                                   Finance → Confirmed receipts card

 Admin opens profit page ──►  Profit calculation (immutable      ──►  Order → Profit page (all 11 steps)
   picks vendor, confirms      versions)                              Finance → Profit tab
   price / shipping / forex                                           Finance → Net profit card
                                       │
                                       ▼
                              Payables: Vendor, CSR, Data entry ──►  Finance → Payouts tab
                              (money out, per order)                 Finance → Payouts outstanding card
                                       │
 Admin records payout ──────►  Payout payments                   ──►  Recent payout payments
```

Four stages, in order: **order totals → payment → profit calculation → payables/payouts**. Each stage only starts when the previous one is done, and *the move from payment to profit calculation is manual*.

---

## Part 2 — Every calculation, in the order it happens

### Stage 1 — Order totals (when an order is created or edited)

Done in `OrderService::recalculateTotals` and `OrderItem::calculateTotals`. All figures are in **USD**.

| Step | Formula |
|------|---------|
| Item subtotal | unit price × quantity |
| Item discount | percent: subtotal × % ÷ 100 · fixed: the value, capped at the subtotal · none: 0 |
| Line total | item subtotal − item discount (never below 0) |
| Order subtotal | sum of all line totals |
| Order discount | percent: subtotal × % ÷ 100 · fixed: the value, capped at the subtotal |
| **Grand total** | subtotal − order discount + shipping cost (never below 0) |
| Total weight | sum of (item weight × quantity) |

Shipping for the customer's checkout comes from `ShippingService`: it finds the destination country's zone, works out the **chargeable weight** (the higher of actual weight and volumetric weight), then looks up the rate band that weight falls in, per active shipping provider.

### Stage 2 — Payment (money in)

Done in `FinanceService`. The payment is always recorded against an **invoice**, and the amount is the **invoice's grand total**.

| Step | What happens |
|------|--------------|
| Invoice | An issued invoice is created if none exists, with a frozen copy of the order's figures (`financial_snapshot`) |
| Reporting amount | amount × forex rate = NPR (the "reporting currency") |
| Forex rate | NRB (Nepal Rastra Bank) USD sell rate, cached for 1 hour. If NRB can't be reached it falls back to the last rate an admin confirmed. If neither exists, **the payment fails and rolls back** (it will not guess) |
| Duplicate guard | Skipped if that PayPal capture ID is already recorded, or if confirmed payments already cover the invoice total |
| Ledger entry | `payment_confirmed` with the amount and NPR amount |
| Audit entry | Who, when, what, from which IP |
| Invoice register | `amount_paid`, `balance_due`, and status `unpaid` / `partially_paid` / `paid` |
| Notification | A "payment received" notification is queued |

Three ways a payment gets created:

1. **PayPal capture** — automatic once PayPal says `COMPLETED`.
2. **Admin "Mark as paid"** — records a `cash` payment for the full invoice total.
3. **Admin "Record payment"** — creates a *pending* payment; a second user with confirm permission approves it. When confirmed receipts (minus refunds) cover the order's grand total, the order flips to paid.

Refunds and reversals:
- A **refund** is its own record and adds a *negative* ledger entry. It can't exceed the original payment.
- A **reversal** adds a *negative payment row* and a negative ledger entry. The original payment keeps its `confirmed` status.

### Stage 3 — Profit calculation (the 11 steps)

Done in `OrderFinanceCalculator` — pure math, no database. Exact decimal arithmetic; each money line is rounded half-up to 2 decimals the moment it is produced, and later lines are built from the rounded numbers, so the printed breakdown always adds up to the cent.

**Inputs** (typed or confirmed by an admin on the profit page, plus saved settings):

| Input | Source |
|-------|--------|
| Product price (USD) | Admin (default: order subtotal − order discount) |
| Shipping charged (USD) | Admin (default: order shipping cost) |
| Forex rate | Admin (default: NRB rate; "Get latest" button refreshes it) |
| Actual shipping cost (NPR) | Admin, optional (default 0) |
| Vendor | Admin picks an active vendor |
| Vendor cost price % and commission % | The vendor's record (currently HME: 50 % / 20 %) |
| Gateway fee %, CSR %, data-entry %, data-entry fixed rate, threshold, include-shipping flag | Finance rate settings (currently 3.5 / 4 / 10 / 150 / 200 / yes) |

**Steps:**

| # | Line | Formula |
|---|------|---------|
| 1 | Selling price (USD) | product price + shipping |
| 2 | Gateway fee (USD) | selling price × gateway % |
| 3 | Received (USD) | selling price − gateway fee |
| 4 | Received (NPR) | received USD × forex |
| 5a | MRP (NPR) | product price × forex *(shipping excluded)* |
| 5b | Vendor cost price | MRP × vendor cost % |
| 5c | Vendor commission | **cost price** × vendor commission % |
| 5d | **Vendor total** | cost price + vendor commission |
| 6 | Balance after vendor | received NPR − vendor total |
| 7 | Commission base (USD) | product + shipping (if "include shipping" is on), else product only |
| 8a | CSR (USD) | base × CSR % |
| 8b | **CSR (NPR)** | CSR USD × forex |
| 9a | Data entry (USD) | base × data-entry % |
| 9b | Data-entry rate used | the real forex rate **if forex ≥ threshold (200)**, otherwise the **fixed rate (150)** |
| 9c | **Data entry (NPR)** | data-entry USD × rate used |
| 10 | Leftover | balance after vendor − CSR − data entry |
| 11 | **Net profit** | leftover − actual shipping cost (NPR) |

**Worked example — order #16 (calculated with today's rates):**

| Line | Calculation | Result |
|------|-------------|--------|
| Product / shipping | | 20.00 / 200.00 USD |
| 1 Selling price | 20 + 200 | 220.00 USD |
| 2 Gateway fee | 3.5 % × 220 | 7.70 USD |
| 3 Received | 220 − 7.70 | 212.30 USD |
| 4 Received NPR | 212.30 × 152.24 | 32,320.55 |
| 5a MRP | 20 × 152.24 | 3,044.80 |
| 5b Cost price | 50 % × 3,044.80 | 1,522.40 |
| 5c Vendor commission | 20 % × 1,522.40 | 304.48 |
| 5d **Vendor total** | | **1,826.88** |
| 6 Balance | 32,320.55 − 1,826.88 | 30,493.67 |
| 7 Commission base | 20 + 200 | 220.00 USD |
| 8 **CSR** | 4 % × 220 = 8.80 × 152.24 | **1,339.71** |
| 9 **Data entry** | 10 % × 220 = 22.00 × 150 | **3,300.00** |
| 10 Leftover | 30,493.67 − 1,339.71 − 3,300 | 25,853.96 |
| 11 **Net profit** | − 0 actual shipping | **25,853.96 NPR** |

**Versioning:** every calculation is saved as a new immutable version (v1, v2…). Each version stores the exact rates and vendor terms it used. Changing a setting later never alters old versions. Reports always read the **latest** version per order.

### Stage 4 — Payables and payouts (money out)

Done in `OrderPayoutService`. Created automatically at the end of a profit calculation, **only if the order is paid**:

| Payee | Owed amount |
|-------|-------------|
| Vendor | step 5d vendor total |
| CSR | step 8b |
| Data entry | step 9c |

- Recalculating updates the owed amounts but **refuses to go below what's already been paid** to that payee.
- Recording a payout (single or bulk) adds a `payout_payments` row, raises `paid_amount`, and sets status: `unpaid` → `partial` → `paid`. Overpaying is blocked.
- Bulk payout pays the **full balance** of several selected payables for one payee type at once, all-or-nothing.
- Every payout writes an audit log entry.

---

## Part 3 — What admins can actually see

Permissions control who sees what:
- `view_finance` — can open the Finance overview and the profit pages (read-only).
- `manage_finance` — also changes rates, vendors, settings, runs profit calculations, records payouts.
- `record_payments` — can record a (pending) customer payment.
- `confirm_payments` — can confirm or reverse payments and confirm refunds.
- `manage_orders` / `manage_invoices` — can "Mark as paid".

### A. Order page (`Admin → Orders → an order`)

| Shown | Detail |
|-------|--------|
| Order totals | Items, discounts, shipping, grand total (USD) |
| **Payments box** (needs a finance permission) | Every payment on the order: amount, method, reference, status, **reporting amount in NPR**, who recorded/confirmed it. Confirm / Reverse buttons, and a "Record payment" form |
| **Profit link** | A "profit" button, **only for orders of type "order"** (not inquiries) |
| Mark as paid button | Records a cash payment for the full amount |

### B. Order profit page (`Finance → order → profit`)

This is the most complete view. It shows **every one of the 11 steps** with its formula note, exactly as stored at calculation time — product price, shipping, selling price, gateway fee and %, received USD/NPR, forex, MRP, cost price, vendor commission, vendor total, balance, commission base, CSR USD/NPR, data-entry USD / rate used / NPR, leftover, actual shipping cost, net profit.

It also shows:
- the **version badge** ("Latest"), when and by whom it was calculated, the vendor, and the invoice number;
- a **Payouts** box: for Vendor / CSR / Data entry — owed, paid, balance, payment history, and a "Record payment" form;
- a **Calculate / Recalculate** form (vendor, forex with a "Get latest" button, product price, shipping, actual shipping cost) — needs `manage_finance`;
- a **History** list of all versions with their net profit.

### C. Finance overview (`Admin → Finance`)

**Headline cards (always visible):**

| Card | Comes from |
|------|-----------|
| Confirmed receipts | Sum of confirmed `payments` (NPR) |
| Net profit | Sum of net profit from each order's latest snapshot (paid, not cancelled/refunded) |
| Payouts outstanding | Vendor + CSR + data entry payables still unpaid |
| Commission outstanding | Legacy `commission_entries` (currently always 0) |
| Invoices | Count of invoices |

**"Needs attention" banner** — appears when there are pending payments to confirm, or paid orders with no profit calculation yet (with buttons to open each).

**Six tabs:**

| Tab | Shows |
|-----|-------|
| **Overview** | Receipts trend chart, "where the money goes" (vendor / CSR / data entry / gateway / shipping / net), payout progress, receipts activity with a date filter (receipts, refunds, reversals, net) |
| **Receipts** | Date-filtered receipts, refunds, reversals totals and net |
| **Profit** | Net profit, received, orders calculated, average profit per order; net profit by order; monthly net profit; profit by vendor; recent profit calculations |
| **Payouts** | Owed / paid / balance per payee type; "Pay outstanding balances" bulk form; recent payout payments |
| **Receivables** | **Recent payments** (added today); unpaid balances by age (0–7, 8–30, 31+ days); largest unpaid orders |
| **Sales** | Paid sales count, gross sales, average sale, recent paid orders |

**Tools row (needs `manage_finance` / `record_payments`):** Finance settings (reporting currency, default commission, commission trigger, loyalty basis, and the profit & commission **rates**), client finance lookup, and record payment.

**Other admin screens:** Vendors list/add/edit (name, contact, cost price %, commission %, active), and a small JSON summary used by the finance page and client lookup.

### D. What is calculated but *not* shown to admins

| Calculated | Not visible because |
|------------|---------------------|
| Gateway fee in **NPR** per order | Only the USD fee is on the profit page; the NPR total appears only as a summed slice in "where the money goes" |
| Legacy commission snapshot and entry per order | Stored but there's no screen for individual entries; only the (zero) outstanding total |
| Ledger entries individually | Only summed into receipts / refunds / reversals |
| Finance audit log | No admin screen lists it |
| Invoice register (`paid`, `balance due`) | Used internally; the finance page computes its own unpaid balances |
| Notification log (`payment_received`) | No admin screen |
| Net profit per month for **months before the last 6** | The chart only goes back 6 months |
| Orders of type "inquiry" that were paid | The profit link is hidden on the order page (but the finance pages still include them) |

### E. What is shown to customers

Only the order total, the invoice (PDF), and the PayPal checkout. **No profit, vendor, CSR or data-entry figure is ever shown to customers.**

---

## Part 4 — Process, start to finish (what really happens)

**Scenario: a customer pays through PayPal.**

1. **Customer checks out.** The cart becomes a pending order. Totals come from Stage 1 and shipping from the shipping zone/weight rates.
2. **PayPal order is created** using the *database's* amount for the order (not anything the browser sends).
3. **Customer approves in the PayPal popup.** The backend captures the payment and confirms PayPal returned `COMPLETED`. If not, the order stays unpaid.
4. **Order is marked paid** inside a locked transaction (so a repeated callback can't double-process). Financials on the order are locked.
5. **Order moves from "unprocessed" to "processed"** and any source inquiry is marked converted.
6. **An invoice is created and issued.**
7. **The payment is recorded** (Stage 2): payment row, ledger, audit, invoice register.
8. **Confirmation email** goes to the customer; the session cart is cleared.
9. *Nothing else happens automatically.* There is no profit calculation and no payables yet.
10. **An admin sees the "paid order with no profit calculation" notice** on the Finance page, opens the order's profit page, picks the vendor, checks price / shipping / forex / actual shipping cost, and clicks **Calculate**.
11. **Profit snapshot v1 is stored** and the three **payables** are created (vendor, CSR, data entry).
12. **Later, an admin records payouts** against each payable (individually or in bulk). Balances shrink; status goes to `paid`.
13. **If a refund or reversal happens**, a negative ledger entry is added. The profit snapshot and payables are *not* automatically adjusted — an admin would have to recalculate.

**Scenario: admin creates an order in the backend and marks it paid.** Same as steps 4–7, except the payment is recorded as **cash** for the full invoice total, and the whole thing (mark paid + record payment) succeeds or fails together. Steps 9–13 are identical.

**Scenario: admin records a payment manually.** Creates a *pending* payment (no ledger entry yet). A different user confirms it → ledger + audit written → if confirmed receipts now cover the order total, the order is marked paid.

---

## Part 5 — Where each number lives (for verifying)

| Number | Table | Used by |
|--------|-------|---------|
| Order totals | `orders`, `order_items` | Order page, invoices, "sales" tab, PayPal amount |
| Money received | `payments` (+ `financial_ledger_entries`) | Order payments box, Confirmed receipts card, Recent payments |
| Receipts / refunds / reversals totals and chart | `financial_ledger_entries` | Overview, Receipts tab |
| Invoice paid/balance | `finance_invoices` | Internal |
| Profit figures | `order_finance_snapshots` | Profit page, Profit tab, Net profit card |
| Amounts owed to vendor / CSR / data entry | `order_payables` | Payouts tab, Payouts outstanding card |
| Payouts made | `payout_payments` | Recent payout payments, payable balances |
| Rates | `finance_rate_settings` (cached) | New profit calculations only |
| Vendor terms | `vendors` | New profit calculations only |
| Forex | NRB API (cached 1 h) → last confirmed rate | Payments (reporting amount) and profit default |
| Legacy commission | `finance_order_snapshots`, `commission_entries` | "Commission outstanding" card only |

**Two different exchange rates are in play:** payments use the rate in force when the payment was recorded; the profit calculation uses the rate an admin confirms on the profit page. They can differ, which is why a payment's NPR amount and a profit snapshot's "received" will not match exactly (the profit figure is also after the gateway fee).

---

## Part 6 — Current database snapshot (19 Oct 2026)

- **Payments:** 6 confirmed, total NPR 209,985.60 (orders 3, 4, 14, 16, 17, 18).
- **Profit snapshots:** one per paid order, all calculated with vendor HME. Orders #3 and #4 used a 14 % gateway fee (set at the time); #14, #16, #17, #18 were calculated today at 3.5 %.
- **Payables:** 18 rows. Orders #3 and #4 fully paid out; #14, #16, #17, #18 fully unpaid.
- **Legacy commission entries:** 4 rows, all 0.00.
- **Reporting currency:** NPR. **Forex rate:** 152.24 (NRB).

---

## Part 7 — Quick answers to likely questions

- **"Why don't I see payables for a paid order?"** Because the profit calculation hasn't been run, or all payables are already fully paid (the outstanding list hides those).
- **"Why is net profit lower/higher than the payment?"** Net profit is built from the typed-in price and shipping, minus fee, vendor, CSR and data entry. It is not the payment amount.
- **"Can I see the gateway fee in NPR on an order?"** Not directly; only USD on the profit page.
- **"Do old orders change if I change a rate?"** No. Rates apply to new calculations only. Recalculating an order creates a new version using today's rates.
- **"Does a refund reduce profit?"** Not automatically. It reduces receipts (via the ledger), but the profit snapshot stays until recalculated.

---

*Not verified: I did not click through the screens in a browser, did not run a live PayPal capture, and did not review the invoice PDF layout or the loyalty tier logic in detail.*
