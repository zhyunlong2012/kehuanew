<?php
namespace app\controller;
use app\common\ApiMsg;
use app\middleware\Auth;
class Finance
{
    protected $middleware = [Auth::class];
    use \app\common\ResponseMsg;

    /**
     * 添加流水信息
     */
    public function add(){
        $user_id = input('number');
        $driver = input('driver');
        $uid = input('uid');
        $status=input('status')?2:1;
        if(empty($number)||empty($driver)){
            return $this->JsonDataArr(ApiMsg::ERR_PARAMS_EMPTY);
        }
        $car_model = new \app\model\Car();
        $res = $car_model->add($number,$driver,$uid,$status);
        return $this->JsonCommon($res);
    }

    /**
     * 修改流水信息
     */
    public function updata(){
        $id = input('id');
        $number = input('number');
        $driver = input('driver');
        $uid = input('uid');
        if(input('status')===true){
            $status =2;
        }elseif(input('status')===false){
            $status =1;
        }else{
            $status = 3;
        }

        if(empty($id)||empty($number)||empty($driver)){
            return $this->JsonDataArr(ApiMsg::ERR_PARAMS_EMPTY);
        }
        
        $car_model = new \app\model\Car();
        $res = $car_model->updata($id,$number,$driver,$uid,$status);
        return $this->JsonCommon($res);
    }


    /**
     * 流水列表
     * @return [type] [description]
     */
    public function list(){
        $current=input('current')?input('current'):1;
        $pageSize=input('pageSize')?input('pageSize'):10;
        $status=input('status')?input('status'):0;
        $number = input('number');
        $driver = input('driver');
        $excel = input('excel')?input('excel'):false;
        $car_model = new \app\model\Car();
        return $car_model->list($current,$pageSize,$number,$driver,$status,$excel);
    }


    /**
     * 删除流水
     * @return [type] [description]
     */
    public function del(){
        $id = input('id');
        if(empty($id)){
            return $this->JsonDataArr(ApiMsg::ERR_PARAMS_EMPTY);
        }
        $car_model = new \app\model\Car();
        $res = $car_model ->del($id);
        return $this->JsonCommon($res);
    }

}