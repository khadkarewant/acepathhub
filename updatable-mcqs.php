<?php
    include("src/db/db_conn.php");
    include("src/db/session.php");
    include("src/db/privileges.php");

    require_once "src/security/csrf.php";

    if($view_topic !== "true"){
        header("Location: courses.php");
    }

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Updatable Unsorted MCQs</title>
    <?php
        include("src/inc/links.php");
    ?>
</head>
<body>
    <?php
       include("src/inc/header.php");
    ?>

    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12 table-responsive">
                <h3>Updatable Unsorted MCQs:</h3>
                <table class="table table-bordered table-hovered" id="datatable">
                    <thead>
                        <tr>
                            <th rowspan="2">Question</th>
                            <th colspan="2" class="text-center">Need Future Update?</th>
                        </tr>
                        <tr>
                            <th class="text-center">Yes</th>
                            <th class="text-center">No</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php

                            $get_question = mysqli_query($conn, "SELECT * FROM `questions` WHERE `need_upgrade` IS NULL LIMIT 500");
                            foreach ($get_question as $key => $value) {
                                echo '
                                    <tr>
                                        <td>'.htmlspecialchars($value['question'], ENT_QUOTES, "UTF-8").'</td>
                                        <td>
                                            <form method="POST" action="question_update-query.php" style="display:inline;">
                                                '.csrf_input().'
                                                <input type="hidden" name="q" value="'.(int)$value['id'].'">
                                                <input type="hidden" name="e" value="yes">
                                                <button type="submit" class="bg-success">Yes</button>
                                            </form>
                                        </td>
                                        <td>
                                            <form method="POST" action="question_update-query.php" style="display:inline;">
                                                '.csrf_input().'
                                                <input type="hidden" name="q" value="'.(int)$value['id'].'">
                                                <input type="hidden" name="e" value="no">
                                                <button type="submit" class="bg-danger">No</button>
                                            </form>
                                        </td>
                                    </tr>
                                    ';
                            }      
                        
                            
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>




    <?php
        include("src/inc/footer.php");
    ?>
</body>
</html>