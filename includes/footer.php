</main> <!-- penutup dashboard-container -->

<footer class="main-footer">
    <div class="footer-content">
        <div class="footer-section">
            <h3><i class="fas fa-school"></i> MTs. Al-Ihsan Batujajar</h3>
            <p>Madrasah Tsanawiyah unggul dalam prestasi, berlandaskan iman dan taqwa, serta berkomitmen mencetak generasi Qur'ani.</p>
            <p><i class="fas fa-map-marker-alt"></i> Jl. Batujajar Blok Pasantren 05/07, Batujajar, Bandung Barat</p>
        </div>
        <div class="footer-section">
            <h3><i class="fas fa-bolt"></i> Menu Cepat</h3>
            <ul class="footer-links">
                <li><a href="<?= $base_url ?>index"><i class="fas fa-home"></i> Beranda</a></li>
                <?php if ($role == 'admin'): ?>
                    <li><a href="<?= $base_url ?>admin/dashboard"><i class="fas fa-chart-line"></i> Dashboard Guru</a></li>
                    <li><a href="<?= $base_url ?>admin/bab"><i class="fas fa-book"></i> Kelola Bab</a></li>
                <?php elseif ($role == 'siswa'): ?>
                    <li><a href="<?= $base_url ?>siswa/dashboard"><i class="fas fa-home"></i> Dashboard Siswa</a></li>
                    <li><a href="<?= $base_url ?>siswa/materi"><i class="fas fa-book-open"></i> Materi Belajar</a></li>
                <?php endif; ?>
            </ul>
        </div>
        <div class="footer-section">
            <h3><i class="fas fa-address-card"></i> Pengembang</h3>
            <ul class="footer-links">
                <li><i class="fas fa-user-tie"></i> Ilham Rizqiawan, S.Pd.</li>
                <li><i class="fab fa-facebook"></i> <a href="https://facebook.com/ilhamzp" target="_blank">facebook.com/ilhamzp</a></li>
                <li><i class="fab fa-instagram"></i> <a href="https://instagram.com/ilhamr_21" target="_blank">instagram.com/ilhamr_21</a></li>
            </ul>
            <div class="social-icons">
                <a href="https://facebook.com/ilhamzp" target="_blank" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                <a href="https://instagram.com/ilham_r21" target="_blank" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                <a href="#" aria-label="YouTube"><i class="fab fa-youtube"></i></a>
            </div>
        </div>
    </div>
    <div class="footer-bottom">
        &copy; <?= date('Y') ?> MTs. Al-Ihsan Batujajar | Sistem Pembelajaran Digital <br>
        Dikembangkan oleh <strong>Ilham Rizqiawan, S.Pd.</strong>
    </div>
</footer>

</body>
</html>