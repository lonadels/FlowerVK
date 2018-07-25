<?php
namespace app\modules;

use bundle\jurl\jURL;
use Error;
use framework;
use app;
use std;
use gui;

class MainFunctions 
{
    public $mainModule;
    public $forms;
    public $vk;
    
    function __construct($mainModule){
        $this->mainModule = $mainModule;
        $this->forms = $mainModule->forms;
        $this->vk = $mainModule->vk;
    }
    
    public function genWords($panel, $allWords, $offset=0, $startOffset=100){
        $wordsCount = count($allWords);

        if( $wordsCount - $offset <= 0 ){
            if($panel->andMore){
                $panel->andMore->hide();
                $panel->andMore->free();
                unset($panel->andMore);
            }
            return;    
        }
        
        $words = array_slice($allWords, $offset, $startOffset, true);
        $this->mainModule->genStats( $panel, $words, true, $offset );
        $offset+=$startOffset;
        
        if( $wordsCount - $offset > 0 ){
            if( ! is_object($panel->andMore) )
                $panel->add($panel->andMore = $andMore = new UXButton);
        
            $panel->andMore->height = 28;
            $panel->andMore->focusTraversable = false;
        
            $panel->andMore->text = "Остальные " . $this->mainModule->strings->nmf( ($wordsCount - $offset ) );
            $panel->andMore->on("click", function()use($panel, $allWords, $offset, $startOffset){
                $this->genWords($panel, $allWords, $offset, $startOffset);
            });
            
            $panel->andMore->toFront();
        }else
            if($panel->andMore){
                $panel->andMore->hide();
                $panel->andMore->free();
                unset($panel->andMore);
            }   
    }
    
