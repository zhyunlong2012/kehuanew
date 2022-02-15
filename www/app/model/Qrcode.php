<?php
namespace app\model;
use think\Model;

/**
 * 码库表格
 */
class Qrcode extends Model{
    // 设置字段信息
    protected $schema = [
        'id'          => 'int',
        'factory_code'    => 'string',
        'qrcode'    => 'string',
        'code'         => 'string',
    ];


    /**
     * 添加
     *
     * @param [type] $factory_code
     * @param [type] $qrcode
     * @param [type] $code
     * @return void
     */
    public function add($factory_code,$qrcode,$code){
        $res = Qrcode::where('factory_code', $factory_code)->where('qrcode',$qrcode)->findOrEmpty();
        if (!$res->isEmpty()) {
            return false;
        }

        $qrcode_model = new Qrcode();
        $qrcode_model ->factory_code = $factory_code;
        $qrcode_model ->qrcode = $qrcode;
        $qrcode_model ->code = $code;
        if($qrcode_model->save() == true){
            addLog('添加码库，码文:'.$qrcode);
            return true;
        }else{
            return false;
        }
    }

    /**
     * 码库列表
     *
     * @param [type] $current
     * @param [type] $pageSize
     * @param [type] $factory_code
     * @param [type] $qrcode
     * @param [type] $code
     * @param boolean $excel
     * @return void
     */
    public function list($current,$pageSize,$factory_code,$qrcode,$code,$excel=false){
        $map = [];
        if(!empty($factory_code)){$map[]=['factory_code','like','%'.$factory_code.'%'];}
        if(!empty($qrcode)){$map[] = ['qrcode','=',$qrcode];}
        if(!empty($code)){$map[] = ['code','=',$code];}
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
     * 修改
     *
     * @param [type] $id
     * @param [type] $factory_code
     * @param [type] $qrcode
     * @param [type] $code
     * @return void
     */
    public function updata($id,$factory_code,$qrcode,$code){
        if((empty($qrcode))||(empty($id))){
            return false;
        }

        $qr_res = $this
                ->where('factory_code',$factory_code)
                ->where('qrcode',$qrcode)
                ->where('code',$code)
                ->findOrEmpty();
        if(!$qr_res->isEmpty()){
            return false;
        }
        $res = Qrcode::find($id);
        if (empty($res)) {
            return false;
        }

        $res->factory_code = $factory_code;
        $res->qrcode = $qrcode;
        $res->code = $code;
        if($res->save() == true){
            addLog('更新码库，新码文:'.$qrcode.'码文id:'.$id);
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
        return Qrcode::destroy($ids);
    }

}