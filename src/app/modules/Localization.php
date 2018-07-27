<?php
namespace app\modules;

use std;

class Localization
{
    /**
     * @var Scanner
     */
    private $scanner;
	
    public function __construct($path)
    {
        $this->scanner = new Scanner(Stream::of($path . '.lang'));
    }
	
    /**
     * @param string $object
     * @return string
     */
    public function get($object)
    {
        $lines = [];
        
        while ($this->scanner->hasNextLine()) {
            $line = $this->scanner->nextLine();
            $split = str::split($line, '=', 2);
            
            if (count($split) > 1) {
                $lines[str::trim($split[0])] = str::trim($split[1]);
            }
        }
        
        return $lines[$object];
    }
}