    public function stats(){
        $progressName = __FUNCTION__;
        return $this->mainModule->thread(function()use($progressName){
            if(! $dialog = $this->mainModule->selectDialog(true) )
                return $this->mainModule->run( function()use(&$proc){ $this->mainModule->setDescription(null); });
            
            $isChat = isset($dialog->message->chat_id);     
            $isUser = $dialog->message->user_id > 0;
            
            $this->mainModule->run( function()use($progressName){
                $this->forms->progressDialog->progressName = $progressName;
                $this->forms->showModal($this->forms->progressDialog);
                
                $this->forms->progressDialog->percent->text = "";
                $this->forms->progressDialog->titleFunc->text = "Подождите...";
                $this->forms->progressDialog->progressBar->progress = -1;
            });
            
            $peer_id = $isChat ? $dialog->message->chat_id + 2000000000 : $dialog->message->user_id;
            if( ! $this->mainModule->parseMessages($peer_id, function($count, $offset){
                static $set;
                if( ! $set ){
                    $this->mainModule->run( function(){
                        $this->forms->progressDialog->titleFunc->text = "Получение сообщений";  
                    });          
                    $set=1;
                }
                
                $percent = round( $offset * 100 / $count );
                $this->mainModule->run( function()use($percent){
                    $this->forms->progressDialog->percent->text = "{$percent}%";
                    $this->forms->progressDialog->progressBar->progress = $percent;
                });
            }, function($item, $count, $offset)
            
            // using vars
            use(    
                
                $progressName,

                &$count,
                &$datesList,
                &$firstDate,
                &$lastDate,
                &$stickers,
                &$stats,
                &$atts,
                &$allWords
                
            ){
                
                static $set, $perSetting;
                
                if( ! isset($this->mainModule->progress[$progressName]) )
                return;
                        
                if( ! $set && $set=1; )
                    $this->mainModule->run( function(){
                        $this->forms->progressDialog->titleFunc->text = "Обработка сообщений";  
                    });        
                    
                $fid = $item->from_id;    
                        
                $a = Regex::of("\\b([a-z-а-я0-9]+)\\b")->with($item->body);
                $b = Regex::of("\\[".($fid>0?"id":"public")."*\\{$fid}|.*\\]")->with($item->body);
                
                if($b->find())
                    $stats[$fid]['ms']++;
                
                if($a->find())
                    foreach ($a as $word)
                        if( trim( $word ) ){
                            $allWords[ ucfirst(strtolower( trim( $word ) )) ]++;
                            $stats[ $item->from_id ]['words'][ucfirst(strtolower( trim( $word ) ))]++;
                        }
                             
                if( $item->fwd_messages )
                    $stats[ $item->from_id ][ 'fwd' ] += count( $item->fwd_messages );
                    
                if( isset($item->attachments) )
                    foreach( $item->attachments as $att ) {
                        if( $att->type == 'doc' and $att->doc->type == 5 and isset( $att->doc->preview->audio_msg ) )
                            $att->type = 'voice';
                        elseif( $att->type == 'doc' and $att->doc->type == 4 and isset( $att->doc->preview->graffiti ) )
                            $att->type = 'graffiti';
                        elseif( $att->type == 'sticker' ) {
                            $image = $att->sticker->images[0];            
                            $stats[ $item->from_id ]['stickers'][ $att->sticker->sticker_id ][] = $image->url;
                            $stickers[ $att->sticker->sticker_id ][] = $image->url;
                        }

                        if( $att->type == 'doc' )
                            $atts[ $att->type ][ $att->doc->type ]++;
                        else
                            $atts[ $att->type ]++;
                    }
                    
                $date = $item->date; 
                
                if( ! $firstDate )            
                    $firstDate = $date;
                    
                $lastDate = $date;
                
                //if(!isset($datesList[date("dd.MM.yyyy", $item->date)]))
                //    print date("dd.MM.yyyy", $item->date) . "\n";
                $datesList[date("dd.MM.yyyy", $date)]++;
                
                $stats[$item->from_id]['messages']++;
                $stats[$item->from_id]['dates'][date("dd.MM.yyyy", $date)]++;     
                
                if( ! $stats[$item->from_id]['firstDate'] )                
                    $stats[$item->from_id]['firstDate'] = $date;                   
                    
                $stats[$item->from_id]['lastDate'] = $date;                   
                         
                if( ! $perSetting ){         
                    $perSetting = 1;
                    $this->mainModule->run( function()use($percent, $offset, $count, &$perSetting){
                        $percent = round( $offset * 100 / $count );  
                        $this->forms->progressDialog->percent->text = "{$percent}%";
                        $this->forms->progressDialog->progressBar->progress = $percent;
                        $perSetting = 0;
                    });
                }
                
            }, function(){
                $this->mainModule->run( function(){
                    $this->forms->progressDialog->percent->text = "";
                    $this->forms->progressDialog->titleFunc->text = "Подождите...";
                    $this->forms->progressDialog->progressBar->progress = -1;
                });
            })){
                $this->forms->showDialog($this->mainModule->lang->get('ERR'), $this->mainModule->lang->get('ERR_DIALOG_PARSE'), false);
                return;
            }
            
            if( ! isset($this->mainModule->progress[$progressName]) )
                return;
    
            $this->mainModule->run( function(){
                $this->forms->progressDialog->percent->text = "";
                $this->forms->progressDialog->titleFunc->text = "Подождите...";
                $this->forms->progressDialog->progressBar->progress = -1;
            });
            
            //var_dump([$firstDate, $lastDate]);
            
            $date=$firstDate-86400; // вот это костыль, пиздец
            while($date<$lastDate){
                $date += $date+86400 >= $lastDate ? $lastDate - $date : 86400;
                // 
                //print date("dd.MM.yyyy", $date) . "\n";
                $dates[date("dd.MM.yyyy", $date)] = isset($datesList[date("dd.MM.yyyy", $date)]) ? $datesList[date("dd.MM.yyyy", $date)] : 0;
                foreach ($stats as $id=>$data)
                    $mdates[$id][date("dd.MM.yyyy", $date)] = isset($data['dates'][date("dd.MM.yyyy", $date)]) ? $data['dates'][date("dd.MM.yyyy", $date)] : 0;
            }
            
            //var_dump($dates);
            
            $allWords = Arr::sort( $allWords, function( $a, $b ) {
                return $b <=> $a;
            }, TRUE);
                     
            foreach ($allWords as $word=>$w){
                $wordsCount += $w;
                $allWords[$word] = $this->mainModule->strings->nmf($w);
            }
              
            /*         
            $atts = Arr::sort( $atts, function( $a, $b ) {
                return $b <=> $a;
            }, TRUE);
            */
                                    
            if( ! empty($stickers) )
            foreach ($stickers as $id => $n)
                $sts[count($n)][$id] = $n[0];
            
            $scount = count($stickers);
            
            if( $sts )
            $stickers = arr::sortByKeys( $sts, function( $a, $b ) {
                return $b <=> $a;
            }, TRUE );
            
            foreach ($stats as $id => $data)
                $users[$id] = $data['messages'];
            
            $users = arr::sort($users, function($a,$b){
                return $b <=> $a;
            }, true);       
            
            
                 
            
            if( isset($dialog->message->chat_id) ){
                $chatUsers = $this->mainModule->getChat($dialog->message->chat_id)->users;        
            
                foreach ($chatUsers as $cu)
                    if( ! isset($users[$cu->id]) )
                        $users[$cu->id] = 0;
            }
            
            $name = ($isChat) ? $this->mainModule->getChat( $dialog->message->chat_id )->title : ( $isUser ? $this->mainModule->getUser( $dialog->message->user_id )->first_name . " " . $this->mainModule->getUser( $dialog->message->user_id )->last_name : $this->mainModule->getGroup( abs($dialog->message->user_id) )->name );
            $ava = ($isChat) ? $this->mainModule->getChat( $dialog->message->chat_id )->photo_50 : ( $isUser ? $this->mainModule->getUser( $dialog->message->user_id )->photo_50 : $this->mainModule->getGroup( abs($dialog->message->user_id) )->photo_50 );       
            $ava = $ava ? $ava : "https://vk.com/images/camera_50.png";        
            
            $mad = $count / (($lastDate - $firstDate) / 86400);
            $mcText = $this->mainModule->strings->nmf($mad)." ".$this->mainModule->strings->declOfNum($mad, ["сообщение","сообщения","сообщений"])."/день";
                     
            if( $isUser )
                $this->mainModule->getUser(array_keys($users));
            else{
                foreach ($users as $group=>$mc)
                    $groups[] = abs($group);
            
                $this->mainModule->getGroup($groups);
            }
            
            if( ! isset($this->mainModule->progress[$progressName]) )
                return;
                
            foreach( $stats as $data )
                if( $data[ 'fwd' ] )
                    $fwds += $data[ 'fwd' ];
            
            $names = [ [ "секунду", "секунды", "секунд" ], [ "минуту", "минуты", "минут" ], [ "час", "часа", "часов" ], [ "день", "дня", "дней" ], [ "месяц", "месяца", "месяцев" ], [ "год", "года", "лет" ] ];

            $times = StringUtils::seconds2times( $lastDate - $firstDate );

            for( $i = count( $times ) - 1; $i >= 0; $i-- )
                 $mainStats["Длительность общения:"] .= ( $i == 0 && ! empty($mainStats["Длительность общения:"]) ? "и " : NULL ) . "$times[$i] " . ( is_array( $names[ $i ] ) ? $this->mainModule->strings->declOfNum( $times[ $i ], $names[ $i ] ) : $names[ $i ] ) . " ";           
            
            $mainStats["Всего сообщений:"] = $this->mainModule->strings->nmf($count);
            $mainStats["Всего слов:"] = $this->mainModule->strings->nmf($wordsCount);
            $mainStats["Переслано сообщений:"] = $this->mainModule->strings->nmf($fwds);
            
            if( $scount > 5 ){
                $this->mainModule->run(function(){
                    foreach ($this->forms->stickers->tilePaneAlt->children as $child)
                        $child->free();
                });
            }
            
            $types = [
                "Фото" => "photo",
                "Видео" => "video",
                "Документы" => "doc",
                "Аудио" => "audio",
                "Ссылки" => "link",
                "Товары" => "market",
                "Наборы товаров" => "market_album",
                "Посты" => "wall",
                "Комментарии" => "wall_reply",
                "Стикеры" => "sticker",
                "Подарки" => "gift",
                "Голосовые" => "voice",
                "Граффити" => "graffiti",
                
                "Смайлы" => "emoji",
                
                "Всего сообщений" => "messages",
                "Пользователи" => "chat_users",
                "Пересланные сообщения" => "forward",
                "Количество слов" => "words",
            ];


            $doctype = [
                "",
                "Текстовые",
                "Архивы",
                "GIF",
                "Изображений",
                "Аудио",
                "Видео",
                "Электронные книги",
                "Другое"
            ];
            
            if( ! empty($atts) )
            foreach ($atts as $att=>$acount){
                if($att=='doc'){
                    foreach ($acount as $c){
                        $allAtts+=$c;
                        $allDocs+=$c;
                    }
                }else{
                    $allAtts+=$acount;
                }
            }
            
            if( ! empty( $atts[ 'doc' ] ) ) {
            
                /*$atts['doc'] = Arr::sort( $atts['doc'], function( $a, $b ) {
                    return $b <=> $a;
                }, TRUE);*/
                
                foreach ( $atts[ 'doc' ] as $type=>$var )
                    if( $var > 0 && $doctype[$type] )
                        $docs[$doctype[$type].":"] = $var  ? $this->mainModule->strings->nmf( $var, 0, '.', ' ' ) : 0;
            }
            
            unset($atts['doc']);
            
            if(! empty($atts)){
            
                $atts = Arr::sort( $atts, function( $a, $b ) {
                    return $b <=> $a;
                }, TRUE);
            
                foreach ($atts as $type=>$var)
                    if( $var > 0 && array_flip($types)[$type] )
                        $attachments[ array_flip($types)[$type] . ":"] = $var ? $this->mainModule->strings->nmf( $var, 0, '.', ' ' ) : 0;
            }
                          
            foreach ( (array)$stickers as $sc => $stcks )              
            foreach ($stcks as $id => $url){                
                $i++;    
                if( ($scount > 6 && $i >= 6 ) || ($scount <= 6 && $i > 6) ) break 2;
                
                $image = $this->mainModule->cachePhoto( $url, "sticker{$id}_64.png", 'stickers' );
                
                $this->mainModule->run( function()use( $scount, $sc, $image ) {                        
                    $this->forms->statistics->hbox8->add($panel = new UXVBox);
                    
                    $panel->alignment = CENTER;
                    $panel->classes->add('funcItem');
                    $panel->borderWidth = 0;
                    $panel->style = "-fx-min-width: 96;";
                    $panel->size = [96,96];
                    $panel->spacing = 5;
                    
                    $panel->add($sticker = new UXImageView);
                    $sticker->autoSize = true;
                    $sticker->image = $image;
                    
                    $panel->add($number = new UXLabel);
                    $number->text = $sc;      
                    $number->autoSize = true;
                    $number->opacity = 0.7;   
                } );  
            }
            
            if( $scount > 6 )
            $this->mainModule->run( function()use( $scount, $stickers ) {    
                $this->forms->statistics->hbox8->add($panel = new UXVBox);
                
                $panel->alignment = CENTER;
                $panel->classes->add('funcItem');
                $panel->style = "-fx-min-width: 96;";
                $panel->borderWidth = 0;
                $panel->size = [96,96];
                $panel->spacing = 5;
                
                $panel->add($number = new UXLabel);
                $number->text = "+" . $this->mainModule->strings->prettyNumber($scount - 6);      
                $number->autoSize = true;
                $number->opacity = 0.7; 
                $number->style = "-fx-font-size: 28";
                
                $panel->on("click", function()use($stickers){
                    $this->forms->showModal($this->forms->progressDialog);
                    
                    $this->forms->progressDialog->percent->text = "";
                    $this->forms->progressDialog->titleFunc->text = "Подождите...";
                    $this->forms->progressDialog->progressBar->progress = -1;
                    
                    $this->mainModule->thread(function()use($stickers){
                        foreach ( (array)$stickers as $count => $stcks )              
                        foreach ($stcks as $id => $url){
                            $image = $this->mainModule->cachePhoto( $url, "sticker{$id}_64.png", 'stickers' );
                            
                            $i++; 
                            
                            $this->mainModule->run(function()use($i, $scount, $count, $image){
                                $this->forms->stickers->tilePaneAlt->add($panel = new UXVBox);
                            
                                $panel->alignment = CENTER;
                                $panel->classes->add('funcItem');
                                $panel->borderWidth = 0;
                                $panel->size = [120,120];
                                $panel->spacing = 5;
                                
                                $panel->add($sticker = new UXImageView);
                                $sticker->autoSize = true;
                                $sticker->image = $image;
                                
                                $panel->add($number = new UXLabel);
                                $number->text = $count;      
                                $number->autoSize = true;
                                $number->opacity = 0.7;  
                            });                            
                        }

                        $this->mainModule->run(function(){
                            $this->forms->showModal($this->forms->stickers);
                        });
                    });
                    
                });
            });
            
            $this->mainModule->run( function()use($stats, $allWords, $allAtts, $allDocs, $wordsCount, $progressName, $mainStats, $attachments, $docs, $scount, $stickers, $mcText, $dialog, $count, $dates, $users, $chatUsers, $isChat, $isUser, $mdates, $firstDate, $lastDate, $name, $ava){     

                foreach ($this->forms->statistics->vbox3->children as $child)
                    $child->free();
                    
                foreach ($this->forms->statistics->vbox9->children as $child)
                    $child->free();
                    
                foreach ($this->forms->statistics->vbox11->children as $child)
                    $child->free();
                    
                foreach ($this->forms->statistics->vbox4->children as $child)
                    $child->free();
                    
                unset($this->forms->statistics->vbox4->andMore);    

                $this->forms->hideModal();
                $this->forms->show($this->forms->statistics);
                
                if( ! empty($mainStats) )
                $this->mainModule->genStats( $this->forms->statistics->vbox3, $mainStats );
                
                if( ! empty($attachments) )
                $this->mainModule->genStats( $this->forms->statistics->vbox9, $attachments );
                
                if( ! empty($docs) )
                $this->mainModule->genStats( $this->forms->statistics->vbox11, $docs );
                
                $this->genWords($this->forms->statistics->vbox4, $allWords);
                
                $this->forms->statistics->userName->text = $name;      
                $this->forms->statistics->messCount->text = number_format($count, 0, ".", " ") . " " . $this->mainModule->strings->declOfNum( $count, ['сообщение','сообщения','сообщений'] );
                
                $image = $this->forms->statistics->userAvatar;      
                                
                $this->mainModule->loadAva($image, $ava);
                
                //var_dump($dates);
                if(count($dates) > 1){
                    $this->mainModule->visman($this->forms->statistics->panelAlt, true);
                    $this->mainModule->visman($this->forms->statistics->hbox3, true);
                    //$this->mainModule->graphic( $this->forms->statistics->canvas, $dates, $this->forms->statistics->panelAlt, $this->forms->statistics->canvasAlt, $this->forms->statistics->panel3 );
                    $this->mainModule->graphic( $this->forms->statistics->canvas, $dates, $this->forms->statistics->panelAlt );
                }else{
                    $this->mainModule->visman($this->forms->statistics->panelAlt, false);
                    $this->mainModule->visman($this->forms->statistics->hbox3, false);
                }
                
                $this->forms->statistics->label4->text = $isChat ? ( ! empty($chatUsers) ? $this->mainModule->strings->nmf(count($chatUsers)) . " из " . $this->mainModule->strings->nmf(count($users)) : $this->mainModule->strings->nmf(count($users))) : "";  
                $this->forms->statistics->label3->text = $mcText;
                $this->forms->statistics->label8->text = $this->mainModule->strings->nmf( $scount );
                $this->forms->statistics->label10->text = $this->mainModule->strings->nmf( $wordsCount );
                
                $this->forms->statistics->vbox9->visible = $this->forms->statistics->vbox9->managed = $this->forms->statistics->hbox14->visible = $this->forms->statistics->hbox14->managed = $allAtts > 0;
                $this->forms->statistics->vbox11->visible = $this->forms->statistics->vbox11->managed = $this->forms->statistics->hbox20->visible = $this->forms->statistics->hbox20->managed = $allDocs > 0;
                $this->forms->statistics->hbox8->visible = $this->forms->statistics->hbox8->managed = $this->forms->statistics->hbox7->visible = $this->forms->statistics->hbox7->managed = ! empty($stickers);
                
                $this->forms->statistics->label28->text = $this->mainModule->strings->nmf( $allAtts );
                $this->forms->statistics->label40->text = $this->mainModule->strings->nmf( $allDocs );

                $this->mainModule->thread(function()use($stats, $users, $dialog, $mdates, $isChat, $isUser, $chatUsers, $progressName){
                    foreach ((array)$users as $userId => $messages){
                        $lisUser = $userId > 0;
                    
                        if( $lisUser ){
                            $userGen = $this->mainModule->getUser($userId, "gen");
                            $user = $this->mainModule->getUser($userId);
                        } else 
                            $group = $this->mainModule->getGroup(abs($userId));
                        
                        $avatar = $lisUser ? $user->photo_50 : $group->photo_50;
                        
                        if( ! $avatar )
                            $avatar = "https://vk.com/images/camera_50.png";
                        
                        if( $isChat && ! empty($chatUsers) ){
                            $find = 0;
                        
                            foreach ($chatUsers as $cu)
                            if( $user->id == $cu->id ){
                                $find = 1;
                                break;
                            }
                        }
                        
                        $c = $this->mainModule->strings->prettyNumber( $messages );
                        $c = "$c " . $this->mainModule->strings->declOfNum( Regex::of('([^0-9])')->with($c)->replace(''), ["сообщение","сообщения","сообщений"] );
                        
                        if( ! isset($this->mainModule->progress[$progressName]) ) return;
                        
                        $this->mainModule->run(function()use($stats, $c, $find, $chatUsers, $group, $mdates, $dialog, $user, $avatar, $userGen, $userId, $messages, $isChat, $isUser,  $lisUser){                        
                            $panel = new UXVBox;
                            $panel->classes->add("funcItem");
                            $panel->alignment = 'CENTER';
                            $panel->borderWidth = 0;
                            $panel->size = [120,120];
                            $panel->spacing = 5;
                            
                            $panel->add( $image = new UXImageArea );
                            $image->centered = true;
                            $image->size = [50,50];
                            
                            $this->mainModule->loadAva($image, $avatar);
                                                        
                            $panel->add( $title = new UXLabel );
                            $title->text = $lisUser ? $user->first_name : $group->name;
                            $title->autoSize = true;
                            $title->style = "-fx-max-width: 100; -fx-font-weight: bold;";
                            $title->wrapText = true;
                            $title->textAlignment = CENTER;
                            $title->alignment = CENTER;
                            
                            $panel->add( $count = new UXLabel );
                            $count->text = $c;
                            $count->autoSize = true;
                            $count->style = "-fx-max-width: 100; -fx-font-size: 10; -fx-opacity: 0.7;";
                            $count->wrapText = true;
                            $count->textAlignment = CENTER;
                            $count->alignment = CENTER;
                            
                            if( $isChat && ! empty($chatUsers) ){                               
                                if( ! $find ){
                                    $title->opacity = 0.7;
                                    $colorAdjustEffect = new ColorAdjustEffectBehaviour();
                                    $colorAdjustEffect->saturation = -1;
                                    $colorAdjustEffect->apply($image);
                                }
                            }
                            
                            if( $messages > 0 )
                            $panel->on("click", function()use($stats, $mdates, $isUser, $isChat, $dialog, $avatar, $user, $userGen, $lisUser, $group){
                                $this->userStats($stats, $mdates, $isUser, $isChat, $dialog, $avatar, $user, $userGen, $lisUser, $group);
                            });
                            
                            $this->forms->statistics->hboxAlt->add($panel);
                        }); 
                    }
                });
                
        
            });
        });
        
        
    }
    
