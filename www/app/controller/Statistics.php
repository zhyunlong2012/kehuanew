<?php
namespace app\controller;
use app\common\ApiMsg;
use app\middleware\Auth;
/**
 * 系统数据统计页面
 */
class Statistics
{
    protected $middleware = [Auth::class];
    use \app\common\ResponseMsg;

    /**
     * 原材料月统计
     *
     * @return void
     */
    public function monthsta(){
        $time = input('oprate_time');
        // $position_id = input('position_id');
        $pro_code = input('pro_code');
        $other_code = input('other_code');
        $pro_cates_id = input('pro_cates_id');
        $forward = input('forward');
        $current=input('current')?input('current'):1;
        $pageSize=input('pageSize')?input('pageSize'):10;
        $excel = input('excel')?input('excel'):false;
        // dump($forward);
        if(empty($time)||empty($pro_cates_id)){
            $empty_data = [
                'total' =>0 ,
                'current' => 1,
                'pageSize' => 100,
                'success' => true,
                'data' => []
        
            ];
            return $empty_data;
        }
        $statistics_model = new \app\model\Statistics();
        $res = $statistics_model->yclMonthStatis($current,$pageSize,$time,$pro_code,$other_code,$pro_cates_id,$forward,$excel);
        return $res;
    }




}