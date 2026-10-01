<?php
$page_title = "Gamer Wallet & Add Money";
require_once __DIR__ . '/includes/functions.php';
require_login();

$user = current_user();
$errors = [];

// Handle Add Money deposit request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validate();

    $amount = (float)($_POST['amount'] ?? 0);
    $method = sanitize($_POST['payment_method'] ?? 'UPI / Direct Bank');
    $reference_no = sanitize($_POST['reference_no'] ?? '');
    $notes = sanitize($_POST['notes'] ?? '');

    if ($amount <= 0) {
        $errors[] = "Please enter a valid deposit amount greater than 0.";
    }
    if (empty($reference_no)) {
        $errors[] = "Please provide your payment reference number / UTR ID for verification.";
    }

    if (empty($errors)) {
        $txn_id = generate_transaction_id();
        $stmt = $pdo->prepare("
            INSERT INTO wallet_transactions 
            (transaction_id, user_id, type, amount, payment_method, reference_no, status, notes) 
            VALUES (?, ?, 'credit', ?, ?, ?, 'pending', ?)
        ");
        $stmt->execute([
            $txn_id,
            $user['id'],
            $amount,
            $method,
            $reference_no,
            $notes ?: 'Manual deposit request via ' . $method
        ]);

        set_flash('success', "Deposit request for " . format_currency($amount) . " submitted! Transaction ID: " . $txn_id . ". Admin will verify and credit your wallet shortly.");
        header("Location: /wallet.php");
        exit;
    }
}

