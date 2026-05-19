<?php
/**
 * Modern Admin Footer
 */
?>
</main>

<!-- JS Dependencies -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
function confirmAction(event, message) {
    event.preventDefault();
    const target = event.currentTarget;
    
    Swal.fire({
        title: 'Are you sure?',
        text: message,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#1A1A1A',
        cancelButtonColor: '#dc3545',
        confirmButtonText: 'Yes, proceed!',
        customClass: {
            confirmButton: 'btn btn-dark px-4 py-2 me-2',
            cancelButton: 'btn btn-outline-danger px-4 py-2'
        },
        buttonsStyling: false
    }).then((result) => {
        if (result.isConfirmed) {
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
        }
    });
    return false;
}
</script>

<script>
// Dashboard Animations Init
document.addEventListener('DOMContentLoaded', function() {
    const cards = document.querySelectorAll('.stat-card, .admin-card');
    cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        card.style.transition = 'all 0.5s cubic-bezier(0.4, 0, 0.2, 1)';
        card.style.transitionDelay = (index * 0.1) + 's';
        
        setTimeout(() => {
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, 100);
    });
});
</script>

</body>
</html>
