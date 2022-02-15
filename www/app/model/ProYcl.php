<?php
namespace app\model;
use think\Model;
use think\facade\Db;
/**
 * 原材料表格
 */
class ProYcl extends Model{
    // 设置字段信息
    protected $schema = [
        'id'          => 'int',
        'erpcode'    => 'string',  
        'pro_code'    => 'string', 
        'ycl'         => 'string', 
        'weight'     => 'string', 
        'yclcode'     => 'string', 
    ];


    /**
     * 添加(有则修改)
     *
     * @param [type] $erpcode
     * @param [type] $pro_code
     * @param [type] $ycl
     * @param [type] $weight
     * @param [type] $yclcode
     * @return void
     */
    public function add($erpcode,$pro_code,$ycl,$weight,$yclcode){
        $res = ProYcl::where('yclcode', $yclcode)->findOrEmpty();
        if (!$res->isEmpty()) {
            ProYcl::where('yclcode', $yclcode)->delete();
        }

        $proycl_model = new ProYcl();
        $proycl_model->erpcode = $erpcode;
        $proycl_model->pro_code = $pro_code;
        $proycl_model->ycl = $ycl;
        $proycl_model->weight = $weight;
        $proycl_model->yclcode = $yclcode;
        if($proycl_model->save() == true){
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
    public function list($current=1,$pageSize=10,$erpcode,$pro_code,$ycl,$yclcode,$excel=false){
        $map = [];
        if(!empty($erpcode)){$map[]=['erpcode','like','%'.$erpcode.'%'];}
        if(!empty($pro_code)){$map[]=['pro_code','like','%'.$pro_code.'%'];}
        if(!empty($ycl)){$map[]=['ycl','like','%'.$ycl.'%'];}
        if(!empty($yclcode)){$map[]=['yclcode','like','%'.$yclcode.'%'];}
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
                    '原材料名称' => $value['ycl'],
                    '原材料代码' => $value['yclcode'],
                    '重量' => $value['weight']
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
        return ProYcl::destroy($ids);
    }

    /**
     * 原材料汇总
     *
     * @param [type] $data
     * @return void
     */
    public function gatherycl($data){
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
        
        //建立原材料数组,和hash数组，hash数组用于统计计算
        $pro_ycl_model = new ProYcl();
        $exist_pro_amount  = $pro_ycl_model->where('erpcode','in',$erp_code_arr)->column('id','erpcode'); //
        // dump(count($exist_pro_amount)); //查询到条数
        $pro_ycl=$pro_ycl_model->select();
        $ycl = Array();//原材料数组，拿到每条原材料数量，未合并
        $hash_ycl = Array();//hash 数组用于去重和统计

        foreach ($pro_ycl as $value) {
            if(isset($hash_data[$value['erpcode']])){
                $ycl[] = [
                    'ycl' =>trim($value['ycl']),
                    'erpcode' => trim($value['erpcode']),
                    'yclcode' => trim($value['yclcode']),
                    'weight' => $hash_data[$value['erpcode']]*trim($value['weight'])/1000
                ];
                $hash_ycl[trim($value['ycl'])] =0;
            }
        }
        //汇总原材料数据
        //建立hash_erp 保存erp代码 hash_ycl_code保存原材料代码
        foreach ($ycl as $value) {
            if(isset($hash_ycl[$value['ycl']])){
                $hash_ycl[$value['ycl']]=$hash_ycl[$value['ycl']]+$value['weight'];
                $hash_erp[$value['ycl']] = $value['erpcode'];
                $hash_ycl_code[$value['ycl']] = $value['yclcode'];
            }
        }
        $data_excel = [];
        foreach ($hash_ycl as $key => $value) {
            $data_excel[]=[
                'erp代码'  => $hash_erp[$key],
                '原材料代码' => $hash_ycl_code[$key],
                '原材料名称' => $key,
                '月需求量' => $value
            ];
        }
        $res = [
            'count' =>count($exist_pro_amount),
            'data' => $data_excel
        ];
        return $res;

    }

    /**
     * 原材料数据中不存在的数据
     *
     * @param [type] $data
     * @return void
     */
    public function noycl($data){
        $erp_code_arr = [];//用于查询已存在数据
        foreach ($data as $value) {
            $erp_code_arr[]=trim($value['erp代码']);
        }
        $pro_ycl_model = new ProYcl();
        $exist_pro = $pro_ycl_model->where('erpcode','in',$erp_code_arr)->column('id','erpcode'); 
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