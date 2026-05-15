<?php
ob_start();
    include("src/db/db_conn.php");
    include("src/db/session.php");
    include("src/db/privileges.php");

   
    if(isset($_GET['mcq_id']) && $_GET['mcq_id'] !== ""){

        $check_mcq_id = mysqli_query($conn, "SELECT * FROM `mcqs` WHERE `id` = '".$_GET['mcq_id']."' ");
        
        if(mysqli_num_rows($check_mcq_id) == 0){
            header("Location: home.php");
        }

        $mcq_id = $_GET['mcq_id'];

        
        if(isset($_GET['submit']) && $_GET['submit'] == "add_question"){
            
            $mcq_id = $_GET['mcq_id'];
           $question = $_GET['question'];   // CKEditor HTML
            $option_a = $_GET['option_a'];   // CKEditor HTML
            $option_b = $_GET['option_b'];   // CKEditor HTML
            $option_c = $_GET['option_c'];   // CKEditor HTML
            $option_d = $_GET['option_d'];   // CKEditor HTML
            $answer = htmlspecialchars($_GET['answer']); // normal text
            $need_upgrade = null;

            $insert_question = mysqli_query($conn, "INSERT INTO `questions`(`mcq_id`, `question`, `option_a`, `option_b`,`option_c`,`option_d`,`answer`,`need_upgrade`)VALUES('".$mcq_id."','".$question."', '".$option_a."','".$option_b."','".$option_c."','".$option_d."', '".$answer."','".$need_upgrade."')");
            


            if ( $insert_question) {

                $get_weight = mysqli_query($conn, "SELECT `question_weight` FROM `mcqs` WHERE `id` = '".$mcq_id."' ");

                if(mysqli_num_rows($get_weight) == 1){
                    while ($row = mysqli_fetch_assoc($get_weight)) {
                        $weight = $row['question_weight'];
                    }
                }

                $new_weight = $weight + 1;

                $update_weight = mysqli_query($conn, "UPDATE `mcqs` SET `question_weight` = '".$new_weight."' WHERE `id` = '".$mcq_id."' ");

                echo'
                    <script>
                        alert("Question Added successfully.");
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
    <title>Add Additional Question</title>
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
                <h2 >Add Question</h2>
                <form  action="<?php echo $_SERVER["PHP_SELF"]; ?>" method="GET" enctype="multipart/form-data">
            
                    <input type="text" hidden name="mcq_id" value="<?php echo $mcq_id ?>" required/>
                    
                    <label>Question:</label>
                    <textarea name="question" id="editor" class="form-control" style="height:250px;font-size:20px;" col="30" required></textarea>
                    
                    <label>Option A:</label>
                    <input type="text" style="font-size:20px;" name="option_a" class="form-control" required/>
            
                    <label>Option B:</label>
                    <input type="text" style="font-size:20px;" name="option_b" class="form-control" required/>
            
                    <label>Option C:</label>
                    <input type="text" style="font-size:20px;" name="option_c" class="form-control" required/>
            
                    <label>Option D:</label>
                    <input type="text" style="font-size:20px;" name="option_d" class="form-control" required/>
            
                    <label>Correct Answer:</label>
                    <select name="answer" class="form-control" required>
                        <option value="" disabled selected>SELECT ONE</option>
                        <option value="a">Option A</option>
                        <option value="b">Option B</option>
                        <option value="c">Option C</option>
                        <option value="d">Option D</option>
                    </select>

                    <br>
                    <button type="submit" name="submit" value="add_question" class="btn" style="background:var(--primary);color:white;">Add MCQ</button>
            
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