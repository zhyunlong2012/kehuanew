<?php
namespace app\model;
use think\Model;
use think\model\concern\SoftDelete;
/**
 * 车辆表格
 */
class Car extends Model{
    use SoftDelete;
    protected $deleteTime = 'delete_time';
    // 设置字段信息
    protected $schema = [
        'id'          => 'int',
        'number'        => 'string',
        'driver'  => 'string',
        'user_id'      => 'int',
        'status'      => 'int',  //1正常 2停用
        'create_time' => 'int',
        'update_time' => 'int',
        'delete_time' => 'int',
        'tel' =>'string'
    ];


    /**
     * 添加车辆
     *
     * @param string $number
     * @param string $driver 司机名字
     * @param string $uid    关联账户
     * @param integer $status
     * @return void
     */
    public function add($number,$driver,$uid='',$status=1,$tel=''){
        $res = Car::where('number', $number)->findOrEmpty();
        if (!$res->isEmpty()) {
            return false;
        }

        $car_model = new Car();
        $car_model->number = $number;
        $car_model->driver = $driver;
        $car_model->user_id = $uid;
        $car_model->status = $status;
        $car_model->tel = $tel;
        addLog('添加车辆,车牌号:'.$number);
        if($car_model->save() == true){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 返回车辆列表
     *
     * @param integer $current
     * @param integer $pageSize
     * @param string $driver
     * @param string $number
     * @param integer $status
     * @return void
     */
    public function list($current=1,$pageSize=10,$number,$driver,$status,$excel=false){
        $map = [];
        if(!empty($number)){$map[]=['number','like','%'.$number.'%'];}
        if(!empty($driver)){$map[]=['driver','like','%'.$driver.'%'];}
        if(!empty($status)){$map[]=['status','=',$status];}
        if($excel){
            $data['data'] = $this->where($map)->order('id','desc')->select();
        }else{
            $data['data'] = $this->where($map)->page($current,$pageSize)->order('id','desc')->select();
        }
        $data['total'] =  $this->where($map)->count();
        $data['current'] = $current;
        $data['pageSize'] = $pageSize;
        $data['success'] = true;
        
        $user_model = new User();
        foreach ($data['data'] as $key => $value) {
            $user_tmp = $user_model-> where('id',$value['user_id'])->find();
            $data['data'][$key]['username'] =$user_tmp?$user_tmp['username']:'注销用户';
        }

        return json($data);
    }

    /**
     * 停用车辆
     *
     * @param string $code
     * @return void
     */
    public function invalid($id){
        if(empty($id)){return false;}
        $scan = $this->where('id',$id)->findOrEmpty();
        if (!$scan->isEmpty()) {
            if($scan['status']==2){
                return false;
            }
            $scan->status = 2;
            if($scan->save() == true){
                return true;
            }else{
                return false;
            }
        }else{
            return false;
        }
    }

    /**
     * 修改信息
     *
     * @param [type] $id
     * @param [type] $number
     * @param [type] $driver
     * @param string $uid
     * @param integer $status
     * @return void
     */
    public function updata($id,$number,$driver,$uid='',$status=1,$tel){
        if((empty($number))||(empty($id))){
            return false;
        }
        $res = Car::find($id);
        if (empty($res)) {
            return false;
        }
        addLog('修改车辆,车牌照:'.$number);

        $res->number = $number;
        $res->driver = $driver;
        $res->tel = $tel;
        $res->user_id = $uid;
        if($status!==3){$res->status = $status;}
        if($res->save() == true){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 删除车辆
     * @param  array  $ids [description]
     * @return [boolen]      [description]
     */
    public function del($ids = []){
        addLog('删除车辆,车辆id:'.implode(',',$ids));
        return Car::destroy($ids);
    }


}