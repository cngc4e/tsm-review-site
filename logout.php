<?php

include_once 'siteutils.php';

session_start();

if (isset($_SESSION['user_id'])) {
    SiteUtils::logOut();
}
header('Location: index.php');
