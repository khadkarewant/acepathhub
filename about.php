<?php
    include("src/db/db_conn.php");
    include("src/db/privileges.php");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us | QuizMania.org</title>
    <?php include("src/inc/links.php"); ?>
    <style>
        .abt{
            padding:20px 10px;
            text-align:center;
            color:whitesmoke;
            text-shadow:0px 0px 2px var(--primary);
            font-size:30px;
        }
        .about-section{
            padding:30px 20px;
            font-size:17px;
            line-height:1.8;
        }
        .about-section h3{
            margin-top:30px;
            font-weight:bold;
            color:var(--primary);
        }
        .about-list li{
            background:#f8f9fa;
            margin-bottom:10px;
            padding:10px 15px;
            border-radius:6px;
            border-left:4px solid var(--primary);
        }
    </style>
</head>
<body>

    <?php include("src/inc/header.php"); ?>

    <div class="container-fluid">
        <div class="row">
            
            <div class="col-md-12">
                <div class="abt">About QuizMania.org</div>
                <hr style="width:50%;margin:0 auto;border:3px solid var(--primary)">
            </div>

            <div class="col-md-10 mx-auto about-section">

                <div>
                    <p class="lead">
                        QuizMania.org is an online learning platform built for students and competitive exam aspirants 
                        who want to practice **MCQs**, attempt **mock tests**, and improve their performance with confidence.
                    </p>
                </div>

                <div>
                    <h3>Our Mission</h3>
                    <p>
                        Our mission is to make exam preparation **fast, simple, and effective**.  
                        Whether you're preparing for Lok Sewa, entrance exams, or skill assessments,  
                        QuizMania offers a clean and distraction-free learning environment.
                    </p>
                </div>

                <div>
                    <h3>What We Offer</h3>
                    <ul class="about-list list-unstyled">
                        <li>✔ Thousands of high-quality MCQs from different subjects</li>
                        <li>✔ Real-exam style mock tests with instant scoring</li>
                        <li>✔ Topic-wise practice sets & mini quizzes</li>
                        <li>✔ Detailed performance analytics</li>
                        <li>✔ Courses designed based on exam patterns & syllabus</li>
                    </ul>
                </div>

                <div>
                    <h3>Why Choose QuizMania?</h3>
                    <p>
                        Unlike other platforms, we focus **only** on MCQs and mock tests.  
                        No unnecessary features — just powerful tools that help you prepare faster and smarter.
                    </p>
                </div>

                <div>
                    <h3>Our Vision</h3>
                    <p>
                        To become Nepal’s most reliable online MCQ and mock-test platform by delivering 
                        accurate content, instant results, and a smooth learning experience.
                    </p>
                </div>

                <div>
                    <hr>
                    <p class="text-muted">
                        Have suggestions or want new courses?  
                        We are improving QuizMania.org every day — your feedback matters!
                    </p>
                </div>

            </div>

        </div>
    </div>

    <?php include("src/inc/footer.php"); ?>

</body>
</html>
