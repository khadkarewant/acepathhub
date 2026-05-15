<?php
  ob_start();
  require_once "src/db/db_conn.php";
  require_once "src/db/session.php";
  require_once "src/db/privileges.php";

  if (!isset($_GET['purchased_id']) || !ctype_digit($_GET['purchased_id'])) {
      header("Location: my-products.php");
      exit;
  }

  $purchased_id = (int)$_GET['purchased_id'];
        
  $stmt = mysqli_prepare($conn,
    "SELECT product_id, remaining_sets FROM purchased_products 
     WHERE id = ? AND user_id = ? AND status = 'active'
     LIMIT 1"
  );
  if (!$stmt) {
      header("Location: my-products.php");
      exit;
  }
  mysqli_stmt_bind_param($stmt, 'ii', $purchased_id, $user_id);
  mysqli_stmt_execute($stmt);
  $result = mysqli_stmt_get_result($stmt);
  $purchased = $result ? mysqli_fetch_assoc($result) : null;
  mysqli_stmt_close($stmt);

  if (!$purchased) {
      header("Location: my-products.php");
      exit;
  }

  if ($purchased['remaining_sets'] <= 0) {
      header("Location: my-products.php");
      exit;
  }

  $product_id = (int)$purchased['product_id'];

  $stmt2 = mysqli_prepare($conn,
      "INSERT INTO exam_sets (product_id, user_id, date, time) VALUES (?, ?, ?, ?)"
  );
  if (!$stmt2) {
      header("Location: my-products.php");
      exit;
  }
  $date = date("Y-m-d");
  $time = date("H:i:s");
  mysqli_stmt_bind_param($stmt2, 'iiss', $product_id, $user_id, $date, $time);
  mysqli_stmt_execute($stmt2);
  mysqli_stmt_close($stmt2);      

  $stmt3 = mysqli_prepare($conn,
    "SELECT course_id, name, exam_duration, level_1, level_2, 
            total_question, mark, tag, description 
     FROM products WHERE id = ? LIMIT 1"
  );
  if (!$stmt3) {
      header("Location: my-products.php");
      exit;
  }
  mysqli_stmt_bind_param($stmt3, 'i', $product_id);
  mysqli_stmt_execute($stmt3);
  $result3 = mysqli_stmt_get_result($stmt3);
  $product = $result3 ? mysqli_fetch_assoc($result3) : null;
  mysqli_stmt_close($stmt3);

  if (!$product) {
      header("Location: my-products.php");
      exit;
  }

  $course_id          = (int)$product['course_id'];
  $product_name       = $product['name'];
  $exam_duration      = $product['exam_duration'];
  $level_1            = $product['level_1'];
  $level_2            = $product['level_2'];
  $total_question     = (int)$product['total_question'];
  $mark_each_question = $product['mark'];
  $exam_tag           = $product['tag'];
  $product_description = $product['description'];    

  $stmt4 = mysqli_prepare($conn,
    "SELECT id FROM exam_sets WHERE user_id = ? ORDER BY id DESC LIMIT 1"
  );
  if (!$stmt4) {
      header("Location: my-products.php");
      exit;
  }
  mysqli_stmt_bind_param($stmt4, 'i', $user_id);
  mysqli_stmt_execute($stmt4);
  $result4 = mysqli_stmt_get_result($stmt4);
  $last_set = $result4 ? mysqli_fetch_assoc($result4) : null;
  mysqli_stmt_close($stmt4);

  if (!$last_set) {
      header("Location: my-products.php");
      exit;
  }

  $set_id = (int)$last_set['id'];
    
ob_end_flush();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam Guidelines</title>
    <?php
        include("src/inc/links.php");
    ?>
</head>
<body>
    <?php
        include("src/inc/header.php");
    ?>
    
    <!-- EXAM ATTEMPT DISCLAIMER -->
    <div class="alert alert-danger mt-4 shadow-sm" role="alert">
      <h6 class="alert-heading mb-2">
        <i class="bi bi-exclamation-octagon-fill"></i> Important Exam Disclaimer
      </h6>
    
      <ul class="mb-2">
        <li>
          Once you click <strong>“Start Test”</strong>, one exam set is immediately
          <strong>deducted</strong> from your account.
        </li>
        <li>
          If you <strong>refresh</strong>, <strong>close</strong>, <strong>go back</strong>,
          lose internet connection, or <strong>leave the exam page</strong> for any reason,
          the exam will be considered <strong>attempted</strong>.
        </li>
        <li>
          <strong>The deducted exam set will NOT be restored</strong> under any circumstances.
        </li>
        <li>
          Ensure a <strong>stable internet connection</strong> and sufficient time before starting.
        </li>
      </ul>
    
      <p class="mb-0">
        By clicking <strong>“Start Test”</strong>, you acknowledge that you have read,
        understood, and agreed to the above conditions.
      </p>
    </div>
    
    <div class="container my-4">
  <div class="row justify-content-center">
    <div class="col-md-10">
      <div class="card shadow-sm">
        <div class="card-body">

          <!-- PRODUCT TITLE + DETAILS -->
          <h4 class="mb-1" style="color:var(--primary)"><?php echo $product_name; ?></h4>
          <p class="text-muted mb-2">Set Number: <?php echo $set_id; ?></p>
          <hr>

          <!-- ROW-WISE SYLLABUS / INFO -->
          <h6 class="text-secondary mb-3">Test Details</h6>

          <div class="row g-3">

            <div class="col-sm-6">
              <div class="p-2 border rounded">
                <small class="text-muted">Total MCQs</small>
                <div><strong><?php echo $total_question; ?></strong></div>
              </div>
            </div>

            <div class="col-sm-6">
              <div class="p-2 border rounded">
                <small class="text-muted">Level 1 Questions</small>
                <div><strong><?php echo $level_1; ?></strong></div>
              </div>
            </div>

            <div class="col-sm-6">
              <div class="p-2 border rounded">
                <small class="text-muted">Level 2 Questions</small>
                <div><strong><?php echo $level_2; ?></strong></div>
              </div>
            </div>

            <div class="col-sm-6">
              <div class="p-2 border rounded">
                <small class="text-muted">Exam Duration</small>
                <div><strong><?php echo $exam_duration;  ?> minutes</strong></div>
              </div>
            </div>

            <div class="col-sm-6">
              <div class="p-2 border rounded">
                <small class="text-muted">Negative Marking</small>
                <div><strong>YES (20%)</strong></div>
              </div>
            </div>
            
            <div class="col-sm-6">
              <div class="p-2 border rounded">
                <small class="text-muted">Mark Per Question</small>
                <div><strong><?php echo $mark_each_question; ?></strong></div>
              </div>
            </div>

          </div>

          <!-- BUTTONS -->
          <div class="mt-4 text-center">
            <!--<a href="#" class="btn btn-outline-primary me-2">Preview</a>-->
            <a href="exam.php?set_id=<?php echo $set_id ?>" class="btn btn-primary">Start Test</a>
          </div>

        </div>
      </div>
    </div>
  </div>
