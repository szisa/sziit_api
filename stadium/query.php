<?php
use api\module\stadium as stadium;

require_once(dirname(dirname(__FILE__))."/header.php");
require_once(dirname(dirname(__FILE__))."/module/stadium.php");

$error = "";
$json = "";
$empty = '{list:[]}'; // 查询不到

// 接口检查
if (!isset($_GET["k"]) && !isset($_GET["d"])) 
{
	echo '{'.$empty.', "error":"not enough arguments."}';
	exit(0);
}

$user = "1201260241";
$passwd = "123456";
$kind = $_GET["k"];
$date = $_GET["d"];
$time = isset($_GET["t"]) ? $_GET["t"] : "";

// 登录体育馆
$cookies = stadium::Login($user, $passwd, $error);

if ("" == $cookies)
{
	echo '{'.$empty.', "error" : "'.$error.'"}';
	exit(0);
}

// 查询订场信息
$json = stadium::Query($cookies, $kind, $date, $time, $error);

// 移除Cookies
if (file_exists($cookies)) unlink($cookies);

if ($error != "")
{
	echo '{'.$empty.', "error" : "'.$error.'"}';
	exit(0);
}

echo $json;