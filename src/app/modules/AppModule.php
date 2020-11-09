<?php
namespace app\modules;

use Exception;
use std, gui, framework, app;

class AppModule extends AbstractModule
{
    function __construct(){                
        new Thread(function(){        
            while( ! Application::isCreated() );     

            if( $key = array_search($GLOBALS['argv'], '-upd', false) ){    
                
                uiLater(function(){
                    app()->form("loader")->show();
                    app()->form("loader")->alwaysOnTop = false;
                });
                
                if( is_file($GLOBALS['argv'][$key+1]) ){
                    try{
                        $path = realpath($GLOBALS['argv'][0]); 
                        
                        if( ! is_writable($GLOBALS['argv'][$key+1]) ) throw new Exception("No writeable");
                        
                        fs::delete($GLOBALS['argv'][$key+1]);
                        fs::copy($path, fs::pathNoExt($GLOBALS['argv'][$key+1]).".".fs::ext($path));
                            
                    }catch(Exception $e){
                        uiLater(function(){
                            app()->form("loader")->description->text = "Ошибка";
                        });
                        sleep(6);
                        return App::shutdown();
                    }    
                    $path = str_replace("\\", "\\\\", $path);
                    execute('java -jar "'.fs::pathNoExt($GLOBALS['argv'][$key+1]).".".fs::ext($path).'"');
                }else{
                    uiLater(function(){
                        app()->form("loader")->description->text = "Ошибка";
                    });
                    sleep(3);
                }
                
                return App::shutdown();
            }else{
                uiLater(function(){
                    app()->form("splashscreen")->show();
                    app()->form("splashscreen")->alwaysOnTop = false;
                });
            }
             
            try {
                new MainModule();
            } catch (Exception $e) {
                uiLaterAndWait(function(){
                    //alert('Произошла ошибка - ' . $e->getMessage());
                    app()->form("error")->buttonAlt->on("click", function(){
                        app::shutdown();
                    });
                    app()->form("error")->showInFragment(app()->form("MainForm")->fragment);
                    app()->form("MainForm")->show();
                });
            }
            
        })->start();
    }
}
