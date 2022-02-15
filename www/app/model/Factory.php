<?php
namespace app\model;
use think\Model;
use think\model\concern\SoftDelete;
/**
 * 供应商表格
 */
class Factory extends Model{
    use SoftDelete;
    protected $deleteTime = 'delete_time';
    // 设置字段信息
    protected $schema = [
        'id'          => 'int',
        'fac_name'    => 'string',
        'fac_code'    => 'string',
        'tel'         => 'string',
        'province'    => 'string',
        'city'        => 'string',
        'area'        => 'string',
        'address'     => 'string',
        'employs'     => 'string',
        'fac_cates_id'=> 'int',
        'status'      => 'int',
        'create_time' => 'int',
        'update_time' => 'int',
        'delete_time' => 'int',
    ];


    /**
     * 添加供应商
     * @param [type]  $facname  [description]
     * @param [type]  $faccode  [description]
     * @param string  $employs  [管理员]
     * @param integer $status   [description]
     * @param [type]  $province [description]
     * @param [type]  $city     [description]
     * @param [type]  $area     [description]
     * @param [type]  $address  [description]
     * @param [type]  $tel      [description]
     * @param int  $upexist      [1是添加，2是如果存在则更新]
     */
    public function add($facname,$faccode,$employs,$status=1,$province,$city,$area,$address,$tel,$fac_cates_id='',$upexist=1){
        $res = Factory::where('fac_code', $faccode)->find();
        if ($res) {
            if($upexist ==2){
                return $this->updata($res->id, $facname, $faccode, $employs, $status, $province, $city, $area, $address, $tel, $fac_cates_id, $upexist);
            }else{
                return false;
            }
        }
        $fac_model = new Factory();
        $fac_model->fac_name = $facname;
        $fac_model->fac_code = $faccode;
        $fac_model->employs = $employs?$employs:'[]';
        $fac_model->status = $status;
        $fac_model->province = $province;
        $fac_model->city = $city;
        $fac_model->area = $area;
        $fac_model->address = $address;
        $fac_model->tel = $tel;
        $fac_model->fac_cates_id = $fac_cates_id;
        addLog('添加供应商,供应商名称:'.$facname);
        if($fac_model->save() == true){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 返回供应商列表
     * @param  integer $current  [description]
     * @param  integer $pageSize [description]
     * @param  integer $status   [description]
     * @param  string  $facname  [description]
     * @param  string  $faccode  [description]
     * @return [type]            [description]
     */
    public function list($current,$pageSize,$status,$facname,$faccode,$excel=false,$fac_cates_id=''){
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
        if(!empty($facname)){$map[]=['fac_name','like','%'.$facname.'%'];}
        if(!empty($faccode)){$map[]=['fac_code','like','%'.$faccode.'%'];}
        if(!empty($fac_cates_id)){$map[]=['fac_cates_id','=',$fac_cates_id];}
        if($excel){
            $data['data'] = $this->where($map)->order('id','asc')->select();
        }else{
            $data['data'] = $this->where($map)->page($current,$pageSize)->order('id','asc')->select();
        }
        $data['total'] =  $this->where($map)->count();
        $data['current'] = $current;
        $data['pageSize'] = $pageSize;
        $data['success'] = true;

        
        //用户管理员ID，装换为登录用户名
        $user_model = new User();
        $fac_cates_model = new FactoryCates();
        foreach ($data['data'] as $key => $value) {
            $cates = $fac_cates_model-> where('id',$value['fac_cates_id'])->find();
            $data['data'][$key]['fac_cates_name'] =$cates?$cates['cates_name']:'注销分类';

            $tmp = json_decode($value['employs'],true);
            $data['data'][$key]['employs'] = $tmp;
            if(count($tmp)>0){
                foreach ($tmp as $key1 => $value1) {
                $_tmp = $user_model-> where('id',$value1)->find();
                $tmp[$key1] =$_tmp?$_tmp['username']:'注销用户';
                }
            }
            $data['data'][$key]['employsname']= $tmp;
            
        }
        
        // //用户管理员ID，装换为档案用户名
        // $profile_model = new Profile();
        // foreach ($data['data'] as $key => $value) {
        //     $tmp = json_decode($value['employs'],true);
        //     if(count($tmp)>0){
        //         foreach ($tmp as $key1 => $value1) {
        //            $_tmp = $profile_model-> where('user_id',$value1)->find();
        //            $tmp[$key1] =$_tmp?$_tmp['nickname']:'注销用户';
        //         }
        //     }
        //     $data['data'][$key]['employs']= $tmp;
        // }
        return json($data);
    }

    /**
     * 修改信息
     *
     * @return void
     */
    public function updata($id,$facname,$faccode,$employs='',$status=1,$province,$city,$area,$address,$tel,$fac_cates_id='',$upexist=1){
        if((empty($faccode))||(empty($id))){
            return false;
        }
        $res = Factory::find($id);
        if (empty($res)) {
            return false;
        }
        addLog('修改供应商,供应商名称:'.$facname);
        $res->fac_name = $facname;
        $res->fac_code = $faccode;
        $res->fac_cates_id = $fac_cates_id;
        $res->tel = $tel;
        $res->address = $address;
        if($upexist!=2){
            $res->employs = $employs;
            if($status!==3){$res->status = $status;}
            $res->province = $province;
            $res->city = $city;
            $res->area = $area;
        }
        if($res->save() == true){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 删除供应商
     * @param  array  $ids [description]
     * @return [boolen]      [description]
     */
    public function del($ids = []){
        addLog('删除供应商,供应商id:'.implode(',',$ids));
        return Factory::destroy($ids);
    }

    /**
     * 返回有权限的列表
     *
     * @return array
     */
    public function get_auth($uid){
        $user_model = new User();
        $user = $user_model ->where('id',$uid)->find();
        if(!$user){return [];}
        if($user['user_group_id']==1){
            $factorys = $this->column('id');
            return $factorys;
        }
        $auth_factory_ids = [];
        $factorys = $this->select()->toArray();
        foreach($factorys as $value){
            $ids = json_decode($value['employs'],true);
            if(count($ids)<=0){continue;}
            if(in_array($uid,$ids)){
                $auth_factory_ids[]=$value['id'];
            }
        }
        return $auth_factory_ids;
    }


}