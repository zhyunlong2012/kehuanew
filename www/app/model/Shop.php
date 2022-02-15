<?php
namespace app\model;
use think\Model;
use think\model\concern\SoftDelete;
/**
 * 店铺表格
 */
class Shop extends Model{
    use SoftDelete;
    protected $deleteTime = 'delete_time';
    // 设置字段信息
    protected $schema = [
        'id'          => 'int',
        'shop_name'    => 'string',
        'shop_code'    => 'string',
        'introduce'   => 'string',
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
    ];
    


    /**
     * 添加
     * @param [type]  $shop_name  [description]
     * @param [type]  $shop_code  [description]
     * @param string  $employs  [管理员]
     * @param integer $status   [description]
     * @param [type]  $province [description]
     * @param [type]  $city     [description]
     * @param [type]  $area     [description]
     * @param [type]  $address  [description]
     * @param [type]  $tel      [description]
     */
    public function add($shop_name,$shop_code,$introduce,$employs='',$status=1,$province,$city,$area,$address,$tel){
        $res = Shop::where('shop_name', $shop_name)->findOrEmpty();
        if (!$res->isEmpty()) {
            return false;
        }

        $shop_model = new Shop();
        $shop_model->shop_name = $shop_name;
        $shop_model->shop_code = $shop_code;
        $shop_model->introduce = $introduce;
        $shop_model->employs = $employs?$employs:'[]';
        $shop_model->status = $status;
        $shop_model->province = $province;
        $shop_model->city = $city;
        $shop_model->area = $area;
        $shop_model->address = $address;
        $shop_model->tel = $tel;
        if($shop_model->save() == true){
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
     * @param  string  $shop_name  [description]
     * @param  string  $shop_code  [description]
     * @return [type]            [description]
     */
    public function list($current,$pageSize,$status,$shop_name,$shop_code,$excel=false){
        $map = [];
        if(!empty($status)){$map[] = ['status','=',$status];}
        if(!empty($shop_name)){$map[]=['shop_name','like','%'.$shop_name.'%'];}
        if(!empty($shop_code)){$map[]=['shop_code','like','%'.$shop_code.'%'];}
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
        foreach ($data['data'] as $key => $value) {
            if($value['employs']=='null'){
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
        
        return json($data);
    }

    /**
     * 修改信息
     *
     * @return void
     */
    public function updata($id,$shop_name,$shop_code,$introduce,$employs='',$status=1,$province,$city,$area,$address,$tel){
        if((empty($shop_name))||(empty($id))){
            return false;
        }
        $res = Shop::find($id);
        if (empty($res)) {
            return false;
        }

        $res->shop_name = $shop_name;
        $res->shop_code = $shop_code;
        $res->introduce = $introduce;
        $res->employs = $employs;
        if($status!==3){$res->status = $status;}
        $res->province = $province;
        $res->city = $city;
        $res->area = $area;
        $res->address = $address;
        $res->tel = $tel;
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
        addLog('删除店铺,店铺id:'.implode(',',$ids));
        return Shop::destroy($ids);
    }

    /**
     * 验证用户是否是管理员
     *
     * @param [type] $user_id
     * @return boolean
     */
    public function isShopAdmin($user_id){
        $shops = Shop::where('status',1)->json(['employs'],true)->select()->toArray();
        $admin_shop = [];
        foreach($shops as $value){
            if(in_array($user_id,$value['employs'])){
                $admin_shop[] = $value;
            }
        }
        return $admin_shop;
    }

}