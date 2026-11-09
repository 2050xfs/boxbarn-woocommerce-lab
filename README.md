# Box Barn - WooCommerce Lab

A fake store that sells boxes, built on a real WordPress + WooCommerce install.
Made for hands-on learning: every product type, shipping zone, tax rule, and
order status a working store uses, plus three deliberately broken items to
diagnose and fix.

## Run it in your browser (no install)

Click this link and WordPress Playground builds the whole store for you:

**https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/2050xfs/boxbarn-woocommerce-lab/main/boxbarn-blueprint.json**

It installs WordPress + WooCommerce + the Storefront theme, then runs
`seed.php` to create the catalog, tax, shipping, coupons, customers, and
orders. You land in a real WP Admin (auto-login: admin / password). Break
anything - reload and it resets.

## What's in the store

- 10 products: simple, variable (4 sizes), grouped, virtual/downloadable,
  a sale item, a low-stock item, a backorder item
- Texas 8.25% sales tax, two shipping zones (Texas / Rest of US),
  free shipping over $75, local pickup
- Bank transfer + cash on delivery gateways
- Coupons: BOXPRO5 (healthy) and MOVEDAY10 (see Ticket 1)
- 3 orders: completed, processing, on-hold

## The 3 starter tickets

1. **"Our promo code doesn't work"** - MOVEDAY10 fails at checkout.
2. **"Customers can't buy the X-Large box"** - one variation cannot be purchased.
3. **"The tape fell off the website"** - Packing Tape vanished from the shop
   but its direct link still works.

Fix paths are written up in the lab guide (LAB-GUIDE.html in the local lab
folder). `TROUBLESHOOTING-LOG.md` documents 7 real issues hit while building
the lab itself.

## Files

- `boxbarn-blueprint.json` - the Playground blueprint (theme + plugin + seed)
- `seed.php` - idempotent seed script; also rebuilds the local VM lab
- `TROUBLESHOOTING-LOG.md` - build issue log with diagnoses
