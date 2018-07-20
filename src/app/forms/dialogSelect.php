<?php
namespace app\forms;

use std, gui, framework, app;


class dialogSelect extends AbstractForm
{
   
    public $closable = true;
    
    public function doModalClose(){
        $this->esc->opacity = 0;
        $this->mainModule->cancelDialogSelect = true;
    }
    
    /**
     * @event show 
     */
    function doShow(UXWindowEvent $e = null)
    {    
        $this->esc->opacity = 0;
    
        $fadeAnim = new FadeAnimationBehaviour();
        $fadeAnim->opacity = 1;
        $fadeAnim->duration = 100;
        $fadeAnim->delay = 250;
        $fadeAnim->apply($this->esc);
        
        $fadeAnim = new FadeAnimationBehaviour();
        $fadeAnim->opacity = 0;
        $fadeAnim->duration = 100;
        $fadeAnim->delay = 1250;
        $fadeAnim->apply($this->esc);
    }

    /**
     * @event edit.keyDown-Enter 
     */
    function doEditKeyDownEnter(UXKeyEvent $e = null)
    {    
        $this->mainModule->searchDialog($e);
    }

    /**
     * @event vbox.scroll 
     */
    function doVboxScroll(UXScrollEvent $e = null)
    {    
        $this->mainModule->scroll($this->container, $e);
    }

}
