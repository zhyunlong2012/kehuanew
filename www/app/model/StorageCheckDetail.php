<?php
namespace app\model;
use think\Model;
use think\facade\Db;
/**
 * 盘点详情表格(库位)
 */
class StorageCheckDetail extends Model{
    // 设置字段信息
    protected $schema = [
        'id'                 => 'int',
        'user_id'            => 'int',
        'storage_check_id'   => 'int',
        'position_id'        => 'int', 
        'position_area_id'   => 'int',
        'product_id'         => 'int',
        'factory_id'         => 'int',
        'customer_id'        => 'int',
        'amount'             => 'string',
        'different'          => 'string', //差异
        'create_time'        => 'int',
        'update_time'        => 'int'
    ];


    /**
     * 添加盘点详情(非扫码)
     *
     * @param [type] $storage_check_id
     * @param [type] $position_id
     * @param [type] $position_area_id
     * @param [type] $product_id
     * @param [type] $factory_id
     * @param string $amount
     * @param [type] $different
     * @return void
     */
    public function add(
        $position_id,
        $position_area_id,
        $product_id,
        $factory_id,
        $customer_id,
        $amount){
        if(empty($position_id)||empty($product_id)||empty($factory_id)){
            return 2;
        }
        
        $storage_check_model = new StorageCheck();
        $storage_check = $storage_check_model->where('position_id', $position_id)->where('status',1)->findOrEmpty();
        if ($storage_check->isEmpty()) {
                return 3;
        }

        $area_storage_model = new AreaStorageCustomer();
        $sto_map = [
            ['position_area_id','=',$position_area_id],
            ['product_id','=',$product_id],
            ['factory_id','=',$factory_id],
            // ['customer_id','=',$customer_id],
        ];
        if($customer_id){$sto_map[]= ['customer_id','=',$customer_id];}
        $detail =$area_storage_model->where($sto_map)->find();
        if($detail){
            $different = $amount - $detail['amount'];
        }else{
            $different =  $amount ;
        }
        $exist_map = [
            ['storage_check_id','=',$storage_check['id']],
            ['position_id','=',$position_id],
            ['position_area_id','=',$position_area_id],
            ['product_id','=',$product_id],
            ['factory_id','=',$factory_id],
            ['customer_id','=',$customer_id],
        ];
        $exist_check = $this->where($exist_map)->find();
        //如有数据雷同，则叠加
        if($exist_check){
            $different = $different + $exist_check['amount'] ;
            $amount = $amount + $exist_check['amount'];
            return $this->updata($exist_check['id'],$position_id,$position_area_id,$product_id,$factory_id,$customer_id,$amount,$different);
        }else{
            $user_id = getUid();
            $storage_check_model = new StorageCheckDetail();
            $storage_check_model->user_id = $user_id;
            $storage_check_model->storage_check_id = $storage_check['id'];
            $storage_check_model->position_id = $position_id;
            $storage_check_model->position_area_id = $position_area_id;
            $storage_check_model->product_id = $product_id;
            $storage_check_model->factory_id = $factory_id;
            $storage_check_model->customer_id = $customer_id;
            $storage_check_model->amount = $amount;
            $storage_check_model->different = $different;
            // addLog('进行盘点:库区ID'.$position_id.'库位ID'.$position_area_id.'产品ID'.$product_id.'厂家ID:'.$factory_id.'盘点数量'.$amount);
            if($storage_check_model->save() == true){
                return 1;
            }else{
                return 4;
            }
        }
        
        
    }

    

    /**
     * 修改信息
     *
     * @param [type] $id
     * @param [type] $storage_check_id
     * @param string $car_id
     * @param integer $status
     * @return void
     */
    public function updata($id,
        $position_id,
        $position_area_id,
        $product_id,
        $factory_id,
        $customer_id,
        $amount,
        $different){
        $user_id = getUid();
        $data = [
            'id' =>$id,
            'position' => $position_id,
            'position_area_id' => $position_area_id,
            'product_id' => $product_id,
            'factory_id' => $factory_id,
            'customer_id' => $customer_id,
            'amount' => $amount,
            'different' => $different,
            'user_id' => $user_id
        ];
        $res = $this-> update($data);
        if($res == true){
            return 1;
        }else{
            return 4;
        }
    }

    /**
     * 返回盘点列表
     *
     * @param string $storage_check_id
     * @return void
     */
    public function list($current,$pageSize,$storage_check_id,$position_id,$position_area_id,$product_id,$create_time=''){
        $map = [];
        if(!empty($storage_check_id)){$map[]=['storage_check_id','like',$storage_check_id];}
        if(!empty($position_id)){$map[]=['position_id','=',$position_id];}
        if(!empty($position_area_id)){$map[]=['position_area_id','=',$position_area_id];}
        if(!empty($product_id)){$map[]=['product_id','=',$product_id];}
        if(!empty($create_time)){
            $between_time = [strtotime($create_time[0]) ,strtotime($create_time[1])];
            $map[] = ['create_time','between',$between_time];
        }
        
        $data['data'] = $this->where($map)->page($current,$pageSize)->order('id','desc')->select();
        
        $data['total'] =  $this->where($map)->count();
        $data['current'] = $current;
        $data['pageSize'] = $pageSize;
        $data['success'] = true;
        
        $position_model = new Position();
        $position_area_model = new PositionArea();
        $product_model = new Product();
        $factory_model = new Factory();
        $customer_model = new Customer();
        foreach ($data['data'] as $key => $value) {
            $pos_tmp = $position_model-> where('id',$value['position_id'])->find();
            $data['data'][$key]['pos_name'] =$pos_tmp?$pos_tmp['pos_name']:'注销库区';
            $data['data'][$key]['pos_code'] =$pos_tmp?$pos_tmp['pos_code']:'注销库区';
            $area_tmp = $position_area_model-> where('id',$value['position_area_id'])->find();
            $data['data'][$key]['area_code'] =$area_tmp?$area_tmp['code']:'注销库位';
            $pro_tmp = $product_model-> where('id',$value['product_id'])->find();
            $data['data'][$key]['pro_code'] =$pro_tmp?$pro_tmp['pro_code']:'注销产品';
            $fac_tmp = $factory_model-> where('id',$value['factory_id'])->find();
            $data['data'][$key]['fac_code'] =$fac_tmp?$fac_tmp['fac_code']:'注销供应商';
            $cus_tmp = $customer_model-> where('id',$value['customer_id'])->find();
            $data['data'][$key]['cus_code'] =$cus_tmp?$cus_tmp['cus_code']:'注销客户';
        }

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
            $detail = StorageCheck::where('storage_check_id',$detail['storage_check_id'])->findOrEmpty();
            if(!$detail->isEmpty()) {
                if($detail['status']!=1){
                    return 2;
                }else{
                    StorageCheckDetail::destroy($id);
                    addLog('删除库位盘点详情id:'.$id);
                    return 1;
                }
            }else{
                return 3;
            }
        }else{
            return 4;
        }
        
    }



}