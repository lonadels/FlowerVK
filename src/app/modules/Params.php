<?php
namespace app\modules;

abstract class Params 
{
    private $data;
    
    function __construct(array $data=[]){
        $this->data = $data;
    }
    
    function __set($p, $v){
        $this->data[$p] = $v;
        return $this;
    }
    
    function __get($p){
        return $this->data[$p];
    }
    
    function add(Array $arr){
        $this->data = array_merge($this->data, $arr);
    }
    
    function toArray(){
        return $this->data;
    }
}
