<?php
$page_title = "Player Profile & Free Fire UID";
require_once __DIR__ . '/includes/functions.php';
require_login();

$user = current_user();
$errors = [];

// Available Free Fire Regions
$ff_regions = [
    'India', 'Bangladesh', 'Indonesia', 'Brazil', 'North America', 
    'Europe', 'Singapore', 'Middle East (MENA)', 'Latin America', 'Global'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validate();
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $name = sanitize($_POST['name'] ?? '');
        $phone = sanitize($_POST['phone'] ?? '');
        $ff_uid = sanitize($_POST['ff_uid'] ?? '');
        $ff_nickname = sanitize($_POST['ff_nickname'] ?? '');
        $ff_region = sanitize($_POST['ff_region'] ?? 'Global');

        if (empty($name)) {
            $errors[] = "Name cannot be empty.";
        }

        if (empty($errors)) {
            $stmt = $pdo->prepare("
                UPDATE users 
                SET name = ?, phone = ?, ff_uid = ?, ff_nickname = ?, ff_region = ? 
                WHERE id = ?
            ");
            $stmt->execute([$name, $phone, $ff_uid, $ff_nickname, $ff_region, $user['id']]);
            set_flash('success', 'Player profile and Free Fire details updated successfully!');
            header("Location: /profile.php");
            exit;
        }
    } elseif ($action === 'change_password') {
        $current_pass = (string)($_POST['current_password'] ?? '');
        $new_pass = (string)($_POST['new_password'] ?? '');
        $confirm_pass = (string)($_POST['confirm_password'] ?? '');

        if (!password_verify($current_pass, $user['password'])) {
            $errors[] = "Current password is incorrect.";
        }
        if (strlen($new_pass) < 6) {
            $errors[] = "New password must be at least 6 characters long.";
        }
        if ($new_pass !== $confirm_pass) {
            $errors[] = "New password confirmation does not match.";
        }

        if (empty($errors)) {
            $new_hash = password_hash($new_pass, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$new_hash, $user['id']]);
            set_flash('success', 'Password updated successfully!');
            header("Location: /profile.php");
            exit;
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-8">
    <div>
        <h1 class="font-gaming text-3xl font-bold text-white tracking-wide flex items-center gap-2">
            <span class="w-2.5 h-2.5 rounded-full bg-red-500"></span> PLAYER PROFILE & FREE FIRE SETTINGS
        </h1>
        <p class="text-xs text-zinc-400 mt-1">Manage your in-game UID, nickname, and account credentials</p>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="p-4 rounded-xl bg-red-950/70 border border-red-500/60 text-red-200 text-xs space-y-1">
            <?php foreach ($errors as $err): ?>
                <p>• <?php echo e($err); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- User Summary Card -->
        <div class="bg-gaming-850 border border-gaming-border rounded-2xl p-6 text-center space-y-4">
            <div class="w-20 h-20 rounded-2xl bg-gradient-to-tr from-red-600 to-amber-500 p-0.5 mx-auto shadow-red-subtle">
                <div class="w-full h-full bg-gaming-950 rounded-[14px] flex items-center justify-center font-gaming text-3xl font-bold text-white">
                    <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                </div>
            </div>
            <div>
                <h3 class="font-gaming text-xl font-bold text-white"><?php echo e($user['name'] ?: $user['username']); ?></h3>
                <p class="text-xs text-zinc-400 font-mono">@<?php echo e($user['username']); ?></p>
                <p class="text-xs text-zinc-500"><?php echo e($user['email']); ?></p>
            </div>

            <div class="pt-4 border-t border-gaming-border space-y-2 text-left text-xs">
                <div class="flex justify-between py-1 border-b border-gaming-border/60">
                    <span class="text-zinc-400">Wallet Balance:</span>
                    <span class="font-gaming font-bold text-emerald-400"><?php echo format_currency($user['wallet_balance']); ?></span>
                </div>
                <div class="flex justify-between py-1 border-b border-gaming-border/60">
                    <span class="text-zinc-400">Saved FF UID:</span>
                    <span class="font-mono font-bold text-white"><?php echo e($user['ff_uid'] ?: 'Not set'); ?></span>
                </div>
                <div class="flex justify-between py-1">
                    <span class="text-zinc-400">Server Region:</span>
                    <span class="font-semibold text-zinc-300"><?php echo e($user['ff_region'] ?: 'Global'); ?></span>
                </div>
            </div>
        </div>

        <!-- Player Information & UID Form -->
        <div class="md:col-span-2 space-y-6">
            <div class="bg-gaming-850 border border-gaming-border rounded-2xl p-6 relative overflow-hidden">
                <h2 class="font-gaming text-lg font-bold text-white mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    Free Fire Player Details
                </h2>

                <form method="POST" action="/profile.php" class="space-y-4">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="action" value="update_profile">

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1">Full Name</label>
                            <input type="text" name="name" value="<?php echo e($user['name']); ?>" required
                                   class="w-full px-3.5 py-2.5 rounded-xl bg-gaming-900 border border-gaming-border focus:border-red-500 text-sm text-white focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1">Contact Phone</label>
                            <input type="text" name="phone" value="<?php echo e($user['phone']); ?>"
                                   class="w-full px-3.5 py-2.5 rounded-xl bg-gaming-900 border border-gaming-border focus:border-red-500 text-sm text-white focus:outline-none"
                                   placeholder="+1 555 123 4567">
                        </div>
                    </div>

                    <div class="p-4 rounded-xl bg-gaming-900 border border-red-900/30 space-y-3">
                        <div class="flex items-center gap-2 text-xs font-bold text-red-400 uppercase tracking-wider">
                            <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                            Free Fire In-Game Identity
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-zinc-300 mb-1">Free Fire UID (Player ID)</label>
                                <input type="text" name="ff_uid" value="<?php echo e($user['ff_uid']); ?>"
                                       class="w-full px-3.5 py-2 rounded-lg bg-gaming-950 border border-gaming-border focus:border-red-500 text-sm text-white font-mono focus:outline-none"
                                       placeholder="e.g. 2938471029">
                                <span class="text-[10px] text-zinc-500">Find in your in-game profile card under your avatar.</span>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-zinc-300 mb-1">In-Game Nickname</label>
                                <input type="text" name="ff_nickname" value="<?php echo e($user['ff_nickname']); ?>"
                                       class="w-full px-3.5 py-2 rounded-lg bg-gaming-950 border border-gaming-border focus:border-red-500 text-sm text-white focus:outline-none"
                                       placeholder="e.g. 亗S N I P E R亗">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-zinc-300 mb-1">Server / Region</label>
                            <select name="ff_region" class="w-full px-3.5 py-2 rounded-lg bg-gaming-950 border border-gaming-border focus:border-red-500 text-sm text-white focus:outline-none">
                                <?php foreach ($ff_regions as $r): ?>
                                    <option value="<?php echo e($r); ?>" <?php echo ($user['ff_region'] === $r) ? 'selected' : ''; ?>>
                                        <?php echo e($r); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <button type="submit" class="btn-gaming-red text-white text-xs font-gaming font-bold py-2.5 px-6 rounded-xl shadow-red-subtle">
                        SAVE PLAYER DETAILS
                    </button>
                </form>
            </div>

            <!-- Change Password Card -->
            <div class="bg-gaming-850 border border-gaming-border rounded-2xl p-6">
                <h2 class="font-gaming text-lg font-bold text-white mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                    Security & Password
                </h2>

                <form method="POST" action="/profile.php" class="space-y-4">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="action" value="change_password">

                    <div>
                        <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1">Current Password</label>
                        <input type="password" name="current_password" required
                               class="w-full px-3.5 py-2.5 rounded-xl bg-gaming-900 border border-gaming-border focus:border-red-500 text-sm text-white focus:outline-none"
                               placeholder="••••••••">
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1">New Password</label>
                            <input type="password" name="new_password" required
                                   class="w-full px-3.5 py-2.5 rounded-xl bg-gaming-900 border border-gaming-border focus:border-red-500 text-sm text-white focus:outline-none"
                                   placeholder="min 6 characters">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1">Confirm New Password</label>
                            <input type="password" name="confirm_password" required
                                   class="w-full px-3.5 py-2.5 rounded-xl bg-gaming-900 border border-gaming-border focus:border-red-500 text-sm text-white focus:outline-none"
                                   placeholder="repeat new password">
                        </div>
                    </div>

                    <button type="submit" class="px-6 py-2.5 rounded-xl bg-gaming-800 hover:bg-gaming-750 text-white text-xs font-semibold border border-gaming-border">
                        UPDATE PASSWORD
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
