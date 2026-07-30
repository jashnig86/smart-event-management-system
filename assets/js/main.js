// Event Management System - Main JS

document.addEventListener('DOMContentLoaded', function () {

    // ===== Auto-dismiss alerts =====
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });

    // ===== Image Preview =====
    const imgInput = document.getElementById('event_image');
    if (imgInput) {
        imgInput.addEventListener('change', function () {
            const preview = document.getElementById('imagePreview');
            if (preview && this.files[0]) {
                const reader = new FileReader();
                reader.onload = e => {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(this.files[0]);
            }
        });
    }

    // ===== Attendance - focus input =====
    const scanInput = document.getElementById('reg_number_scan');
    if (scanInput) {
        scanInput.focus();
        // Also allow barcode scanner (Enter key submit)
        scanInput.addEventListener('keypress', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                document.getElementById('scanForm').submit();
            }
        });
    }

    // ===== Mobile sidebar toggle =====
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.querySelector('.sidebar');
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', () => sidebar.classList.toggle('open'));
        document.addEventListener('click', (e) => {
            if (!sidebar.contains(e.target) && e.target !== sidebarToggle) {
                sidebar.classList.remove('open');
            }
        });
    }

    // ===== Confirm delete =====
    document.querySelectorAll('.btn-delete').forEach(btn => {
        btn.addEventListener('click', function (e) {
            if (!confirm('Are you sure you want to delete this? This cannot be undone.')) {
                e.preventDefault();
            }
        });
    });

    // ===== Reject modal - pass event ID =====
    const rejectModal = document.getElementById('rejectModal');
    if (rejectModal) {
        rejectModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const eventId = button.getAttribute('data-event-id');
            document.getElementById('reject_event_id').value = eventId;
        });
    }

    // ===== QR Download =====
    document.querySelectorAll('.btn-download-qr').forEach(btn => {
        btn.addEventListener('click', function () {
            const imgSrc = this.getAttribute('data-qr-src');
            const regNum = this.getAttribute('data-reg-num');
            const link = document.createElement('a');
            link.href = imgSrc;
            link.download = `QR-${regNum}.png`;
            link.click();
        });
    });
});
