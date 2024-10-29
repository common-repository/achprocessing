<?php

require('../../../wp-blog-header.php');
?>
<?php
// ?type=cron
$type = $_GET['type'];

$ACHP_Admin = new ACHP_Admin();
$ACHP_Admin->schedule_status_update();

if ($type == 'cron') {
    echo 'success';
} else {
    header('Location: ' . $_SERVER['HTTP_REFERER']);
}

