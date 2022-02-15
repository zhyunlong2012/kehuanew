<?php
namespace app\controller;
use app\common\ApiMsg;
use app\middleware\Auth;
class Customer
{
    protected $middleware = [Auth::class];
    use \app\common\ResponseMsg;

    /**
     * 添加客户
     */
    public function add(){
        $cusname = input('cus_name');
        $cuscode = input('cus_code');
        $cus_cates_id = input('cus_cates_id');
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
        $distance  = input('distance');
        $cus_cates_code = input('cus_cates_code');
        if($cus_cates_code){
            $cus_cates_model = new \app\model\CustomerCates();
            $customer = $cus_cates_model->where('cates_code',$cus_cates_code)->find();
            if($customer){
                $cus_cates_id = $customer['id'];
            }else{
                return $this->JsonDataArr(ApiMsg::ERR_CUSTOMER_CATES_NOTEXIST);
            }
        }

        if(empty($cusname)||empty($cuscode)){
            return $this->JsonDataArr(ApiMsg::ERR_PARAMS_EMPTY);
        }
        
        $cus_model = new \app\model\Customer();
        $res = $cus_model->add($cusname,$cuscode,$employs,$status,$province,$city,$area,$address,$tel,$cus_cates_id,$distance,$upexist);
        return $this->JsonCommon($res);
    }

    /**
     * 修改客户
     */
    public function updata(){
        $id = input('id');
        if(empty($id)){return false;}
        $cusname = input('cus_name');
        $cuscode = input('cus_code');
        $cus_cates_id = input('cus_cates_id');
        // $employs = json_encode(input('employs'),256);
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
        if(input('status')===true){
            $status =2;
        }elseif(input('status')===false){
            $status =1;
        }else{
            $status = 3;
        }

        if(empty($id)||empty($cusname)||empty($cuscode)){
            return $this->JsonDataArr(ApiMsg::ERR_PARAMS_EMPTY);
        }
        $distance  = input('distance');
        $cus_model = new \app\model\Customer();
        $res = $cus_model->updata($id,$cusname,$cuscode,$employs,$status,$province,$city,$area,$address,$tel,$cus_cates_id,$distance);
        return $this->JsonCommon($res);
    }


    /**
     * 客户列表
     * @return [type] [description]
     */
    public function list(){
        $current=input('current')?input('current'):1;
        $pageSize=input('pageSize')?input('pageSize'):10;
        $status=input('status')?input('status'):1;
        $cus_name=input('cus_name');
        $cus_code=input('cus_code');
        $cus_cates_id = input('cus_cates_id');
        $excel = input('excel')?input('excel'):false;
        $cus_model = new \app\model\Customer();
        return $cus_model->list($current,$pageSize,$status,$cus_name,$cus_code,$excel,$cus_cates_id);
    }

    /**
     * 删除客户
     * @return [type] [description]
     */
    public function del(){
        $id = input('id');
        if(empty($id)){
            return $this->JsonDataArr(ApiMsg::ERR_PARAMS_EMPTY);
        }
        $cus_model = new \app\model\Customer();
        $res = $cus_model ->del($id);
        return $this->JsonCommon($res);
    }


}