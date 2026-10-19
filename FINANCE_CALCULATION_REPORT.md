# How the Finance Calculations Work Right Now

*A plain-language walkthrough, written from reading the code and the current database (19 Oct 2026). Nothing here is a proposal — it describes what the system does today, so you can compare it against what you actually want.*

---

## 1. The short version

The finance side is really **three separate things** that were built at different times and only partly talk to each other:

| # | Part | Question it answers | Where the numbers come from |
|---|------|---------------------|-----------------------------|
| A | **Payments (money in)** | "How much did customers actually pay us?" | `payments` table + `financial_ledger_entries` |
| B | **Profit calculation** | "After vendor, fees, CSR and data entry, how much did we keep on this order?" | `order_finance_snapshots`, calculated *by hand* per order |
| C | **Payables (money out)** | "Who do we still owe, and how much?" | `order_payables`, created *from the profit calculation* |

There's also an older **fourth** piece (a "commission entry" system) that still runs in the background but currently produces only zeros. More on that in section 6 — it's the main reason things feel like they're "not on the same page".

The most important thing to understand: **A does not feed B or C automatically.** Paying an order records the payment, but nobody is owed anything until someone opens the order's profit page and runs the calculation.

---

## 2. Part A — Money coming in (payments)

### How a payment gets recorded

There are two automatic ways, and one manual way:

1. **PayPal checkout** — customer pays → PayPal says `COMPLETED` → the order is marked paid → `recordSettledPayment(order, 'paypal', captureId)` runs.
2. **Admin clicks "Mark as paid"** — same function, but with method `cash`.
3. **Admin records a payment by hand** — creates a *pending* payment that someone must later *confirm*. Confirming it marks the order paid only once confirmed receipts cover the order total.

### What one recorded payment creates

For a settled payment (cases 1 and 2) the system, in a single all-or-nothing transaction:

- makes sure the order has an **issued invoice** (creates one if missing),
- creates a **`payments` row** — `confirmed`, with the invoice's grand total as the amount,
- converts it to NPR (the "reporting currency") using the **current forex rate**, and stores both amounts and the rate,
- writes a **ledger entry** (`payment_confirmed`) and an **audit entry**,
- refreshes the **invoice register** (paid / balance due / paid-in-full),
- queues a "payment received" notification,
- if the order is paid, creates the legacy commission snapshot (see section 6) and *tries* to sync payables (which does nothing without a profit calculation).

It skips (returns nothing) if the same reference was already recorded, or if confirmed payments already cover the invoice total. That's what stops a repeated PayPal callback from double counting.

### Worked example (real data, order #3)

Order total 300.00 USD, forex rate 152.24:

- `amount` = 300.00 USD
- `reporting_amount` = 300 × 152.24 = **45,672.00 NPR**

### Refunds and reversals

- A **refund** is a separate record (`refunds` table) that adds a negative ledger entry.
- A **reversal** creates a *new negative payment row* (status `reversed`) that points back at the original.
- Neither one changes the original payment's status. The original stays `confirmed`. (This matters — see Finding 2.)

---

## 3. Part B — The profit calculation

This is the heart of the "what did we make" logic. It lives in one small, database-free class (`OrderFinanceCalculator`) so the math is easy to test. All arithmetic is exact (no floating-point), and every money line is rounded to the cent **at the moment it's produced**, so the printed breakdown always adds up exactly.

### It is a manual step

An admin opens **Finance → order profit page**, picks a **vendor**, and confirms four inputs:

| Input | Default offered |
|-------|-----------------|
| Product price (USD) | order subtotal minus order-level discount |
| Shipping charged (USD) | the order's shipping cost |
| Forex rate (NPR per USD) | the current NRB rate |
| Actual shipping cost (NPR) | 0 |

Each calculation is saved as a new **immutable version** (`version 1, 2, 3…`). Old versions keep the rates they were calculated with, so changing a setting tomorrow never rewrites history. The newest version is the one every report uses.

### The rates it uses today

| Setting | Value now | Where it comes from |
|---------|-----------|---------------------|
| Gateway fee | **3.5 %** | Finance rate settings |
| CSR commission | 4 % | Finance rate settings |
| Data-entry commission | 10 % | Finance rate settings |
| Data-entry fixed rate | 150 NPR/USD | Finance rate settings |
| Data-entry threshold | 200 NPR/USD | Finance rate settings |
| Include shipping in commission | Yes | Finance rate settings |
| Vendor "HME" cost price | 50 % of price | Vendor record |
| Vendor "HME" commission | 20 % | Vendor record |

