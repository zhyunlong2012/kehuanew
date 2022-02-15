<?php
namespace app\model;
use think\Model;
use think\facade\Db;
/**
 * 解放车辆表格
 */
class Jfcar extends Model{
    // 设置字段信息
    protected $schema = [
        'id'          => 'int',
        'carcode'     => 'string',  //车型代码
        'cartype'     => 'string',  //J4X4
    ];


    /**
     * 添加车型(有则修改)
     *
     * @param [type] $carcode
     * @param [type] $cartype
     * @return void
     */
    public function add($carcode,$cartype){
        $res = Jfcar::where('carcode', $carcode)->findOrEmpty();
        if (!$res->isEmpty()) {
            return $this->updata($res['id'],$carcode,$cartype);
        }

        $car_model = new Jfcar();
        $car_model->carcode = $carcode;
        $car_model->cartype = $cartype;
        if($car_model->save() == true){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 返回车型列表
     *
     * @param integer $current
     * @param integer $pageSize
     * @param [type] $carcode
     * @param [type] $cartype
     * @param boolean $excel
     * @return void
     */
    public function list($current=1,$pageSize=10,$carcode,$cartype,$excel=false){
        $map = [];
        if(!empty($carcode)){$map[]=['carcode','=',$carcode];}
        if(!empty($cartype)){$map[]=['cartype','=',$cartype];}
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
     * @param [type] $id
     * @param [type] $carcode
     * @param [type] $cartype
     * @return void
     */
    public function updata($id,$carcode,$cartype){
        $res = Jfcar::find($id);
        if (empty($res)) {
            return false;
        }

        $res->carcode = $carcode;
        $res->cartype = $cartype;
        if($res->save() == true){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 删除车型的同时，删除车型详细信息
     * @param  array  $ids [description]
     * @return [boolen]      [description]
     */
    public function del($ids = []){
        // 启动事务
        Db::startTrans();
        try {
            $jfcar_detail_model = new JfcarDetail();
            $jfcar_detail_carcodes = $this->where('id','in',$ids)->column('carcode');
            $res = JfCar::destroy($ids);
            $res1 = $jfcar_detail_model->where('carcode','in',$jfcar_detail_carcodes)->delete();
            if($res&&$res1){
                // 提交事务
                Db::commit();
                addLog('删除车型库信息!车型id:'. implode(",",$ids));
                return true;
            }else{
                Db::rollback();
                return false;
            }
            
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return false;
        }
    }

    /**
     * excel批量添加车型
     *
     * @param [type] $carcode
     * @param [type] $cartype
     * @param [type] $pro_code
     * @param [type] $amount
     * @return void
     */
    public function addTogather($carcode,$cartype,$pro_code,$amount){
        $jfcar_model = new Jfcar();
        $jfcar_detail_model = new JfcarDetail();
        // 启动事务
        Db::startTrans();
        try {
            $res1 = $jfcar_model ->add($carcode,$cartype);
            $res2 = $jfcar_detail_model ->add($carcode,$pro_code,$amount);
            if($res1&&$res2){
                // 提交事务
                Db::commit();
                return true;
            }else{
                Db::rollback();
                return false;
            }
            
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return false;
        }
    }


}