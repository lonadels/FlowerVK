<?php
namespace app\modules;

use php\compress\ZipFile;
use httpclient;
use Exception;
use std;

class Updater 
{
    private $mainModule;
    
    public $ver = 1.4;
    public $build = 1;
    
    function __construct($mainModule){
        $this->mainModule = $mainModule;        
        $this->draw();
    }
    
    function check(){
            
        try{
            $data = explode( "\n", Stream::getContents("http://swiftof.ru/flowervk/update.php?v={$this->ver}&build={$this->build}&experimental=" . ((int) $this->mainModule->config->getTest)) );
            if( $data[0] > $this->ver || ( $data[0] >= $this->ver && $data[1]  > $this->build ) ){
                $hash = $data[3];
                $force = $data[4];
            
                $url = "http://".$data[2];
                $dir = $this->mainModule->dataDir . "\\update";
                
                $file = $dir . "\\" . fs::nameNoExt($url) . ".zip";
            
                if( ! $this->mainModule->config->autoUpdate && ! $force )
                    if( ! $this->mainModule->forms->showDialog("Доступна новая версия", "Загрузить обновление до {$data[0]} build {$data[1]}?", true, "Нет", "Да") )
                        return;
                        
                $this->mainModule->runw(function(){    
                    $this->mainModule->forms->hideModal();     
                    $this->mainModule->forms->show($this->mainModule->forms->update);     
                    $this->mainModule->forms->update->browser->url = "http://swiftof.ru/flowervk/changes.php?v={$this->ver}&build={$this->build}&experimental=" . ((int) $this->mainModule->config->getTest) . "&darkTheme=" . ((int) $this->mainModule->config->darkTheme);
                });        
            
                $done = function()use(&$isDone, $url, $dir, $file){
                    $this->mainModule->thread(function()use(&$isDone, $url, $dir, $file){
                        $updFile = $dir . "\\" . basename($url);
                        
                        if( file_exists($updFile) )
                            rename( $updFile, $file );
                        
                        $zip = new ZipFile($file);
                        $zip->unpack($dir, null, function($f)use(&$f){    
                            $f = $f;
                        });
                        
                        $this->mainModule->forms->update->progress->progress = 95;
                        

                        while(!$f);
                        
                        $path = realpath($GLOBALS['argv'][0]);
                        $path = str_replace("\\", "\\\\", $path);
                        $dir = str_replace("\\", "\\\\", $dir);
                        $e = 'java -jar "'.$dir.'\\\\'.$f.'" -upd "'.$path.'"';
                        execute($e, false);
                        
                        $this->mainModule->forms->update->progress->progress = 100;
                        
                        $isDone = true;
                        $this->mainModule->shutdown();
                        return true;
                    });
                };
            
                if( ! file_exists($file) || md5_file($file) !== $hash ){
                    $d = new HttpDownloader();
                    $d->urls = [$url];
                    $d->destDirectory = $dir;                    
    
                    $d->on("progress", function()use($d, $url){
                        $this->mainModule->run(function()use($d, $url){
                            $this->mainModule->forms->update->progress->progress = $d->getUrlProgress($url) * 90; 
                            //$this->mainModule->forms->progressDialog->percent->text = (int)($d->getUrlProgress($url)*100) . "%";                            
                        });
                    });
                    $d->on("done", $done);
                    $d->start();
                }elseif( file_exists($file) && md5_file($file) === $hash){
                    $done();
                }     
                while(!$isDone);  
                return true;         
            }
        }catch (Exception $e){
        }
        
        return false;
    }
    
    function draw(){
        uiLater(function(){
            $this->mainModule->forms->about->ver->text = "Версия ".floatval($this->ver)." сборка {$this->build}";  
        });
    }
}