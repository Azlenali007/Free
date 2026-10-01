<?php
$page_title = "Database Backup & Export";
require_once __DIR__ . '/includes/admin_header.php';

// Handle SQL Backup Download Action
if (isset($_GET['action']) && $_GET['action'] === 'download_sql') {
    // Generate clean SQL dump of all tables and data
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

    $sql_dump = "-- FireZone Store MySQL Database Backup\n";
    $sql_dump .= "-- Generated on: " . date('Y-m-d H:i:s') . "\n";
    $sql_dump .= "-- Database: " . DB_NAME . "\n";
    $sql_dump .= "-- Global Currency: INR (₹)\n\n";
    $sql_dump .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

    foreach ($tables as $t) {
        // Table structure
        $create_stmt = $pdo->query("SHOW CREATE TABLE `{$t}`")->fetch(PDO::FETCH_ASSOC);
        $sql_dump .= "DROP TABLE IF EXISTS `{$t}`;\n";
        $sql_dump .= $create_stmt['Create Table'] . ";\n\n";

        // Table data
        $rows = $pdo->query("SELECT * FROM `{$t}`")->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($rows)) {
            $cols = array_keys($rows[0]);
            $col_names = implode('`, `', $cols);

            $sql_dump .= "INSERT INTO `{$t}` (`{$col_names}`) VALUES\n";
            $val_lines = [];
            foreach ($rows as $r) {
                $escaped_vals = [];
                foreach ($r as $val) {
                    if ($val === null) {
                        $escaped_vals[] = "NULL";
                    } else {
                        $escaped_vals[] = $pdo->quote($val);
                    }
                }
                $val_lines[] = "(" . implode(', ', $escaped_vals) . ")";
            }
            $sql_dump .= implode(",\n", $val_lines) . ";\n\n";
        }
    }

    $sql_dump .= "SET FOREIGN_KEY_CHECKS=1;\n";

    log_admin_activity('download_database_backup', "Exported full SQL backup of database: " . DB_NAME);

    // Download headers
    $filename = 'firezone_store_backup_' . date('Ymd_His') . '.sql';
    header('Content-Type: application/sql');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($sql_dump));
    echo $sql_dump;
    exit;
}

// Table inspection for metrics
$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
$table_stats = [];
$total_rows = 0;

foreach ($tables as $t) {
    $count = (int)$pdo->query("SELECT COUNT(*) FROM `{$t}`")->fetchColumn();
    $total_rows += $count;
    $table_stats[] = [
        'name' => $t,
        'rows' => $count
    ];
}
?>

<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="font-gaming text-2xl sm:text-3xl font-bold text-white tracking-wide flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-red-500"></span> DATABASE BACKUP & DATA EXPORT
            </h1>
            <p class="text-xs text-zinc-400 mt-1">Export full MySQL schemas, customer accounts, order history, and product tables safely</p>
        </div>
        <div>
            <a href="/admin/backup.php?action=download_sql" class="btn-gaming-red text-white text-xs font-gaming font-bold px-5 py-3 rounded-xl shadow-red-subtle inline-flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                <span>Download Instant .SQL Backup</span>
            </a>
        </div>
    </div>

    <!-- Quick Stats Card -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-gaming-850 border border-gaming-border rounded-xl p-4">
            <span class="text-xs text-zinc-400 uppercase font-semibold">Active MySQL Database</span>
            <div class="font-mono text-xl font-bold text-white mt-1"><?php echo e(DB_NAME); ?></div>
            <span class="text-[10px] text-zinc-500">Host: <?php echo e(DB_HOST); ?>:<?php echo e(DB_PORT); ?></span>
        </div>

        <div class="bg-gaming-850 border border-gaming-border rounded-xl p-4">
            <span class="text-xs text-zinc-400 uppercase font-semibold">Total Schema Tables</span>
            <div class="font-gaming text-2xl font-bold text-emerald-400 mt-1"><?php echo count($tables); ?> Tables</div>
            <span class="text-[10px] text-zinc-500">InnoDB UTF8MB4</span>
        </div>

        <div class="bg-gaming-850 border border-gaming-border rounded-xl p-4">
            <span class="text-xs text-zinc-400 uppercase font-semibold">Total Stored Records</span>
            <div class="font-gaming text-2xl font-bold text-white mt-1"><?php echo number_format($total_rows); ?> Rows</div>
            <span class="text-[10px] text-zinc-500">Orders, users, products, logs</span>
        </div>
    </div>

    <!-- Table Details -->
    <div class="bg-gaming-850 border border-gaming-border rounded-2xl overflow-hidden shadow-2xl">
        <div class="p-5 border-b border-gaming-border flex items-center justify-between">
            <h2 class="font-gaming text-lg font-bold text-white">Database Tables & Row Counts</h2>
            <span class="text-xs text-zinc-400 font-mono">Currency: ₹ INR</span>
        </div>

        <div class="p-6 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
            <?php foreach ($table_stats as $ts): ?>
                <div class="p-3 rounded-xl bg-gaming-900 border border-gaming-border flex items-center justify-between">
                    <span class="font-mono text-xs text-zinc-300 font-semibold truncate"><?php echo e($ts['name']); ?></span>
                    <span class="font-mono text-xs text-red-400 font-bold bg-gaming-950 px-2 py-0.5 rounded border border-gaming-border shrink-0">
                        <?php echo number_format($ts['rows']); ?>
                    </span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
