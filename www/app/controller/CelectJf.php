<?php
namespace app\controller;
use QL\Querylist;
use QL\Services\HttpService;
use QL\Ext\CurlMulti;
use app\common\ApiMsg;
use app\middleware\Auth;
/**
 * 辽弹翻译模块
 */
class CelectJf
{
    protected $middleware = [Auth::class];
    use \app\common\ResponseMsg;

    /**
     * 添加登录
     */
    public function login(){
        $username = input('username');
        $password = input('password');
        // $username = 'LC301';
        // $password = 'Lt2019@#';
        $jf_model = new \app\model\CelectJf();
        $res = $jf_model->login($username,$password);
        if($res){
            return $this->JsonSuccess($res);
        }else{
            return $this->JsonErr();
        }
    }

    /**
     * 辽弹计划
     *
     * @return void
     */
    public function getPlan(){
        $cookie = input('cookie');
        $jf_model = new \app\model\CelectJf();
        $res = $jf_model->getPlan($cookie);
        if($res){
            return $this->JsonSuccess($res);
        }else{
            return $this->JsonErr();
        }
    }

    /**
     * 下载辽弹计划
     *
     * @return void
     */
    public function addPlan(){
        $title = input('title');
        $url = input('url');
        if(empty($title)||empty($url)){
            return $this->JsonDataArr(ApiMsg::ERR_PARAMS_EMPTY);
        }
        $jf_plan_model = new \app\model\JfPlan();
        $res = $jf_plan_model->add($title,$url);
        if($res){
            return $this->JsonSuccess($res);
        }else{
            return $this->JsonErr();
        }
    }

    /**
     * 辽弹查找车型
     *
     * @return void
     */
    public function getCar(){
        $cookie = input('cookie');
        $carcode = input('carcode');
        $cartype = input('cartype');
        $continue = input('continue')?input('continue'):1;
        // $no =  'C014H45561T74QYB4';
        $jf_model = new \app\model\CelectJf();
        $res = $jf_model->getCar($cookie,$carcode,$cartype,$continue);
        if($res){
            return $this->JsonSuccess();
        }else{
            return $this->JsonErr();
        }
    }

    /**
     * 输出订单翻译表格
     *
     * @return void
     */
    public function getPlanExcel(){
        $data = input('data');
        $celectjf_model = new \app\model\CelectJf();
        $res = $celectjf_model ->getPlanExcel($data);
        return $this->JsonSuccess($res); 
    }

    /**
     * 输出订单月计划
     *
     * @return void
     */
    public function getMonthExcel(){
        $data = input('data');
        $celectjf_model = new \app\model\CelectJf();
        $res = $celectjf_model ->getMonthExcel($data);
        return $this->JsonSuccess($res); 
    }

}