<?php
namespace app\forms;

use std, gui, framework, app;


class photoViewer extends AbstractForm
{


    /**
     * @event buttonOk.click-Left 
     */
    function doButtonOkClickLeft(UXMouseEvent $e = null)
    {
        $this->forms->hideModal();
        $this->result = 1;
    }





}
