<?php
include("src/db/db_conn.php");
include("src/db/session.php");
include("src/db/privileges.php");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users Exam Stats</title>

    <?php include("src/inc/links.php"); ?>

    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">

    <style>
        /* Blended table style */
        .custom-table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
        }
        .custom-table th, .custom-table td {
            padding: 10px 12px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
            color: #333;
        }
        .custom-table th {
            background: transparent;
            font-weight: 600;
        }
        .custom-table tr:hover {
            background: #f9f9f9;
        }
        .btn-custom {
            background: var(--primary);
            color: #fff;
            border: none;
            padding: 5px 12px;
            border-radius: 4px;
            font-size: 14px;
        }
        .btn-custom:hover {
            opacity: 0.9;
        }
        @media (max-width: 767px) {
            .custom-table th, .custom-table td {
                padding: 8px;
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
<?php include("src/inc/header.php"); ?>

<div class="container my-4">
    <?php
        if($type == "student"){
            echo '<h4 class="mb-3">Your Performance Stats:</h4>';
        } else {
            echo '<h4 class="mb-3">Students Performance Stats:</h4>';
        }
    ?>
    <div class="table-responsive">
        <table id="datatable" class="custom-table display">
            <thead>
                <tr>
                    <th>Set Id</th>
                    <th class="<?= ($type == 'student') ? 'd-none' : '' ?>">Username</th>
                    <th class="<?= ($type == 'student') ? 'd-none' : '' ?>">Product Id</th>
                    <th>Product Name</th>
                    <th>Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if($type == "student"){
                    $get_exam_stats = mysqli_query($conn ,"SELECT * FROM `exam_stats` WHERE `user_id` = '$user_id' ORDER BY `id` DESC");
                } else {
                    $get_exam_stats = mysqli_query($conn ,"SELECT * FROM `exam_stats` ORDER BY `id` DESC LIMIT 500");
                }

                if(mysqli_num_rows($get_exam_stats) > 0){
                    while($value = mysqli_fetch_assoc($get_exam_stats)){
                        $student_username = '';
                        if($type !== "student"){
                            $get_username = mysqli_query($conn, "SELECT username FROM `users` WHERE `user_id` ='".$value['user_id']."' LIMIT 1");
                            if(mysqli_num_rows($get_username) > 0){
                                $row = mysqli_fetch_assoc($get_username);
                                $student_username = $row['username'];
                            }
                        }

                        echo '<tr>
                                <td>'.$value['set_id'].'</td>
                                <td class="'.($type == 'student' ? 'd-none' : '').'">'.$student_username.'</td>
                                <td class="'.($type == 'student' ? 'd-none' : '').'">'.$value['product_id'].'</td>
                                <td>'.$value['product_name'].'</td>
                                <td>'.$value['attempted_date'].'</td>
                                <td>
                                    <button class="btn-custom" onclick="window.location.href=\'exam-summery.php?set_id='.$value['set_id'].'\'">View</button>
                                </td>
                              </tr>';
                    }
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<?php include("src/inc/footer.php"); ?>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script>
$(document).ready(function(){
    // Only initialize if not already initialized
    if (! $.fn.DataTable.isDataTable('#datatable') ) {
        $('#datatable').DataTable({
            "paging": true,
            "searching": true,
            "ordering": true,
            "info": true,
            "lengthChange": true,
            "pageLength": 10,
            "language": {
                "search": "Search exams:",
                "lengthMenu": "Show _MENU_ entries",
                "info": "Showing _START_ to _END_ of _TOTAL_ exams",
                "infoEmpty": "No exams available",
                "zeroRecords": "No matching exams found"
            },
            "columnDefs": [
                { "orderable": false, "targets": 5 } // disable sorting on Action column
            ]
        });
    }
});
</script>
</body>
</html>
