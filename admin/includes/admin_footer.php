    </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const sidebar = document.getElementById('adminSidebar');
    const openBtn = document.getElementById('openSidebarBtn');
    const closeBtn = document.getElementById('closeSidebarBtn');

    if (openBtn && sidebar) {
        openBtn.addEventListener('click', function () {
            sidebar.classList.remove('-translate-x-full');
        });
    }
    if (closeBtn && sidebar) {
        closeBtn.addEventListener('click', function () {
            sidebar.classList.add('-translate-x-full');
        });
    }
});
</script>

</body>
</html>
