<?php
namespace app\controller;
use app\middleware\Auth;
class Log
{
    protected $middleware = [Auth::class];
    use \app\common\ResponseMsg;


    /**
     * 日志列表
     * @return [type] [description]
     */
    public function list(){
        $current=input('current')?input('current'):1;
        $pageSize=input('pageSize')?input('pageSize'):10;
        $username = input('username');
        $content = input('content');
        $oprate_time  = input('oprate_time');
        $excel = input('excel');
        $Log_model = new \app\model\Log();
        return $Log_model->list($current,$pageSize,$username,$content,$oprate_time,$excel);
    }
}