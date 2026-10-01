<?php
$page_title = "Register Gamer Account";
require_once __DIR__ . '/includes/functions.php';

// Redirect if already logged in
if (is_logged_in()) {
    header("Location: /dashboard.php");
    exit;
}

$errors = [];
$name = '';
$username = '';
$email = '';
$phone = '';
$ff_uid = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validate();

    $name = sanitize($_POST['name'] ?? '');
    $username = sanitize($_POST['username'] ?? '');
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $phone = sanitize($_POST['phone'] ?? '');
    $ff_uid = sanitize($_POST['ff_uid'] ?? '');
    $password = (string)($_POST['password'] ?? '');
    $confirm_password = (string)($_POST['confirm_password'] ?? '');

    // Validation
    if (empty($name)) {
        $errors[] = "Full Name is required.";
    }
    if (empty($username) || strlen($username) < 3 || !preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $errors[] = "Username must be at least 3 characters and contain only letters, numbers, and underscores.";
    }
    if (!$email) {
        $errors[] = "A valid Email address is required.";
    }
    if (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters long.";
    }
    if ($password !== $confirm_password) {
        $errors[] = "Password confirmation does not match.";
    }

    // Check duplicate username & email in MySQL
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            $errors[] = "Username or Email is already registered. Please log in or use a different one.";
        }
    }

    // Insert user into MySQL
    if (empty($errors)) {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("
            INSERT INTO users (name, username, email, password, phone, ff_uid, wallet_balance, status) 
            VALUES (?, ?, ?, ?, ?, ?, 0.00, 'active')
        ");
        $stmt->execute([$name, $username, $email, $hashed, $phone, $ff_uid]);
        $new_user_id = $pdo->lastInsertId();

        // Automatically log in
        $_SESSION['user_id'] = $new_user_id;
        $_SESSION['username'] = $username;
        set_flash('success', "Welcome to FireZone Store, " . htmlspecialchars($username) . "! Your account has been created.");
        header("Location: /dashboard.php");
        exit;
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="py-12 px-4 sm:px-6 lg:px-8 max-w-md mx-auto">
    <div class="bg-gaming-850 border border-gaming-border rounded-2xl p-6 sm:p-8 shadow-2xl relative overflow-hidden">
        <!-- Accent top line -->
        <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-red-600 to-amber-500"></div>

        <div class="text-center mb-6">
            <h1 class="font-gaming text-2xl font-bold text-white tracking-wider">CREATE GAMER ACCOUNT</h1>
            <p class="text-xs text-zinc-400 mt-1">Join FireZone for instant Free Fire top-ups & wallet perks</p>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="mb-5 p-3.5 rounded-xl bg-red-950/70 border border-red-500/50 text-red-200 text-xs space-y-1">
                <?php foreach ($errors as $err): ?>
                    <p>• <?php echo e($err); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="/register.php" class="space-y-4">
            <?php echo csrf_field(); ?>

            <div>
                <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1">Full Name</label>
                <input type="text" name="name" value="<?php echo e($name); ?>" required
                       class="w-full px-3.5 py-2.5 rounded-xl bg-gaming-900 border border-gaming-border focus:border-red-500 text-sm text-white focus:outline-none transition-colors"
                       placeholder="e.g. Alex Drake">
            </div>

            <div>
                <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1">Username</label>
                <input type="text" name="username" value="<?php echo e($username); ?>" required
                       class="w-full px-3.5 py-2.5 rounded-xl bg-gaming-900 border border-gaming-border focus:border-red-500 text-sm text-white focus:outline-none transition-colors"
                       placeholder="e.g. shadow_sniper">
            </div>

            <div>
                <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1">Email Address</label>
                <input type="email" name="email" value="<?php echo e($email); ?>" required
                       class="w-full px-3.5 py-2.5 rounded-xl bg-gaming-900 border border-gaming-border focus:border-red-500 text-sm text-white focus:outline-none transition-colors"
                       placeholder="gamer@example.com">
            </div>

            <div>
                <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1">Free Fire UID (Optional)</label>
                <input type="text" name="ff_uid" value="<?php echo e($ff_uid); ?>"
                       class="w-full px-3.5 py-2.5 rounded-xl bg-gaming-900 border border-gaming-border focus:border-red-500 text-sm text-white focus:outline-none transition-colors font-mono"
                       placeholder="e.g. 1928392102">
                <span class="text-[10px] text-zinc-400">Save your UID once to auto-fill every purchase.</span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1">Password</label>
                    <input type="password" name="password" required
                           class="w-full px-3.5 py-2.5 rounded-xl bg-gaming-900 border border-gaming-border focus:border-red-500 text-sm text-white focus:outline-none transition-colors"
                           placeholder="••••••••">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1">Confirm Password</label>
                    <input type="password" name="confirm_password" required
                           class="w-full px-3.5 py-2.5 rounded-xl bg-gaming-900 border border-gaming-border focus:border-red-500 text-sm text-white focus:outline-none transition-colors"
                           placeholder="••••••••">
                </div>
            </div>

            <button type="submit" class="w-full btn-gaming-red text-white font-gaming text-sm font-bold py-3 rounded-xl shadow-red-glow mt-2">
                COMPLETE REGISTRATION &rarr;
            </button>
        </form>

        <div class="mt-6 text-center text-xs text-zinc-400 border-t border-gaming-border pt-4">
            Already have a gaming account? 
            <a href="/login.php" class="text-red-400 hover:text-red-300 font-semibold underline underline-offset-2">Log In Here</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
