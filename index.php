<?php
    include("src/db/db_conn.php");
?>


<?php
// Set dynamic page metadata (can be overridden per page)
$page_title = $page_title ?? "AcePath Hub | Nigerian Exam Preparation";
$page_description = $page_description ?? "Prepare for JAMB, WAEC, NECO and more with AcePath Hub. Unlimited MCQ practice, instant results, mock tests online.";
$page_url = "https://acepathhub.com" . $_SERVER['REQUEST_URI'];
$page_image = "https://acepathhub.com/assets/img/logo.png";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>

    <!-- Dynamic Title & Description -->
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($page_description); ?>">

    <!-- Include your standard CSS/JS links -->
    <?php include("inc/links.php"); ?>

 
</head>
<body>

    <?php include("inc/header.php"); ?>

    <!-- HERO SECTION -->
    <div class="hero">
        <h1 class="text-center">
                ACE PATH HUB
        </h1>
        <h2>CRACK YOUR EXAMS WITH SMARTER PRACTICE !</h2>
        <h4>Enroll today and get a <i class="text-danger"><u>FREE Demo Test</u></i></h4>

        <div class="cta-group">
            <!-- Visible Sign-up CTA -->
            <a href="signup.php" class="btn-primary-custom" role="button">Sign Up</a>

            <!-- Secondary action (example: try demo) -->
            <a href="login.php" class="btn-outline-custom" role="button">Try Demo</a>
        </div>
    </div>

    <!-- FEATURES SECTION -->
    <div class="container features-section">
        <h4>WE PROVIDE:</h4>
        <div class="divider"></div>

        <div class="card_box">
            <h1>1000<strong>+</strong></h1>
            <div>Questions Pool</div>
        </div>

        <div class="card_box">
            <h1>Unlimited</h1>
            <div>Exam Sets</div>
        </div>

        <!--<div class="card_box">-->
        <!--    <h1>3<strong>+</strong></h1>-->
        <!--    <div>Exam Types</div>-->
        <!--</div>-->

        <div class="card_box">
            <h1>Connect</h1>
            <div>Community Chat</div>
        </div>

        <div class="card_box">
            <h1>24/7</h1>
            <div>Student Support</div>
        </div>

        <div class="card_box">
            <h1>Instant</h1>
            <div>Results & Reviews</div>
        </div>
    </div>

    <?php include("inc/footer.php"); ?>

</body>
</html>
