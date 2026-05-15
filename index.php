<?php
    include("src/db/db_conn.php");
    include("src/db/privileges.php");
?>


<?php
// Set dynamic page metadata (can be overridden per page)
$page_title = $page_title ?? "QuizMania | Lok Sewa Preparation Nepal";
$page_description = $page_description ?? "Prepare for Lok Sewa Aayog exams in Nepal with QuizMania. Practice real exam format mock tests, sample question papers, unlimited MCQs, instant results, and free demo tests online.";
$page_url = "https://quizmania.org" . $_SERVER['REQUEST_URI'];
$page_image = "https://quizmania.org/src/img/lsw.png";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <meta name="facebook-domain-verification" content="zit9hbl7vxuwwdp4gyfnym9inh8cvu" />

    <!-- Dynamic Title & Description -->
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($page_description); ?>">

    <!-- Include your standard CSS/JS links -->
    <?php include("src/inc/links.php"); ?>

    <style>
        :root{
            --primary: #0d6efd; /* fallback if your CSS variables not loaded */
        }

        /* HERO SECTION */
        .hero {
            padding: 30px 20px;
            background: #f8f9fa;
            border-bottom: 3px solid var(--primary);
            text-align: center;
        }

        .hero h2 {
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 10px;
        }

        .hero h4 {
            margin-top: 8px;
            color: var(--primary);
            font-weight: 600;
        }

        .hero .cta-group {
            margin-top: 20px;
        }

        .btn-primary-custom {
            padding: 10px 20px;
            background: var(--primary);
            color: #fff;
            border: none;
            border-radius: 6px;
            display: inline-block;
            text-decoration: none;
            cursor: pointer;
        }

        .btn-outline-custom {
            padding: 10px 20px;
            background: transparent;
            color: var(--primary);
            border: 2px solid var(--primary);
            border-radius: 6px;
            display: inline-block;
            text-decoration: none;
            margin-left: 10px;
            cursor: pointer;
        }

        .btn-primary-custom:hover,
        .btn-outline-custom:hover {
            opacity: 0.9;
        }

        /* Feature Cards */
        .features-section {
            text-align: center;
            padding: 40px 0;
        }

        .features-section h4 {
            color: var(--primary);
            font-weight: bold;
        }

        .card_box {
            box-shadow: 0px 0px 10px #b4b4b4;
            padding: 20px;
            margin: 15px 15px;
            text-align: center;
            border-radius: 10px;
            width: 100%;
            max-width: 180px;
            transition: 0.3s;
            border: 2px solid var(--primary);
            display: inline-block;
        }

        .card_box:hover {
            background: var(--primary);
            color: white;
            cursor: pointer;
            transform: translateY(-5px);
        }

        .card_box h1 {
            font-size: 2.2rem;
            margin: 0;
        }

        .divider {
            width: 180px;
            height: 3px;
            background: var(--primary);
            margin: 0 auto 30px auto;
        }

        /* small screens */
        @media (max-width: 576px) {
            .card_box {
                width: 48% !important; /* 2 cards per row */
                max-width: none;
                margin: 8px 1%;
                padding: 15px;
            }
        }
        @media (max-width: 400px) {
            .card_box {
                width: 100% !important; /* 1 card per row */
                margin: 10px 0;
            }
        }
    </style>
</head>
<body>

    <?php include("src/inc/header.php"); ?>

    <!-- HERO SECTION -->
    <div class="hero">
        <h1 class="text-center" style="
                color:var(--primary);
                font-weight:900;
                font-size:60px;
                letter-spacing:1px;
                text-shadow:3px 3px 10px rgba(0,0,0,0.35);
            ">
                लोकसेवा तयारी — प्रथम पत्र
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
            <h1>10000<strong>+</strong></h1>
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

    <?php include("src/inc/footer.php"); ?>

</body>
</html>
