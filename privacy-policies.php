<?php
    include("src/db/db_conn.php");
    include("src/db/privileges.php");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy | QuizMania.org</title>
    <?php include("src/inc/links.php"); ?>

    <style>
        .abt{
            padding:20px 10px;
            text-align:center;
            color:whitesmoke;
            text-shadow:0px 0px 2px var(--primary);
            font-size:30px;
        }
        .policy-section{
            padding:30px 20px;
            font-size:17px;
            line-height:1.8;
        }
        .policy-section h3{
            margin-top:30px;
            font-weight:bold;
            color:var(--primary);
        }
        .policy-section ul{
            margin-left:20px;
        }
        .policy-section ul li{
            margin-bottom:6px;
        }
    </style>
</head>
<body>

<?php include("src/inc/header.php"); ?>

<div class="container-fluid">
    <div class="row">
        <!-- TOP TITLE SECTION -->
        <div class="col-md-12">
            <div class="abt">Privacy Policy</div>
            <p class="text-center text-muted mb-0">
                <small>Last Updated: December 16, 2025</small>
            </p>
            <hr style="width:50%;margin:0 auto;border:3px solid var(--primary)">
        </div>

        <div class="col-md-10 mx-auto policy-section">

            <p>
                Welcome to <strong>QUIZMANIA.ORG</strong> (“we”, “our”, “us”).  
                Your privacy is important to us. This Privacy Policy explains how we collect, use, store, 
                and protect your personal information when you use our website, mock tests, MCQs, and related 
                services (“Services”).
            </p>

            <p>
                By using our website, you agree to the practices described in this Privacy Policy.
            </p>

            <h3>1. Information We Collect</h3>
            <p><strong>a) Personal Information</strong></p>
            <ul>
                <li>Name</li>
                <li>Email address</li>
                <li>Phone number</li>
                <li>Login credentials</li>
                <li>Profile details</li>
            </ul>

            <p><strong>b) Usage Data</strong></p>
            <ul>
                <li>IP address</li>
                <li>Device type (mobile/desktop)</li>
                <li>Browser type</li>
                <li>Pages visited</li>
                <li>Time spent on pages</li>
                <li>Test scores and practice attempts</li>
            </ul>

            <p><strong>c) Cookies & Tracking</strong></p>
            <ul>
                <li>Cookies</li>
                <li>Analytics tools</li>
                <li>Session tracking</li>
            </ul>
            <p>to improve user experience, store preferences, and analyze performance.</p>

            <h3>2. How We Use Your Information</h3>
            <ul>
                <li>Provide access to mock tests and MCQs</li>
                <li>Personalize your learning experience</li>
                <li>Improve the quality and accuracy of our content</li>
                <li>Maintain website performance and security</li>
                <li>Communicate important updates and notifications</li>
                <li>Process payments</li>
                <li>Prevent fraudulent or unauthorized activities</li>
            </ul>

            <h3>3. Sharing Your Information</h3>
            <p>We do not sell, trade, or rent your personal information.</p>
            <ul>
                <li>Trusted service providers (payment gateways, analytics tools, hosting providers)</li>
                <li>Legal authorities if required to comply with law or protect rights</li>
                <li>Business partners when necessary for website operations</li>
            </ul>
            <p>All third parties are obligated to keep your information secure.</p>

            <h3>4. Payment Information (If applicable)</h3>
            <ul>
                <li>Payments are processed through secure third-party gateways.</li>
            </ul>

            <h3>5. Data Security</h3>
            <ul>
                <li>Encryption</li>
                <li>Secure servers</li>
                <li>Access control protocols</li>
            </ul>
            <p>However, no internet transmission is 100% secure. Use the website at your own discretion.</p>

            <h3>6. Your Rights</h3>
            <ul>
                <li>Access the data we hold about you</li>
                <li>Request corrections or updates</li>
                <li>Delete your account or personal information</li>
                <li>Opt out of emails and marketing</li>
                <li>Disable cookies through browser settings</li>
            </ul>
            <p>To request any of these, contact us at: <a href="mailto:loksewa@quizmania.org">loksewa@quizmania.org</a></p>

            <h3>7. Cookies Policy</h3>
            <ul>
                <li>Remember your preferences</li>
                <li>Keep you logged in</li>
                <li>Analyze traffic and usage</li>
            </ul>
            <p>You can block cookies through browser settings, but some features may stop working.</p>

            <h3>8. Third-Party Links</h3>
            <p>
                Our website may include links to external websites.  
                We are not responsible for their privacy practices or content.  
                Please review their privacy policies independently.
            </p>

            <h3>9. Data Retention</h3>
            <ul>
                <li>Your account remains active</li>
                <li>Required for legitimate business purposes</li>
                <li>Required by law</li>
            </ul>
            <p>You may request deletion at any time.</p>

            <h3>10. Changes to This Privacy Policy</h3>
            <p>
                We may update this Privacy Policy periodically.  
                Any changes will be posted with a new “Last Updated” date.  
                Continued use of our Services means you accept these changes.
            </p>

            <h3>11. Contact Us</h3>
            <p>For questions or concerns about this Privacy Policy:</p>
            <p>📧 Email: <a href="mailto:loksewa@quizmania.org">loksewa@quizmania.org</a></p>
            <p>🌐 Website: <a href="https://www.quizmania.org" target="_blank">www.quizmania.org</a></p>
            <p>📱 WhatsApp: <a href="https://wa.me/9779700186061" target="_blank">+977 9700186061</a></p>

        </div>
    </div>
</div>

<?php include("src/inc/footer.php"); ?>

</body>
</html>
