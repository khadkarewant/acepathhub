<?php
// Ensure these variables are set; otherwise, provide defaults
$page_title = $page_title ?? "QuizMania | Lok Sewa Preparation Nepal";
$page_description = $page_description ?? "Prepare for Lok Sewa Aayog exams in Nepal with QuizMania. Practice real exam format mock tests, sample question papers, unlimited MCQs, instant results, and free demo tests online.";
$page_url = "https://quizmania.org" . $_SERVER['REQUEST_URI'];
$page_image = $page_image ?? "https://quizmania.org/src/img/lsw.png";
?>

<!-- CSS -->
<link rel="stylesheet" href="src/css/bootstrap.css">
<link rel="stylesheet" href="src/css/datatable.css">
<link rel="stylesheet" href="src/css/mcq.css">

<!-- JS -->
<script src="src/js/jquery.js"></script>
<script src="src/js/bootstrap.js"></script>
<script src="src/js/datatable.js"></script>
<script src="src/js/mcq.js"></script>
<script src="src/js/header.js"></script>

<!-- SEO Meta -->
<title><?php echo htmlspecialchars($page_title); ?></title>
<meta name="description" content="<?php echo htmlspecialchars($page_description); ?>">

<!-- Robots & Canonical -->
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="<?php echo htmlspecialchars($page_url); ?>">

    <!-- Open Graph / Twitter -->
    <meta property="og:title" content="<?php echo htmlspecialchars($page_title); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($page_description); ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo htmlspecialchars($page_url); ?>">
    <meta property="og:image" content="<?php echo htmlspecialchars($page_image); ?>">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo htmlspecialchars($page_title); ?>">
    <meta name="twitter:description" content="<?php echo htmlspecialchars($page_description); ?>">
    <meta name="twitter:image" content="<?php echo htmlspecialchars($page_image); ?>">

<style>
    .desktop{
        display:block;
    }
    .mobile{
        display:none;
    }

    @media only screen and (max-width: 500px){
        .desktop{
            display:none;
        }
        .mobile{
            display:block;
        }

        #side_panel_icon{
            display:block;
        }

        .side_panel_cancel{
            display: none;
        }

        #side_menu_links{
            display:none;
        }
    }

    *{
        font-family:'Times New Roman', Times, serif;
    }

    :root{
        --primary:rgb(4, 102, 200);
        /* --primary:rgb(14, 36, 99); */
    } 


    .new{
        width:7px;
        height:7px;
        border-radius: 50%;
        background:firebrick;
        display:inline-block;
        position:absolute;
    }

    .profile_img{
        width:30px;
        height:35px;
        border-radius:50%;
        border:1px solid var(--primary);
    }
    .links{
        display:inline-block;
        color:white;
        padding:0px 5px;
        position:relative;
        width:fit-content;
        cursor:pointer;
    }

    .links a{
        text-decoration: none;
        color:white;
        display: block;
    }

    .submenu{
        background: var(--primary);
        position:absolute;
        right:0px;
        text-align:left;
        width:120px;
        display:none;
    }
    .sublinks{
        border:1px solid var(--primary);
        padding:2px 10px;
    }

    .sublinks:hover{
        background:white;
        padding:2px 10px;
    }

    .sublinks:hover a{
        display: block;
        color:var(--primary);
    }


    .icon{
        font-size:30px;
        color:white;
        font-weight:700;
        float:right;
        cursor:pointer;
    }

    .side_panel_icon{
        display:none;
        width:23px;
        padding:3px 7px;
        cursor:pointer;
        box-shadow:0px 0px 5px grey;
        border-radius:50%;
        font-weight:700;
        background:white
    }

    .side_panel_icon:hover{
        transform: scale(1.1);
    }

    .side_panel_cancel{
        display:none;
        width:23px;
        padding:3px 7px;
        cursor:pointer;
        box-shadow:0px 0px 5px grey;
        border-radius:50%;
        font-weight:700;
        background:white;
    }
    .side_panel_cancel:hover{
        box-shadow:0px 0px 5px var(--primary);
    }


    #side_panel{
        position:relative;
    }

    .side_menu_links{
        width:250px;
        max-width:100%;
        height:calc(100vh - 72px);
        position:absolute;
        left:0px;
        border:1px solid var(--primary);
        text-align:left;
        background:whitesmoke;
    }

    .side-links{
        color:var(--primary);
        display:block;
        padding:4px 0px 4px 10px;
        width:100%;
        cursor:pointer;
        border:1px solid var(--primary);
    }

    .side-links a{
        text-decoration: none;
        display: block;
        color:var(--primary);
    }

    .side-submenu{
        background: white;
        width:100%;
        height:fit-content;
        text-align:left;
        display:none;
    }

    .side-sublinks{
        border:1px solid var(--primary);
        padding:4px 10px;
    }

    .side-sublinks:hover{
        background:white;
    }

    .side-sublinks:hover a{
        display: block;
        color:var(--primary);
    }

    .side-links:hover .side-submenu{
        display: block;
        
    }

    .footer a{
        display:block;
        text-decoration:none;
        color: var(--primary);
    }

    .footer a:hover{
        color: grey;
    }

    .messanger img{
        width:40px;
        border-radius: 50%;
        height:40px;
        position: fixed;
        right:18px;
        bottom:18px;
        box-shadow: 0px 0px 10px grey;
        cursor: pointer;
        border:1px solid white;
    }
    .messanger img:hover{
        transform: scale(1.1);
        transition-duration: 200ms;
    }
</style>
<script>
$(document).ready(function(){

    // Prevent JS crash if DataTable or #datatable is missing
    if ($.fn.DataTable && $("#datatable").length) {
        $("#datatable").DataTable();
    }

    $("#side_panel_icon").click(function(){
        $(".side_panel_cancel").css("display", "block");
        $("#side_panel_icon").css("display", "none");
        $("#side_panel").css("display", "block");
        $("#side_menu_links").css("display", "block");
    });

    $(".side_panel_cancel").click(function(){
        $("#side_panel_icon").css("display", "block");
        $(".side_panel_cancel").css("display", "none");
        $("#side_panel").css("display", "none");
        $("#side_menu_links").css("display", "none");            
    });

    // Toggle profile menu on click (mobile)
    $(".profile_menu").on("click", function (e) {
        e.stopPropagation(); // prevent document click
        $(".profile_submenu").toggle();
    });
    
    // Close profile menu when clicking outside
    $(document).on("click", function () {
        $(".profile_submenu").hide();
    });


    $(document).keydown(function(e){
        if(e.key === "Escape"){
            $(".profile_submenu").css("display", "none");
        }
    });

});
</script>

<?php
if(isset($_SESSION['id'])){
?>
    <script>
        setTimeout(() => {
            window.location.href="logout.php";
        }, 1800000);
    </script>

<?php
}
?>

