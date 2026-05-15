<?php
    include("src/db/db_conn.php");
    // include("src/db/session.php");
    include("src/db/privileges.php");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FAQ - QuizMania.org</title>

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
        h1 {
            margin-bottom: 10px;
        }
        hr {
            margin: 20px 0;
            border: none;
            border-top: 2px solid #eee;
        }
        .faq-item {
            margin-bottom: 15px;
        }
        .faq-question {
            background: #007bff;
            color: #fff;
            cursor: pointer;
            padding: 12px;
            border-radius: 5px;
            font-weight: bold;
            user-select: none;
        }
        .faq-answer {
            display: none;
            padding: 12px;
            border-left: 3px solid #007bff;
            background: #f1f1f1;
            margin-top: 5px;
            border-radius: 5px;
            cursor: default;
        }
        .faq-answer ul {
            padding-left: 20px;
        }
        .faq-question.active {
            background: #0056b3;
        }
        .faq-answer a {
            color: #0d6efd;
            text-decoration: underline;
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
    <h1>📘 Frequently Asked Questions (FAQ)</h1>
    <h2>QUIZMANIA.ORG</h2>
    <hr>

    <div class="faq-item">
        <div class="faq-question">1. What is this website about?</div>
        <div class="faq-answer">
            This website provides Multiple Choice Question (MCQ)–based mock tests for various subjects and exams. It helps students study, practice, evaluate their knowledge, and improve exam performance.
        </div>
    </div>

    <div class="faq-item">
        <div class="faq-question">2. Do I need to create an account to take mock tests?</div>
        <div class="faq-answer">Yes, you are required to create an account.</div>
    </div>

    <div class="faq-item">
        <div class="faq-question">3. Are the mock tests free?</div>
        <div class="faq-answer">
            We offer a combination of demo and premium tests. Premium tests include full-length exams, analytics, and performance reports.
        </div>
    </div>

    <div class="faq-item">
        <div class="faq-question">4. How can I start a test?</div>
        <div class="faq-answer">
            <ol>
                <li>Choose a category or exam type</li>
                <li>Click Attempt Exam</li>
                <li>Answer all questions within the given time</li>
            </ol>
        </div>
    </div>

    <div class="faq-item">
        <div class="faq-question">5. Is there a time limit for the tests?</div>
        <div class="faq-answer">
            Most tests have a timer, similar to real exam conditions. Some practice sets may be untimed for flexible learning.
        </div>
    </div>

    <div class="faq-item">
        <div class="faq-question">6. Can I see the correct answers after completing the test?</div>
        <div class="faq-answer">
            Yes. After submitting the test, you will see:
            <ul>
                <li>Correct answers</li>
                <li>Your answers</li>
                <li>Unattempted questions</li>
                <li>Score and performance breakdown</li>
            </ul>
        </div>
    </div>

    <div class="faq-item">
        <div class="faq-question">7. Can I retake a test?</div>
        <div class="faq-answer">No, you get new question sets every time you attempt.</div>
    </div>

    <div class="faq-item">
        <div class="faq-question">8. Do you offer performance analytics?</div>
        <div class="faq-answer">
            Yes, users can view:
            <ul>
                <li>Overall score</li>
                <li>Subject/topic-wise performance (for courses)</li>
                <li>Negative markings</li>
            </ul>
        </div>
    </div>

    <div class="faq-item">
        <div class="faq-question">9. Can I access tests on mobile devices?</div>
        <div class="faq-answer">Yes. Our website is fully mobile-friendly and works on smartphones, tablets, and desktop computers.</div>
    </div>

    <div class="faq-item">
        <div class="faq-question">10. Which exams do you provide mock tests for?</div>
        <div class="faq-answer">
            We provide MCQ mock tests for multiple exams such as:
            <ul>
                <li>General knowledge for non-technical exams taken by Lok Sewa.</li>
            </ul>
        </div>
    </div>

    <div class="faq-item">
        <div class="faq-question">11. How often are new questions added?</div>
        <div class="faq-answer">
            Our team regularly updates question banks and adds new mock tests to ensure the content stays relevant.
        </div>
    </div>

    <div class="faq-item">
        <div class="faq-question">12. What should I do if I find an error in a question or answer?</div>
        <div class="faq-answer">
            You can report issues using the Report Question button or by contacting us through the Support page.
        </div>
    </div>

    <div class="faq-item">
        <div class="faq-question">13. How do I contact customer support?</div>
        <div class="faq-answer">
            You can reach us through:<br>
            📧 Email: <a href="mailto:loksewa@quizmania.org">loksewa@quizmania.org</a><br>
            🌐 Website: <a href="https://www.quizmania.org" target="_blank">www.quizmania.org</a><br>
            📱 WhatsApp: <a href="https://wa.me/9779700186061" target="_blank">+977 9700186061</a>
        </div>
    </div>

</div>

<?php include("src/inc/footer.php"); ?>

<script>
    const questions = document.querySelectorAll('.faq-question');

    questions.forEach(q => {
        q.addEventListener('click', () => {
            q.classList.toggle('active');
            const answer = q.nextElementSibling;
            answer.style.display = answer.style.display === "block" ? "none" : "block";
        });
    });

    // ✅ Ensure links inside answers are clickable
    const answers = document.querySelectorAll('.faq-answer');
    answers.forEach(a => {
        a.addEventListener('click', function(e) {
            e.stopPropagation(); // prevents accordion toggle when clicking inside
        });
    });
</script>

</body>
</html>