    public function userStats($stats, $mdates, $isUser, $isChat, $dialog, $avatar, $user, $userGen, $lisUser, $group){
        $userId = $lisUser ? $user->id : -$group->id;                                    
        
        if( ! empty($stats[$userId]['stickers']) )
        foreach ($stats[$userId]['stickers'] as $id => $n)
            $sts[count($n)][$id] = $n[0];
        
        $scount = count($stats[$userId]['stickers']);
        
        if( $sts )
        $stickers = arr::sortByKeys( $sts, function( $a, $b ) {
            return $b <=> $a;
        }, TRUE );
    
        foreach ( (array)$stickers as $sc => $stcks )              
        foreach ($stcks as $id => $url){                
            $i++;    
            if( ($scount > 5 && $i >= 5 ) || ($scount <= 5 && $i > 5) ) break 2;
            
            $image = $this->mainModule->cachePhoto( $url, "sticker{$id}_64.png", 'stickers' );
            
            $this->mainModule->run( function()use( $scount, $sc, $image ) {                        
                $this->forms->userStats->hboxAlt->add($panel = new UXVBox);
                
                $panel->alignment = CENTER;
                $panel->classes->add('funcItem');
                $panel->borderWidth = 0;
                $panel->size = [120,120];
                $panel->spacing = 5;
                
                $panel->add($sticker = new UXImageView);
                $sticker->autoSize = true;
                $sticker->image = $image;
                
                $panel->add($number = new UXLabel);
                $number->text = $sc;      
                $number->autoSize = true;
                $number->opacity = 0.7;   
            } );  
        }
        
        $this->mainModule->visman($this->forms->userStats->hboxAlt, ! empty($stats[$userId]['stickers']));
        $this->mainModule->visman($this->forms->userStats->hbox3, ! empty($stats[$userId]['stickers']));
        $this->forms->userStats->labelAlt->text = $this->mainModule->strings->nmf( $scount );
        
        if( ! empty($stats[$userId]['words']) )
        $allWords = Arr::sort( $stats[$userId]['words'], function( $a, $b ) {
            return $b <=> $a;
        }, TRUE);
        
        $this->mainModule->visman($this->forms->userStats->vbox4, ! empty($stats[$userId]['words']));
        $this->mainModule->visman($this->forms->userStats->hbox5, ! empty($stats[$userId]['words']));
        
        if( ! empty($allWords) )
        foreach ($allWords as $word=>$w){
            $wordsCount += $w;
            $allWords[$word] = $this->mainModule->strings->nmf($w);
        }
        
        $names = [ [ "секунду", "секунды", "секунд" ], [ "минуту", "минуты", "минут" ], [ "час", "часа", "часов" ], [ "день", "дня", "дней" ], [ "месяц", "месяца", "месяцев" ], [ "год", "года", "лет" ] ];

        $times = StringUtils::seconds2times( $stats[$userId]['lastDate'] - $stats[$userId]['firstDate'] );

        for( $i = count( $times ) - 1; $i >= 0; $i-- )
             $mainStats["Длительность общения:"] .= ( $i == 0 && ! empty($mainStats["Длительность общения:"]) ? "и " : NULL ) . "$times[$i] " . ( is_array( $names[ $i ] ) ? $this->mainModule->strings->declOfNum( $times[ $i ], $names[ $i ] ) : $names[ $i ] ) . " ";           
        
        $mainStats["Всего сообщений:"] = $this->mainModule->strings->nmf($stats[$userId]['messages']);
        $mainStats["Всего слов:"] = $this->mainModule->strings->nmf($wordsCount);
        $mainStats["Переслано сообщений:"] = $this->mainModule->strings->nmf($stats[$userId]['fwd']);
        
        if( $stats[$userId]['ms'] > 0 )
            $mainStats["Упоминаний:"] = $this->mainModule->strings->nmf($stats[$userId]['ms']);
        
        $this->forms->userStats->label5->text = $this->mainModule->strings->nmf($wordsCount);
        
        if( ! empty($allWords) )
            $this->genWords($this->forms->userStats->vbox4, $allWords, 0, 30);
        
        if( ! empty($mainStats) )
            $this->mainModule->genStats( $this->forms->userStats->vbox3, $mainStats );
        
        if( $scount > 5 )
        $this->mainModule->run( function()use( $scount, $stickers, $stats, $mdates, $isUser, $isChat, $dialog, $avatar, $user, $userGen, $lisUser, $group ) {                                        
            $this->forms->userStats->hboxAlt->add($panel = new UXVBox);
            
            $panel->alignment = CENTER;
            $panel->classes->add('funcItem');
            $panel->borderWidth = 0;
            $panel->size = [120,120];
            $panel->spacing = 5;
            
            $panel->add($number = new UXLabel);
            $number->text = "+" . ($scount - 4);      
            $number->autoSize = true;
            $number->opacity = 0.7; 
            $number->style = "-fx-font-size: 36";
            
            $panel->on("click", function()use($stickers, $stats, $mdates, $isUser, $isChat, $dialog, $avatar, $user, $userGen, $lisUser, $group){
                $this->mainModule->thread(function()use($stickers, $stats, $mdates, $isUser, $isChat, $dialog, $avatar, $user, $userGen, $lisUser, $group){
                    $this->mainModule->runw(function(){
                        $this->forms->stickers->noHide = true;
                        $this->forms->showModal($this->forms->stickers);
                    });
                    
                    foreach ( (array)$stickers as $count => $stcks )              
                    foreach ($stcks as $id => $url){
                        $image = $this->mainModule->cachePhoto( $url, "sticker{$id}_64.png", 'stickers' );
                        
                        $i++; 
                        
                        $this->mainModule->run(function()use($i, $scount, $count, $image){
                            $this->forms->stickers->tilePaneAlt->add($panel = new UXVBox);
                        
                            $panel->alignment = CENTER;
                            $panel->classes->add('funcItem');
                            $panel->borderWidth = 0;
                            $panel->size = [120,120];
                            $panel->spacing = 5;
                            
                            $panel->add($sticker = new UXImageView);
                            $sticker->autoSize = true;
                            $sticker->image = $image;
                            
                            $panel->add($number = new UXLabel);
                            $number->text = $count;      
                            $number->autoSize = true;
                            $number->opacity = 0.7;  
                        });                            
                    }
                    
                    while(!$this->forms->stickers->result);
                    
                    $this->mainModule->run(function()use($stats, $mdates, $isUser, $isChat, $dialog, $avatar, $user, $userGen, $lisUser, $group){
                        $this->userStats($stats, $mdates, $isUser, $isChat, $dialog, $avatar, $user, $userGen, $lisUser, $group);    
                    });
                });
                
            });
        });
            
        $image = $this->forms->userStats->image;            
            
        $t = $this->mainModule->thread(function()use($image, $isUser, $isChat, $dialog, $avatar, $user, $mdates, $userGen, $lisUser, $group){
           
            if( $lisUser ){
                $id = $dialog->message->user_id == $user->id ? $this->vk->user->id : $dialog->message->user_id;
                $du = $this->mainModule->getUser($id, "ins", 1);
                
            } 
            if( $isChat ) $chat = $this->mainModule->getChat($dialog->message->chat_id); elseif(!$isUser) $gr = $this->mainModule->getGroup(abs($dialog->message->user_id));
            
            $avatar = $this->mainModule->cachePhoto( $avatar );
            $image->image = $avatar;
            $this->mainModule->run(function()use($image, $mdates, $chat, $du, $isChat, $isUser, $gr, $userGen, $user, $lisUser, $group){
               
                $this->forms->userStats->dialogTitle->text = "Статистика " . ($lisUser ? "{$userGen->first_name} {$userGen->last_name}" : $group->name);
                $this->forms->userStats->dialogText->text = $isChat ? "в беседе «".$chat->title."»" : ($isUser ? "в диалоге с {$du->first_name} {$du->last_name}" : "в сообщениях сообщества «".$group->name."»" );                                    
            
                $this->mainModule->setBorderRadius( $image, 25 );
                
                $cmo = count($mdates[$lisUser ? $user->id : -$group->id]) > 1;
                
                $this->mainModule->visman($this->forms->statistics->panelAlt, $cmo);
                $this->mainModule->visman($this->forms->statistics->hbox3, $cmo);
                
                if($cmo)
                    $this->mainModule->graphic( $this->forms->userStats->canvas, $mdates[$lisUser ? $user->id : -$group->id], $this->forms->userStats->panelAlt );
            });
        });
        
        while($t->isAlive());
        
        $this->forms->showModal( $this->forms->userStats );
    }
    
