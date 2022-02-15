<?php
namespace app\model;
use think\Model;
use think\model\concern\SoftDelete;
/**
 * 用户组表格
 */
class UserGroup extends Model{
    use SoftDelete;
    protected $deleteTime = 'delete_time';
    // 设置字段信息
    protected $schema = [
        'id'          => 'int',
        'groupname'   => 'string',
        'status'      => 'int',
        'create_time' => 'int',
        'update_time' => 'int',
        'delete_time' => 'int',
    ];

    //关联用户
    public function user()
    {
        return $this->hasMany(User::class);
    }

    /**
     * 添加用户组
     * @param [type]  $groupname [description]
     * @param integer $status    [description]
     */
    public function add($groupname,$status=1){
        $res = UserGroup::where('groupname', $groupname)->findOrEmpty();
        if (!$res->isEmpty()) {
            return false;
        }

        $user_group_model = new UserGroup();
        $user_group_model->groupname = $groupname;
        $user_group_model->status = $status;
        if($user_group_model->save() == true){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 返回用户组列表
     *
     * @param integer $current
     * @param integer $pageSize
     * @param integer $status
     * @param string $groupname
     * @return void
     */
    public function list($current=1,$pageSize=10,$status=0,$groupname=''){
        $user_group_model = new UserGroup();
        $map = [];

        if(!empty($status)){
            $map[] = ['status','=',$status];
        }
        if(!empty($groupname)){
            $map[]=['groupname','like','%'.$groupname.'%'];
        }

        $data['data'] = $user_group_model->where($map)->page($current,$pageSize)->order('id','desc')->select();
        $data['total'] =  $user_group_model->where($map)->count();
        $data['current'] = $current;
        $data['pageSize'] = $pageSize;
        $data['success'] = true;
        
        return json($data);
    }

    /**
     * 修改信息
     *
     * @param integer $id
     * @param string $groupname,
     * @param integer $status
     * @return void
     */
    public function updata(int $id,string $groupname,int $status=1){
        if((empty($groupname))||(empty($id))){
            return false;
        }
        $res = UserGroup::find($id);
        if (empty($res)) {
            return false;
        }
        addLog('修改用户组,新用户组名称:'.$groupname);

        $res->groupname = $groupname;
        if($status!==3){$res->status = $status;};
        if($res->save() == true){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 删除用户组
     * @param  array  $ids [description]
     * @return [boolen]      [description]
     */
    public function delgroup($ids = []){
        return UserGroup::destroy($ids);
    }

}