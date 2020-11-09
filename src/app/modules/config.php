<?php
namespace app\modules;

class config 
{
    public $mainModule;
    
    public $db;
    public $ver = 2;
        
    public $data;
    public $oldData;
    
    function __construct($mainModule){
        $this->mainModule = $mainModule;
        $this->db = $mainModule->database;

        $res = $this->db->query( "SELECT * FROM `settings`" );

        foreach ($res as $data){
            $data = $data->toArray();   
            $this->data[ $data[ 'param' ] ] = $data[ 'value' ];
        }
        
        $this->oldData = $this->data;
        
        $defaultConfig = $this->getDefault();
    
        foreach( $defaultConfig as $option => $value )
            if( $this->$option === NULL || $this->configVer != $this->ver )
                $this->$option = $value;
        
        $this->save();
    }
    
    public function getDefault(){
        return [
            'autoAuth'=>true,
            'onlineNotify'=>false,
            'showHorizontalScrolls'=>true,
            'offAnimations'=>false,
            'checkUpdates'=>true,
            'autoUpdate'=>true,
            'getTest'=>false,
            'darkTheme'=>false,
            'testMode'=>false,
            'configVer'=>$this->ver,
        ];
    }

    public function __set( $p, $v ) {
        if( property_exists($this, $p) )
            $this->$p = $v;
        else
            $this->data[$p] = $v;
    }
    

    public function __get( $p ) {
        if( property_exists($this, $p) )
            return $this->$p;
        elseif( isset($this->data[$p]) )
            return $this->data[$p];
        else
            return null;
    }
    
    public function save() {
        return $this->mainModule->thread(function(){
            foreach( $this->data as $param => $value ) {
                if( isset( $this->oldData[ $param ] ) )
                    $this->db->query( "UPDATE `settings` SET `value` = ? WHERE `param` = ?;", [$value, $param] )->update();    
                else
                    $this->db->query( "INSERT INTO `settings` VALUES (?, ?); ", [$param, $value] )->update();             
            }            
    
            $this->oldData = $this->data;            
        });
    }
}