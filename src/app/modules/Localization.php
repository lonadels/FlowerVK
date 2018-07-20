<?php
namespace app\modules;

use ErrorException;
use Exception;
use app;

class Localization 
{
    public $lang = 'ru';
    private $messages;
    private $v;
    
    function __construct($lang){
        $this->setLang($lang);
    }
    function setLang($lang){
        $this->lang = in_array($lang,['ru']) ? $lang : "ru";
    }
    
    function __get($v){
        $const = "app\\modules\\lang_{$this->lang}::$v";
        return constant($const);
    }
}