<?php
require_once __DIR__ . '/admin_auth.php';
$site_name = get_setting('site_name', 'FireZone Store');
$admin = current_admin();
$current_page = basename($_SERVER['PHP_SELF'] ?? '');

// Counts for badges
$badge_pending_orders = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE order_status = 'pending'")->fetchColumn();
$badge_pending_payments = (int)$pdo->query("SELECT COUNT(*) FROM payments WHERE status = 'pending'")->fetchColumn();
$badge_pending_refunds = (int)$pdo->query("SELECT COUNT(*) FROM refunds WHERE status = 'pending'")->fetchColumn();
$badge_open_tickets = (int)$pdo->query("SELECT COUNT(*) FROM support_tickets WHERE status = 'open'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? e($page_title) . ' - Admin Panel' : 'FireZone Admin Panel'; ?></title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        gaming: {
                            950: '#07070a',
                            900: '#0a0a0f',
                            850: '#111118',
                            800: '#161622',
                            750: '#1e1e2d',
                            700: '#252538',
                            border: '#272738',
                            red: '#ef4444',
                            crimson: '#dc2626'
                        }
                    },
                    boxShadow: {
                        'red-glow': '0 0 20px rgba(239, 68, 68, 0.35)',
                        'red-subtle': '0 0 10px rgba(239, 68, 68, 0.15)',
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="bg-gaming-950 text-zinc-100 min-h-screen flex antialiased">

<!-- Mobile Overlay Backdrop -->
<div id="sidebarOverlay" class="fixed inset-0 bg-black/70 z-45 hidden md:hidden transition-opacity"></div>

<!-- Sidebar Container -->
<aside id="adminSidebar" class="fixed inset-y-0 left-0 z-50 w-64 bg-gaming-900 border-r border-gaming-border flex flex-col justify-between transition-transform duration-200 -translate-x-full md:translate-x-0 overflow-y-auto">
    <div>
        <!-- Brand Header -->
        <div class="h-16 flex items-center justify-between px-6 border-b border-gaming-border shrink-0">
            <a href="/admin/index.php" class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg bg-red-600 flex items-center justify-center text-white font-gaming font-extrabold text-sm shadow-red-subtle">
                    FZ
                </div>
                <div class="flex flex-col">
                    <span class="font-gaming font-bold text-base text-white tracking-wider">ADMIN PANEL</span>
                    <span class="text-[10px] text-red-400 uppercase font-mono tracking-widest"><?php echo e($site_name); ?></span>
                </div>
            </a>
            <button type="button" id="closeSidebarBtn" class="md:hidden text-zinc-400 hover:text-white p-1 rounded-lg hover:bg-gaming-800">✕</button>
        </div>

        <!-- Navigation Links -->
        <nav class="p-4 space-y-4 text-xs font-semibold">
            <!-- Core -->
            <div>
                <p class="text-[10px] uppercase font-mono text-zinc-400 px-3 mb-1.5 tracking-wider">Overview</p>
                <div class="space-y-1">
                    <a href="/admin/index.php" class="flex items-center gap-3 px-3 py-2 rounded-xl transition-all <?php echo ($current_page === 'index.php' || $current_page === 'dashboard.php') ? 'bg-red-600 text-white shadow-red-subtle' : 'text-zinc-300 hover:bg-gaming-800 hover:text-white'; ?>">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                        <span>Dashboard</span>
                    </a>
                    <a href="/admin/reports.php" class="flex items-center gap-3 px-3 py-2 rounded-xl transition-all <?php echo $current_page === 'reports.php' ? 'bg-red-600 text-white shadow-red-subtle' : 'text-zinc-300 hover:bg-gaming-800 hover:text-white'; ?>">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                        <span>Sales & Reports</span>
                    </a>
                </div>
            </div>

            <!-- Orders & Fulfillment -->
            <div>
                <p class="text-[10px] uppercase font-mono text-zinc-400 px-3 mb-1.5 tracking-wider">Orders & Refunds</p>
                <div class="space-y-1">
                    <a href="/admin/orders.php" class="flex items-center justify-between px-3 py-2 rounded-xl transition-all <?php echo ($current_page === 'orders.php' || $current_page === 'order-details.php') ? 'bg-red-600 text-white shadow-red-subtle' : 'text-zinc-300 hover:bg-gaming-800 hover:text-white'; ?>">
                        <div class="flex items-center gap-3">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                            <span>Orders</span>
                        </div>
                        <?php if ($badge_pending_orders > 0): ?>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-500 text-black">
                                <?php echo $badge_pending_orders; ?>
                            </span>
                        <?php endif; ?>
                    </a>
                    <a href="/admin/refunds.php" class="flex items-center justify-between px-3 py-2 rounded-xl transition-all <?php echo $current_page === 'refunds.php' ? 'bg-red-600 text-white shadow-red-subtle' : 'text-zinc-300 hover:bg-gaming-800 hover:text-white'; ?>">
                        <div class="flex items-center gap-3">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>
                            <span>Refunds</span>
                        </div>
                        <?php if ($badge_pending_refunds > 0): ?>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-red-500 text-white animate-pulse">
                                <?php echo $badge_pending_refunds; ?>
                            </span>
                        <?php endif; ?>
                    </a>
                </div>
            </div>

            <!-- Store Catalog -->
            <div>
                <p class="text-[10px] uppercase font-mono text-zinc-400 px-3 mb-1.5 tracking-wider">Catalog & Inventory</p>
                <div class="space-y-1">
                    <a href="/admin/products.php" class="flex items-center gap-3 px-3 py-2 rounded-xl transition-all <?php echo ($current_page === 'products.php' || $current_page === 'product-add.php' || $current_page === 'product-edit.php') ? 'bg-red-600 text-white shadow-red-subtle' : 'text-zinc-300 hover:bg-gaming-800 hover:text-white'; ?>">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                        <span>Products</span>
                    </a>
                    <a href="/admin/categories.php" class="flex items-center gap-3 px-3 py-2 rounded-xl transition-all <?php echo $current_page === 'categories.php' ? 'bg-red-600 text-white shadow-red-subtle' : 'text-zinc-300 hover:bg-gaming-800 hover:text-white'; ?>">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                        <span>Categories</span>
                    </a>
                    <a href="/admin/product-variants.php" class="flex items-center gap-3 px-3 py-2 rounded-xl transition-all <?php echo ($current_page === 'product-variants.php' || $current_page === 'variants.php') ? 'bg-red-600 text-white shadow-red-subtle' : 'text-zinc-300 hover:bg-gaming-800 hover:text-white'; ?>">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                        <span>Product Variants</span>
                    </a>
                </div>
            </div>

            <!-- Marketing & Promotions -->
            <div>
                <p class="text-[10px] uppercase font-mono text-zinc-400 px-3 mb-1.5 tracking-wider">Marketing & Deals</p>
                <div class="space-y-1">
                    <a href="/admin/flash-sales.php" class="flex items-center gap-3 px-3 py-2 rounded-xl transition-all <?php echo $current_page === 'flash-sales.php' ? 'bg-red-600 text-white shadow-red-subtle' : 'text-zinc-300 hover:bg-gaming-800 hover:text-white'; ?>">
                        <svg class="w-4 h-4 shrink-0 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        <span>Flash Sales</span>
                    </a>
                    <a href="/admin/coupons.php" class="flex items-center gap-3 px-3 py-2 rounded-xl transition-all <?php echo $current_page === 'coupons.php' ? 'bg-red-600 text-white shadow-red-subtle' : 'text-zinc-300 hover:bg-gaming-800 hover:text-white'; ?>">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/></svg>
                        <span>Coupons & Promos</span>
                    </a>
                    <a href="/admin/banners.php" class="flex items-center gap-3 px-3 py-2 rounded-xl transition-all <?php echo $current_page === 'banners.php' ? 'bg-red-600 text-white shadow-red-subtle' : 'text-zinc-300 hover:bg-gaming-800 hover:text-white'; ?>">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <span>Slider & Banners</span>
                    </a>
                    <a href="/admin/reviews.php" class="flex items-center gap-3 px-3 py-2 rounded-xl transition-all <?php echo $current_page === 'reviews.php' ? 'bg-red-600 text-white shadow-red-subtle' : 'text-zinc-300 hover:bg-gaming-800 hover:text-white'; ?>">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                        <span>Product Reviews</span>
                    </a>
                    <a href="/admin/referrals.php" class="flex items-center gap-3 px-3 py-2 rounded-xl transition-all <?php echo $current_page === 'referrals.php' ? 'bg-red-600 text-white shadow-red-subtle' : 'text-zinc-300 hover:bg-gaming-800 hover:text-white'; ?>">
                        <svg class="w-4 h-4 shrink-0 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        <span>Referral Program</span>
                    </a>
                    <a href="/admin/notifications.php" class="flex items-center gap-3 px-3 py-2 rounded-xl transition-all <?php echo $current_page === 'notifications.php' ? 'bg-red-600 text-white shadow-red-subtle' : 'text-zinc-300 hover:bg-gaming-800 hover:text-white'; ?>">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                        <span>Send Notifications</span>
                    </a>
                </div>
            </div>

            <!-- Finance & Gateways -->
            <div>
                <p class="text-[10px] uppercase font-mono text-zinc-400 px-3 mb-1.5 tracking-wider">Finance & Wallet (₹ INR)</p>
                <div class="space-y-1">
                    <a href="/admin/gateways.php" class="flex items-center gap-3 px-3 py-2 rounded-xl transition-all <?php echo $current_page === 'gateways.php' ? 'bg-red-600 text-white shadow-red-subtle' : 'text-zinc-300 hover:bg-gaming-800 hover:text-white'; ?>">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                        <span>Payment Gateways</span>
                    </a>
                    <a href="/admin/payments.php" class="flex items-center justify-between px-3 py-2 rounded-xl transition-all <?php echo $current_page === 'payments.php' ? 'bg-red-600 text-white shadow-red-subtle' : 'text-zinc-300 hover:bg-gaming-800 hover:text-white'; ?>">
                        <div class="flex items-center gap-3">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                            <span>Transactions</span>
                        </div>
                        <?php if ($badge_pending_payments > 0): ?>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-red-500 text-white animate-pulse">
                                <?php echo $badge_pending_payments; ?>
                            </span>
                        <?php endif; ?>
                    </a>
                    <a href="/admin/wallet.php" class="flex items-center gap-3 px-3 py-2 rounded-xl transition-all <?php echo ($current_page === 'wallet.php' || $current_page === 'wallet-adjust.php') ? 'bg-red-600 text-white shadow-red-subtle' : 'text-zinc-300 hover:bg-gaming-800 hover:text-white'; ?>">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                        <span>Wallet Audit & Adjustment</span>
                    </a>
                </div>
            </div>

            <!-- Users, Resellers & Providers -->
            <div>
                <p class="text-[10px] uppercase font-mono text-zinc-400 px-3 mb-1.5 tracking-wider">Users & Partners</p>
                <div class="space-y-1">
                    <a href="/admin/users.php" class="flex items-center gap-3 px-3 py-2 rounded-xl transition-all <?php echo $current_page === 'users.php' ? 'bg-red-600 text-white shadow-red-subtle' : 'text-zinc-300 hover:bg-gaming-800 hover:text-white'; ?>">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        <span>Users Directory</span>
                    </a>
                    <a href="/admin/resellers.php" class="flex items-center gap-3 px-3 py-2 rounded-xl transition-all <?php echo $current_page === 'resellers.php' ? 'bg-red-600 text-white shadow-red-subtle' : 'text-zinc-300 hover:bg-gaming-800 hover:text-white'; ?>">
                        <svg class="w-4 h-4 shrink-0 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        <span>Reseller Partners</span>
                    </a>
                    <a href="/admin/providers.php" class="flex items-center gap-3 px-3 py-2 rounded-xl transition-all <?php echo $current_page === 'providers.php' ? 'bg-red-600 text-white shadow-red-subtle' : 'text-zinc-300 hover:bg-gaming-800 hover:text-white'; ?>">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/></svg>
                        <span>Top-Up Provider APIs</span>
                    </a>
                    <a href="/admin/api.php" class="flex items-center gap-3 px-3 py-2 rounded-xl transition-all <?php echo $current_page === 'api.php' ? 'bg-red-600 text-white shadow-red-subtle' : 'text-zinc-300 hover:bg-gaming-800 hover:text-white'; ?>">
                        <svg class="w-4 h-4 shrink-0 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>
                        <span>Developer REST API</span>
                    </a>
                    <a href="/admin/tickets.php" class="flex items-center justify-between px-3 py-2 rounded-xl transition-all <?php echo ($current_page === 'tickets.php' || $current_page === 'ticket-view.php') ? 'bg-red-600 text-white shadow-red-subtle' : 'text-zinc-300 hover:bg-gaming-800 hover:text-white'; ?>">
                        <div class="flex items-center gap-3">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                            <span>Support Tickets</span>
                        </div>
                        <?php if ($badge_open_tickets > 0): ?>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-500 text-black">
                                <?php echo $badge_open_tickets; ?>
                            </span>
                        <?php endif; ?>
                    </a>
                </div>
            </div>

            <!-- Security, System & Settings -->
            <div>
                <p class="text-[10px] uppercase font-mono text-zinc-400 px-3 mb-1.5 tracking-wider">System & Security</p>
                <div class="space-y-1">
                    <a href="/admin/activity-logs.php" class="flex items-center gap-3 px-3 py-2 rounded-xl transition-all <?php echo $current_page === 'activity-logs.php' ? 'bg-red-600 text-white shadow-red-subtle' : 'text-zinc-300 hover:bg-gaming-800 hover:text-white'; ?>">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        <span>Admin Activity Logs</span>
                    </a>
                    <a href="/admin/security-logs.php" class="flex items-center gap-3 px-3 py-2 rounded-xl transition-all <?php echo $current_page === 'security-logs.php' ? 'bg-red-600 text-white shadow-red-subtle' : 'text-zinc-300 hover:bg-gaming-800 hover:text-white'; ?>">
                        <svg class="w-4 h-4 shrink-0 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                        <span>Security & Lockouts</span>
                    </a>
                    <a href="/admin/backup.php" class="flex items-center gap-3 px-3 py-2 rounded-xl transition-all <?php echo $current_page === 'backup.php' ? 'bg-red-600 text-white shadow-red-subtle' : 'text-zinc-300 hover:bg-gaming-800 hover:text-white'; ?>">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        <span>Database Backup</span>
                    </a>
                    <a href="/admin/settings.php" class="flex items-center gap-3 px-3 py-2 rounded-xl transition-all <?php echo $current_page === 'settings.php' ? 'bg-red-600 text-white shadow-red-subtle' : 'text-zinc-300 hover:bg-gaming-800 hover:text-white'; ?>">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <span>Store Settings</span>
                    </a>
                </div>
            </div>
        </nav>
    </div>

    <!-- Admin Footer Navigation & Logout -->
    <div class="p-4 border-t border-gaming-border space-y-2 shrink-0 bg-gaming-900">
        <a href="/index.php" target="_blank" class="flex items-center gap-2 px-3 py-2 rounded-lg bg-gaming-800 hover:bg-gaming-750 text-xs text-zinc-300 hover:text-white transition-colors">
            <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
            <span>View Live Store</span>
        </a>
        <a href="/admin/logout.php" class="flex items-center gap-2 px-3 py-2 rounded-lg text-xs font-semibold text-red-400 hover:bg-red-950/40 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
            <span>Admin Logout</span>
        </a>
    </div>
</aside>

<!-- Main Wrapper -->
<div class="flex-1 flex flex-col md:pl-64 min-h-screen">
    <!-- Top Bar -->
    <header class="h-16 bg-gaming-900/90 backdrop-blur border-b border-gaming-border sticky top-0 z-40 px-4 sm:px-6 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <button type="button" id="openSidebarBtn" class="md:hidden p-2 rounded-lg text-zinc-400 hover:text-white hover:bg-gaming-800">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
            <span class="text-xs text-zinc-400 hidden sm:inline">Store Console</span>
        </div>

        <div class="flex items-center gap-3">
            <a href="/admin/reports.php" class="hidden sm:flex items-center gap-1.5 text-xs text-zinc-300 hover:text-white bg-gaming-800 px-3 py-1.5 rounded-lg border border-gaming-border">
                <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                <span>Reports</span>
            </a>
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg bg-gradient-to-tr from-red-600 to-amber-500 p-0.5">
                    <div class="w-full h-full bg-gaming-950 rounded-[6px] flex items-center justify-center font-bold text-white text-xs">
                        <?php echo strtoupper(substr($admin['username'] ?? 'A', 0, 1)); ?>
                    </div>
                </div>
                <div class="hidden sm:block text-left text-xs">
                    <p class="font-bold text-white"><?php echo e($admin['name'] ?? $admin['username'] ?? 'Admin'); ?></p>
                    <span class="text-[10px] text-red-400 font-mono">Super Admin</span>
                </div>
            </div>
        </div>
    </header>

    <?php require_once __DIR__ . '/../../includes/alerts.php'; ?>

    <main class="flex-1 p-4 sm:p-6 lg:p-8 space-y-6">
