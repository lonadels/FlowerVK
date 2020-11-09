<?php
namespace app\forms;

use std, gui, framework, app;


class autoAuth extends AbstractForm
{
   
    public $closable = true;
    
    public function doModalClose(){
        $this->esc->opacity = 0;
        $this->forms->auth->panel3->managed = $this->forms->auth->panel3->visible = True;
    }
    
    /**
     * @event show 
     */
    function doShow(UXWindowEvent $e = null)
    {    
        $this->esc->opacity = 0;
    
        $fadeAnim = new FadeAnimationBehaviour();
        $fadeAnim->opacity = 0.35;
        $fadeAnim->duration = 100;
        $fadeAnim->delay = 250;
        $fadeAnim->apply($this->esc);
    }

}
