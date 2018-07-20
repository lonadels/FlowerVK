<?php
namespace app\forms;

use std, gui, framework, app;


class error extends AbstractForm
{

    /**
     * @event buttonAlt.click-Left 
     */
    function doButtonAltClickLeft(UXMouseEvent $e = null)
    {    
        $this->mainModule->shutdown();
    }

    /**
     * @event button.click-Left 
     */
    function doButtonClickLeft(UXMouseEvent $e = null)
    {
        new Thread(function(){
            $this->forms->showDialog("Журнал отладки", "Данная функция в разработке", false);
        })->start();
    }

}
