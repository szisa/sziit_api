<?php
use api\module\stadium as stadium;

require_once(dirname(dirname(__FILE__))."/header.php");
require_once(dirname(dirname(__FILE__))."/module/stadium.php");

$error = "";
$json = "";
$empty = '"status" : "failed"'; // 订场失败

// 接口检查
if (!isset($_GET["u"]) && !isset($_GET["p"]) && !isset($_GET["n"]) && !isset($_GET["t"]) && !isset($_GET["d"])) 
{
	echo '{'.$empty.', "error":"not enough arguments."}';
	exit(0);
}

$user = $_GET["u"]; 
$passwd = $_GET["p"];
$number = $_GET["n"];
$time = $_GET["t"];
$date = $_GET["d"];

// 登录体育馆
$cookies = stadium::Login($user, $passwd, $error);

if ("" == $cookies)
{
	echo '{'.$empty.', "error" : "'.$error.'"}';
	exit(0);
}

// 预定场馆
$status = stadium::Order($cookies, $date, $time, $number, $error);

// 移除Cookies
if (file_exists($cookies)) unlink($cookies);

if (!$status)
{
	echo '{'.$empty.', "error" : "'.$error.'"}';
	exit(0);
}

echo '{"status" : "success"}';