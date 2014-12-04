<?php
$host = getenv('OPENSHIFT_MYSQL_DB_HOST').":".getenv('OPENSHIFT_MYSQL_DB_PORT');
$username = getenv("OPENSHIFT_MYSQL_DB_USERNAME");
$password = getenv("OPENSHIFT_MYSQL_DB_PASSWORD");
$dbase = "fizteh";
$sqltime = 25200;
$data_dir = getenv('OPENSHIFT_DATA_DIR');
$history_dir = $data_dir.'/games';
$client_id = "220267231332-46hns53sk33pkpbqc4ohd1iu913nreu0.apps.googleusercontent.com";
include($data_dir.'/secret_constants.php');
?>
