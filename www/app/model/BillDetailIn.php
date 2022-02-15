<?php
namespace app\model;
use think\Model;
use think\facade\Db;
/**
 * 随车单详情入库表格，用于入库时比对数据
 */
class BillDetailIn extends Model{
    // 设置字段信息
    protected $schema = [
        'id'          => 'int',
        'number'      => 'string',
        'pro_code'    => 'string', 
        'fac_code'   =>'string',
        'cus_code'   => 'string',
        'other_code'   => 'string',
        'amount'      => 'int',
        'amount_checked' =>'int', //随车校验数量
    ];

    /**
     * 返回随车单列表
     *
     * @param string $number
     * @return void
     */
    public function list($number,$pro_code=''){
        $map = [];
        if(!empty($number)){$map[]=['number','like',$number];}
        if(!empty($pro_code)){$map[]=['pro_code','=',$pro_code];}
        $data['data'] = $this->where($map)->order('id','desc')->select();
        $data['total'] =  $this->where($map)->count();
        $data['current'] = 0;
        $data['pageSize'] = 1;
        $data['success'] = true;
        return json($data);
    }

/**
    * 校验包裹，随车单,随车校验入库
    *
    * @param [type] $number 随车单
    * @param [type] $code   包裹二维码
    * @return void
    */
    public function checkbillinsto($number,$code){
        // 启动事务
        Db::startTrans();
        try {
            $scan_model = new Scan();
            $scan = $scan_model->where('code',$code)->lock(true) ->find();
            if(!$scan){ return 5;}
            if($scan['status']==2){return 4;}
            $bill_detail_model = new BillDetailIn();
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
                    $res = $bill_detail->save();
                }
            }else{
                return 6;
            }
            
            if($res){
                addLog('随车单入库,单号:'.$number.'包裹号:'.$code);
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
     * 检查随车单是否校验通过
     *
     * @param [type] $number
     * @return void
     */
    public function checkpass($number){
        $bill_detail = $this->where('number',$number) ->select();
        if($bill_detail->isEmpty()){
            return false;
        }
     
        foreach($bill_detail as $value){
            if($value['amount']>$value['amount_checked']){
                return false;
            }
        }
        return true;                 
    }

    /**
     * 随车单撤回
     *
     * @param [type] $number
     * @return void
     */
    public function detailrollback($number){
        if(empty($number)){
            return false;
        }
        $detail = $this->where('number',$number)->select();
        if(count($detail)<=0){
            return false;
        }
        $data = [];
        foreach($detail as $value){
            $data[]=[
                'id' =>$value['id'],
                'amount_checked'=>0
            ];
        }
        
        $res = $this->saveAll($data);
        if($res){
            return true;
        }else{
            return false;
        }
    }
 
    /**
    * 校验产品是否在随车单内，如果没超出数量则入库，并检验随车单是否完成
    *
    * @param [type] $number 随车单
    * @param string $amount入库数量
    * @return void
    */
    public function billproinsto($number,$pro_code,$fac_code,$cus_code,$position_id,$position_area_code,$amount,$oprate_time=''){
        // 启动事务
        Db::startTrans();
        try {
             $map = [['number','=',$number]];
             if($fac_code){$map[]=['fac_code','=',$fac_code];}
            if($cus_code){
                $customer_model = new Customer();
                $customer = $customer_model->where('cus_code',$cus_code)->find();
                if(!$customer){ return 3; }else{$map[]=['cus_code','=',$cus_code];}
            }
            $product_model = new Product();
            $product = $product_model->where('pro_code',$pro_code)->find();
            if(!$product){ return 4; }else{$map[]=['pro_code','=',$pro_code];}
            $position_model = new Position();
            $position = $position_model->where('id',$position_id)->find();
            if(!$position){ return 5; }
            $position_area_model = new PositionArea();
            $position_area = $position_area_model->where('position_id',$position_id)->where('code',$position_area_code)->find();
            if(!$position_area){  return 6; }
            $bill_detail_model = new BillDetailIn();
            $bill_detail = $bill_detail_model ->where($map) ->find();

            if($bill_detail){
                $left = $bill_detail['amount']-$bill_detail['amount_checked']; //未检查数量
                if(($left<=0)||($left<$amount)){
                    return 7;
                }else{
                    
                    if(!$fac_code){ $fac_code= $bill_detail['fac_code']; }
                    $factory_model = new Factory();
                    $factory = $factory_model->where('fac_code',$fac_code)->find();
                    if(!$factory){ return 2; }

                    if(!$cus_code){ $cus_code = $bill_detail['cus_code'];}
                    $customer_model = new Customer();
                    $customer = $customer_model->where('cus_code',$cus_code)->find();
                    if(!$customer){ return 3; }

                    $bill_detail->amount_checked = $bill_detail['amount_checked'] + $amount;
                    $res1 = $bill_detail->save();
                    
                    $storage_in_model = new StorageIn();
                    $weight = $amount*$product['weight'];
                    $res2 = $storage_in_model->add($position_id,$product['id'],$amount,'随车',$oprate_time,3,$position_area['id'],'',$customer['id'],$factory['id'],$weight);
                    // if($amount==$left){$this->checkpass($number);}
                }
            }else{
                return 8;
            }
            if($res1&&$res2){
                addLog('随车单入库:'.$number.'产品:'.$pro_code.'数量:'.$amount);
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