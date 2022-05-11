<?php
/**
 * Notifcaster.com — sending and recieving notifcations using Telegram bot api.
 * @author Ameer Mousavi <ameer.ir>
 * forked from Notifygram by Anton Ilzheev <ilzheev@gmail.com>
 * Attention! $method always must be started with slash " / "
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class Notifcaster_Class
{
    protected
        $api_token  = null,
        $url        = 'https://tg-notifcaster.rhcloud.com/api/v1',
        $api_method = null,
        $excluded_tags = null,
        $parse_mode = null,
        $web_preview = 0;
  
    public function Notifcaster($api_token, $url = 'https://tg-notifcaster.rhcloud.com/api/v1')
    {
        $this->api_token = $api_token;
        $this->url = $url;
    }
 
    public function _telegram($bot_token, $parse_mode = null, $web_preview = 0)
    {
        $this->url = 'https://api.telegram.org/bot'.$bot_token;
        $this->parse_mode = $parse_mode;
        $this->web_preview = $web_preview;
        if(strtolower($parse_mode) == "html"){
            $this->excluded_tags = "<b><strong><em><i><a><code><pre>";
        }
    }
 
    public function notify($msg = 'NULL')
    {
        $params = array(
            'api_token'  => $this->api_token,
            'msg'        => $msg
        );
        $this->api_method = "/selfMessage";
        $response = $this->make_request($params);
        return $response;
    }

    public function get_bot()
    {
        $params = array();
        $this->api_method = "/getMe";
        $response = $this->make_request($params);
        return $response;
    }
  
    public function channel_text($chat_id , $msg)
    {
        if(strtolower($this->parse_mode) == "markdown"){
            $msg = $this->markdown($msg, 1, 1, 1);
        }
        $params = array(
            'chat_id'  => $chat_id,
            'text'        => strip_tags($msg, $this->excluded_tags),
            'parse_mode' => $this->parse_mode,
            'disable_web_page_preview' => $this->web_preview
        );

        $this->api_method = "/sendMessage";
        $response = $this->make_request($params);
        return $response;
    }
  
    public function channel_photo($chat_id , $caption , $photo)
    {
        $params = array(
            'chat_id'  => $chat_id,
            'caption'  => $caption,
            'photo'    => $photo
        );
        $this->api_method = "/sendPhoto";
        $file_upload = true;
        $response = $this->make_request($params, $file_upload);
        return $response;
    }

    protected function make_request(array $params = array(), $file_upload = false)
    {
        $default_params = $params;
        if (!empty($params)) {
            if (isset($params['caption'])) {
                if (mb_strlen($params['caption']) > 140){
                  $splitted_text = $this->str_split_unicode($params['caption'], 4096);
                  $params['caption'] = '';  
                }
            }
            if (isset($params['text'])) {
                if(mb_strlen($params['text']) > 4096){
                  $splitted_text = $this->str_split_unicode($params['text'], 4096);  
                }
            }
        }
        if (function_exists('curl_init')) {
            $curl = curl_init($this->url.$this->api_method);
            if (PHP_MAJOR_VERSION >= 5 && PHP_MINOR_VERSION >= 5){
                curl_setopt($curl, CURLOPT_SAFE_UPLOAD, true);
            }
            if ($file_upload) {
                if (class_exists('CURLFile')) {
                    $params['photo'] = new CURLFile($params['photo']);
                } else {
                    $params = $this->curl_custom_postfields($curl, array('chat_id'  => $params['chat_id'], 'caption' => $params['caption']), array('photo' => $params['photo']));
                }
            } else {
                $params = http_build_query($params);
            }
            curl_setopt_array($curl, array(
                CURLOPT_SSL_VERIFYPEER => 0,
                CURLOPT_SSL_VERIFYHOST => 0,
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_POST => 1,
                CURLOPT_POSTFIELDS => $params
            ));
            $response = curl_exec($curl);
            // if text
            if (isset($splitted_text)) {
                foreach ($splitted_text as $text_part) {
                    $params = array(
                        'chat_id'  => $default_params['chat_id'],
                        'text'     => $text_part,
                        'parse_mode' => $this->parse_mode
                        );
                    $params = http_build_query($params);
                    curl_setopt_array($curl, array(
                        CURLOPT_URL => $this->url."/sendMessage",
                        CURLOPT_POSTFIELDS => $params
                        ));
                    $response = curl_exec($curl);
                }
            }
            curl_close($curl);                     
        } else {
            $context = stream_context_create(array(
                'http' => array(
                    'method' => 'POST',
                    'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                    'content' => $params,
                    'timeout' => 10,
                ),
            ));
            $response = file_get_contents($this->url.$this->api_method, false, $context);
        }
        return $this->response = json_decode($response, true);
    }
    
    
    protected function curl_custom_postfields($ch, array $assoc = array(), array $files = array()) {


        static $disallow = array("\0", "\"", "\r", "\n");
        $body = array();
        foreach ($assoc as $k => $v) {
            $k = str_replace($disallow, "_", $k);
            $body[] = implode("\r\n", array(
                "Content-Disposition: form-data; name=\"{$k}\"",
                "",
                filter_var($v), 
                ));
        }

        foreach ($files as $k => $v) {
            switch (true) {
                case false === $v = realpath(filter_var($v)):
                case !is_file($v):
                case !is_readable($v):
                continue;
            }
            $data = file_get_contents($v);
            $v = call_user_func("end", explode(DIRECTORY_SEPARATOR, $v));
            list($k, $v) = str_replace($disallow, "_", array($k, $v));
            $body[] = implode("\r\n", array(
                "Content-Disposition: form-data; name=\"{$k}\"; filename=\"{$v}\"",
                "Content-Type: application/octet-stream",
                "",
                $data,
                ));
        }


        do {
            $boundary = "---------------------" . md5(mt_rand() . microtime());
        } while (preg_grep("/{$boundary}/", $body));


        foreach ($body as &$part) {
			$part = "--{$boundary}\r\n{$part}"; unset($part);
		}


        $body[] = "--{$boundary}--";
        $body[] = "";


        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                "Expect: 100-continue",
                "Content-Type: multipart/form-data; boundary={$boundary}",
                )
            );
        return implode("\r\n", $body);
    }

    public function markdown ($html, $b = 0, $i = 0, $u = 0) {
        $allowed_tags = "";
        $re = array();
        $subst = array();
        if ($b){
            $allowed_tags .= "<strong>";
            array_push($re, "/<strong>(.+?)<\\/strong>/is");
            array_push($subst, "*$1*");

        }
        if ($i){
            $allowed_tags .= "<em>";
            array_push($re, "/<em>(.+?)<\\/em>/is");
            array_push($subst, "_$1_");
        }
        if ($u){
            $allowed_tags .= "<a>";
            array_push($re, "/<a\\s+(?:[^>]*?\\s+)?href=[\"']?([^'\"]*)[\"']?.*?>(.*?)<\\/a>/is");
            array_push($subst, "[$2]($1)");
        }
        strip_tags($html, $allowed_tags);
        $result = preg_replace($re, $subst, $html);
        return $result;
    }

    function str_split_unicode($str, $l = 0) {
        if ($l > 0) {
            $ret = array();
            $len = mb_strlen($str, "UTF-8");
            for ($i = 0; $i < $len; $i += $l) {
                $ret[] = mb_substr($str, $i, $l, "UTF-8");
            }
            return $ret;
        }
        return preg_split("//u", $str, -1, PREG_SPLIT_NO_EMPTY);
    }
}
?>
