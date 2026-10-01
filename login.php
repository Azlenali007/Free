<?php
$page_title = "Login to Gamer Account";
require_once __DIR__ . '/includes/functions.php';

// If already logged in, redirect
if (is_logged_in()) {
    header("Location: /dashboard.php");
    exit;
}

$errors = [];
$identifier = '';
$redirect = sanitize($_GET['redirect'] ?? '/dashboard.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validate();

    $identifier = trim($_POST['identifier'] ?? '');
    $password = (string)($_POST['password'] ?? '');
    $redirect = sanitize($_POST['redirect'] ?? '/dashboard.php');

    // Check account lockout
    $lockout_mins = is_login_locked($identifier);
    if ($lockout_mins !== false) {
        $errors[] = "Too many failed attempts. This account is temporarily locked for security. Try again in " . $lockout_mins . " minutes.";
    } elseif (empty($identifier) || empty($password)) {
        $errors[] = "Please enter your username/email and password.";
    } else {
        // Query user by username or email
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1");
        $stmt->execute([$identifier, $identifier]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            if ($user['status'] === 'banned') {
                $errors[] = "Your account has been suspended. Please contact customer support.";
                log_security_event('banned_login_attempt', "Banned user attempted login: {$identifier}", 'warning', $user['id']);
            } else {
                // Clear any previous failed attempts
                clear_login_attempts($identifier);

                // Update last login
                $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")->execute([$user['id']]);

                // Successful login
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];

                // Update session
                session_regenerate_id(true);

                log_security_event('user_login', "Successful user login: {$user['username']}", 'info', $user['id']);

                set_flash('success', "Welcome back, " . htmlspecialchars($user['name'] ?: $user['username']) . "!");
                
                // Safe redirect
                if (empty($redirect) || strpos($redirect, 'http') === 0 || strpos($redirect, '//') === 0) {
                    $redirect = '/dashboard.php';
                }
                header("Location: " . $redirect);
                exit;
            }
        } else {
            record_failed_login($identifier);
            $errors[] = "Invalid credentials. Please verify your username/email and password.";
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="py-16 px-4 sm:px-6 lg:px-8 max-w-md mx-auto">
    <div class="bg-gaming-850 border border-gaming-border rounded-2xl p-6 sm:p-8 shadow-2xl relative overflow-hidden">
        <!-- Accent top line -->
        <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-red-600 to-amber-500"></div>

        <div class="text-center mb-6">
            <h1 class="font-gaming text-2xl font-bold text-white tracking-wider">GAMER LOGIN</h1>
            <p class="text-xs text-zinc-400 mt-1">Access your Free Fire wallet, orders, and tickets</p>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="mb-5 p-3.5 rounded-xl bg-red-950/70 border border-red-500/50 text-red-200 text-xs space-y-1">
                <?php foreach ($errors as $err): ?>
                    <p>• <?php echo e($err); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="/login.php" class="space-y-4 text-xs">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="redirect" value="<?php echo e($redirect); ?>">

            <div>
                <label class="block text-zinc-300 font-semibold mb-1">Username or Email *</label>
                <input type="text" name="identifier" value="<?php echo e($identifier); ?>" required placeholder="e.g. fire_gamer or gamer@example.com" class="w-full px-4 py-2.5 rounded-xl bg-gaming-900 border border-gaming-border focus:border-red-500 text-white text-xs focus:outline-none">
            </div>

            <div>
                <div class="flex items-center justify-between mb-1">
                    <label class="block text-zinc-300 font-semibold">Password *</label>
                </div>
                <input type="password" name="password" required placeholder="Enter your password" class="w-full px-4 py-2.5 rounded-xl bg-gaming-900 border border-gaming-border focus:border-red-500 text-white text-xs focus:outline-none">
            </div>

            <button type="submit" class="w-full btn-gaming-red text-white font-gaming text-xs font-bold py-3 rounded-xl shadow-red-glow mt-2">
                LOG IN TO STORE
            </button>
        </form>

        <div class="mt-6 pt-4 border-t border-gaming-border text-center text-xs text-zinc-400">
            Don't have an account? 
            <a href="/register.php" class="text-red-400 hover:text-red-300 font-semibold underline underline-offset-4 ml-1">
                Register Free
            </a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
