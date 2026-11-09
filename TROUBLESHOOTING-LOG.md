# Box Barn Lab - Build Troubleshooting Log

Real issues hit while building this lab on 2026-11-04, and how each was diagnosed.
This log is part of the training material: every one of these is a real-world
WordPress/WooCommerce failure pattern.

## 1. "Your PHP installation appears to be missing the MySQL extension"
- **Symptom:** `wp core install` died immediately.
- **Diagnosis:** `php -m` showed no mysqli. WordPress checks for the extension
  before it ever looks at a database drop-in.
- **Fix:** `apt-get install php-mysql`. Lesson: read the exact error, check
  `php -m` / WooCommerce > Status before guessing.

## 2. Plugin downloads silently truncated (no End-Of-Central-Directory)
- **Symptom:** `wp plugin install woocommerce` said "plugin could not be
  found"; downloaded zips failed with "End-of-central-directory signature not
  found" in unzip and "File is not a zip file" in Python.
- **Diagnosis:** The file started with a valid `PK` header but had no EOCD
  record and was short (13.4 MB vs the real 18.1 MB). The network proxy was
  cutting large downloads mid-stream without an error.
- **Fix:** Resume-download loop with `curl -C -` until Python's zipfile could
  open the archive (WooCommerce took 7 passes). Lesson: a corrupt zip error
  usually means an incomplete download, not a bad plugin. Verify file size /
  zip integrity before debugging WordPress.

## 3. SQLite drop-in path abandoned
- **Symptom:** The sqlite-database-integration zip had the same truncation
  problem, and WP-CLI plugin install cannot run before a database exists.
- **Decision:** Pivoted to MariaDB, the production-standard stack for
  WooCommerce. Lesson: pick the stack the client actually runs; don't fight a
  dev-only shortcut.

## 4. Downloadable product rejected: "not located within an approved directory"
- **Symptom:** Seeding crashed with WC_Data_Exception when creating the
  downloadable PDF product.
- **Diagnosis:** WooCommerce validates download file paths against its
  Approved Download Directories list (a security feature added after stores
  got hacked via arbitrary file downloads).
- **Fix:** Moved the file into `wp-content/uploads/woocommerce_uploads/`.
  Lesson: when WooCommerce refuses something, it is often a deliberate
  security control, not a bug.

## 5. Fatal: Call to undefined method WC_Shipping_Zone::add_zone_location()
- **Symptom:** Seed script fataled during shipping-zone setup.
- **Diagnosis:** The method was renamed in current WooCommerce; the current
  API is `add_location()`, and method settings are written via
  `update_option('woocommerce_{method}_{instance_id}_settings', ...)`.
- **Fix:** Rewrote the zone block against the current API. Lesson: code
  written for an older WooCommerce version breaks quietly after updates -
  check the current class reference, and always test after plugin updates.

## 6. "sendmail: not found" - order emails never send
- **Symptom:** Every order creation logged `sh: 1: /usr/sbin/sendmail: not found`.
- **Diagnosis:** WordPress mail depends on the server having a mail transport.
  No MTA on this box = no order emails, password resets, or notifications.
  This is the #1 "my store emails stopped working" ticket in real life.
- **Status:** Left broken ON PURPOSE as Lab Drill 6 (fix with an SMTP plugin
  or mail logging). On a real host this is solved with an SMTP plugin +
  authenticated sending domain, never by "the host should handle it."

## 7. Public tunnel blocked by the sandbox proxy
- **Symptom:** cloudflared quick tunnel failed with "tls: first record does
  not look like a TLS handshake" to api.trycloudflare.com.
- **Diagnosis:** This VM's egress proxy intercepts TLS; cloudflared cannot
  negotiate through it.
- **Workaround:** The lab runs locally on the VM (WP-CLI drills + agent-driven
  admin), and Eddie's clickable copy runs via WordPress Playground, which
  executes the same seed in his browser.

## Verified healthy at handoff
- Shop, product, cart pages return 200; checkout redirects to cart when empty
  (normal WooCommerce behavior).
- Tax math verified: $36.39 cart + 8.25% TX tax = $39.39 order total.
- 10 products (simple / variable / grouped / virtual-downloadable), 4 product
  categories, TX + Rest-of-US shipping zones, BACS + COD gateways, 2 coupons,
  2 test customers, 3 orders (completed / processing / on-hold).
