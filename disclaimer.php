<?php
    include("src/db/db_conn.php");
    // include("src/db/session.php"); // optional
    include("src/db/privileges.php");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Disclaimer - QuizMania.org</title>

    <?php include("src/inc/links.php"); ?>

    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 0;
            background: #f9f9f9;
        }
        .container {
            max-width: 900px;
            margin: 40px auto;
            padding: 20px;
            background: #fff;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            border-radius: 8px;
        }
        h1, h2 {
            text-align: center;
            color: #333;
        }
        hr {
            margin: 20px 0;
            border: none;
            border-top: 2px solid #eee;
        }
        .section {
            margin-bottom: 15px;
        }
        .section-title {
            background: #dc3545;
            color: #fff;
            padding: 12px;
            cursor: pointer;
            border-radius: 5px;
            font-weight: bold;
        }
        .section-content {
            display: none;
            padding: 12px;
            background: #f1f1f1;
            border-left: 3px solid #dc3545;
            margin-top: 5px;
            border-radius: 5px;
        }
        .section-content ul {
            padding-left: 20px;
        }
        .section-title.active {
            background: #b02a37;
        }
        .last-updated {
            text-align: center;
            font-size: 14px;
            color: #666;
        }
    </style>
</head>
<body>

<?php include("src/inc/header.php"); ?>

<div class="container">
    <h1>Disclaimer</h1>
    <h2>QUIZMANIA.ORG</h2>
    <p class="last-updated"><strong>Last Updated:</strong> December 16, 2025</p>
    <hr>

    <p>
        Welcome to QUIZMANIA.ORG (“we”, “our”, “us”).  
        This Disclaimer outlines important limitations regarding the use of our website,
        mock tests, MCQs, and learning materials. By accessing or using our Services,
        you agree to the terms below.
    </p>

    <div class="section">
        <div class="section-title">1. Educational Purpose Only</div>
        <div class="section-content">
            All mock tests, study materials, MCQs, explanations, and content provided on
            QUIZMANIA.ORG are meant solely for educational and practice purposes.
            <ul>
                <li>Our questions will appear in any actual exam</li>
                <li>Using our platform guarantees exam success</li>
            </ul>
        </div>
    </div>

    <div class="section">
        <div class="section-title">2. No Professional Advice</div>
        <div class="section-content">
            The information on our website does not constitute:
            <ul>
                <li>Professional coaching</li>
                <li>Legal advice</li>
                <li>Career counseling</li>
                <li>Guaranteed exam strategy</li>
            </ul>
            Users should independently verify exam requirements from official sources.
        </div>
    </div>

    <div class="section">
        <div class="section-title">3. Accuracy of Information</div>
        <div class="section-content">
            While we strive to provide accurate and updated content:
            <ul>
                <li>We do not guarantee 100% accuracy or completeness</li>
                <li>Question patterns and syllabus may change</li>
                <li>Errors or outdated information may occur</li>
            </ul>
            We are not responsible for consequences arising from reliance on our content.
        </div>
    </div>

    <div class="section">
        <div class="section-title">4. External Links Disclaimer</div>
        <div class="section-content">
            Our website may contain links to third-party sites.
            We are not responsible for their content, accuracy, or policies.
        </div>
    </div>

    <div class="section">
        <div class="section-title">5. User Responsibility</div>
        <div class="section-content">
            By using our website, you acknowledge that:
            <ul>
                <li>Your exam results depend on your own effort</li>
                <li>You will use the website responsibly</li>
                <li>You will verify important info from official authorities</li>
            </ul>
        </div>
    </div>

    <div class="section">
        <div class="section-title">6. Technical Issues</div>
        <div class="section-content">
            We are not liable for internet outages, device issues, or server downtime.
        </div>
    </div>

    <div class="section">
        <div class="section-title">7. Limitation of Liability</div>
        <div class="section-content">
            To the maximum extent permitted by law, QUIZMANIA.ORG is not liable for any
            direct or indirect damages arising from use of our services.
        </div>
    </div>

    <div class="section">
        <div class="section-title">8. Test Scores & Performance</div>
        <div class="section-content">
            All scores and analytics are estimates only and do not guarantee exam success.
        </div>
    </div>

    <div class="section">
        <div class="section-title">9. Updates to This Disclaimer</div>
        <div class="section-content">
            We may update this Disclaimer at any time without prior notice.
        </div>
    </div>

    <div class="section">
        <div class="section-title">10. Contact Us</div>
        <div class="section-content">
            📧 Email:
            <a href="mailto:loksewa@quizmania.org">loksewa@quizmania.org</a><br>

            🌐 Website:
            <a href="https://www.quizmania.org" target="_blank">www.quizmania.org</a><br>

            📱 WhatsApp:
            <a href="https://wa.me/9779700186061" target="_blank">
                +977 9700186061
            </a>
        </div>
    </div>

</div>

<?php include("src/inc/footer.php"); ?>

<script>
    const sections = document.querySelectorAll('.section-title');
    sections.forEach(title => {
        title.addEventListener('click', () => {
            title.classList.toggle('active');
            const content = title.nextElementSibling;
            content.style.display =
                content.style.display === "block" ? "none" : "block";
        });
    });
</script>

</body>
</html>
