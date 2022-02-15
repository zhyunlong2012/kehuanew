<?php
namespace app\model;
use think\Model;

/**
 * 库位库存表格(客户)
 */
class AreaStorageCustomer extends Model{
    // 设置字段信息
    protected $schema = [
        'id'                 => 'int',
        'position_area_id'   => 'int',
        'product_id'         => 'int',
        'factory_id'         => 'int',
        'amount'             => 'int',
        'customer_id'        => 'int'
    ];


    /**
     * 更改库存(如何不存在则添加，存在则修改)
     * @param integer $position_area_id
     * @param integer $product_id
     * @param integer $amount
     * @return void
     */
    public function add($position_area_id,$product_id,$amount,$factory_id,$customer_id){
        $map = [
            ['position_area_id','=' , $position_area_id],
            ['product_id','=',$product_id],
            ['factory_id','=',$factory_id],
            ['customer_id','=',$customer_id]
        ];
        $res = AreaStorageCustomer::where($map)->findOrEmpty();
        if (!$res->isEmpty()) {
            // if($amount<0){
            //     $total = $this->where($map)->sum('amount');
            //     if($total + $amount <0){
            //         return false;
            //     }
            // }
            //已有库存，修改库存
            $res->amount = $res['amount']+$amount;
            $res1 = $res->save();
            if($res1){
                return true;
            }else{
                return false;
            }
        }else{
            //未有库存，新增库存
            // if($amount<0){
            //     return false;
            // }
            $storage_model = new AreaStorageCustomer();
            $storage_model->position_area_id = $position_area_id;
            $storage_model->product_id = $product_id;
            $storage_model->factory_id = $factory_id;
            $storage_model->amount = $amount;
            $storage_model->customer_id = $customer_id;
            $res1 = $storage_model->save();
            if($res1){
                return true;
            }else{
                return false;
            }
        }
    }

    /**
     * 实时库存
     *
     * @param [type] $current
     * @param [type] $pageSize
     * @param [type] $position_id
     * @param [type] $pro_code
     * @param [type] $factory_id
     * @param boolean $excel
     * @return void
     */
    public function nowSto($current,$pageSize,$position_id,$position_area_id,$pro_code,$factory_id,$customer_id,$excel=false,$other_code=''){
        // $map1 = [];
        $map = [];
        if(!empty($pro_code)){$map1[] = ['pro_code','=',$pro_code];}
        if(!empty($ohter_code)){$map1[] = ['ohter_code','=',$ohter_code];}
        // if(!empty($factory_id)){$map1[] = ['factory_id','=',$factory_id];}
        if($pro_code||$other_code){
            $pro_model = new Product();
            $pro_id_list = $pro_model-> where($map1)->column('id');
            if(count($pro_id_list)>0){
                $map[]=['product_id','in',$pro_id_list];
            }else{
                $data['total'] = 0;
                $data['success'] = true;
                return json($data);
            }
        }

        if(!empty($position_id)){
            $position_area_model = new PositionArea();
            $area_id_list = $position_area_model-> where('position_id',$position_id)->column('id');
            if(count($area_id_list)>0){
                $map[]=['position_area_id','in',$area_id_list];
            }else{
                $data['total'] = 0;
                $data['success'] = true;
                return json($data);
            }
        }
        
        if(!empty($position_area_id)){$map[] = ['position_area_id','=',$position_area_id];}
        if(!empty($factory_id)){$map[] = ['factory_id','=',$factory_id];}
        if(!empty($customer_id)){$map[] = ['customer_id','=',$customer_id];}
        $map[] = ['amount','<>',0];
        if($excel){
            $data['data'] = $this->where($map)->order('id','desc')->select();
        }else{
            $data['data'] = $this->where($map)->page($current,$pageSize)->order('id','desc')->select();
        }
        
        $data['total'] =  $this->where($map)->count();
        $data['current'] = $current;
        $data['pageSize'] = $pageSize;
        $data['success'] = true;

        $fac_model = new Factory();
        $pro_model = new Product();
        $pos_model = new Position();
        $position_area_model = new PositionArea();
        foreach ($data['data'] as $key => $value) {
            $pro_tmp = $pro_model-> where('id',$value['product_id'])->find();
            if($pro_tmp){
                $data['data'][$key]['pro_name']= $pro_tmp['pro_name'];
                $data['data'][$key]['pro_code']= $pro_tmp['pro_code'];
                $data['data'][$key]['other_code']= $pro_tmp['other_code'];
            }else{
                $data['data'][$key]['pro_name']= '注销产品';
                $data['data'][$key]['pro_code']= '注销产品';
                $data['data'][$key]['other_code']= '注销产品';
            }
            $pos = $position_area_model->where('id',$value['position_area_id'])->find();
            if($pos){
                $data['data'][$key]['pos_area_code'] =$pos?$pos['code']:'注销库位';
                $pos_tmp = $pos_model->where('id',$pos['position_id'])->find();
            }else{
                $data['data'][$key]['pos_area_code'] ='注销库位';
                $pos_tmp = null;
            }

            
            $fac_tmp = $fac_model ->where('id',$value['factory_id'])->find();
            $data['data'][$key]['fac_name'] =$fac_tmp?$fac_tmp['fac_name']:'注销厂家';
            
            $data['data'][$key]['pos_name'] =$pos_tmp?$pos_tmp['pos_name']:'注销库区';
            $data['data'][$key]['pos_code'] =$pos_tmp?$pos_tmp['pos_code']:'注销库区';
        } 
        
        if($excel==true){
            $excel_data = [];
            foreach($data['data'] as $value){
                $excel_data[] = [
                    '库区名称' => $value['pos_name'],
                    '库区编号' => $value['pos_code'],
                    '库位编号' => $value['pos_area_code'],
                    '产品名称' => $value['pro_name'],
                    '产品编码' => $value['pro_code'],
                    '其他编码' => $value['other_code'],
                    '生产厂家' => $value['fac_name'],
                    '数量' => $value['amount']
                ];
            }
            $data['data']=$excel_data;

        }
        return json($data);

    }

   



    
}