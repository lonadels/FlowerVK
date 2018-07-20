<?php
namespace app\forms;

use std, gui, framework, app;


class about extends AbstractForm
{

    public $closable = true;

    /**
     * @event buttonOk.click-Left 
     */
    function doButtonOkClickLeft(UXMouseEvent $e = null)
    {    
        $this->forms->hideModal();
    }

    /**
     * @event label.click-Left 
     */
    function doLabelClickLeft(UXMouseEvent $e = null)
    {
        browse("http://vk.com/swiftof");
    }






}
