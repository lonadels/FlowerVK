<?php
namespace app\forms;

use std, gui, framework, app;


class stickers extends AbstractForm
{


    public $closable = true;
    public $noHide = false;

    /**
     * @event buttonOk.click-Left 
     */
    function doButtonOkClickLeft(UXMouseEvent $e = null)
    {
        if( ! $this->noHide )
        $this->forms->hideModal();
        $this->result = 1;
    }

    /**
     * @event tilePaneAlt.scroll 
     */
    function doTilePaneAltScroll(UXScrollEvent $e = null)
    {    
        $this->mainModule->scroll($this->container, $e);
    }
}
