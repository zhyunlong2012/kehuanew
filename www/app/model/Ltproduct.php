<?php
namespace app\model;
use think\Model;
/**
 * 产品表格
 */
class Ltproduct extends Model{
    // 设置字段信息
    protected $schema = [
        'id'          => 'int',
        'pro_name'    => 'string',
        'pro_code'    => 'string',
        'fac_code'    => 'string',
        'pp_code'     => 'string',
        'pp_name'     => 'string',
        'geshu'       => 'string',  //每托个数
        'tuoshu'      => 'string',  //每格托数
        'bili'        => 'string',   //E,F线装车比例
        'weight'      => 'string',
        'create_time' => 'int',
        'update_time' => 'int',
    ];


    /**
     * 添加产品
     */
    public function add($pro_name,$pro_code,$fac_code,$pp_name,$pp_code,$geshu,$tuoshu,$bili,$weight){
        $res = Ltproduct::where('pro_code', $pro_code)->find();
        if ($res) {
           return $this->updata($res->id,$pro_name,$pro_code,$fac_code,$pp_name,$pp_code,$geshu,$tuoshu,$bili,$weight);
        }
        $pro_model = new Ltproduct();
        $pro_model->pro_name = $pro_name;
        $pro_model->pro_code = $pro_code;
        $pro_model->fac_code = $fac_code;
        $pro_model->pp_name = $pp_name;
        $pro_model->pp_code = $pp_code;
        $pro_model->geshu = $geshu;
        $pro_model->tuoshu = $tuoshu;
        $pro_model->bili = $bili;
        $pro_model->weight = $weight;
        addLog('添加分拣产品,产品编号:'.$pro_code);
        if($pro_model->save() == true){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 返回产品列表
     * @param  integer $current  [description]
     * @param  integer $pageSize [description]
     * @param  integer $status   [description]
     * @param  string  $pro_name [description]
     * @param  string  $pro_code [description]
     * @return [type]            [description]
     */
    public function list($current=1,$pageSize=10,$pro_name='',$pro_code='',$excel=false){
        $map = [];
        if(!empty($pro_name)){$map[]=['pro_name','like','%'.$pro_name.'%'];}
        if(!empty($pro_code)){$map[]=['pro_code','like','%'.$pro_code.'%'];}
        if($excel){
            $data['data'] = $this->where($map)->order('id','desc')->select();
        }else{
            $data['data'] = $this->where($map)->page($current,$pageSize)->order('id','desc')->select();
        }
        $data['total'] =  $this->where($map)->count();
        $data['current'] = $current;
        $data['pageSize'] = $pageSize;
        $data['success'] = true;

        return json($data);
    }

    /**
     * 修改信息
     *
     * @return void
     */
    public function updata($id,$pro_name,$pro_code,$fac_code,$pp_name,$pp_code,$geshu,$tuoshu,$bili,$weight){
        if((empty($pro_name))||(empty($id))){
            return false;
        }
        $res = Ltproduct::find($id);
        if (empty($res)) {
            return false;
        }
        addLog('修改分拣产品,产品名称:'.$pro_name);

        $res->pro_name = $pro_name;
        $res->pro_code = $pro_code;
        $res->fac_code = $fac_code;
        $res->pp_name = $pp_name;
        $res->pp_code = $pp_code;
        $res->geshu = $geshu;
        $res->tuoshu = $tuoshu;
        $res->bili = $bili;
        $res->weight = $weight;
        if($res->save() == true){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 删除产品
     * @param  array  $ids [description]
     * @return [boolen]      [description]
     */
    public function del($ids = []){
        addLog('删除分拣产品,产品id:'.implode(',', $ids));
        return Ltproduct::destroy($ids);
    }

    

}