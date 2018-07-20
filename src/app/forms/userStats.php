<?php
namespace app\forms;

use std, gui, framework, app;


class userStats extends AbstractForm
{


    public $closable = true;

    /**
     * @event buttonOk.click-Left 
     */
    function doButtonOkClickLeft(UXMouseEvent $e = null)
    {
        $this->forms->hideModal();
        $this->result = 1;
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
     * @event vboxAlt.scroll 
     */
    function doVboxAltScroll(UXScrollEvent $e = null)
    {    
        $this->mainModule->scroll($this->container, $e);
    }
}
