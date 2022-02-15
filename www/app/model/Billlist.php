<?php
namespace app\model;
use think\Model;
use think\facade\Db;
use think\model\concern\SoftDelete;
/**
 * 随车单表格
 */
class Billlist extends Model{
    use SoftDelete;
    protected $deleteTime = 'delete_time';
    // 设置字段信息
    protected $schema = [
        'id'          => 'int',
        'number'      => 'string',
        'car_id'      => 'int',
        'status'      => 'int',  //1正常 2随车已验 3出厂已验  9作废
        'create_time' => 'int',
        'update_time' => 'int',
        'delete_time' => 'int',
        'factory_id'  => 'int',
        'customer_id' => 'int'
    ];


    /**
     * 添加随车单
     *
     * @param string $number 随车单号
     * @param string $car_id    
     * @param integer $status
     * @return void
     */
    public function add($number,$car_id,$status=1,$factory_id,$customer_id){
        $res = Billlist::where('number', $number)->findOrEmpty();
        if (!$res->isEmpty()) {
            return false;
        }

        $bill_model = new Billlist();
        $bill_model->number = $number;
        $bill_model->car_id = $car_id;
        $bill_model->status = $status;
        $bill_model->factory_id= $factory_id;
        $bill_model->customer_id = $customer_id;
        addLog('添加随车单,随车单号:'.$number);
        if($bill_model->save() == true){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 返回随车单列表
     *
     * @param integer $current
     * @param integer $pageSize
     * @param integer $car_id
     * @param string $number
     * @param integer $status
     * @return void
     */
    public function list($current,$pageSize,$number,$car_id,$status,$create_time='',$excel=false,$factory_id='',$customer_id=''){
        $map = [];
        if(!empty($number)){$map[]=['number','like','%'.$number.'%'];}
        if(!empty($car_id)){$map[]=['car_id','like','%'.$car_id.'%'];}
        if(!empty($status)){$map[]=['status','=',$status];}
        if(!empty($factory_id)){$map[]=['factory_id','=',$factory_id];}
        if(!empty($customer_id)){$map[]=['customer_id','=',$customer_id];}
        if(!empty($create_time)){
            $between_time = [strtotime($create_time[0]) ,strtotime($create_time[1])];
            $map[] = ['create_time','between',$between_time];
        }
        if($excel){
            $data['data'] = $this->where($map)->order('id','desc')->select();
        }else{
            $data['data'] = $this->where($map)->page($current,$pageSize)->order('id','desc')->select();
        }
        $data['total'] =  $this->where($map)->count();
        $data['current'] = $current;
        $data['pageSize'] = $pageSize;
        $data['success'] = true;
        
        $car_model = new Car();
        $bill_detail_model = new BillDetail();
        $customer_model = new Customer();
        $factory_model = new Factory();
        $profile_model = new Profile();
        $user_model = new User();
        $uid = getUid();
        $a_profile = $profile_model ->where('user_id',$uid)->find();
        $a_user = $user_model ->where('id',$uid)->find();
        foreach ($data['data'] as $key => $value) {
            $car_tmp = $car_model-> where('id',$value['car_id'])->find();
            $data['data'][$key]['car_number'] =$car_tmp?$car_tmp['number']:'注销车辆';
            $data['data'][$key]['driver'] =$car_tmp?$car_tmp['driver']:'注销车辆';
            $data['data'][$key]['a_name'] =$a_profile?$a_profile['nickname']:'未设定';
            $data['data'][$key]['a_tel'] =$a_user?$a_user['tel']:'未设定';
            $data['data'][$key]['b_tel'] =$car_tmp?$car_tmp['tel']:'未录入电话';
            if($car_tmp){
                if($car_tmp['user_id']){
                    $b_user = $user_model ->where('id',$car_tmp['user_id'])->find();
                }else{
                    $data['data'][$key]['b_tel'] ='未设定';
                }
                
            }
            //目的地
            if($value['customer_id']){
                $customer = $customer_model ->where('id',$value['customer_id'])->find();
                $data['data'][$key]['cus_name'] = $customer?$customer['cus_name']:'未设定';
                $data['data'][$key]['cus_address'] = $customer?$customer['address']:'未设定';
                
                $reach_time = time() + 86400*$customer['distance'];
                $data['data'][$key]['reach_time'] = date("Y-m-d",$reach_time);
            }else{
                $data['data'][$key]['cus_name'] ='未设定';
                $data['data'][$key]['cus_address'] = '未设定';
            }

            //目的地
            if($value['factory_id']){
                $factory = $factory_model ->where('id',$value['factory_id'])->find();
                $data['data'][$key]['fac_name'] = $factory?$factory['fac_name']:'未设定';
                $data['data'][$key]['fac_address'] = $factory?$factory['address']:'未设定';
            }else{
                $data['data'][$key]['fac_name'] ='未设定';
                $data['data'][$key]['fac_address'] = '未设定';
            }
            $tmp_total = $bill_detail_model -> where('number',$value['number'])->sum('amount');
            if($tmp_total>0){
                $tmp_check_total = $bill_detail_model -> where('number',$value['number'])->sum('amount_checked');
                $tmp_doorcheck_total = $bill_detail_model -> where('number',$value['number'])->sum('door_checked');
                $data['data'][$key]['progress'] =100*($tmp_check_total+$tmp_doorcheck_total)/(2*$tmp_total);
            }else{
                $data['data'][$key]['progress'] = 0 ;
                // $data['data'][$key]['goal'] = '未设定';
            }
            
            // $data['data'][$key]['progress'] =$value['status']*33.4;
        }

        return json($data);
    }

    /**
     * 作废随车单
     *
     * @param string $code
     * @return void
     */
    public function invalid($id){
        if(empty($id)){return false;}
        $bill = $this->where('id',$id)->findOrEmpty();
        if (!$bill->isEmpty()) {
            // if($bill['status']!=1){
            //     return false;
            // }
            $bill->status = 9;
            if($bill->save() == true){
                addLog('作废随车单,随车单号:'.$bill['number']);
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
     * @param string $car_id
     * @param integer $status
     * @return void
     */
    public function updata($id,$number,$car_id,$status,$factory_id,$customer_id){
        if((empty($number))||(empty($id))){
            return false;
        }
        $res = Billlist::find($id);
        if (empty($res)) {
            return false;
        }
        addLog('修改随车单,随车单号:'.$number);

        $res->number = $number;
        $res->car_id = $car_id;
        $res->status = $status;
        $res->factory_id = $factory_id;
        $res->customer_id = $customer_id;
        if($res->save() == true){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 删除随车单
     * @param  array  $ids [description]
     * @return [boolen]      [description]
     */
    public function del($ids = []){
        $res = $this->where('id','in',$ids)->column('status');
        if(in_array(2,$res)||in_array(3,$res)){
            return false;
        }
        addLog('删除随车单,随车单id:'.implode(',', $ids));
        return Billlist::destroy($ids);
    }

   /**
    * 校验包裹，随车单,随车校验
    *
    * @param [type] $number 随车单
    * @param [type] $code   包裹二维码
    * @return void
    */
    public function checkbill($number,$code){
        // 启动事务
        Db::startTrans();
        try {
            $scan_model = new Scan();
            $scan = $scan_model ->where('code',$code)->where('status','=',3)->lock(true) ->find(); //出库产品才能随车
            if(!$scan){
                return 5;
            }
            $bill_detail_model = new BillDetail();
            $bill_detail = $bill_detail_model
                            ->where('number',$number)
                            ->where('pro_code',$scan['pro_code'])
                            ->where('cus_code',$scan['cus_code'])
                            ->where('fac_code',$scan['fac_code'])
                            ->find();

            if($bill_detail){
                $left = $bill_detail['amount']-$bill_detail['amount_checked'];
                if(($left<=0)||($left<$scan['amount'])){
                    return 2;
                }else{
                    $bill_detail->amount_checked = $bill_detail['amount_checked'] + $scan['amount'];
                    $res1 = $bill_detail->save();
                    $scan->number = $number;
                    $scan->status = 4;
                    $res2 = $scan->save();
                }
            }else{
                return 6;
            }

        
            if($res1&&$res2){
                addLog('校验随车单,单号:'.$number.'包裹号:'.$code);
                // 提交事务
                Db::commit();
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

    /**
    * 验证包裹产品是否在随车单内，是否超过随车单数量
    *不更改数据
    * @param [type] $number 随车单
    * @param [type] $code   包裹二维码
    * @return void
    */
    public function checkinbill($number,$code){
        // 启动事务
        Db::startTrans();
        try {
            $scan_model = new Scan();
            $scan = $scan_model ->where('code',$code)->where('status','<=',3)->lock(true) ->find(); //出库产品才能随车
            if(!$scan){
                return 5;
            }
            $bill_detail_model = new BillDetail();
            $bill_detail = $bill_detail_model
                            ->where('number',$number)
                            ->where('pro_code',$scan['pro_code'])
                            ->where('cus_code',$scan['cus_code'])
                            ->where('fac_code',$scan['fac_code'])
                            ->find();

            if($bill_detail){
                $left = $bill_detail['amount']-$bill_detail['amount_checked'];
                if(($left<=0)||($left<$scan['amount'])){
                    return 2;
                }else{
                    return 1;

                }
            }else{
                return 6;
            }
            
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return 3;
        }
        
    }

    /**
     * 检查随车单是否校验通过
     *
     * @param [type] $number
     * @return void
     */
    public function checkpack($number){
        $bill = $this->where('number',$number)->where('status',1) ->findOrEmpty();
        if($bill->isEmpty()){
            return false;
        }
        $bill_detail_model = new BillDetail();
        $bill_detail = $bill_detail_model
                        ->where('number',$number)
                        ->select();
        if($bill_detail->isEmpty()){
            return false;
        }
     
        foreach($bill_detail as $value){
            if($value['amount']>$value['amount_checked']){
                return false;
            }
        }
        $bill->status = 2;
        $bill->save();
        return true;                 
    }


    /**
    * 校验包裹，随车单,出厂校验
    *
    * @param [type] $number 随车单
    * @param [type] $code   包裹二维码
    * @return void
    */
    public function doorcheckbill($number,$code){
        $scan_model = new Scan();
        $scan = $scan_model ->where('code',$code)->where('status','=',4) ->find(); //随车校验通过的才能出厂
        if(!$scan){
            return 5;
        }
        $bill_detail_model = new BillDetail();
        $bill_detail = $bill_detail_model
                        ->where('number',$number)
                        ->where('pro_code',$scan['pro_code'])
                        ->where('cus_code',$scan['cus_code'])
                        ->where('fac_code',$scan['fac_code'])
                        ->find();

        if($bill_detail){
            $left = $bill_detail['amount']-$bill_detail['door_checked'];
            if(($left<=0)||($left<$scan['amount'])){
                return 2;
            }else{
                $bill_detail->door_checked = $bill_detail['door_checked'] + $scan['amount'];

                // 启动事务
                Db::startTrans();
                try {
                    $res1 = $bill_detail->save();
                    $scan->number = $number;
                    $scan->status = 5;
                    $res2 = $scan->save();
                    if($res1&&$res2){
                        addLog('出厂校验随车单,单号:'.$number.'包裹号:'.$code);
                        // 提交事务
                        Db::commit();
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
            return 4;
        }
        
    }

    /**
     * 出厂 校验随车单
     *
     * @param [type] $number
     * @return void
     */
    public function doorcheck($number){
        $bill = $this->where('number',$number)->where('status',2) ->findOrEmpty();
        if($bill->isEmpty()){
            return false;
        }
        $bill_detail_model = new BillDetail();
        $bill_detail = $bill_detail_model
                        ->where('number',$number)
                        ->select();
        if($bill_detail->isEmpty()){
            return false;
        }
     
        foreach($bill_detail as $value){
            if($value['amount']>$value['door_checked']){
                return false;
            }
        }
        $bill->status = 3;
        $bill->save();
        return true;                 
    }

    /**
     * 随车单撤回
     *
     * @param [type] $number
     * @param [type] $step 撤回步骤
     * @return void
     */
    public function billrollback($number,$step){
        if(empty($number)||empty($step)){
            return 2;
        }
        if(($step!=1)&&($step!=2)){
            return 2;
        }
        $bill = $this->where('number',$number)->findOrEmpty();
        if($bill->isEmpty()){
            return 3;
        }
        if(($bill['status'] >= 3)){
            return 4;
        }
        if($bill['status']!=$step){
            return 4;
        }
        

        // 启动事务
        Db::startTrans();
        try {
            $bill_detail_model = new BillDetail();
            $res1 = $bill_detail_model->detailrollback($number,$step);
            $scan_model = new Scan();
            $res2 = $scan_model->scanrollback($number,$step+2);
            if($res1&&$res2){
                addLog('撤回随车单,单号:'.$number.'撤回到'.$step);
                // 提交事务
                Db::commit();
                return 1;
            }else{
                Db::rollback();
                return 5;
            }
            
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return 5;
        }
        
    }

    /**
    * 校验产品是否在随车单内，如果没超出数量则出库，并检验随车单是否完成
    *
    * @param [type] $number 随车单
    * @param string $amount 出库数量
    * @return void
    */
    public function checkproinbill($number,$pro_code,$fac_code,$cus_code,$position_id,$position_area_code,$amount,$oprate_time=''){
        // 启动事务
        Db::startTrans();
        try {
            $factory_model = new Factory();
            $factory = $factory_model->where('fac_code',$fac_code)->find();
            if(!$factory){ return 2; }
            $customer_model = new Customer();
            $customer = $customer_model->where('cus_code',$cus_code)->find();
            if(!$customer){ return 3; }
            $product_model = new Product();
            $product = $product_model->where('pro_code',$pro_code)->find();
            if(!$product){ return 4; }
            $position_model = new Position();
            $position = $position_model->where('id',$position_id)->find();
            if(!$position){ return 5; }
            $position_area_model = new PositionArea();
            $position_area = $position_area_model->where('position_id',$position_id)->where('code',$position_area_code)->find();
            if(!$position_area){  return 6; }
            $bill_detail_model = new BillDetail();
            $bill_detail = $bill_detail_model
                            ->where('number',$number)
                            ->where('pro_code',$pro_code)
                            ->where('cus_code',$cus_code)
                            ->where('fac_code',$fac_code)
                            ->find();

            if($bill_detail){
                $left = $bill_detail['amount']-$bill_detail['amount_checked']; //未检查数量
                if(($left<=0)||($left<$amount)){
                    return 7;
                }else{
                    $bill_detail->amount_checked = $bill_detail['amount_checked'] + $amount;
                    $res1 = $bill_detail->save();
                    
                    $storage_out_model = new StorageOut();
                    $weight = $amount*$product['weight'];
                    $res2 = $storage_out_model->add($position_id,$product['id'],$amount,'随车',$oprate_time,3,$position_area['id'],'',$customer['id'],$factory['id'],$weight);
                    if($amount==$left){$this->checkpack($number);}
                }
            }else{
                return 8;
            }
            if($res1&&$res2){
                addLog('校验随车单,单号:'.$number.'产品:'.$pro_code.'数量:'.$amount);
                // 提交事务
                Db::commit();
                return 1;
            }else{
                Db::rollback();
                return 9;
            }
            
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return 9;
        }
        
    }

    


}