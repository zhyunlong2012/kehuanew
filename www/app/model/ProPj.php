<?php
namespace app\model;
use think\Model;
use think\facade\Db;
/**
 * 配件表格
 */
class ProPj extends Model{
    // 设置字段信息
    protected $schema = [
        'id'          => 'int',
        'erpcode'    => 'string',  
        'pro_code'    => 'string', 
        'pj'         => 'string', 
        'pjtype'     => 'string', 
        'amount'     => 'int', 
    ];


    /**
     * 添加(有则修改)
     *
     * @param [type] $erpcode
     * @param [type] $pro_code
     * @param [type] $pj
     * @param [type] $type
     * @param [type] $amount
     * @return void
     */
    public function add($erpcode,$pro_code,$pj,$type,$amount){
        $res = ProPj::where('erpcode', $erpcode)->findOrEmpty();
        if (!$res->isEmpty()) {
            ProPj::where('erpcode', $erpcode)->delete();
        }

        $propj_model = new ProPj();
        $propj_model->erpcode = $erpcode;
        $propj_model->pro_code = $pro_code;
        $propj_model->pj = $pj;
        $propj_model->pjtype = $type;
        $propj_model->amount = $amount;
        if($propj_model->save() == true){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 返回列表
     *
     * @param integer $current
     * @param integer $pageSize
     * @param [type] $erpcode
     * @param [type] $pro_code
     * @param [type] $ycl
     * @param [type] $yclcode
     * @param boolean $excel
     * @return void
     */
    public function list($current=1,$pageSize=10,$erpcode,$pro_code,$pj,$type,$excel=false){
        $map = [];
        if(!empty($erpcode)){$map[]=['erpcode','like','%'.$erpcode.'%'];}
        if(!empty($pro_code)){$map[]=['pro_code','like','%'.$pro_code.'%'];}
        if(!empty($pj)){$map[]=['pj','like','%'.$pj.'%'];}
        if(!empty($type)){$map[]=['type','like','%'.$type.'%'];}
        if($excel){
            $data['data'] = $this->where($map)->order('id','desc')->select();
        }else{
            $data['data'] = $this->where($map)->page($current,$pageSize)->order('id','desc')->select();
        }
        $data['total'] =  $this->where($map)->count();
        $data['current'] = $current;
        $data['pageSize'] = $pageSize;
        $data['success'] = true;

        if($excel==true){
            $excel_data = [];
            foreach($data['data'] as $value){
                $excel_data[] = [
                    'erp代码' => $value['erpcode'],
                    '产品代码' => $value['pro_code'],
                    '配件代码' => $value['pj'],
                    '配件种类' => $value['pjtype'],
                    '数量' => $value['amount']
                ];
            }
            $data['data']=$excel_data;
        }

        return json($data);
    }


    
    /**
     * 删除
     * @param  array  $ids [description]
     * @return [boolen]      [description]
     */
    public function del($ids = []){
        $res = $this->where('id','in',$ids)->delete();
        return $res;
    }

    /**
     * 配件汇总
     *
     * @param [type] $data
     * @return void
     */
    public function gatherpj($data){
        $hash_data = [];
        $erp_code_arr = [];//用于查询已存在数据
        foreach ($data as $value) {
            $erp_code_arr[]=$value['erp代码'];
            if(isset($hash_data[trim($value['erp代码'])])){
                $hash_data[trim($value['erp代码'])]=$hash_data[trim($value['erp代码'])]  + $value['数量'];
            }else{
                $hash_data[trim($value['erp代码'])]=$value['数量'];
            }
        }
        // print_r($data);
        //建立原材料数组,和hash数组，hash数组用于统计计算
        $pro_pj_model = new ProPj();
        $exist_pj_amount  = $pro_pj_model->where('erpcode','in',$erp_code_arr)->column('id','erpcode'); 
        // print_r($hash_data); //查询到条数
        $pro_pj=$pro_pj_model->select();
        $pj =[];//原材料数组，拿到每条原材料数量，未合并
        $hash_pj = [];//hash 数组用于去重和统计

        foreach ($pro_pj as $value) {
            if(isset($hash_data[$value['erpcode']])){
                $pj[] = [
                    'pjtype' =>trim($value['pjtype']),
                    'pro_code' => trim($value['pro_code']),
                    'pj' => trim($value['pj']),
                    'number' => $hash_data[$value['erpcode']],
                    'amount' => $value['amount']
                ];
                $hash_pj[trim($value['pj'])] =0;
            }
        }
        
        //汇总数据
        $hash_type = [];
        foreach ($pj as $value) {
            // print_r($value);
            if(empty($value['pj'])){continue;}
            if(isset($hash_pj[$value['pj']])){
                $hash_pj[$value['pj']]=$hash_pj[$value['pj']]+$value['amount']*$value['number'];
                $hash_type[$value['pj']]= $value['pjtype'];
            }
        }
        
        $data_excel = [];
        foreach ($hash_pj as $key => $value) {
            if(empty($key)){continue;}
            $data_excel[]=[
                '种类'  => $hash_type[$key],
                '配件' => $key,
                '月需求量' => $value
            ];
        }
        $res = [
            'count' =>count($exist_pj_amount),
            'data' => $data_excel
        ];
        return $res;

    }

    /**
     * 配件数据中不存在的数据
     *
     * @param [type] $data
     * @return void
     */
    public function nopj($data){
        $erp_code_arr = [];//用于查询已存在数据
        foreach ($data as $value) {
            $erp_code_arr[]=trim($value['erp代码']);
        }
        
        $pro_pj_model = new ProPj();
        $exist_pro = $pro_pj_model->where('erpcode','in',$erp_code_arr)->column('id','erpcode'); 
        $not_exist_pro = [];
        foreach($erp_code_arr as $value){
            if(isset($exist_pro[$value])){
                continue;
            }else{
                $not_exist_pro[] = ['erp代码'=>$value];
            }
        }
        
        return $not_exist_pro;

    }



}