### The steps, in order

1. **Selling price (USD)** = product price + shipping
2. **Gateway fee (USD)** = selling price × gateway %
3. **Received (USD)** = selling price − gateway fee
4. **Received (NPR)** = received USD × forex rate
5. **Vendor payout (NPR):**
   - MRP in NPR = product price × forex rate *(shipping is not included)*
   - Cost price = MRP × vendor cost %
   - Vendor commission = **cost price** × vendor commission %
   - **Vendor total = cost price + vendor commission**
6. **Balance after vendor** = received NPR − vendor total
7. **Commission base (USD)** = product price + shipping (or product price alone if "include shipping" is off)
8. **CSR (NPR)** = base × CSR % × forex rate
9. **Data entry (NPR)** = base × data-entry % × a rate that is:
   - the real forex rate **if it's ≥ the threshold (200)**,
   - otherwise the **fixed rate (150)**
10. **Leftover** = balance after vendor − CSR − data entry
11. **Net profit** = leftover − actual shipping cost (NPR)

### Worked example (real data, order #3, calculated when gateway was 14 %)

| Step | Calculation | Result |
|------|-------------|--------|
| Selling price | 100 + 200 | 300.00 USD |
| Gateway fee | 14 % × 300 | 42.00 USD |
| Received | 300 − 42 | 258.00 USD |
| Received NPR | 258 × 152.24 | 39,277.92 |
| MRP NPR | 100 × 152.24 | 15,224.00 |
| Cost price | 50 % × 15,224 | 7,612.00 |
| Vendor commission | 20 % × 7,612 | 1,522.40 |
| **Vendor total** | 7,612 + 1,522.40 | **9,134.40** |
| Balance after vendor | 39,277.92 − 9,134.40 | 30,143.52 |
| CSR | 4 % × 300 = 12 USD × 152.24 | 1,826.88 |
| Data entry | 10 % × 300 = 30 USD × 150 | 4,500.00 |
| Leftover | 30,143.52 − 1,826.88 − 4,500 | 23,816.64 |
| **Net profit** | − 0 shipping cost | **23,816.64 NPR** |

I re-added every line of the stored snapshot and it matches the code exactly.

---

## 4. Part C — Money going out (payables)

Once an order is **paid** *and* has a **profit calculation**, the system creates three payables for it, all in NPR:

| Payee | Amount |
|-------|--------|
| Vendor | vendor total |
| CSR | CSR amount |
| Data entry | data-entry amount |

- Re-running the profit calculation **updates** these amounts. It refuses to drop an amount below what has already been paid.
- Payments against a payable can be recorded one at a time or in bulk. Status goes `unpaid → partial → paid`.
- The "outstanding payables" list on the dashboard only shows rows where `paid < amount`. Fully paid payables disappear from it — that's why it can look empty even though payables exist.

**Today's state:** 18 payables exist across 6 orders. Orders #3 and #4 are fully paid out. Orders #14, #16, #17, #18 are fully unpaid (I created their profit calculations today so they'd have payables).

---

## 5. What the Finance dashboard actually shows

| Card / table | Calculated from |
|--------------|-----------------|
| Confirmed receipts | **Sum of `payments` rows with status `confirmed`** (NPR) |
| Receipts / refunds / reversals (date filtered) | **Ledger entries** |
| Receipts chart | Ledger entries |
| Net profit, vendor / CSR / DE totals | Latest profit snapshot per paid, non-cancelled, non-refunded order |
| Outstanding payouts | Payables where paid < amount |
| Outstanding commissions | **Legacy `commission_entries`** (not the CSR/DE payables) |
| Sales details (gross, average) | Orders: type = order, paid, not cancelled/refunded — sum of `grand_total` |
| Recent payments *(added today)* | Latest 10 confirmed `payments` rows |

Notice that these use **four different sources** (payments table, ledger, snapshots, orders table). They agree when everything is clean, but nothing forces them to.

---

## 6. Where it's likely "not on the same page" — findings

These are things I noticed while tracing the code. I haven't changed any of them. Items 1–3 are the ones I'd look at first.

### Finding 1 — Two commission systems, and the old one is empty
There's the **new** system (CSR 4 % + data entry 10 % inside the profit calculation, creating payables) and an **older** one (`finance_settings.default_commission_value` → `finance_order_snapshots` → `commission_entries`). The old one is still triggered whenever an order is settled, but its default commission is **0.00**, so every commission entry is 0.00 and "Outstanding commissions" on the dashboard always reads 0 — even while CSR and data-entry money is genuinely owed in payables. If you consider CSR/DE "commissions", the dashboard card is measuring the wrong thing.

