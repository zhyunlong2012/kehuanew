<?php
namespace app\controller;
use app\common\ApiMsg;
use app\middleware\Auth;
class Jfmonthdetail
{
    protected $middleware = [Auth::class];
    use \app\common\ResponseMsg;

    // /**
    //  * 添加信息
    //  */
    // public function add(){
    //     $title = input('title');
    //     if(empty($title)){
    //         return $this->JsonDataArr(ApiMsg::ERR_PARAMS_EMPTY);
    //     }
    //     $jfmonth_plan_model = new \app\model\jfmonthplanDetail();
    //     $res = $jfmonth_plan_model->add($title);
    //     return $this->JsonCommon($res);
    // }

    // /**
    //  * 修改信息
    //  */
    // public function updata(){
    //     $id = input('id');
    //     $title = input('title');
    //     if(empty($id)||empty($title)){
    //         return $this->JsonDataArr(ApiMsg::ERR_PARAMS_EMPTY);
    //     }
        
    //     $jfmonth_plan_model = new \app\model\jfmonthplan();
    //     $res = $jfmonth_plan_model->updata($id,$title);
    //     return $this->JsonCommon($res);
    // }


    /**
     * 车型列表
     * @return [type] [description]
     */
    public function list(){
        $current=input('current')?input('current'):1;
        $pageSize=input('pageSize')?input('pageSize'):10;
        $title = input('title');
        $pro_code = input('pro_code');
        $cartype = input('cartype');
        $excel = input('excel')?input('excel'):false;
        if(empty($title)){
            return $this->JsonDataArr(ApiMsg::ERR_PARAMS_EMPTY);
        }
        if(strlen($title)>7){
            $title = substr($title,0,7);
        }
        $jfmonth_plan_model = new \app\model\JfmonthplanDetail();
        return $jfmonth_plan_model->list($current,$pageSize,$title,$pro_code,$cartype,$excel);
    }


    /**
     * 删除
     * @return [type] [description]
     */
    public function del(){
        $id = input('id');
        if(empty($id)){
            return $this->JsonDataArr(ApiMsg::ERR_PARAMS_EMPTY);
        }
        $jfmonth_plan_model = new \app\model\jfmonthplanDetail();
        $res = $jfmonth_plan_model ->del($id);
        return $this->JsonCommon($res);
    }



}