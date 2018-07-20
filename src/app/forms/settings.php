<?php
namespace app\forms;

use php\gui\charts\UXXYChartData;
use php\gui\charts\UXXYChartSeries;
use php\gui\charts\UXLineChart;
use php\gui\charts\UXNumberAxis;
use std, gui, framework, app;


class settings extends AbstractForm
{

    /**
     * @event hbox5.click-Left 
     */
    function doHbox5ClickLeft(UXMouseEvent $e = null)
    {
        $this->mainModule->saveSettings();
    }

    /**
     * @event button.click-Left 
     */
    function doButtonClickLeft(UXMouseEvent $e = null)
    {    
        $this->forms->showModal($this->forms->about);
    }

    /**
     * @event buttonAlt.click-Left 
     */
    function doButtonAltClickLeft(UXMouseEvent $e = null)
    {
        
        $this->forms->progressDialog->progressName = $progressName;
        $this->forms->showModal($this->forms->progressDialog);
        
        $this->forms->progressDialog->percent->text = "";
        $this->forms->progressDialog->titleFunc->text = "Подождите...";
        $this->forms->progressDialog->progressBar->progress = -1;
        
        $this->mainModule->thread(function(){
            if( ! $this->mainModule->update->check() )
                $this->forms->showDialog('Обновлений не обнаружено','Вы используете актуальную версию',false);    
        });
    }


    /**
     * @event darkTheme.click-Left 
     */
    function doDarkThemeClickLeft(UXMouseEvent $e = null)
    {   
        $this->mainModule->updateTheme();
    
        $this->mainModule->config->darkTheme = $this->darkTheme->selected;
        $this->mainModule->config->save();
        
        $this->forms->loadImages($this);
    }

    /**
     * @event vboxAlt.scroll 
     */
    function doVboxAltScroll(UXScrollEvent $e = null)
    {    
        $this->mainModule->scroll($this->container, $e);
    }



}
