<?php
namespace app\forms;

use std, gui, framework, app;


class funcSettings extends AbstractForm
{
   
    /**
     * @event buttonCancel.click-Left 
     */
    function doButtonCancelClickLeft(UXMouseEvent $e = null)
    {
        $this->forms->hideModal();
        $this->result = 0;
    }

    /**
     * @event buttonOk.click-Left 
     */
    function doButtonOkClickLeft(UXMouseEvent $e = null)
    {
        $this->forms->hideModal();
        $this->result = 1;
    }

    /**
     * @event vbox.scroll 
     */
    function doVboxScroll(UXScrollEvent $e = null)
    {    
        $this->mainModule->scroll($this->container, $e);
    }

}
