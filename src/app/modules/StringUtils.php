<?php
namespace app\modules;

use std;

function date($str, $unix=null){ 
    return StringUtils::date($str, $unix);
}

class StringUtils 
{
    public $mainModule;
    
    function __construct($mainModule){
        $this->mainModule = $mainModule;
    }
    
    public static function date($str, $unix=null){  
        $time = new Time(isset($unix) ? $unix*1000 : null );
        return $time->toString($str);
        
    }
    
    public function getMonth( $time, $full = false ) {
        $m = date( "M", $time );
        $month = [ "января", "февраля", "марта", "апреля", "мая", "июня", "июля", "августа", "сентября", "октября", "ноября", "декабря" ][ $m - 1 ];
    
        if( ! $full )
            $month = substr( $month, 0, 3 );
    
        return $month;
    }
    
    public function smartTime( int $time, bool $full = false ) : string {
        $diff = time() - $time;

        $hours = floor( $diff / 3600 );
        $days = floor( $diff / 86400 );
        $months = floor( $diff / 2629743 );
        $year = floor( $diff / 31536000 );

        if( $hours <= 24 )
            return date( "HH:mm", $time );
        elseif( $days == 1 )
            return "вчера" . ($full ? " в " . date("HH:mm", $time) : null);
        elseif( $year <= 1 )
            return date( "d " . self::getMonth( $time ) . ($full ? " HH:mm" : null), $time );
        else
            return date( "d " . self::getMonth( $time )  . ($full ? " HH:mm" : null) . " YYYY", $time );
    }
    
    public function timeAgo( int $time ) : string {
        $diff = time() - $time;

        $seconds = $diff;
        $minutes = round( $diff / 60 );
        $hours = round( $diff / 3600 );
        $days = round( $diff / 86400 );
        $months = round( $diff / 2419200 );

        if( $seconds <= 1 )
            return "только что";
        elseif( $seconds <= 5 )
            return "$seconds " . self::declOfNum( $seconds, [ "секунду", "секунды", "секунд" ] ) . " назад";
        elseif( $seconds <= 60 ) {
            return "меньше минуты назад";
        } elseif( $minutes <= 60 ) {
            if( $minutes == 1 )
                return "минуту назад";
            else
                return "$minutes " . self::declOfNum( $minutes, [ "минуту", "минуты", "минут" ] ) . " назад";
        } elseif( $hours <= 24 ) {
            if( $hours == 1 )
                return "час назад";
            elseif( $hours == 2 )
                return "два часа назад";
            elseif( $hours <= 12 )
                return "$hours " . self::declOfNum( $hours, [ "час", "часа", "часов" ] ) . " назад";
            else
                return "сегодня в " . self::date( "HH:mm", $time );
        } elseif( $days <= 30 ) {
            if( $days == 1 )
                return "вчера в " . self::date( "HH:mm", $time );
            else
                return date( "d " . self::getMonth( $time ) . " в HH:mm", $time );
        } elseif( $months <= 12 )
            return date( "d ", $time ) . self::getMonth( $time );
        else
            return date( "d " . self::getMonth( $time ) . " YYYY", $time );

    }
    
    public function declOfNum($number, $titles) {  
        $cases = array (2, 0, 1, 1, 1, 2);
        return $titles[ ($number%100>4 && $number%100<20)? 2 : $cases[min($number%10, 5)] ];
    }
    
    public static function seconds2times( $seconds ) : array {
        $times = [];
        $count_zero = FALSE;
        $periods = [ 60, 3600, 86400, 2629743, 31536000 ];

        for( $i = 4; $i >= 0; $i-- ) {
            $period = floor( $seconds / $periods[ $i ] );
            if( ( $period > 0 ) || ( $period == 0 && $count_zero ) ) {
                $times[ $i + 1 ] = $period;
                $seconds -= $period * $periods[ $i ];

                $count_zero = TRUE;
            }
        }

        $times[ 0 ] = $seconds;
        return $times;
    }
    
    public static function nmf($n){
        return number_format( $n, 0, '.', ' ' );
    }
    
    public function prettyNumber($n, $precision = 1) {
        if ($n < 1000) $n_format = number_format($n, 0, "", " ");
        else if ($n < 1000000) $n_format = number_format($n/1000, $precision) . 'K';
        else if ($n < 1000000000) $n_format = number_format($n / 1000000, $precision) . 'M';
        else $n_format = number_format($n / 1000000000, $precision) . 'B';
    
        return $n_format;
    }
}