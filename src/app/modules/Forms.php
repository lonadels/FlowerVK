<?php
namespace app\modules;

use gui;
use std;
use framework;

class Forms
{
    private $mainModule;
    private $config;
    
    public $currentModal;
    public $currentForm;
    
    public $isHide;

    function __construct($mainModule){
        $this->mainModule = $mainModule;
        $this->config = $mainModule->config;
    }

    /**
     * @param string formName
     * @return UXForm
     */
    function __get($v): UXForm {
        $application = Application::get();
        $application->form($v)->mainModule = $this->mainModule;
        $application->form($v)->forms = $this;
        return $application->form($v);
    }

    function showAnim($panel){
        if( ! $this->config->offAnimations ){
            $panel->scale = 0.97;
            $panel->opacity = 0;
    
            $scaleAnim = new ScaleAnimationBehaviour();
            $scaleAnim->scale = 1;
            $scaleAnim->duration = 130;
    
            $fadeAnim = new FadeAnimationBehaviour();
            $fadeAnim->opacity = 1;
            $fadeAnim->duration = 130;
    
            $scaleAnim->apply($panel);
            $fadeAnim->apply($panel);
        }else{
            $panel->scale = 1;
            $panel->opacity = 1;
        }
    }

    function hideModal(){
    
        $panel = $this->MainForm->tilePaneAlt;
        $fragment = $this->MainForm->fragmentAlt;

        $this->isHide = 1;

        if( ! $this->config->offAnimations ){
            $fadeAnim = new FadeAnimationBehaviour();
            $fadeAnim->opacity = 0;
            $fadeAnim->duration = 130;
            $fadeAnim->apply($panel);
    
            $scaleAnim = new ScaleAnimationBehaviour();
            $scaleAnim->scale = 0.97;
            $scaleAnim->duration = 130;
    
            $scaleAnim->apply($fragment);
    
            Timer::after(130, function()use($panel) {
                $this->mainModule->run(function()use($panel) {
                    $panel->hide();
                    
                    if( is_object($this->currentModal) )
                        $this->currentModal->free();
                        
                    $this->currentModal = null;
                    $this->isHide = 0;
                });
            });      
        }else{
            $this->mainModule->run(function()use($panel) {
                $panel->hide();
                
                if( is_object($this->currentModal) )
                    $this->currentModal->free();
                    
                $this->currentModal = null;
            });
            $this->isHide = 0;
        }
        
    }

    function showModal($dialogForm){
        
        $this->mainModule->thread( function()use($dialogForm){            
            while( $this->isHide );
    
            $this->mainModule->runw(function()use($dialogForm){
                           
                $panel = $this->MainForm->tilePaneAlt;
                $fragment = $this->MainForm->fragmentAlt;
                
                $duration = 130;
            
                if( ! $panel->visible ){
                    if( ! $this->config->offAnimations ){
                    $panel->opacity = 0;
                    $fragment->scale = 0.97;
                    $panel->show();
        
                    $fadeAnim = new FadeAnimationBehaviour();
                    $fadeAnim->opacity = 1;
                    $fadeAnim->duration = $duration;
        
                    $scaleAnim = new ScaleAnimationBehaviour();
                    $scaleAnim->scale = 1;
                    $scaleAnim->duration = $duration;
        
                    $fadeAnim2 = new FadeAnimationBehaviour();
                    $fadeAnim2->opacity = 1;
                    $fadeAnim2->duration = $duration;
        
                    $fadeAnim->apply($panel);
                    $scaleAnim->apply($fragment);
                    $fadeAnim2->apply($fragment);
                    }else{
                        $panel->opacity = 1;
                        $fragment->scale = 1;
                        $panel->show();
                    }
                    if( ! $dialogForm->isFragment() )
                        $dialogForm->showInFragment( $fragment );
                }else{
                    if( isset( $this->currentModal ) && $this->currentModal != $dialogForm )
                    $this->mainModule->runw(function(){
                        $this->currentModal->free();
                    });
                    
                    if( ! $this->config->offAnimations ){
                        $fadeAnim = new FadeAnimationBehaviour();
                        $fadeAnim->opacity = 0;
                        $fadeAnim->duration = $duration;
            
                        $scaleAnim = new ScaleAnimationBehaviour();
                        $scaleAnim->scale = 0.97;
                        $scaleAnim->duration = $duration;
            
                        $fadeAnim2 = new FadeAnimationBehaviour();
                        $fadeAnim2->opacity = 1;
                        $fadeAnim2->delay = $duration;
                        $fadeAnim2->duration = $duration;
            
                        $scaleAnim2 = new ScaleAnimationBehaviour();
                        $scaleAnim2->scale = 1;
                        $scaleAnim2->delay = $duration;
                        $scaleAnim2->duration = $duration;
            
                        $fadeAnim->apply($fragment);
                        $fadeAnim2->apply($fragment);
            
                        $scaleAnim->apply($fragment);
                        $scaleAnim2->apply($fragment);
                        
                        Timer::after($duration, function()use($dialogForm, $fragment) {
                            $this->mainModule->run(function()use($dialogForm, $fragment) {
                                if( ! $dialogForm->isFragment() )
                                    $dialogForm->showInFragment( $fragment );
                            });
                        });
                    }else{
                        $this->mainModule->run(function()use($dialogForm, $fragment) {
                            if( ! $dialogForm->isFragment() )
                                $dialogForm->showInFragment( $fragment );
                        });
                    }
        
                }
                
                $this->currentModal = $dialogForm;
                
                $this->loadImages($dialogForm);
                $this->mainModule->update->draw();    
            });
            
        });

    }
    
