<?php
namespace app\model;
use think\Model;
/**
 * 解放车型产品表格
 */
class JfcarDetail extends Model{
    // 设置字段信息
    protected $schema = [
        'id'          => 'int',
        'carcode'     => 'string',
        'pro_code'    => 'string',
        'amount'          => 'int',
    ];


    /**
     * 添加车型(有则删除)
     *
     * @param [type] $carcode
     * @param [type] $cartype
     * @return void
     */
    public function add($carcode,$pro_code,$amount){
        $res = JfcarDetail::where('carcode', $carcode)->where('pro_code',$pro_code)->findOrEmpty();
        if (!$res->isEmpty()) {
            return $this->updata($res['id'],$carcode,$pro_code,$amount);
        }

        $jfcar_detail_model = new JfcarDetail();
        $jfcar_detail_model->carcode = $carcode;
        $jfcar_detail_model->pro_code = $pro_code;
        $jfcar_detail_model->amount = $amount;
        if($jfcar_detail_model->save() == true){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 返回车型产品列表
     *
     * @param integer $current
     * @param integer $pageSize
     * @param [type] $carcode
     * @param [type] $pro_code
     * @param [type] $amount
     * @param boolean $excel
     * @return void
     */
    public function list($current=1,$pageSize=10,$carcode,$pro_code,$amount,$excel=false){
        $map = [];
        if(!empty($carcode)){$map[]=['carcode','=',$carcode];}
        if(!empty($pro_code)){$map[]=['pro_code','=',$pro_code];}
        if(!empty($amount)){$map[]=['amount','=',$amount];}
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
                    '车型代码' => $value['carcode'],
                    '产品代码' => $value['pro_code'],
                    '数量' => $value['amount']
                ];
            }
            $data['data']=$excel_data;
        }
        return json($data);
    }


    /**
     * 修改信息
     *
     * @param [type] $id
     * @param [type] $carcode
     * @param [type] $cartype
     * @return void
     */
    public function updata($id,$carcode,$pro_code,$amount){
        $res = JfcarDetail::find($id);
        if (empty($res)) {
            return false;
        }

        $res->carcode = $carcode;
        $res->pro_code = $pro_code;
        $res->amount = $amount;
        if($res->save() == true){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 删除
     * @param  array  $ids [description]
     * @return [boolen]      [description]
     */
    public function del($ids = []){
        return JfcarDetail::destroy($ids);
    }


}