</div><!-- /#main-content -->

<?php if (!isset($_SESSION['id'])): ?>

<footer class="footer">
    <div class="container">
        <div class="row text-center text-md-start">

            <!-- Logo + description -->
            <div class="col-12 col-md-3 mb-4">
                <span style="color:var(--accent); font-weight:800; font-size:1.3rem;">AcePath Hub</span>
                <p class="footer-desc">Your complete exam preparation platform for JAMB, WAEC, NECO and more.</p>
            </div>

            <!-- Policies -->
            <div class="col-6 col-md-3 mb-4 footer-links">
                <h6 class="footer-title">Policies</h6>
                <a href="privacy-policies.php">Privacy Policies</a>
                <a href="t_c.php">Terms &amp; Conditions</a>
                <a href="payment-policies.php">Payment Policies</a>
                <a href="disclaimer.php">Disclaimer</a>
            </div>

            <!-- Useful Links -->
            <div class="col-6 col-md-3 mb-4 footer-links">
                <h6 class="footer-title">Useful Links</h6>
                <a href="contact.php">Contact Us</a>
                <a href="about.php">About Us</a>
                <a href="faq.php">FAQs</a>
            </div>

            <!-- Social -->
            <div class="col-12 col-md-3 mb-4 footer-links text-center">
                <h6 class="footer-title">Our Social Media</h6>
                <div class="footer-social">
                    <a href="#" target="_blank" rel="noopener noreferrer" title="Facebook">
                        <i class="fa-brands fa-facebook"></i>
                    </a>
                    <a href="https://wa.me/WA_NUMBER" target="_blank" rel="noopener noreferrer" title="WhatsApp">
                        <i class="fa-brands fa-whatsapp"></i>
                    </a>
                </div>
            </div>

        </div>

        <!-- Copyright -->
        <div class="footer-copyright">
            &copy; AcePath Hub - 2025
        </div>
    </div>
</footer>

<?php endif; ?>
