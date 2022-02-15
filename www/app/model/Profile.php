<?php
namespace app\model;
use think\Model;
use think\model\concern\SoftDelete;
/**
 * 用户档案表格
 */
class Profile extends Model{
    use SoftDelete;
    protected $deleteTime = 'delete_time';
    // 设置字段信息
    protected $schema = [
        'id'          => 'int',
        'user_id'     => 'int',
        'nickname'    => 'sting',
        'sex'         => 'int',
        'email'       => 'string',
        'card'        => 'string',
        'thumb_url'    => 'string',
        'province'    => 'string',
        'city'        => 'string',
        'area'        => 'string',
        'address'     => 'string',
        'create_time' => 'int',
        'update_time' => 'int',
        'delete_time' => 'int',
    ];

    //关联用户组
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 添加用户档案
     * @param int  $user_id  [description]
     * @param string  $nickname [description]
     * @param integer $sex      [description]
     * @param string  $email    [description]
     * @param string  $card     [description]
     * @param int  $thumb_url [description]
     * @param string  $province [description]
     * @param string  $city     [description]
     * @param string  $area     [description]
     * @param string  $address  [description]
     */
    public function add($user_id='',$nickname='',$sex=1,$email='',$card='',$thumb_url='',$province='',$city='',$area='',$address=''){
        $profile_model = new Profile();
        $profile_model->user_id = $user_id;
        $res = $this->where('user_id',$user_id)->find();
        if($res){
            return $this->updata($res['id'],$user_id,$nickname,$sex,$email,$card,$thumb_url,$province,$city,$area,$address);
        }
        $profile_model->nickname =$nickname;
        $profile_model->sex = $sex;
        $profile_model->email = $email;
        $profile_model->card = $card;
        $profile_model->thumb_url = $thumb_url;
        $profile_model->province = $province;
        $profile_model->city = $city;
        $profile_model->area = $area;
        $profile_model->address = $address;
        if($profile_model->save() == true){
            addLog('添加用户档案,档案ID：'.$profile_model->id);
            return true;
        }else{
            return false;
        }
    }

    /**
     * 修改信息
     *
     * @return void
     */
    public function updata($id,$user_id='',$nickname='',$sex=1,$email='',$card='',$thumb_url='',$province='',$city='',$area='',$address=''){
        if((empty($user_id))||(empty($id))){
            return false;
        }
        $res = Profile::find($id);
        if (empty($res)) {
            return false;
        }
        addLog('修改用户档案,用户id:'.$user_id.'原用户名'.$res['nickname']);

        $res->user_id = $user_id;
        $res->nickname =$nickname;
        $res->sex = $sex;
        $res->email = $email;
        $res->card = $card;
        $res->thumb_url = $thumb_url;
        $res->province = $province;
        $res->city = $city;
        $res->area = $area;
        $res->address = $address;
        if($res->save() == true){
            return true;
        }else{
            return false;
        }
    }

}