    public function graffiti(){
        $progressName = __FUNCTION__;
        return $this->mainModule->thread(function()use($progressName){          
            $this->mainModule->runw(function()use(&$dialog){
                $dialog = new UXFileChooser;
                $dialog->extensionFilters = [
                    ['description' => 'PNG', 'extensions' => ['*.png']], 
                ];
                
                $dialog = $dialog->showOpenDialog();
            });
          
            if( $dialog ) $file = $dialog->getPath();
                else return;
          
            if(! $d = $this->mainModule->selectDialog(true) )
                return $this->mainModule->run( function(){ $this->mainModule->setDescription(null); });
          
            $this->mainModule->run( function()use($progressName){
                $this->forms->progressDialog->progressName = $progressName;
                $this->forms->showModal($this->forms->progressDialog);
                
                $this->forms->progressDialog->percent->text = "";
                $this->forms->progressDialog->titleFunc->text = "Подождите...";
                $this->forms->progressDialog->progressBar->progress = -1;
            });
            
            $server = $this->vk->method( ["docs", "getUploadServer"], new MethodParams([ "type" => "graffiti" ]) )->response->upload_url;

            $connect = new jURL($url);
            $connect->setOpts([
                'url' => $server,
                'postFiles' => ["file" => $file]
            ]);
            $content = $connect->exec();
            
            $errors = $connect->getError();
            
            if($errors !== false)
                return $this->mainModule->forms->showDialog("Ошибка отправки", "Не удалось загрузить граффити на сервер", false);

            $file = json_decode( $content )->file;
            if( ! $file = $this->vk->method( ["docs", "save"], new MethodParams([ "file" => $file ]) )->response[ 0 ] )
                return $this->mainModule->forms->showDialog("Ошибка отправки", "Не удалось загрузить граффити на сервер", false);
    
            $peer_id = isset($d->message->chat_id) ? $d->message->chat_id + 2000000000 : $d->message->user_id;
    
            if( $this->vk->method( ["messages", "send"], new MethodParams([ "attachment" => "doc" . $file->owner_id . "_" . $file->id, "peer_id" => $peer_id ]) )->error )
                return $this->mainModule->forms->showDialog("Ошибка отправки", "Не удалось отправить граффити в диалог", false);
                
            $this->mainModule->forms->showDialog("Успешно", "Граффити отправлено в диалог", false);    
        });
        
    }
    
