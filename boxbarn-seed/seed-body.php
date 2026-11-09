<?php
// Generated from ../seed.php - do not edit by hand.

// Box Barn seed script - run with: wp eval-file seed.php
// Idempotent-ish: skips product creation if a product with the same SKU exists.

function bb_log($m){ error_log('[boxbarn-seed] '.$m); $GLOBALS['bb_seed_log'][] = $m; }
function bb_product_by_sku($sku){
    $id = wc_get_product_id_by_sku($sku);
    return $id ? wc_get_product($id) : null;
}

// ---------- Store settings ----------
update_option('woocommerce_store_address', '1200 Commerce St');
update_option('woocommerce_store_city', 'Dallas');
update_option('woocommerce_default_country', 'US:TX');
update_option('woocommerce_store_postcode', '75202');
update_option('woocommerce_currency', 'USD');
update_option('timezone_string', 'America/Chicago');
update_option('woocommerce_calc_taxes', 'yes');
update_option('woocommerce_prices_include_tax', 'no');
update_option('woocommerce_enable_coupons', 'yes');
update_option('woocommerce_manage_stock', 'yes');
update_option('woocommerce_notify_low_stock_amount', 5);
update_option('permalink_structure', '/%postname%/');

// ---------- Tax rate: Texas 8.25% ----------
global $wpdb;
$existing = $wpdb->get_var("SELECT tax_rate_id FROM {$wpdb->prefix}woocommerce_tax_rates WHERE tax_rate_state='TX' LIMIT 1");
if (!$existing) {
    WC_Tax::_insert_tax_rate(array(
        'tax_rate_country'  => 'US',
        'tax_rate_state'    => 'TX',
        'tax_rate'          => '8.2500',
        'tax_rate_name'     => 'TX Sales Tax',
        'tax_rate_priority' => 1,
        'tax_rate_compound' => 0,
        'tax_rate_shipping' => 1,
        'tax_rate_order'    => 0,
        'tax_rate_class'    => '',
    ));
    bb_log('Tax rate TX 8.25% created');
}

// ---------- Categories ----------
$cats = array('Moving Boxes', 'Shipping Boxes', 'Storage & Wardrobe', 'Packing Supplies', 'Moving Kits');
$cat_ids = array();
foreach ($cats as $c) {
    $term = term_exists($c, 'product_cat');
    if (!$term) { $term = wp_insert_term($c, 'product_cat'); }
    $cat_ids[$c] = is_array($term) ? $term['term_id'] : $term;
}
bb_log('Categories ready');

// ---------- Attribute: Size ----------
$attr_id = wc_attribute_taxonomy_id_by_name('size');
if (!$attr_id) {
    $attr_id = wc_create_attribute(array('name' => 'Size', 'slug' => 'size', 'type' => 'select', 'order_by' => 'menu_order', 'has_archives' => false));
    // register taxonomy so terms can be added
    register_taxonomy('pa_size', array('product'), array('hierarchical' => false));
}
foreach (array('Small', 'Medium', 'Large', 'X-Large') as $i => $size) {
    if (!term_exists($size, 'pa_size')) { wp_insert_term($size, 'pa_size', array('slug' => sanitize_title($size))); }
}
bb_log('Size attribute ready (id '.$attr_id.')');

// Helper to make a simple product
function bb_simple($args){
    if (bb_product_by_sku($args['sku'])) { bb_log('skip existing '.$args['sku']); return bb_product_by_sku($args['sku']); }
    $p = new WC_Product_Simple();
    $p->set_name($args['name']);
    $p->set_sku($args['sku']);
    $p->set_regular_price($args['price']);
    if (!empty($args['sale'])) { $p->set_sale_price($args['sale']); }
    $p->set_description($args['desc']);
    $p->set_short_description($args['short']);
    $p->set_category_ids(array($args['cat']));
    $p->set_manage_stock(true);
    $p->set_stock_quantity($args['stock']);
    $p->set_stock_status($args['stock'] > 0 ? 'instock' : 'outofstock');
    if (!empty($args['backorders'])) { $p->set_backorders($args['backorders']); }
    if (!empty($args['weight'])) { $p->set_weight($args['weight']); }
    if (!empty($args['dims'])) { $p->set_length($args['dims'][0]); $p->set_width($args['dims'][1]); $p->set_height($args['dims'][2]); }
    if (!empty($args['virtual'])) { $p->set_virtual(true); }
    if (!empty($args['downloadable'])) {
        $p->set_downloadable(true);
        $file = new WC_Product_Download();
        $file->set_name('Moving Labels PDF');
        $file->set_file(get_site_url() . '/wp-content/uploads/woocommerce_uploads/moving-labels.txt');
        $p->set_downloads(array($file));
        $p->set_download_limit(3);
        $p->set_download_expiry(30);
    }
    if (!empty($args['visibility'])) { $p->set_catalog_visibility($args['visibility']); }
    $id = $p->save();
    bb_log('created simple '.$args['sku'].' (#'.$id.')');
    return wc_get_product($id);
}

