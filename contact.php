<?php
    include("src/db/db_conn.php");
    include("src/db/privileges.php");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us | QuizMania.org</title>
    <?php include("src/inc/links.php"); ?>

    <style>
        .abt{
            padding:20px 10px;
            text-align:center;
            color:whitesmoke;
            text-shadow:0px 0px 2px var(--primary);
            font-size:30px;
        }

        .contact-info-text {
            text-align:center;
            font-size:17px;
            padding:0 15px;
            margin-bottom:25px;
            color:#444;
        }

        .contact_card{
            display:inline-block;
            padding:20px;
            margin:12px;
            border:1px solid var(--primary);
            border-radius:8px;
            text-align:center;
            min-width:150px;
            transition:0.2s;
            background:white;
        }

        .contact_card:hover{
            cursor:pointer;
            box-shadow:0px 0px 10px var(--primary);
            transform:translateY(-3px);
        }

        .section-title{
            font-weight:bold;
            margin-top:30px;
            margin-bottom:10px;
            color:var(--primary);
            text-align:center;
        }

        .details{
            text-align:center;
            font-size:16px;
            margin-bottom:5px;
        }
    </style>
</head>
<body>

    <?php include("src/inc/header.php"); ?>

    <div class="container-fluid">
        <div class="row">
            
            <div class="col-md-12">
                <div class="abt">Contact Us</div>
                <p class="text-center text-muted mb-0"><small>We’re here to help you anytime!</small></p>
                <hr style="width:50%;margin:0 auto;border:3px solid var(--primary)">
            </div>

            <div class="col-md-10 mx-auto">

                <!-- short intro -->
                <p class="contact-info-text">
                    Need help with mock tests, accounts, payments, or general inquiries?  
                    Our support team is ready to assist you.  
                    Choose any of the contact options below:
                </p>

                <div class="text-center">

                    <div class="contact_card" onclick="window.location.href='https://wa.me/+9779700186061'">
                        <h5 class="text-success">WhatsApp</h5>
                        <small class="text-muted">Quick replies</small>
                    </div>

                    <div class="contact_card" onclick="window.location.href='https://www.facebook.com/quizmania.loksewa/'">
                        <h5 class="text-primary">Facebook</h5>
                        <small class="text-muted">Message our page</small>
                    </div>

                    <div class="contact_card" onclick="window.location.href='mailto:loksewa@quizmania.org'">
                        <h5 class="text-danger">Email</h5>
                        <small class="text-muted">loksewa@quizmania.org</small>
                    </div>

                    <div class="contact_card" onclick="window.location.href='tel:+9779700186061'">
                        <h5 class="text-secondary">Call Us</h5>
                        <small class="text-muted">+977 9700186061</small>
                    </div>

                </div>

                <!-- office hours -->
                <!--<h4 class="section-title">Office Hours</h4>-->
                <!--<p class="details">Sunday – Friday</p>-->
                <!--<p class="details">9:00 AM – 6:00 PM (Nepal Time)</p>-->

                <!-- support notice -->
                <h4 class="section-title">Support Notice</h4>
                <p class="details">
                    For faster support, please include your registered email  
                    or screenshot if your inquiry is related to payments.
                </p>

                <!-- location info -->
                <h4 class="section-title">Location</h4>
                <p class="details">QuizMania.org – Online Educational Platform</p>
                <p class="details">Based in Nepal</p>

            </div>
        </div>
    </div>

    <?php include("src/inc/footer.php"); ?>

</body>
</html>