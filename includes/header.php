<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';
$cartCount = getCartCount($conn);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Amimi Shop - Toko online terpercaya untuk fashion wanita, pria, anak-anak, aksesoris, dan peralatan rumah tangga.">
    <title><?= isset($pageTitle) ? $pageTitle . ' - Amimi Shop' : 'Amimi Shop - Belanja Online Terpercaya' ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/Amimi/css/style.css">
    <link rel="stylesheet" href="/Amimi/css/shopee-style.css">
</head>
<body>
    <link rel="stylesheet" href="<?= BASE_PATH ?>css/style.css">
    <script>
        // expose BASE_PATH to client and fix hardcoded absolute links at runtime
        window.BASE_PATH = '<?= BASE_PATH ?>';
        window.PROJECT_FOLDER = '<?= PROJECT_FOLDER ?>';
        document.addEventListener('DOMContentLoaded', function(){
            var pf = '/' + window.PROJECT_FOLDER + '/';
            // anchors
            document.querySelectorAll('a[href^="/"]').forEach(function(a){
                var h = a.getAttribute('href');
                if (h.indexOf(pf) === 0) a.setAttribute('href', h.replace(pf, window.BASE_PATH));
            });
            // links (css)
            document.querySelectorAll('link[href^="/"]').forEach(function(l){
                var h = l.getAttribute('href');
                if (h.indexOf(pf) === 0) l.setAttribute('href', h.replace(pf, window.BASE_PATH));
            });
            // images
            document.querySelectorAll('img[src^="/"]').forEach(function(img){
                var s = img.getAttribute('src');
                if (s.indexOf(pf) === 0) img.setAttribute('src', s.replace(pf, window.BASE_PATH));
            });
            // form actions
            document.querySelectorAll('form[action^="/"]').forEach(function(f){
                var a = f.getAttribute('action');
                if (a.indexOf(pf) === 0) f.setAttribute('action', a.replace(pf, window.BASE_PATH));
            });
        });
    </script>
<?php include __DIR__ . '/navbar.php'; ?>
<?php displayFlash(); ?>
<main>
