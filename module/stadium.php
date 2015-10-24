<?php
namespace api\module;

use api\lib\curl as curl;

require_once(dirname(dirname(__FILE__))."/lib/curl.php");

class stadium
{
	static $numbers = array(
		array( "100" ), // 游泳馆
		array( "201", "202", "203", "204", "205", "206", "207", "208", "223", "224", "225", "226", "227", "228", "229" ), // 羽毛球
		array( "301", "302", "303", "304" ), // 网球场
		array( "401", "402", "403", "404", "405", "406", "407", "408", "409", "410" ), // 篮球场
		array( "501", "502", "503", "504", "505", "506" ), // 排球场
		array( "602" ), // 足球场
		array("701", "702", "703", "704", "705", "706", "707", "708", "709", "710", "711", "712", "713", "714", "715", "716", "717", "718", "719", "720" ), // 乒乓球
		array("800" ), // 健身房
	);
	
	static function Login($user, $passwd, &$error)
	{
		$error = "";
		$times = 3;
		$curDate = date("Ymdhis");
		$cookies = dirname(dirname(__FILE__))."/cookies/stadium.login.".$curDate.".cookie";
		
		// 将登錄界面的cookie写入文件中
		$Home = "http://113.106.49.136:860/bs/index.htm";
		$html = curl::Cookies($Home, $cookies, "");
		
		CAPTCHA:
		// 利用登錄界面的cookie获取验证码圖片
		$url = "http://113.106.49.136:860/bs/images/Image.jsp";
		$captchaFile = dirname(__FILE__)."\\".$curDate."captcha.jpg";
		$file = fopen($captchaFile, "w");
		fwrite($file, curl::Get($url, $cookies));
		fclose($file);

		if(!file_exists($captchaFile))
		{
			$error = "captcha file save failed.";
			return "";
		}
		// 使用captcha.exe破解驗證碼
		$verCode = exec(dirname(dirname(__FILE__))."\\bin\\captcha.exe ".$captchaFile);
		unlink($captchaFile);
		
		// 獲取登錄賬號基本信息
		$CallBack = "http://113.106.49.136:861/SUReader/page/InitqAcc.jsp?pacc=".$user."&callback=";
		$jsonBack = trim(trim(curl::Get($CallBack, $cookies)), "()");
		$jsonBack = json_decode($jsonBack);

		// 生成登錄請求Post數據
		/*member_no=1201260241&password=123456&verCode=5555&workstation_no=&name=%D1%EE%D3%C0%C7%E0&sexNo=1&deptCode=001124101106&cardNo=3408270927&accountNo=3408270927&studentCode=1201260241&idCard=441521199207211132&pid=01&balance=15030&idNo=000000006495&expireDate=330405*/
		$PostArray = array(
			"member_no" => $user,
			"password" => $passwd,
			"verCode" => $verCode,
			"workstation_no" => "",
			"name" => urlencode(mb_convert_encoding($jsonBack->name, 'gb2312')),
			"sexNo" => $jsonBack->sexNo,
			"deptCode" => $jsonBack->deptCode,
			"cardNo" => $jsonBack->cardNo,
			"accountNo" => $jsonBack->accountNo,
			"studentCode" => $jsonBack->studentCode,
			"idCard" => $jsonBack->idCard,
			"pid" => $jsonBack->pid,
			"balance" => $jsonBack->balance,
			"idNo" => $jsonBack->idNo,
			"expireDate" => $jsonBack->expireDate,
		);

		$data = "";
		foreach($PostArray as $key => $v)
		{
			$data .= $key."=".$v."&";
		}
		$data = trim($data, "&");

		// 發出登錄請求，
		$url = "http://113.106.49.136:860/bs/system_operator_Signon.htm";
		$html = curl::encoding(curl::Post($url, $cookies, $data), "gbk");

		// 判斷驗證碼是否解析有誤
		if(stadium::isCaptcha($html))
		{
			if($times-- > 0) goto CAPTCHA; // 返回重新取得驗證碼
			$error = "captcha read failed.";
			return "";
		}
		
		// 判斷是否登錄成功
		if(!stadium::isLogin($html))
		{
			$error = "login failed.";
			return "";
		}
		
		return $cookies;
	}
	
	static function Order($cookies, $date, $time, $number, &$error)
	{
		$error = "";
		// pdisabledFlag=false&action=2&timeval=09%3A30-10%3A30&reqPlace_no=229&place_nos=229&place_name=&guest_num=1&is_all=N
		$url = "http://113.106.49.136:860/bs/biz_preconcert_IncludePreconcertForm.htm?place_no=".$number."&timeval=".urlencode($time)."&querydate=".urlencode($date)."&nousexxxxx=nousexxxx&dialogTitle=";
		$data = "pdisabledFlag=false&action=2&timeval=".urlencode($time)."&reqPlace_no=".$number."&place_nos=".$number."&place_name=&guest_num=1&is_all=N";
		$html = curl::encoding(curl::Post($url, $cookies, $data), "gbk");
		
		$regex = '/<td align="left" class="error-cue">(.*?)<\/td>/';
		if(preg_match_all($regex, $html, $matches))
		{
			$error = $matches[1][0];
			return false;
		}
		
		if(stadium::isSystemError($html))
		{
			$error = "参数不合法。";
			return false;
		}
	
		return true;
	}
	
	static function Query($cookies, $type, $date, $time, &$error)
	{
		$error = "";
		$url = "http://113.106.49.136:860/bs/biz_preconcert_PreconcertPlaceTimeView.htm";
		$regex = '/<input type="checkbox" name="timeval" value="([\d:-]+)"( disabled="true"|)\/>/';
		if($time != "") $regex = '/<input type="checkbox" name="timeval" value="('.$time.')"( disabled="true"|)\/>/';
		
		$numberList = stadium::$numbers[$type];
		$json = '{"list":{';
		foreach($numberList as $k => $v)
		{
			$data = "place_no=".$v."&currentDateStr=".$date;
			$html = curl::Post($url, $cookies, $data);
			if(stadium::isSystemError($html))
			{
				$error = "参数不合法。";
				return false;
			}
			if(preg_match_all($regex, $html, $matches))
			{		
				$json .= '"'.$v.'":{';
				for($i = 0; $i < count($matches[1]); $i++)
				{
					$json .= '"'.$matches[1][$i].'":"'.($matches[2][$i] == '' ? 'on' : 'off').'",';
				}
				$json = trim($json, ",") . "},";
			}
		}
		$json = trim($json, ",") . "}}";
		
		return $json;
	}

	private static function isSystemError($Data)
	{
		$rightStr = "系统连接异常";
		return strpos($Data, $rightStr);
	}

	private static function isCaptcha($Data)
	{
		$rightStr = "*错误：验证码";
		return strpos($Data, $rightStr);
	}
	
	private static function isLogin($Data)
	{
		$rightStr = "*错误：null";
		return strpos($Data, $rightStr);
	}

}
