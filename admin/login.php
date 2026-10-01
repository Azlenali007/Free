<?php
require_once __DIR__ . '/../includes/functions.php';

if (is_admin_logged_in()) {
    header("Location: /admin/index.php");
    exit;
}

$errors = [];
$identifier = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validate();

    $identifier = trim($_POST['identifier'] ?? '');
    $password = (string)($_POST['password'] ?? '');

    if (empty($identifier) || empty($password)) {
        $errors[] = "Please provide your admin username and password.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ? OR email = ? LIMIT 1");
        $stmt->execute([$identifier, $identifier]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password'])) {
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            session_regenerate_id(true);

            $pdo->prepare("UPDATE admins SET last_login = NOW() WHERE id = ?")->execute([$admin['id']]);

            set_flash('success', "Welcome back to FireZone Admin Console, " . htmlspecialchars($admin['name']) . "!");
            header("Location: /admin/index.php");
            exit;
        } else {
            $errors[] = "Invalid administrator credentials.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - FireZone Store Console</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        gaming: {
                            950: '#07070a',
                            900: '#0a0a0f',
                            850: '#111118',
                            800: '#161622',
                            border: '#272738',
                            red: '#ef4444'
                        }
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="bg-gaming-950 text-zinc-100 min-h-screen flex items-center justify-center p-4">

<div class="max-w-md w-full bg-gaming-900 border border-gaming-border rounded-2xl p-6 sm:p-8 shadow-2xl relative overflow-hidden">
    <div class="absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r from-red-600 via-red-500 to-amber-500"></div>

    <div class="text-center mb-6">
        <div class="w-14 h-14 mx-auto rounded-2xl bg-gradient-to-tr from-red-600 to-amber-500 p-0.5 shadow-lg mb-3 flex items-center justify-center">
            <div class="w-full h-full bg-gaming-950 rounded-[14px] flex items-center justify-center font-gaming text-xl font-bold text-red-500">
                FZ
            </div>
        </div>
        <h1 class="font-gaming text-2xl font-bold text-white tracking-wider">ADMIN CONSOLE LOGIN</h1>
        <p class="text-xs text-zinc-400 mt-1">Authorized personnel only. Access is monitored and logged.</p>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="mb-5 p-3.5 rounded-xl bg-red-950/70 border border-red-500/50 text-red-200 text-xs space-y-1">
            <?php foreach ($errors as $err): ?>
                <p>• <?php echo htmlspecialchars($err, ENT_QUOTES, 'UTF-8'); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="/admin/login.php" class="space-y-4">
        <?php echo csrf_field(); ?>

        <div>
            <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1">Admin Username / Email</label>
            <input type="text" name="identifier" value="<?php echo htmlspecialchars($identifier, ENT_QUOTES, 'UTF-8'); ?>" required autofocus
                   class="w-full px-3.5 py-2.5 rounded-xl bg-gaming-950 border border-gaming-border focus:border-red-500 text-sm text-white focus:outline-none transition-colors"
                   placeholder="admin or admin@firezone.com">
        </div>

        <div>
            <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1">Password</label>
            <input type="password" name="password" required
                   class="w-full px-3.5 py-2.5 rounded-xl bg-gaming-950 border border-gaming-border focus:border-red-500 text-sm text-white focus:outline-none transition-colors"
                   placeholder="Enter your password">
        </div>

        <button type="submit" class="w-full btn-gaming-red text-white font-gaming text-sm font-bold py-3 rounded-xl shadow-red-glow mt-2">
            ACCESS CONSOLE &rarr;
        </button>
    </form>

    <div class="mt-6 text-center text-xs text-zinc-400 border-t border-gaming-border pt-4">
        <a href="/index.php" class="text-zinc-400 hover:text-white">&larr; Return to Store Front</a>
    </div>
</div>

</body>
</html>
