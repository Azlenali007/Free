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

    if (empty($identifier) || empty($password)) {
        $errors[] = "Please enter your username/email and password.";
    } else {
        // Query user by username or email
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1");
        $stmt->execute([$identifier, $identifier]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            if ($user['status'] === 'banned') {
                $errors[] = "Your account has been suspended. Please contact customer support.";
            } else {
                // Successful login
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];

                // Update session
                session_regenerate_id(true);

                set_flash('success', "Welcome back, " . htmlspecialchars($user['name'] ?: $user['username']) . "!");
                
                // Safe redirect
                if (empty($redirect) || strpos($redirect, 'http') === 0 || strpos($redirect, '//') === 0) {
                    $redirect = '/dashboard.php';
                }
                header("Location: " . $redirect);
                exit;
            }
        } else {
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
            <div class="w-12 h-12 rounded-xl bg-red-950/80 border border-red-800/40 text-red-500 flex items-center justify-center mx-auto mb-3">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                </svg>
            </div>
            <h1 class="font-gaming text-2xl font-bold text-white tracking-wider">GAMER LOGIN</h1>
            <p class="text-xs text-zinc-400 mt-1">Access your Free Fire orders, wallet balance, and top-up panel</p>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="mb-5 p-3.5 rounded-xl bg-red-950/70 border border-red-500/50 text-red-200 text-xs space-y-1">
                <?php foreach ($errors as $err): ?>
                    <p>• <?php echo e($err); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="/login.php" class="space-y-4">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="redirect" value="<?php echo e($redirect); ?>">

            <div>
                <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1">Username or Email</label>
                <input type="text" name="identifier" value="<?php echo e($identifier); ?>" required autofocus
                       class="w-full px-3.5 py-2.5 rounded-xl bg-gaming-900 border border-gaming-border focus:border-red-500 text-sm text-white focus:outline-none transition-colors"
                       placeholder="Enter your username or email">
            </div>

            <div>
                <div class="flex items-center justify-between mb-1">
                    <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider">Password</label>
                </div>
                <input type="password" name="password" required
                       class="w-full px-3.5 py-2.5 rounded-xl bg-gaming-900 border border-gaming-border focus:border-red-500 text-sm text-white focus:outline-none transition-colors"
                       placeholder="Enter your password">
            </div>

            <button type="submit" class="w-full btn-gaming-red text-white font-gaming text-sm font-bold py-3 rounded-xl shadow-red-glow mt-2">
                SIGN IN TO DASHBOARD &rarr;
            </button>
        </form>

        <div class="mt-6 text-center text-xs text-zinc-400 border-t border-gaming-border pt-4">
            Don't have an account yet? 
            <a href="/register.php" class="text-red-400 hover:text-red-300 font-semibold underline underline-offset-2">Create Gamer Account</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
