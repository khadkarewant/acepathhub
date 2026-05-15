<?php

        ob_start();
        require_once "src/db/db_conn.php";
        require_once "src/db/session.php";
        require_once "src/db/privileges.php";

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || ($_POST['submit'] ?? '') !== 'submit_exam') {
            header("Location: home.php");
            exit;
        }

        if (!isset($_POST['set_id']) || !ctype_digit($_POST['set_id'])) {
            header("Location: home.php");
            exit;
        }

        $set_id = (int)$_POST['set_id'];

        $stmt = mysqli_prepare($conn,
            "SELECT product_id FROM exam_sets 
            WHERE id = ? AND user_id = ? AND attempted = 'no'
            LIMIT 1"
        );
        if (!$stmt) {
            header("Location: home.php");
            exit;
        }
        mysqli_stmt_bind_param($stmt, 'ii', $set_id, $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $set_row = $result ? mysqli_fetch_assoc($result) : null;
        mysqli_stmt_close($stmt);

        if (!$set_row) {
            header("Location: home.php");
            exit;
        }

        $product_id = (int)$set_row['product_id'];

        $stmt_update = mysqli_prepare($conn,
            "UPDATE exam_sets SET attempted = 'yes' WHERE id = ? AND user_id = ? LIMIT 1"
        );
        if ($stmt_update) {
            mysqli_stmt_bind_param($stmt_update, 'ii', $set_id, $user_id);
            mysqli_stmt_execute($stmt_update);
            mysqli_stmt_close($stmt_update);
        }

        $stmt2 = mysqli_prepare($conn,
            "SELECT name, total_question, mark FROM products WHERE id = ? LIMIT 1"
        );
        if (!$stmt2) {
            header("Location: home.php");
            exit;
        }
        mysqli_stmt_bind_param($stmt2, 'i', $product_id);
        mysqli_stmt_execute($stmt2);
        $result2 = mysqli_stmt_get_result($stmt2);
        $product = $result2 ? mysqli_fetch_assoc($result2) : null;
        mysqli_stmt_close($stmt2);

        if (!$product) {
            header("Location: home.php");
            exit;
        }

        $product_name = $product['name'];
        $total_question = (int)$product['total_question'];
        $mark = (float)$product['mark'];
        

        $total_attempt = 0;
        $correct_attempt = 0;
        $wrong_attempt = 0;
        $results_data = [];

        for ($i = 1; $i <= $total_question; $i++) {
            if (!isset($_POST['answer'.$i])) continue;

            $question_id = isset($_POST['question_id'.$i]) && ctype_digit($_POST['question_id'.$i])
                ? (int)$_POST['question_id'.$i]
                : null;

            if (!$question_id) continue;

            $answer = in_array($_POST['answer'.$i], ['a','b','c','d','nan'])
                ? $_POST['answer'.$i]
                : 'nan';

            if ($answer !== 'nan') $total_attempt++;

            // Get mcq_id and correct answer in one query
            $stmt3 = mysqli_prepare($conn,
                "SELECT mcq_id, answer FROM questions WHERE id = ? LIMIT 1"
            );
            if (!$stmt3) continue;
            mysqli_stmt_bind_param($stmt3, 'i', $question_id);
            mysqli_stmt_execute($stmt3);
            $result3 = mysqli_stmt_get_result($stmt3);
            $question_row = $result3 ? mysqli_fetch_assoc($result3) : null;
            mysqli_stmt_close($stmt3);

            if (!$question_row) continue;

            $mcq_id = (int)$question_row['mcq_id'];
            $correct_answer = $question_row['answer'];

            $is_correct = ($answer !== 'nan' && $answer === $correct_answer);
            if ($is_correct) $correct_attempt++;
            elseif ($answer !== 'nan') $wrong_attempt++;

            $results_data[] = [
                'question_id'    => $question_id,
                'mcq_id'         => $mcq_id,
                'answer'         => $answer,
                'correct_answer' => $correct_answer,
            ];
        }

        $gross_mark    = $correct_attempt * $mark;
        $negative_mark = $wrong_attempt * $mark * 0.20;
        $net_mark      = $gross_mark - $negative_mark;
        $percentage    = $total_question > 0
            ? ($net_mark / ($total_question * $mark)) * 100
            : 0;

        // Wrap inserts in transaction
        mysqli_begin_transaction($conn);
        try {
            $stmt_res = mysqli_prepare($conn,
                "INSERT INTO results (set_id, user_id, mcq_id, question_id, attempted_answer, correct_answer, submitted_on)
                VALUES (?, ?, ?, ?, ?, ?, ?)"
            );
            if (!$stmt_res) throw new Exception("prepare results failed");

            $submitted_on = date("Y-m-d H:i:s");
            foreach ($results_data as $r) {
                mysqli_stmt_bind_param($stmt_res, 'iiissss',
                    $set_id,
                    $user_id,
                    $r['mcq_id'],
                    $r['question_id'],
                    $r['answer'],
                    $r['correct_answer'],
                    $submitted_on
                );
                if (!mysqli_stmt_execute($stmt_res)) throw new Exception("insert result failed");
            }
            mysqli_stmt_close($stmt_res);

            $stmt_stats = mysqli_prepare($conn,
                "INSERT INTO exam_stats (user_id, set_id, attempted_date, product_id, product_name, total_question, total_attempt, correct_attempt, wrong_attempt, gross_mark, negative_mark, net_mark, percentage)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            if (!$stmt_stats) throw new Exception("prepare stats failed");

            $attempted_date = date("Y-m-d H:i:s");
            mysqli_stmt_bind_param($stmt_stats, 'iisisiiiiiddd',
                $user_id,
                $set_id,
                $attempted_date,
                $product_id,
                $product_name,
                $total_question,
                $total_attempt,
                $correct_attempt,
                $wrong_attempt,
                $gross_mark,
                $negative_mark,
                $net_mark,
                $percentage
            );
            if (!mysqli_stmt_execute($stmt_stats)) throw new Exception("insert stats failed");
            mysqli_stmt_close($stmt_stats);

            $stmt_notif = mysqli_prepare($conn,
                "INSERT INTO notification (user_id, notification, date, time)
                VALUES (?, 'You just attempted exam. Please check your stats to know more.', ?, ?)"
            );
            if (!$stmt_notif) throw new Exception("prepare notification failed");

            $date = date("Y-m-d");
            $time = date("H:i:s");
            mysqli_stmt_bind_param($stmt_notif, 'iss', $user_id, $date, $time);
            if (!mysqli_stmt_execute($stmt_notif)) throw new Exception("insert notification failed");
            mysqli_stmt_close($stmt_notif);

            mysqli_commit($conn);
            header("Location: exam-summery.php?set_id=" . $set_id);
            exit;

        } catch (Exception $e) {
            mysqli_rollback($conn);
            header("Location: home.php?err=exam_failed");
            exit;
        }      
    
    ob_end_flush();
?>