// A real file for the downloadable product
$up = wp_upload_dir();
if (!file_exists($up['basedir'] . '/woocommerce_uploads')) { mkdir($up['basedir'] . '/woocommerce_uploads', 0755, true); }
file_put_contents($up['basedir'] . '/woocommerce_uploads/moving-labels.txt', "BOX BARN - Printable Moving Labels (lab placeholder file)\nFRAGILE / KITCHEN / BEDROOM / BATH / LIVING ROOM / GARAGE\n");

bb_simple(array('sku'=>'BB-SMB-100','name'=>'Small Moving Box - Single','price'=>'2.49','stock'=>500,'weight'=>'1.1','dims'=>array('16','12','12'),'cat'=>$cat_ids['Moving Boxes'],'desc'=>'Classic small moving box for books, tools, and heavy items. Double-wall corrugated cardboard, 32 ECT.','short'=>'16x12x12 box for books and heavy items.'));
bb_simple(array('sku'=>'BB-HDS-24','name'=>'Heavy-Duty Shipping Box 24x18x18','price'=>'8.99','stock'=>180,'weight'=>'2.4','dims'=>array('24','18','18'),'cat'=>$cat_ids['Shipping Boxes'],'desc'=>'Double-wall 48 ECT shipping box built for freight and e-commerce fulfillment.','short'=>'Heavy-duty double-wall shipper.'));
bb_simple(array('sku'=>'BB-WRD-01','name'=>'Wardrobe Box with Hanging Bar','price'=>'14.99','stock'=>60,'weight'=>'3.8','dims'=>array('24','20','34'),'cat'=>$cat_ids['Storage & Wardrobe'],'desc'=>'Move hanging clothes straight from closet to box. Metal hanging bar included.','short'=>'Closet-to-box wardrobe mover with bar.'));
bb_simple(array('sku'=>'BB-BUB-175','name'=>'Bubble Cushion Roll - 175 ft','price'=>'19.99','sale'=>'15.99','stock'=>90,'weight'=>'2.0','dims'=>array('24','12','12'),'cat'=>$cat_ids['Packing Supplies'],'desc'=>'Perforated bubble cushion roll, 175 feet, 3/16 inch bubbles. On sale this week.','short'=>'175 ft perforated bubble roll. (Sale item)'));
$tape = bb_simple(array('sku'=>'BB-TAPE-6','name'=>'Packing Tape - 6 Pack','price'=>'11.49','stock'=>240,'weight'=>'1.5','dims'=>array('12','8','6'),'cat'=>$cat_ids['Packing Supplies'],'desc'=>'Six rolls of 2 inch acrylic packing tape, 110 yards total per pack.','short'=>'6 rolls, 110 yards each.','visibility'=>'hidden')); // PRE-BROKEN #3: hidden from catalog
bb_simple(array('sku'=>'BB-BLNKT-01','name'=>'Furniture Moving Blanket','price'=>'16.99','stock'=>3,'weight'=>'4.5','dims'=>array('72','80','1'),'cat'=>$cat_ids['Packing Supplies'],'desc'=>'Quilted 72x80 moving blanket for furniture protection. Low stock item.','short'=>'Quilted furniture blanket. Only a few left.'));
bb_simple(array('sku'=>'BB-DISH-01','name'=>'Dish Pack Kit with Inserts','price'=>'21.99','stock'=>0,'backorders'=>'yes','weight'=>'3.0','dims'=>array('24','12','12'),'cat'=>$cat_ids['Moving Kits'],'desc'=>'Cell inserts and foam pouches for dishes and glassware. Popular - currently on backorder.','short'=>'Dish kit on backorder (teaches backorder flow).'));
bb_simple(array('sku'=>'BB-LBL-PDF','name'=>'Printable Moving Labels - PDF Pack','price'=>'4.99','stock'=>999,'virtual'=>true,'downloadable'=>true,'cat'=>$cat_ids['Packing Supplies'],'desc'=>'Downloadable, printable room labels and inventory sheets. Virtual product - no shipping.','short'=>'Instant download. Virtual product.'));

