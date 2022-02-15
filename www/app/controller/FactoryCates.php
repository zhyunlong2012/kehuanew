<?php
namespace app\controller;
use app\common\ApiMsg;
use app\middleware\Auth;
class FactoryCates
{
    protected $middleware = [
        Auth::class 	=> ['except' 	=> ['list'] ],
    ];
    use \app\common\ResponseMsg;

    /**
     * 添加分类信息
     */
    public function add(){
        $pid = input('pid')?input('pid'):0;
        $cates_name = input('cates_name');
        $cates_code = input('cates_code');
        $status=input('status')?2:1;
        if(empty($cates_name)){
            return $this->JsonDataArr(ApiMsg::ERR_PARAMS_EMPTY);
        }
        $fac_cates_model = new \app\model\FactoryCates();
        $res = $fac_cates_model->add($pid,$cates_name,$cates_code,$status);
        return $this->JsonCommon($res);
    }

    /**
     * 修改信息
     */
    public function updata(){
        $id = input('id');
        $cates_name = input('cates_name');
        $cates_code = input('cates_code');
        $pid = input('pid');
        if(input('status')===true){
            $status =2;
        }elseif(input('status')===false){
            $status =1;
        }else{
            $status = 3;
        }

        if(empty($id)||empty($cates_name)){
            return $this->JsonDataArr(ApiMsg::ERR_PARAMS_EMPTY);
        }
        
        $fac_cates_model = new \app\model\FactoryCates();
        $res = $fac_cates_model->updata($id,$pid,$cates_name,$cates_code,$status);
        return $this->JsonCommon($res);
    }


    /**
     * 供应商列表
     * @return [type] [description]
     */
    public function list(){
        $current=input('current')?input('current'):1;
        $pageSize=input('pageSize')?input('pageSize'):10;
        $status=input('status')?input('status'):0;
        $cates_name = input('cates_name');
        $cates_code = input('cates_code');
        $excel = input('excel')?input('excel'):false;
        $tree = input('tree')?true:false;
        $pid = input('pid');
        $fac_cates_model = new \app\model\FactoryCates();
        return $fac_cates_model->list($current,$pageSize,$pid,$cates_name,$cates_code,$status,$excel,$tree);
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
        $fac_cates_model = new \app\model\FactoryCates();
        $res = $fac_cates_model ->del($id);
        return $this->JsonCommon($res);
    }

}