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
$ref_code = sanitize($_GET['ref'] ?? $_POST['ref'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validate();

    // Rate Limiting
    if (!check_rate_limit('register', 10, 300)) {
        $errors[] = "Too many registration attempts from your IP. Please wait a few minutes.";
    }

    $name = sanitize($_POST['name'] ?? '');
    $username = sanitize($_POST['username'] ?? '');
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $phone = sanitize($_POST['phone'] ?? '');
    $ff_uid = sanitize($_POST['ff_uid'] ?? '');
    $password = (string)($_POST['password'] ?? '');
    $confirm_password = (string)($_POST['confirm_password'] ?? '');
    $ref_code = sanitize($_POST['ref'] ?? '');

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

    // Check referral code
    $referred_by_id = null;
    if (!empty($ref_code) && empty($errors)) {
        $stmt_ref = $pdo->prepare("SELECT id FROM users WHERE referral_code = ?");
        $stmt_ref->execute([$ref_code]);
        $ref_user = $stmt_ref->fetch();
        if ($ref_user) {
            $referred_by_id = $ref_user['id'];
        }
    }

    // Insert user into MySQL
    if (empty($errors)) {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $my_ref_code = 'FZ' . strtoupper(substr(md5(uniqid($username, true)), 0, 6));

        $stmt = $pdo->prepare("
            INSERT INTO users (name, username, email, password, phone, ff_uid, referral_code, referred_by, wallet_balance, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0.00, 'active')
        ");
        $stmt->execute([$name, $username, $email, $hashed, $phone, $ff_uid, $my_ref_code, $referred_by_id]);
        $new_user_id = $pdo->lastInsertId();

        // Create welcome notification
        create_notification(
            $new_user_id, 
            "Welcome to FireZone Store!", 
            "Your gamer account is ready. Deposit to your wallet for 1-click Free Fire top-ups.", 
            'account', 
            '/wallet.php'
        );

        log_security_event('user_registered', "New user registered: {$username} ({$email})", 'info', $new_user_id);

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
            <?php if (!empty($ref_code)): ?>
                <div class="mt-2 inline-block px-2.5 py-0.5 rounded bg-emerald-950 text-emerald-400 border border-emerald-800 text-[10px] font-mono">
                    Referral Code Applied: <?php echo e($ref_code); ?>
                </div>
            <?php endif; ?>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="mb-5 p-3.5 rounded-xl bg-red-950/70 border border-red-500/50 text-red-200 text-xs space-y-1">
                <?php foreach ($errors as $err): ?>
                    <p>• <?php echo e($err); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="/register.php" class="space-y-4 text-xs">
            <?php echo csrf_field(); ?>
            <?php if (!empty($ref_code)): ?>
                <input type="hidden" name="ref" value="<?php echo e($ref_code); ?>">
            <?php endif; ?>

            <div>
                <label class="block text-zinc-300 font-semibold mb-1">Full Name *</label>
                <input type="text" name="name" value="<?php echo e($name); ?>" required placeholder="e.g. John Doe" class="w-full px-4 py-2.5 rounded-xl bg-gaming-900 border border-gaming-border focus:border-red-500 text-white text-xs focus:outline-none">
            </div>

            <div>
                <label class="block text-zinc-300 font-semibold mb-1">Username *</label>
                <input type="text" name="username" value="<?php echo e($username); ?>" required placeholder="e.g. fire_gamer" class="w-full px-4 py-2.5 rounded-xl bg-gaming-900 border border-gaming-border focus:border-red-500 text-white font-mono text-xs focus:outline-none">
            </div>

            <div>
                <label class="block text-zinc-300 font-semibold mb-1">Email Address *</label>
                <input type="email" name="email" value="<?php echo e($email); ?>" required placeholder="e.g. gamer@example.com" class="w-full px-4 py-2.5 rounded-xl bg-gaming-900 border border-gaming-border focus:border-red-500 text-white text-xs focus:outline-none">
            </div>

            <div>
                <label class="block text-zinc-300 font-semibold mb-1">Free Fire UID (Optional, save for 1-click orders)</label>
                <input type="text" name="ff_uid" value="<?php echo e($ff_uid); ?>" placeholder="e.g. 192837192" class="w-full px-4 py-2.5 rounded-xl bg-gaming-900 border border-gaming-border focus:border-red-500 text-white font-mono text-xs focus:outline-none">
            </div>

            <div>
                <label class="block text-zinc-300 font-semibold mb-1">Password *</label>
                <input type="password" name="password" required placeholder="Minimum 6 characters" class="w-full px-4 py-2.5 rounded-xl bg-gaming-900 border border-gaming-border focus:border-red-500 text-white text-xs focus:outline-none">
            </div>

            <div>
                <label class="block text-zinc-300 font-semibold mb-1">Confirm Password *</label>
                <input type="password" name="confirm_password" required placeholder="Repeat your password" class="w-full px-4 py-2.5 rounded-xl bg-gaming-900 border border-gaming-border focus:border-red-500 text-white text-xs focus:outline-none">
            </div>

            <button type="submit" class="w-full btn-gaming-red text-white font-gaming text-xs font-bold py-3 rounded-xl shadow-red-glow mt-4">
                CREATE MY ACCOUNT
            </button>
        </form>

        <div class="mt-6 pt-4 border-t border-gaming-border text-center text-xs text-zinc-400">
            Already have an account? 
            <a href="/login.php" class="text-red-400 hover:text-red-300 font-semibold underline underline-offset-4 ml-1">
                Log In Here
            </a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
