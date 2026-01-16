<?php
// admin/footer.php
?>
        </div> <!-- Penutup main-content -->
    </div> <!-- Penutup wrapper -->

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Font Awesome -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>

    <!-- Custom Scripts -->
    <script>
    // Auto-hide alerts untuk semua halaman
    document.addEventListener('DOMContentLoaded', function() {
        // Tunggu 5 detik lalu hide semua alert
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                // Skip jika alert sudah di-close manual
                if (alert.classList.contains('manually-closed')) {
                    return;
                }

                // Tambahkan animasi fade out
                alert.style.transition = 'all 0.5s ease';
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-10px)';
                alert.style.maxHeight = '0';
                alert.style.padding = '0';
                alert.style.margin = '0';
                alert.style.border = '0';
                alert.style.overflow = 'hidden';

                // Hapus dari DOM setelah animasi selesai
                setTimeout(function() {
                    if (alert.parentNode) {
                        alert.remove();
                    }
                }, 500);
            });
        }, 5000); // 5 detik

        // Jika ada tombol close, beri fungsi manual
        const closeButtons = document.querySelectorAll('.alert .btn-close');
        closeButtons.forEach(function(button) {
            button.addEventListener('click', function() {
                const alert = this.closest('.alert');
                if (alert) {
                    // Tandai sebagai manually closed
                    alert.classList.add('manually-closed');

                    alert.style.transition = 'all 0.3s ease';
                    alert.style.opacity = '0';
                    alert.style.maxHeight = '0';
                    alert.style.padding = '0';
                    alert.style.margin = '0';

                    setTimeout(function() {
                        if (alert.parentNode) {
                            alert.remove();
                        }
                    }, 300);
                }
            });
        });
    });
    </script>
</body>
</html>
