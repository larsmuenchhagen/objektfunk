<?php
/*
 * Copyright (c) 2021  Lars Münchhagen
 * email: lars.muenchhagen@outlook.de
 */

if (!isset($_SESSION)){session_start();}
/*-----------------REQUIREMENTS------------------*/

const ROOT = __DIR__;
require_once './system/Autoloader.php';

/*-----------------TODO AND FIXES--------------*/
?>

<html lang="de">
<head>
    <title>Objektfunkdatenbank</title>
    <link rel="stylesheet" href="resources/css/general.css">
    <link rel="stylesheet" href="resources/css/login.css">
</head>
<body>
<header>
    <h1>Objektfunkdatenbank</h1>
</header>
<main>
    <div class="lightbox"></div>
</main>
</body>
</html>
