<?php
namespace app\model;
use think\Model;
use think\model\concern\SoftDelete;
/**
 * 库区表格
 */
class Position extends Model{
    use SoftDelete;
    protected $deleteTime = 'delete_time';
    // 设置字段信息
    protected $schema = [
        'id'          => 'int',
        'pos_name'    => 'string',
        'pos_code'    => 'string',
        'area'        => 'string',
        'size'        => 'string',
        'employs'     => 'string',
        'status'      => 'int',
        'create_time' => 'int',
        'update_time' => 'int',
        'delete_time' => 'int',
    ];


    /**
     * 添加库区
     * @param [type]  $pos_name [description]
     * @param [type]  $pos_code [description]
     * @param integer $area     [description]
     * @param integer $size     [容积]
     * @param string  $employs  [description]
     * @param integer $status   [description]
     */
    public function add($pos_name,$pos_code,$area=0,$size=0,$employs='',$status=1){
        $res = Position::where('pos_code', $pos_code)->findOrEmpty();
        if (!$res->isEmpty()) {
            return false;
        }
        $pos_model = new Position();
        $pos_model->pos_name = $pos_name;
        $pos_model->pos_code = $pos_code;
        $pos_model->area = $area;
        $pos_model->size = $size;
        $pos_model->employs = $employs;
        $pos_model->status = $status;
        addLog('添加库区,库区编号:'.$pos_code);
        if($pos_model->save() == true){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 库区列表
     * @param  integer $current  [description]
     * @param  integer $pageSize [description]
     * @param  integer $status   [description]
     * @param  string  $pos_name [description]
     * @param  string  $pos_code [description]
     * @return [type]            [description]
     */
    public function list($current=1,$pageSize=10,$status=0,$pos_name='',$pos_code='',$excel,$tree=false){
        $uid = getUid();
        $user_model = new User();
        $user = $user_model ->where('id',$uid) ->find();
        
        $data = [
            'data' => [],
            'total' =>0,
            'success' => true
        ];
        if(!$user){
            return json($data);
        }
        
        $map = [];
        if($user['user_group_id']!==1){
            $auth_ids  = $this->get_auth($uid);
            if(count($auth_ids)==0){
                return json($data);
            }else{
                $map[] = ['id','in',$auth_ids];
            }
            
        }

        if(!empty($status)){$map[] = ['status','=',$status];}
        if(!empty($pos_name)){$map[]=['pos_name','like','%'.$pos_name.'%'];}
        if(!empty($pos_code)){$map[]=['pos_code','like','%'.$pos_code.'%'];}
        if($excel){
            $data['data'] = $this->where($map)->order('id','desc')->select();
        }else{
            $data['data'] = $this->where($map)->page($current,$pageSize)->order('id','desc')->select();
        }

        if($tree){
            foreach($data['data'] as $key => $value){
                $data['data'][$key]['title'] = $value['pos_name'];
                $data['data'][$key]['value'] = $value['id'];
            }
        }

        $data['total'] =  $this->where($map)->count();
        $data['current'] = $current;
        $data['pageSize'] = $pageSize;
        $data['success'] = true;

        
        //用户管理员ID，装换为登录用户名
        $user_model = new User();
        $storage_check_model = new StorageCheck();
        foreach ($data['data'] as $key => $value) {
            $tmp = json_decode($value['employs'],true);
            $data['data'][$key]['employs'] = $tmp;
            if(count($tmp)>0){
                foreach ($tmp as $key1 => $value1) {
                   $_tmp = $user_model-> where('id',$value1)->find();
                   $tmp[$key1] =$_tmp?$_tmp['username']:'注销用户';
                }
            }
            $data['data'][$key]['employsname']= $tmp;
            $tmp_sto_check = $storage_check_model ->where('position_id',$value['id'])->where('status',2)->order('id','desc')->limit(1)->select();
            
            if(count($tmp_sto_check)==1){
                $data['data'][$key]['pan_time'] = $tmp_sto_check[0]['create_time'];
            }else{
                $data['data'][$key]['pan_time'] = '未盘点';
            }
        }
        return json($data);
    }

    /**
     * 修改信息
     * @param  [type]  $id       [description]
     * @param  [type]  $pos_name [description]
     * @param  [type]  $pos_code [description]
     * @param  integer $area     [description]
     * @param  integer $size     [description]
     * @param  string  $employs  [description]
     * @param  integer $status   [description]
     * @return [type]            [description]
     */
    public function updata($id,$pos_name,$pos_code,$area=0,$size=0,$employs='',$status=1){
        if((empty($pos_name))||(empty($id))){
            return false;
        }
        $res = Position::find($id);
        if (empty($res)) {
            return false;
        }
        addLog('修改库区,库区名称:'.$pos_name);

        $res->pos_name = $pos_name;
        $res->pos_code = $pos_code;
        $res->area = $area;
        $res->size = $size;
        $res->employs = $employs;
        if($status!==3){$res->status = $status;}
        if($res->save() == true){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 删除库区
     * @param  array  $ids [description]
     * @return [boolen]      [description]
     */
    public function del($ids = []){
        addLog('删除库区,库区id:'.implode(',', $ids));
        return Position::destroy($ids);
    }

    /**
     * 返回有权限的列表
     *
     * @return array
     */
    public function get_auth($uid){
        $auth_position_ids = [];
        $positions = $this->select()->toArray();
        foreach($positions as $value){
            $ids = json_decode($value['employs'],true);
            if(count($ids)<=0){continue;}
            if(in_array($uid,$ids)){
                $auth_position_ids[]=$value['id'];
            }
        }
        return $auth_position_ids;
    }

}