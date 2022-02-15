<?php
namespace app\model;
use think\Model;

/**
 * 库存冲减表格
 */
class MinusSto extends Model{
    // 设置字段信息
    protected $schema = [
        'id'          => 'int',
        'storage_in_id'    => 'int',
        'storage_out_id'    => 'int',
        'amount'        => 'string',
        'source'        => 'int',  //1出库，2负入库，3盘点
        'desc'        => 'string',
        'create_time' => 'int',
        'update_time' =>'int'
    ];


    /**
     * 添加库区
     * @param [type]  $pos_name [description]
     * @param [type]  $pos_code [description]
     * @param integer $area     [description]
     * @param integer $size     [容积]
     * @param string  $employs  [description]
     * @param integer $status   [description]
     */
    public function add($storage_in_id,$storage_out_id,$amount,$desc=null,$source=1){
        $this->storage_in_id = $storage_in_id;
        $this->storage_out_id = $storage_out_id;
        $this->source = $source;
        $this->amount = $amount;
        $this->desc = $desc;
        if($this->save() == true){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 冲减列表
     * @param  integer $current  [description]
     * @param  integer $pageSize [description]
     * @param  integer $status   [description]
     * @param  string  $pos_name [description]
     * @param  string  $pos_code [description]
     * @return [type]            [description]
     */
    public function list($current=1,$pageSize=10,$storage_in_id,$storage_out_id,$amount,$desc,$excel){
        $map = [];
        if(!empty($storage_in_id)){$map[] = ['storage_in_id','=',$storage_in_id];}
        if(!empty($storage_out_id)){$map[] = ['storage_out_id','=',$storage_out_id];}
        if(!empty($amount)){$map[] = ['amount','=',$amount];}
        if(!empty($desc)){$map[]=['desc','like','%'.$desc.'%'];}
        if($excel){
            $data['data'] = $this->where($map)->order('id','desc')->select();
        }else{
            $data['data'] = $this->where($map)->page($current,$pageSize)->order('id','desc')->select();
        }
        $data['total'] =  $this->where($map)->count();
        $data['current'] = $current;
        $data['pageSize'] = $pageSize;
        $data['success'] = true;

        return json($data);
    }

}