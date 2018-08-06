<?php
namespace app\modules;

use jurl;
use app;
use std;
use bundle\jurl\jURL;

class Request 
{    
    public $url;
    public $result;
    //public $args;
    
    function __construct( $url ){
        $this->url = $url;
    }
    
    function build( Params $args ){  
        //return $this->args = $args->toArray();
       
        foreach( $args->toArray() as $arg => $value )
            $url .= (empty($url) ? "" : "&") . urlencode($arg) . '=' . urlencode($value);
            
        $this->url .= $url;

        return $this;    
    }
    
    public function query(){
        
        $ch = new jURL( $this->url );
        //$ch->setPostData( $this->args );
        $result = $ch->exec();
        
        var_dump($result);
        $this->result = json_decode( $result );
        
        return $this;
    }
    
    public function get(){
        return $this->result;
    }
}