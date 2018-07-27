<?php
namespace app\modules;

use std;

class Localization
{
    /**
     * @var String
     */
    private $path;
    
    public function __construct($path){
        $this->path = $path;       
    }
    
    /**
     * @param string $object
     * @return string
     */
    public function get($object){
        $scanner = new Scanner(Stream::of("res://langs/" . $this->path . '.lang'));
        $lines = [];
        
        while ($scanner->hasNextLine()) {
            $line = $scanner->nextLine();
            $split = str::split($line, '=', 2);
            
            if (count($split) > 1) {
                $lines[str::trim($split[0])] = str::trim($split[1]);
            }
        }
        
        return $lines[$object];
    }
}