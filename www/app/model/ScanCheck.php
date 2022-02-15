<?php
namespace app\model;
use think\Model;
use think\facade\Db;
/**
 * 盘点详情表格(库位包裹)
 */
class ScanCheck extends Model{
    // 设置字段信息
    protected $schema = [
        'id'                 => 'int',
        'user_id'            => 'int',
        'storage_check_id'   => 'int',
        'position_id'        => 'int', 
        'position_area_id'   => 'int',
        'code'               => 'string',
        'create_time'        => 'int',
        'update_time'        => 'int'
    ];


    /**
     * 添加盘点详情(扫码)
     *
     * @param [type] $position_id
     * @param [type] $position_area_id
     * @param [type] $code
     * @return void
     */
    public function add(
        $position_id,
        $position_area_id,
        $code){
        if(empty($position_id)||empty($position_area_id)||empty($code)){
            return 2;
        }

        $storage_check_model = new StorageCheck();
        $storage_check = $storage_check_model->where('position_id', $position_id)->where('status',1)->findOrEmpty();
        if ($storage_check->isEmpty()) {
                return 3;
        }

        $exist_map = [
            ['storage_check_id','=',$storage_check['id']],
            ['position_id','=',$position_id],
            // ['position_area_id','=',$position_area_id],
            ['code','=',$code],
        ];
        $exist_check = $this->where($exist_map)->find();
        if($exist_check){
            return 5;
        }
        
        $user_id = getUid();
        $scan_check_model = new ScanCheck();
        $scan_check_model->user_id = $user_id;
        $scan_check_model->storage_check_id = $storage_check['id'];
        $scan_check_model->position_id = $position_id;
        $scan_check_model->position_area_id = $position_area_id;
        $scan_check_model->code = $code;
       
        // $scan_check_detail_model = new ScanCheckDetail();
        Db::startTrans();
        try {
            // dump($code);dump($position_area_id);dump($position_id);
            $res1 = $scan_check_model->save();
            // $res2 = $scan_check_detail_model ->add($code,$position_area_id,$position_id,'in');
            // dump($res1);
            if($res1){
                // 提交事务
                Db::commit();
                return 1;
            }else{
                Db::rollback();
                return 4;
            }
            
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return 4;
        }


    }

    
     /**
     * 返回盘点列表
     *
     * @param string $storage_check_id
     * @return void
     */
    public function list($current,$pageSize,$storage_check_id,$position_id,$position_area_id,$code,$excel=false,$create_time=''){
        $map = [];
        if(!empty($storage_check_id)){$map[]=['storage_check_id','like',$storage_check_id];}
        if(!empty($position_id)){$map[]=['position_id','=',$position_id];}
        if(!empty($position_area_id)){$map[]=['position_area_id','=',$position_area_id];}
        if(!empty($code)){$map[]=['code','=',$code];}
        if(!empty($create_time)){
            $between_time = [strtotime($create_time[0]) ,strtotime($create_time[1])];
            $map[] = ['create_time','between',$between_time];
        }
        if($excel){
            $data['data'] = $this->where($map)->order('id','asc')->select();
        }else{
            $data['data'] = $this->where($map)->page($current,$pageSize)->order('id','asc')->select();
        }
        // $data['data'] = $this->where($map)->page($current,$pageSize)->order('id','desc')->select();
        
        $data['total'] =  $this->where($map)->count();
        $data['current'] = $current;
        $data['pageSize'] = $pageSize;
        $data['success'] = true;
        
        $position_model = new Position();
        $position_area_model = new PositionArea();
        foreach ($data['data'] as $key => $value) {
            $pos_tmp = $position_model-> where('id',$value['position_id'])->find();
            $data['data'][$key]['pos_name'] =$pos_tmp?$pos_tmp['pos_name']:'注销库区';
            $data['data'][$key]['pos_code'] =$pos_tmp?$pos_tmp['pos_code']:'注销库区';
            $area_tmp = $position_area_model-> where('id',$value['position_area_id'])->find();
            $data['data'][$key]['area_code'] =$area_tmp?$area_tmp['code']:'注销库位';
        }

        if($excel==true){
            $excel_data = [];
            foreach($data['data'] as $value){
                $excel_data[] = [
                    '库区名称' => $value['pos_name'],
                    '库区编号' => $value['pos_code'],
                    '库位编号' => $value['area_code'],
                    '包裹单号' => $value['code'],
                    '盘点时间'=>$value['create_time']
                ];
            }
            $data['data']=$excel_data;

        }

        return json($data);
    }

    /**
     * 删除
     * @param  array  $ids [description]
     * @return [boolen]      [description]
     */
    public function del($storage_check_id,$id=[]){
        // $detail = $this->where('id',$id)->findOrEmpty();
        // if(!$detail->isEmpty()) {
            $storage_check = StorageCheck::where('id',$storage_check_id)->findOrEmpty();
            if(!$storage_check->isEmpty()) {
                if($storage_check['status']!=1){
                    return 2;
                }else{
                    ScanCheck::destroy($id);
                    addLog('删除扫码盘点id:'.json_encode($id));
                    return 1;
                }
            }else{
                return 3;
            }
        // }else{
        //     return 4;
        // }
        
    }



}