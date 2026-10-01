<?php
$flash_messages = get_flash();
if (!empty($flash_messages)): ?>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4 space-y-2">
        <?php foreach ($flash_messages as $flash): 
            $type = $flash['type'];
            $msg = $flash['message'];
            if ($type === 'success') {
                $border = 'border-emerald-500/40 bg-emerald-950/40 text-emerald-300';
                $icon = '<svg class="w-5 h-5 text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>';
            } elseif ($type === 'error' || $type === 'danger') {
                $border = 'border-red-500/50 bg-red-950/50 text-red-200';
                $icon = '<svg class="w-5 h-5 text-red-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>';
            } elseif ($type === 'warning') {
                $border = 'border-amber-500/40 bg-amber-950/40 text-amber-200';
                $icon = '<svg class="w-5 h-5 text-amber-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>';
            } else {
                $border = 'border-red-900/40 bg-zinc-900/90 text-zinc-300';
                $icon = '<svg class="w-5 h-5 text-red-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>';
            }
        ?>
            <div class="flex items-center gap-3 p-4 rounded-xl border backdrop-blur-md shadow-lg <?php echo $border; ?> transition-all animate-fade-in">
                <?php echo $icon; ?>
                <div class="text-sm font-medium leading-relaxed"><?php echo e($msg); ?></div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