// ---------- Variable product: Moving Box by Size ----------
if (!bb_product_by_sku('BB-MVB-VAR')) {
    $product = new WC_Product_Variable();
    $product->set_name('Moving Box - Choose Your Size');
    $product->set_sku('BB-MVB-VAR');
    $product->set_description('Our best-selling moving box in four sizes. Pick a size - price, weight, and dimensions change per variation, just like a real catalog.');
    $product->set_short_description('One product, four sizes. The variable-product lesson.');
    $product->set_category_ids(array($cat_ids['Moving Boxes']));
    $attribute = new WC_Product_Attribute();
    $attribute->set_id($attr_id);
    $attribute->set_name('pa_size');
    $attribute->set_options(array('Small','Medium','Large','X-Large'));
    $attribute->set_visible(true);
    $attribute->set_variation(true);
    $product->set_attributes(array($attribute));
    $pid = $product->save();

    $variations = array(
        array('size'=>'Small',   'sku'=>'BB-MVB-S',  'price'=>'2.49', 'stock'=>500, 'weight'=>'1.1', 'dims'=>array('16','12','12')),
        array('size'=>'Medium',  'sku'=>'BB-MVB-M',  'price'=>'3.99', 'stock'=>420, 'weight'=>'1.5', 'dims'=>array('18','18','16')),
        array('size'=>'Large',   'sku'=>'BB-MVB-L',  'price'=>'5.49', 'stock'=>260, 'weight'=>'1.9', 'dims'=>array('24','18','18')),
        array('size'=>'X-Large', 'sku'=>'BB-MVB-XL', 'price'=>'',     'stock'=>140, 'weight'=>'2.3', 'dims'=>array('24','24','24')), // PRE-BROKEN #2: no price
    );
    foreach ($variations as $v) {
        $var = new WC_Product_Variation();
        $var->set_parent_id($pid);
        $var->set_sku($v['sku']);
        $var->set_attributes(array('pa_size' => sanitize_title($v['size'])));
        if ($v['price'] !== '') { $var->set_regular_price($v['price']); }
        $var->set_manage_stock(true);
        $var->set_stock_quantity($v['stock']);
        $var->set_stock_status('instock');
        $var->set_weight($v['weight']);
        $var->set_length($v['dims'][0]); $var->set_width($v['dims'][1]); $var->set_height($v['dims'][2]);
        $var->save();
    }
    bb_log('variable product created (#'.$pid.')');
} else { bb_log('variable product exists, skipped'); }

// ---------- Grouped product: Moving Kit ----------
if (!bb_product_by_sku('BB-KIT-GRP')) {
    $children = array();
    foreach (array('BB-SMB-100','BB-HDS-24','BB-TAPE-6','BB-BUB-175') as $sku) { $cp = bb_product_by_sku($sku); if ($cp) $children[] = $cp->get_id(); }
    $g = new WC_Product_Grouped();
    $g->set_name('Starter Moving Kit - Build Your Bundle');
    $g->set_sku('BB-KIT-GRP');
    $g->set_description('Grouped product: customers pick quantities of each item. Teaches grouped product setup and its quirks.');
    $g->set_short_description('Grouped product demo.');
    $g->set_category_ids(array($cat_ids['Moving Kits']));
    $g->set_children($children);
    $gid = $g->save();
    bb_log('grouped product created (#'.$gid.')');
}

// ---------- Shipping zones ----------
$zones = WC_Shipping_Zones::get_zones();
$has_tx = false; $has_us = false;
foreach ($zones as $z) { if ($z['zone_name'] === 'Texas') $has_tx = true; if ($z['zone_name'] === 'Rest of US') $has_us = true; }
if (!$has_tx) {
    $zone = new WC_Shipping_Zone();
    $zone->set_zone_name('Texas');
    $zone->set_zone_order(0);
    $zone->add_location('US:TX', 'state');
    $zone->save();
    $flat = $zone->add_shipping_method('flat_rate');
    update_option('woocommerce_flat_rate_' . $flat . '_settings', array('title' => 'Texas Flat Rate', 'tax_status' => 'taxable', 'cost' => '6.95', 'type' => 'class'));
    $free = $zone->add_shipping_method('free_shipping');
    update_option('woocommerce_free_shipping_' . $free . '_settings', array('title' => 'Free Shipping (orders $75+)', 'requires' => 'min_amount', 'min_amount' => '75', 'ignore_discounts' => 'no'));
    $pickup = $zone->add_shipping_method('local_pickup');
    update_option('woocommerce_local_pickup_' . $pickup . '_settings', array('title' => 'Local Pickup - Dallas Warehouse', 'tax_status' => 'none', 'cost' => '0'));
    bb_log('Texas shipping zone created');
}
if (!$has_us) {
    $zone2 = new WC_Shipping_Zone();
    $zone2->set_zone_name('Rest of US');
    $zone2->set_zone_order(1);
    $zone2->add_location('US', 'country');
    $zone2->save();
    $flat2 = $zone2->add_shipping_method('flat_rate');
    update_option('woocommerce_flat_rate_' . $flat2 . '_settings', array('title' => 'US Flat Rate', 'tax_status' => 'taxable', 'cost' => '12.95', 'type' => 'class'));
    bb_log('Rest of US zone created');
}

