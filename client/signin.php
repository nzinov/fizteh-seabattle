<?php
session_start();
include('db.php');
set_include_path(get_include_path() . PATH_SEPARATOR . "./lib/src");
require_once 'Google/Client.php';
require_once 'Google/Service/Plus.php';
$act = $_REQUEST['act'];
$client = new Google_Client();
$client->setClientId($client_id);
$client->setClientSecret($client_secret);
$client->setRedirectUri('postmessage');

$plus = new Google_Service_Plus($client);
if ($act == "logout")
{
    mysql_query("UPDATE `users` SET `online`=SUBTIME(NOW(),'0 0:10:0') WHERE `id` = '".$_SESSION['id']."';");
    unset($_SESSION['token']);
    unset($_SESSION['id']);
}
else if($act == "disconnect")
{
    mysql_query("DELETE FROM `users` WHERE `id` = '{$_SESSION['id']}';");
    $token = json_decode($_SESSION['token'])->access_token;
    $client->revokeToken($token);
    unset($_SESSION['token']);
    unset($_SESSION['id']);
}
else if ($act == "connect")
{
    if (!isset($_SESSION['token']))
    {
        if ($_REQUEST['state'] != $_SESSION['state']) {
            http_response_code(401);
            die();
        }
        $_SESSION['state'] = "";
        $code = $_REQUEST['code'];
        $client->authenticate($code);
        $token = json_decode($client->getAccessToken());
        $attributes = $client->verifyIdToken($token->id_token, $client_id)
            ->getAttributes();
        $gplus_id = $attributes["payload"]["sub"];
        $_SESSION['token'] = json_encode($token);
        $sql = "SELECT * FROM `users` WHERE `gid` = $gplus_id;";
        $res = mysql_query($sql);
        $me = $plus->people->get('me');
        if (mysql_num_rows($res)==0)
        {
            $name = $me->getDisplayName();
            $sql = "INSERT INTO `users` (`gid`, `name`, `rate`) VALUES ($gplus_id, '$name', 22000);";
            mysql_query($sql);
            $id = mysql_insert_id();
        }
        else
        {
            $name = mysql_result($res, 0, 'name');
            $id = mysql_result($res, 0, 'id');
        }
        $_SESSION['name'] = $name;
        $_SESSION['id'] = $id;
        $_SESSION['image_url'] = $me->getImage()->getUrl();
    }
}
?>
