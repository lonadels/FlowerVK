<?php
namespace app\modules;

use Exception;
use facade\Json;
use Error;
use php\gui\UXImageViewWrapper;
use bundle\sql\SqliteClient;
use std, gui, framework, app;

class MainModule extends AbstractModule
{

    public $forms;
    public $vk;
    public $update;
    public $lang;
    public $strings;
    public $config;
    
    public $dataDir;
    public $database;
    
    public $cancelDialogSelect;
    public $progress;
    
    public $users;
    public $groups;
    public $chats;
    
    public $threads;

    public $mainFunctionsList = [
        'stats'=>"Статистика диалога",
        'graffiti'=>"Отправка граффити",
        'voice'=>"Отправка голосового",
        'like'=>"Установка лайков",
        'search'=>"Поиск сообщений",
        'save'=>"Сохранение диалога",
        'history'=>"История действий",
    ];
    public $mainFunctions;
    
    public $additionFunctionsList = [
        'clearWall'=>["Очистка стены", "Удаление всех записей на стене", [
            'onlyRepost' => ['check', 'Только репосты', false],
            'onlyOthers' => ['check', 'Только чужие записи', false],
            'minLikes' => ['num', 'Минимум лайков', 0],
            'minReposts' => ['num', 'Минимум репостов', 0],
            'minComments' => ['num', 'Минимум комментариев', 0],
            //'dateStart' => ['date', 'От', 0],
            //'dateEnd' => ['date', 'До', 0],
        ]],
        'clearComments'=>["Очистка комментариев", "Очистка всех комментариев под записями на стене или фото", [
            'onlyOthers' => ['check', 'Удалять только чужие комментарии', false],
            //'noLikes' => ['check', 'Не удалять комментарии с лайками', false],
            'minLikes' => ['num', 'Минимум лайков', false],
        ]],
        'clearPhoto'=>["Очистка фотографий", "Очистка всех альбомов от фотографий"],
        'clearSubs'=>["Очистка подписчиков", "Добавление всех подписчиков в чёрный список"],
        'clearFriends'=>["Очистка друзей и подписок", "Отписка от всех пользователей в друзьях и подписках"],
        'clearGroups'=>["Очистка групп", "Выход из всех сообществ, публичных страниц и групп"],
        'clearDocs'=>["Очистка документов", "Очистка всех документов на странице"],
        'clearBlackList'=>["Очистка ЧС", "Удаление пользователей из чёрного списка"],
        'clearFavs'=>["Очистка закладок", "Удаление лайков, ссылок, людей и др. из закладок"],
    ];
    public $additionFunctions;

