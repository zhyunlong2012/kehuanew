<?php
namespace app\model;
use think\Model;

/**
 * 随车单详情表格
 */
class BillDetail extends Model{
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
        'door_checked'  => 'int' //出厂校验数量
    ];


    /**
     * 添加随车单详情
     *
     * @param string $number 随车单号
     * @param string $car_id    
     * @param integer $status
     * @return void
     */
    public function add($number,$fac_code,$cus_code,$pro_code,$other_code,$amount){
        if(empty($number)){
            return 2;
        }

        $billlist = Billlist::where('number', $number)->findOrEmpty();
        if (!$billlist->isEmpty()) {
            if($billlist['status']!=1){
                return 3;
            }
        }else{
            return 4;
        }

        $factory = Factory::where('fac_code', $fac_code)->findOrEmpty();
        if ($factory->isEmpty()) {
            return 5;
        }

        if($pro_code){
            $product = Product::where('pro_code',$pro_code)->find();
        }else{
            $product = Product::where('other_code',$other_code)->find();
        }
        
        if (!$product) {
            return 6;
        }else{
            $pro_code = $product['pro_code'];
            $other_code = $product['other_code'];
        }

        $customer = Customer::where('cus_code', $cus_code)->findOrEmpty();
        if ($customer->isEmpty()) {
            return 7;
        }

        $bill_detail_model = new BillDetail();

        $detail = $bill_detail_model
                    ->where('number',$number)
                    ->where('pro_code',$product['pro_code'])
                    ->where('cus_code',$cus_code)
                    ->findOrEmpty();
        if(!$detail->isEmpty()){
            return 9;
        }
        $bill_detail_model->number = $number;
        $bill_detail_model->pro_code = $product['pro_code'];
        $bill_detail_model->other_code = $other_code;
        $bill_detail_model->fac_code = $fac_code;
        $bill_detail_model->cus_code = $cus_code;
        $bill_detail_model->amount = $amount;
        $bill_detail_model->amount_checked = 0;
        $bill_detail_model->door_checked = 0;
        addLog('添加随车详情,随车单号:'.$number.'产品编码:'.$other_code.''.$amount);
        if($bill_detail_model->save() == true){
            return 1;
        }else{
            return 8;
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
    public function updata($id,$number,$fac_code,$cus_code,$pro_code,$other_code,$amount){
        $billlist = Billlist::where('number', $number)->find();
        if ($billlist) {
            if($billlist['status']!=1){
                return 3;
            }
        }else{
            return 4;
        }

        $factory = Factory::where('fac_code', $fac_code)->findOrEmpty();
        if ($factory->isEmpty()) {
            return 5;
        }
        if($pro_code){
            $product = Product::where('pro_code',$pro_code)->find();
        }else{
            $product = Product::where('other_code',$other_code)->find();
        }
        
        if (!$product) {
            return 6;
        }else{
            $pro_code = $product['pro_code'];
            $other_code = $product['other_code'];
        }

        $customer = Customer::where('cus_code', $cus_code)->findOrEmpty();
        if ($customer->isEmpty()) {
            return 7;
        }

        $res = BillDetail::find($id);
        if (empty($res)) {
            return 9;
        }
        addLog('修改随车单内容,随车单号:'.$number);

        $res->number = $number;
        $res->pro_code = $product['pro_code'];
        $res->other_code = $other_code;
        $res->fac_code = $fac_code;
        $res->cus_code = $cus_code;
        $res->amount = $amount;
        $res->amount_checked = 0;
        $res->door_checked = 0;

        if($res->save() == true){
            return 1;
        }else{
            return 8;
        }
    }

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
     * 删除
     * @param  int  $ids [description]
     * @return [boolen]      [description]
     */
    public function del($id){
        $detail = $this->where('id',$id)->findOrEmpty();
        if(!$detail->isEmpty()) {
            $detail = Billlist::where('number',$detail['number'])->findOrEmpty();
            if(!$detail->isEmpty()) {
                if($detail['status']!=1){
                    return 2;
                }else{
                    BillDetail::destroy($id);
                    addLog('删除随车单详情id:'.$id);
                    return 1;
                }
            }else{
                return 3;
            }
        }else{
            return 4;
        }
        
    }

    /**
     * 随车单撤回
     *
     * @param [type] $number
     * @param [type] $step 撤回步骤
     * @return void
     */
    public function detailrollback($number,$step){
        if(empty($number)){
            return false;
        }
        $detail = $this->where('number',$number)->select();
        if(count($detail)<=0){
            return false;
        }
        $data = [];
        if($step==1){
            foreach($detail as $value){
                $data[]=[
                    'id' =>$value['id'],
                    'amount_checked'=>0
                ];
            }
        }else{
            foreach($detail as $value){
                $data[]=[
                    'id' =>$value['id'],
                    'door_checked'=>0
                ];
            }
        }
        
        $res = $this->saveAll($data);
        if($res){
            return true;
        }else{
            return false;
        }
    }

     



}