    public function voice(){
        $progressName = __FUNCTION__;
        return $this->mainModule->thread(function()use($progressName){          
            $this->mainModule->runw(function()use(&$dialog){
                $dialog = new UXFileChooser;
                $dialog->extensionFilters = [
                    ['description' => 'Звуковые файлы', 'extensions' => ['*.mp3', '*.ogg']], 
                    ['description' => 'MP3', 'extensions' => ['*.mp3']], 
                    ['description' => 'OGG', 'extensions' => ['*.ogg']], 
                ];
                
                $dialog = $dialog->showOpenDialog();
            });
          
            if( $dialog ) $file = $dialog->getPath();
                else return;
          
            if(! $d = $this->mainModule->selectDialog(true) )
                return $this->mainModule->run( function(){ $this->mainModule->setDescription(null); });

            $this->mainModule->run( function()use($progressName){
                $this->forms->progressDialog->progressName = $progressName;
                $this->forms->showModal($this->forms->progressDialog);
                
                $this->forms->progressDialog->percent->text = "";
                $this->forms->progressDialog->titleFunc->text = "Подождите...";
                $this->forms->progressDialog->progressBar->progress = -1;
            });

            $server = $this->vk->method( ["docs", "getUploadServer"], new MethodParams([ "type" => "audio_message" ]) )->response->upload_url;

            $connect = new jURL($url);
            $connect->setOpts([
                'url' => $server,
                'postFiles' => ["file" => $file]
            ]);
            $content = $connect->exec();
            
            $errors = $connect->getError();
            
            if($errors !== false)
                return $this->mainModule->forms->showDialog("Ошибка отправки", "Не удалось загрузить голосовое сообщение на сервер", false);

            $file = json_decode( $content )->file;
            
            if( ! $file = $this->vk->method( ["docs", "save"], new MethodParams([ "file" => $file ]) )->response[ 0 ] )
                return $this->mainModule->forms->showDialog("Ошибка отправки", "Не удалось загрузить голосовое сообщение на сервер", false);
                    
            $peer_id = isset($d->message->chat_id) ? $d->message->chat_id + 2000000000 : $d->message->user_id;
    
            if( $this->vk->method( ["messages", "send"], new MethodParams([ "attachment" => "doc" . $file->owner_id . "_" . $file->id, "peer_id" => $peer_id ]) )->error )
                return $this->mainModule->forms->showDialog("Ошибка отправки", "Не удалось отправить голосовое сообщение в диалог", false);
                
            $this->mainModule->forms->showDialog("Успешно", "Голосовое сообщение отправлено в диалог", false);    
        });
        
    }
    
    
    
