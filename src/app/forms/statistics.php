<?php
namespace app\forms;

use std, gui, framework, app;


class statistics extends AbstractForm
{

    /**
     * @event hbox5.click-Left 
     */
    function doHbox5ClickLeft(UXMouseEvent $e = null)
    {    
        $this->mainModule->showMain();
    }

    /**
     * @event hboxAlt.scroll 
     */
    function doHboxAltScroll(UXScrollEvent $e = null)
    {    

        if($e->deltaY<0)
            $this->containerAlt->scrollX += (50*100/$this->containerAlt->content->width)/100;
        else
            $this->containerAlt->scrollX -= (50*100/$this->containerAlt->content->width)/100;
            
        if( $this->containerAlt->scrollX > 0 && $this->containerAlt->scrollX < 1 )    
            $e->consume();   
            
        $this->mainModule->scroll($this->containerAlt, $e, true);    
    }

    /**
     * @event vboxAlt.scroll 
     */
    function doVboxAltScroll(UXScrollEvent $e = null)
    {    
        $this->mainModule->scroll($this->container, $e);
    }


}
