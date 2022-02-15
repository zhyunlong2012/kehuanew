<?php
namespace app\model;
use think\Model;
use think\facade\Db;
use think\model\concern\SoftDelete;
/**
 * 购物车表格
 */
class ShopCar extends Model{
    use SoftDelete;
    protected $deleteTime = 'delete_time';
    // 设置字段信息
    protected $schema = [
        'id'          => 'int',
        'number'        => 'string',
        // 'shop_id'     =>'int',
        'user_id'      => 'int',  //个人购物
        'buy_shop_id'      => 'int', //门店购物
        'money'       => 'string', 
        'status'      => 'int',  //1下单 2已付款 3已发货 4收货 9作废
        'create_time' => 'int',
        'update_time' => 'int',
        'delete_time' => 'int',
    ];


    /**
     * 添加购物车
     *
     * @param string $number 购物车号
     * @param string $user_id    
     * @param integer $status
     * @return void
     */
    public function add($number,$user_id,$buy_shop_id,$money,$status=1){
        $res = ShopCar::where('number', $number)->findOrEmpty();
        if (!$res->isEmpty()) {
            return false;
        }

        $shop_car_model = new ShopCar();
        $shop_car_model->number = $number;
        $shop_car_model->user_id = $user_id;
        $shop_car_model->buy_shop_id = $buy_shop_id;
        $shop_car_model->money = $money;
        $shop_car_model->status = $status;
        addLog('添加购物车,购物车号:'.$number);
        if($shop_car_model->save() == true){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 返回购物车列表
     *
     * @param integer $current
     * @param integer $pageSize
     * @param integer $user_id
     * @param string $number
     * @param integer $status
     * @return void
     */
    public function list($current,$pageSize,$number,$user_id,$buy_shop_id,$status,$excel=false){
        $map = [];
        $map1 = [];
        $map2 = [];
        if(!empty($number)){$map1[] = $map2[] = ['number','like','%'.$number.'%'];}
        if(!empty($status)){$map1[] = $map2[]=['status','=',$status];}
        if(!empty($user_id)){$map1[]=['user_id','=',$user_id];}
        if(!empty($buy_shop_id)){$map2[]=['buy_shop_id','in',$buy_shop_id];}
        if(count($map1)>0 && count($map2)>0){
            $map = [$map1,$map2];
            if($excel){
                $data['data'] = $this->whereOr($map)->order('id','desc')->select();
            }else{
                $data['data'] = $this->whereOr($map)->page($current,$pageSize)->order('id','desc')->select();
            }
            $data['total'] =  $this->whereOr($map)->count();
        }else{
            if(count($map2)>0){
                $map = $map2;
            }else{
                $map = $map1;
            }
            if($excel){
                $data['data'] = $this->where($map)->order('id','desc')->select();
            }else{
                $data['data'] = $this->where($map)->page($current,$pageSize)->order('id','desc')->select();
            }
            $data['total'] =  $this->where($map)->count();
        }
        
        $data['current'] = $current;
        $data['pageSize'] = $pageSize;
        $data['success'] = true;
        
        $user_model = new User();
        $shop_model = new Shop();
        foreach ($data['data'] as $key => $value) {
            $user_tmp = $user_model-> where('id',$value['user_id'])->find();
            $data['data'][$key]['user_name'] =$user_tmp?$user_tmp['username']:'注销用户';
            if($value['buy_shop_id']){
                $shop_tmp = $shop_model-> where('id',$value['buy_shop_id'])->find();
                $data['data'][$key]['buy_shop_name'] =$shop_tmp?$shop_tmp['shop_name']:'注销门店';
            }else{
                $data['data'][$key]['buy_shop_name'] = '个人购买';
            }
            
        }

        return json($data);
    }

    /**
     * 作废购物车
     *
     * @param string $code
     * @return void
     */
    public function invalid($id){
        if(empty($id)){return false;}
        $shop_car = $this->where('id',$id)->findOrEmpty();
        if (!$shop_car->isEmpty()) {
            if($shop_car['status']!=1){
                return false;
            }
            $shop_car->status = 9;
            if($shop_car->save() == true){
                addLog('作废购物车,购物车号:'.$shop_car['number']);
                return true;
            }else{
                return false;
            }
        }else{
            return false;
        }
    }

    /**
     * 修改信息
     *
     * @param [type] $id
     * @param [type] $number
     * @param string $user_id
     * @param integer $status
     * @return void
     */
    public function updata($id,$number,$user_id,$buy_shop_id,$status){
        if((empty($number))||(empty($id))){
            return false;
        }
        $res = ShopCar::find($id);
        if (empty($res)) {
            return false;
        }
        addLog('修改购物车,购物车号:'.$number);

        $res->number = $number;
        $res->user_id = $user_id;
        $res->buy_shop_id = $buy_shop_id;
        $res->status = $status;
        if($res->save() == true){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 删除购物车
     * @param  array  $ids [description]
     * @return [boolen]      [description]
     */
    public function del($ids = []){
        $res = $this->where('id','in',$ids)->column('status');
        if(!in_array(1,$res)){
            return false;
        }
        addLog('删除购物车,购物车id:'.implode(',', $ids));
        return ShopCar::destroy($ids);
    }
    
    /**
     * 计算购物车货款
     *
     * @param [type] $number
     * @return void
     */
    public function calShopCarMoney($number){
        $shop_car_detail_model = new ShopCarDetail();
        $money = $shop_car_detail_model->where('number',$number)->sum('money');
        $res = ShopCar::where('number', $number)->findOrEmpty();
        if ($res->isEmpty()) {
            return false;
        }else{
            if($res['status']!=1){
                return false;
            }
        }

        $res->money = $money;
        if($res->save() == true){
            return true;
        }else{
            return false;
        }
    }


   

}