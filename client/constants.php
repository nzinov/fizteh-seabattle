<?php
$host = $_SERVER['OPENSHIFT_MYSQL_DB_HOST'].":".$_SERVER['OPENSHIFT_MYSQL_DB_PORT'];
$username = $_SERVER["OPENSHIFT_MYSQL_DB_USERNAME"];
$password = $_SERVER["OPENSHIFT_MYSQL_DB_PASSWORD"];
$dbase = "fizteh";
$sqltime = 25200;
$history_dir = $_SERVER['OPENSHIFT_DATA_DIR'].'/games';
?>