</div>

  <?php
    $stmt5 = mysqli_prepare($conn,
      "SELECT id, question_weight FROM question_patterns WHERE product_id = ?"
    );
    if ($stmt5) {
        mysqli_stmt_bind_param($stmt5, 'i', $product_id);
        mysqli_stmt_execute($stmt5);
        $pattern_result = mysqli_stmt_get_result($stmt5);

        while ($pattern_row = mysqli_fetch_assoc($pattern_result)) {
            $question_weight = (int)$pattern_row['question_weight'];
            $pattern_id = (int)$pattern_row['id'];

            for ($i = 1; $i <= $question_weight; $i++) {

                $stmt6 = mysqli_prepare($conn,
                    "SELECT topic_id FROM question_topics WHERE pattern_id = ? ORDER BY RAND() LIMIT 1"
                );
                if (!$stmt6) continue;
                mysqli_stmt_bind_param($stmt6, 'i', $pattern_id);
                mysqli_stmt_execute($stmt6);
                $topic_result = mysqli_stmt_get_result($stmt6);
                $topic_row = $topic_result ? mysqli_fetch_assoc($topic_result) : null;
                mysqli_stmt_close($stmt6);

                if (!$topic_row) { $i--; continue; }
                $topic_id = (int)$topic_row['topic_id'];

                $stmt7 = mysqli_prepare($conn,
                    "SELECT tag FROM topics WHERE id = ? LIMIT 1"
                );
                if (!$stmt7) continue;
                mysqli_stmt_bind_param($stmt7, 'i', $topic_id);
                mysqli_stmt_execute($stmt7);
                $tag_result = mysqli_stmt_get_result($stmt7);
                $tag_row = $tag_result ? mysqli_fetch_assoc($tag_result) : null;
                mysqli_stmt_close($stmt7);

                if (!$tag_row) { $i--; continue; }
                $tag = $tag_row['tag'];

                $stmt8 = mysqli_prepare($conn,
                    "SELECT id FROM mcqs WHERE topic_id = ? AND verified = 'true' AND status = 'live' ORDER BY RAND() LIMIT 1"
                );
                if (!$stmt8) { $i--; continue; }
                mysqli_stmt_bind_param($stmt8, 'i', $topic_id);
                mysqli_stmt_execute($stmt8);
                $mcq_result = mysqli_stmt_get_result($stmt8);
                $mcq_row = $mcq_result ? mysqli_fetch_assoc($mcq_result) : null;
                mysqli_stmt_close($stmt8);

                if (!$mcq_row) { $i--; continue; }
                $new_mcq_id = (int)$mcq_row['id'];

                $stmt9 = mysqli_prepare($conn,
                    "SELECT id FROM exam_questions WHERE mcq_id = ? AND set_id = ? LIMIT 1"
                );
                if (!$stmt9) { $i--; continue; }
                mysqli_stmt_bind_param($stmt9, 'ii', $new_mcq_id, $set_id);
                mysqli_stmt_execute($stmt9);
                mysqli_stmt_store_result($stmt9);
                $already_exists = mysqli_stmt_num_rows($stmt9) > 0;
                mysqli_stmt_close($stmt9);

                if ($already_exists) {
                    $i--;
                    continue;
                }

                $stmt10 = mysqli_prepare($conn,
                    "INSERT INTO exam_questions (set_id, mcq_id, tag) VALUES (?, ?, ?)"
                );
                if (!$stmt10) { $i--; continue; }
                mysqli_stmt_bind_param($stmt10, 'iis', $set_id, $new_mcq_id, $tag);
                mysqli_stmt_execute($stmt10);
                mysqli_stmt_close($stmt10);
            }
        }
        mysqli_stmt_close($stmt5);
    }

  
      
  
  ?>

  <?php
      include("src/inc/footer.php");
  ?>
</body>
</html>