<?php
namespace app\forms;

use std, gui, framework, app;


class update extends AbstractForm
{

    /**
     * @event browser.load 
     */
    function doBrowserLoad(UXEvent $e = null)
    {    
        $this->browser->show();
    }

}
