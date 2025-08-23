<?php
session_start();
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}
$username = htmlspecialchars($_SESSION['username']);

$page = $_GET['page'] ?? 'home';
$allowed = ['home', 'tickets', 'bestellungen', 'textbestellungen', 'bestand', 'steuermarken'];
if (!in_array($page, $allowed)) {
    $page = 'home';
}
?>
<!DOCTYPE html>
<html lang="zxx">

<head>
    <meta charset="utf-8" />
    <meta http-equiv="x-ua-compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="description" content="" />
    <meta name="keyword" content="" />
    <meta name="author" content="flexilecode" />
    <!--! The above 6 meta tags *must* come first in the head; any other head content must come *after* these tags !-->
    <!--! BEGIN: Apps Title-->
    <title>Hoffmann Intranet || Dashboard</title>
    <!--! END:  Apps Title-->
    <!--! BEGIN: Favicon-->
    <link rel="shortcut icon" type="image/x-icon" href="assets/images/favicon.ico" />
    <!--! END: Favicon-->
    <!--! BEGIN: Bootstrap CSS-->
    <link rel="stylesheet" type="text/css" href="assets/css/bootstrap.min.css" />
    <!--! END: Bootstrap CSS-->
    <!--! BEGIN: Vendors CSS-->
    <link rel="stylesheet" type="text/css" href="assets/vendors/css/vendors.min.css" />
    <link rel="stylesheet" type="text/css" href="assets/vendors/css/daterangepicker.min.css" />
    <!--! END: Vendors CSS-->
    <!--! BEGIN: Custom CSS-->
    <link rel="stylesheet" type="text/css" href="assets/css/theme.min.css" />
    <!--! END: Custom CSS-->
    <!--! HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries !-->
    <!--! WARNING: Respond.js doesn"t work if you view the page via file: !-->
    <!--[if lt IE 9]>
                        <script src="https:oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
                        <script src="https:oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
                <![endif]-->
</head>

<body>
<?php include 'menu.php'; ?>
<main class="nxl-container">
<?php include __DIR__ . '/pages/' . $page . '.php'; ?>
</main>
<!--! ================================================================ !-->
<!--! Footer Script !-->
<!--! ================================================================ !-->
<!--! BEGIN: Vendors JS !-->
<script src="assets/vendors/js/vendors.min.js"></script>
<!-- vendors.min.js {always must need to be top} -->
<script src="assets/vendors/js/daterangepicker.min.js"></script>
<script src="assets/vendors/js/apexcharts.min.js"></script>
<script src="assets/vendors/js/circle-progress.min.js"></script>
<!--! END: Vendors JS !-->
<!--! BEGIN: Apps Init  !-->
<script src="assets/js/common-init.min.js"></script>
<script src="assets/js/dashboard-init.min.js"></script>
<!--! END: Apps Init !-->
<!--! BEGIN: Theme Customizer  !-->
<script src="assets/js/theme-customizer-init.min.js"></script>
<!--! END: Theme Customizer !-->
</body>

</html>
