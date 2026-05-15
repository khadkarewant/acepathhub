<?php
    include("src/db/db_conn.php");
    include("src/db/privileges.php");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms & Conditions | QuizMania.org</title>
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

            <!-- TOP TITLE SECTION (Same Format as Refund & Privacy) -->
            <div class="col-md-12">
                <div class="abt">Terms & Conditions</div>
                <p class="text-center text-muted mb-0">
                    <small>Last Updated: December 16, 2025</small>
                </p>
                <hr style="width:50%;margin:0 auto;border:3px solid var(--primary)">
            </div>

            <!-- MAIN CONTENT -->
            <div class="col-md-10 mx-auto policy-section">

                <p>
                    Welcome to <strong>QUIZMANIA.ORG</strong> (“we”, “our”, “us”).  
                    These Terms & Conditions (“Terms”) govern your access to and use of our website, services, mock tests, MCQs, study materials, and any related features (collectively, the “Services”).
                </p>

                <p>
                    By accessing or using our Services, you agree to be bound by these Terms.  
                    If you do not agree, please do not use the Services.
                </p>

                <h3>1. Use of the Website</h3>
                <p>You agree not to:</p>
                <ul>
                    <li>Use the Services for unlawful or unauthorized purposes.</li>
                    <li>Attempt to gain unauthorized access to our systems.</li>
                    <li>Copy, distribute, or misuse any content without permission.</li>
                    <li>Interfere with the website’s security or performance.</li>
                </ul>
                <p>We reserve the right to suspend or terminate your access for any violation of these Terms.</p>

                <h3>2. Account Registration</h3>
                <p>You agree to:</p>
                <ul>
                    <li>Provide accurate information.</li>
                    <li>Keep login credentials confidential.</li>
                    <li>Be responsible for all activities under your account.</li>
                </ul>
                <p>We are not liable for any unauthorized account access caused by your actions.</p>

                <h3>3. Mock Tests & Study Material</h3>
                <p>
                    All MCQs, explanations, study materials, and mock tests on the website are for educational and practice purposes only.  
                    We do not guarantee:
                </p>
                <ul>
                    <li>Accuracy or completeness of questions.</li>
                    <li>Success in any real examination.</li>
                </ul>

                <h3>4. Payments & Refunds</h3>
                <ul>
                    <li>Prices are subject to change without notice.</li>
                    <li><strong>Refund Policy:</strong> All sales are final, and no refunds will be issued once access is granted.</li>
                </ul>

                <h3>5. Intellectual Property</h3>
                <p>
                    All content, including questions, design, graphics, logos, tests, and software, is the property of QUIZMANIA.ORG unless otherwise stated.
                </p>
                <p>You are not allowed to:</p>
                <ul>
                    <li>Reproduce</li>
                    <li>Sell</li>
                    <li>Distribute</li>
                    <li>Modify</li>
                </ul>
                <p>any materials without written permission.</p>

                <h3>6. Third-Party Links</h3>
                <p>Our Services may include links to external websites.  
                We are not responsible for:</p>
                <ul>
                    <li>Content</li>
                    <li>Accuracy</li>
                    <li>Policies</li>
                    <li>Practices</li>
                </ul>
                <p>of any third-party site.</p>

                <h3>7. Disclaimer of Warranties</h3>
                <p>Our Services are provided on an “as is” and “as available” basis.  
                We do not guarantee:</p>
                <ul>
                    <li>Error-free performance</li>
                    <li>Uninterrupted access</li>
                    <li>Accuracy of the content</li>
                    <li>Compatibility with your device</li>
                </ul>
                <p>Use the website at your own risk.</p>

                <h3>8. Limitation of Liability</h3>
                <p>To the maximum extent permitted by law, we are not liable for:</p>
                <ul>
                    <li>Loss of data</li>
                    <li>Exam results</li>
                    <li>Financial loss</li>
                    <li>Indirect or incidental damages</li>
                    <li>Any issues arising from using our Services</li>
                </ul>
                <p>even if we have been advised of the possibility.</p>
                
                <h3>9. Privacy Policy</h3>
                <p>
                    Your use of the website is also governed by our Privacy Policy.<br>
                    Please review it here:
                    <a href="https://quizmania.org/privacy-policies.php" target="_blank">
                        <strong>QUIZMANIA.ORG/PRIVACY</strong>
                    </a>
                </p>

                <h3>10. Changes to Terms</h3>
                <p>
                    We may update these Terms at any time.  
                    Changes become effective upon posting.  
                    Continued use of the Services means you accept the updated Terms.
                </p>

                <h3>11. Contact Us</h3>
                <p>If you have any questions or concerns:</p>

                <p>
                    <strong>📧 Email:</strong>
                    <a href="mailto:loksewa@quizmania.org">
                        loksewa@quizmania.org
                    </a>
                </p>
                <p>
                    <strong>🌐 Website:</strong>
                    <a href="https://www.quizmania.org" target="_blank">
                        www.quizmania.org
                    </a>
                </p>
                <p>
                    <strong>📱 WhatsApp:</strong>
                    <a href="https://wa.me/9779700186061" target="_blank">
                        +977 9700186061
                    </a>
                </p>

            </div>
        </div>
    </div>

    <?php include("src/inc/footer.php"); ?>

</body>
</html>