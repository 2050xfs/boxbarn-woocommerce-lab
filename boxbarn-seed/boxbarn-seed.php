<?php
/**
 * Plugin Name:       Box Barn Seed
 * Description:       One-time seeder for the Box Barn WooCommerce lab. Runs the store build (products, tax, shipping, coupons, customers, orders) on the first request after WooCommerce is active, records its log in an option, and never runs again. Safe to delete after seeding.
 * Version:           1.0.0
 * Requires Plugins:  woocommerce
 */

if (!defined('ABSPATH')) { exit; }

add_action('init', 'boxbarn_seed_maybe_run', 99);

function boxbarn_seed_maybe_run() {
    if (get_option('boxbarn_seeded')) { return; }
    if (!class_exists('WooCommerce')) { return; }

    // Claim the run before doing work so parallel requests cannot double-seed.
    update_option('boxbarn_seeded', 'running');

    $GLOBALS['bb_seed_log'] = array();
    try {
        include __DIR__ . '/seed-body.php';
        update_option('boxbarn_seed_log', $GLOBALS['bb_seed_log']);
        update_option('boxbarn_seeded', 'done');
    } catch (Throwable $e) {
        update_option('boxbarn_seed_log', $GLOBALS['bb_seed_log']);
        update_option('boxbarn_seed_error', $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
        update_option('boxbarn_seeded', 'failed');
    }
}

add_action('admin_notices', 'boxbarn_seed_notice');
function boxbarn_seed_notice() {
    $state = get_option('boxbarn_seeded');
    if ($state === 'done') {
        echo '<div class="notice notice-success"><p><strong>Box Barn Seed:</strong> store seeded. Products, tax, shipping, coupons, customers and orders are in. This plugin can be deleted now.</p></div>';
    } elseif ($state === 'failed') {
        echo '<div class="notice notice-error"><p><strong>Box Barn Seed FAILED:</strong> ' . esc_html(get_option('boxbarn_seed_error')) . '</p></div>';
    }
}
