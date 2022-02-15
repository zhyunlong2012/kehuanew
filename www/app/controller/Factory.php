<?php
namespace app\controller;
use app\common\ApiMsg;
use app\middleware\Auth;
class Factory
{
    protected $middleware = [Auth::class];
    use \app\common\ResponseMsg;

    /**
     * 添加供应商
     */
    public function add(){
        $facname = input('fac_name');
        $faccode = input('fac_code');
        $fac_cates_id = input('fac_cates_id');
        $employs = input('employs')?json_encode(input('employs'),256):null;
        $pro_city_area  = input('pro_city_area');
        if($pro_city_area){
            $province = $pro_city_area[0];
            $city = $pro_city_area[1];
            $area = $pro_city_area[2];
        }else{
            $province = $city = $area = NULL;
        }

        $tel = input('tel');
        $address = input('address');
        $status=input('status')?2:1;
        $upexist = input('upexist')?input('upexist'):1;
        $fac_cates_code = input('fac_cates_code');
        if($fac_cates_code){
            $fac_cates_model = new \app\model\FactoryCates();
            $factory = $fac_cates_model->where('cates_code',$fac_cates_code)->find();
            if($factory){
                $fac_cates_id = $factory['id'];
            }else{
                return $this->JsonDataArr(ApiMsg::ERR_SEARCH_FACTORY_CATES);
            }
        }

        if(empty($facname)||empty($faccode)){
            return $this->JsonDataArr(ApiMsg::ERR_PARAMS_EMPTY);
        }

        $fac_model = new \app\model\Factory();
        $res = $fac_model->add($facname,$faccode,$employs,$status,$province,$city,$area,$address,$tel,$fac_cates_id,$upexist);
        return $this->JsonCommon($res);
    }

    /**
     * 修改供应商
     */
    public function updata(){
        $id = input('id');
        $facname = input('fac_name');
        $faccode = input('fac_code');
        $fac_cates_id = input('fac_cates_id');
        $employs = json_encode(input('employs'),256);
        $pro_city_area  = input('pro_city_area');
        if($pro_city_area){
            $province = $pro_city_area[0];
            $city = $pro_city_area[1];
            $area = $pro_city_area[2];
        }else{
            $province = $city = $area = NULL;
        }

        $tel = input('tel');
        $address = input('address');
        if(input('status')===true){
            $status =2;
        }elseif(input('status')===false){
            $status =1;
        }else{
            $status = 3;
        }
        if(empty($id)||empty($facname)||empty($faccode)){
            return $this->JsonDataArr(ApiMsg::ERR_PARAMS_EMPTY);
        }
        $fac_model = new \app\model\Factory();
        $res = $fac_model->updata($id,$facname,$faccode,$employs,$status,$province,$city,$area,$address,$tel,$fac_cates_id);
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
        $facname=input('fac_name');
        $faccode=input('fac_code');
        $fac_cates_id = input('fac_cates_id');
        $excel = input('excel')?input('excel'):false;
        $fac_model = new \app\model\Factory();
        return $fac_model->list($current,$pageSize,$status,$facname,$faccode,$excel,$fac_cates_id);
    }


    /**
     * 删除供应商
     * @return [type] [description]
     */
    public function del(){
        $id = input('id');
        if(empty($id)){
            return $this->JsonDataArr(ApiMsg::ERR_PARAMS_EMPTY);
        }
        $factory_model = new \app\model\Factory();
        $res = $factory_model ->del($id);
        return $this->JsonCommon($res);
    }

}