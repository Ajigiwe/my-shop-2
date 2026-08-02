<?php
/**
 * ASO Admin — Avazonia shell footer
 * Port of Avazonia admin/layout/footer.php wired to ASO api/notifications.php.
 * Provides global confirmAction() (native confirm, preserves ASO form/nav behaviour).
 */
?>
</main>

<!-- Notification Container -->
<div id="notification-toast-container"></div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    let seenNotifications = new Set();
    const pollInterval = 30000;

    function fetchNotifications() {
        fetch('api/notifications.php?action=list')
            .then(res => res.json())
            .then(data => {
                if (data.success && data.notifications.length > 0) {
                    data.notifications.forEach(notif => {
                        if (!seenNotifications.has(notif.id)) {
                            showToast(notif);
                            seenNotifications.add(notif.id);
                        }
                    });
                }
            })
            .catch(() => {});
    }

    function showToast(notif) {
        const container = document.getElementById('notification-toast-container');
        const toast = document.createElement('div');
        toast.className = 'toast-alert';

        const link = notif.link && notif.link.length ? notif.link : 'manage_orders.php';

        toast.innerHTML = `
            <div style="flex: 1;">
                <div style="font-weight: 800; margin-bottom: 4px; text-transform: uppercase;">${notif.type ? notif.type : 'Alert'}</div>
                <div style="opacity: 0.8; font-size: 11px;">${notif.message}</div>
            </div>
            <a href="${link}" class="view-link">View</a>
            <div class="close-toast" onclick="this.parentElement.remove()">×</div>
        `;

        container.appendChild(toast);

        setTimeout(() => {
            if (toast.parentElement) {
                toast.style.opacity = '0';
                toast.style.transform = 'translateX(100%)';
                toast.style.transition = 'all 0.5s';
                setTimeout(() => toast.remove(), 500);
            }
        }, 10000);

        markAsRead(notif.id);
    }

    function markAsRead(id) {
        fetch('api/notifications.php?action=read&id=' + encodeURIComponent(id)).catch(() => {});
    }

    fetchNotifications();
    setInterval(fetchNotifications, pollInterval);
});
</script>

<script>
/* Modal helpers (replaces Bootstrap Modal) */
function openModal(id) {
    const el = document.getElementById(id);
    if (el) { el.classList.add('active'); document.body.style.overflow = 'hidden'; }
}
function closeModal(id) {
    const el = document.getElementById(id);
    if (el) { el.classList.remove('active'); document.body.style.overflow = ''; }
}
document.addEventListener('click', function (e) {
    const overlay = e.target.closest('.modal-overlay');
    if (overlay && e.target === overlay) {
        overlay.classList.remove('active');
        document.body.style.overflow = '';
    }
});
document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal-overlay.active').forEach(function (el) {
            el.classList.remove('active');
        });
        document.body.style.overflow = '';
    }
});

/* Global confirm helper (native) — replaces SweetAlert2-based confirmAction */
function confirmAction(event, message) {
    event.preventDefault();
    const target = event.currentTarget;
    if (!window.confirm(message || 'Are you sure?')) return false;
    if (target.tagName === 'FORM') {
        target.submit();
    } else if (target.tagName === 'A') {
        window.location.href = target.href;
    } else if (target.type === 'submit' || target.tagName === 'BUTTON') {
        const form = target.closest('form');
        if (form) {
            if (target.name) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = target.name;
                input.value = target.value;
                form.appendChild(input);
            }
            form.submit();
        }
    }
    return false;
}
</script>

</body>
</html>
