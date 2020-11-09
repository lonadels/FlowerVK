<?php
namespace app\modules;

use app;
use php\io\IOException;
use std;

class RequestParams extends Params{};
class MethodParams extends Params{};

class VKAPI
{
    private $id = 3140623;
    private $secret = 'VeWdmVclDCtn6ihuP1nt';
    private $ver = 5.124;

    public $user;
    public $token;
    
    public $mainModule;
    
    function __construct($mainModule){
        $this->mainModule = $mainModule;
    }
    
    public function getService() {

        $params = new RequestParams();
        $params->grant_type = 'client_credentials';
        $params->client_id = $this->id;
        $params->client_secret = $this->secret;
        $params->v = $this->ver;

        try{
            $query = new Request( "https://oauth.vk.com/token?" );
            $query->build($params);
            $query->query();
        } catch (IOException $e) {
            $e->printJVMStackTrace();
            return False;
        }

        if( ! isset($query->get()->error) ){
            $this->token = $query->get()->access_token;
            return True;
        }else{
            return $query->get(); 
        }
    }
    
    public function validation($login, $password, $params, $query){
        $vType = $params->force_sms ? "2fa_sms" : $query->get()->validation_type;
            
        switch( $vType ) {
            case "2fa_sms":
                $validation = $this->mainModule->validation( $query->get()->phone_mask );
                break;

            case "2fa_app":
                $validation = $this->mainModule->validation();
                break;
        }

        $params->force_sms = NULL;

        if( $validation['sms'] )
            $params->force_sms = TRUE;
        else
            $params->code = $validation['code'];

        $auth = $this->auth( $login, $password, $params );
        if( $auth === True )
            return True;
        else{
            $validation = $this->mainModule->validationError($auth);
            return $this->validation($login, $password, $params, $query);    
        }
    }
    
    public function auth($login, $password, RequestParams $params = null){
    
        if( ! isset($params) )
            $params = new RequestParams();
            
        $params->grant_type = 'password';
        $params->client_id = $this->id;
        $params->client_secret = $this->secret;
        $params->username = $login;
        $params->password = $password;
        $params->scope = 140492255; //140492255;
        $params->v = $this->ver;
        $params->add(["2fa_supported"=>1]);

        try{
            $query = new Request( "https://oauth.vk.com/token?" );
            $query->build($params);
            $query->query();
        } catch (IOException $e) {
            $e->printJVMStackTrace();
            return False;
        }
    
        if( ! isset($query->get()->error)){
            $this->token = $query->get()->access_token;
            $this->user = new VKUser( $this, $query->get()->user_id );
            return True;
        }
        
        switch( $query->get()->error ) {
            case "need_validation":

                return $this->validation($login, $password, $params, $query);
                break;

            case "need_captcha":
                $params->captcha_sid = $obj->captcha_sid;
                $params->captcha_key = $this->mainModule->captcha( $obj->captcha_img );

                return $this->auth( $login, $password, $params );
                break;
        }
        
        return $query->get(); 
        
    }
    
    public function authToken($token){
        $res = $this->checkToken( $token );

        if( $res ) {
            $this->token = $token;
            $this->user = new VKUser( $this, $res->response->user_id );
            return TRUE;
        }

        return FALSE;
    }
    
    public function checkToken( $token ) {
        //if( ! $this->token )
            $this->getService();

        $res = $this->method( ["secure", "checkToken"], new MethodParams(["token"=>$token]) );

        if( isset( $res->error ) )
            return FALSE;

        if( $res->response->success )
            return $res;

        return FALSE;
    }
    
    public function execute($code){
        return $this->method(["execute"], new MethodParams(["code"=>$code]));
    }
    
    public function method(array $method, MethodParams $params = null ){
        if( ! isset($params) ) $params = new MethodParams;
        
        $params->client_id = $this->id;
        $params->client_secret = $this->secret;
        $params->access_token = $this->token;
        $params->v = $this->ver;
        
        try{
            $m = isset($method[1]) ? "{$method[0]}.{$method[1]}" : $method[0];
            $query = new Request( "https://api.vk.com/method/$m?" );
            $query->build($params);
            $query->query();
            
            if(isset($query->get()->error) && $query->get()->error_code == 6 ){
                sleep(1);
                return $this->method($method, $params);
            }
            return $query->get(); 
        } catch (IOException $e) {
            $e->printJVMStackTrace();
            return False;
        }
    }
}

