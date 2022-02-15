<?php
namespace app\model;
use think\Model;
use think\model\concern\SoftDelete;

use function GuzzleHttp\json_encode;

/**
 * 用户表格
 */
class User extends Model{
    use SoftDelete;
    protected $deleteTime = 'delete_time';
    // 设置字段信息
    protected $schema = [
        'id'          => 'int',
        'username'        => 'string',
        'password'      => 'string',
        'user_group_id' => 'int',
        'tel'      => 'string',
        'status'       => 'int',
        'create_time' => 'int',
        'update_time' => 'int',
        'login_time' => 'int',
        'delete_time' => 'int',
        'access'  => 'string',
    ];

    // 设置字段自动转换类型
    protected $type = [
        'login_time' => 'timestamp',
    ];

    //关联用户组
    public function userGroup()
    {
        return $this->hasOne(UserGroup::class,'id','user_group_id')->bind([
                'groupname',
            ]);
    }

    //关联用户档案
    public function profile()
    {
        return $this->hasOne(Profile::class, 'user_id')->bind([
                'email',
                'truename'  => 'nickname',
            ]);
    }

    /**
     * 用户登录
     *
     * @param [type] $username
     * @param string $password
     * @return void
     */
    public function login($username,$password){
        // echo password_hash('123456', PASSWORD_DEFAULT);
        $user = User::where('username',$username)->find();
        if(!$user){
            return false;
        }
        if(password_verify($password,$user['password'])){
            $user->login_time = time();
            if($user->save()){
                addLog('登录系统',$user['id']);
                return $user;
            }else{
                return $user;
            }
            
        }else{
            return false;
        }
    }

    /**
     * 添加用户
     * @param  [type]  $username      [description]
     * @param  string  $password      [description]
     * @param  string  $tel           [description]
     * @param  integer $user_group_id [description]
     * @return [type]                 [description]
     */
    public function add($username,$password,$tel='',$user_group_id = 2,$truename='',$access='[]'){
        if(empty($username)||empty($password)||empty($user_group_id)){
            return false;
        }

        $user_res = User::where('username', $username)->findOrEmpty();
        if (!$user_res->isEmpty()) {
            return false;
        }

        $user = new User();
        $user->username = $username;
        $user->password = password_hash($password, PASSWORD_DEFAULT);
        $user->tel = $tel;
        $user->user_group_id = $user_group_id;
        $user->access = json_encode($access);
        $profile = new Profile();
        $profile->nickname = $truename;
        $user->profile= $profile;
        $res = $user->together(['profile'])->save();

        if($res){
            addLog('添加账户,账户id:'.$user->id.'登录账户'.$username);
            return true;
        }else{
            return false;
        }
    }

    /**
     * 返回用户列表
     *
     * @param integer $current
     * @param integer $pageSize
     * @param integer $status
     * @param string $username
     * @param string $tel
     * @return void
     */
    public function list($current=1,$pageSize=10,$status=1,$username='',$tel=''){
        $user_model = new User();
        $map = [];
        if(!empty($status)){ $map[] = ['status','=',$status]; }
        if(!empty($username)){ $map[]=['username','like','%'.$username.'%'];}
        if(!empty($tel)){ $map[]=['tel','like','%'.$tel.'%']; }
        $data['data'] = $user_model
        ->with(['userGroup','profile'])
        ->where($map)
        ->withoutField('password')
        ->page($current,$pageSize)
        ->order('id','desc')
        ->select();

        $data['total'] =  $user_model->where($map)->count();
        $data['current'] = $current;
        $data['pageSize'] = $pageSize;
        $data['success'] = true;
        
        return json($data);
    }

    /**
     * 修改用户信息
     *
     * @param integer $id
     * @param string $username
     * @param string $tel
     * @param integer $user_group_id
     * @param integer $status
     * @return void
     */
    public function updata($id,$username,$tel,$user_group_id='',$status=1,$password='',$truename='',$access='[]'){
        $res = User::find($id);
        if (empty($res)) {
            return false;
        }

        if($username){$res->username = $username;}
        if($tel){$res->tel = $tel;}
        if($user_group_id){$res->user_group_id = $user_group_id;}
        if($status!==3){$res->status = $status;}
        if($password){$res->password = password_hash($password, PASSWORD_DEFAULT);}
        if($access){$res->access = json_encode($access);}
        if($truename){$res->profile->nickname = $truename;}

        if($res->together(['profile'])->save() == true){
            addLog('修改账户,账户名称:'.$username);
            return true;
        }else{
            return false;
        }
    }

    /**
     * 删除用户
     * @param  array  $ids [description]
     * @return [boolen]      [description]
     */
    public function deluser($ids = []){
        addLog('删除账户,id:'.implode(',', $ids));
        return User::destroy($ids);
    }

    /**
     * 用户资料
     * @param  array  $ids [description]
     * @return [boolen]      [description]
     */
    public function info($id){
        $user = $this->field('id,username,tel,access')->withJoin([
            'profile'	=>	['nickname', 'email'],
            'userGroup' => ['groupname']
        ])->find($id);
        return $user;
    }

}