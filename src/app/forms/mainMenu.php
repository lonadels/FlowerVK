<?php
namespace app\forms;

use std, gui, framework, app;


class mainMenu extends AbstractForm
{

    public $curPos;
    

    /**
     * @event image_load_settings_min.mouseEnter 
     */
    function doImage_load_settings_minMouseEnter(UXMouseEvent $e = null)
    {    
        
    }

    /**
     * @event image_load_settings_min.click-Left 
     */
    function doImage_load_settings_minClickLeft(UXMouseEvent $e = null)
    {    
        $this->forms->show($this->forms->settings);
        $this->mainModule->loadSettings();
        $this->mainModule->setDescription("настройки");
    }





    /**
     * @event container.mouseMove 
     */
    function doContainerMouseMove(UXMouseEvent $e = null)
    {
        $this->curPos = $e->position;
    }

    /**
     * @event vbox4.scroll 
     */
    function doVbox4Scroll(UXScrollEvent $e = null)
    {
        /*if( $e->deltaY < 0 ){       
            if($this->panel->y >= 0 - $this->panel->height){
                $this->panel->y -= 20;
                $e->consume();
            }
        }else{
            if($this->panel->y < 0){
                $this->panel->y += 20;
                $e->consume();
            } else    
                $this->panel->y = 0;
        }
        
        $this->containerAlt->y = $this->panel->y + $this->panel->height;
        $this->forms->MainForm->label->text = $this->containerAlt->height;
        
        $this->container->y = $this->panel->y + $this->panel->height + $this->containerAlt->height;
        $this->container->height = 472 - $this->container->y;        
        
        //if( $e->deltaY < 0 )
            //$this->mainModule->visman( $this->containerAlt, false);
        //else
            //$this->mainModule->visman( $this->containerAlt, true);
        */
        
        $this->mainModule->scroll($this->container, $e);
    }

    /**
     * @event hbox7.scroll 
     */
    function doHbox7Scroll(UXScrollEvent $e = null)
    {  
        if($e->deltaY<0)
            $this->containerAlt->scrollX += (50*100/$this->containerAlt->content->width)/100;
        else
            $this->containerAlt->scrollX -= (50*100/$this->containerAlt->content->width)/100;
        
        $e->consume();   
        $this->mainModule->scroll($this->containerAlt, $e, true);        
    }

    /**
     * @event container.scroll 
     */
    function doContainerScroll(UXScrollEvent $e = null)
    {    
        if ($e->deltaY != 0) $e->consume();
    }

}
