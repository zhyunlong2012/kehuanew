<?php
namespace app\model;
use think\Model;
use think\model\concern\SoftDelete;
/**
 * 财务表格
 */
class Finance extends Model{
    use SoftDelete;
    protected $deleteTime = 'delete_time';
    // 设置字段信息
    protected $schema = [
        'id'          => 'int',
        'user_id'    => 'int',
        'shop_id'    => 'int',
        'money'       => 'string',
        'forward'      => 'string',  //in收入 out支出
        'oprate_time'      => 'int',
        'create_time' => 'int',
        'update_time' => 'int',
        'delete_time' => 'int',
    ];

    // 设置字段自动转换类型
    protected $type = [
        'oprate_time' => 'timestamp',
    ];


    /**
     * 添加产品
     */
    public function add($user_id,$shop_id,$money,$forward,$oprate_time){
        $finance_model = new Finance();
        $finance_model->user_id = $user_id;
        $finance_model->shop_id = $shop_id;
        $finance_model->money = $money;
        $finance_model->forward = $forward;
        $finance_model->oprate_time = $oprate_time;
        addLog('添加流水');
        if($finance_model->save() == true){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 返回产品列表
     * @param  integer $current  [description]
     * @param  integer $pageSize [description]
     * @param  integer $status   [description]
     * @param  string  $pro_name [description]
     * @param  string  $pro_code [description]
     * @return [type]            [description]
     */
    public function list($current=1,$pageSize=10,$shop_id,$forward,$oprate_time='',$excel=false){
        $map = [];
        if(!empty($shop_id)){$map[] = ['shop_id','=',$shop_id];}
        if(!empty($forward)){$map[]=['forward','=',$forward];}
        if(!empty($oprate_time)){
            $a = $oprate_time;
            $between_time = [strtotime($a['begin_time']) ,strtotime($a['end_time'])];
            $map[] = ['oprate_time','between',$between_time];
        }
        if($excel){
            $data['data'] = $this->where($map)->order('id','desc')->select();
        }else{
            $data['data'] = $this->where($map)->page($current,$pageSize)->order('id','desc')->select();
        }
        $data['total'] =  $this->where($map)->count();
        $data['current'] = $current;
        $data['pageSize'] = $pageSize;
        $data['success'] = true;

        $shop_model = new Shop();
        $user_model = new User();
        foreach ($data['data'] as $key => $value) {
            $tmp = $shop_model-> where('id',$value['shop_id'])->find();
            $data['data'][$key]['shop_name']= $tmp?$tmp['shop_name']:'注销门店';
            $tmp_user = $user_model-> where('id',$value['user_id'])->find();
            $data['data'][$key]['username']= $tmp_user?$tmp_user['username']:'注销用户';
        }
        return json($data);
    }

    /**
     * 修改信息
     *
     * @return void
     */
    public function updata($id,$user_id,$shop_id,$money,$forward,$oprate_time){
        $res = Finance::find($id);
        if (empty($res)) {
            return false;
        }
        addLog('修改流水:'.$id);

        $res->user_id = $user_id;
        $res->shop_id = $shop_id;
        $res->money = $money;
        $res->forward = $forward;
        $res->oprate_time = $oprate_time;
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
        addLog('删除产品,产品id:'.implode(',', $ids));
        return Finance::destroy($ids);
    }

}