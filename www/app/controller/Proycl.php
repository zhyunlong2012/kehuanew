<?php
namespace app\controller;
use app\common\ApiMsg;
use app\middleware\Auth;
use think\facade\Db;
class Proycl
{
    protected $middleware = [Auth::class];
    use \app\common\ResponseMsg;
    
    /**
     * 添加信息
     */
    public function add(){
        $erpcode = input('erpcode');
        $pro_code = input('pro_code');
        $ycl = input('ycl');
        $weight = input('weight');
        $yclcode = input('yclcode');
        if(empty($erpcode )||empty($pro_code)||empty($ycl )||empty($weight)||empty($yclcode)){
            return $this->JsonDataArr(ApiMsg::ERR_PARAMS_EMPTY);
        }
        $proycl_model = new \app\model\ProYcl();
        $res = $proycl_model->add($erpcode,$pro_code,$ycl,$weight,$yclcode);
        return $this->JsonCommon($res);
    }


    /**
     * 车型列表
     * @return [type] [description]
     */
    public function list(){
        $current=input('current')?input('current'):1;
        $pageSize=input('pageSize')?input('pageSize'):10;
        $erpcode = input('erpcode');
        $pro_code = input('pro_code');
        $ycl = input('ycl');
        $yclcode = input('yclcode');
        $excel = input('excel')?input('excel'):false;
        $proycl_model = new \app\model\ProYcl();
        return $proycl_model->list($current,$pageSize,$erpcode,$pro_code,$ycl,$yclcode,$excel);
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
        $proycl_model = new \app\model\ProYcl();
        $res = $proycl_model ->del($id);
        return $this->JsonCommon($res);
    }

    /**
     * 原材料汇总
     *
     * @return void
     */
    public function gatherycl(){
        $data = input('data');
        if(empty($data)){
            return $this->JsonDataArr(ApiMsg::ERR_PARAMS_EMPTY);
        }
        $proycl_model = new \app\model\ProYcl();
        $res = $proycl_model ->gatherycl($data);
        return $this->JsonSuccess($res);
    }

    /**
     * 查询数据库中不存在的原材料erp码
     *
     * @return void
     */
    public function noycl(){
        $data = input('data');
        if(empty($data)){
            return $this->JsonDataArr(ApiMsg::ERR_PARAMS_EMPTY);
        }
        $proycl_model = new \app\model\ProYcl();
        $res = $proycl_model ->noycl($data);
        return $this->JsonSuccess($res);
    }


}