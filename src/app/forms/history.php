<?php
namespace app\forms;

use std, gui, framework, app;


class history extends AbstractForm
{

    /**
     * @event hbox21.click-Left 
     */
    function doHbox21ClickLeft(UXMouseEvent $e = null)
    {
        $this->mainModule->showMain();
    }

    /**
     * @event vbox.scroll 
     */
    function doVboxScroll(UXScrollEvent $e = null)
    {    
        $this->mainModule->scroll($this->container, $e);
    }

    /**
     * @event button.click-Left 
     */
    function doButtonClickLeft(UXMouseEvent $e = null)
    {    
        $this->mainModule->clearHistory();
    }

}