### Finding 2 — "Confirmed receipts" ignores refunds and reversals
That headline card adds up every payment still marked `confirmed`. A refund or reversal never changes the original payment's status, so after one happens the card **overstates** what you've received. The date-filtered receipts summary and the chart use the ledger instead, which *does* subtract them. So the headline number and the chart can disagree. (Currently no refunds or reversals exist, so today they match: NPR 209,985.60.)

### Finding 3 — Profit is separate from what was actually paid
The profit calculation uses the **price and shipping an admin types in**, not the payment that was received. Consequences:
- A paid order with no profit calculation contributes to receipts but **not** to profit or payables.
- If the order is edited later, or a partial refund happens, the profit snapshot doesn't change unless someone recalculates.
- "Received NPR" in the profit view (after gateway fee, at the admin's forex rate) will not equal the payment's "reporting amount" (full total, at the rate in force when the payment was recorded). Example, order #16: payment = 33,440.00 NPR; profit "received" = 32,320.55 NPR.

### Finding 4 — The gateway fee is a flat % on every order, whatever the payment method
The same fee (now 3.5 %) is deducted whether the customer paid by PayPal or the admin marked it as cash. Cash orders have no PayPal fee, so their profit is understated. (Also: the fee was 14 % when orders #3 and #4 were calculated and 3.5 % now, so those old snapshots deduct four times as much. That's by design — snapshots are frozen — but it makes older and newer orders hard to compare.)

### Finding 5 — The vendor is paid cost **plus** commission
Vendor total = 50 % of MRP **+** 20 % of that 50 % = 60 % of MRP. The comment in the code says "commission applies on cost price only, not MRP". It's worth confirming this is what you intend: is the vendor's "commission" something *we* pay *them* on top of cost, or something *they* pay us out of their sale? The code treats it as extra money paid to the vendor.

### Finding 6 — Data-entry rate is almost always the fixed 150
The rule is "use the real forex rate if it's ≥ 200, otherwise use 150". With the rate at 152.24, data entry is always converted at 150, never the real rate. Probably intentional (a protected rate), but it's an unusual rule and worth a sanity check.

### Finding 7 — Payables must be triggered by hand
Nothing creates payables when an order is paid. Someone must pick a vendor and calculate. Products in this store have no vendor field, so the system can't pick one itself. The dashboard shows a yellow "paid orders have no profit calculation" warning, but it's easy to miss.

### Finding 8 — Smaller things
- **PayPal amount isn't compared.** The recorded payment equals the *invoice total*, not the amount PayPal reports as captured. If they ever differ, the books follow the invoice.
- **Failure is quiet.** If recording the payment fails after a PayPal capture (e.g. no forex rate), the customer is charged and the order is paid, but no payment exists. I upgraded that log line to an error with the fix command; it still doesn't alert anyone.
- **Test-data typo.** Payment #2 has method `pappal`, so it won't group with other PayPal payments in method-based views.
- **Shipping cost is manual.** "Actual shipping cost (NPR)" defaults to 0 and is only ever entered by hand; until then profit counts all shipping you charged as profit.
- **Mixed order types.** Orders #14, #16 and #17 have type `inquiry` but are paid and in finance. The sales-details card counts only type `order`, so it will under-count those.

---

## 7. Questions to answer so we can line it up with what you want

1. **Should paying an order automatically create the profit calculation and payables**, with a default vendor, or stay a manual review step?
2. **Are CSR and data-entry "commissions"?** If yes, should "Outstanding commissions" read from payables, and should the old commission system be removed?
3. **Should the gateway fee apply only to PayPal orders?** (Cash/manual = 0 %?)
4. **Vendor payout:** is "cost + 20 % commission" correct, or should the vendor receive just the cost price (or just a percentage of MRP)?
5. **Should "Confirmed receipts" be net of refunds and reversals?**
6. **Which exchange rate should profit use** — the rate when the payment was recorded, or one an admin enters?
7. **Data-entry rate rule:** keep "150 unless forex ≥ 200", or use the real rate?
8. **Should shipping profit use the real shipping cost** — and where should it be entered?

---

## 8. What I did not verify

- I read the code and queried the database, and created and rolled back one test order to watch a "mark as paid" end to end. I did not click through the admin screens in a browser.
- I did not test a live PayPal capture; PayPal behaviour is from reading the code.
- I did not look at the older finance screens (invoices, refunds, loyalty) beyond how they connect to the above.
