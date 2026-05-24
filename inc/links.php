<?php
$page_title       = $page_title       ?? "AcePath Hub | Nigerian Exam Preparation";
$page_description = $page_description ?? "Prepare for JAMB, WAEC, NECO and more with AcePath Hub. Unlimited MCQ practice, instant results, mock tests online.";
$page_url         = "https://acepathhub.com" . $_SERVER['REQUEST_URI'];
$page_image       = $page_image       ?? "https://acepathhub.com/assets/img/logo.png";
?>


<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<!-- CSS CDN -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<!-- Custom CSS -->
<link rel="stylesheet" href="assets/css/style.css">

<!-- SEO Meta -->
<meta name="robots" content="index, follow">
<link rel="canonical" href="<?= htmlspecialchars($page_url) ?>">

<!-- Open Graph -->
<meta property="og:title"       content="<?= htmlspecialchars($page_title) ?>">
<meta property="og:description" content="<?= htmlspecialchars($page_description) ?>">
<meta property="og:type"        content="website">
<meta property="og:url"         content="<?= htmlspecialchars($page_url) ?>">
<meta property="og:image"       content="<?= htmlspecialchars($page_image) ?>">

<!-- Twitter -->
<meta name="twitter:card"        content="summary_large_image">
<meta name="twitter:title"       content="<?= htmlspecialchars($page_title) ?>">
<meta name="twitter:description" content="<?= htmlspecialchars($page_description) ?>">
<meta name="twitter:image"       content="<?= htmlspecialchars($page_image) ?>">

<!-- JS CDN -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<!-- Custom JS -->
<script src="assets/js/app.js"></script>

<?php if(isset($_SESSION['id'])): ?>
<script>
    setTimeout(() => { window.location.href = "logout.php"; }, 1800000);
</script>
<?php endif; ?>