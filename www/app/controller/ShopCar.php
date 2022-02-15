<?php
namespace app\controller;
use app\common\ApiMsg;
use app\middleware\Auth;
class ShopCar
{
    protected $middleware = [Auth::class];
    use \app\common\ResponseMsg;

    /**
     * 添加 购物车信息
     */
    public function add(){
        $number = input('number');
        $buy_shop_id = input('buy_shop_id');
        $money = 0;
        $user_id = getUid();
        $status=1;
        if(empty($number)||empty($buy_shop_id)){
            return $this->JsonDataArr(ApiMsg::ERR_PARAMS_EMPTY);
        }

        $shop_car_model = new \app\model\ShopCar();
        $res = $shop_car_model->add($number,$user_id,$buy_shop_id,$money,$status);
        return $this->JsonCommon($res);
    }

    /**
     * 添加 购物车详情
     */
    public function adddetail(){
        $number = input('number');
        $shop_id = input('shop_id');
        $user_id = getUid();
        $buy_shop_id = input('buy_shop_id');
        $sale_pro_id = input('sale_pro_id');
        $amount=input('amount');
        if(empty($number)||empty($amount)){
            return $this->JsonDataArr(ApiMsg::ERR_PARAMS_EMPTY);
        }

        
        $shop_code = input('shop_code');
        if($shop_code){
            $shop_model = new \app\model\Shop();
            $shop=$shop_model ->where('shop_code',$shop_code)->findOrEmpty();
            if($shop->isEmpty()){
                return $this->JsonDataArr(ApiMsg::ERR_SHOP);
            }else{
                $shop_id = $shop['id'];
            }
        }
        //表格采购
        $excel = input('excel');
        if($excel){

            $fac_code = input('fac_code');
            $pro_code = input('pro_code');
            if($fac_code && $pro_code){
                $factory_model = new \app\model\Factory();
                $factory = $factory_model ->where('fac_code',$fac_code)->findOrEmpty();
                if($factory->isEmpty()){
                    return $this->JsonDataArr(ApiMsg::ERR_SEARCH_FACTORY);
                }else{
                    $product_model = new \app\model\Product();
                    $product = $product_model ->where('factory_id',$factory['id'])->where('pro_code',$pro_code)->findOrEmpty();
                    if($product->isEmpty()){
                        return $this->JsonDataArr(ApiMsg::ERR_SEARCH_PRODUCT);
                    }else{
                        $sale_pro_model = new \app\model\SalePro();
                        $sale_pro = $sale_pro_model ->where('shop_id',$shop_id)->where('product_id',$product['id'])->findOrEmpty();
                        if($sale_pro->isEmpty()){
                            return $this->JsonDataArr(ApiMsg::ERR_SALE_PRO);
                        }else{
                            $sale_pro_id = $sale_pro['id'];
                        }
                    }
                }
            }

        }
        $shop_car_detail_model = new \app\model\ShopCarDetail();
        $res = $shop_car_detail_model->add($number,$user_id,$shop_id,$buy_shop_id,$sale_pro_id,$amount);
        switch($res){
            case 2:
                return $this->JsonDataArr(ApiMsg::ERR_SHOP_CAR_NUMBER);
                break;
            case 3:
                return $this->JsonDataArr(ApiMsg::ERR_SHOP_CAR_STATE);
                break;
            case 4:
                return $this->JsonDataArr(ApiMsg::ERR_USER);
                break;
            case 5:
                return $this->JsonDataArr(ApiMsg::ERR_SHOP);
                break;
            case 6:
                return $this->JsonDataArr(ApiMsg::ERR_SALE_PRO);
                break;
            case 7:
                return $this->JsonDataArr(ApiMsg::ERR_SAVE);
                break;
            default:
                return $this->JsonSuccess();
        }
    }

