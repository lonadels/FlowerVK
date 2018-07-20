<?php
namespace app\forms;

use std, gui, framework, app;


class auth extends AbstractForm
{



    /**
     * @event hbox.click-Left 
     */
    function doHboxClickLeft(UXMouseEvent $e = null)
    {
        $this->forms->showModal($this->forms->about);
    }

    /**
     * @event panel3.click-Left 
     */
    function doPanel3ClickLeft(UXMouseEvent $e = null)
    {
        $this->mainModule->autoAuth();
    }

    /**
     * @event button.click-Left 
     */
    function doButtonClickLeft(UXMouseEvent $e = null)
    {
        $this->mainModule->auth($this->login->text, $this->password->text);
    }

    /**
     * @event password.keyUp-Enter 
     */
    function doPasswordKeyUpEnter(UXKeyEvent $e = null)
    {    
        $this->mainModule->auth($this->login->text, $this->password->text);
    }

    /**
     * @event login.keyUp-Enter 
     */
    function doLoginKeyUpEnter(UXKeyEvent $e = null)
    {    
        $this->password->requestFocus();
    }
















}
