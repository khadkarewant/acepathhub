<?php
if (!isset($_SESSION['id'])) {
?>

    <footer class="footer mt-5" style="background:#f4f6f9; border-top:3px solid var(--primary); padding:40px 0;">

        <div class="container">

            <div class="row text-center text-md-start">

                <!-- Logo + description -->
                
                <div class="col-12 col-md-3 mb-4 mt-0 pt-0">
                    <img src="src/img/full_logo_wb.png" style="width:150px;" alt="">
                    <p class="mt-2" style="color:#444; font-size:0.95rem;">
                        Mock MCQs for different competitive exams.
                    </p>
                    <!--<strong style="color:var(--primary);">Mock MCQ</strong>-->
                </div>

                <!-- Policies -->
                <div class="col-6 col-md-3 mb-4 footer-links">
                    <h6 class="footer-title">Policies</h6>
                    <a href="privacy-policies.php">Privacy Policies</a>
                    <a href="t&c.php">Terms & Conditions</a>
                    <a href="payment-policies.php">Payment Policies</a>
                    <a href="disclaimer.php">Disclaimer</a>
                </div>

                <!-- Info -->
                <div class="col-6 col-md-3 mb-4 footer-links">
                    <h6 class="footer-title">Useful Links</h6>
                    <a href="contact.php">Contact Us</a>
                    <a href="about.php">About Us</a>
                    <a href="faq.php">FAQs</a>
                </div>

                <!-- Social -->
                <div class="col-12 col-md-3 mb-4 footer-links text-center">
                    <h6>Our Social Media:</h6>
                    <div style="display:flex; justify-content:center; gap:10px; align-items:center;">
                        <a href="https://facebook.com/quizmania.loksewa" target="_blank" rel="noopener noreferrer">
                            <img src="src/img/fb.png" alt="QuizMania Facebook" style="width:40px; cursor:pointer;">
                        </a>
                
                        <a href="https://wa.me/9779700186061" target="_blank" rel="noopener noreferrer">
                            <img src="src/img/wa.png" alt="WhatsApp" style="width:40px; cursor:pointer;">
                        </a>
                    </div>
                </div>

            </div>

            <!-- Copyright -->
            <div class="text-center mt-3" style="color:var(--primary); font-weight:500; font-size:18px;">
                &copy; Quizmania - 2025
            </div>
        </div>

    </footer>

<!-- Floating Messenger Button -->
<!--<div id="messenger-btn" -->
<!--     style="-->
<!--        position: fixed;-->
<!--        bottom: 20px;-->
<!--        right: 20px;-->
<!--        width: 60px;-->
<!--        height: 60px;-->
<!--        border-radius: 50%;-->
<!--        display: flex;-->
<!--        align-items: center;-->
<!--        justify-content: center;-->
<!--        background: transparent;-->
<!--        box-shadow: 0 4px 10px rgba(0,0,0,0.3);-->
<!--        cursor: pointer;-->
<!--        z-index: 2147483647;-->
<!--        transition: transform 0.2s;-->
<!--     "-->
<!--     title="Message Us"-->
<!--     onclick="window.open('https://m.me/quizmania.loksewa', '_blank');">-->
<!--    <img src="src/img/messenger.png" alt="Messenger" -->
<!--         style="width: 32px; height: 32px; display: block; border: none; outline: none;">-->
<!--</div>-->

<!-- Optional: Hover effect -->
<style>
#messenger-btn:hover {
    transform: scale(1.1);
}
</style>



<?php
}
?>

<style>
    .footer-links a {
        display: block;
        color: #444;
        text-decoration: none;
        margin: 4px 0;
        font-size: 0.95rem;
    }

    .footer-links a:hover {
        color: var(--primary);
        text-decoration: underline;
    }

    .footer-title {
        font-weight: 600;
        margin-bottom: 8px;
        color: var(--primary);
    }
</style>