    function __construct(){
        $this->thread(function(){
            $this->checkDirs();
    
            try {
                $dbfile = $this->dataDir . "\\data.db";

                $this->database = new SqliteClient;
                $this->database->file = $dbfile;
        
                $this->database->query('                
                        CREATE TABLE IF NOT EXISTS `users` ( 
                          id          INT NOT NULL,
                          token       TEXT,
                          domain      TEXT,
                          first_name  TEXT,
                          last_name   TEXT,
                          last_auth   INT,
                          avatar      TEXT
                        );
                ')->update();
                
                $this->database->query('            
                        CREATE TABLE IF NOT EXISTS `settings` (
                          param      TEXT NOT NULL,
                          value      TEXT
                        );
                ')->update();  
                
                $this->database->query('          
                        CREATE TABLE IF NOT EXISTS `story` (
                          owner      INT NOT NULL,
                          id         INT NOT NULL,
                          data       TEXT,
                          type       TEXT NOT NULL,
                          time       INT NOT NULL,
                          repair     INT
                        );
                ')->update();
                
                $this->database->query('          
                        DROP TABLE IF EXISTS `history`;
                ')->update();
            } catch (Exception $e){
                return app::shutdown();
            }
    
            $this->config = new Config($this);
            $this->forms = new Forms($this);
            $this->update = new Updater($this);
            $this->vk = new VKAPI($this);
            $this->lang = new Localization('ru');
            $this->strings = new StringUtils($this);
            
            $this->mainFunctions = new MainFunctions($this);
            $this->additionFunctions = new AdditionFunctions($this);
            
            $count = (int) $this->database->query( "SELECT count(*) FROM users" )->fetch()->get('count(*)');

            uiLaterAndWait(function()use($count){
                $theme = $this->config->darkTheme ? "dark" : "light";
                
                $this->forms->MainForm->addStylesheet(".theme/style_{$theme}.fx.css");
                $this->forms->MainForm->show();
                
                foreach ( ['ico', 'min', 'max', 'close'] as $e )
                    $this->forms->MainForm->$e->image = new UXImage("res://.data/img/{$theme}/{$e}.png");
                
                $this->forms->animPanel($this->forms->MainForm->panel);
                $this->forms->show($this->forms->auth);   
                $this->forms->auth->tilePane->enabled = false;      
                $this->forms->auth->panel3->managed = $this->forms->auth->panel3->visible = $count > 0 && ! $this->config->autoAuth;    
            });
            
            if($this->config->checkUpdates)
                $this->update->check();
            
            uiLater(function(){
                $this->setDescription("авторизация");
                $this->forms->auth->tilePane->enabled=true;      
                
                if( $this->config->autoAuth )
                    $this->autoAuth();    
            });
                      
            
        });
    }
    
    public function clearHistory(){
        
        $this->thread(function(){
            if( $this->forms->showDialog("Очистка истории", "Восстановление удалённых данных будет невозможно!", true, "Отмена", "Продолжить") ){
                $this->database->query("DELETE FROM history;")->update();
                    
                $this->run(function(){
                    foreach ( $this->forms->history->children as $c )
                        $c->free();    
                });    
            }
        });
    }
    
    public function updateTheme(){   
        $theme = $this->config->darkTheme ? "dark" : "light";
        $ntheme = $this->config->darkTheme ? "light" : "dark";
        
        $this->forms->MainForm->removeStylesheet(".theme/style_{$theme}.fx.css");
        $this->forms->MainForm->addStylesheet(".theme/style_{$ntheme}.fx.css");

        foreach ( ['ico', 'min', 'max', 'close'] as $e )
            $this->forms->MainForm->$e->image = new UXImage("res://.data/img/{$ntheme}/{$e}.png");       
    }
    
    public function saveSettings(){    
        
        foreach ($this->config->getDefault() as $c=>$v)
            if(is_object($this->forms->settings->$c)){
                if($this->config->$c != (bool) $this->forms->settings->$c->selected )
                    switch($c){
                        case 'darkTheme':
                            $this->updateTheme();
                            break;
                        case 'autoTheme':
                            if( (bool) $this->forms->settings->$c->selected )
                                $this->themeTimer->start();
                            else
                                $this->themeTimer->stop();
                            break;    
                    }
                $this->config->$c = (bool) $this->forms->settings->$c->selected;
            }
                
        $thread = $this->config->save();  
        $this->updateSettings();  
        
        $this->showMain();
    }
    
    public function loadSettings(){   
        foreach ($this->config->getDefault() as $c=>$v){
            if(is_object($this->forms->settings->$c))
                $this->forms->settings->$c->selected = (bool) $this->config->$c;
        }
    }
    
    public function updateSettings(){
        $config = $this->config;
        
        $boxes = [$this->forms->statistics->containerAlt, $this->forms->mainMenu->containerAlt];
        
        foreach ($boxes as $box)
            $box->css("-fx-hbar-policy", $config->showHorizontalScrolls ? "as_needed" : "never");
    }
    
    public function setDescription($desc){
        $this->forms->MainForm->description->text = empty($desc) ? "" : "- $desc";
    }
    
    public function getUser($id, $name_case='Nom', $force=0){
        $name_case = strtolower($name_case);
    
        if( is_array($id) ){
            $users = $this->vk->method( ["users","get"], new MethodParams(["name_case"=>$name_case, "user_ids"=>implode(",",$id), "fields"=>"photo_id, verified, sex, bdate, city, country, home_town, has_photo, photo_50, photo_100, photo_200_orig, photo_200, photo_400_orig, photo_max, photo_max_orig, online, domain, has_mobile, contacts, site, education, universities, schools, status, last_seen, followers_count, common_count, occupation, nickname, relatives, relation, personal, connections, exports, wall_comments, activities, interests, music, movies, tv, books, games, about, quotes, can_post, can_see_all_posts, can_see_audio, can_write_private_message, can_send_friend_request, is_favorite, is_hidden_from_feed, timezone, screen_name, maiden_name, crop_photo, is_friend, friend_status, career, military, blacklisted, blacklisted_by_me"]) )->response;
            
            if( $users )
            foreach ($users as $user)
                $this->users[$user->id][$name_case] = new VKUser($this->vk, $user->id, $name_case, $user);
                
            return $users;
        }
    
        if( ! isset( $this->users[$id][$name_case] ) || $force )
            $this->users[$id][$name_case] = new VKUser($this->vk, $id, $name_case);
        
        return $this->users[$id][$name_case];    
    }
    
    public function getGroup($id){    
        if( is_array($id) ){
            $groups = $this->vk->method( ["groups","getById"], new MethodParams(["group_ids"=>implode(",",$id), "fields"=>"city, country, place, description, wiki_page, market, members_count, counters, start_date, finish_date, can_post, can_see_all_posts, activity, status, contacts, links, fixed_post, verified, site, ban_info, cover"]) )->response;
   
            if( $groups )
            foreach ($groups as $group)
                $this->groups[$group->id] = new VKGroup($this->vk, $group->id, $group);
                
            return $groups;
        }
    
        if( ! isset( $this->groups[$id] ) )
            $this->groups[$id] = new VKGroup($this->vk, $id);
        
        return $this->groups[$id];    
    }
    
    public function getChat($id){
        if( is_array($id) ){
            $chats = $this->vk->method( ["messages","getChat"], new MethodParams(["chat_ids"=>implode(",",$id), "fields"=>"nickname, screen_name, sex, bdate, city, country, timezone, photo_50, photo_100, photo_200_orig, has_mobile, contacts, education, online, counters, relation, last_seen, status, can_write_private_message, can_see_all_posts, can_post, universities"]) )->response;
            
            if( $chats )
            foreach ($chats as $chat)
                $this->chats[$chat->id] = new VKChat($this->vk, $chat->id, $chat);
                
            return $chats;
        }
    
        if( ! isset( $this->chats[$id] ) )
            $this->chats[$id] = new VKChat($this->vk, $id);
        
        return $this->chats[$id];    
    }
    
    public $madown = null;
    public function genAdditions(){
        foreach($this->additionFunctionsList as $func => $texts){
            $panel = new UXPanel;
            $panel->classes->add("funcItem");
            $panel->classes->add("main");
            $panel->borderWidth = 0;
            
            $panel->add( $vbox = new UXVBox );
            $vbox->paddingLeft = 22;
            $vbox->alignment = CENTER_LEFT;
            $vbox->size =  [480, 64];
            $vbox->anchors = [1,1,1,1];
            
            $vbox->add( $title = new UXLabel );
            $title->text = $texts[0];
            $title->autoSize = true;
            $title->style = "-fx-font-size: 16; -fx-font-weight: bold;";
            
            $vbox->add( $description = new UXLabel );
            $description->text = $texts[1];
            $description->autoSize = true;
            $description->style = "-fx-opacity: 0.7;";
            
            $panel->add( $hbox = new UXHBox );
            $hbox->size = [248, 64];
            $hbox->position = [488, 0];
            $hbox->alignment = CENTER_RIGHT;
            $hbox->anchors = [0,1,1,1];
            
            $hbox->add( $startBox = new UXHBox );
            $startBox->paddingRight = 22;
            $startBox->alignment = CENTER_RIGHT;
            $startBox->spacing = 5;
            
            /*
            $startBox->add( $setting= new UXButton );
            $setting->text = "Настроить";
            $setting->classes->add("outline");
            $setting->focusTraversable = false;
            */
            
            $theme = $this->config->darkTheme ? "dark" : "light";
            
            $startBox->add( $setting = new UXImageArea );
            $setting->classes->add("icon");
            $setting->centered = true;
            $setting->size = [32,32];
            $setting->image = new UXImage("res://.data/img/{$theme}/settings_ico.png");
            $setting->hide();
            
            $startBox->add( $start = new UXButton );
            $start->text = "Запустить";
            $start->focusTraversable = false;
            
            $hbox->add( $progressBox = new UXHBox );
            $progressBox->spacing = 5;
            $progressBox->paddingRight = 22;
            $progressBox->alignment = CENTER_RIGHT;
            $progressBox->managed = $progressBox->visible = false;
            
            $progressBox->add( $percent = $progressBox->percent =  new UXLabel );
            $percent->style = "-fx-opacity: 0.7";
            $percent->text = "0%";
            
            $progressBox->add( $progress = $progressBox->progress = new UXProgressBar );
            $progress->size = [82, 10];

            $progressBox->add( $cancel = $progressBox->cancel = new UXImageArea );
            $cancel->image = new UXImage( "res://.data/img/multiply.png" );
            $cancel->centered = true;
            $cancel->size = [16,32];
            $cancel->classes->add('icon');
            
            $cancel->on('click', function($e){
                unset($this->progress[$e->sender->progress]);
            });

            if( ! method_exists($this->additionFunctions, $func) && ! ( method_exists($this->additionFunctions, "{$func}_test") && $this->config->testMode)  )
                $panel->opacity = 0.5;

            $setting->on("click", function()use($texts, $func){            
                $this->forms->funcSettings->label4->text = $texts[0];
                
                foreach ( $this->additionFunctionsList[$func][2] as $setting => $data ){
                    switch($data[0]){
                        case "check":
                            $this->forms->funcSettings->vbox->add( $cList[$setting] = $c = new UXCheckbox );
                            $c->text = $data[1];
                            $c->selected = $data[2];
                            $c->focusTraversable = false;
                            break;
                        
                        case "num":
                            $this->forms->funcSettings->vbox->add( $p = new UXHbox );
                            $p->add( $t = new UXLabel );
                            $p->add( $cList[$setting] = $n = new UXNumberSpinner );
                            $p->spacing = 6;
                            $p->alignment = CENTER_LEFT;
                            $t->text = $data[1];
                            $n->initial = $data[2];
                            $n->width = 60;
                            $n->editable = true;
                            $n->min = 0;
                            $n->minHeight = 22;
                            break;
                            
                        case "date":
                            $this->forms->funcSettings->vbox->add( $p = new UXHbox );
                            $p->add( $t = new UXLabel );
                            $p->add( $cList[$setting] = $d = new UXDatePicker );
                            $p->spacing = 6;
                            $p->alignment = CENTER_LEFT;
                            $t->text = $data[1];
                            $d->value = $data[2];
                            $d->width = 130;
                            $d->editable = true;
                            $d->minHeight = 22;
                            break;
                    }
                }
                
                $this->forms->showModal($this->forms->funcSettings);
                $this->thread(function()use($func, $cList){
                    while(! isset($this->forms->funcSettings->result) && ! app()->isShutdown() );
                    if($this->forms->funcSettings->result)
                    foreach( $cList as $setting=>$c ){
                        switch ($this->additionFunctionsList[$func][2][$setting][0]){
                            case "check":
                                $value = $c->selected;
                                break;
                            case "num":
                                $value = $c->value;
                                break; 
                            case "date":
                                $value = $c->valueAsTime->toString("dd.MM.yyyy");
                                print_r($value);
//                                $t = new Time(); 
                                break;    
                        }
                        $this->additionFunctionsList[$func][2][$setting][2] = $value;
                    }
                });
            });
            
            $start->on("click", function()use($func, $progressBox, $startBox){
                if( method_exists($this->additionFunctions, "{$func}_test") && $this->config->testMode )
                    $func = "{$func}_test";
            
                if( isset($this->progress[$func]) && $this->progress[$func]->isAlive() ) return;
            
                if( method_exists($this->additionFunctions, $func) )
                    $thread = call_user_func( [$this->additionFunctions, $func], $progressBox, $startBox );            
                else   
                    return $this->thread(function(){ 
                        $this->forms->showDialog("Функция в разработке", "Ожидайте появления этого функционала в следующих версиях", false);
                    });
                    
                $this->progress[$func] = $thread;
                    
                $this->thread(function()use($thread, $func){
                    while( isset($this->progress[$func]) && $thread->isAlive() );
                    
                    if( $thread && $thread->isAlive() ){ 
                        $thread->interrupt();
                        
                        if( method_exists($this->mainFunctions, "{$func}_cancel") )
                            call_user_func([$this->mainFunctions, "{$func}_cancel"]);   
                    }
                });    
            });
            
            $cancel->on("click", function()use($progressBox, $startBox){
                $progressBox->managed = $progressBox->visible = false;
                $startBox->managed = $startBox->visible = true;
            });
            
            $panel->on("MouseDown", function(UXMouseEvent $e = null){      
                $this->madown = [$e->sender, $e->position];
                //$this->forms->MainForm->label3->text = "1";
            });
            
            $panel->on("MouseUp", function(UXMouseEvent $e = null){      
                $this->madown = null;
                //$this->forms->MainForm->label3->text = "0";
            });
            
            $this->forms->mainMenu->container->on("MouseMove", function(UXMouseEvent $e = null){
                
                if( ! isset($this->madown) ) return;
                
                //$this->forms->MainForm->label3->text = "4";
                
                //if( $this->down[1][1] > $e->y - 1 && $this->down[1][1] < $e->y + 1 ) return;
                      
                //$this->forms->MainForm->label3->text = "5";                      
                      
                if( $this->madown[0]->parent != $this->forms->mainMenu->container->content)
                    $this->forms->mainMenu->container->content->add( $this->madown[0] );
                    
                $this->forms->MainForm->label3->text = "3";    
                    
                $this->madown[0]->y = $e->y + $this->madown[1][1]; //app()->form('mainMenu')->curPos[1];
            });
            
            $panel->on("MouseEnter", function()use($setting){
                $setting->show();
            });
            
            $panel->on("MouseExit", function()use($setting){
                $setting->hide();
            });

            $this->forms->mainMenu->vbox4->add($panel);
        }        
    }
    
    public function visman($obj, $status){
        $obj->visible = $obj->managed = $status;
    }
    
    public function validationError($auth){
        if( $auth->error_description == 'wrong code' )
            $this->forms->showDialog("Ошибка", "Вы ввели неверный код", false);
        else   
            $this->forms->showDialog(ucfirst($this->lang->ERR_AUTH_TITLE), ucfirst($this->lang->ERR_AUTH_INCORRECT), false); 
    }
    
    public function validation($phone=null){
        $this->runw(function()use(&$v, $phone){
            $v = $this->forms->validate;
            $v->result = null;
        
            $this->visman($v->resendBox, false);
        
            if( $phone ){
                $this->visman($v->resendBox, true);
                                
                $this->thread(function()use($v){
                    $time = time();
                    
                    while( time() - $time < 60 ){
                        $left = 60 - (time() - $time);
                        $this->run(function()use($left, $v){
                            $v->resendLeft->text = $left; 
                            $v->second->text = $this->strings->declOfNum($left, ["секунду", "секунды", "секунд"]); 
                        });
                        sleep(1);
                    }
                    
                    $this->visman($v->resendBox, false);
                    $this->forms->validate->sms->enabled = true;
                });
                
                $this->forms->validate->number->text = $phone;
                $this->forms->validate->number->visible = $this->forms->validate->number->managed = true;
                $this->forms->validate->text1->text = "На номер";
                $this->forms->validate->text2->text = "отправлено СМС с кодом для подтверждения";
                $this->forms->validate->sms->text = "Отправить повторно";
                $this->forms->validate->sms->enabled = false;
            }else{
                $this->forms->validate->number->visible = $this->forms->validate->number->managed = false;
                $this->forms->validate->text1->text = "Подтвердите авторизацию с помощью кода в сообщении от Администрации";
                $this->forms->validate->text2->text = " или из приложения генерации кодов";
                $this->forms->validate->sms->text = "Отправить СМС";
                $this->forms->validate->sms->enabled = true;
            }
            $this->forms->show($v);
        });

        while(!isset($v->result));
        
        $this->runw(function()use(&$code){
            $code = $this->forms->validate->edit1->text; 
        });
        
        return $v->result ? ['code'=>$code] : ['sms'=>1];
    }
    
    public function captcha($img){
        $img = $this->cachePhoto($img, null, 'captcha');
    }
    
    public function genMain(){

        foreach($this->mainFunctionsList as $func => $titlext){    

            $panel = new UXVBox;
            $panel->classes->add("funcItem");
            $panel->classes->add("main");
            $panel->alignment = CENTER;
            $panel->borderWidth = 0;
            $panel->size = [120,120];
            $panel->spacing = 5;
            
            $panel->add( $image = new UXImageArea );
            $image->image = new UXImage( "res://.data/img/{$func}.png" );
            $image->centered = true;
            $image->size = [48,48];
            
            $panel->add( $title = new UXLabel );
            $title->text = $titlext;
            $title->autoSize = true;
            $title->style = "-fx-max-width: 100";
            $title->wrapText = true;
            $title->textAlignment = CENTER;
            $title->alignment = CENTER;
            
            if( ! method_exists($this->mainFunctions, $func) && ! ( method_exists($this->mainFunctions, "{$func}_test") && $this->config->testMode)  )
                $panel->opacity = 0.5;

            $panel->on("click", function()use($func, $titlext){
                $this->setDescription($titlext);
            
                if( method_exists($this->mainFunctions, "{$func}_test") && $this->config->testMode )
                    $func = "{$func}_test";
            
                if( isset($this->progress[$func]) && $this->progress[$func]->isAlive() ) return;
            
                if( method_exists($this->mainFunctions, $func) ){              
                    $thread = call_user_func( [$this->mainFunctions, $func] );                        
                    $this->progress[$func] = $thread;
                        
                    $this->thread(function()use($thread, $func){
                        while( isset($this->progress[$func]) && $thread->isAlive() );
                        
                        if( $thread && $thread->isAlive() ){ 
                            $thread->interrupt();
                            
                            if( method_exists($this->mainFunctions, "{$func}_cancel") )
                                call_user_func([$this->mainFunctions, "{$func}_cancel"]);   
                        }
                    });
                } else   
                    $this->thread(function(){ 
                        $this->forms->showDialog("Функция в разработке", "Ожидайте появления этого функционала в следующих версиях", false);
                        $this->run(function(){
                            $this->setDescription(null);
                        });
                    });
            });
            
            $this->forms->mainMenu->hbox7->add($panel);
        }
    }
    
    public function genDialogList($dialog){        
        $user_id = $dialog->message->user_id;        
          
        if( isset($dialog->message->chat_id) ){
            $isChat = true;
            $avatar = $dialog->message->photo_50;
            $chat = $this->getChat($dialog->message->chat_id);
            $title = $chat->title;
            $this->getUser($dialog->message->user_id);
        }elseif( $user_id < 0 ){
            $isGroup = true;
            $group = $this->getGroup(abs($user_id));
            $avatar = $group->photo_50;
            $title = $group->name;
        }elseif( $user_id > 0 ){
            $user = $this->getUser($user_id); 
            $isUser = true;    
            $avatar = $user->photo_50;
            $title = "{$user->first_name} {$user->last_name}";
        }
        
        $att[ "photo" ] = "Фотография";
        $att[ "video" ] = "Видео";
        $att[ "audio" ] = "Аудио";
        $att[ "doc" ] = "Документ";
        $att[ "link" ] = "Ссылка";
        $att[ "market" ] = "Товар";
        $att[ "market_album" ] = "Подборка товаров";
        $att[ "wall" ] = "Запись на стене";
        $att[ "wall_reply" ] = "Комментарий на стене";
        $att[ "sticker" ] = "Стикер";
        $att[ "gift" ] = "Подарок";
        
        if(! $avatar )
            $avatar = "https://vk.com/images/camera_50.png";
            
        $body = str_replace( "\n", " ", $dialog->message->body );
        
        if( empty($body) && $dialog->message->attachments )
            $body = $att[ $dialog->message->attachments[ 0 ]->type ];
        elseif( isset( $dialog->message->fwd_messages ) )
            $body = count( $dialog->message->fwd_messages ) . " " . $this->strings->declOfNum( count( $dialog->message->fwd_messages ), [ "сообщение", "сообщения", "сообщений" ] );
        elseif( isset( $dialog->message->action ) ){
                
            $actions['chat_photo_update'] = "обновил фотографию беседы";
            $actions['chat_photo_remove '] = "удалил фотографию беседы";
            
            if( isset($dialog->message->action_text) ){
                $actions['chat_create'] = "создал беседу «" . $dialog->message->action_text . "»";
                $actions['chat_title_update'] = "изменил название беседы на «" . $dialog->message->action_text . "»";
            }
            
            if( isset($dialog->message->action_mid) ){
                $actions['chat_invite_user'] = $dialog->message->action_mid != $dialog->message->user_id ? ("пригласил " . $this->getUser($dialog->message->action_mid, 'gen')->first_name . " " . $this->getUser($dialog->message->action_mid, 'gen')->last_name) : "вернулся в беседу";
                $actions['chat_kick_user'] = $dialog->message->action_mid != $dialog->message->user_id ? ( "исключил " . $this->getUser($dialog->message->action_mid, 'gen')->first_name . " " . $this->getUser($dialog->message->action_mid, 'gen')->last_name ) : "покинул беседу";
            }
            
            $actions['chat_pin_message '] = "закрепил сообщение";
            $actions['chat_unpin_message'] = "открепил сообщение";
            $actions['chat_invite_user_by_link'] = "присоеденился по ссылке";
            
            $body = $this->getUser($dialog->message->user_id)->first_name . " " . $this->getUser($dialog->message->user_id)->last_name . " " . $actions[$dialog->message->action];
        }        
        
        $regex = Regex::of("\\[(id|public)[0-9]+\\|(.*)\\]")->with($body);
        if($regex->find())
            $body = $regex->replace($regex->group(2));
        
        if( $this->cancelDialogSelect )
            return;
        
        $this->runw(function()use($dialog, $title, $count, $text, $body, $avatar, &$panel, $isChat){   
                            
            if( $i > 0 )
                $panel->classes->add('line');
    
            $i++;
            
            $panel = new UXPanel();
            $panel->size = [604, 72];
            $panel->borderWidth = 0;
            $panel->classes->add('autoAuthItem');
    
            $image = new UXImageArea();
            $image->size = [40, 40];
            $image->position = [20, 16];
            $image->centered = true;
            $image->stretch = true;
            $image->proportional = true;
            
            $this->loadAva($image, $avatar);
            
            $vbox = new UXVBox();
            $vbox->size = [544, 40];
            $vbox->position = [80, 14];
            $vbox->spacing = 0;
            $vbox->alignment = CENTER_LEFT;
            
            $hbox = new UXHBox();
            $hbox->spacing = 4;
            $hbox->alignment = CENTER_LEFT;
            
            $hbox2 = new UXHBox();
            $hbox2->spacing = 3;
            $hbox2->alignment = CENTER_LEFT;
            
            if( ( $dialog->message->out || $isChat ) && ! isset( $dialog->message->action )){
                $lastAuth2 = new UXLabel();
                $lastAuth2->autoSize = true;
                $lastAuth2->style = "-fx-opacity: 0.5";
                
                if( $dialog->message->out )
                    $lastAuth2->text = "Вы:";
                else 
                    $lastAuth2->text = $this->getUser($dialog->message->user_id)->first_name . ":";
                    
                $hbox2->add($lastAuth2);
            }
            
            $lastAuth = new UXLabel();
            $lastAuth->autoSize = true;
            $lastAuth->style = (empty($dialog->message->body) && ( $dialog->message->attachments || isset($dialog->message->fwd_messages) )) ? "-fx-text-fill: -primary-color;" : "-fx-opacity: 0.8;";
            $lastAuth->text = $body;
            
            $userName = new UXLabel();
            $userName->autoSize = true;
            $userName->style = "-fx-font-family: \"roboto medium\"; -fx-font-size: 16;" . ($dialog->message->user_id == 100 ? " -fx-text-fill: -primary-color;" : "");
            $userName->text = $title;
            
            $userDomain = new UXLabel();
            $userDomain->autoSize = true;
            $userDomain->style = "-fx-font-size: 16; -fx-opacity: 0.7";
            $userDomain->text = $this->strings->smartTime($dialog->message->date);
            
            $hbox->add($userName);
            $hbox->add($userDomain);
            
            $hbox2->add($lastAuth);
            
            $vbox->add($hbox);
            $vbox->add($hbox2);
            
            $panel->add($vbox);
            $panel->add($image);
            
            $panel->on("click", function()use($isChat, $dialog){
                $this->forms->dialogSelect->dialog = $dialog;
                $this->cancelDialogSelect = true;
                //$this->forms->hideModal();
            });
        });
        
        return $panel;
    } 
    
    public function loadAva($image, $avatar){
        $image->opacity = 0;
            
        $this->thread(function()use($image, $avatar){
            $avatar = $this->cachePhoto( $avatar );
            $image->image = $avatar;
            $this->run(function()use($image){
                $image->opacity = 1;
                $this->setBorderRadius( $image );
                if( ! $this->config->offAnimations ){
                    $image->opacity = 0;
                    Animation::fadeIn($image, 100);      
                }          
            });
        });
    }
    
    public $dialogPanels = [];
    
    public function loadDialogs($offset=0){
        $res = $this->vk->method( ["messages","getDialogs"], new MethodParams(["count"=>"10", "offset"=>$offset]) );

        if( ! isset($res->response->items) )
            return $this->forms->showDialog($this->lang->ERR, $this->lang->ERR_DIALOG_PARSE, false);
          
        $dialogs = $res->response->items;  
        if( empty($dialogs) ){ 
            $this->loaded = true;   
            return;
        }
                   
        foreach ( $dialogs as $dialog )
            if( isset( $dialog->message->chat_id) )
                $chats[] = $dialog->message->chat_id;
            elseif( isset( $dialog->message->user_id ) && $dialog->message->user_id > 0 )
                $users[] = $dialog->message->user_id;
            elseif( isset( $dialog->message->user_id ) && $dialog->message->user_id < 0 )    
                $groups[] = abs( $dialog->message->user_id );

        if( ! empty($chats) )
            $this->getChat($chats); 
        
        if( ! empty($users) )
            $this->getUser($users); 
        
        if( ! empty($groups) )
            $this->getGroup($groups); 

        foreach( $dialogs as $dialog ){
            if( $this->cancelDialogSelect ) return;
            $panel = $this->dialogPanels[] = $this->genDialogList($dialog);
            $this->run(function()use($panel){
                if( ! is_object($panel) ) return;
                
                $this->forms->dialogSelect->vbox->add($panel);
                $this->forms->dialogSelect->label->visible = false;
            });
        }
            
        $this->loaded = true;    
    }
    
    public $loaded = false;
    public $searching = false;
    
    public function searchDialog($e){
        $text = trim($e->sender->text);
        
        $this->forms->dialogSelect->container->scrollY = 0;
        $this->loaded = true;
        
        foreach ($this->dialogPanels as $panel){
            $panel->hide();
            $panel->free();
        }
        
        $this->dialogPanels = [];
        
        if( empty($text) ) return $this->searching = false;
        $this->searching = true;
        
        $this->thread(function()use($text){
            $result = $this->vk->method(["messages", "searchDialogs"], new MethodParams(["limit"=>100, "q"=>$text, "fields"=>"nickname, screen_name, sex, bdate, city, country, timezone, photo_50, photo_100, photo_200_orig, has_mobile, contacts, education, online, counters, relation, last_seen, status, can_write_private_message, can_see_all_posts, can_post, universities "]))->response;

            if( empty($result ) ){
                $this->runw(function(){
                    $this->forms->dialogSelect->label->text = "Ничего не найдено";
                    $this->forms->dialogSelect->label->show();
                });
            }
            
            foreach ($result as $dialog){
                switch($dialog->type){
                    case "profile":
                        $title = "{$dialog->first_name} {$dialog->last_name}";    
                        $dialog->user_id = $dialog->id; 
                        break;
                        
                    case "group": case "page":
                        $title = $dialog->name;
                        $dialog->user_id = -$dialog->id;
                        break;
                        
                    case "chat":
                        $dialog->chat_id = $dialog->id;
                        $title = $dialog->title;
                        break;
                        
                    default:
                        continue 2;            
                }
                
                $avatar = $dialog->photo_50;
                unset($dialog->id);
                
                if(! $avatar )
                    $avatar = "https://vk.com/images/camera_50.png";
                
                $this->runw(function()use($dialog, $title, $avatar, &$panel){   
                                
                    if( $i > 0 )
                        $panel->classes->add('line');
            
                    $i++;
                    
                    $panel = new UXPanel();
                    $panel->size = [604, 72];
                    $panel->borderWidth = 0;
                    $panel->classes->add('autoAuthItem');
            
                    $image = new UXImageArea();
                    $image->size = [40, 40];
                    $image->position = [20, 16];
                    $image->centered = true;
                    $image->stretch = true;
                    $image->proportional = true;
                    
                    $this->loadAva($image, $avatar);
                    
                    $vbox = new UXVBox();
                    $vbox->size = [544, 40];
                    $vbox->position = [80, 16];
                    $vbox->spacing = 0;
                    $vbox->alignment = CENTER_LEFT;
                    
                    $userName = new UXLabel();
                    $userName->autoSize = true;
                    $userName->style = "-fx-font-size: 18;";
                    $userName->text = $title;
                    
                    $vbox->add($userName);
                    
                    $panel->add($vbox);
                    $panel->add($image);
                    
                    $panel->on("click", function()use($dialog){
                        $this->forms->dialogSelect->dialog = (object) ['message'=>$dialog];
                        $this->cancelDialogSelect = true;
                        $this->searching = false;
                        //$this->forms->hideModal();
                    });
                    
                    $this->dialogPanels[] = $panel;
                    $this->forms->dialogSelect->vbox->add($panel);
                    
                    $this->forms->dialogSelect->label->visible = false;
                });
            }  
        });
        
    }
    
    public function selectDialog($noClose){
        $this->loaded = false;
        $this->cancelDialogSelect = false;
        
        $this->run(function(){
            $this->forms->showModal($this->forms->dialogSelect);
        });

        while(!$this->cancelDialogSelect && ! app()->isShutdown() ){

            while( $this->searching && ! $this->cancelDialogSelect && ! app()->isShutdown()  ){
                $force = ! $this->cancelDialogSelect;
                $offset = 0;
            };
            if( ! $first || $force || $this->forms->dialogSelect->container->scrollY == 1 && $this->loaded  ){
           
                $force = 0;
            
                if( isset($loader) )
                    $loader->managed = $loader->visible = true;
                    
                $this->loaded = false;                
                $this->loadDialogs($offset);
                $offset += 10;
                $first=true;
                
                $this->runw( function()use(&$loader){
                    if( ! isset($loader) ){
                        $this->forms->dialogSelect->label->text = "Загрузка диалогов";
                        $this->forms->dialogSelect->label->show();
                        
                        $this->forms->dialogSelect->vbox->add( $loader = new UXLabel );
                        $loader->text = "Загрузка диалогов";
                        $loader->alignment = CENTER;
                        $loader->width = $this->forms->dialogSelect->vbox->width;
                        $loader->style = "-fx-text-fill: -text-dark-gray; -fx-padding: 24;";
                        $this->dialogPanels[] = $loader;
                    }
                    $loader->toFront();
                });
                $loader->managed = $loader->visible = false;
            }
        }
        
        $dialog = $this->forms->dialogSelect->dialog;
        
        if( ! $noClose ){
            $this->forms->hideModal();
            wait(130);
        }
        
        return $dialog;
    }   
    
    public function scroll(UXScrollPane $obj, UXScrollEvent $e, bool $x=false){
        static $scrollSpeed;
        static $arrow;
        static $proccess;
        
        if( $this->config->offAnimations ) return;
        
        new Thread(function()use($obj, $e, $x, &$scrollspeed, &$arrow, &$proccess) {
            $e->consume();
            $arrow = $e->deltaY > 0;

            $i = 0.04;
            if($proccess) return;
            for($scrollspeed = 3; $scrollspeed > 0; $scrollspeed = $scrollspeed - $i){

                $proccess = true;
                if( $x ){
                    if($arrow)
                        $obj->scrollX = $obj->scrollX - ($scrollspeed*100/$obj->content->width)/100;
                    else
                        $obj->scrollX = $obj->scrollX + ($scrollspeed*100/$obj->content->width)/100;
                }else{    
                    if($arrow)
                        $obj->scrollY = $obj->scrollY - ($scrollspeed*100/$obj->content->height)/100;
                    else
                        $obj->scrollY = $obj->scrollY + ($scrollspeed*100/$obj->content->height)/100;
                }
                
                wait(5);
            }
            $proccess = false;         
            
        })->start(); 
    }
    
    public function changeAuth(){
        static $panels;
    
        if( $this->forms->mainMenu->panelAlt->visible ){
            $this->forms->animPanel($this->forms->mainMenu->panelAlt, 130, 1, 0, 1, 1);
            Timer::after(130, function(){
                 $this->forms->mainMenu->panelAlt->hide();
            });
            return;
        }
        if( $count = (int) $this->database->query( "SELECT count(*) FROM users" )->fetch()->get('count(*)') ){
            $data = $this->database->query( "SELECT * FROM users ORDER BY last_auth DESC" );
            
            if( $count > 0 ){
                if($panels)
                foreach ( $panels as $c )
                    $c->free();
                    
                foreach ( $data as $user ){ 
                    $user = $user->toArray();          
                    if( $user['id'] == $this->vk->user->id ) continue;
                                    
                    if( $i > 0 )
                        $panel->classes->add('line');
        
                    $i++;
                    
                    $panels[] = $panel = new UXPanel();
                    $panel->size = [352, 72];
                    $panel->borderWidth = 0;
                    $panel->classes->add('autoAuthItem');

                    $panel->on("click", function()use($user){
                        $this->forms->animPanel($this->forms->mainMenu->panelAlt, 130, 1, 0, 1, 1);
                        Timer::after(130, function(){
                             $this->forms->mainMenu->panelAlt->hide();
                        });
                        
                        $this->thread(function()use($user){
                            $autorized = isset($this->vk->user);   
                        
                            if( $this->vk->authToken($user['token']) ){
                                $this->afterAuth($autorized);
                            }else{
                                $this->forms->auth->tilePane->enabled=true;
                                $this->forms->showDialog(ucfirst($this->lang->ERR_AUTH_TITLE), ucfirst($this->lang->ERR_AUTOAUTH_INCORRECT), false);
                                $this->forms->auth->panel3->managed = $this->forms->auth->panel3->visible = True;    
                            }     
                        });
                    });
                    
                    $image = new UXImageArea();
                    $image->size = [40, 40];
                    $image->position = [20, 16];
                    $image->centered = true;
                    $image->stretch = true;
                    $image->proportional = true;

                    $this->loadAva($image, $user['avatar'] ? $user['avatar'] : "https://vk.com/images/camera_50.png" );
                    
                    $vbox = new UXVBox();
                    $vbox->size = [248, 0];
                    $vbox->position = [80, 20];
                    $vbox->spacing = 0;
                    $vbox->alignment = CENTER_LEFT;
                    
                    $hbox = new UXHBox();
                    $hbox->size = [200, 0];
                    $hbox->spacing = 4;
                    $hbox->alignment = CENTER_LEFT;
                    
                    $lastAuth = new UXLabel();
                    $lastAuth->autoSize = true;
                    $lastAuth->style = "-fx-font-weight: bold; -fx-font-size: 12;";
                    $lastAuth->style = "-fx-opacity: 0.7";
                    $lastAuth->text = (isset($this->vk->user) && $this->vk->user->id == $user['id']) ? "Используется" : ucfirst( $this->strings->timeAgo( $user['last_auth'] ) );
                    
                    $userName = new UXLabel();
                    $userName->autoSize = true;
                    $userName->style = "-fx-font-weight: bold; -fx-font-size: 14;";
                    $userName->text = "{$user['first_name']} {$user['last_name']}";
                    
                    $userDomain = new UXLabel();
                    $userDomain->autoSize = true;
                    $userDomain->style = "-fx-font-size: 10; -fx-opacity: 0.5";
                    $userDomain->text = "@{$user['domain']}";
                    
                    $hbox->add($userName);
                    $hbox->add($userDomain);
                    
                    $vbox->add($hbox);
                    $vbox->add($lastAuth);
                    
                    $panel->add($vbox);
                    $panel->add($image);
                    
                    $this->forms->mainMenu->vbox3->add($panel);
                }   
                
                if( $i > 0 )
                    $panel->classes->add('line');
                
                $this->forms->mainMenu->vbox3->add( $panels[] = $panel = new UXPanel() );
                $panel->size = [352, 72];
                $panel->borderWidth = 0;
                $panel->classes->add('autoAuthItem');
                        
                $image = new UXImageArea();
                $image->size = [40, 40];
                $image->position = [20, 16];
                $image->centered = true;
                $image->stretch = true;
                $image->proportional = true;
                $image->image = new UXImage("res://.data/img/login.png");
                    
                $vbox = new UXVBox();
                $vbox->size = [248, 40];
                $vbox->position = [80, 16];
                $vbox->spacing = 0;
                $vbox->alignment = CENTER_LEFT;
                           
                $userName = new UXLabel();
                $userName->autoSize = true;
                $userName->style = "-fx-font-weight: bold; -fx-font-size: 14;";
                $userName->text = "Выйти";        
                   
                $vbox->add($userName);
                
                $panel->add($vbox);
                $panel->add($image);
                
                $panel->on("click", function(){
                    $this->quit();
                });
                
                $this->forms->mainMenu->panelAlt->show();
                $this->forms->animPanel($this->forms->mainMenu->panelAlt, 130, 1, 1, 1, 0);
            }
        }
    }
    
    public function quit(){
        $this->forms->mainMenu->panelAlt->hide();
        $this->forms->hideModal();
    
        $this->vk->token = null;
        $this->vk->user = null;
        
        $count = (int) $this->database->query( "SELECT count(*) FROM users" )->fetch()->get('count(*)');
        
        if( $this->forms->auth->panel3->parent != $this->forms->auth->vbox )
            $this->forms->auth->vbox->add($this->forms->auth->panel3);
        
        $this->forms->auth->panel3->managed = $this->forms->auth->panel3->visible = $count > 0;    
        
        $this->forms->show($this->forms->auth);
    }

    public function autoAuth(){
        if( $count = (int) $this->database->query( "SELECT count(*) FROM users" )->fetch()->get('count(*)') ){
            $data = $this->database->query( "SELECT * FROM users ORDER BY last_auth DESC" );
            if( $count >= 1 ){
                foreach ( $data as $user ){
                                    
                    $user = $user->toArray();                
                                    
                    if( $i > 0 )
                        $panel->classes->add('line');
        
                    $i++;
                    
                    $panel = new UXPanel();
                    $panel->size = [352, 72];
                    $panel->borderWidth = 0;
                    $panel->classes->add('autoAuthItem');

                    $panel->on("click", function()use($user, &$isDel){
                        if($isDel[$user['id']]) return;
                        $this->thread(function()use($user){
                            $this->forms->auth->tilePane->enabled=false;      
                            $autorized = isset($this->vk->user);                            
                            
                            if( $autorized && $this->vk->user->id == $user['id'] ) return;
                            
                            if( $this->vk->authToken($user['token']) ){
                                $this->forms->hideModal();
                                $this->afterAuth($autorized);
                            }else{
                                $this->forms->auth->tilePane->enabled=true;
                                $this->forms->showDialog(ucfirst($this->lang->ERR_AUTH_TITLE), ucfirst($this->lang->ERR_AUTOAUTH_INCORRECT), false);
                                $this->forms->auth->panel3->managed = $this->forms->auth->panel3->visible = True;    
                            }     
                        });
                    });
                    
                    $image = new UXImageArea();
                    $image->size = [40, 40];
                    $image->position = [20, 16];
                    $image->centered = true;
                    $image->stretch = true;
                    $image->proportional = true;
                    
                    $delete = new UXImageArea;
                    $delete->classes->add('icon');
                    $delete->size = [20,20];
                    $delete->position = [312, 26];
                    $delete->centered = true;
                    $delete->stretch = false;
                    $delete->visible = false;
                    $delete->image = new UXImage("res://.data/img/cancel.png");
    
                    $delete->on('click', function()use($user, $panel, &$isDel){
                        $count = (int) $this->database->query( "SELECT count(*) FROM users" )->fetch()->get('count(*)');
                    
                        $isDel[$user['id']] = true;
                        $panel->free();
                        $this->database->query( "DELETE FROM users WHERE `id` = ?", [$user['id']] )->update();
                        
                        if($count<=1) $this->forms->hideModal();
                    });

                    $this->loadAva($image, $user['avatar'] ? $user['avatar'] : "https://vk.com/images/camera_50.png" );
                    
                    $vbox = new UXVBox();
                    $vbox->size = [248, 0];
                    $vbox->position = [80, 20];
                    $vbox->spacing = 0;
                    $vbox->alignment = CENTER_LEFT;
                    
                    $hbox = new UXHBox();
                    $hbox->size = [200, 0];
                    $hbox->spacing = 4;
                    $hbox->alignment = CENTER_LEFT;
                    
                    $lastAuth = new UXLabel();
                    $lastAuth->autoSize = true;
                    $lastAuth->style = "-fx-font-weight: bold; -fx-font-size: 12;";
                    $lastAuth->style = "-fx-opacity: 0.7";
                    $lastAuth->text = (isset($this->vk->user) && $this->vk->user->id == $user['id']) ? "Используется" : ucfirst( $this->strings->timeAgo( $user['last_auth'] ) );
                    
                    $userName = new UXLabel();
                    $userName->autoSize = true;
                    $userName->style = "-fx-font-weight: bold; -fx-font-size: 14;";
                    $userName->text = "{$user['first_name']} {$user['last_name']}";
                    
                    $userDomain = new UXLabel();
                    $userDomain->autoSize = true;
                    $userDomain->style = "-fx-font-size: 10; -fx-opacity: 0.5";
                    $userDomain->text = "@{$user['domain']}";
                    
                    $hbox->add($userName);
                    $hbox->add($userDomain);
                    
                    $vbox->add($hbox);
                    $vbox->add($lastAuth);
                    
                    $panel->add($vbox);
                    $panel->add($image);
                    $panel->add($delete);
                    
                    $panel->on('MouseEnter', function()use($delete){
                        $delete->visible=true;
                    });
                    $panel->on('MouseExit', function()use($delete){
                        $delete->visible=false;
                    });
                    
                    $this->forms->autoAuth->vbox->add($panel);
                }   

                $this->visman( $this->forms->autoAuth->esc, ! isset($this->vk->user) );
                $this->forms->showModal($this->forms->autoAuth);
            }
        }
        
        if( $this->forms->auth->panel3->parent != $this->forms->auth->vbox )
            $this->forms->auth->vbox->add($this->forms->auth->panel3);
        
        $this->forms->auth->panel3->managed = $this->forms->auth->panel3->visible = False;
    }
    
    public function cachePhoto($url, $name=null, $folder='photos'){

        $this->checkDir($this->dataDir . "\\cache\\{$folder}\\");
        
        if( ! isset($name) )
            $name = basename( explode('?', $url)[0] );
    
        $file = $this->dataDir . "\\cache\\{$folder}\\" . $name;
    
        if( ! file_exists( $file ) )
            file_put_contents($file, Stream::getContents( $url ));
            
        return new UXImage($file);
    }
    
    public function checkDirs(){
        
        $this->dataDir = System::getEnv()['APPDATA'] . "\\FlowerVK";
        
        if( ! is_dir($this->dataDir) )
            mkdir($this->dataDir);
        
        if( ! is_dir( $this->dataDir . "\\cache") )
            mkdir($this->dataDir . "\\cache" );
            
        if( ! is_dir( $this->dataDir . "\\update") )
            mkdir($this->dataDir . "\\update" );
    }
    
    public function checkDir($dir){
        $this->checkDirs();
        
        if( ! is_dir( $dir ) )
            mkdir( $dir );
    }

    public function checkFields($login, $password){
        $login = trim($login);
        $password = trim($password);
    
        $errors = [
           $this->lang->ERR_AUTH_EMPTYDATA => ( empty($login) && empty($password) ),
           $this->lang->ERR_AUTH_EMPTYLOGIN => (bool) empty($login),
           $this->lang->ERR_AUTH_EMPTYPASSWORD => (bool) empty($password),
           $this->lang->ERR_AUTH_INCORRECT => (bool) ( strlen($password) < 6 ),
        ];
    
        foreach ($errors as $error=>$expersion)
            if( $expersion ) break;
        
        if( ! $expersion )
            return True;
        
        $this->forms->showDialog(ucfirst($this->lang->ERR_AUTH_TITLE), ucfirst($error), false);    
        return False;      
    }

    public function auth($login, $password){
        $this->thread( function()use(&$adAccepted, $ad, $login, $password){ 
            if( ! $this->checkFields($login, $password) ) return;
        
            $this->forms->auth->tilePane->enabled=false;
            
            if( $this->vk->auth( $login, $password ) === True )
                return $this->afterAuth();
                
            $this->forms->auth->tilePane->enabled=true;      
            $this->forms->showDialog(ucfirst($this->lang->ERR_AUTH_TITLE), ucfirst($this->lang->ERR_AUTH_INCORRECT), false);
                
        });    
    }
    
    public function afterAuth($nga=0){
        $this->database->query( "INSERT INTO story (owner, id, type, time) VALUES (?, ?, ?, ?);", [$this->vk->user->id, $this->vk->user->id, 'auth', time()] )->update();

        if( $this->database->query( "SELECT count(*) FROM users WHERE id = ?", [$this->vk->user->id] )->fetch()->get("count(*)") == 0 )
            $this->database->query( "INSERT INTO users (id, token) VALUES ( ?, ? )", [$this->vk->user->id, $this->vk->token] )->update();

        $this->database->query( "UPDATE `users` SET `last_auth` = ?, `token` = ?, `first_name` = ?, `last_name` = ?, `domain` = ?, `avatar` = ? WHERE `id` = ?", 
                                [time(), $this->vk->token, $this->vk->user->first_name, $this->vk->user->last_name, $this->vk->user->domain, $this->vk->user->photo_50, $this->vk->user->id] )->update();

        $this->users[$this->vk->user->id]['nom'] = $this->vk->user;

        $this->run( function()use($nga){    
            $this->showMain($nga);
        });
    }
    
    public function showMain($nga=0){
        $nw = $this->database->query( "SELECT count(*) FROM users" )->fetch()->get("count(*)") <= 1;
    
        $this->forms->mainMenu->userName->text = "{$this->vk->user->first_name} {$this->vk->user->last_name}";
        $status = $this->forms->mainMenu->userStatus->text = $this->vk->user->status;
        $this->forms->mainMenu->userStatus->managed = $this->forms->mainMenu->userStatus->visible = ! empty($status);
        $this->forms->mainMenu->userName->style = "-fx-font-size: " . (empty($status) ? 24 : 18);
        
        if( ! $nga ){
            $this->genMain();
            $this->genAdditions();
        }
        
        $this->forms->mainMenu->image_load_down->show();
        $this->forms->mainMenu->flowPane->on("click", function(){
             $this->changeAuth();
        });
        
        if( ! $this->forms->mainMenu->flowPane->classes->has("iconDown") );
            $this->forms->mainMenu->flowPane->classes->add("iconDown");
        
        $this->loadAva( $this->forms->mainMenu->userAvatar, $this->vk->user->photo_50 ? $this->vk->user->photo_50 : "https://vk.com/images/camera_50.png" );
        $this->forms->show($this->forms->mainMenu);
        
        $this->setDescription(null);
    }
    
    public function setBorderRadius( $element, $radius=null ) {
        if( ! isset($radius) )
            $radius = $element->width/2;
    
        $rect = new \php\gui\shape\UXRectangle;
        $rect->width = $element->width;
        $rect->height = $element->height;
        $rect->arcWidth = $radius * 2;
        $rect->arcHeight = $radius * 2;
        
        $element->clip = $rect;
        $element->image = $element->snapshot();
        $element->clip = NULL;
    }
    
    public function graphic( UXCanvas $canvas, $graphic, UXPanel $panel, UXCanvas $canvasMap=null, UXPanel $panelMap=null){

        if($canvasMap)
            $grm = $canvasMap->gc();
    
        $gr = $canvas->gc();
        
        $gr->clearRect(0, 0, $canvas->width, $canvas->height);
        
        $gr->font = UXFont::of('Roboto', 10);
        $gr->fillColor = "gray";

        foreach (array_values( $graphic ) as $pos => $point)
            if($max < $point)
                $max = $point;

        $round = $max > 1000 ? 100 : ($max > 100 ? 10 : ($max > 10 ? 5 : 0.5));
        $count = $max > 100 ? 8 : ($max > 10 ? 4 : 2);
        
        $x_max = count($graphic) - 1;
        $sy = ceil($max/$round)*$round;

        $x0 = ($gr->font->calculateTextWidth($sy) + 5) * 2;
        $y0 = 5;
        $width = $canvas->width - $x0 * 1.5;
        $height = $canvas->height - 40;
        $stepY = $height / $max;
        $stepX = $width / $x_max;
        
        if( $canvasMap ){
            $widthMap = $canvasMap->width - $x0 * 1.5;
            $heightMap = $canvasMap->height - 10;
            $stepYMap = $heightMap / $max;
            $stepXMap = $widthMap / $x_max;
        }
        
        $acg = sqrt($sy);
        $systep = $sy / ($height/$acg);
        
        $systepMap = $sy / ($heightMap/$acg);

        foreach( array_values( $graphic ) as $pos => $point){
            $date = array_keys($graphic)[$pos];
            $textWidth += $gr->font->calculateTextWidth($date) + 5;
        }
        
        $textWidth /= $x_max;
        
        $gr->beginPath();
        for($x=$x0;$x<=$x0+$width;$x+=$stepX){
            if(  $x - $lastStep < $width/$count ) continue;
            $lastStep = $x;
            $gr->moveTo($x, $y0);    
            $gr->lineTo($x, $y0 + $height);    
        }
        
        $sc = $this->config->darkTheme ? "#2F2F2F" : "#EDEDED";
        
        $gr->lineWidth = 1;
        $gr->strokeColor = $sc;
        $gr->stroke();
        $gr->closePath();

        $gr->beginPath();
        for($y=0;$y<$height;$y+=$height/$count){
            $c = ($y*$systep/$acg);
            $text2 = round($c/$round)*$round;
            if( $text2 == $lastText ) continue;
            $lastText = $text2;
            $gr->moveTo($x0, $y0 + $height - $y);    
            $gr->lineTo($x0 + $stepX*$x_max, $y0 + $height - $y);    
            $gr->fillText($text2, $x0 - $gr->font->calculateTextWidth($text2) - 5, $y0 + $height - $y + 2.5);
        }
        
        $gr->lineWidth = 1;
        $gr->strokeColor = $sc;
        $gr->stroke();
        $gr->closePath();

        $gr->beginPath();
        
        $gr->moveTo($x0, $height + $y0);
        
        //горизонтальная линия
        $gr->lineTo($x0 + $stepX*$x_max, $height + $y0);

        $gr->lineWidth = 2;
        $gr->strokeColor = $sc; // 
        $gr->stroke();
        $gr->closePath();
        
        $gr->beginPath();
        
        if( $canvasMap )
            $grm->beginPath();
              
        foreach( array_values( $graphic ) as $pos => $point){
            $x = $x0 + $pos * $stepX;
            $y = $y0 + ($height - $point * $stepY);
            
            if( $canvasMap ){
                $xMap = $x0 + $pos * $stepXMap;
                $yMap = $y0 + ($heightMap - $point * $stepYMap);
            }
            
            if( $pos == 0 ){
                $gr->moveTo($x, $y);
                if( $canvasMap )
                    $grm->moveTo($xMap, $yMap);
            }else{
                $gr->lineTo($x, $y);
                if( $canvasMap )
                    $grm->lineTo($xMap, $yMap);
            }    
            
            $gr->arc($x, $y, 1, 0, 2 * pi(), false);    
            
            if( $canvasMap )
                $grm->arc($xMap, $yMap, 1, 0, 2 * pi(), false);    
                
            $poses[] = [$x, $y, $point, array_keys($graphic)[$pos]];
            
            if( $canvasMap )
                $posesMap[] = [$xMap, $yMap, $point, array_keys($graphic)[$pos]];
        }
                     
        $gr->strokeColor = "#518BCB";
        $gr->lineWidth = 2;//толщина линии        
        $gr->stroke();           
         
        if( $canvasMap ){ 
            $grm->strokeColor = "#518BCB";
            $grm->lineWidth = 2;//толщина линии        
            $grm->stroke();
        }
        
        foreach( array_values( $graphic ) as $pos => $point){
                       
            $x = $x0 + $pos * $stepX;
            $y = $y0 + ($height - $point * $stepY);   
                
            //$gr->fillText(array_keys($graphic)[$pos], $x, $y);     
            $date = array_keys($graphic)[$pos];
            $tx = $x - $gr->font->calculateTextWidth($point)/2;
            $tx = $tx > 0 ? $tx : 0; 
            $tx = ($tx + $gr->font->calculateTextWidth($point)/2) < $width ? $tx : $width - $gr->font->calculateTextWidth($point); 
        }
        
        $gr->fillText(arr::first(array_keys( $graphic )), $x0, $y0+$height+15);
        $gr->fillText(arr::last(array_keys( $graphic )), $x0+$width-$gr->font->calculateTextWidth(arr::last(array_keys( $graphic ))), $y0+$height+15);
        
        if(!$panel->textl){
            $panel->add( $panel->textl = new UXLabel() );
            $panel->textl->textAlignment = CENTER;
            $panel->textl->autoSize = true;
            $panel->textl->textColor = "white";
            $panel->textl->style = "-fx-background-color: -primary-color; 
            -fx-padding: 5 10;
            -fx-background-radius: 4;
            -fx-border-width: 0;
            -fx-effect: dropshadow(three-pass-box, rgba(81,139,203,0.2), 12, 0, 0, 5);";
            
            $panel->textl->visible = false;
        }
        
        if(!$panel->text4){
            $panel->add( $panel->text4 = new UXLabel() );
            $panel->text4->textAlignment = CENTER;
            $panel->text4->autoSize = true;
            $panel->text4->style = "-fx-font-size: 10; 
            -fx-background-color: ".($this->config->darkTheme ? "-light-gray" : "white")."; 
            -fx-padding: 3 5; 
            -fx-background-radius: 4; 
            -fx-border-width: 0; 
            -fx-text-fill: -text;
            -fx-effect: dropshadow(three-pass-box, rgba(0,0,0,0.1), 5, 0, 0, 2);";
            
            $panel->text4->visible = false;
        }
        
        $panel->on("mouseExit", function(UXMouseEvent $e = null)use($panel){
            $panel->textl->visible = $panel->text4->visible = false;
        });
        
        if( $canvasMap ){
            $panelMap->add( $transfer = new UXRectangle );
            $transfer->x = $x0 - 10;
            $transfer->y = $canvasMap->height;
            $transfer->size = [1,1];
            $transfer->strokeColor = "-primary-color";
            $transfer->strokeWidth = 2;
            $transfer->fillColor = "-primary-color";
            
            $this->forms->statistics->slider->max = $x_max;
            
            $panelMap->on("mouseMove", function(UXMouseEvent $e = null)use($x_max, $x0, $widthMap, $posesMap, $canvas, $panel, $graphic, $panelMap, $transfer){
                $x = $e->x < $x0 ? $x0 : ($e->x-$x0 > $widthMap ? $widthMap + $x0 : $e->x); 

                $width = $this->forms->statistics->slider->value;
                
                $transfer->size = [$width, 1];
                $transfer->x = $x;
                
                $x = floor(count($graphic)*(($x-$x0)*100/$widthMap)/100);
                $arr = array_slice($graphic, $x, $width, true);
                $this->graphic($canvas, $arr, $panel);                
            });
                 
            //$panelMap->on("mouseMove", function(UXMouseEvent $e = null)use($x0, $widthMap, $posesMap, $canvas, $panel, $graphic, $panelMap, $transfer){

            //}); 
            
            
        }
        
        $panel->on("mouseMove", function(UXMouseEvent $e = null)use($panel, $poses, $canvas, $stepX, $y0, $x0, $height, $width){
                        
            foreach ($poses as $p)
            if( ( $e->x >= $p[0] - $stepX/2 && $e->x <= $p[0] + $stepX/2 ) ){
                $panel->textl->visible = $panel->text4->visible = true;
                $panel->textl->text = $p[2];   
                $panel->textl->x = $p[0] - ($panel->textl->font->calculateTextWidth($p[2])+20)/2;
                $panel->textl->y = $p[1] - $panel->textl->font->size - 20;
                
                $panel->text4->text = $p[3];
                $ctw = $panel->text4->font->calculateTextWidth($p[3])+5;
                $x = $p[0] - $ctw/2;
                
                $y = $y0 + $height + 2;
                //$y = ( $x+$ctw>=$width+$x0-$ctw || $x<=$x0+$ctw ) ? $y+18 : $y;
                
                $x = $x+$ctw>$width+$x0 ? $width+$x0-$ctw : ($x<$x0 ? $x0-5 : $x);
                
                $panel->text4->x = $x;
                $panel->text4->y = $y;
                
                return;
            }
            
            $panel->textl->visible = $panel->text4->visible = false;
            
        });

    }
    
    public function execThread($id, $offset){
        return $this->vk->execute( '   
            var i = 0;
            var offset = ' . $offset . ';
            var messages = [];

            while(i < 5000){
                messages = messages + API.messages.getHistory({"peer_id": ' . $id . ', "count": 200, "rev": 1, "offset": offset}).items;
                i = i + 200;
                offset = offset + 200;
            }
    
            return messages;' );
    }
    
    public function execThreadWall($offset){
        return $this->vk->execute( '   
            var i = 0;
            var offset = ' . $offset . ';
            var posts = [];

            while(i < 2500){
                posts = posts + API.wall.get({"count": 100, "offset": offset}).items;
                i = i + 100;
                offset = offset + 100;
            }
    
            return posts;' );
    }
    
    public function execThreadComments($post, $offset){
        return $this->vk->execute( '   
            var i = 0;
            var offset = ' . $offset . ';
            var post = ' . $post . ';
            var posts = [];

            while(i < 2500){
                posts = posts + API.wall.getComments({"post_id": post, "count": 100, "offset": offset}).items;
                i = i + 100;
                offset = offset + 100;
            }
    
            return posts;' );
    }
    
    public function genStats($vbox, $array, $numbers=false, $i=0){
        foreach ($array as $name=>$value){            
            $vbox->add($hbox = new UXHBox);
            $hbox->spacing = 3;
            $hbox->alignment = BOTTOM_LEFT;
            
            if( $numbers ) $hbox->add($number = new UXLabel);
            $hbox->add($title = new UXLabel);
            $hbox->add($set = new UXLabel);
            
            if( $numbers ){
                $i++;
                $value = "- $value";
                $number->text = "{$i}.";
                $number->style = "-fx-font-size: 10";
                $number->opacity = 0.7;
            }
                       
            $title->text = $name;
            $title->style = "-fx-font-weight: bold";
            
            $set->text = $value;
            $set->opacity = 0.7;
        }
    }
    
    public function parseMessages($id, $get, $process, $pause){
        $messCount = $this->vk->method( ['messages', 'getHistory'], new MethodParams( [ 'peer_id' => $id, "count" => 1 ] ) )->response->count;
        
        $offset = $parsed= 0;
        $dialogs = [];

        while( $offset < $messCount ) {

            $i++;
            if( time() - $lastExec <= 1 || ceil($messCount/15000) <= 0 ){
                $res = $this->execThread($id, $offset);
    
                $dialogHistory = $res->response;
                $c = count($dialogHistory);
                
                $offset+=$c;
                $parsed+=$c;
                
                $dialogs[$i][] = $dialogHistory;
                
                if($get)
                    call_user_func( $get, $messCount, $parsed );
            } else
            for( $p = 0; $p < (ceil($messCount/15000) > 3 ? 3 : ceil($messCount/15000)); $p++ ) {

                if( $p==2 )
                    $lastExec = time();
                
                $threads["{$i}_{$p}"] = $this->thread(function()use($i, $p, $messCount, $get, $iterations, $id, $offset, &$parsed, &$dialogs, &$threads){
                    //while ( ! is_object($dialogHistory = $this->execThread($id, $offset)->response) ){
                    //    print "Limit?\n";
                    //    sleep(1);
                    //};
                    $dialogHistory = $this->execThread($id, $offset);
                    $dialogs[$i][$p] = $dialogHistory->response;
                    $parsed+=count($dialogHistory->response);
                    
                    unset($threads["{$i}_{$p}"]);
                    
                    if($get)
                        call_user_func( $get, $messCount, $parsed );
                });
                
                $offset += $offset+5000>$messCount ? $messCount-$offset : 5000;
            }
        }
        
        while(!empty($threads));
        
        if( $pause )
        call_user_func( $pause );
        
        foreach( $dialogs as $i => $pdata )
        $dialogs[$i] = arr::sortByKeys( $pdata, function( $a, $b ) {
            return $a <=> $b;
        }, TRUE );
        
        $dialogs = arr::sortByKeys( $dialogs, function( $a, $b ) {
            return $a <=> $b;
        }, TRUE );
        
        foreach( $dialogs as $data )
            foreach( $data as $items )
                foreach( $items as $item )
                    call_user_func( $process, $item, $messCount, ++$mc );
                
        return True;        
    }
    
    public function parseComments($post, $get, $process, $pause){
        $messCount = $this->vk->method( ['wall', 'getComments'], new MethodParams( [ "post_id"=>$post, "count" => 1 ] ) )->response->count;
        
        $offset = $parsed= 0;
        $dialogs = [];

        while( $offset < $messCount ) {

            $i++;
            if( time() - $lastExec <= 1 || ceil($messCount/7500) <= 0 ){
                $res = $this->execThreadComments($post, $offset);
    
                $dialogHistory = $res->response;
                $c = count($dialogHistory);
                
                $offset+=$c;
                $parsed+=$c;
                
                $dialogs[$i][] = $dialogHistory;
                
                if($get)
                call_user_func( $get, $messCount, $parsed );
            } else
            for( $p = 0; $p < (ceil($messCount/7500) > 3 ? 3 : ceil($messCount/7500)); $p++ ) {

                if( $p==2 )
                    $lastExec = time();
                
                $threads["{$i}_{$p}"] = $this->thread(function()use($post, $i, $p, $messCount, $get, $iterations, $offset, &$parsed, &$dialogs, &$threads){
                    //while ( ! is_object($dialogHistory = $this->execThread($id, $offset)->response) ){
                    //    print "Limit?\n";
                    //    sleep(1);
                    //};
                    $dialogHistory = $this->execThreadComments($post, $offset);
                    $dialogs[$i][$p] = $dialogHistory->response;
                    $parsed+=count($dialogHistory->response);
                    
                    unset($threads["{$i}_{$p}"]);
                    
                    if($get)
                    call_user_func( $get, $messCount, $parsed );
                });
                
                $offset += $offset+2500>$messCount ? $messCount-$offset : 2500;
            }
        }
        
        while(!empty($threads));
        
        if($pause)
        call_user_func( $pause );
        
        foreach( $dialogs as $i => $pdata )
        $dialogs[$i] = arr::sortByKeys( $pdata, function( $a, $b ) {
            return $a <=> $b;
        }, TRUE );
        
        $dialogs = arr::sortByKeys( $dialogs, function( $a, $b ) {
            return $a <=> $b;
        }, TRUE );

        foreach( $dialogs as $data )
            foreach( $data as $items )
                foreach( $items as $item )
                    call_user_func( $process, $item, $messCount, ++$mc );
                
        return True;        
    }
    public function parseWall($get, $process, $pause){
        $messCount = $this->vk->method( ['wall', 'get'], new MethodParams( [ "count" => 1 ] ) )->response->count;
        
        $offset = $parsed= 0;
        $dialogs = [];

        while( $offset < $messCount ) {

            $i++;
            if( time() - $lastExec <= 1 || ceil($messCount/7500) <= 0 ){
                $res = $this->execThreadWall($offset);
    
                $dialogHistory = $res->response;
                $c = count($dialogHistory);
                
                $offset+=$c;
                $parsed+=$c;
                
                $dialogs[$i][] = $dialogHistory;
                
                if($get)
                call_user_func( $get, $messCount, $parsed );
            } else
            for( $p = 0; $p < (ceil($messCount/7500) > 3 ? 3 : ceil($messCount/7500)); $p++ ) {

                if( $p==2 )
                    $lastExec = time();
                
                $threads["{$i}_{$p}"] = $this->thread(function()use($i, $p, $messCount, $get, $iterations, $offset, &$parsed, &$dialogs, &$threads){
                    //while ( ! is_object($dialogHistory = $this->execThread($id, $offset)->response) ){
                    //    print "Limit?\n";
                    //    sleep(1);
                    //};
                    $dialogHistory = $this->execThreadWall($offset);
                    $dialogs[$i][$p] = $dialogHistory->response;
                    $parsed+=count($dialogHistory->response);
                    
                    unset($threads["{$i}_{$p}"]);
                    
                    if($get)
                    call_user_func( $get, $messCount, $parsed );
                });
                
                $offset += $offset+2500>$messCount ? $messCount-$offset : 2500;
            }
        }
        
        while(!empty($threads));
        
        if($pause)
        call_user_func( $pause );
        
        foreach( $dialogs as $i => $pdata )
        $dialogs[$i] = arr::sortByKeys( $pdata, function( $a, $b ) {
            return $a <=> $b;
        }, TRUE );
        
        $dialogs = arr::sortByKeys( $dialogs, function( $a, $b ) {
            return $a <=> $b;
        }, TRUE );
        
        foreach( $dialogs as $data )
            foreach( $data as $items )
                foreach( $items as $item )
                    call_user_func( $process, $item, $messCount, ++$mc );
                
        return True;        
    }
    
    public function genStoryItem(UXForm $form, string $titleText, string $timeText, string $descriptionText, callable $undo = null ){
        $form->vbox->add( $storyItem = new UXHBox() );
        
        $storyItem->paddingRight = $storyItem->paddingLeft = 22;
        $storyItem->spacing = 5;
        $storyItem->size = [808, 96];
        $storyItem->classes->add('funcItem');
        $storyItem->classes->add('main');
        $storyItem->minHeight = 96;
        
        $storyItem->add( $linePanel = new UXPanel );
        $linePanel->titleOffset = -8;
        $linePanel->size = [8, 96];
        $linePanel->borderWidth = 0;
        $linePanel->classes->add('trPane');
        
        $linePanel->add( $lineBox = new UXVBox );
        $lineBox->spacing = 5;
        $lineBox->size = [8, 96];
        $lineBox->anchors = [1,1,1,1];
        $lineBox->alignment = CENTER;
        
        $lineBox->add( $circle = new UXCircle );
        $circle->style = "-fx-fill: -primary-color";
        $circle->size = [8, 8];
        
        $linePanel->add( $line = new UXPanel );
        $line->anchors = [0,0,1,1];
        $line->position = [3,0];
        $line->size = [2,96];
        $line->borderWidth = 0;
        $line->titleOffset = -20;
        $line->style = '-fx-background-color: -primary-color';
        
        $storyItem->add( $mainBox = new UXVBox );
        $mainBox->size = [541, 96];
        $mainBox->spacing = 5;
        $mainBox->paddingLeft = 14;
        $mainBox->alignment = CENTER_LEFT;
        
        $mainBox->add( $titleBox = new UXHBox );
        $titleBox->spacing = 3;
        
        $titleBox->add( $title = new UXLabel );
        $title->autoSize = true;
        $title->text = $titleText;
        $title->font->family = "Roboto Black";
        $title->font->size = 16;
        $title->style = "-fx-text-fill: -primary-color";
        
        $titleBox->add( $time = new UXLabel );
        $time->autoSize = true;
        $time->text = $this->strings->smartTime($timeText, true);
        $time->opacity = 0.7;
        $time->font->size = 10;
        $time->font->family = 'Roboto Medium';
        
        $mainBox->add( $secondBox = new UXHBox );
        $secondBox->spacing = 3;
        
        $secondBox->add( $description = new UXLabel );
        $description->opacity = 0.7;
        $description->text = $descriptionText;
        $description->autoSize = true;
        $description->font->size = 10;
        
        $storyItem->add( $cancelBox = new UXHBox );
        $cancelBox->size = [200, 96];
        $cancelBox->alignment = CENTER_RIGHT;
        
        $cancelBox->add( $cancel = new UXButton );
        $cancel->text = "Восстановить";
        $cancel->enabled = isset($undo);
        
        if( isset($undo) )
            $cancel->on('click', $undo);
        
        $storyItem->toFront();
    }
    
    public function restart(){
        $path = realpath($GLOBALS['argv'][0]);
        $path = str_replace("\\", "\\\\", $path);
        $e = 'java -jar "'.$path.'"';
        execute($e, false);
        $this->shutDown();
    }

    public function shutDown(){    
        $this->forms->animPanel($this->forms->MainForm->panel, 130, 0.97, 0, 1, 1);
        $this->thread(function(){
            $save = $this->config->save();
            while( $save->isAlive() );
            Timer::after(130, function(){
                app()->shutdown();
            });    
        });
        
    }

    public function thread($function){    
        $thread = new Thread($function);
        $thread->start();
        return $thread;
    }

    public function run($function, $wait=false){
        if($wait)
            return UXApplication::runLaterAndWait($function);
        else
            return UXApplication::runLater($function);
    }

    public function runw($function){
        return $this->run($function, true);
    }
}
