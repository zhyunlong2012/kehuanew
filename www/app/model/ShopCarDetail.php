<?php
namespace app\model;
use think\Model;
use think\facade\Db;
/**
 * 购物车详情表格
 */
class ShopCarDetail extends Model{
    // 设置字段信息
    protected $schema = [
        'id'          => 'int',
        'number'      => 'string',
        'user_id'    => 'int', //个人购买
        'shop_id'    => 'int',  //销售产品店铺
        'buy_shop_id'   => 'int',  //购买产品店铺
        'sale_pro_id'   => 'int', 
        'amount'      => 'int', //数量
        'money'      => 'string' //金额
    ];


    /**
     * 添加购物车详情
     *
     * @param string $number 购物车号
     * @param string $car_id    
     * @param integer $status
     * @return void
     */
    public function add($number,$user_id,$shop_id,$buy_shop_id,$sale_pro_id,$amount){
        $shop_car = ShopCar::where('number', $number)->findOrEmpty();
        if (!$shop_car->isEmpty()) {
            if($shop_car['status']!=1){
                return 2;
            }
        }else{
            return 3;
        }

        $user = User::where('id', $user_id)->findOrEmpty();
        if ($user->isEmpty()) {
            return 4;
        }

        $shop = Shop::where('id',$shop_id)->where('status',1)->findOrEmpty();
        if ($shop->isEmpty()) {
            return 5;
        }

        $buy_shop = Shop::where('id',$buy_shop_id)->where('status',1)->findOrEmpty();
        if ($buy_shop->isEmpty()) {
            return 5;
        }
        
        $sale_pro = SalePro::where('id',$sale_pro_id)->where('is_sale',1)->findOrEmpty();
        if ($sale_pro->isEmpty()) {
            return 6;
        }

        $shop_car_detail_model = new ShopCarDetail();

        $shop_car_detail_model->number = $number;
        $shop_car_detail_model->user_id = $user_id;
        $shop_car_detail_model->shop_id = $shop_id;
        $shop_car_detail_model->buy_shop_id = $buy_shop_id;
        $shop_car_detail_model->sale_pro_id = $sale_pro_id;
        $shop_car_detail_model->amount = $amount;
        $shop_car_detail_model->money = $amount*$sale_pro['price'];
        // 启动事务
        Db::startTrans();
        try {
            $res1 = $shop_car_detail_model->save();
            $shop_car_model = new ShopCar();
            $res2 = $shop_car_model->calShopCarMoney($number);
            if($res1&&$res2){
                // 提交事务
                Db::commit();
                addLog('添加随车详情,购物车号:'.$number.'产品id:'.$sale_pro_id.'数量'.$amount);
                return 1;
            }else{
                Db::rollback();
                return 7;
            }
            
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return 7;
        }
    }

    /**
     * 修改信息
     *
     * @param [type] $id
     * @param [type] $number
     * @param string $car_id
     * @param integer $status
     * @return void
     */
    public function updata($id,$number,$user_id,$shop_id,$buy_shop_id,$sale_pro_id,$amount){
        $shop_car = ShopCar::where('number', $number)->findOrEmpty();
        if (!$shop_car->isEmpty()) {
            if($shop_car['status']!=1){
                return 2;
            }
        }else{
            return 3;
        }
        $user = User::where('id', $user_id)->findOrEmpty();
        if ($user->isEmpty()) {
            return 4;
        }

        $shop = Shop::where('id',$shop_id)->where('status',1)->findOrEmpty();
        if ($shop->isEmpty()) {
            return 5;
        }

        $buy_shop = Shop::where('id',$buy_shop_id)->where('status',1)->findOrEmpty();
        if ($buy_shop->isEmpty()) {
            return 5;
        }

        $sale_pro = SalePro::where('id',$sale_pro_id)->where('is_sale',1)->findOrEmpty();
        if ($sale_pro->isEmpty()) {
            return 6;
        }

        $res = ShopCarDetail::find($id);
        if (empty($res)) {
            return 7;
        }
        addLog('修改购物车内容,购物车号:'.$number);

        $res->number = $number;
        $res->number = $number;
        $res->user_id = $user_id;
        $res->shop_id = $shop_id;
        $res->buy_shop_id = $buy_shop_id;
        $res->sale_pro_id = $sale_pro_id;
        $res->amount = $amount;
        $res->money = $amount*$sale_pro['price'];
         // 启动事务
         Db::startTrans();
         try {
             $res1 = $res->save();
             $shop_car_model = new ShopCar();
             $res2 = $shop_car_model->calShopCarMoney($number);
             if($res1&&$res2){
                 // 提交事务
                 Db::commit();
                 addLog('添加随车详情,购物车号:'.$number.'产品id:'.$sale_pro_id.'数量'.$amount);
                 return 1;
             }else{
                 Db::rollback();
                 return 8;
             }
             
         } catch (\Exception $e) {
             // 回滚事务
             Db::rollback();
             return 8;
         }
    }

