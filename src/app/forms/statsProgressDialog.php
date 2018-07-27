<?php
namespace app\forms;

use std, gui, framework, app;


class statsProgressDialog extends AbstractForm
{
   
    public $closable = true;
    public $progressName;
    
    public function doModalClose(){
        $this->esc->opacity = 0;        
        unset( $this->mainModule->progress[$this->progressName] );
    }
    
    /**
     * @event show 
     */
    function doShow(UXWindowEvent $e = null)
    {    
        $this->esc->opacity = 0;
    
        $this->closable = ! empty($this->progressName);
        if(empty($this->progressName)) return;
    
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

}
