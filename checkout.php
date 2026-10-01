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

// Fetch active product variants
$stmt_var = $pdo->prepare("SELECT * FROM product_variants WHERE product_id = ? AND status = 'active' ORDER BY sort_order ASC, id ASC");
$stmt_var->execute([$product_id]);
$variants = $stmt_var->fetchAll();

// Fetch active payment gateways
$stmt_gw = $pdo->query("SELECT * FROM payment_gateways WHERE status = 'active' ORDER BY sort_order ASC");
$gateways = $stmt_gw->fetchAll();

$errors = [];
$ff_regions = [
    'India', 'Bangladesh', 'Indonesia', 'Brazil', 'North America', 
    'Europe', 'Singapore', 'Middle East (MENA)', 'Latin America', 'Global'
];

$ff_uid = sanitize($_POST['ff_uid'] ?? $user['ff_uid'] ?? '');
$ff_nickname = sanitize($_POST['ff_nickname'] ?? $user['ff_nickname'] ?? '');
$ff_region = sanitize($_POST['ff_region'] ?? $user['ff_region'] ?? 'Global');
$selected_variant_id = (int)($_POST['variant_id'] ?? $_GET['variant_id'] ?? 0);
$coupon_code = strtoupper(sanitize($_POST['coupon_code'] ?? ''));
$payment_method = sanitize($_POST['payment_method'] ?? 'wallet');
$payment_reference = sanitize($_POST['payment_reference'] ?? '');

// Calculate pricing (Standard vs Variant vs Flash Sale vs Reseller)
$selected_variant = null;
if ($selected_variant_id > 0) {
    foreach ($variants as $v) {
        if ((int)$v['id'] === $selected_variant_id) {
            $selected_variant = $v;
            break;
        }
    }
}

if ($selected_variant) {
    $base_price = (float)$selected_variant['price'];
    $diamonds_to_deliver = (int)$selected_variant['diamonds_amount'] + (int)$selected_variant['bonus_diamonds'];
    $item_name = $product['name'] . ' - ' . $selected_variant['name'];
} else {
    // Check flash sale server-side
    $effective = get_effective_product_price($product);
    $base_price = $effective['price'];
    $diamonds_to_deliver = (int)$product['diamonds_amount'] + (int)$product['bonus_diamonds'];
    $item_name = $product['name'];
}

// Check Reseller Pricing
if (is_reseller($user)) {
    $reseller_pr = get_reseller_product_price($product['id'], $selected_variant ? $selected_variant['id'] : null, $user['reseller_level'] ?? 'main');
    if ($reseller_pr !== null && $reseller_pr < $base_price) {
        $base_price = $reseller_pr;
    }
}

// Check Coupon
$coupon_discount = 0.00;
$coupon_result = null;
if (!empty($coupon_code)) {
    $coupon_result = validate_coupon($coupon_code, $base_price, $user['id']);
    if ($coupon_result['valid']) {
        $coupon_discount = $coupon_result['discount'];
    } else {
        $errors[] = $coupon_result['message'];
    }
}

