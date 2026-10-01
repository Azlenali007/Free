<?php
$page_title = "B2B Reseller Portal & API";
require_once __DIR__ . '/includes/functions.php';
require_login();

$user = current_user();

// Handle Reseller activation or application
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'activate_reseller') {
    csrf_validate();
    $pdo->prepare("UPDATE users SET is_reseller = 1, reseller_level = 'main' WHERE id = ?")->execute([$user['id']]);
    set_flash('success', 'Congratulations! Your B2B Wholesale Reseller account is now active.');
    header("Location: /reseller.php");
    exit;
}

// Handle Generate API Key
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'generate_api_key') {
    csrf_validate();
    if (!is_reseller($user)) {
        set_flash('error', 'Only active resellers can generate API keys.');
    } else {
        $key_name = sanitize($_POST['key_name'] ?? 'Default Store API Key');
        $new_key = 'fz_live_' . bin2hex(random_bytes(16));
        $new_secret = bin2hex(random_bytes(32));

        $stmt = $pdo->prepare("INSERT INTO api_keys (user_id, api_key, api_secret, name) VALUES (?, ?, ?, ?)");
        $stmt->execute([$user['id'], $new_key, $new_secret, $key_name]);

        set_flash('success', "API Key successfully created! Key: {$new_key}");
        header("Location: /reseller.php");
        exit;
    }
}

// Handle Revoke API Key
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'revoke_api_key') {
    csrf_validate();
    $key_id = (int)$_POST['key_id'];
    $pdo->prepare("DELETE FROM api_keys WHERE id = ? AND user_id = ?")->execute([$key_id, $user['id']]);
    set_flash('success', 'API key revoked successfully.');
    header("Location: /reseller.php");
    exit;
}

// Fetch API Keys
$stmt_keys = $pdo->prepare("SELECT * FROM api_keys WHERE user_id = ? ORDER BY id DESC");
$stmt_keys->execute([$user['id']]);
$api_keys = $stmt_keys->fetchAll();

