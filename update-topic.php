<?php
    include("src/db/db_conn.php");
    include("src/db/session.php");
    include("src/db/privileges.php");

    

    if(isset($_GET['topic_id']) && $_GET['topic_id'] !== ""){
        
        $topic_id = $_GET['topic_id'];
        $get_data = mysqli_query($conn, "SELECT * FROM `topics` WHERE `id` = '".$topic_id."'");
        
        if(mysqli_num_rows($get_data) == 0){
            header("Location: home.php");
        }
        
        while ($row = mysqli_fetch_assoc($get_data)) {
            if($modify_topic == "true"){
                $topic_name = $row['name'];
                $description = $row['description'];
            }else{
                header("Location: courses.php");
            }
        }
        if(isset($_GET['submit']) && $_GET['submit'] == "update_topic"){
            $new_name = htmlentities($_GET['topic_name']);
            $new_description =  htmlentities($_GET['description']);
    
            $update_topic = mysqli_query($conn, "UPDATE `topics` SET `name` = '".$new_name."', `description` = '".$new_description."' WHERE `id` = '".$topic_id."' ");
    
            if ($update_topic) {
                echo'
                    <script>
                        alert("Topic Updated successfully.");
                        window.location.href="topic-details.php?topic_id='.$topic_id.'";
                    </script>
                ';
            }
        }

    }else{
        header("Location: courses.php");
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Topic </title>
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
            <div class="col-md-6">
                <h2>Update Topic</h2>
                <form class="form p-1" action="<?php echo $_SERVER["PHP_SELF"]; ?>" method="GET" enctype="multipart/form-data">
            
                    <input type="text" name="topic_id" hidden value="<?php echo $topic_id; ?>" required/>
            
                    <label>Topic Name:</label>
                    <input type="text" name="topic_name" placeholder="Topic Name" class="form-control" value="<?php echo $topic_name; ?>" required/>
                    
                    
                    <label>Description:</label>
                    <input type="text" placeholder="Enter description" name="description" class="form-control"  value="<?php echo $description; ?>" required/>
                    <br>
                    
                    <button type="submit" name="submit" value="update_topic" class="btn form-submit" style="background:var(--primary);color:white;">Update Topic</button>
            
                </form>

            </div>
        </div>
    </div>


    <?php
        include("src/inc/footer.php");
    ?>
</body>
</html>