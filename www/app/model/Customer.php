<?php
namespace app\model;
use think\Model;
use think\model\concern\SoftDelete;
/**
 * 客户表格
 */
class Customer extends Model{
    use SoftDelete;
    protected $deleteTime = 'delete_time';
    // 设置字段信息
    protected $schema = [
        'id'          => 'int',
        'cus_name'    => 'string',
        'cus_code'    => 'string',
        'cus_cates_id'=> 'int',
        'tel'         => 'string',
        'province'    => 'string',
        'city'        => 'string',
        'area'        => 'string',
        'address'     => 'string',
        'employs'     => 'string',
        'status'      => 'int',
        'create_time' => 'int',
        'update_time' => 'int',
        'delete_time' => 'int',
        'distance' => 'string'
    ];
    


    /**
     * 添加客户
     * @param [type]  $cusname  [description]
     * @param [type]  $cuscode  [description]
     * @param string  $employs  [管理员]
     * @param integer $status   [description]
     * @param [type]  $province [description]
     * @param [type]  $city     [description]
     * @param [type]  $area     [description]
     * @param [type]  $address  [description]
     * @param [type]  $tel      [description]
     */
    public function add($cusname,$cuscode,$employs='',$status=1,$province,$city,$area,$address,$tel,$cus_cates_id='',$distance=2,$upexist=1){
        $res = Customer::where('cus_name', $cusname)->find();
        if ($res) {
            if($upexist ==2){
                return $this->updata($res->id,$cusname,$cuscode,$employs='',$status=1,$province,$city,$area,$address,$tel,$cus_cates_id,$distance, $upexist);
            }else{
                return false;
            }
        }
        $cus_model = new Customer();
        $cus_model->cus_name = $cusname;
        $cus_model->cus_code = $cuscode;
        $cus_model->employs = $employs?$employs:'[]';
        $cus_model->status = $status;
        $cus_model->province = $province;
        $cus_model->city = $city;
        $cus_model->area = $area;
        $cus_model->address = $address;
        $cus_model->tel = $tel;
        $cus_model->distance = $distance;
        $cus_model->cus_cates_id = $cus_cates_id;
        if($cus_model->save() == true){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 返回客户列表
     * @param  integer $current  [description]
     * @param  integer $pageSize [description]
     * @param  integer $status   [description]
     * @param  string  $cusname  [description]
     * @param  string  $cuscode  [description]
     * @return [type]            [description]
     */
    public function list($current,$pageSize,$status,$cusname,$cuscode,$excel=false,$cus_cates_id=''){
        $uid = getUid();
        $user_model = new User();
        $user = $user_model ->where('id',$uid) ->find();
        
        $data = [
            'data' => [],
            'total' =>0,
            'success' => true
        ];
        if(!$user){return json($data);}
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
        if(!empty($cusname)){$map[]=['cus_name','like','%'.$cusname.'%'];}
        if(!empty($cuscode)){$map[]=['cus_code','like','%'.$cuscode.'%'];}
        if(!empty($cus_cates_id)){$map[]=['cus_cates_id','=',$cus_cates_id];}
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
        $cus_cates_model = new CustomerCates();
        foreach ($data['data'] as $key => $value) {
            $cates = $cus_cates_model-> where('id',$value['cus_cates_id'])->find();
            $data['data'][$key]['cus_cates_name'] =$cates?$cates['cates_name']:'注销分类';

            if($value['employs']==NULL){
                $tmp[0] = '未指定用户';
            }else{
                $tmp = json_decode($value['employs'],true);
                $data['data'][$key]['employs'] = $tmp;
                if(count($tmp)>0){
                    foreach ($tmp as $key1 => $value1) {
                    $_tmp = $user_model-> where('id',$value1)->find();
                    $tmp[$key1] =$_tmp?$_tmp['username']:'注销用户';
                    }
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
    public function updata($id,$cusname,$cuscode,$employs='',$status=1,$province,$city,$area,$address,$tel,$cus_cates_id='',$distance=2,$upexist=1){
        if((empty($cusname))||(empty($id))){
            return false;
        }
        $res = Customer::find($id);
        if (empty($res)) {
            return false;
        }

        $res->cus_name = $cusname;
        $res->cus_code = $cuscode;
        $res->address = $address;
        $res->tel = $tel;
        $res->cus_cates_id = $cus_cates_id;
        $res->distance = $distance;
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
     * 删除客户
     * @param  array  $ids [description]
     * @return [boolen]      [description]
     */
    public function del($ids = []){
        addLog('删除客户,客户id:'.implode(',',$ids));
        return Customer::destroy($ids);
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