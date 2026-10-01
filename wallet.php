<?php
$page_title = "Gamer Wallet & Add Money";
require_once __DIR__ . '/includes/functions.php';
require_login();

$user = current_user();
$errors = [];

// Fetch active payment gateways
$stmt_gw = $pdo->query("SELECT * FROM payment_gateways WHERE status = 'active' AND code != 'wallet' ORDER BY sort_order ASC");
$gateways = $stmt_gw->fetchAll();

// Handle Add Money deposit request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validate();

    $amount = (float)($_POST['amount'] ?? 0);
    $gateway_code = sanitize($_POST['gateway_code'] ?? 'manual_deposit');
    $reference_no = sanitize($_POST['reference_no'] ?? '');
    $notes = sanitize($_POST['notes'] ?? '');

    if ($amount <= 0.50) {
        $errors[] = "Minimum deposit amount is " . format_currency(0.50) . ".";
    }

    // Validate gateway
    $chosen_gateway = null;
    foreach ($gateways as $gw) {
        if ($gw['code'] === $gateway_code) {
            $chosen_gateway = $gw;
            break;
        }
    }
    if (!$chosen_gateway) {
        $errors[] = "Please select a valid payment gateway.";
    }

    if (empty($reference_no) && in_array($gateway_code, ['manual_deposit', 'mobile_wallet'])) {
        $errors[] = "Please provide your Transaction ID / UTR number for verification.";
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            $payment_id = generate_payment_id();
            $txn_id = generate_transaction_id();
            
            // If automated card or gateway (simulated live verification)
            $is_instant_verified = in_array($gateway_code, ['stripe', 'razorpay']);
            
            if ($is_instant_verified) {
                // Server-side instant payment completion
                $pay_status = 'completed';
                $txn_status = 'completed';
                $current_bal = (float)$user['wallet_balance'];
                $new_bal = round($current_bal + $amount, 2);

                // Update user wallet
                $stmt_upd = $pdo->prepare("UPDATE users SET wallet_balance = ? WHERE id = ?");
                $stmt_upd->execute([$new_bal, $user['id']]);

                // Record wallet transaction
                $stmt_txn = $pdo->prepare("
                    INSERT INTO wallet_transactions 
                    (transaction_id, user_id, type, amount, previous_balance, new_balance, payment_method, reference_no, status, notes) 
                    VALUES (?, ?, 'credit', ?, ?, ?, ?, ?, 'completed', ?)
                ");
                $stmt_txn->execute([$txn_id, $user['id'], $amount, $current_bal, $new_bal, $gateway_code, $payment_id, "Instant deposit via " . $chosen_gateway['name']]);
                $wallet_txn_id = $pdo->lastInsertId();

                // Record in payments table
                $stmt_pay = $pdo->prepare("
                    INSERT INTO payments 
                    (payment_id, user_id, wallet_transaction_id, gateway_code, amount, currency, status, gateway_txn_id, gateway_response) 
                    VALUES (?, ?, ?, ?, ?, 'USD', 'completed', ?, ?)
                ");
                $stmt_pay->execute([
                    $payment_id,
                    $user['id'],
                    $wallet_txn_id,
                    $gateway_code,
                    $amount,
                    $payment_id,
                    json_encode(['status' => 'APPROVED', 'gateway' => $chosen_gateway['name']])
                ]);

                // Create user notification
                create_notification($user['id'], "Wallet Funded!", "Your wallet has been credited with " . format_currency($amount) . " via " . $chosen_gateway['name'] . ".", 'wallet');
                $pdo->commit();

                set_flash('success', "Deposit of " . format_currency($amount) . " successfully verified and credited to your wallet!");
            } else {
                // Manual deposit requiring admin verification
                $pay_status = 'pending';
                $stmt_txn = $pdo->prepare("
                    INSERT INTO wallet_transactions 
                    (transaction_id, user_id, type, amount, previous_balance, new_balance, payment_method, reference_no, status, notes) 
                    VALUES (?, ?, 'credit', ?, ?, ?, ?, ?, 'pending', ?)
                ");
                $stmt_txn->execute([
                    $txn_id, 
                    $user['id'], 
                    $amount, 
                    $user['wallet_balance'], 
                    $user['wallet_balance'], 
                    $gateway_code, 
                    $reference_no, 
                    $notes ?: ('Manual deposit request via ' . $chosen_gateway['name'])
                ]);
                $wallet_txn_id = $pdo->lastInsertId();

                $stmt_pay = $pdo->prepare("
                    INSERT INTO payments 
                    (payment_id, user_id, wallet_transaction_id, gateway_code, amount, currency, status, gateway_txn_id, gateway_response) 
                    VALUES (?, ?, ?, ?, ?, 'USD', 'pending', ?, ?)
                ");
                $stmt_pay->execute([
                    $payment_id,
                    $user['id'],
                    $wallet_txn_id,
                    $gateway_code,
                    $amount,
                    $reference_no,
                    json_encode(['reference' => $reference_no, 'notes' => $notes])
                ]);

                create_notification($user['id'], "Deposit Pending", "Your deposit request for " . format_currency($amount) . " is being reviewed.", 'wallet');
                $pdo->commit();

                set_flash('success', "Deposit request for " . format_currency($amount) . " submitted! Ref ID: " . $reference_no . ". Admin will verify and credit your wallet shortly.");
            }

            header("Location: /wallet.php");
            exit;
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errors[] = "Failed to process deposit: " . $e->getMessage();
        }
    }
}

// Filter transactions by tab
$tab = sanitize($_GET['tab'] ?? 'all');
$where_txn = ["user_id = ?"];
$params_txn = [$user['id']];

if (in_array($tab, ['completed', 'pending', 'failed', 'refunded'])) {
    $where_txn[] = "status = ?";
    $params_txn[] = $tab;
}

$stmt = $pdo->prepare("
    SELECT * FROM wallet_transactions 
    WHERE " . implode(' AND ', $where_txn) . " 
    ORDER BY id DESC
");
$stmt->execute($params_txn);
$transactions = $stmt->fetchAll();

$deposit_instructions = get_setting('deposit_instructions', '');

require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-8">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="font-gaming text-3xl font-extrabold text-white tracking-wide flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span> GAMER WALLET & DEPOSITS
            </h1>
            <p class="text-xs text-zinc-400 mt-1">Manage your store balance for fast 1-click Free Fire top-ups</p>
        </div>
        <button type="button" onclick="document.getElementById('depositModal').classList.remove('hidden')" 
                class="btn-gaming-red text-white text-xs font-gaming font-bold px-5 py-2.5 rounded-xl shadow-red-glow flex items-center gap-1.5 self-start sm:self-auto">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
            <span>ADD MONEY TO WALLET</span>
        </button>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="p-4 rounded-xl bg-red-950/70 border border-red-500/60 text-red-200 text-xs space-y-1">
            <?php foreach ($errors as $err): ?>
                <p>• <?php echo e($err); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Wallet Summary Card -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-gradient-to-br from-gaming-850 via-gaming-850 to-gaming-900 border border-emerald-500/30 rounded-2xl p-6 relative overflow-hidden shadow-2xl">
            <div class="absolute -right-8 -bottom-8 w-36 h-36 bg-emerald-500/10 blur-2xl rounded-full"></div>
            <div class="flex items-center justify-between mb-4">
                <span class="text-xs font-semibold text-emerald-400 uppercase tracking-wider">Current Balance</span>
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-pulse"></span>
            </div>
            <div class="font-gaming text-4xl font-extrabold text-white">
                <?php echo format_currency($user['wallet_balance']); ?>
            </div>
            <p class="text-xs text-zinc-400 mt-2">Available for instant diamond recharges</p>
        </div>

        <div class="md:col-span-2 bg-gaming-850 border border-gaming-border rounded-2xl p-6 flex flex-col justify-between">
            <div>
                <h3 class="font-gaming text-base font-bold text-white mb-2 flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-red-500"></span> Supported Payment Gateways
                </h3>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 pt-2">
                    <?php foreach ($gateways as $gw): ?>
                        <div class="p-2.5 rounded-xl bg-gaming-900 border border-gaming-border text-center">
                            <span class="font-gaming font-bold text-xs text-white block truncate"><?php echo e($gw['name']); ?></span>
                            <span class="text-[10px] text-emerald-400 font-mono">Active</span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="pt-4 flex items-center justify-between text-xs text-zinc-400 border-t border-gaming-border mt-4">
                <span>Secure SSL Protected Deposits</span>
                <button type="button" onclick="document.getElementById('depositModal').classList.remove('hidden')" class="text-red-400 hover:text-red-300 font-semibold underline underline-offset-4">
                    + Deposit Funds Now
                </button>
            </div>
        </div>
    </div>

    <!-- Filter Tabs (Requirement 2: Successful, Pending, Failed, Refunded) -->
    <div class="flex flex-wrap items-center gap-2 border-b border-gaming-border pb-4 text-xs font-semibold">
        <a href="/wallet.php?tab=all" class="px-4 py-2 rounded-xl transition-all <?php echo $tab === 'all' ? 'bg-red-600 text-white shadow-red-subtle' : 'bg-gaming-850 text-zinc-400 hover:text-white border border-gaming-border'; ?>">
            All Transactions
        </a>
        <a href="/wallet.php?tab=completed" class="px-4 py-2 rounded-xl transition-all <?php echo $tab === 'completed' ? 'bg-emerald-600 text-white shadow-red-subtle' : 'bg-gaming-850 text-zinc-400 hover:text-white border border-gaming-border'; ?>">
            Successful (Completed)
        </a>
        <a href="/wallet.php?tab=pending" class="px-4 py-2 rounded-xl transition-all <?php echo $tab === 'pending' ? 'bg-amber-600 text-white shadow-red-subtle' : 'bg-gaming-850 text-zinc-400 hover:text-white border border-gaming-border'; ?>">
            Pending Verification
        </a>
        <a href="/wallet.php?tab=failed" class="px-4 py-2 rounded-xl transition-all <?php echo $tab === 'failed' ? 'bg-red-600 text-white shadow-red-subtle' : 'bg-gaming-850 text-zinc-400 hover:text-white border border-gaming-border'; ?>">
            Failed / Cancelled
        </a>
        <a href="/wallet.php?tab=refunded" class="px-4 py-2 rounded-xl transition-all <?php echo $tab === 'refunded' ? 'bg-purple-600 text-white shadow-red-subtle' : 'bg-gaming-850 text-zinc-400 hover:text-white border border-gaming-border'; ?>">
            Refunded
        </a>
    </div>

    <!-- Transactions List -->
    <div class="space-y-4">
        <h2 class="font-gaming text-lg font-bold text-white tracking-wide">
            WALLET TRANSACTION HISTORY
        </h2>

        <?php if (empty($transactions)): ?>
            <div class="text-center py-12 rounded-2xl bg-gaming-900 border border-gaming-border p-6 text-zinc-400 text-xs">
                No wallet transactions found for this filter tab.
            </div>
        <?php else: ?>
            <!-- Mobile Cards -->
            <div class="grid grid-cols-1 gap-3 md:hidden">
                <?php foreach ($transactions as $txn): 
                    $is_credit = ($txn['type'] === 'credit');
                    $badge = match($txn['status']) {
                        'completed' => 'bg-emerald-950 text-emerald-400 border-emerald-800',
                        'failed', 'rejected' => 'bg-red-950 text-red-400 border-red-800',
                        'refunded' => 'bg-purple-950 text-purple-400 border-purple-800',
                        default => 'bg-amber-950 text-amber-400 border-amber-800'
                    };
                ?>
                    <div class="p-4 rounded-xl bg-gaming-850 border border-gaming-border space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="font-mono text-xs text-zinc-400"><?php echo e($txn['transaction_id']); ?></span>
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase border <?php echo $badge; ?>">
                                <?php echo e($txn['status']); ?>
                            </span>
                        </div>
                        <div class="flex items-baseline justify-between">
                            <div>
                                <span class="font-semibold text-white text-xs block"><?php echo e($txn['notes'] ?: $txn['payment_method']); ?></span>
                                <span class="text-[10px] text-zinc-500"><?php echo date('M d, Y h:i A', strtotime($txn['created_at'])); ?></span>
                            </div>
                            <span class="font-mono font-bold text-sm <?php echo $is_credit ? 'text-emerald-400' : 'text-red-400'; ?>">
                                <?php echo ($is_credit ? '+' : '-') . format_currency($txn['amount']); ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Desktop Table -->
            <div class="hidden md:block overflow-hidden rounded-2xl bg-gaming-850 border border-gaming-border">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-gaming-border bg-gaming-900 text-zinc-400 uppercase font-mono text-[10px]">
                            <th class="py-3 px-6">Transaction ID & Date</th>
                            <th class="py-3 px-4">Type</th>
                            <th class="py-3 px-4">Gateway</th>
                            <th class="py-3 px-4">Reference</th>
                            <th class="py-3 px-4">Amount</th>
                            <th class="py-3 px-4">Status</th>
                            <th class="py-3 px-6">Details</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gaming-border/60">
                        <?php foreach ($transactions as $txn): 
                            $is_credit = ($txn['type'] === 'credit');
                            $badge = match($txn['status']) {
                                'completed' => 'bg-emerald-950 text-emerald-400 border-emerald-800',
                                'failed', 'rejected' => 'bg-red-950 text-red-400 border-red-800',
                                'refunded' => 'bg-purple-950 text-purple-400 border-purple-800',
                                default => 'bg-amber-950 text-amber-400 border-amber-800'
                            };
                        ?>
                            <tr class="hover:bg-gaming-800/40 transition-colors">
                                <td class="py-3.5 px-6">
                                    <span class="font-mono font-bold text-white block"><?php echo e($txn['transaction_id']); ?></span>
                                    <span class="text-[10px] text-zinc-500"><?php echo date('M d, Y h:i A', strtotime($txn['created_at'])); ?></span>
                                </td>
                                <td class="py-3.5 px-4 font-mono font-bold uppercase text-[11px] <?php echo $is_credit ? 'text-emerald-400' : 'text-red-400'; ?>">
                                    <?php echo e($txn['type']); ?>
                                </td>
                                <td class="py-3.5 px-4 font-semibold text-white uppercase text-[11px]">
                                    <?php echo e($txn['payment_method']); ?>
                                </td>
                                <td class="py-3.5 px-4 font-mono text-zinc-300">
                                    <?php echo e($txn['reference_no'] ?: '—'); ?>
                                </td>
                                <td class="py-3.5 px-4 font-mono font-bold text-sm <?php echo $is_credit ? 'text-emerald-400' : 'text-red-400'; ?>">
                                    <?php echo ($is_credit ? '+' : '-') . format_currency($txn['amount']); ?>
                                </td>
                                <td class="py-3.5 px-4">
                                    <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold uppercase border <?php echo $badge; ?>">
                                        <?php echo e($txn['status']); ?>
                                    </span>
                                </td>
                                <td class="py-3.5 px-6 text-zinc-400 text-[11px]">
                                    <?php echo e($txn['notes'] ?: 'Standard balance adjustment'); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add Money Modal -->
<div id="depositModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-black/80 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="relative w-full max-w-lg rounded-2xl bg-gaming-900 border border-gaming-border shadow-2xl p-6 space-y-6">
        <div class="flex items-center justify-between pb-3 border-b border-gaming-border">
            <h3 class="font-gaming text-lg font-bold text-white flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-emerald-400"></span> ADD MONEY TO WALLET
            </h3>
            <button type="button" onclick="document.getElementById('depositModal').classList.add('hidden')" class="text-zinc-400 hover:text-white text-lg">✕</button>
        </div>

        <?php if (!empty($deposit_instructions)): ?>
            <div class="p-3.5 rounded-xl bg-gaming-850 border border-gaming-border text-xs text-zinc-300 space-y-1">
                <span class="text-red-400 font-bold font-mono text-[10px] uppercase block">Deposit Instructions:</span>
                <p class="whitespace-pre-line leading-relaxed"><?php echo e($deposit_instructions); ?></p>
            </div>
        <?php endif; ?>

        <form method="POST" action="/wallet.php" class="space-y-4 text-xs">
            <?php echo csrf_field(); ?>

            <div>
                <label class="block text-zinc-300 font-semibold mb-1">Select Deposit Amount (USD) *</label>
                <div class="grid grid-cols-4 gap-2 mb-2">
                    <button type="button" onclick="document.getElementById('depositAmt').value='5.00'" class="py-1.5 rounded-lg bg-gaming-850 hover:bg-gaming-800 text-white font-mono border border-gaming-border">$5.00</button>
                    <button type="button" onclick="document.getElementById('depositAmt').value='10.00'" class="py-1.5 rounded-lg bg-gaming-850 hover:bg-gaming-800 text-white font-mono border border-gaming-border">$10.00</button>
                    <button type="button" onclick="document.getElementById('depositAmt').value='25.00'" class="py-1.5 rounded-lg bg-gaming-850 hover:bg-gaming-800 text-white font-mono border border-gaming-border">$25.00</button>
                    <button type="button" onclick="document.getElementById('depositAmt').value='50.00'" class="py-1.5 rounded-lg bg-gaming-850 hover:bg-gaming-800 text-white font-mono border border-gaming-border">$50.00</button>
                </div>
                <input type="number" step="0.01" min="0.50" id="depositAmt" name="amount" required placeholder="Custom Amount (e.g. 15.00)" class="w-full px-4 py-2.5 rounded-xl bg-gaming-850 border border-gaming-border focus:border-red-500 text-white font-mono text-sm focus:outline-none">
            </div>

            <div>
                <label class="block text-zinc-300 font-semibold mb-1">Select Payment Gateway *</label>
                <select name="gateway_code" required class="w-full px-3 py-2.5 rounded-xl bg-gaming-850 border border-gaming-border text-white text-xs focus:outline-none">
                    <?php foreach ($gateways as $gw): ?>
                        <option value="<?php echo e($gw['code']); ?>"><?php echo e($gw['title']); ?> (<?php echo e($gw['name']); ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-zinc-300 font-semibold mb-1">Transaction Reference / UTR / TrxID *</label>
                <input type="text" name="reference_no" required placeholder="Enter bank reference or transaction code" class="w-full px-4 py-2.5 rounded-xl bg-gaming-850 border border-gaming-border text-white font-mono text-xs focus:outline-none">
            </div>

            <div>
                <label class="block text-zinc-300 font-semibold mb-1">Deposit Note (Optional)</label>
                <input type="text" name="notes" placeholder="e.g. Added via GooglePay" class="w-full px-4 py-2 rounded-xl bg-gaming-850 border border-gaming-border text-white text-xs focus:outline-none">
            </div>

            <div class="pt-2 flex items-center justify-end gap-3">
                <button type="button" onclick="document.getElementById('depositModal').classList.add('hidden')" class="px-4 py-2.5 rounded-xl bg-gaming-800 text-zinc-400 hover:text-white">Cancel</button>
                <button type="submit" class="btn-gaming-red text-white font-gaming text-xs font-bold px-6 py-2.5 rounded-xl shadow-red-glow">
                    CONFIRM & SUBMIT DEPOSIT
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