    /**
     * 返回购物车列表
     *
     * @param string $number
     * @return void
     */
    public function list($current,$pageSize,$number,$user_id,$buy_shop_id,$sale_shop_id,$excel=false){
        $map = [];
        if(!empty($number)){$map[]=['number','=',$number];}
        if(!empty($user_id)){$map[]=['user_id','=',$user_id];}
        if(!empty($buy_shop_id)){$map[]=['buy_shop_id','=',$buy_shop_id];}
        if(!empty($sale_shop_id)){$map[]=['shop_id','=',$sale_shop_id];}
        if($excel){
            $data['data'] = $this->where($map)->order('id','desc')->select();
        }else{
            $data['data'] = $this->where($map)->page($current,$pageSize)->order('id','desc')->select();
        }
        $user_model = new User();
        $shop_model = new Shop();
        $sale_pro_model = new SalePro();
        $product_model = new Product();
        $factory_model = new Factory();
        foreach ($data['data'] as $key => $value) {
            $tmp_user = $user_model-> where('id',$value['user_id'])->find();
            $data['data'][$key]['user_name']= $tmp_user?$tmp_user['username']:'注销用户';
            $tmp_shop = $shop_model-> where('id',$value['shop_id'])->find();
            $data['data'][$key]['shop_code']= $tmp_shop?$tmp_shop['shop_code']:'注销门店';
            $data['data'][$key]['shop_name']= $tmp_shop?$tmp_shop['shop_name']:'注销门店';
            $tmp_sale_pro = $sale_pro_model-> where('id',$value['sale_pro_id'])->find();
            if($tmp_sale_pro){
                $data['data'][$key]['pro_title']=$tmp_sale_pro['title'];
                $data['data'][$key]['pro_pic']=$tmp_sale_pro['pic'];
                $data['data'][$key]['pro_price']=$tmp_sale_pro['price'];
                $tmp_pro = $product_model-> where('id',$tmp_sale_pro['product_id'])->find();
                if($tmp_pro){
                    $data['data'][$key]['pro_code']=$tmp_pro['pro_code'];
                    $tmp_fac = $factory_model-> where('id',$tmp_pro['factory_id'])->find();
                    $data['data'][$key]['fac_code']= $tmp_fac?$tmp_fac['fac_code']:'注销厂家';
                }else{
                    $data['data'][$key]['pro_code']='注销产品';
                    $data['data'][$key]['fac_code']='注销厂家';
                }
                
            }else{
                $data['data'][$key]['pro_title']='注销产品';
                $data['data'][$key]['pro_code']= '注销产品';
                $data['data'][$key]['pro_price']= '注销产品';
                $data['data'][$key]['pro_pic']= '注销产品';
                $data['data'][$key]['fac_code']='注销厂家';
            }
            
            // $data['data'][$key]['pro_title']= $tmp_sale_pro?$tmp_sale_pro['title']:'注销产品';
        }
        $data['total'] =  $this->where($map)->count();
        $data['current'] = 0;
        $data['pageSize'] = 1;
        $data['success'] = true;
        return json($data);
    }

    /**
     * 删除
     * @param  int  $ids [description]
     * @return [boolen]      [description]
     */
    public function del($id){
        $detail = $this->where('id',$id)->findOrEmpty();
        if(!$detail->isEmpty()) {
            $detail = ShopCar::where('number',$detail['number'])->findOrEmpty();
            if(!$detail->isEmpty()) {
                if($detail['status']!=1){
                    return 2;
                }else{
                    // 启动事务
                    Db::startTrans();
                    try {
                        ShopCarDetail::destroy($id);
                        $shop_car_model = new ShopCar();
                        $res = $shop_car_model->calShopCarMoney($detail['number']);
                        if($res){
                            // 提交事务
                            Db::commit();
                            addLog('删除购物车详情id:'.$id);
                            return 1;
                        }else{
                            Db::rollback();
                            return 3;
                        }
                        
                    } catch (\Exception $e) {
                        // 回滚事务
                        Db::rollback();
                        return 3;
                    }
                }
            }else{
                return 3;
            }
        }else{
            return 4;
        }
        
    }


}