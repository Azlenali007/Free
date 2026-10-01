<?php
$site_name = get_setting('site_name', 'FireZone Store');
$support_email = get_setting('support_email', 'support@firezonestore.com');
$support_whatsapp = get_setting('support_whatsapp', '+1 555 374 8391');
$support_hours = get_setting('support_hours', '24/7 Gamer Care');
?>
</main>

<footer class="bg-gaming-950 border-t border-gaming-border mt-20 pt-12 pb-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8 mb-10">
            <!-- Brand & Description -->
            <div class="md:col-span-1 space-y-4">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-tr from-red-600 to-amber-500 p-0.5">
                        <div class="w-full h-full bg-gaming-950 rounded-[6px] flex items-center justify-center">
                            <svg class="w-4 h-4 text-red-500" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 2L1 21h22L12 2zm0 3.84L19.46 19H4.54L12 5.84zM11 10h2v4h-2zm0 6h2v2h-2z"/>
                            </svg>
                        </div>
                    </div>
                    <span class="font-gaming font-bold text-lg text-white tracking-wider"><?php echo e($site_name); ?></span>
                </div>
                <p class="text-xs text-zinc-400 leading-relaxed">
                    Premium fast-delivery gaming store for direct player UID diamond top-ups, battle passes, and gaming items. 100% safe & trusted top-up service.
                </p>
                <div class="flex items-center gap-2 text-xs text-emerald-400">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span>Direct UID Top-Up Active (0-5 min)</span>
                </div>
            </div>

            <!-- Quick Links -->
            <div>
                <h4 class="font-gaming font-bold text-sm uppercase tracking-wider text-red-400 mb-4">Quick Navigation</h4>
                <ul class="space-y-2 text-xs text-zinc-400">
                    <li><a href="/index.php" class="hover:text-white transition-colors">Home Page</a></li>
                    <li><a href="/products.php" class="hover:text-white transition-colors">Diamond Store</a></li>
                    <li><a href="/dashboard.php" class="hover:text-white transition-colors">User Dashboard</a></li>
                    <li><a href="/orders.php" class="hover:text-white transition-colors">Track Orders</a></li>
                    <li><a href="/wallet.php" class="hover:text-white transition-colors">Wallet & Top-up</a></li>
                </ul>
            </div>

            <!-- Support & Contact -->
            <div>
                <h4 class="font-gaming font-bold text-sm uppercase tracking-wider text-red-400 mb-4">24/7 Gamer Support</h4>
                <ul class="space-y-2.5 text-xs text-zinc-400">
                    <li class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-red-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        <span><?php echo e($support_email); ?></span>
                    </li>
                    <li class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                        </svg>
                        <span>WhatsApp: <?php echo e($support_whatsapp); ?></span>
                    </li>
                    <li class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-amber-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span><?php echo e($support_hours); ?></span>
                    </li>
                    <li>
                        <a href="/tickets.php" class="inline-block mt-2 text-xs font-semibold text-red-400 hover:text-red-300 underline underline-offset-4">
                            Open a Support Ticket &rarr;
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Trust & Security -->
            <div>
                <h4 class="font-gaming font-bold text-sm uppercase tracking-wider text-red-400 mb-4">Security & Guarantee</h4>
                <div class="space-y-3 text-xs text-zinc-400">
                    <div class="flex items-start gap-2.5">
                        <svg class="w-4 h-4 text-emerald-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                        <span><strong>100% Safe UID Only:</strong> We never ask for your game password or login credentials.</span>
                    </div>
                    <div class="flex items-start gap-2.5">
                        <svg class="w-4 h-4 text-red-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                        <span><strong>Instant System:</strong> Real-time automated queue processing for diamonds.</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bottom Disclaimer & Copyright -->
        <div class="pt-6 border-t border-gaming-border/80 flex flex-col md:flex-row items-center justify-between gap-4 text-[11px] text-zinc-400">
            <p>&copy; <?php echo date('Y'); ?> <?php echo e($site_name); ?>. All rights reserved.</p>
            <p class="text-zinc-400 text-center md:text-right">
                Disclaimer: This store is an independent gaming service platform and is not affiliated with or endorsed by Garena Free Fire.
            </p>
        </div>
    </div>
</footer>

</body>
</html>
