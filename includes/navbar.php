<?php
$current_page = basename($_SERVER['PHP_SELF'] ?? '');
$user = current_user();
$site_name = get_setting('site_name', 'FireZone Store');
$unread_notifs = $user ? get_unread_notifications_count($user['id']) : 0;
?>
<header class="sticky top-0 z-50 bg-gaming-900/90 backdrop-blur-md border-b border-gaming-border">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            <!-- Brand Logo -->
            <a href="/index.php" class="flex items-center gap-3 group">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-red-700 via-red-600 to-amber-500 p-0.5 shadow-red-subtle group-hover:shadow-red-glow transition-all">
                    <div class="w-full h-full bg-gaming-950 rounded-[10px] flex items-center justify-center">
                        <svg class="w-6 h-6 text-red-500 group-hover:text-red-400 group-hover:scale-110 transition-transform" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2L1 21h22L12 2zm0 3.84L19.46 19H4.54L12 5.84zM11 10h2v4h-2zm0 6h2v2h-2z"/>
                        </svg>
                    </div>
                </div>
                <div class="flex flex-col">
                    <span class="font-gaming font-bold text-xl tracking-wider text-white group-hover:text-red-400 transition-colors flex items-center gap-1.5">
                        <?php echo e($site_name); ?>
                        <span class="text-xs font-sans px-1.5 py-0.5 rounded bg-red-950 text-red-400 border border-red-800/40 uppercase font-semibold">Store</span>
                    </span>
                    <span class="text-[10px] text-zinc-400 uppercase tracking-widest hidden sm:inline">Official Top-Up Panel</span>
                </div>
            </a>

            <!-- Desktop Navigation Links -->
            <nav class="hidden md:flex items-center gap-1">
                <a href="/index.php" class="px-3.5 py-2 rounded-lg text-sm font-medium transition-colors <?php echo $current_page === 'index.php' ? 'text-red-400 bg-red-950/40 border border-red-900/30' : 'text-zinc-300 hover:text-white hover:bg-gaming-800'; ?>">
                    Home
                </a>
                <a href="/products.php" class="px-3.5 py-2 rounded-lg text-sm font-medium transition-colors <?php echo $current_page === 'products.php' || $current_page === 'product.php' ? 'text-red-400 bg-red-950/40 border border-red-900/30' : 'text-zinc-300 hover:text-white hover:bg-gaming-800'; ?>">
                    Products
                </a>
                <a href="/reseller.php" class="px-3 py-2 rounded-lg text-sm font-medium transition-colors <?php echo $current_page === 'reseller.php' ? 'text-red-400 bg-red-950/40 border border-red-900/30' : 'text-zinc-300 hover:text-white hover:bg-gaming-800'; ?> flex items-center gap-1.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span> Reseller B2B
                </a>

                <?php if ($user): ?>
                    <a href="/dashboard.php" class="px-3.5 py-2 rounded-lg text-sm font-medium transition-colors <?php echo $current_page === 'dashboard.php' ? 'text-red-400 bg-red-950/40 border border-red-900/30' : 'text-zinc-300 hover:text-white hover:bg-gaming-800'; ?>">
                        Dashboard
                    </a>
                    <a href="/orders.php" class="px-3.5 py-2 rounded-lg text-sm font-medium transition-colors <?php echo $current_page === 'orders.php' || $current_page === 'order-details.php' ? 'text-red-400 bg-red-950/40 border border-red-900/30' : 'text-zinc-300 hover:text-white hover:bg-gaming-800'; ?>">
                        My Orders
                    </a>
                    <a href="/tickets.php" class="px-3.5 py-2 rounded-lg text-sm font-medium transition-colors <?php echo $current_page === 'tickets.php' || $current_page === 'ticket-view.php' || $current_page === 'ticket-create.php' ? 'text-red-400 bg-red-950/40 border border-red-900/30' : 'text-zinc-300 hover:text-white hover:bg-gaming-800'; ?>">
                        Support
                    </a>
                <?php endif; ?>
            </nav>

            <!-- Right Actions (User Profile / Wallet / Notifs / Auth) -->
            <div class="hidden md:flex items-center gap-3">
                <?php if ($user): ?>
                    <!-- Notification Bell -->
                    <a href="/notifications.php" class="relative p-2 rounded-lg bg-gaming-800 border border-gaming-border hover:border-red-600/40 text-zinc-300 hover:text-white transition-all" title="Notifications">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                        </svg>
                        <?php if ($unread_notifs > 0): ?>
                            <span class="absolute -top-1 -right-1 w-4 h-4 rounded-full bg-red-600 text-[10px] font-bold text-white flex items-center justify-center animate-pulse">
                                <?php echo min(9, $unread_notifs); ?>
                            </span>
                        <?php endif; ?>
                    </a>

                    <!-- Wallet Badge -->
                    <a href="/wallet.php" class="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-gaming-800 border border-gaming-border hover:border-red-600/50 hover:bg-gaming-750 transition-all group">
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                        <span class="text-xs text-zinc-400">Wallet:</span>
                        <span class="text-sm font-bold font-gaming text-emerald-400 group-hover:text-emerald-300">
                            <?php echo format_currency($user['wallet_balance']); ?>
                        </span>
                        <span class="text-xs text-red-400 font-bold bg-red-950/80 px-1 rounded border border-red-900/40">+</span>
                    </a>

                    <!-- User Dropdown Menu -->
                    <div class="relative" id="userMenuWrapper">
                        <button type="button" id="userMenuBtn" class="flex items-center gap-2 p-1.5 pr-3 rounded-lg bg-gaming-800 border border-gaming-border hover:border-red-600/40 text-sm text-zinc-200 transition-all">
                            <div class="w-7 h-7 rounded-md bg-gradient-to-br from-red-600 to-zinc-800 flex items-center justify-center font-bold text-white text-xs">
                                <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                            </div>
                            <span class="font-medium truncate max-w-[100px]"><?php echo e($user['username']); ?></span>
                            <svg class="w-4 h-4 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div id="userMenuDropdown" class="hidden absolute right-0 mt-2 w-52 rounded-xl bg-gaming-850 border border-gaming-border shadow-2xl py-1 z-50 animate-fade-in">
                            <div class="px-4 py-2 border-b border-gaming-border">
                                <p class="text-xs text-zinc-400">Signed in as</p>
                                <p class="text-sm font-semibold text-white truncate"><?php echo e($user['name']); ?></p>
                                <?php if (!empty($user['ff_uid'])): ?>
                                    <p class="text-[11px] text-red-400 font-mono mt-0.5">UID: <?php echo e($user['ff_uid']); ?></p>
                                <?php endif; ?>
                            </div>
                            <a href="/profile.php" class="block px-4 py-2 text-sm text-zinc-300 hover:text-white hover:bg-gaming-800">Profile & Player UID</a>
                            <a href="/wallet.php" class="block px-4 py-2 text-sm text-zinc-300 hover:text-white hover:bg-gaming-800">Wallet & Add Money</a>
                            <a href="/orders.php" class="block px-4 py-2 text-sm text-zinc-300 hover:text-white hover:bg-gaming-800">Order History</a>
                            <a href="/referral.php" class="block px-4 py-2 text-sm text-zinc-300 hover:text-white hover:bg-gaming-800 flex items-center justify-between">
                                <span>Refer & Earn</span>
                                <span class="text-[10px] bg-red-950 text-red-400 px-1.5 py-0.5 rounded font-mono">Bonus</span>
                            </a>
                            <a href="/reseller.php" class="block px-4 py-2 text-sm text-zinc-300 hover:text-white hover:bg-gaming-800">Reseller Dashboard</a>
                            <a href="/notifications.php" class="block px-4 py-2 text-sm text-zinc-300 hover:text-white hover:bg-gaming-800">Notifications</a>
                            <div class="border-t border-gaming-border my-1"></div>
                            <a href="/logout.php" class="block px-4 py-2 text-sm text-red-400 hover:text-red-300 hover:bg-red-950/40">Logout</a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="/login.php" class="px-4 py-2 rounded-lg text-sm font-medium text-zinc-300 hover:text-white hover:bg-gaming-800 transition-colors">
                        Login
                    </a>
                    <a href="/register.php" class="btn-gaming-red text-white font-medium px-4 py-2 rounded-lg text-sm transition-all shadow-red-subtle">
                        Register
                    </a>
                <?php endif; ?>

                <?php if (is_admin_logged_in()): ?>
                    <a href="/admin/index.php" class="px-2.5 py-1.5 rounded-lg text-xs font-semibold bg-red-950 text-red-300 border border-red-700/60 hover:bg-red-900 transition-all flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span> Admin Panel
                    </a>
                <?php endif; ?>
            </div>

            <!-- Mobile Hamburger Button -->
            <div class="flex md:hidden items-center gap-2">
                <?php if ($user): ?>
                    <a href="/notifications.php" class="relative p-1.5 rounded-lg bg-gaming-800 border border-gaming-border text-zinc-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                        </svg>
                        <?php if ($unread_notifs > 0): ?>
                            <span class="absolute -top-1 -right-1 w-3.5 h-3.5 rounded-full bg-red-600 text-[9px] font-bold text-white flex items-center justify-center">
                                <?php echo min(9, $unread_notifs); ?>
                            </span>
                        <?php endif; ?>
                    </a>
                    <a href="/wallet.php" class="flex items-center gap-1 px-2.5 py-1 rounded bg-gaming-800 border border-gaming-border text-xs font-bold text-emerald-400">
                        <span><?php echo format_currency($user['wallet_balance']); ?></span>
                    </a>
                <?php endif; ?>
                <button type="button" id="mobileMenuBtn" class="p-2 rounded-lg bg-gaming-800 border border-gaming-border text-zinc-300 hover:text-white focus:outline-none">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile Drawer Navigation -->
    <div id="mobileDrawer" class="hidden md:hidden bg-gaming-900 border-b border-gaming-border px-4 pt-2 pb-6 space-y-2">
        <?php if ($user): ?>
            <div class="p-3 mb-2 rounded-xl bg-gaming-850 border border-gaming-border flex items-center justify-between">
                <div>
                    <p class="text-xs text-zinc-400">Logged in as</p>
                    <p class="text-sm font-bold text-white"><?php echo e($user['username']); ?></p>
                </div>
                <div class="text-right">
                    <p class="text-[10px] text-zinc-400">Wallet</p>
                    <p class="text-sm font-gaming font-bold text-emerald-400"><?php echo format_currency($user['wallet_balance']); ?></p>
                </div>
            </div>
        <?php endif; ?>

        <a href="/index.php" class="block px-3 py-2 rounded-lg text-base font-medium <?php echo $current_page === 'index.php' ? 'text-red-400 bg-red-950/40' : 'text-zinc-300 hover:text-white hover:bg-gaming-800'; ?>">
            Home
        </a>
        <a href="/products.php" class="block px-3 py-2 rounded-lg text-base font-medium <?php echo $current_page === 'products.php' ? 'text-red-400 bg-red-950/40' : 'text-zinc-300 hover:text-white hover:bg-gaming-800'; ?>">
            Products
        </a>
        <a href="/reseller.php" class="block px-3 py-2 rounded-lg text-base font-medium <?php echo $current_page === 'reseller.php' ? 'text-red-400 bg-red-950/40' : 'text-zinc-300 hover:text-white hover:bg-gaming-800'; ?>">
            Reseller Portal
        </a>

        <?php if ($user): ?>
            <a href="/dashboard.php" class="block px-3 py-2 rounded-lg text-base font-medium <?php echo $current_page === 'dashboard.php' ? 'text-red-400 bg-red-950/40' : 'text-zinc-300 hover:text-white hover:bg-gaming-800'; ?>">
                Dashboard
            </a>
            <a href="/orders.php" class="block px-3 py-2 rounded-lg text-base font-medium <?php echo $current_page === 'orders.php' ? 'text-red-400 bg-red-950/40' : 'text-zinc-300 hover:text-white hover:bg-gaming-800'; ?>">
                My Orders
            </a>
            <a href="/wallet.php" class="block px-3 py-2 rounded-lg text-base font-medium <?php echo $current_page === 'wallet.php' ? 'text-red-400 bg-red-950/40' : 'text-zinc-300 hover:text-white hover:bg-gaming-800'; ?>">
                Wallet & Add Money
            </a>
            <a href="/referral.php" class="block px-3 py-2 rounded-lg text-base font-medium <?php echo $current_page === 'referral.php' ? 'text-red-400 bg-red-950/40' : 'text-zinc-300 hover:text-white hover:bg-gaming-800'; ?>">
                Refer & Earn
            </a>
            <a href="/notifications.php" class="block px-3 py-2 rounded-lg text-base font-medium <?php echo $current_page === 'notifications.php' ? 'text-red-400 bg-red-950/40' : 'text-zinc-300 hover:text-white hover:bg-gaming-800'; ?>">
                Notifications (<?php echo $unread_notifs; ?>)
            </a>
            <a href="/profile.php" class="block px-3 py-2 rounded-lg text-base font-medium <?php echo $current_page === 'profile.php' ? 'text-red-400 bg-red-950/40' : 'text-zinc-300 hover:text-white hover:bg-gaming-800'; ?>">
                Player Profile & UID
            </a>
            <a href="/tickets.php" class="block px-3 py-2 rounded-lg text-base font-medium <?php echo $current_page === 'tickets.php' ? 'text-red-400 bg-red-950/40' : 'text-zinc-300 hover:text-white hover:bg-gaming-800'; ?>">
                Support Tickets
            </a>
            <div class="border-t border-gaming-border my-2"></div>
            <a href="/logout.php" class="block px-3 py-2 rounded-lg text-base font-medium text-red-400 hover:bg-red-950/40">
                Logout
            </a>
        <?php else: ?>
            <div class="pt-2 flex flex-col gap-2">
                <a href="/login.php" class="w-full text-center px-4 py-2.5 rounded-lg text-sm font-medium bg-gaming-800 text-white border border-gaming-border">
                    Login
                </a>
                <a href="/register.php" class="w-full text-center btn-gaming-red text-white font-medium px-4 py-2.5 rounded-lg text-sm">
                    Create Account
                </a>
            </div>
        <?php endif; ?>

        <?php if (is_admin_logged_in()): ?>
            <div class="border-t border-gaming-border my-2"></div>
            <a href="/admin/index.php" class="block px-3 py-2 rounded-lg text-base font-medium text-red-300 bg-red-950/60 border border-red-800/40">
                Admin Panel &rarr;
            </a>
        <?php endif; ?>
    </div>
</header>

<script>
// Toggle user dropdown on desktop
const userBtn = document.getElementById('userMenuBtn');
const userDropdown = document.getElementById('userMenuDropdown');
if (userBtn && userDropdown) {
    userBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        userDropdown.classList.toggle('hidden');
    });
    document.addEventListener('click', () => {
        if (!userDropdown.classList.contains('hidden')) {
            userDropdown.classList.add('hidden');
        }
    });
}

// Toggle mobile menu drawer
const mobileBtn = document.getElementById('mobileMenuBtn');
const mobileDrawer = document.getElementById('mobileDrawer');
if (mobileBtn && mobileDrawer) {
    mobileBtn.addEventListener('click', () => {
        mobileDrawer.classList.toggle('hidden');
    });
}
</script>
