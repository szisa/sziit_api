<?php
/**
 * 网络请求工具类 curl
 */

namespace api\lib;

class curl
{
    static function Cookies($Url, $Cookies, $PostData)
    {
        return curl::Curl($Url, $Cookies, true, $PostData);
    }

    static function Get($Url, $Cookies)
    {
        return curl::Curl($Url, $Cookies, false, "");
    }

    static function Post($Url, $Cookies, $PostData)
    {
        return curl::Curl($Url, $Cookies, false, $PostData);
    }

    static function Curl($Url, $Cookies, $bCreate, $PostData)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $Url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, $bCreate ? CURLOPT_COOKIEJAR : CURLOPT_COOKIEFILE, $Cookies);
        if("" != $PostData)
        {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $PostData);
        }
        $content = curl_exec($ch);
        curl_close($ch);
        return $content;
    }

    static function encoding($con, $srcEncode, $dstEncode = "UTF-8")
    {
        return iconv($srcEncode, $dstEncode, $con);
    }
}

?>