$final_total = max(0, round($base_price - $coupon_discount, 2));

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_order') {
    csrf_validate();

    if (empty($ff_uid)) {
        $errors[] = "Free Fire Player ID / UID is required to deliver your diamonds.";
    }
    if (strlen($ff_uid) < 5 || !preg_match('/^[0-9]+$/', $ff_uid)) {
        $errors[] = "Please enter a valid numeric Free Fire UID (e.g. 192837192).";
    }

    // Validate Payment Method
    $chosen_gateway = null;
    foreach ($gateways as $gw) {
        if ($gw['code'] === $payment_method) {
            $chosen_gateway = $gw;
            break;
        }
    }

    if (!$chosen_gateway) {
        $errors[] = "Please select an active, authorized payment method.";
    }

    if ($payment_method === 'wallet') {
        if ((float)$user['wallet_balance'] < $final_total) {
            $errors[] = "Insufficient wallet balance. You have " . format_currency($user['wallet_balance']) . " but require " . format_currency($final_total) . ". Please add money or choose another payment gateway.";
        }
    } elseif (in_array($payment_method, ['manual_deposit', 'mobile_wallet'])) {
        if (empty($payment_reference)) {
            $errors[] = "Please enter your Transaction Reference / TrxID / UTR number for verification.";
        }
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            $order_number = generate_order_id();
            $payment_id = generate_payment_id();

            // 1. Process Payment based on gateway
            if ($payment_method === 'wallet') {
                // Transaction-safe balance debit
                $user_bal = (float)$user['wallet_balance'];
                $new_balance = max(0, round($user_bal - $final_total, 2));
                
                $stmt_upd = $pdo->prepare("UPDATE users SET wallet_balance = ? WHERE id = ?");
                $stmt_upd->execute([$new_balance, $user['id']]);

                // Create wallet transaction record
                $wallet_txn_id = generate_transaction_id();
                $stmt_txn = $pdo->prepare("
                    INSERT INTO wallet_transactions 
                    (transaction_id, user_id, type, amount, previous_balance, new_balance, payment_method, reference_no, status, notes) 
                    VALUES (?, ?, 'debit', ?, ?, ?, 'wallet', ?, 'completed', ?)
                ");
                $stmt_txn->execute([$wallet_txn_id, $user['id'], $final_total, $user_bal, $new_balance, $order_number, "Payment for Order #" . $order_number]);
                $wallet_txn_row_id = $pdo->lastInsertId();

                $payment_status = 'paid';
                $order_status = 'processing'; // In automated delivery queue
                $gateway_txn_id = $wallet_txn_id;
            } else {
                $payment_status = 'unpaid';
                $order_status = 'pending';
                $wallet_txn_row_id = null;
                $gateway_txn_id = !empty($payment_reference) ? $payment_reference : ('PENDING-' . uniqid());
            }

            // 2. Create Payment Record in payments table
            $stmt_pay = $pdo->prepare("
                INSERT INTO payments 
                (payment_id, user_id, wallet_transaction_id, gateway_code, amount, currency, status, gateway_txn_id, gateway_response) 
                VALUES (?, ?, ?, ?, ?, 'USD', ?, ?, ?)
            ");
            $pay_init_status = ($payment_status === 'paid') ? 'completed' : 'pending';
            $stmt_pay->execute([
                $payment_id,
                $user['id'],
                $wallet_txn_row_id,
                $payment_method,
                $final_total,
                $pay_init_status,
                $gateway_txn_id,
                json_encode(['reference' => $payment_reference, 'method' => $payment_method])
            ]);
            $payment_row_id = $pdo->lastInsertId();

            // 3. Create Order
            $stmt_ord = $pdo->prepare("
                INSERT INTO orders 
                (order_number, user_id, variant_id, variant_name, original_amount, total_amount, coupon_code, discount_amount, payment_method, payment_status, order_status, ff_uid, ff_nickname, ff_region, payment_reference) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt_ord->execute([
                $order_number,
                $user['id'],
                $selected_variant ? $selected_variant['id'] : null,
                $selected_variant ? $selected_variant['name'] : 'Standard Pack',
                $base_price,
                $final_total,
                !empty($coupon_code) ? $coupon_code : null,
                $coupon_discount,
                $payment_method,
                $payment_status,
                $order_status,
                $ff_uid,
                $ff_nickname,
                $ff_region,
                $payment_id
            ]);
            $order_id = $pdo->lastInsertId();

            // Link order_id to payment
            $pdo->prepare("UPDATE payments SET order_id = ? WHERE id = ?")->execute([$order_id, $payment_row_id]);

            // 4. Create Order Item
            $stmt_item = $pdo->prepare("
                INSERT INTO order_items 
                (order_id, product_id, product_name, price, quantity, diamonds_amount) 
                VALUES (?, ?, ?, ?, 1, ?)
            ");
            $stmt_item->execute([
                $order_id,
                $product['id'],
                $item_name,
                $final_total,
                $diamonds_to_deliver
            ]);

            // 5. Record Coupon Usage if coupon used
            if (!empty($coupon_code) && $coupon_result && $coupon_result['valid']) {
                $stmt_cu = $pdo->prepare("
                    INSERT INTO coupon_usage (coupon_id, user_id, order_id, discount_amount) 
                    VALUES (?, ?, ?, ?)
                ");
                $stmt_cu->execute([$coupon_result['coupon']['id'], $user['id'], $order_id, $coupon_discount]);
                $pdo->prepare("UPDATE coupons SET used_count = used_count + 1 WHERE id = ?")->execute([$coupon_result['coupon']['id']]);
            }

            // 6. Save player UID back to user profile if user didn't have one
            if (empty($user['ff_uid'])) {
                $stmt_usr = $pdo->prepare("UPDATE users SET ff_uid = ?, ff_nickname = ?, ff_region = ? WHERE id = ?");
                $stmt_usr->execute([$ff_uid, $ff_nickname, $ff_region, $user['id']]);
            }

            // 7. Referral Reward Check (First qualifying order)
            if (!empty($user['referred_by'])) {
                $ref_min = (float)get_setting('referral_min_order', 1.00);
                $ref_reward = (float)get_setting('referral_reward_amount', 0.25);
                if (get_setting('referral_enabled', '1') === '1' && $final_total >= $ref_min) {
                    // Check if already rewarded
                    $chk_ref = $pdo->prepare("SELECT COUNT(*) FROM referral_records WHERE referred_user_id = ? AND status = 'rewarded'");
                    $chk_ref->execute([$user['id']]);
                    if ((int)$chk_ref->fetchColumn() === 0) {
                        // Record pending referral reward
                        $stmt_ref = $pdo->prepare("INSERT INTO referral_records (referrer_id, referred_user_id, order_id, reward_amount, status) VALUES (?, ?, ?, ?, 'rewarded')");
                        $stmt_ref->execute([$user['referred_by'], $user['id'], $order_id, $ref_reward]);
                        
                        // Credit referrer's wallet
                        adjust_user_wallet($user['referred_by'], 'credit', $ref_reward, 'referral_bonus', "Referral bonus for user " . $user['username'], $order_number);
                        create_notification($user['referred_by'], "Referral Bonus Received!", "You earned " . format_currency($ref_reward) . " from your friend's order.", 'referral');
                    }
                }
            }

            // 8. Create In-Site User Notification
            create_notification(
                $user['id'], 
                "Order Created: #" . $order_number, 
                "Your order for " . $item_name . " has been submitted with status: " . ucfirst($order_status) . ".", 
                'order', 
                "/order-details.php?id=" . $order_id
            );

            // 9. Provider Auto-Dispatch Architecture (Simulated / Live Provider Trigger)
            if ($payment_status === 'paid') {
                $stmt_prov = $pdo->query("SELECT * FROM providers WHERE status = 'active' ORDER BY id ASC LIMIT 1");
                $active_provider = $stmt_prov->fetch();
                if ($active_provider) {
                    $prov_ref = 'PRV-' . strtoupper(substr(uniqid(), -8));
                    $stmt_upd_ord = $pdo->prepare("
                        UPDATE orders 
                        SET provider_id = ?, provider_order_id = ?, provider_response = ?, order_status = 'completed' 
                        WHERE id = ?
                    ");
                    $stmt_upd_ord->execute([
                        $active_provider['id'], 
                        $prov_ref, 
                        json_encode(['status' => 'SUCCESS', 'message' => 'Direct UID Recharge Delivered', 'api_provider' => $active_provider['name']]),
                        $order_id
                    ]);

                    create_notification(
                        $user['id'], 
                        "Diamonds Delivered! #" . $order_number, 
                        "Your " . $diamonds_to_deliver . " Free Fire Diamonds have been credited to UID: " . $ff_uid . ".", 
                        'order', 
                        "/order-details.php?id=" . $order_id
                    );
                }
            }

            $pdo->commit();

            set_flash('success', "Order #" . $order_number . " placed successfully!");
            header("Location: /order-confirmation.php?id=" . $order_id);
            exit;

        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errors[] = "Failed to create order: " . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-8">
    <div class="flex items-center justify-between border-b border-gaming-border pb-4">
        <div>
            <a href="/products.php" class="text-xs text-red-400 hover:text-red-300 font-mono mb-1 inline-flex items-center gap-1">
                &larr; Back to Catalog
            </a>
            <h1 class="font-gaming text-3xl font-extrabold text-white tracking-wide">CONFIRM & ORDER</h1>
        </div>
        <div class="hidden sm:flex items-center gap-2 text-xs text-emerald-400 font-mono bg-gaming-900 border border-emerald-900/40 px-3 py-1.5 rounded-lg">
            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
            <span>100% Official UID Delivery</span>
        </div>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="p-4 rounded-xl bg-red-950/70 border border-red-800 text-red-200 text-xs space-y-1">
            <div class="font-bold flex items-center gap-2">
                <svg class="w-4 h-4 text-red-400 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                <span>Please resolve the following:</span>
            </div>
            <ul class="list-disc list-inside pl-2 space-y-0.5">
                <?php foreach ($errors as $err): ?>
                    <li><?php echo e($err); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" action="/checkout.php?product_id=<?php echo $product['id']; ?>" class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="action" value="create_order">
        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">

        <!-- Left Column: Product & Variants Selection + Player Details -->
        <div class="lg:col-span-7 space-y-6">
            <!-- 1. Package / Variant Selection -->
            <div class="p-6 rounded-2xl bg-gaming-900 border border-gaming-border space-y-4">
                <div class="flex items-center gap-2 text-red-400">
                    <span class="w-6 h-6 rounded-full bg-red-950 border border-red-800 flex items-center justify-center font-gaming text-xs font-bold text-red-400">1</span>
                    <h2 class="font-gaming text-lg font-bold text-white tracking-wide">SELECT PACKAGE / VARIANT</h2>
                </div>

                <div class="space-y-3">
                    <!-- Base Product Option -->
                    <label class="block p-4 rounded-xl border transition-all cursor-pointer <?php echo ($selected_variant_id === 0) ? 'border-red-600 bg-red-950/20 shadow-red-subtle' : 'border-gaming-border bg-gaming-850 hover:border-gaming-border'; ?>">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <input type="radio" name="variant_id" value="0" <?php echo ($selected_variant_id === 0) ? 'checked' : ''; ?> onchange="this.form.submit()" class="text-red-600 focus:ring-0">
                                <div>
                                    <span class="font-gaming font-bold text-white text-sm block"><?php echo e($product['name']); ?></span>
                                    <span class="text-xs text-zinc-400"><?php echo e($product['diamonds_amount']); ?> Diamonds <?php echo $product['bonus_diamonds'] > 0 ? '(+' . $product['bonus_diamonds'] . ' Bonus)' : ''; ?></span>
                                </div>
                            </div>
                            <span class="font-gaming text-base font-bold text-white">
                                <?php echo format_currency(get_effective_product_price($product)['price']); ?>
                            </span>
                        </div>
                    </label>

                    <!-- Product Variants -->
                    <?php foreach ($variants as $v): ?>
                        <label class="block p-4 rounded-xl border transition-all cursor-pointer <?php echo ($selected_variant_id === (int)$v['id']) ? 'border-red-600 bg-red-950/20 shadow-red-subtle' : 'border-gaming-border bg-gaming-850 hover:border-gaming-border'; ?>">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <input type="radio" name="variant_id" value="<?php echo $v['id']; ?>" <?php echo ($selected_variant_id === (int)$v['id']) ? 'checked' : ''; ?> onchange="this.form.submit()" class="text-red-600 focus:ring-0">
                                    <div>
                                        <span class="font-gaming font-bold text-white text-sm block"><?php echo e($v['name']); ?></span>
                                        <span class="text-xs text-zinc-400"><?php echo e($v['diamonds_amount']); ?> Diamonds <?php echo $v['bonus_diamonds'] > 0 ? '(+' . $v['bonus_diamonds'] . ' Bonus)' : ''; ?></span>
                                    </div>
                                </div>
                                <span class="font-gaming text-base font-bold text-white">
                                    <?php echo format_currency($v['price']); ?>
                                </span>
                            </div>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- 2. Player ID & Region -->
            <div class="p-6 rounded-2xl bg-gaming-900 border border-gaming-border space-y-4">
                <div class="flex items-center gap-2 text-red-400">
                    <span class="w-6 h-6 rounded-full bg-red-950 border border-red-800 flex items-center justify-center font-gaming text-xs font-bold text-red-400">2</span>
                    <h2 class="font-gaming text-lg font-bold text-white tracking-wide">ENTER PLAYER DETAILS</h2>
                </div>

                <div class="space-y-4 text-xs">
                    <div>
                        <label class="block text-zinc-300 font-semibold mb-1.5">Free Fire Player UID / Account ID *</label>
                        <input type="text" name="ff_uid" value="<?php echo e($ff_uid); ?>" required placeholder="e.g. 1928371928" class="w-full px-4 py-3 rounded-xl bg-gaming-850 border border-gaming-border focus:border-red-500 text-white font-mono text-sm focus:outline-none">
                        <span class="text-[10px] text-zinc-400 mt-1 block">Find your UID on your in-game profile card next to your avatar.</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-zinc-300 font-semibold mb-1.5">In-Game Nickname (Optional)</label>
                            <input type="text" name="ff_nickname" value="<?php echo e($ff_nickname); ?>" placeholder="e.g. ProGamer99" class="w-full px-4 py-2.5 rounded-xl bg-gaming-850 border border-gaming-border focus:border-red-500 text-white text-xs focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-zinc-300 font-semibold mb-1.5">Game Server / Region</label>
                            <select name="ff_region" class="w-full px-4 py-2.5 rounded-xl bg-gaming-850 border border-gaming-border text-white text-xs focus:outline-none">
                                <?php foreach ($ff_regions as $r): ?>
                                    <option value="<?php echo e($r); ?>" <?php echo $ff_region === $r ? 'selected' : ''; ?>><?php echo e($r); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. Payment Gateway Selection -->
            <div class="p-6 rounded-2xl bg-gaming-900 border border-gaming-border space-y-4">
                <div class="flex items-center gap-2 text-red-400">
                    <span class="w-6 h-6 rounded-full bg-red-950 border border-red-800 flex items-center justify-center font-gaming text-xs font-bold text-red-400">3</span>
                    <h2 class="font-gaming text-lg font-bold text-white tracking-wide">PAYMENT GATEWAY</h2>
                </div>

                <div class="space-y-3">
                    <?php foreach ($gateways as $gw): ?>
                        <label class="block p-4 rounded-xl border transition-all cursor-pointer <?php echo ($payment_method === $gw['code']) ? 'border-red-600 bg-red-950/20 shadow-red-subtle' : 'border-gaming-border bg-gaming-850 hover:border-gaming-border'; ?>">
                            <div class="flex items-start justify-between">
                                <div class="flex items-start gap-3">
                                    <input type="radio" name="payment_method" value="<?php echo e($gw['code']); ?>" <?php echo ($payment_method === $gw['code']) ? 'checked' : ''; ?> onchange="document.getElementById('refWrapper').classList.toggle('hidden', this.value === 'wallet');" class="mt-1 text-red-600 focus:ring-0">
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <span class="font-gaming font-bold text-white text-sm"><?php echo e($gw['title']); ?></span>
                                            <?php if ($gw['code'] === 'wallet'): ?>
                                                <span class="text-[10px] bg-emerald-950 text-emerald-400 px-2 py-0.5 rounded border border-emerald-800/40 font-mono">
                                                    Bal: <?php echo format_currency($user['wallet_balance']); ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <p class="text-xs text-zinc-400 mt-1"><?php echo e($gw['instructions']); ?></p>
                                    </div>
                                </div>
                            </div>
                        </label>
                    <?php endforeach; ?>
                </div>

                <!-- Reference Input for Manual / Mobile / Bank Gateways -->
                <div id="refWrapper" class="<?php echo ($payment_method === 'wallet') ? 'hidden' : ''; ?> pt-3 border-t border-gaming-border">
                    <label class="block text-zinc-300 font-semibold text-xs mb-1.5">
                        Transaction Reference / TrxID / Payment ID *
                    </label>
                    <input type="text" name="payment_reference" value="<?php echo e($payment_reference); ?>" placeholder="Enter transaction reference or screenshot ID" class="w-full px-4 py-2.5 rounded-xl bg-gaming-850 border border-gaming-border focus:border-red-500 text-white font-mono text-xs focus:outline-none">
                    <span class="text-[10px] text-zinc-400 mt-1 block">Used by automated queue / verification team to match payment instantly.</span>
                </div>
            </div>
        </div>

        <!-- Right Column: Order Summary & Coupon -->
        <div class="lg:col-span-5 space-y-6">
            <div class="p-6 rounded-2xl bg-gaming-900 border border-gaming-border space-y-5 sticky top-24">
                <h3 class="font-gaming text-lg font-bold text-white tracking-wide border-b border-gaming-border pb-3">
                    ORDER SUMMARY
                </h3>

                <!-- Product Snapshot -->
                <div class="flex items-center gap-3 p-3 rounded-xl bg-gaming-850 border border-gaming-border">
                    <div class="w-12 h-12 rounded-lg bg-red-950 border border-red-800/40 flex items-center justify-center text-red-500 shrink-0 font-gaming font-extrabold text-sm">
                        💎
                    </div>
                    <div class="flex-1 min-w-0">
                        <h4 class="font-bold text-white text-xs truncate"><?php echo e($item_name); ?></h4>
                        <span class="text-[11px] text-amber-400 font-bold block"><?php echo $diamonds_to_deliver; ?> Diamonds Total</span>
                    </div>
                </div>

                <!-- Coupon Input Form -->
                <div class="pt-2">
                    <label class="block text-zinc-300 text-xs font-semibold mb-1.5">Have a Coupon or Promo Code?</label>
                    <div class="flex items-center gap-2">
                        <input type="text" name="coupon_code" value="<?php echo e($coupon_code); ?>" placeholder="e.g. FIRE10" class="flex-1 px-3 py-2 rounded-xl bg-gaming-850 border border-gaming-border focus:border-red-500 text-white font-mono text-xs uppercase focus:outline-none">
                        <button type="submit" class="px-4 py-2 rounded-xl bg-gaming-800 hover:bg-gaming-750 text-white text-xs font-bold border border-gaming-border">
                            Apply
                        </button>
                    </div>
                    <?php if ($coupon_result && $coupon_result['valid']): ?>
                        <span class="text-[11px] text-emerald-400 font-semibold mt-1 block">
                            ✓ <?php echo e($coupon_result['message']); ?>
                        </span>
                    <?php endif; ?>
                </div>

                <!-- Price Calculations -->
                <div class="space-y-2 pt-3 border-t border-gaming-border text-xs">
                    <div class="flex items-center justify-between text-zinc-400">
                        <span>Package Price:</span>
                        <span class="text-white font-mono"><?php echo format_currency($base_price); ?></span>
                    </div>
                    <?php if ($coupon_discount > 0): ?>
                        <div class="flex items-center justify-between text-emerald-400">
                            <span>Coupon Discount (<?php echo e($coupon_code); ?>):</span>
                            <span class="font-mono">-<?php echo format_currency($coupon_discount); ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if (is_reseller($user)): ?>
                        <div class="flex items-center justify-between text-amber-400">
                            <span>Wholesale Reseller Tier:</span>
                            <span class="font-mono">Applied</span>
                        </div>
                    <?php endif; ?>
                    <div class="flex items-center justify-between text-zinc-400">
                        <span>Server UID Delivery Fee:</span>
                        <span class="text-emerald-400 font-mono">FREE (0.00)</span>
                    </div>
                    <div class="flex items-center justify-between pt-3 border-t border-gaming-border">
                        <span class="font-gaming font-bold text-white text-sm">TOTAL AMOUNT:</span>
                        <span class="font-gaming font-extrabold text-2xl text-red-400">
                            <?php echo format_currency($final_total); ?>
                        </span>
                    </div>
                </div>

                <!-- Complete Order Button -->
                <button type="submit" class="w-full btn-gaming-red text-white font-gaming text-sm font-bold py-3.5 rounded-xl shadow-red-glow flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    <span>CONFIRM & PLACE ORDER</span>
                </button>

                <!-- Security Guarantee -->
                <div class="flex items-center justify-center gap-2 text-[11px] text-zinc-400 pt-2">
                    <svg class="w-4 h-4 text-emerald-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                    <span>SSL Encrypted & Server Authorized</span>
                </div>
            </div>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
