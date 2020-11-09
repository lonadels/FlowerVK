<?php
namespace app\modules;

use app;

class VKUser 
{
    private $user;
    
    function __construct($api, $userId, $name_case='nom', $data=null){
        $params = new MethodParams();
        $params->user_ids = $userId;
        $params->name_case = $name_case;
        $params->fields = 'photo_id, verified, sex, bdate, city, country, home_town, has_photo, photo_50, photo_100, photo_200_orig, photo_200, photo_400_orig, photo_max, photo_max_orig, online, domain, has_mobile, contacts, site, education, universities, schools, status, last_seen, followers_count, common_count, occupation, nickname, relatives, relation, personal, connections, exports, wall_comments, activities, interests, music, movies, tv, books, games, about, quotes, can_post, can_see_all_posts, can_see_audio, can_write_private_message, can_send_friend_request, is_favorite, is_hidden_from_feed, timezone, screen_name, maiden_name, crop_photo, is_friend, friend_status, career, military, blacklisted, blacklisted_by_me';
        if( ! isset($data) )
            $this->user = $api->method(['users','get'], $params)->response[0];
        else
            $this->user = $data;
    }
    function __get($v){        
        return isset($this->user->{$v}) ? $this->user->{$v} : null;
    }
}