<?php
$page_title = "Reseller REST API Documentation";
require_once __DIR__ . '/includes/header.php';

$site_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . ($_SERVER['HTTP_HOST'] ?? 'localhost:3000');
?>

<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-10">
    <!-- Header -->
    <div class="border-b border-gaming-border pb-6">
        <div class="flex items-center gap-2 mb-2">
            <span class="w-2.5 h-2.5 rounded-full bg-emerald-400"></span>
            <span class="text-xs font-mono font-bold text-emerald-400 uppercase">Version 1.0 (Stable)</span>
        </div>
        <h1 class="font-gaming text-3xl font-extrabold text-white tracking-wide">RESELLER REST API DOCUMENTATION</h1>
        <p class="text-xs sm:text-sm text-zinc-400 mt-1">Integrate automated direct Free Fire UID top-ups into your Discord bot, Telegram bot, or external gaming website.</p>
    </div>

    <!-- Authentication Section -->
    <div class="p-6 rounded-2xl bg-gaming-850 border border-gaming-border space-y-4">
        <h2 class="font-gaming text-lg font-bold text-white tracking-wide">1. AUTHENTICATION HEADERS</h2>
        <p class="text-xs text-zinc-300 leading-relaxed">
            All API requests must include your authorized Reseller API credentials as HTTP headers. You can generate API keys directly in your <a href="/reseller.php" class="text-red-400 underline">Reseller Dashboard</a>.
        </p>

        <div class="p-4 rounded-xl bg-gaming-950 border border-gaming-border font-mono text-xs text-zinc-200 space-y-1">
            <p><span class="text-red-400">X-API-Key:</span> fz_live_your_api_key_here</p>
            <p><span class="text-red-400">X-API-Secret:</span> your_api_secret_here</p>
            <p><span class="text-zinc-500">Content-Type:</span> application/json</p>
        </div>

        <div class="text-[11px] text-zinc-400">
            <strong class="text-amber-400">Security Rule:</strong> Never expose your API keys or secret in public client-side JavaScript. All requests must be dispatched from your backend server.
        </div>
    </div>

    <!-- Endpoint 1: Get Products -->
    <div class="p-6 rounded-2xl bg-gaming-850 border border-gaming-border space-y-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2.5">
                <span class="px-2.5 py-1 rounded bg-blue-950 text-blue-400 border border-blue-800 font-mono font-bold text-xs">GET</span>
                <span class="font-mono text-white text-sm font-bold">/api/v1/products.php</span>
            </div>
            <span class="text-xs text-zinc-500 font-mono">Rate Limit: 60 req/min</span>
        </div>
        <p class="text-xs text-zinc-300">Retrieves the full catalog of active Free Fire diamond packages with your customized wholesale reseller pricing.</p>

        <div class="p-4 rounded-xl bg-gaming-950 border border-gaming-border font-mono text-xs text-zinc-300 overflow-x-auto">
            <p class="text-zinc-500 mb-2">// Sample JSON Response</p>
            <pre class="text-emerald-400 leading-relaxed">{
  "status": "success",
  "data": [
    {
      "id": 1,
      "name": "100 + 10 Diamonds",
      "diamonds_amount": 100,
      "bonus_diamonds": 10,
      "standard_price": 0.99,
      "reseller_price": 0.89,
      "variants": [
        {
          "id": 1,
          "name": "Single Delivery",
          "diamonds_amount": 100,
          "price": 0.89
        }
      ]
    }
  ]
}</pre>
        </div>
    </div>

    <!-- Endpoint 2: Check Balance -->
    <div class="p-6 rounded-2xl bg-gaming-850 border border-gaming-border space-y-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2.5">
                <span class="px-2.5 py-1 rounded bg-blue-950 text-blue-400 border border-blue-800 font-mono font-bold text-xs">GET</span>
                <span class="font-mono text-white text-sm font-bold">/api/v1/balance.php</span>
            </div>
            <span class="text-xs text-zinc-500 font-mono">Rate Limit: 60 req/min</span>
        </div>
        <p class="text-xs text-zinc-300">Checks your current reseller gaming wallet balance available for automated order fulfillment.</p>

        <div class="p-4 rounded-xl bg-gaming-950 border border-gaming-border font-mono text-xs text-zinc-300 overflow-x-auto">
            <pre class="text-emerald-400">{
  "status": "success",
  "username": "reseller_pro",
  "wallet_balance": 4500.00,
  "currency": "INR"
}</pre>
        </div>
    </div>

    <!-- Endpoint 3: Create Order -->
    <div class="p-6 rounded-2xl bg-gaming-850 border border-gaming-border space-y-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2.5">
                <span class="px-2.5 py-1 rounded bg-emerald-950 text-emerald-400 border border-emerald-800 font-mono font-bold text-xs">POST</span>
                <span class="font-mono text-white text-sm font-bold">/api/v1/order.php</span>
            </div>
            <span class="text-xs text-zinc-500 font-mono">Rate Limit: 30 req/min</span>
        </div>
        <p class="text-xs text-zinc-300">Places a real diamond top-up order. Automatically debits your reseller wallet and enqueues the top-up for instant direct UID delivery.</p>

        <div class="space-y-2">
            <span class="text-xs font-bold text-white">Parameters (JSON Body):</span>
            <div class="p-4 rounded-xl bg-gaming-950 border border-gaming-border font-mono text-xs text-zinc-300 overflow-x-auto">
                <pre class="text-amber-300">{
  "product_id": 1,
  "variant_id": 1,         // Optional
  "ff_uid": "1928371928",  // Required (Player ID)
  "ff_nickname": "Pro99",  // Optional
  "ff_region": "Global"    // Optional (default Global)
}</pre>
            </div>
        </div>

        <div class="space-y-2">
            <span class="text-xs font-bold text-white">Response:</span>
            <div class="p-4 rounded-xl bg-gaming-950 border border-gaming-border font-mono text-xs text-zinc-300 overflow-x-auto">
                <pre class="text-emerald-400">{
  "status": "success",
  "order_number": "FF-C91823-812",
  "order_status": "processing",
  "ff_uid": "1928371928",
  "diamonds_amount": 110,
  "charged_amount": 80.00,
  "remaining_balance": 4420.00
}</pre>
            </div>
        </div>
    </div>

    <!-- Endpoint 4: Check Order Status -->
    <div class="p-6 rounded-2xl bg-gaming-850 border border-gaming-border space-y-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2.5">
                <span class="px-2.5 py-1 rounded bg-blue-950 text-blue-400 border border-blue-800 font-mono font-bold text-xs">GET</span>
                <span class="font-mono text-white text-sm font-bold">/api/v1/order-status.php?order_number=FF-C91823-812</span>
            </div>
            <span class="text-xs text-zinc-500 font-mono">Rate Limit: 60 req/min</span>
        </div>
        <p class="text-xs text-zinc-300">Fetches the live status of an order (processing, completed, failed, or refunded).</p>

        <div class="p-4 rounded-xl bg-gaming-950 border border-gaming-border font-mono text-xs text-zinc-300 overflow-x-auto">
            <pre class="text-emerald-400">{
  "status": "success",
  "order_number": "FF-C91823-812",
  "order_status": "completed",
  "payment_status": "paid",
  "ff_uid": "1928371928",
  "provider_order_id": "PRV-A9812401",
  "created_at": "2026-10-01 08:30:00"
}</pre>
        </div>
    </div>

    <!-- Error Codes Table -->
    <div class="p-6 rounded-2xl bg-gaming-850 border border-gaming-border space-y-4">
        <h2 class="font-gaming text-lg font-bold text-white tracking-wide">ERROR CODES REFERENCE</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs font-mono">
                <thead>
                    <tr class="border-b border-gaming-border text-zinc-400 text-[10px]">
                        <th class="py-2.5">HTTP Code</th>
                        <th class="py-2.5">Error Message</th>
                        <th class="py-2.5">Description</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gaming-border/60">
                    <tr>
                        <td class="py-2.5 text-amber-400">401</td>
                        <td class="py-2.5 text-white">INVALID_API_KEY</td>
                        <td class="py-2.5 text-zinc-400">Missing or invalid X-API-Key / X-API-Secret headers.</td>
                    </tr>
                    <tr>
                        <td class="py-2.5 text-amber-400">402</td>
                        <td class="py-2.5 text-white">INSUFFICIENT_FUNDS</td>
                        <td class="py-2.5 text-zinc-400">Reseller wallet balance is lower than the wholesale package cost.</td>
                    </tr>
                    <tr>
                        <td class="py-2.5 text-amber-400">422</td>
                        <td class="py-2.5 text-white">INVALID_UID</td>
                        <td class="py-2.5 text-zinc-400">Free Fire UID is missing or not a valid numeric identifier.</td>
                    </tr>
                    <tr>
                        <td class="py-2.5 text-amber-400">429</td>
                        <td class="py-2.5 text-white">RATE_LIMIT_EXCEEDED</td>
                        <td class="py-2.5 text-zinc-400">Exceeded requests allowed per minute threshold.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
