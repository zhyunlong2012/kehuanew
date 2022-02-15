<?php
namespace app\controller;
use app\common\ResponseMsg;
use app\common\ApiMsg;
use app\common\JwtAuther;

/**
 * 用户登录 2019.11.24
 * edit by madfrog
 */
class Login{
    use ResponseMsg;
    public function index(){
        $username = input('username');
        $password = input('password');
        if((!$username)||(!$password)){
            $content = [
                'status' => 'error',
                "type" => "account",
                'currentAuthority' =>'guest',
                'token' => '' 
            ];

            // return $this->JsonResponse(ApiMsg::ERR_EMPTY[0],ApiMsg::ERR_EMPTY[1],[]);
        }else{
            $user_model = new \app\model\User();
            $res = $user_model->login($username,$password);
            if(!$res){
                $content = [
                    'status' => 'error',
                    "type" => "account",
                    'currentAuthority' =>'guest',
                    'token' => '' 
                ];
                // return $this->JsonResponse(ApiMsg::ERR_PASSWORD[0],ApiMsg::ERR_PASSWORD[1],[]);
            }else{
                $jwttoken = JwtAuther::getInstance();
                $token = $jwttoken->setUid($res['id']) ->encode()->getToken();
                $content = [
                    'status' => 'ok',
                    "type" => "account",
                    'currentAuthority' =>strval($res['user_group_id']),
                    'token' => $token
                ];
                // return $this->JsonSuccess(['token'=>$token]);
            }
        }

        return json($content);
        
    }
}