<?php
include("src/db/db_conn.php");
include("src/db/session.php");
include("src/db/privileges.php"); 

// Fetch all live practice courses (for now course_id = 2)
$courses_sql = "SELECT * FROM courses WHERE id = 2 AND status = 'live'"; 
$courses_res = mysqli_query($conn, $courses_sql);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Practice Courses | QuizMania</title>
    <?php include("src/inc/links.php"); // CSS/JS links ?>

<style>
body { 
    font-family: Arial, sans-serif; 
    background:#f5f5f5; 
    margin:0; 
    padding:0;
}

.container { 
    max-width: 900px; 
    margin: 0 auto; 
    padding: 20px; 
}

.page-title { 
    text-align:center; 
    margin-bottom: 30px; 
    font-size:2em; 
    color:#333; 
}

.course-card { 
    background:#fff; 
    margin-bottom:20px; 
    padding:15px; 
    border-radius:10px; 
    box-shadow:0 2px 8px rgba(0,0,0,0.1); 
    cursor:pointer; 
    transition: 0.2s; 
}

.course-card:hover { 
    background:#007bff; 
    color:#fff; 
}

.course-card:hover .course-title {
    color:#fff; 
}

.course-title { 
    margin-top:0; 
    color:#222; 
    font-size: 1.5em;
    text-align:center;
}

.coming-soon { 
    text-align:center; 
    padding:50px; 
    font-size:1.2em; 
    color:#555; 
}

/* MOBILE RESPONSIVE */
@media (max-width: 600px) {
    .course-card { padding:15px; }
    .course-title { font-size:1.3em; }
}
</style>

</head>
<body>

<?php include("src/inc/header.php"); ?>

<main class="container">
    <h1 class="page-title">Practice Courses</h1>

    <?php if(mysqli_num_rows($courses_res) > 0): ?>
        <?php while($course = mysqli_fetch_assoc($courses_res)): ?>
            <!-- Make the whole card clickable -->
            <article class="course-card" onclick="window.location='learn.php?course_id=<?php echo $course['id']; ?>';">
                <h2 class="course-title"><?php echo htmlspecialchars($course['name']); ?></h2>
            </article>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="coming-soon">
            Nothing available at the moment. <br>
            New courses are coming live soon! 🚀
        </div>
    <?php endif; ?>

</main>

<?php include("src/inc/footer.php"); ?>

</body>
</html>
