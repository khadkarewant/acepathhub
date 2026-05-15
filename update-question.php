<?php
ob_start();
    include("src/db/db_conn.php");
    include("src/db/session.php");
    include("src/db/privileges.php");

    

    if(isset($_GET['question_id']) && $_GET['question_id'] !== ""){
        
        $question_id = $_GET['question_id'];

        $get_data = mysqli_query($conn, "SELECT * FROM `questions` WHERE `id` = '".$question_id."'");
        
        if(mysqli_num_rows($get_data) == 0){
            header("Location: home.php");
        }

        while ($row = mysqli_fetch_assoc($get_data)) {

            if($modify_mcq == "true"){
                $mcq_id = $row['mcq_id'];
                $question = $row['question'];
                $option_a =  $row['option_a'];
                $option_b =  $row['option_b'];
                $option_c =  $row['option_c'];
                $option_d =  $row['option_d'];
                $answer =  $row['answer'];
            }else{
                header("Location: courses.php");
            }
        }
        if(isset($_GET['submit']) && $_GET['submit'] == "update_mcq"){
            $question_id = $_GET['question_id'];
            
            $new_question = $_GET['question'];
            
            $new_option_a =  htmlentities($_GET['option_a']);
            $new_option_b =  htmlentities($_GET['option_b']);
            $new_option_c =  htmlentities($_GET['option_c']);
            $new_option_d =  htmlentities($_GET['option_d']);
            
            $new_answer =  htmlentities($_GET['answer']);
            
            $update_mcq = mysqli_query($conn, "UPDATE `questions` SET `question` = '".$new_question."', `option_a` = '".$new_option_a."', `option_b` = '".$new_option_b."', `option_c` = '".$new_option_c."', `option_d` = '".$new_option_d."', `answer` = '".$new_answer."' WHERE `id` = '".$question_id."' ");
    
            if ($update_mcq) {
                echo'
                    <script>
                        alert("Question Updated successfully.");
                        window.location.href="mcq-details.php?mcq_id='.$mcq_id.'";
                    </script>
                ';
            }
        }

    }else{
        header("Location: courses.php");
    }
ob_end_flush();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Question</title>
    <script src="https://cdn.ckeditor.com/4.22.1/standard/ckeditor.js"></script>
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
            <div class="col-md-8">

                <h2>Update Question</h2>
                <form class="form p-1" action="<?php echo $_SERVER["PHP_SELF"]; ?>" method="GET" enctype="multipart/form-data">

                    <input type="text" hidden name="question_id" value="<?php echo $question_id ?>" required/>
                   
                    
                    <label>Question:</label>
                    <textarea name="question" id="editor" style="height:250px;font-size:20px;" class="form-control" required><?php echo $question ?></textarea>
                    
                    <label>Option A:</label>
                    <input type="text" style="font-size:20px;" name="option_a" value="<?php echo $option_a ?>" class="form-control" required/>
        
                    <label>Option B:</label>
                    <input type="text" style="font-size:20px;" name="option_b" value="<?php echo $option_b ?>" class="form-control" required/>
        
                    <label>Option C:</label>
                    <input type="text" style="font-size:20px;" name="option_c" value="<?php echo $option_c ?>" class="form-control" required/>
        
                    <label>Option D:</label>
                    <input type="text" style="font-size:20px;" name="option_d" value="<?php echo $option_d ?>" class="form-control" required/>
        
                    <label>Correct Answer:</label>
                    <select name="answer" class="form-control" required>
                        <option selected value="<?php echo $answer ?>" style="text-transform:capitalize"><?php echo $answer ?></option>
                        <option value="a">A</option>
                        <option value="b">B</option>
                        <option value="c">C</option>
                        <option value="d">D</option>
                    </select>

                    <br>
                    <button type="submit" name="submit" value="update_mcq" class="btn form-submit" style="background:var(--primary);color:white;">Update MCQ</button>
        
                </form>
            </div>
        </div>
    </div>

    <script>
        // Replace the textarea with CKEditor
        CKEDITOR.replace('editor');
    </script>
    
    <?php
        include("src/inc/footer.php");
    ?>
</body>
</html>