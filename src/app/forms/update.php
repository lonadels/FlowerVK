<?php
namespace app\forms;

use std, gui, framework, app;


class update extends AbstractForm
{

    /**
     * @event browser.load 
     */
    function doBrowserLoad(UXEvent $e = null){
        $this->hbox->add($this->browser);
        $this->browser->style = "-fx-effect: dropshadow(three-pass-box, rgba(0,0,0,0.09), 25, 0, 0, 0);";
        $this->browser->show();
    }

}