    /**
     * 修改 购物车详情
     */
    public function moddetail(){
        $id = input('id');
        $number = input('number');
        $shop_id = input('shop_id');
        $user_id = getUid();
        $buy_shop_id = input('buy_shop_id');
        $sale_pro_id = input('sale_pro_id');
        $amount=input('amount');
        $shop_code = input('shop_code');
        if($shop_code){
            $shop_model = new \app\model\Shop();
            $shop=$shop_model ->where('shop_code',$shop_code)->findOrEmpty();
            if($shop->isEmpty()){
                return $this->JsonDataArr(ApiMsg::ERR_SHOP);
            }else{
                $shop_id = $shop['id'];
            }
        }
        if(empty($number)||empty($shop_id)||empty($amount)){
            return $this->JsonDataArr(ApiMsg::ERR_PARAMS_EMPTY);
        }
        $shop_car_detail_model = new \app\model\ShopCarDetail();
        $res = $shop_car_detail_model->updata($id,$number,$user_id,$shop_id,$buy_shop_id,$sale_pro_id,$amount);
        switch($res){
            case 2:
                return $this->JsonDataArr(ApiMsg::ERR_SHOP_CAR_NUMBER);
                break;
            case 3:
                return $this->JsonDataArr(ApiMsg::ERR_SHOP_CAR_STATE);
                break;
            case 4:
                return $this->JsonDataArr(ApiMsg::ERR_USER);
                break;
            case 5:
                return $this->JsonDataArr(ApiMsg::ERR_SHOP);
                break;
            case 6:
                return $this->JsonDataArr(ApiMsg::ERR_SALE_PRO);
                break;
            case 7:
                return $this->JsonDataArr(ApiMsg::ERR_SHOP_CAR_DETAIL);
                break;
            case 8:
                return $this->JsonDataArr(ApiMsg::ERR_SAVE);
                break;
            default:
                return $this->JsonSuccess();
        }
    }

    /**
     * 查询 购物车详情'
     */
    public function detail(){
        $current=input('current')?input('current'):1;
        $pageSize=input('pageSize')?input('pageSize'):10;
        $number = input('number');
        $user_id = input('user_id');
        $buy_shop_id = input('buy_shop_id');
        $sale_shop_id = input('sale_shop_id');
        $excel = input('excel')?input('excel'):false;
        $shop_car_detail_model = new \app\model\ShopCarDetail();
        return $shop_car_detail_model->list($current,$pageSize,$number,$user_id,$buy_shop_id,$sale_shop_id,$excel);
    }



    /**
     *  购物车列表
     * @return [type] [description]
     */
    public function list(){
        $current=input('current')?input('current'):1;
        $pageSize=input('pageSize')?input('pageSize'):10;
        $number = input('number');
        $user_id = getUid();
        $buy_shop_id = input('buy_shop_id');
        $status = input('status');
        $excel = input('excel')?input('excel'):false;
        $shop_car_model = new \app\model\ShopCar();
        return $shop_car_model->list($current,$pageSize,$number,$user_id,$buy_shop_id,$status,$excel);
    }


    /**
     * 删除 购物车
     * @return [type] [description]
     */
    public function del(){
        $id = input('id');
        if(empty($id)){
            return $this->JsonDataArr(ApiMsg::ERR_PARAMS_EMPTY);
        }
        $shop_car_model = new \app\model\ShopCar();
        $res = $shop_car_model ->del($id);
        return $this->JsonCommon($res);
    }

    
    /**
     * 删除 购物车产品
     * @return [type] [description]
     */
    public function deldetail(){
        $id = input('id');
        if(empty($id)){
            return $this->JsonDataArr(ApiMsg::ERR_PARAMS_EMPTY);
        }
        $shop_car_detail_model = new \app\model\ShopCarDetail();
        $res = $shop_car_detail_model ->del($id);
        switch($res){
            case 2:
                return $this->JsonDataArr(ApiMsg::ERR_SHOP_CAR);
            case 3:
                return $this->JsonDataArr(ApiMsg::ERR_SHOP_CAR_DETAIL);
            case 4:
                return $this->JsonDataArr(ApiMsg::ERR_SHOP_CAR_DETAIL);
            case 1:
                return $this->JsonSuccess();
                
        }
    }

    

}