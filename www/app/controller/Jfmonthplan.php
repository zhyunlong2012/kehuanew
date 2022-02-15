<?php
namespace app\controller;
use app\common\ApiMsg;
use app\middleware\Auth;
class Jfmonthplan
{
    protected $middleware = [Auth::class];
    use \app\common\ResponseMsg;

    /**
     * 添加信息
     */
    public function add(){
        $title = input('title');
        if(empty($title)){
            return $this->JsonDataArr(ApiMsg::ERR_PARAMS_EMPTY);
        }
        $jfmonth_plan_model = new \app\model\Jfmonthplan();
        $res = $jfmonth_plan_model->add($title);
        return $this->JsonCommon($res);
    }

    /**
     * 修改信息
     */
    public function updata(){
        $id = input('id');
        $title = input('title');
        if(empty($id)||empty($title)){
            return $this->JsonDataArr(ApiMsg::ERR_PARAMS_EMPTY);
        }
        
        $jfmonth_plan_model = new \app\model\Jfmonthplan();
        $res = $jfmonth_plan_model->updata($id,$title);
        return $this->JsonCommon($res);
    }


    /**
     * 车型列表
     * @return [type] [description]
     */
    public function list(){
        $current=input('current')?input('current'):1;
        $pageSize=input('pageSize')?input('pageSize'):10;
        $title = input('title');
        $url = input('url');
        $excel = input('excel')?input('excel'):false;
        $jfmonth_plan_model = new \app\model\Jfmonthplan();
        return $jfmonth_plan_model->list($current,$pageSize,$title,$excel);
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
        $jfmonth_plan_model = new \app\model\Jfmonthplan();
        $res = $jfmonth_plan_model ->del($id);
        return $this->JsonCommon($res);
    }

    /**
     * 批量添加车型
     *
     * @return void
     */
    public function addTogather(){
        $title = input('title');
        $data = input('data');
        if(empty($data)||empty($title)){
            return $this->JsonDataArr(ApiMsg::ERR_PARAMS_EMPTY);
        }
        $jfcelect_model = new \app\model\CelectJf();
        $monthplan = $jfcelect_model -> getMonthExcel($data);
        $jfmonth_plan_model = new \app\model\Jfmonthplan();
        $res = $jfmonth_plan_model->addTogather($title,$monthplan);
        return $this->JsonCommon($res);
    }

    /**
     * 和最后一版差异
     *
     * @return void
     */
    public function difflastplan(){
        $data = input('data');
        if(empty($data)){
            return $this->JsonDataArr(ApiMsg::ERR_PARAMS_EMPTY);
        }

        $jfcelect_model = new \app\model\CelectJf();
        $monthplan = $jfcelect_model -> getMonthExcel($data);
        $jfmonth_plan_model = new \app\model\Jfmonthplan();
        $res = $jfmonth_plan_model->difflastplan($monthplan);
        return $this->JsonSuccess($res);
    }


}