    function showDialog($title, $text, $cancel=true, $cancelText=null, $okText=null){
        if( ! isset( $cancelText ) )
            $cancelText = $this->mainModule->lang->get('CANCEL');
            
        if( ! isset( $okText ) )
            $okText = $this->mainModule->lang->get('OK');
            
        $this->mainModule->run( function()use($title, $text, $cancel, &$dialog, $cancelText, $okText){
            $dialog = $this->modalDialog;
            $dialog->result = null;
            $dialog->dialogTitle->text = ucfirst($title);
            $dialog->dialogText->text = ucfirst($text);
            $dialog->buttonCancel->visible = $cancel;
            $dialog->buttonCancel->text = $cancelText;
            $dialog->buttonOk->text = $okText;
            $dialog->buttonOk->on("click", function()use($dialog){
                $this->hideModal();
                $dialog->result = 1;
            });
            $dialog->buttonCancel->on("click", function()use($dialog){
                $this->hideModal();
                $dialog->result = 0;
            });
            $this->showModal( $dialog );
        });
        while( ! isset($dialog->result) && ! app()->isShutdown() );
        return $dialog->result;
    }
    
    function animPanel($panel, $duration=130, $scale=1, $opacity=1, $scaleStart=0.97, $opacityStart=0){
        if( ! $this->config->offAnimations ){
            $panel->opacity = $opacityStart;
            $panel->scale = $scaleStart;
    
            $fadeAnim = new FadeAnimationBehaviour();
            $fadeAnim->opacity = $opacity;
            $fadeAnim->duration = $duration;
    
            $scaleAnim = new ScaleAnimationBehaviour();
            $scaleAnim->scale = $scale;
            $scaleAnim->duration = $duration;
    
            $fadeAnim->apply($panel);
            $scaleAnim->apply($panel);
        }else{
            $panel->opacity = $opacity;
            $panel->scale = $scale;
        }
    }
    
    function allChildren($obj, $children = []){
        foreach ($obj->children as $child){
            if( $child->children )
                $children = $this->allChildren($child, $children);
            else
                $children[] = $child;
        }    
        
        return $children;
    }

    function loadImages($form){
        $theme = $this->mainModule->config->darkTheme ? "dark" : "light";
        
        foreach( $this->allChildren($form) as $child){
            if( $child instanceof UXImageArea ){
                $r = Regex::of("image\\_load\\_(.*)")->with($child->id);
                
                if( $r->find() )
                    $child->image = new UXImage( "res://.data/img/{$theme}/". $r->group(1) .".png" );
                    
            }
        }

    }

    function show($form){    
        if($this->currentForm == $form) return;
        
        $this->loadImages($form);
    
        $panel = $this->MainForm->fragment;
        $form->showInFragment( $panel );
        
        if( isset($this->currentForm) )
            $this->currentForm->free();
            
        $this->currentForm = $form;    
        $this->mainModule->updateSettings();
        $this->mainModule->update->draw();    
    }
}
