<?php
namespace app\modules;

use app;

class VKChat 
{
    private $chat;
    
    function __construct($api, $chatId, $data=null){
        $params = new MethodParams();
        $params->chat_ids = $chatId;
        $params->fields = 'nickname, screen_name, sex, bdate, city, country, timezone, photo_50, photo_100, photo_200_orig, has_mobile, contacts, education, online, counters, relation, last_seen, status, can_write_private_message, can_see_all_posts, can_post, universities';
        if( ! isset($data) )
            $this->chat = $api->method(['messages','getChat'], $params)->response[0];
        else
            $this->chat = $data;
  
    }
    function __get($v){       
        return isset($this->chat->{$v}) ? $this->chat->{$v} : null;
    }
}