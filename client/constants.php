<?php
$host = getenv('OPENSHIFT_MYSQL_DB_HOST').":".getenv('OPENSHIFT_MYSQL_DB_PORT');
$username = getenv("OPENSHIFT_MYSQL_DB_USERNAME");
$password = getenv("OPENSHIFT_MYSQL_DB_PASSWORD");
$dbase = "fizteh";
$sqltime = 25200;
$history_dir = getenv('OPENSHIFT_DATA_DIR').'/games';
?>