    public function history(){
        $form = $this->forms->history;
        
        $history = $this->mainModule->database->query( "SELECT * FROM story WHERE owner = ? ORDER BY time DESC", [$this->vk->user->id] );
        
        if(!empty($history)) {
            foreach ( $history as $event ){
            
                $event = $event->toArray();
                
                if( $lastType != $event['type'] )
                    $events[] = ["time"=>$event["time"], "type"=>$event['type'], "count"=>1];
                else 
                    $events[ count($events)-1 ]['count']++;
                    
                $lastType = $event['type'];
                
            }
            
            $titles = [
                "post" => "Очистка стены",
                "comment" => "Очистка комментариев",
            ];
            
            $desc = [
                "post" => [ ['Удалена', 'Удалено', 'Удалено'], ['запись', 'записи', 'записей'] ],
                "comment" => [ ['Удален', 'Удалено', 'Удалено'], ['комментарий', 'комментария', 'комментариев'] ],
            ];
            
            foreach ($events as $event){
            
                if($event['type'] == 'auth')
                    continue;
                            
                $this->mainModule->genStoryItem($form, $titles[ $event['type'] ], $event['time'], $this->mainModule->strings->declOfNum($event['count'], $desc[$event['type']][0]) . " {$event['count']} " . $this->mainModule->strings->declOfNum($event['count'], $desc[$event['type']][1]) );
            
            }
        }
        
        $this->forms->show($this->forms->history);
    }

}