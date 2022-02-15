<?php
namespace app\model;
use think\Model;
use think\model\concern\SoftDelete;
/**
 * 客户分类表格
 */
class CustomerCates extends Model{
    use SoftDelete;
    protected $deleteTime = 'delete_time';
    // 设置字段信息
    protected $schema = [
        'id'          => 'int',
        'pid'         => 'int',
        'cates_name'  => 'string',
        'cates_code'  => 'code',
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
    public function add($pid,$cates_name,$cates_code,$status=1){
        $res = CustomerCates::where('cates_code', $cates_code)->findOrEmpty();
        if (!$res->isEmpty()) {
            return $this->updata($res->id,$pid,$cates_name,$cates_code,$status);
        }

        $customer_cates_model = new CustomerCates();
        $customer_cates_model->pid = $pid;
        $customer_cates_model->cates_name = $cates_name;
        $customer_cates_model->cates_code = $cates_code;
        $customer_cates_model->status = $status;
        addLog('添加客户分类'.$cates_name);
        if($customer_cates_model->save() == true){
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
        if($excel){
            $cates = $this->where($map)->order('id','desc')->select()->toArray();
        }else{
            $cates = $this->where($map)->page($current,$pageSize)->order('id','desc')->select()->toArray();
        }
        //antd treeSelect dataform
        $tree_data = [];
        if($tree){
            foreach($cates as $value){
                $tree_data[] = [
                    'id' => $value['id'],
                    'value' => $value['id'],
                    'pid' => $value['pid'],
                    'title' => $value['cates_name'],
                ];
            }
        }else{
            $tree_data = $cates;
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
    public function updata($id,$pid,$cates_name,$cates_code,$status=1){
        if((empty($cates_name))||(empty($id))){
            return false;
        }
        $res = CustomerCates::find($id);
        if (empty($res)) {
            return false;
        }
        addLog('修改客户分类:'.$cates_name);
        $res->pid = $pid;
        $res->cates_name = $cates_name;
        $res->cates_code = $cates_code;
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
        return CustomerCates::destroy($ids);
    }


}