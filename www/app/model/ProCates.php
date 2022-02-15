<?php
namespace app\model;
use think\Model;
use think\model\concern\SoftDelete;
/**
 * 产品种类表格
 */
class ProCates extends Model{
    use SoftDelete;
    protected $deleteTime = 'delete_time';
    // 设置字段信息
    protected $schema = [
        'id'          => 'int',
        'pid'         => 'int',
        'cates_name'  => 'string',
        'cates_code'  => 'code',
        'employs'     => 'string',
        'status'      => 'int',  //1正常 2停用
        'create_time' => 'int',
        'update_time' => 'int',
        'delete_time' => 'int',
    ];


    /**
     * 添加
     *
     * @param [type] $cates_name
     * @param integer $status
     * @return void
     */
    public function add($pid,$cates_name,$cates_code,$employs='[]',$status=1){
        $res = ProCates::where('cates_code', $cates_code)->findOrEmpty();
        if (!$res->isEmpty()) {
            return $this->updata($res->id,$pid,$cates_name,$cates_code,$employs,$status);
        }

        $pro_cates_model = new ProCates();
        $pro_cates_model->pid = $pid;
        $pro_cates_model->cates_name = $cates_name;
        $pro_cates_model->cates_code = $cates_code;
        $pro_cates_model->employs = $employs?$employs:'[]';
        $pro_cates_model->status = $status;
        addLog('添加产品分类'.$cates_name);
        if($pro_cates_model->save() == true){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 列表
     *
     * @param integer $current
     * @param integer $pageSize
     * @param [type] $cates_name
     * @param [type] $status
     * @param boolean $excel
     * @return void
     */
    public function list($current=1,$pageSize=10,$pid,$cates_name,$cates_code,$status,$excel=false,$tree=false){
        $map = [];
        if(!empty($pid)){$map[]=['pid','=',$pid];}
        if(!empty($cates_name)){$map[]=['cates_name','like','%'.$cates_name.'%'];}
        if(!empty($cates_code)){$map[]=['cates_code','like','%'.$cates_code.'%'];}
        if(!empty($status)){$map[]=['status','=',$status];}

        $uid = getUid();
        $user_model = new User();
        $user = $user_model ->where('id',$uid) ->find();
        if($user['user_group_id']!==1){
            $auth_ids  = $this->get_auth($uid);
            if(count($auth_ids)==0){
                $data = [
                    'data' => [],
                    'total' =>0,
                    'success' => true
                ];
                return json($data);
            }else{
                $map[] = ['id','in',$auth_ids];
            }
            
        }
        
        if($excel){
            $cates = $this->where($map)->order('id','desc')->select()->toArray();
        }else{
            $cates = $this->where($map)->page($current,$pageSize)->order('id','desc')->select()->toArray();
        }
        //用户管理员ID，装换为登录用户名
        $user_model = new User();

        //antd treeSelect dataform
        $tree_data = [];
        if($tree){
            foreach($cates as $value){
                $tmp = json_decode($value['employs'],true);
                $tmp2=[];
                if(count($tmp)>0){
                    foreach ($tmp as $key1 => $value1) {
                    $_tmp = $user_model-> where('id',$value1)->find();
                    $tmp2[$key1] =$_tmp?$_tmp['username']:'注销用户';
                    }
                }

                $tree_data[] = [
                    'id' => $value['id'],
                    'value' => $value['id'],
                    'pid' => $value['pid'],
                    'title' => $value['cates_name'],
                    'employs' => $tmp,
                    'employsname' => $tmp2,
                ];
            }
        }else{
            $tree_data = $cates;
            foreach ($tree_data as $key => $value) {
                $tmp = json_decode($value['employs'],true);
                $tree_data[$key]['employs'] = $tmp;
                if(count($tmp)>0){
                    foreach ($tmp as $key1 => $value1) {
                       $_tmp = $user_model-> where('id',$value1)->find();
                       $tmp[$key1] =$_tmp?$_tmp['username']:'注销用户';
                    }
                }
                $tree_data[$key]['employsname']= $tmp;
            }
        }


        $data['data'] = recursive($tree_data,0);
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
     * @param [type] $number
     * @param [type] $driver
     * @param string $uid
     * @param integer $status
     * @return void
     */
    public function updata($id,$pid,$cates_name,$cates_code,$employs,$status=1){
        if((empty($cates_name))||(empty($id))){
            return false;
        }
        $res = ProCates::find($id);
        if (empty($res)) {
            return false;
        }
        addLog('修改分类:'.$cates_name);
        $res->pid = $pid;
        $res->cates_name = $cates_name;
        $res->cates_code = $cates_code;
        $res->employs = $employs;
        if($status!==3){$res->status = $status;}
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
        addLog('删除分类，id:'.implode(',', $ids));
        return ProCates::destroy($ids);
    }

    /**
     * 返回有权限的列表
     *
     * @return array
     */
    public function get_auth($uid){
        $auth_pro_cates_ids = [];
        $pro_cates = $this->select()->toArray();
        // dump($pro_cates);
        foreach($pro_cates as $value){
            $ids = json_decode($value['employs'],true);
            if(count($ids)<=0){continue;}
            if(in_array($uid,$ids)){
                $auth_pro_cates_ids[]=$value['id'];
            }
        }
        return $auth_pro_cates_ids;
    }


}