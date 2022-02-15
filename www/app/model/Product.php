<?php
namespace app\model;
use think\Model;
use think\model\concern\SoftDelete;
/**
 * 产品表格
 */
class Product extends Model{
    use SoftDelete;
    protected $deleteTime = 'delete_time';
    // 设置字段信息
    protected $schema = [
        'id'          => 'int',
        'pro_name'    => 'string',
        'pro_code'    => 'string',
        'factory_id'  => 'int',
        'pro_cates_id'  => 'int',
        'price'       => 'string',
        'weight'      => 'string',
        'size'        => 'string',
        'high_line'   => 'string',
        'low_line'    => 'string',
        'other_code'  => 'string',
        'status'      => 'int',
        'create_time' => 'int',
        'update_time' => 'int',
        'delete_time' => 'int',
    ];


    /**
     * 添加产品
     * @param string  $pro_name   [description]
     * @param string  $pro_code   [description]
     * @param integer  $factory_id [description]
     * @param integer $price      [description]
     * @param integer $weight     [description]
     * @param integer $size       [体积]
     * @param integer $high_line [description]
     * @param integer $low_line   [description]
     * @param integer $status     [description]
     */
    public function add($pro_name,$pro_code,$pro_cates_id,$price=0,$weight=0,$size=0,$high_line=0,$low_line=0,$other_code,$status=1,$upexist=1){
        $res = Product::where('pro_code', $pro_code)->find();
        if ($res) {
            if($upexist ==2){
                return $this->updata($res->id,$pro_name,$pro_code,$pro_cates_id,$price,$weight,$size,$high_line,$low_line,$other_code,$status,$upexist);
            }else{
                return false;
            }
        }
        $pro_model = new Product();
        $pro_model->pro_name = $pro_name;
        $pro_model->pro_code = $pro_code;
        $pro_model->pro_cates_id = $pro_cates_id;
        $pro_model->price = $price;
        $pro_model->weight = $weight;
        $pro_model->size = $size;
        $pro_model->high_line = $high_line;
        $pro_model->low_line = $low_line;
        $pro_model->other_code = $other_code;
        $pro_model->status = $status;
        addLog('添加产品,产品编号:'.$pro_code);
        if($pro_model->save() == true){
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
    public function list($current=1,$pageSize=10,$status=0,$pro_name='',$pro_code='',$other_code='',$excel=false,$pro_cates_id=''){
        $map = [];
        $uid = getUid();
        $user_model = new User();
        $user = $user_model ->where('id',$uid) ->find();
        
        $data = [
            'data' => [],
            'total' =>0,
            'success' => true
        ];
        if(!$user){return json($data);}
        if($user['user_group_id']!==1){
            $auth_ids  = $this->get_auth($uid);
            if(count($auth_ids)==0){
                return json($data);
            }else{
                $map[] = ['pro_cates_id','in',$auth_ids];
            }
            
        }
        if(!empty($status)){$map[] = ['status','=',$status];}
        if(!empty($pro_name)){$map[]=['pro_name','like','%'.$pro_name.'%'];}
        if(!empty($pro_code)){$map[]=['pro_code','like','%'.$pro_code.'%'];}
        if(!empty($other_code)){$map[]=['other_code','like','%'.$other_code.'%'];}
        if(!empty($pro_cates_id)){$map[] = ['pro_cates_id','=',$pro_cates_id];}
        if($excel){
            $data['data'] = $this->where($map)->order('id','desc')->select();
        }else{
            $data['data'] = $this->where($map)->page($current,$pageSize)->order('id','desc')->select();
        }
        $data['total'] =  $this->where($map)->count();
        $data['current'] = $current;
        $data['pageSize'] = $pageSize;
        $data['success'] = true;

        $pro_cates_model = new ProCates();
        foreach ($data['data'] as $key => $value) {
            $tmp_cates = $pro_cates_model-> where('id',$value['pro_cates_id'])->find();
            $data['data'][$key]['cates_name']= $tmp_cates?$tmp_cates['cates_name']:'注销分类';
        }
        return json($data);
    }

    /**
     * 修改信息
     *
     * @return void
     */
    public function updata($id,$pro_name,$pro_code,$pro_cates_id,$price=0,$weight=0,$size=0,$high_line=0,$low_line=0,$other_code='',$status=1,$upexist=1){
        if((empty($pro_name))||(empty($id))){
            return false;
        }
        $res = Product::find($id);
        if (empty($res)) {
            return false;
        }
        addLog('修改产品,产品名称:'.$pro_name);

        $res->pro_name = $pro_name;
        $res->pro_code = $pro_code;
        $res->pro_cates_id = $pro_cates_id;
        $res->price = $price;
        $res->weight = $weight;
        $res->size = $size;
        $res->high_line = $high_line;
        $res->low_line = $low_line;
        $res->other_code = $other_code;
        if($upexist!=2){
            if($status!==3){$res->status = $status;}
        }
        if($res->save() == true){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 删除产品
     * @param  array  $ids [description]
     * @return [boolen]      [description]
     */
    public function del($ids = []){
        addLog('删除产品,产品id:'.implode(',', $ids));
        return Product::destroy($ids);
    }

    /**
     * 返回有权限的列表
     *
     * @return array
     */
    public function get_auth($uid){
        $auth_position_ids = [];
        $pro_cates_model = new ProCates();
        $positions = $pro_cates_model->select()->toArray();
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