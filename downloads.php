<?php
include("src/db/db_conn.php");
include("src/db/session.php");
include("src/db/privileges.php"); 

// Folders to fetch PDFs from
$pdfFolders = [
    'Syllabus'      => 'src/syllabus/',
    'Advertisement' => 'src/link_pdf/'
];

// Map filenames to custom titles
$pdfTitles = [
    '1.pdf' => 'खरिदार वा सो सरह (अप्राविधिक) (आ.व. २०८१/८२ को विज्ञापन देखि लागू हुने पाठ्यक्रम)',
    '2.pdf' => 'नायव सुब्वा वा सो सरह (अप्राविधिक) (आ.व. २०८१/८२ को विज्ञापन देखि लागू हुने पाठ्यक्रम)',
    '3.pdf' => 'शाखा अधिकृत वा सो सरह (अप्राविधिक)',
    'officer_advertisement.2082.08.24.pdf' =>
        'शाखा अधिकृत वा सो सरह (अप्राविधिक) (सूचना नं. ६५०/०८२-८३) (विज्ञापन प्रकाशित मिति: २०८२/०८/२४)',

    // ✅ NEW advertisement PDF
    'nasu_advertisement.2082.09.30.pdf' =>
        'नायब सुब्बा वा सो सरह (अप्राविधिक) (सूचना नं. ९०१/०८२-८३) (विज्ञापन प्रकाशित मिति: २०८२/०९/३०)'
];


$downloads = [];

// Scan folders and build downloads array
foreach($pdfFolders as $category => $folder){
    if(is_dir($folder)){
        $files = scandir($folder);
        foreach($files as $file){
            if(in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), ['pdf'])){
                $downloads[] = [
                    'file'     => $folder . $file,
                    'title'    => $pdfTitles[$file] ?? $file,
                    'category' => $category
                ];
            }
        }
    }
}

// Serve PDF inline if requested
if(isset($_GET['file'])){
    $file = $_GET['file'];
    $file = str_replace(['../','..\\'], '', $file);

    $allowedDirs = ['src/syllabus/','src/link_pdf/'];
    $allowed = false;
    foreach($allowedDirs as $dir){
        if(str_starts_with($file, $dir)){
            $allowed = true;
            break;
        }
    }

    if(!$allowed || !file_exists($file)){
        http_response_code(404);
        exit('File not found');
    }

    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="'.basename($file).'"');
    header('Content-Length: '.filesize($file));
    readfile($file);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Downloads</title>
<?php include("src/inc/links.php"); ?>
<style>
.download-card{
    display:flex;
    align-items:center;
    border-radius:6px;
    margin-bottom:10px;
    padding:12px 15px;
    font-weight:bold;
    text-decoration:none;
    color:#fff;
    transition: transform 0.2s;
}
.download-card:hover{
    transform: scale(1.02);
}
.download-card a{
    color:#fff;
    text-decoration:none;
    flex-grow:1;
}
.download-card .icon{
    margin-right:12px;
    font-size:20px;
}
/* Color codes */
.download-card.syllabus{ background:#1e88e5; }       /* Blue for Syllabus */
.download-card.advertisement{ background:#43a047; } /* Green for Advertisement */
.section-title{
    margin-top:30px;
    margin-bottom:15px;
    border-bottom:2px solid var(--primary);
    padding-bottom:5px;
    color:var(--primary);
    font-weight:bold;
}
</style>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
</head>
<body>
<?php include("src/inc/header.php"); ?>

<div class="container mt-4">

    <!-- Syllabus Section -->
    <h4 class="section-title">पाठ्यक्रम</h4>
    <?php 
    $syllabusDownloads = array_filter($downloads, fn($d) => $d['category'] === 'Syllabus'); 
    if(!empty($syllabusDownloads)):
        foreach($syllabusDownloads as $d):
            if(file_exists($d['file'])): ?>
                <div class="download-card syllabus">
                    <span class="icon"><i class="fa-solid fa-file-pdf"></i></span>
                    <a href="<?php echo '?file=' . urlencode($d['file']); ?>" target="_blank"><?php echo $d['title']; ?></a>
                </div>
            <?php endif;
        endforeach;
    else: ?>
        <p class="text-muted">No syllabus downloads available.</p>
    <?php endif; ?>

    <!-- Advertisement Section -->
    <h4 class="section-title">विज्ञापन</h4>
    <?php 
    $advDownloads = array_filter($downloads, fn($d) => $d['category'] === 'Advertisement'); 
    if(!empty($advDownloads)):
        foreach($advDownloads as $d):
            if(file_exists($d['file'])): ?>
                <div class="download-card advertisement">
                    <span class="icon"><i class="fa-solid fa-file-pdf"></i></span>
                    <a href="<?php echo '?file=' . urlencode($d['file']); ?>" target="_blank"><?php echo $d['title']; ?></a>
                </div>
            <?php endif;
        endforeach;
    else: ?>
        <p class="text-muted">No advertisement downloads available.</p>
    <?php endif; ?>

</div>

<?php include("src/inc/footer.php"); ?>
</body>
</html>