// ---------- Payment gateways ----------
$gateways = get_option('woocommerce_bacs_settings', array()); update_option('woocommerce_bacs_settings', array_merge($gateways, array('enabled'=>'yes','title'=>'Direct bank transfer','description'=>'Pay by bank transfer. Orders ship after funds clear.','instructions'=>'Use your order number as the reference.')));
update_option('woocommerce_cod_settings', array('enabled'=>'yes','title'=>'Cash on delivery','description'=>'Pay cash at pickup or delivery.','enable_for_methods'=>array(),'enable_for_virtual'=>'no'));
bb_log('Gateways: BACS + COD enabled');

// ---------- Coupons ----------
global $wpdb; if (!$wpdb->get_var($wpdb->prepare("SELECT ID FROM {$wpdb->posts} WHERE post_title=%s AND post_type='shop_coupon'", 'MOVEDAY10'))) {
    $c = new WC_Coupon();
    $c->set_code('MOVEDAY10');
    $c->set_discount_type('percent');
    $c->set_amount(10);
    $c->set_description('Move Day promo - 10% off. (Lab note: expiry is set in the past on purpose - Ticket #1)');
    $c->set_date_expires(strtotime('-1 day')); // PRE-BROKEN #1: expired yesterday
    $c->set_usage_limit(100);
    $c->save();
    bb_log('Coupon MOVEDAY10 created (expired on purpose)');
}
if (!$wpdb->get_var($wpdb->prepare("SELECT ID FROM {$wpdb->posts} WHERE post_title=%s AND post_type='shop_coupon'", 'BOXPRO5'))) {
    $c2 = new WC_Coupon();
    $c2->set_code('BOXPRO5');
    $c2->set_discount_type('fixed_cart');
    $c2->set_amount(5);
    $c2->set_description('$5 off orders $50+ (healthy coupon for comparison)');
    $c2->set_minimum_amount(50);
    $c2->save();
    bb_log('Coupon BOXPRO5 created');
}

// ---------- Test customers ----------
foreach (array(array('maria.test@example.com','Maria','Tester'), array('james.test@example.com','James','Sample')) as $cu) {
    if (!email_exists($cu[0])) {
        $uid = wp_create_user($cu[0], wp_generate_password(12), $cu[0]);
        $u = new WP_User($uid); $u->set_role('customer');
        update_user_meta($uid, 'first_name', $cu[1]); update_user_meta($uid, 'last_name', $cu[2]);
        bb_log('customer created: '.$cu[0]);
    }
}

// ---------- Test orders in different statuses ----------
function bb_order($email, $sku_qty, $status, $note){
    foreach (wc_get_orders(array('billing_email'=>$email, 'limit'=>-1)) as $eo) {
        if ($eo->get_customer_note() === $note) { bb_log('order exists for '.$note); return; }
    }
    $order = wc_create_order();
    $user = get_user_by('email', $email);
    if ($user) $order->set_customer_id($user->ID);
    $order->set_billing_email($email);
    $order->set_billing_first_name('Test'); $order->set_billing_last_name('Customer');
    $order->set_billing_address_1('500 Elm St'); $order->set_billing_city('Dallas'); $order->set_billing_state('TX'); $order->set_billing_postcode('75202'); $order->set_billing_country('US');
    $order->set_shipping_address_1('500 Elm St'); $order->set_shipping_city('Dallas'); $order->set_shipping_state('TX'); $order->set_shipping_postcode('75202'); $order->set_shipping_country('US');
    $order->set_customer_note($note);
    foreach ($sku_qty as $sku => $qty) { $p = bb_product_by_sku($sku); if ($p) $order->add_product($p, $qty); }
    $order->set_payment_method('bacs');
    $order->set_payment_method_title('Direct bank transfer');
    $order->calculate_totals();
    $order->set_status($status);
    $order->save();
    bb_log('order #'.$order->get_id().' created, status '.$status);
}
bb_order('maria.test@example.com', array('BB-SMB-100'=>10, 'BB-TAPE-6'=>1), 'completed', 'LAB-ORDER-1 healthy completed order');
bb_order('james.test@example.com', array('BB-MVB-M'=>5), 'processing', 'LAB-ORDER-2 processing order');
bb_order('maria.test@example.com', array('BB-BUB-175'=>2), 'on-hold', 'LAB-ORDER-3 on-hold awaiting payment');

// WooCommerce pages + pretty permalinks refresh
WC_Install::create_pages();
flush_rewrite_rules();
bb_log('SEED DONE');
