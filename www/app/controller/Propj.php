<?php
namespace app\controller;
use app\common\ApiMsg;
use app\middleware\Auth;
use think\facade\Db;
class Propj
{
    protected $middleware = [Auth::class];
    use \app\common\ResponseMsg;
    
    /**
     * 添加信息
     */
    public function add(){
        $erpcode = input('erpcode');
        $pro_code = input('pro_code');
        $pj = input('pj');
        $type = input('type');
        $amount = input('amount');
        if(empty($erpcode )||empty($pro_code)||empty($pj)||empty($type)||empty($amount)){
            return $this->JsonDataArr(ApiMsg::ERR_PARAMS_EMPTY);
        }
        $propj_model = new \app\model\ProPj();
        $res = $propj_model->add($erpcode,$pro_code,$pj,$type,$amount);
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
        $pj = input('pj');
        $type = input('type');
        $excel = input('excel')?input('excel'):false;
        $propj_model = new \app\model\ProPj();
        return $propj_model->list($current,$pageSize,$erpcode,$pro_code,$pj,$type,$excel);
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
        $propj_model = new \app\model\ProPj();
        $res = $propj_model ->del($id);
        return $this->JsonCommon($res);
    }

    /**
     * 配件汇总
     *
     * @return void
     */
    public function gatherpj(){
        $data = input('data');
        if(empty($data)){
            return $this->JsonDataArr(ApiMsg::ERR_PARAMS_EMPTY);
        }
        $propj_model = new \app\model\ProPj();
        $res = $propj_model ->gatherpj($data);
        return $this->JsonSuccess($res);
    }

    /**
     * 查询数据库中不存在的配件erp码
     *
     * @return void
     */
    public function nopj(){
        $data = input('data');
        if(empty($data)){
            return $this->JsonDataArr(ApiMsg::ERR_PARAMS_EMPTY);
        }
        $propj_model = new \app\model\ProPj();
        $res = $propj_model ->nopj($data);
        return $this->JsonSuccess($res);
    }


}