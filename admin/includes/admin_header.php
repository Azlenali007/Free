<?php
require_once __DIR__ . '/admin_auth.php';
$site_name = get_setting('site_name', 'FireZone Store');
$admin = current_admin();
$current_page = basename($_SERVER['PHP_SELF'] ?? '');

// Counts for badges
$badge_pending_orders = $pdo->query("SELECT COUNT(*) FROM orders WHERE order_status = 'pending'")->fetchColumn();
$badge_pending_payments = $pdo->query("SELECT COUNT(*) FROM wallet_transactions WHERE status = 'pending'")->fetchColumn();
$badge_open_tickets = $pdo->query("SELECT COUNT(*) FROM support_tickets WHERE status = 'open'")->fetchColumn();
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

<!-- Sidebar Container -->
<aside id="adminSidebar" class="fixed inset-y-0 left-0 z-50 w-64 bg-gaming-900 border-r border-gaming-border flex flex-col justify-between transition-transform duration-200 -translate-x-full md:translate-x-0">
    <div>
        <!-- Brand Header -->
        <div class="h-16 flex items-center justify-between px-6 border-b border-gaming-border">
            <a href="/admin/index.php" class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg bg-red-600 flex items-center justify-center text-white font-gaming font-extrabold text-sm shadow-red-subtle">
                    FZ
                </div>
                <div class="flex flex-col">
                    <span class="font-gaming font-bold text-base text-white tracking-wider">ADMIN PANEL</span>
                    <span class="text-[10px] text-red-400 uppercase font-mono tracking-widest"><?php echo e($site_name); ?></span>
                </div>
            </a>
            <button type="button" id="closeSidebarBtn" class="md:hidden text-zinc-400 hover:text-white">✕</button>
        </div>

        <!-- Navigation Links -->
        <nav class="p-4 space-y-1.5 text-xs font-semibold">
            <!-- Dashboard -->
            <a href="/admin/index.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all <?php echo $current_page === 'index.php' ? 'bg-red-600 text-white shadow-red-subtle' : 'text-zinc-300 hover:bg-gaming-800 hover:text-white'; ?>">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                <span>Dashboard</span>
            </a>

            <!-- Orders -->
            <a href="/admin/orders.php" class="flex items-center justify-between px-3.5 py-2.5 rounded-xl transition-all <?php echo ($current_page === 'orders.php' || $current_page === 'order-details.php') ? 'bg-red-600 text-white shadow-red-subtle' : 'text-zinc-300 hover:bg-gaming-800 hover:text-white'; ?>">
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

            <!-- Products -->
            <a href="/admin/products.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all <?php echo ($current_page === 'products.php' || $current_page === 'product-add.php' || $current_page === 'product-edit.php') ? 'bg-red-600 text-white shadow-red-subtle' : 'text-zinc-300 hover:bg-gaming-800 hover:text-white'; ?>">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                <span>Products & Packs</span>
            </a>

            <!-- Users -->
            <a href="/admin/users.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all <?php echo $current_page === 'users.php' ? 'bg-red-600 text-white shadow-red-subtle' : 'text-zinc-300 hover:bg-gaming-800 hover:text-white'; ?>">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                <span>User Management</span>
            </a>

            <!-- Payments / Wallet Deposits -->
            <a href="/admin/payments.php" class="flex items-center justify-between px-3.5 py-2.5 rounded-xl transition-all <?php echo $current_page === 'payments.php' ? 'bg-red-600 text-white shadow-red-subtle' : 'text-zinc-300 hover:bg-gaming-800 hover:text-white'; ?>">
                <div class="flex items-center gap-3">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    <span>Payments / Wallet</span>
                </div>
                <?php if ($badge_pending_payments > 0): ?>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-red-500 text-white animate-pulse">
                        <?php echo $badge_pending_payments; ?>
                    </span>
                <?php endif; ?>
            </a>

            <!-- Support Tickets -->
            <a href="/admin/tickets.php" class="flex items-center justify-between px-3.5 py-2.5 rounded-xl transition-all <?php echo ($current_page === 'tickets.php' || $current_page === 'ticket-view.php') ? 'bg-red-600 text-white shadow-red-subtle' : 'text-zinc-300 hover:bg-gaming-800 hover:text-white'; ?>">
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

            <!-- Settings -->
            <a href="/admin/settings.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all <?php echo $current_page === 'settings.php' ? 'bg-red-600 text-white shadow-red-subtle' : 'text-zinc-300 hover:bg-gaming-800 hover:text-white'; ?>">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                <span>Store Settings</span>
            </a>
        </nav>
    </div>

    <!-- Admin Footer Navigation & Logout -->
    <div class="p-4 border-t border-gaming-border space-y-2">
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
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg bg-gradient-to-tr from-red-600 to-amber-500 p-0.5">
                    <div class="w-full h-full bg-gaming-950 rounded-[6px] flex items-center justify-center font-bold text-white text-xs">
                        <?php echo strtoupper(substr($admin['username'], 0, 1)); ?>
                    </div>
                </div>
                <div class="hidden sm:block text-left text-xs">
                    <p class="font-bold text-white"><?php echo e($admin['name'] ?: $admin['username']); ?></p>
                    <span class="text-[10px] text-red-400 font-mono">Super Admin</span>
                </div>
            </div>
        </div>
    </header>

    <?php require_once __DIR__ . '/../../includes/alerts.php'; ?>

    <main class="flex-1 p-4 sm:p-6 lg:p-8 space-y-6">
