<?php
namespace app\model;
use think\Model;
use think\facade\Db;
/**
 * 盘点详情表格(库位,扫码，数据整合，用此数据，计算差异相当于EXCEL表格)
 */
class ScanCheckDetail extends Model{
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
        'create_time'        => 'int',
        'update_time'        => 'int'
    ];


    /**
     * 添加盘点详情(扫码)相同产品累加
     *
     * @param [type] $storage_check_id
     * @param [type] $position_id
     * @param [type] $position_area_id
     * @param [type] $product_id
     * @param [type] $factory_id
     * @param [type] $amount
     * @param [type] $different
     * @return void
     */
    public function add($code,$position_area_id,$position_id,$forward='in'){
        $scan_model = new Scan();
        $scan = $scan_model ->where('code',$code)->find();
        if(!$scan){return false;}
        $product_model = new Product();
        $product = $product_model ->where('pro_code',$scan['pro_code'])->find();
        if(!$product){return false;}
        $factory_model = new Factory();
        $factory = $factory_model ->where('fac_code',$scan['fac_code'])->find();
        if(!$factory){return false;}
        $customer_model = new Customer();
        $customer = $customer_model ->where('cus_code',$scan['cus_code'])->find();
        if($customer){
            $customer_id = $customer['id'];
        }else{
            $customer_id = '';
        }
        $storage_check_model = new StorageCheck();
        $storage_check = $storage_check_model->where('position_id', $position_id)->where('status',1)->findOrEmpty();
        if ($storage_check->isEmpty()) {
                return false;
        }

        $exist_map = [
            ['storage_check_id','=',$storage_check['id']],
            ['position_id','=',$position_id],
            ['position_area_id','=',$position_area_id],
            ['product_id','=',$product['id']],
            ['factory_id','=',$factory['id']],
            ['customer_id','=',$customer_id]
        ];
        $exist_check = $this->where($exist_map)->find();
        $amount = 0;
        if($exist_check){
            if($forward=='out'){
                $amount = $exist_check['amount']-$scan['amount'];
            }else{
                $amount = $exist_check['amount']+$scan['amount'];
            }
            $exist_check->amount = $amount;
            $res = $exist_check->save();
            if($res){
                return true;
            }else{
                return false;
            }
        }
        if($forward=='out'){
            $amount = '-'.$scan['amount'];
        }else{
            $amount = $scan['amount'];
        }
        $scan_check_model = new ScanCheckDetail();
        $scan_check_model->storage_check_id = $storage_check['id'];
        $scan_check_model->position_id = $position_id;
        $scan_check_model->position_area_id = $position_area_id;
        $scan_check_model->product_id = $product['id'];
        $scan_check_model->factory_id = $factory['id'];
        $scan_check_model->customer_id = $customer_id;
        $scan_check_model->amount = $amount;
        if($scan_check_model->save() == true){
            return true;
        }else{
            return false;
        }
    }

    

    /**
     * 返回盘点列表
     *
     * @param string $storage_check_id
     * @return void
     */

    public function list($current,$pageSize,$storage_check_id,$position_id,$position_area_id,$create_time=''){
        $map = [];
        if(!empty($storage_check_id)){$map[]=['storage_check_id','like',$storage_check_id];}
        if(!empty($position_id)){$map[]=['position_id','=',$position_id];}
        if(!empty($position_area_id)){$map[]=['position_area_id','=',$position_area_id];}
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
        foreach ($data['data'] as $key => $value) {
            $pos_tmp = $position_model-> where('id',$value['position_id'])->find();
            $data['data'][$key]['pos_name'] =$pos_tmp?$pos_tmp['pos_name']:'注销库区';
            $data['data'][$key]['pos_code'] =$pos_tmp?$pos_tmp['pos_code']:'注销库区';
            $area_tmp = $position_area_model-> where('id',$value['position_area)id'])->find();
            $data['data'][$key]['area_code'] =$area_tmp?$area_tmp['code']:'注销库位';
        }

        return json($data);
    }

    /**
     * 删除
     * @param  int  $ids [description]
     * @return [boolen]      [description]
     */
    public function del($id){
        ScanCheckDetail::destroy($id);
        
    }



}