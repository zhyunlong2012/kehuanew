<?php
declare (strict_types = 1);

namespace app\middleware;

use think\facade\Request;
use app\common\ApiMsg;
use app\common\JwtAuther;
class Auth
{
    use \app\common\ResponseMsg;
    /**
     * 处理请求
     *
     * @param \think\Request $request
     * @param \Closure       $next
     * @return Response
     */
    public function handle($request, \Closure $next)
    {
        $token = Request::header('Authorization');
        // $req =  Request::header(); 
        // $token = substr($token,7);
        // dump($req);
        if(($token)&&($token!=='null')){
            $jwtAuth = JwtAuther::getInstance();
            $jwtAuth->setToken($token);

            if($jwtAuth->validate() && $jwtAuth->verify()){
                return $next($request);
            }else{
                return $this->jsonData(ApiMsg::ERR_TIME[0],ApiMsg::ERR_TIME[1]);
            }
        }else{
            return $this->jsonData(ApiMsg::ERR_TOKEN[0],ApiMsg::ERR_TOKEN[1]);
        }
    }
}
