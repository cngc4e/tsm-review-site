<?php

final class DiscordMsgBuilder {
    public $content = null;
    public $embeds = [];
    
    public function setContent($c) {
        $this->content = $c;
        return $this;
    }
    
    public function addEmbed($e) {
        $this->embeds[] = $e;
        return $this;
    }
}

final class DiscordConn {
    private $url;
    private $username;
    private $avatar;

    public function __construct(string $url, string $username = null, string $avatar = null) {
        $this->url = $url;
        $this->username = $username ?? 'Botbot';
        $this->avatar = $avatar ?? '';
    }
    
    private function sendPost($fields) : void {
        $curl = curl_init();

        curl_setopt($curl, CURLOPT_TIMEOUT, 5);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);

        curl_setopt($curl, CURLOPT_URL, $this->url);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($fields));

        $result = json_decode(curl_exec($curl), true);

        if (curl_getinfo($curl, CURLINFO_HTTP_CODE) != 204) {
            error_log("Failed to send discord message: " . $result['message']);
            error_log("The messaged that failed to be sent are as follows:\n" . print_r($fields, true));
        }

        curl_close($curl);
    }

    public function DMBuilder() : DiscordMsgBuilder {
        return new DiscordMsgBuilder();
    }
    
    public function sendMessage(DiscordMsgBuilder $msg) : void {
        $pst = [
            'username' => $this->username,
            'avatar_url' => $this->avatar,
        ];
        
        if ($msg->content)
            $pst["content"] = $msg->content;
            
        if (count($msg->embeds) > 0)
            $pst["embeds"] = $msg->embeds;
            
        $this->sendPost($pst);
    }
}