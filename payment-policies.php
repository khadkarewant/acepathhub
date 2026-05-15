<?php
    include("src/db/db_conn.php");
    include("src/db/privileges.php");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Refund Policy | QuizMania.org</title>
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
        .policy-section a{
            color: #0d6efd;
            text-decoration: underline;
        }
    </style>
</head>
<body>

<?php include("src/inc/header.php"); ?>

<div class="container-fluid">
    <div class="row">
        <!-- TOP TITLE -->
        <div class="col-md-12">
            <div class="abt">Refund Policy</div>
            <p class="text-center text-muted mb-0">
                <small>Last Updated: December 16, 2025</small>
            </p>
            <hr style="width:50%;margin:0 auto;border:3px solid var(--primary)">
        </div>

        <!-- MAIN CONTENT -->
        <div class="col-md-10 mx-auto policy-section">

            <p>
                Thank you for choosing <strong>QUIZMANIA.ORG</strong> (“we”, “our”, “us”).  
                We aim to provide high-quality MCQ mock tests and learning resources.  
                This Refund Policy explains when refunds may or may not be issued for our digital products or subscriptions.
            </p>

            <p>
                By purchasing any product or plan on our website, you agree to the terms of this Refund Policy.
            </p>

            <h3>1. Digital Products Are Non-Refundable</h3>
            <p>
                All mock tests, question banks, subscriptions, and digital learning materials sold by QUIZMANIA.ORG are 
                <strong>non-refundable</strong>, as users gain instant access immediately after purchase.
            </p>
            <p>Refunds are NOT provided in cases such as:</p>
            <ul>
                <li>You changed your mind</li>
                <li>You purchased by mistake</li>
                <li>You expected different content</li>
                <li>You no longer need the material</li>
                <li>You did not attempt the tests after purchase</li>
            </ul>

            <h3>2. Exceptions (Refunds May Be Considered)</h3>
            <p><strong>a) Duplicate Payment:</strong> If you are charged twice for the same product, we will refund the duplicate amount.</p>
            <p><strong>b) Technical Issues:</strong> If you cannot access purchased content due to a technical issue caused by QUIZMANIA.ORG, and our support team cannot resolve it within 48 hours, a refund may be considered.</p>
            <p><strong>c) Wrong Deduction:</strong> If payment is deducted but service is not activated, we may issue a refund after verification.</p>

            <h3>3. No Refund For:</h3>
            <ul>
                <li>Low scores or dissatisfaction with performance</li>
                <li>Misunderstanding exam difficulty level</li>
                <li>Internet or device-related issues</li>
                <li>Temporary server downtime</li>
                <li>Content not matching personal expectations (mock tests vary by exam pattern)</li>
            </ul>

            <h3>4. How to Request a Refund</h3>
            <p>If you believe you are eligible, contact us within <strong>3 days of purchase</strong>:</p>
            <ul>
                <li>Full name</li>
                <li>Registered email</li>
                <li>Screenshot/proof of payment</li>
                <li>Reason for refund request</li>
            </ul>
            <p>Refunds, if approved, will be processed within <strong>7–10 business days</strong> to your original payment method.</p>

            <h3>5. Subscription Cancellations</h3>
            <p>You may cancel your subscription anytime, but canceled subscriptions are <strong>not refundable</strong>. Access continues until the end of the paid period.</p>

            <h3>6. Modification of Refund Policy</h3>
            <p>We may update this Refund Policy at any time. Changes will be posted here with a new “Last Updated” date.</p>

            <h3>7. Contact Us</h3>
            <p>If you have questions about this Refund Policy:</p>
            <p>📧 Email: <a href="mailto:loksewa@quizmania.org">loksewa@quizmania.org</a></p>
            <p>🌐 Website: <a href="https://www.quizmania.org" target="_blank">www.quizmania.org</a></p>
            <p>📱 WhatsApp: <a href="https://wa.me/9779700186061" target="_blank">+977 9700186061</a></p>

        </div>
    </div>
</div>

<?php include("src/inc/footer.php"); ?>

</body>
</html>
