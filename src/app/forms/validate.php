<?php
namespace app\forms;

use std, gui, framework, app;


class validate extends AbstractForm
{

    /**
     * @event ok.click-Left 
     */
    function doOkClickLeft(UXMouseEvent $e = null)
    {    
        $this->result = 1;
    }

    /**
     * @event sms.click-Left 
     */
    function doSmsClickLeft(UXMouseEvent $e = null)
    {    
        $this->result = 0;
    }

    /**
     * @event edit1.keyUp-Enter 
     */
    function doEdit1KeyUpEnter(UXKeyEvent $e = null)
    {    
        $this->result = 1;
    }

    /*
     * @event edit1.keyDown-Enter 
     */
    function doEdit1KeyDownEnter(UXKeyEvent $e = null)
    {    
        $this->result = 1;
    }




}
