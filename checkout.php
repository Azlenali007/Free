<?php
$page_title = "Checkout & Complete Order";
require_once __DIR__ . '/includes/functions.php';
require_login();

$user = current_user();
$product_id = (int)($_GET['product_id'] ?? $_POST['product_id'] ?? 0);

if (!$product_id) {
    header("Location: /products.php");
    exit;
}

// Fetch product from MySQL
$stmt = $pdo->prepare("
    SELECT p.*, c.name as category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    WHERE p.id = ? AND p.status = 'active'
");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if (!$product) {
    set_flash('error', 'Selected product is currently unavailable or inactive.');
    header("Location: /products.php");
    exit;
}

$errors = [];
$ff_regions = [
    'India', 'Bangladesh', 'Indonesia', 'Brazil', 'North America', 
    'Europe', 'Singapore', 'Middle East (MENA)', 'Latin America', 'Global'
];

$ff_uid = sanitize($_POST['ff_uid'] ?? $user['ff_uid'] ?? '');
$ff_nickname = sanitize($_POST['ff_nickname'] ?? $user['ff_nickname'] ?? '');
$ff_region = sanitize($_POST['ff_region'] ?? $user['ff_region'] ?? 'Global');
$payment_method = sanitize($_POST['payment_method'] ?? 'wallet');
$payment_reference = sanitize($_POST['payment_reference'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validate();

    if (empty($ff_uid)) {
        $errors[] = "Free Fire Player ID / UID is required to deliver your diamonds.";
    }
    if (strlen($ff_uid) < 5 || !preg_match('/^[0-9]+$/', $ff_uid)) {
        $errors[] = "Please enter a valid numeric Free Fire UID (e.g. 192837192).";
    }

    $total_amount = (float)$product['price'];

    if ($payment_method === 'wallet') {
        if ((float)$user['wallet_balance'] < $total_amount) {
            $errors[] = "Insufficient wallet balance. You have " . format_currency($user['wallet_balance']) . " but need " . format_currency($total_amount) . ". Please add money or choose Direct Manual Payment.";
        }
    } elseif ($payment_method === 'direct') {
        if (empty($payment_reference)) {
            $errors[] = "Please provide your Transaction Reference / UTR Number for manual payment verification.";
        }
    } else {
        $errors[] = "Please select a valid payment method.";
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            $order_number = generate_order_id();

            if ($payment_method === 'wallet') {
                // Deduct wallet balance
                $new_balance = (float)$user['wallet_balance'] - $total_amount;
                $stmt_upd = $pdo->prepare("UPDATE users SET wallet_balance = ? WHERE id = ?");
                $stmt_upd->execute([$new_balance, $user['id']]);

                // Create debit wallet transaction
                $txn_id = generate_transaction_id();
                $stmt_txn = $pdo->prepare("
                    INSERT INTO wallet_transactions 
                    (transaction_id, user_id, type, amount, payment_method, reference_no, status, notes) 
                    VALUES (?, ?, 'debit', ?, 'wallet', ?, 'completed', ?)
                ");
                $stmt_txn->execute([$txn_id, $user['id'], $total_amount, $order_number, "Payment for order #" . $order_number]);

                $payment_status = 'paid';
                $order_status = 'processing'; // In automated queue
                $ref_to_save = 'Wallet Debited (' . $txn_id . ')';
            } else {
                // Manual direct payment (UPI, Bank, QR)
                $payment_status = 'unpaid';
                $order_status = 'pending'; // Requires admin confirmation
                $ref_to_save = $payment_reference;
            }

            // Create Order
            $stmt_ord = $pdo->prepare("
                INSERT INTO orders 
                (order_number, user_id, total_amount, payment_method, payment_status, order_status, ff_uid, ff_nickname, ff_region, payment_reference) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt_ord->execute([
                $order_number,
                $user['id'],
                $total_amount,
                $payment_method,
                $payment_status,
                $order_status,
                $ff_uid,
                $ff_nickname,
                $ff_region,
                $ref_to_save
            ]);
            $order_id = $pdo->lastInsertId();

            // Create Order Item
            $stmt_item = $pdo->prepare("
                INSERT INTO order_items 
                (order_id, product_id, product_name, price, quantity, diamonds_amount) 
                VALUES (?, ?, ?, ?, 1, ?)
            ");
            $stmt_item->execute([
                $order_id,
                $product['id'],
                $product['name'],
                $product['price'],
                $product['diamonds_amount'] + $product['bonus_diamonds']
            ]);

            // Save player UID back to user profile if user didn't have one
            if (empty($user['ff_uid'])) {
                $stmt_usr = $pdo->prepare("UPDATE users SET ff_uid = ?, ff_nickname = ?, ff_region = ? WHERE id = ?");
                $stmt_usr->execute([$ff_uid, $ff_nickname, $ff_region, $user['id']]);
            }

            $pdo->commit();

            set_flash('success', "Order #" . $order_number . " has been created successfully!");
            header("Location: /order-confirmation.php?id=" . $order_id);
            exit;

        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = "Failed to process order: " . $e->getMessage();
        }
    }
}

$deposit_instructions = get_setting('deposit_instructions', '');

require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-8">
    <div class="flex items-center justify-between pb-4 border-b border-gaming-border">
        <div>
            <h1 class="font-gaming text-2xl md:text-3xl font-bold text-white tracking-wide flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-red-500"></span> SECURE ORDER CHECKOUT
            </h1>
            <p class="text-xs text-zinc-400 mt-1">Direct UID Diamond Fulfillment Queue</p>
        </div>
        <a href="/products.php" class="text-xs font-semibold text-zinc-400 hover:text-white flex items-center gap-1">
            &larr; Back to Catalog
        </a>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="p-4 rounded-xl bg-red-950/70 border border-red-500/60 text-red-200 text-xs space-y-1">
            <?php foreach ($errors as $err): ?>
                <p>• <?php echo e($err); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="/checkout.php" class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">

        <!-- Left Column: Player Details & Payment Selection -->
        <div class="lg:col-span-7 space-y-6">
            <!-- Step 1: Free Fire Player Details -->
            <div class="bg-gaming-850 border border-gaming-border rounded-2xl p-6 relative">
                <div class="flex items-center gap-2 mb-4">
                    <span class="w-6 h-6 rounded-full bg-red-600 text-white font-gaming font-bold text-xs flex items-center justify-center">1</span>
                    <h2 class="font-gaming text-lg font-bold text-white tracking-wide">ENTER FREE FIRE PLAYER UID</h2>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1">
                            Player UID / Account ID <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="ff_uid" value="<?php echo e($ff_uid); ?>" required
                               class="w-full px-4 py-2.5 rounded-xl bg-gaming-900 border border-gaming-border focus:border-red-500 text-base font-mono text-white focus:outline-none"
                               placeholder="e.g. 1928392102">
                        <span class="text-[11px] text-zinc-400 mt-1 block">
                            Diamonds will be delivered directly to this Player ID. Please verify carefully.
                        </span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1">Player Nickname (Optional)</label>
                            <input type="text" name="ff_nickname" value="<?php echo e($ff_nickname); ?>"
                                   class="w-full px-3.5 py-2.5 rounded-xl bg-gaming-900 border border-gaming-border focus:border-red-500 text-sm text-white focus:outline-none"
                                   placeholder="e.g. Hunter77">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1">Server / Region</label>
                            <select name="ff_region" class="w-full px-3.5 py-2.5 rounded-xl bg-gaming-900 border border-gaming-border focus:border-red-500 text-sm text-white focus:outline-none">
                                <?php foreach ($ff_regions as $r): ?>
                                    <option value="<?php echo e($r); ?>" <?php echo ($ff_region === $r) ? 'selected' : ''; ?>>
                                        <?php echo e($r); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Step 2: Payment Method Selection -->
            <div class="bg-gaming-850 border border-gaming-border rounded-2xl p-6 relative">
                <div class="flex items-center gap-2 mb-4">
                    <span class="w-6 h-6 rounded-full bg-red-600 text-white font-gaming font-bold text-xs flex items-center justify-center">2</span>
                    <h2 class="font-gaming text-lg font-bold text-white tracking-wide">SELECT PAYMENT METHOD</h2>
                </div>

                <div class="space-y-3">
                    <!-- Option 1: Store Wallet -->
                    <label class="flex items-start gap-3 p-4 rounded-xl border cursor-pointer transition-all <?php echo $payment_method === 'wallet' ? 'border-red-500 bg-gaming-900 shadow-red-subtle' : 'border-gaming-border bg-gaming-900/50 hover:bg-gaming-900'; ?>"
                           id="label_wallet">
                        <input type="radio" name="payment_method" value="wallet" <?php echo $payment_method === 'wallet' ? 'checked' : ''; ?>
                               class="mt-1 text-red-600 focus:ring-red-500" onchange="togglePayment(this.value)">
                        <div class="flex-1">
                            <div class="flex items-center justify-between">
                                <span class="font-gaming font-bold text-sm text-white">Gamer Wallet Balance</span>
                                <span class="font-gaming font-bold text-emerald-400 text-xs">
                                    Balance: <?php echo format_currency($user['wallet_balance']); ?>
                                </span>
                            </div>
                            <p class="text-xs text-zinc-400 mt-1">
                                Instant automated delivery. Funds are deducted directly from your account balance.
                            </p>
                            <?php if ((float)$user['wallet_balance'] < (float)$product['price']): ?>
                                <p class="text-[11px] text-amber-400 mt-1 font-semibold">
                                    ⚠️ Insufficient balance. <a href="/wallet.php" target="_blank" class="underline text-red-400">Add money</a> or choose Direct Payment below.
                                </p>
                            <?php endif; ?>
                        </div>
                    </label>

                    <!-- Option 2: Direct Manual Transfer -->
                    <label class="flex items-start gap-3 p-4 rounded-xl border cursor-pointer transition-all <?php echo $payment_method === 'direct' ? 'border-red-500 bg-gaming-900 shadow-red-subtle' : 'border-gaming-border bg-gaming-900/50 hover:bg-gaming-900'; ?>"
                           id="label_direct">
                        <input type="radio" name="payment_method" value="direct" <?php echo $payment_method === 'direct' ? 'checked' : ''; ?>
                               class="mt-1 text-red-600 focus:ring-red-500" onchange="togglePayment(this.value)">
                        <div class="flex-1">
                            <div class="flex items-center justify-between">
                                <span class="font-gaming font-bold text-sm text-white">Direct Manual Transfer / UPI / QR / Bank</span>
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-zinc-800 text-zinc-300 uppercase">Manual Verify</span>
                            </div>
                            <p class="text-xs text-zinc-400 mt-1">
                                Transfer to our official gamer account and submit the reference code below.
                            </p>
                        </div>
                    </label>

                    <!-- Direct Payment Instructions & Reference Input Field -->
                    <div id="directPaymentBox" class="<?php echo $payment_method === 'direct' ? '' : 'hidden'; ?> p-4 rounded-xl bg-gaming-950 border border-gaming-border space-y-3">
                        <div class="text-xs text-zinc-300 whitespace-pre-line bg-gaming-900 p-3 rounded-lg border border-red-900/30 font-mono">
                            <?php echo e($deposit_instructions); ?>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-red-400 uppercase tracking-wider mb-1">
                                Transaction Reference / UTR Number <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="payment_reference" value="<?php echo e($payment_reference); ?>"
                                   class="w-full px-3.5 py-2.5 rounded-xl bg-gaming-900 border border-gaming-border focus:border-red-500 text-sm text-white focus:outline-none"
                                   placeholder="e.g. UTR 4291039821 / TXN ID">
                            <span class="text-[10px] text-zinc-400 mt-1 block">Order will be verified by admin and processed upon receipt.</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Order Summary & Confirm Button -->
        <div class="lg:col-span-5 space-y-6">
            <div class="bg-gaming-850 border border-gaming-border rounded-2xl p-6 sticky top-20 shadow-2xl space-y-6">
                <h3 class="font-gaming text-lg font-bold text-white pb-3 border-b border-gaming-border">
                    ORDER SUMMARY
                </h3>

                <!-- Product Preview Card -->
                <div class="flex items-center gap-4 p-3.5 rounded-xl bg-gaming-900 border border-gaming-border">
                    <div class="w-16 h-16 rounded-xl bg-gaming-950 border border-gaming-border flex items-center justify-center p-2 shrink-0">
                        <img src="<?php echo e($product['image'] ?: '/assets/images/diamonds.svg'); ?>" 
                             alt="" class="max-h-12 max-w-full">
                    </div>
                    <div class="overflow-hidden">
                        <span class="text-[10px] text-red-400 font-semibold uppercase"><?php echo e($product['category_name']); ?></span>
                        <h4 class="font-gaming font-bold text-white text-base truncate"><?php echo e($product['name']); ?></h4>
                        <?php if ($product['diamonds_amount'] > 0): ?>
                            <p class="text-xs text-zinc-400">
                                Diamonds: <strong class="text-white"><?php echo $product['diamonds_amount']; ?></strong>
                                <?php if ($product['bonus_diamonds'] > 0): ?>
                                    <span class="text-emerald-400 font-semibold">+<?php echo $product['bonus_diamonds']; ?> Bonus</span>
                                <?php endif; ?>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Price Breakdown -->
                <div class="space-y-2.5 text-xs text-zinc-400 pt-2 border-t border-gaming-border/80">
                    <div class="flex justify-between">
                        <span>Package Price</span>
                        <span class="font-gaming font-bold text-white"><?php echo format_currency($product['price']); ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span>Delivery Fee</span>
                        <span class="text-emerald-400 font-semibold">FREE (Instant UID)</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Anti-Ban Guarantee</span>
                        <span class="text-emerald-400 font-semibold">Included</span>
                    </div>
                    <div class="pt-3 border-t border-gaming-border flex justify-between items-baseline">
                        <span class="font-gaming font-bold text-sm text-white uppercase">Total Amount</span>
                        <span class="font-gaming text-2xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-red-500 to-amber-400">
                            <?php echo format_currency($product['price']); ?>
                        </span>
                    </div>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="w-full btn-gaming-red text-white font-gaming text-base font-bold py-3.5 rounded-xl shadow-red-glow flex items-center justify-center gap-2 group">
                    <span>CONFIRM & PLACE ORDER</span>
                    <svg class="w-5 h-5 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                    </svg>
                </button>

                <p class="text-[11px] text-zinc-400 text-center leading-relaxed">
                    By confirming, you verify that the Free Fire Player UID is accurate. Top-ups are non-refundable once delivered.
                </p>
            </div>
        </div>
    </form>
</div>

<script>
function togglePayment(method) {
    const box = document.getElementById('directPaymentBox');
    const labelWallet = document.getElementById('label_wallet');
    const labelDirect = document.getElementById('label_direct');

    if (method === 'direct') {
        box.classList.remove('hidden');
        labelDirect.classList.add('border-red-500', 'bg-gaming-900', 'shadow-red-subtle');
        labelWallet.classList.remove('border-red-500', 'bg-gaming-900', 'shadow-red-subtle');
    } else {
        box.classList.add('hidden');
        labelWallet.classList.add('border-red-500', 'bg-gaming-900', 'shadow-red-subtle');
        labelDirect.classList.remove('border-red-500', 'bg-gaming-900', 'shadow-red-subtle');
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
