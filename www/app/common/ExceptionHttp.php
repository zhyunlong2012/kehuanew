<?php
namespace app\common;

use think\exception\Handle;
use think\exception\HttpException;
use think\exception\ValidateException;
use think\Response;
use Throwable;

class ExceptionHttp extends Handle
{
    use ResponseMsg;
    public function render($request, Throwable $e): Response
    {
        // // 参数验证错误
        // if ($e instanceof ValidateException) {
        //     return json($e->getError(), 422);
        // }

        // // 请求异常
        // if ($e instanceof HttpException && $request->isAjax()) {
        //     return response($e->getMessage(), $e->getStatusCode());
        // }
            // dump($e->getCode());die;
        // //自定义异常
        if($e instanceof ApiMsg){
            $code = $e->getStatusCode();
            $msg = $e -> getMessage();
        }else{
            $code = $e->getStatusCode();
            if(!$code || $code<0){
                $code = ApiMsg::ERR_UNKNOWN[0];
            }
            
            $msg = $e -> getMessage()?:ApiMsg::ERR_UNKNOWN[1];
        }
        return $this->JsonData($code,$msg);
        // 其他错误交给系统处理
        // return parent::render($request, $e);
    }

}