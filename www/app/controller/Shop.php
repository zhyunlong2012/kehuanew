<?php
namespace app\controller;
use app\common\ApiMsg;
use app\middleware\Auth;
class Shop
{
    protected $middleware = [Auth::class];
    use \app\common\ResponseMsg;

    /**
     * 添加店铺
     */
    public function add(){
        $shop_name = input('shop_name');
        $shop_code = input('shop_code');
        $introduce = input('introduce');
        $employs = input('employs')?json_encode(input('employs'),256):null;
        $pro_city_area  = input('city');
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

        if(empty($shop_name)||empty($shop_code)){
            return $this->JsonDataArr(ApiMsg::ERR_PARAMS_EMPTY);
        }
        
        $cus_model = new \app\model\Shop();
        $res = $cus_model->add($shop_name,$shop_code,$introduce,$employs,$status,$province,$city,$area,$address,$tel);
        return $this->JsonCommon($res);
    }

    /**
     * 修改店铺
     */
    public function updata(){
        $id = input('id');
        if(empty($id)){return false;}
        $shop_name = input('shop_name');
        $shop_code = input('shop_code');
        $introduce = input('introduce');
        $employs = json_encode(input('employs'),256);
        $pro_city_area  = input('city');
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

        if(empty($id)||empty($shop_name)||empty($shop_code)){
            return $this->JsonDataArr(ApiMsg::ERR_PARAMS_EMPTY);
        }

        $cus_model = new \app\model\Shop();
        $res = $cus_model->updata($id,$shop_name,$shop_code,$introduce,$employs,$status,$province,$city,$area,$address,$tel);
        return $this->JsonCommon($res);
    }


    /**
     * 客户列表
     * @return [type] [description]
     */
    public function list(){
        $current=input('current')?input('current'):1;
        $pageSize=input('pageSize')?input('pageSize'):10;
        $status=input('status');
        $cus_name=input('cus_name');
        $cus_code=input('cus_code');
        $excel = input('excel')?input('excel'):false;
        $cus_model = new \app\model\Shop();
        return $cus_model->list($current,$pageSize,$status,$cus_name,$cus_code,$excel);
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
        $cus_model = new \app\model\Shop();
        $res = $cus_model ->del($id);
        return $this->JsonCommon($res);
    }

    
    /**
     * 授权店铺列表
     *
     * @return void
     */
    public function adminlist(){
        $uid = getUid();
        $shop_model = new \app\model\Shop();
        $res = $shop_model->isShopAdmin($uid);
        // print_r($res);
        return $this->JsonSuccess($res);
    }

}