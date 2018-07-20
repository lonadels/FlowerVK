<?php
namespace app\modules;

use std;
use app;

class AdditionFunctions 
{
    public $mainModule;
    public $forms;
    public $vk;
    
    function __construct($mainModule){
        $this->mainModule = $mainModule;
        $this->forms = $mainModule->forms;
        $this->vk = $mainModule->vk;
    }

    function clearWall($progressBox, $startBox){
        $progressName = __FUNCTION__;
    
        return $this->mainModule->thread(function()use($progressName, $progressBox, $startBox){
                   
            $this->mainModule->run( function()use($progressName, $progressBox, $startBox){
            
                $progressBox->managed = $progressBox->visible = true;
                $startBox->managed = $startBox->visible = false;
                
                $progressBox->progress->progress = 0;
                $progressBox->cancel->progress = $progressName;

            });
            
            $r = Regex::of("(.*)_test")->with($progressName);
            $func = $r->find() ? $r->group(1) : $progressName;
            
            foreach ( $this->mainModule->additionFunctionsList[$func][2] as $setting => $data ){
                $s[$setting] = $data[2];
            }
        
            $this->mainModule->parseWall(null, function($item, $count, $i)use($s, $progressName, $progressBox, &$deleted){
                static $perSetting;    
            
                if( ! isset($this->mainModule->progress[$progressName]) ) return;
    
                if( $s['onlyRepost'] && empty($item->copy_history) ) return;
                if( $s['onlyOthers'] && $item->from_id == $this->vk->user->id  ) return;
                
                if( $s['minLikes'] && $item->likes->count < $s['minLikes'] ) return;
                if( $s['minComments'] && $item->comments->count < $s['minComments'] ) return;
                if( $s['minReposts'] && $item->reposts->count < $s['minReposts'] ) return;
                
                //if( $s['minDate'] && $item->reposts->date * 1000 < $s['minDate'] ) return;
                //if( $s['maxDate'] && $item->reposts->date * 1000 > $s['maxDate'] ) return;
    
                print "{$item->id}\n";
                $this->vk->method( ["wall", "delete"], new MethodParams(["post_id"=>$item->id]) );
                
                $this->mainModule->database->query( "INSERT INTO story (owner, id, type, time) VALUES (?, ?, ?, ?)", [$this->vk->user->id, $item->id, 'post', time()] )->update();
                
                if( ! $perSetting ){         
                    $perSetting = 1;
                    $this->mainModule->run( function()use($percent, $i, $count, $progressBox, &$perSetting){
                        $percent = round( $i * 100 / $count );  
                        $progressBox->percent->text = "{$percent}%";
                        $progressBox->progress->progress = $percent;
                        $perSetting = 0;
                    });
                }
                
                $deleted++;
            }, null);
            
            $this->mainModule->run( function()use($progressBox, $startBox){
                $progressBox->managed = $progressBox->visible = false;
                $startBox->managed = $startBox->visible = true;
            });
        });
        
    }
    
    function clearComments_test($progressBox, $startBox){
        $progressName = __FUNCTION__;
    
        return $this->mainModule->thread(function()use($progressName, $progressBox, $startBox){
                   
            $this->mainModule->run( function()use($progressName, $progressBox, $startBox){
            
                $progressBox->managed = $progressBox->visible = true;
                $startBox->managed = $startBox->visible = false;
                
                $progressBox->progress->progress = 0;
                $progressBox->cancel->progress = $progressName;

            });
            
            $r = Regex::of("(.*)_test")->with($progressName);
            $func = $r->find() ? $r->group(1) : $progressName;
            
            foreach ( $this->mainModule->additionFunctionsList[$func][2] as $setting => $data ){
                $s[$setting] = $data[2];
            }
        
            $this->mainModule->parseWall(null, function($item, $count, $i)use($s, $progressName, $progressBox, &$deleted){
                if( ! isset($this->mainModule->progress[$progressName]) ) return;
                $this->mainModule->parseComments($item->id, null, function($item, $count, $i)use($s, $progressName, $progressBox, &$deleted){
                    static $perSetting; 
                
                    if( ! isset($this->mainModule->progress[$progressName]) ) return;
        
                    if( $s['onlyOthers'] && $item->from_id == $this->vk->user->id  ) return;
                    if( $s['minLikes'] && $item->likes->count < $s['minLikes'] ) return;
                    
                    //if( $s['minDate'] && $item->reposts->date * 1000 < $s['minDate'] ) return;
                    //if( $s['maxDate'] && $item->reposts->date * 1000 > $s['maxDate'] ) return;
        
                    print "{$item->id}\n";
                    $this->vk->method( ["wall", "deleteComment"], new MethodParams(["comment_id"=>$item->id]) );
                    
                    $this->mainModule->database->query( "INSERT INTO story (owner, id, type, time) VALUES (?, ?, ?, ?)", [$this->vk->user->id, $item->id, 'comment', time()] )->update();
                    
                    $deleted++;
                }, null);
                if( ! $perSetting ){         
                    $perSetting = 1;
                    $this->mainModule->run( function()use($percent, $i, $count, $progressBox, &$perSetting){
                        $percent = round( $i * 100 / $count );  
                        $progressBox->percent->text = "{$percent}%";
                        $progressBox->progress->progress = $percent;
                        $perSetting = 0;
                    });
                }
            }, null);
            
            $this->mainModule->run( function()use($progressBox, $startBox){
                $progressBox->managed = $progressBox->visible = false;
                $startBox->managed = $startBox->visible = true;
            });
        });
        
    }

}