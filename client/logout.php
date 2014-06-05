<?
session_start();
session_unset();
session_destroy();
header("Location: index.php?alert=4&alerttype=info");
?>