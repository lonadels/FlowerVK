<?php
namespace app\modules;

use app;

class VKGroup 
{
    private $group;
    
    function __construct($api, $groupId, $data=null){
        $params = new MethodParams();
        $params->group_ids = $groupId;
        $params->fields = 'city, country, place, description, wiki_page, market, members_count, counters, start_date, finish_date, can_post, can_see_all_posts, activity, status, contacts, links, fixed_post, verified, site, ban_info, cover';
        if( ! isset($data) )
            $this->group = $api->method(['groups','getById'], $params)->response[0];
        else
            $this->group = $data;
    }
    function __get($v){        
        return isset($this->group->{$v}) ? $this->group->{$v} : null;
    }
}