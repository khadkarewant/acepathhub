<?php
// ===================== CHECK IF USER IS ALLOWED =====================
// Assuming $user_id is already set from session
$allowed_check = mysqli_query($conn, "
    SELECT 1 
    FROM allowed_users 
    WHERE user_id = '$user_id' 
    LIMIT 1
");

if(mysqli_num_rows($allowed_check) === 0){
    // User is not allowed, redirect
    header("Location: home.php");
    exit();
}
?>







<?php
        // for the Useful Download link (add pdfs here)
    $downloads = [
        [
            "title" => "शाखा अधिकृत विज्ञापन (२०८२.०८.२४)",
            "file"  => "src/link_pdf/officer_advertisement.2082.08.24.pdf"
        ],
        [
            "title" => "Exam Rules 2079",
            "file"  => "src/link_pdf/exam_rules.pdf"
        ],
        [
            "title" => "Syllabus 2079",
            "file"  => "src/link_pdf/syllabus_2079.pdf"
        ]
    ];
    
?>

Download links
    <div class="col-md-12 mt-4">
        <h5>Useful Downloads</h5>
        <ul class="list-group">
            <?php
            $found = false;
            foreach($downloads as $d){
                if(file_exists($d['file'])){
                    $found = true;
                    echo '<li class="list-group-item">
                            <a href="'.$d['file'].'" target="_blank">'.$d['title'].'</a>
                          </li>';
                }
            }
    
            if(!$found){
                echo '<li class="list-group-item text-muted">No links found.</li>';
            }
            ?>
        </ul>
    </div>