// Fetch all transactions for this user from MySQL
$stmt = $pdo->prepare("
    SELECT * FROM wallet_transactions 
    WHERE user_id = ? 
    ORDER BY id DESC
");
$stmt->execute([$user['id']]);
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
                    <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    How to Deposit Funds
                </h3>
                <p class="text-xs text-zinc-400 leading-relaxed">
                    Transfer money directly using our official gaming payment channels (UPI, QR, Bank Transfer). Submit your transaction reference ID to request verification. Once verified by our automated team, your wallet balance will be credited instantly.
                </p>
            </div>
            <div class="pt-4 flex flex-wrap items-center gap-3">
                <button type="button" onclick="document.getElementById('depositModal').classList.remove('hidden')" 
                        class="px-4 py-2 rounded-xl bg-gaming-800 hover:bg-gaming-750 text-xs font-semibold text-white border border-gaming-border">
                    View Deposit Instructions & Form
                </button>
            </div>
        </div>
    </div>

    <!-- Transaction History Section -->
    <div class="bg-gaming-850 border border-gaming-border rounded-2xl p-6 space-y-4 shadow-2xl">
        <div class="flex items-center justify-between pb-3 border-b border-gaming-border">
            <h2 class="font-gaming text-xl font-bold text-white tracking-wide">WALLET TRANSACTIONS</h2>
            <span class="text-xs text-zinc-400 font-mono"><?php echo count($transactions); ?> Records Found</span>
        </div>

        <?php if (!empty($transactions)): ?>
            <!-- Desktop Table View -->
            <div class="hidden sm:block overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-gaming-border text-xs text-zinc-400 uppercase font-gaming">
                            <th class="py-3 px-3">Txn ID</th>
                            <th class="py-3 px-3">Type</th>
                            <th class="py-3 px-3">Amount</th>
                            <th class="py-3 px-3">Method / Ref</th>
                            <th class="py-3 px-3">Status</th>
                            <th class="py-3 px-3">Date</th>
                            <th class="py-3 px-3">Notes</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gaming-border/60 text-xs">
                        <?php foreach ($transactions as $txn): 
                            $is_credit = $txn['type'] === 'credit';
                            $status_class = match($txn['status']) {
                                'completed' => 'bg-emerald-950/80 text-emerald-400 border-emerald-800/40',
                                'rejected' => 'bg-red-950/80 text-red-400 border-red-800/40',
                                default => 'bg-amber-950/80 text-amber-400 border-amber-800/40'
                            };
                        ?>
                            <tr class="hover:bg-gaming-800/40 transition-colors">
                                <td class="py-3 px-3 font-mono font-bold text-white"><?php echo e($txn['transaction_id']); ?></td>
                                <td class="py-3 px-3">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase <?php echo $is_credit ? 'bg-emerald-950 text-emerald-400 border border-emerald-800/40' : 'bg-red-950 text-red-400 border border-red-800/40'; ?>">
                                        <?php echo $is_credit ? '+ Deposit' : '- Purchase'; ?>
                                    </span>
                                </td>
                                <td class="py-3 px-3 font-gaming font-bold text-sm <?php echo $is_credit ? 'text-emerald-400' : 'text-zinc-200'; ?>">
                                    <?php echo ($is_credit ? '+' : '-') . format_currency($txn['amount']); ?>
                                </td>
                                <td class="py-3 px-3">
                                    <div class="text-zinc-300"><?php echo e($txn['payment_method']); ?></div>
                                    <?php if (!empty($txn['reference_no'])): ?>
                                        <div class="font-mono text-[11px] text-zinc-500"><?php echo e($txn['reference_no']); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3 px-3">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase border <?php echo $status_class; ?>">
                                        <?php echo e($txn['status']); ?>
                                    </span>
                                </td>
                                <td class="py-3 px-3 text-zinc-400"><?php echo date('M d, Y H:i', strtotime($txn['created_at'])); ?></td>
                                <td class="py-3 px-3 text-zinc-400 max-w-xs truncate"><?php echo e($txn['notes'] ?: '-'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Mobile Card View -->
            <div class="sm:hidden space-y-3">
                <?php foreach ($transactions as $txn): 
                    $is_credit = $txn['type'] === 'credit';
                    $status_class = match($txn['status']) {
                        'completed' => 'bg-emerald-950/80 text-emerald-400 border-emerald-800/40',
                        'rejected' => 'bg-red-950/80 text-red-400 border-red-800/40',
                        default => 'bg-amber-950/80 text-amber-400 border-amber-800/40'
                    };
                ?>
                    <div class="p-3.5 rounded-xl bg-gaming-900 border border-gaming-border space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="font-mono text-xs font-bold text-white"><?php echo e($txn['transaction_id']); ?></span>
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase border <?php echo $status_class; ?>">
                                <?php echo e($txn['status']); ?>
                            </span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-zinc-400"><?php echo e($txn['payment_method']); ?></span>
                            <span class="font-gaming font-extrabold text-base <?php echo $is_credit ? 'text-emerald-400' : 'text-zinc-200'; ?>">
                                <?php echo ($is_credit ? '+' : '-') . format_currency($txn['amount']); ?>
                            </span>
                        </div>
                        <?php if (!empty($txn['reference_no'])): ?>
                            <div class="text-[11px] font-mono text-zinc-500">Ref: <?php echo e($txn['reference_no']); ?></div>
                        <?php endif; ?>
                        <div class="pt-2 border-t border-gaming-border/60 text-[10px] text-zinc-500">
                            <?php echo date('M d, Y h:i A', strtotime($txn['created_at'])); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-10 text-zinc-500 text-xs">
                No wallet transactions recorded yet. Click "Add Money to Wallet" above to make your first deposit!
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add Money Modal Dialog -->
<div id="depositModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
    <div class="bg-gaming-900 border border-gaming-border rounded-2xl max-w-lg w-full p-6 space-y-5 shadow-2xl relative">
        <button type="button" onclick="document.getElementById('depositModal').classList.add('hidden')" 
                class="absolute top-4 right-4 text-zinc-400 hover:text-white text-lg">
            ✕
        </button>

        <div class="border-b border-gaming-border pb-3">
            <h3 class="font-gaming text-xl font-bold text-white flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span> ADD MONEY TO WALLET
            </h3>
            <p class="text-xs text-zinc-400 mt-1">Manual top-up with verification</p>
        </div>

        <!-- Instructions from settings -->
        <div class="p-3.5 rounded-xl bg-gaming-950 border border-red-900/30 text-xs text-zinc-300 whitespace-pre-line font-mono">
            <?php echo e($deposit_instructions); ?>
        </div>

        <form method="POST" action="/wallet.php" class="space-y-4">
            <?php echo csrf_field(); ?>

            <div>
                <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1">Deposit Amount ($)</label>
                <div class="flex items-center gap-2 mb-2">
                    <?php foreach ([5, 10, 25, 50, 100] as $preset): ?>
                        <button type="button" onclick="document.getElementById('depositInput').value='<?php echo $preset; ?>'" 
                                class="px-2.5 py-1 rounded bg-gaming-800 hover:bg-red-950 text-xs font-gaming font-bold text-zinc-200 border border-gaming-border">
                            +$<?php echo $preset; ?>
                        </button>
                    <?php endforeach; ?>
                </div>
                <input type="number" id="depositInput" name="amount" step="0.01" min="1" required
                       class="w-full px-3.5 py-2.5 rounded-xl bg-gaming-950 border border-gaming-border focus:border-red-500 text-lg font-gaming font-bold text-white focus:outline-none"
                       placeholder="Enter amount (e.g. 25.00)">
            </div>

            <div>
                <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1">Payment Method</label>
                <select name="payment_method" class="w-full px-3.5 py-2.5 rounded-xl bg-gaming-950 border border-gaming-border focus:border-red-500 text-sm text-white focus:outline-none">
                    <option value="UPI / Instant Pay">UPI / Instant Pay</option>
                    <option value="Bank Wire Transfer">Bank Wire Transfer</option>
                    <option value="QR Code Payment">QR Code Payment</option>
                    <option value="Crypto / USDT">Crypto / USDT</option>
                    <option value="Voucher Code">Voucher Code</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1">
                    Transaction Reference No. / UTR <span class="text-red-500">*</span>
                </label>
                <input type="text" name="reference_no" required
                       class="w-full px-3.5 py-2.5 rounded-xl bg-gaming-950 border border-gaming-border focus:border-red-500 text-sm text-white focus:outline-none font-mono"
                       placeholder="e.g. UTR 429103982104 / Bank Txn Ref">
                <span class="text-[10px] text-zinc-500 mt-1 block">Found on your bank/UPI payment receipt.</span>
            </div>

            <div>
                <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1">Notes / Sender Info (Optional)</label>
                <input type="text" name="notes"
                       class="w-full px-3.5 py-2.5 rounded-xl bg-gaming-950 border border-gaming-border focus:border-red-500 text-xs text-white focus:outline-none"
                       placeholder="e.g. Paid from John's Account">
            </div>

            <div class="pt-2 flex gap-3">
                <button type="submit" class="flex-1 btn-gaming-red text-white font-gaming text-sm font-bold py-3 rounded-xl shadow-red-glow">
                    SUBMIT DEPOSIT PROOF &rarr;
                </button>
                <button type="button" onclick="document.getElementById('depositModal').classList.add('hidden')"
                        class="px-5 py-3 rounded-xl bg-gaming-800 text-zinc-400 hover:text-white text-xs font-semibold">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
