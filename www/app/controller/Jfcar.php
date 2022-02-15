<?php
namespace app\controller;
use app\common\ApiMsg;
use app\middleware\Auth;
use think\facade\Db;
class Jfcar
{
    protected $middleware = [Auth::class];
    use \app\common\ResponseMsg;

    /**
     * 添加车型信息
     */
    public function add(){
        $carcode = input('carcode');
        $cartype = input('cartype');
        if(empty($carcode)||empty($cartype)){
            return $this->JsonDataArr(ApiMsg::ERR_PARAMS_EMPTY);
        }
        $car_model = new \app\model\Jfcar();
        $res = $car_model->add($carcode,$cartype);
        return $this->JsonCommon($res);
    }

    /**
     * 修改车型信息
     */
    public function updata(){
        $id = input('id');
        $carcode = input('carcode');
        $cartype = input('cartype');
        if(empty($id)||empty($carcode)||empty($cartype)){
            return $this->JsonDataArr(ApiMsg::ERR_PARAMS_EMPTY);
        }
        
        $car_model = new \app\model\Jfcar();
        $res = $car_model->updata($id,$carcode,$cartype);
        return $this->JsonCommon($res);
    }


    /**
     * 车型列表
     * @return [type] [description]
     */
    public function list(){
        $current=input('current')?input('current'):1;
        $pageSize=input('pageSize')?input('pageSize'):10;
        $carcode = input('carcode');
        $cartype = input('cartype');
        $excel = input('excel')?input('excel'):false;
        $car_model = new \app\model\Jfcar();
        return $car_model->list($current,$pageSize,$carcode,$cartype,$excel);
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
        $car_model = new \app\model\Jfcar();
        $res = $car_model ->del($id);
        return $this->JsonCommon($res);
    }

     /**
     * 查询详情
     */
    public function detail(){
        $carcode = input('carcode');
        $excel = input('excel')?input('excel'):false;
        $jfcar_detail_model = new \app\model\JfcarDetail();
        return $jfcar_detail_model->list(1,10,$carcode,null,null,$excel);
    }


    /**
     * 批量添加车型
     *
     * @return void
     */
    public function addTogather(){
        $carcode = input('carcode');
        $cartype = input('cartype');
        $pro_code = input('pro_code');
        $amount = input('amount');
        $jfcar_model = new \app\model\Jfcar();
        $res = $jfcar_model->addTogather($carcode,$cartype,$pro_code,$amount);
        return $this->JsonCommon($res);
    }

}