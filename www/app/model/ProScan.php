<?php
namespace app\model;
use think\Model;

/**
 * 产品扫码记录表格
 */
class ProScan extends Model{
    // 设置字段信息
    protected $schema = [
        'id'          => 'int',
        'user_id'    => 'int',
        'status'    => 'int', //1已扫2已打包
        'qrcode'    => 'string', //产品二维码
        'code'     => 'string', //包裹二维码
        'pro_code'     => 'string', //产品二维码
        'create_time' => 'int',
        'update_time' => 'int'
    ];


    /**
     * 添加
     *
     * @param [type] $factory_code
     * @param [type] $qrcode
     * @param [type] $code
     * @return void
     */
    public function add($qrcode,$pro_code){
        $res = ProScan::where('qrcode',$qrcode)->findOrEmpty();
        if (!$res->isEmpty()) {
            return false;
        }
        $user_id = getUid();
        $pro_scan_model = new ProScan();
        $pro_scan_model ->qrcode = $qrcode;
        $pro_scan_model ->pro_code = $pro_code;
        $pro_scan_model ->user_id = $user_id;
        $pro_scan_model ->status = 1;
        if($pro_scan_model->save() == true){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 打包完成
     *
     * @param [type] $code
     * @return void
     */
    public function proscaned($code){
        if(empty($code)){
            return false;
        }
        $user_id = getUid();
        if(empty($user_id)){
            return false;
        }
        $pro_scan = $this->where('user_id',$user_id)->where('status',1)->select();
        // if(count($pro_scan)<=0){
        //     return false;
        // }
        $data = [];
        foreach($pro_scan as $value){
            $data[]=[
                'id' =>$value['id'],
                'status' => 2,
                'code'=>$code
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
     * 打包撤回
     *
     * @return void
     */
    public function proscanrollback(){
        $user_id = getUid();
        $res = $this->where('user_id',$user_id)->where('status',1)->delete();
        if($res){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 扫码列表
     *
     * @param [type] $current
     * @param [type] $pageSize
     * @param [type] $qrcode
     * @param boolean $excel
     * @return void
     */
    public function list($current,$pageSize,$qrcode,$code,$status,$excel=false){
        $map = [];
        $user_id = getUid();
        $map[] = ['user_id','=',$user_id];
        if(!empty($qrcode)){$map[] = ['qrcode','=',$qrcode];}
        if(!empty($code)){$map[] = ['code','=',$code];}
        if(!empty($status)){$map[] = ['status','=',$status];}

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
     * 删除
     * @param  array  $ids [description]
     * @return [boolen]      [description]
     */
    public function del($ids = []){
        return ProScan::destroy($ids);
    }

    /**
     * 打包完成的所有数据
     *
     * @return void
     */
    public function unpackpro(){
        $user_id = getUid();
        $products = $this->where('user_id',$user_id)->where('status',1)->select();
        if(count($products)>0){
            $hash = $this->where('user_id',$user_id)->where('status',1)->column('id','pro_code');
            if(count($hash)>1){
                $res = ['code' => 3];
            }else{
                $res=[
                    'code' => 1,
                    'data'=> [
                        'pro_code' => $products[0]['pro_code'],
                        'amount' => count($products),
                        'fac_code' => 'LC301'
                    ]
                ];
            }
            
        }else{
            $res = ['code' => 2];
        }
        return json($res);
    }

}