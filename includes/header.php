<?php
require_once __DIR__ . '/functions.php';
$site_name = get_setting('site_name', 'FireZone Store');
$site_tagline = get_setting('site_tagline', 'Instant Free Fire Diamonds & Gaming Top-Ups');
$page_title = isset($page_title) ? $page_title . ' - ' . $site_name : $site_name . ' | ' . $site_tagline;
$announcement = get_setting('announcement', '');
$user = current_user();
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title><?php echo e($page_title); ?></title>
    <meta name="description" content="<?php echo e($site_tagline); ?>">
    
    <!-- Tailwind CSS with custom gaming theme config -->
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
                            850: '#101017',
                            800: '#151520',
                            750: '#1b1b28',
                            700: '#232334',
                            600: '#32324a',
                            border: '#262638',
                            borderRed: '#dc262640',
                            red: '#ef4444',
                            darkred: '#991b1b',
                            crimson: '#dc2626',
                            bright: '#ff334b'
                        }
                    },
                    boxShadow: {
                        'red-glow': '0 0 20px rgba(239, 68, 68, 0.35)',
                        'red-glow-lg': '0 0 35px rgba(239, 68, 68, 0.55)',
                        'red-subtle': '0 0 10px rgba(239, 68, 68, 0.15)',
                    }
                }
            }
        }
    </script>

    <!-- Custom Gaming CSS -->
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="bg-gaming-900 text-zinc-100 min-h-screen flex flex-col antialiased selection:bg-red-600 selection:text-white">

<?php if (!empty($announcement)): ?>
    <div class="bg-gradient-to-r from-red-950/80 via-red-900/60 to-zinc-950 border-b border-red-900/40 text-xs py-2 px-4 text-center text-red-200 font-medium tracking-wide flex items-center justify-center gap-2">
        <span class="inline-block w-2 h-2 rounded-full bg-red-500 animate-ping"></span>
        <?php echo e($announcement); ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/navbar.php'; ?>
<?php require_once __DIR__ . '/alerts.php'; ?>

<main class="flex-grow">