// Fetch Wholesale Pricing Table
$stmt_prods = $pdo->query("
    SELECT p.*, c.name as category_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.status = 'active'
    ORDER BY p.diamonds_amount ASC, p.price ASC
");
$catalog = $stmt_prods->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-8">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-gaming-border">
        <div>
            <div class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-amber-400"></span>
                <h1 class="font-gaming text-3xl font-extrabold text-white tracking-wide">B2B RESELLER PORTAL</h1>
                <?php if (is_reseller($user)): ?>
                    <span class="px-2.5 py-0.5 rounded text-xs font-mono font-bold uppercase bg-amber-950 text-amber-400 border border-amber-800">
                        <?php echo strtoupper(e($user['reseller_level'] ?: 'Main')); ?> RESELLER
                    </span>
                <?php endif; ?>
            </div>
            <p class="text-xs text-zinc-400 mt-1">Wholesale discounted pricing, bulk fulfillment, and developer REST API</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="/api-docs.php" class="px-3.5 py-2 rounded-xl bg-gaming-800 hover:bg-gaming-750 text-xs font-semibold text-white border border-gaming-border flex items-center gap-1.5 shadow-sm">
                <span>API Documentation &rarr;</span>
            </a>
            <a href="/wallet.php" class="px-3.5 py-2 rounded-xl bg-emerald-950/60 text-xs font-semibold text-emerald-300 border border-emerald-800/40">
                Wallet: <?php echo format_currency($user['wallet_balance']); ?>
            </a>
        </div>
    </div>

    <?php if (!is_reseller($user)): ?>
        <!-- Reseller Activation Card -->
        <div class="p-8 sm:p-12 rounded-3xl bg-gradient-to-br from-gaming-900 via-gaming-850 to-amber-950/20 border border-amber-800/40 text-center max-w-2xl mx-auto shadow-2xl space-y-6">
            <div class="w-16 h-16 mx-auto rounded-2xl bg-amber-950 border border-amber-700/60 flex items-center justify-center text-amber-400 shadow-lg">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
            </div>
            <div>
                <h2 class="font-gaming text-2xl font-bold text-white">Join the FireZone Reseller Program</h2>
                <p class="text-xs text-zinc-400 mt-2 leading-relaxed">
                    Designed for gaming shop owners, esports streamers, and bulk diamond distributors. Enjoy wholesale margin discounts up to 15% off standard store prices, instant automated UID queue delivery, and developer REST API access.
                </p>
            </div>

            <form method="POST" action="/reseller.php">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="action" value="activate_reseller">
                <button type="submit" class="btn-gaming-red text-white font-gaming text-sm font-bold px-8 py-3.5 rounded-xl shadow-red-glow">
                    ACTIVATE RESELLER ACCOUNT NOW
                </button>
            </form>
        </div>
    <?php else: ?>
        <!-- Reseller Dashboard -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="p-6 rounded-2xl bg-gaming-850 border border-gaming-border space-y-2">
                <span class="text-xs text-zinc-400 uppercase font-mono">Wholesale Discount Level</span>
                <p class="font-gaming text-2xl font-bold text-amber-400"><?php echo strtoupper(e($user['reseller_level'] ?: 'Main Tier')); ?></p>
                <span class="text-[11px] text-zinc-500">Fixed margins applied automatically at checkout</span>
            </div>

            <div class="p-6 rounded-2xl bg-gaming-850 border border-gaming-border space-y-2">
                <span class="text-xs text-zinc-400 uppercase font-mono">Active API Keys</span>
                <p class="font-gaming text-2xl font-bold text-white"><?php echo count($api_keys); ?></p>
                <span class="text-[11px] text-zinc-500">Used for programmatic top-up integration</span>
            </div>

            <div class="p-6 rounded-2xl bg-gaming-850 border border-gaming-border space-y-2">
                <span class="text-xs text-zinc-400 uppercase font-mono">Reseller Wallet</span>
                <p class="font-gaming text-2xl font-bold text-emerald-400"><?php echo format_currency($user['wallet_balance']); ?></p>
                <a href="/wallet.php" class="text-[11px] text-red-400 hover:text-red-300 font-semibold block">+ Add Funds</a>
            </div>
        </div>

        <!-- API Keys Management -->
        <div class="p-6 rounded-2xl bg-gaming-900 border border-gaming-border space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-gaming-border">
                <div>
                    <h3 class="font-gaming text-lg font-bold text-white">RESELLER REST API KEYS</h3>
                    <p class="text-xs text-zinc-400">Automate diamond top-ups from your own website or bot</p>
                </div>
                <form method="POST" action="/reseller.php" class="flex items-center gap-2">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="action" value="generate_api_key">
                    <input type="text" name="key_name" placeholder="API Key Name (e.g. My Website Bot)" required class="px-3 py-1.5 rounded-lg bg-gaming-850 border border-gaming-border text-white text-xs">
                    <button type="submit" class="btn-gaming-red text-white text-xs font-bold px-4 py-2 rounded-lg shadow-red-subtle">
                        + Generate Key
                    </button>
                </form>
            </div>

            <?php if (empty($api_keys)): ?>
                <p class="text-xs text-zinc-400">No active API keys generated yet. Click "+ Generate Key" above to create your credentials.</p>
            <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($api_keys as $ak): ?>
                        <div class="p-4 rounded-xl bg-gaming-850 border border-gaming-border flex flex-col sm:flex-row sm:items-center justify-between gap-4 text-xs">
                            <div class="space-y-1">
                                <div class="flex items-center gap-2">
                                    <span class="font-bold text-white"><?php echo e($ak['name']); ?></span>
                                    <span class="px-2 py-0.5 rounded text-[10px] bg-emerald-950 text-emerald-400 border border-emerald-800 font-mono">ACTIVE</span>
                                </div>
                                <p class="font-mono text-zinc-400 text-[11px]">API Key: <span class="text-white"><?php echo e($ak['api_key']); ?></span></p>
                                <p class="font-mono text-zinc-400 text-[11px]">API Secret: <span class="text-red-400"><?php echo e($ak['api_secret']); ?></span></p>
                            </div>
                            <form method="POST" action="/reseller.php" onsubmit="return confirm('Revoke this API key? Any bots using it will stop working.');">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="action" value="revoke_api_key">
                                <input type="hidden" name="key_id" value="<?php echo $ak['id']; ?>">
                                <button type="submit" class="text-xs text-red-400 hover:text-red-300 font-semibold px-3 py-1.5 rounded-lg bg-red-950/40 border border-red-800/40">
                                    Revoke
                                </button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Wholesale Price Sheet -->
        <div class="space-y-4">
            <h3 class="font-gaming text-lg font-bold text-white">YOUR WHOLESALE PRICING SHEET</h3>
            <div class="overflow-hidden rounded-2xl bg-gaming-850 border border-gaming-border">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-gaming-border bg-gaming-900 text-zinc-400 uppercase font-mono text-[10px]">
                            <th class="py-3 px-6">Product Package</th>
                            <th class="py-3 px-4">Diamonds</th>
                            <th class="py-3 px-4">Normal Price</th>
                            <th class="py-3 px-4">Reseller Price</th>
                            <th class="py-3 px-4">Your Margin</th>
                            <th class="py-3 px-6 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gaming-border/60">
                        <?php foreach ($catalog as $prod): 
                            $normal_pr = (float)$prod['price'];
                            $reseller_pr = get_reseller_product_price($prod['id'], null, $user['reseller_level'] ?? 'main') ?: $normal_pr;
                            $savings = max(0, $normal_pr - $reseller_pr);
                        ?>
                            <tr>
                                <td class="py-3.5 px-6 font-bold text-white">
                                    <?php echo e($prod['name']); ?>
                                </td>
                                <td class="py-3.5 px-4 font-mono font-bold text-amber-400">
                                    <?php echo e($prod['diamonds_amount']); ?> 💎
                                </td>
                                <td class="py-3.5 px-4 font-mono text-zinc-400 line-through">
                                    <?php echo format_currency($normal_pr); ?>
                                </td>
                                <td class="py-3.5 px-4 font-mono font-bold text-emerald-400">
                                    <?php echo format_currency($reseller_pr); ?>
                                </td>
                                <td class="py-3.5 px-4 font-mono text-red-400 font-bold">
                                    Save <?php echo format_currency($savings); ?>
                                </td>
                                <td class="py-3.5 px-6 text-right">
                                    <a href="/checkout.php?product_id=<?php echo $prod['id']; ?>" class="btn-gaming-red text-white text-xs font-bold px-3 py-1.5 rounded-lg shadow-red-subtle">
                                        Quick Buy
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
