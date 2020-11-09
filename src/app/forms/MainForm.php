<?php
namespace app\forms;

use std, gui, framework, app;


class MainForm extends AbstractForm
{
    
    public $mainModule;
    public $curPos;

    /**
     * @event panel.mouseMove 
     */
    function doPanelMouseMove(UXMouseEvent $e = null)
    {    
        /*
        $this->curPos = $e->position;
    
        $w = $e->sender->width;
        $h = $e->sender->height;

        $x = $e->x;
        $y = $e->y;
        
        $offset = 5;
        
        $left = ( $x < $offset );
        $right = ( $x > $w - $offset );
        $top = ( $y < $offset );
        $bottom = ( $y > $h - $offset );
        
        if( $left && $top )
            $e->sender->style = '-fx-cursor: nw-resize';
        elseif( $right && $top )    
            $e->sender->style = '-fx-cursor: ne-resize';
        elseif( $left && $bottom )  
            $e->sender->style = '-fx-cursor: sw-resize';
        elseif( $right && $bottom )  
            $e->sender->style = '-fx-cursor: se-resize';
        elseif( $left || $right )  
            $e->sender->style = '-fx-cursor: w-resize'; 
        elseif( $bottom || $top )  
            $e->sender->style = '-fx-cursor: s-resize';    
        else
            $e->sender->style = '-fx-cursor: default';  
        */ 
    }

    /**
     * @event panel.mouseDrag 
     */
    function doPanelMouseDrag(UXMouseEvent $e = null)
    {    
        /*
    
        $w = $e->sender->width;
        $h = $e->sender->height;

        $x = $e->x;
        $y = $e->y;
        
        $sx = $e->screenX - $this->panel->x;
        $sy = $e->screenY - $this->panel->y;
        
        $offset = 5;
        
        $left = ( $x < $offset );
        $right = ( $x > $w - $offset );
        $top = ( $y < $offset );
        $bottom = ( $y > $h - $offset );
        
        if( $left && $top ){
            $this->x = $sx;
            $this->y = $sy;
        }elseif( $right && $top ){    
            $this->x = $sx;
            $this->y = $sy;
        }elseif( $left && $bottom ){  
            $this->x = $sx;
            $this->y = $sy;
        }elseif( $right && $bottom ){  
            $this->x = $sx;
            $this->y = $sy;
        }elseif( $left || $right ){  
            $this->x = $sx;
        }elseif( $bottom || $top ){  
            $this->y = $sy;
        }
        */
    }


    /**
     * @event keyDown-Esc 
     */
    function doKeyDownEsc(UXKeyEvent $e = null)
    {    
        if( isset( $this->forms->currentModal ) && $this->forms->currentModal->closable ){
            if( method_exists ( $this->forms->currentModal, 'doModalClose' ) )
                call_user_func([$this->forms->currentModal, 'doModalClose']);
                
            if( ! $this->forms->currentModal->noHide )    
                $this->forms->hideModal();
        }
    }


    /**
     * @event close.click-Left 
     */
    function doCloseClickLeft(UXMouseEvent $e = null)
    {
        $this->mainModule->shutdown();        
    }

    /**
     * @event min.click-Left 
     */
    function doMinClickLeft(UXMouseEvent $e = null)
    {
        $this->iconified = true; 
    }



}
