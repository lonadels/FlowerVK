<?php
namespace app\modules;

use app;
use std;

class Request 
{    
    public $url;
    public $result;
    
    function __construct( $url ){
        $this->url = $url;
    }
    
    function build( Params $args ){     
        foreach( $args->toArray() as $arg => $value )
            $url .= (empty($url) ? "" : "&") . urlencode($arg) . '=' . urlencode($value);
            
        $this->url .= $url;

        return $this;    
    }
    
    public function query(){
        $this->result = json_decode( Stream::getContents($this->url) );
        return $this;
    }
    
    public function get(){
        return $this->result;
    }
}