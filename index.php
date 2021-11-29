<?php

/**
 * @copyright 2021 châu chí quốc
 * @version 2.0
 */

namespace TSR;
header('Content-Type: application/json');
require_once(__DIR__.DIRECTORY_SEPARATOR."/function.php");

use Core\QUOCCMS;
use DOMDocument;
$quoc = new QUOCCMS;
$username = $quoc->setting('username_tsr');
$password = $quoc->setting('password_tsr');
class thesieure
{
    function __login__()
    {
        global $username;
        global $password;
        $dom = new DOMDocument();
        $curl = curl_init("https://thesieure.com/account/login");
        curl_setopt_array($curl, [
            CURLOPT_COOKIEJAR => 'cookie.txt',
            CURLOPT_COOKIEFILE => 'cookie.txt',
            CURLOPT_CONNECTTIMEOUT => 30,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_FOLLOWLOCATION => 1
        ]);
        $exit = curl_exec($curl);
        curl_close($curl);
        @$dom->loadHTML($exit);
        $_csrf_token = $dom->getElementsByTagName('input')[0]->getAttribute('value');
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://thesieure.com/account/login",
            CURLOPT_COOKIEJAR => "cookie.txt",
            CURLOPT_COOKIEFILE => "cookie.txt",
            CURLOPT_CONNECTTIMEOUT => 30,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_FOLLOWLOCATION => 1,
            CURLOPT_POST => 1,
            CURLOPT_POSTFIELDS => "phoneOrEmail=$username&password=$password&_token=" . $_csrf_token,
            CURLINFO_HEADER_OUT => true
        ));
        $exec = curl_exec($curl);
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        if ($dom->getElementsByTagName('title')->item(0)->textContent == "Thesieure.com - Hệ thống bán thẻ carot, thẻ game, thẻ cào điện thoại, nạp cước giá rẻ, đổi thẻ cào sang bangiftcode") {
            return json_encode(array("status" => "success", "msg" => "Login Thành công"), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        } else {
            return json_encode(array("status" => "danger", "msg" => "login Thất Bại"), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        }
    }
    function CHECK_LSGD()
    {
        global $quoc;
        $dom = new DOMDocument();
        $this->__login__();
        $ch = curl_init();
        curl_setopt_array($ch, array(
            CURLOPT_URL => "https://thesieure.com/wallet/transfer",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_COOKIEFILE => "cookie.txt",
            CURLOPT_COOKIEJAR => "cookie.txt"
        ));
        $exec = curl_exec($ch);
        curl_close($ch);
        @$dom->loadHTML($exec);
        $tables = $dom->getElementsByTagName('table');
        $table = $tables->item(2);
        $rows = $table->getElementsByTagName('tr');
        $i = 0;
        foreach ($rows as $row) {
            if ($i != 0) {
                $columns = $row->getElementsByTagName('td');
                $magd = $columns->item(0)->textContent;
                $sotien = trim(preg_replace('/[^0-9]/', '', $columns->item(1)->textContent));
                $nguoigui = trim($columns->item(2)->textContent);
                $ngaytao = trim($columns->item(3)->textContent);
                $tinhtrang = trim($columns->item(4)->textContent);
                $noidung = trim($columns->item(5)->textContent);
                $loai = trim($columns->item(1)->getElementsByTagName("b")->item(0)->getElementsByTagName("span")->item(0)->getAttribute('class'));
                if($loai == "text-success")
                {
                    $check = $quoc->number_sql("SELECT * FROM `history_thesieure` WHERE `magd` = '{$magd}'");
                    if($check < 1)
                    {
                        $explode = explode('_',$noidung);
                        if($explode[0] == 'NAPTIEN')
                        {
                            $quoc->congtien($sotien,$explode[1]);
                            $quoc->insert('history_thesieure',[
                                'magd' => $magd,
                                'sotien' => $sotien,
                                'nguoigui' => $nguoigui,
                                'ngaytao' => $ngaytao,
                                'status' => $tinhtrang,
                                'noidung' => $noidung,
                                'username' => $explode[1],
                            ]);
                        }
                    }
                }
            }
            $i += 1;
        }
    }
}
?>
