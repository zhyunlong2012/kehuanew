<?php
namespace app\model;
use think\Model;
use think\facade\Db;
use think\model\concern\SoftDelete;
/**
 * 库位表格
 */
class PositionArea extends Model{
    use SoftDelete;
    protected $deleteTime = 'delete_time';
    // 设置字段信息
    protected $schema = [
        'id'          => 'int',
        'position_id'    => 'int',
        'code'         => 'string',
        'size'    => 'int',
        'last_pro_code'        => 'string',
        'status'      => 'int',
        'packamount'      => 'int',
        'fac_code'        => 'string',
        'cus_code'        => 'string',
        'create_time' => 'int',
        'update_time' => 'int',
        'delete_time' => 'int',
    ];


    /**
     * 添加库位
     *
     * @param [type] $position_id
     * @param [type] $code
     * @param [type] $size
     * @param [type] $last_pro_code
     * @param integer $status
     * @param integer $max 库位最多包裹数
     * @return void
     */
    public function add($position_id,$code,$size,$status=1){
        $res = PositionArea::where('code', $code)->where('position_id',$position_id)->findOrEmpty();
        if (!$res->isEmpty()) {
            return false;
        }

        $pos_area_model = new PositionArea();
        $pos_area_model->position_id = $position_id;
        $pos_area_model->code = $code;
        $pos_area_model->size = $size;
        $pos_area_model->status = $status;
        $pos_area_model->packamount = 0;
        if($pos_area_model->save() == true){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 返回库位列表
     *
     * @param [type] $current
     * @param [type] $pageSize
     * @param [type] $position_id
     * @param [type] $code
     * @param [type] $last_pro_code
     * @param [type] $excel
     * @return void
     */
    public function list($current,$pageSize,$position_id,$code,$size,$last_pro_code,$status,$excel=false){
        
        
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
        if(!empty($position_id)){$map[] = ['position_id','=',$position_id];}
        if(!empty($code)){$map[]=['code','like','%'.$code.'%'];}
        if(!empty($size)){$map[]=['size','>=',$size];}
        if(!empty($last_pro_code))$map[]=['last_pro_code','like','%'.$last_pro_code.'%'];
        if($excel){
            $data['data'] = $this->where($map)->order('id','desc')->select();
        }else{
            $data['data'] = $this->where($map)->page($current,$pageSize)->order('id','desc')->select();
        }
        $data['total'] =  $this->where($map)->count();
        
        $data['current'] = $current;
        $data['pageSize'] = $pageSize;
        $data['success'] = true;

        $postion_model = new Position();
        foreach ($data['data'] as $key => $value) {
            $position = $postion_model-> where('id',$value['position_id'])->find();
            $data['data'][$key]['pos_name']= $position?$position['pos_name']:'注销库区';
            $data['data'][$key]['pos_code']= $position?$position['pos_code']:'注销库区';
        }
        
        return json($data);
    }

    /**
     * 扫码入库推荐 
     * 有产品编号的查询相同产品编号，按照同产品同厂家排序，然后是空位置排序
     * 没有产品编码的，按照空位置排序
     *
     * @param [type] $current
     * @param [type] $pageSize
     * @param [type] $position_id
     * @param [type] $last_pro_code
     * @param [type] $fac_code
     * @param [type] $cus_code
     * @param boolean $excel
     * @return void
     */
    public function inlist($current,$pageSize,$position_id,$last_pro_code,$fac_code,$cus_code,$excel=false){
        
        $map = [];
        $map[] = ['status','=',1];
        $map[] = ['position_id','=',$position_id];
        $map[] = ['size', 'exp', Db::raw('>packamount')];
        if(!empty($last_pro_code)){
            $mapFirst[] =['status','=',1];
            $mapFirst[] = ['position_id','=',$position_id];
            if(!empty($last_pro_code)){$mapFirst[] = ['last_pro_code','=',$last_pro_code];}
            if(!empty($fac_code)){$mapFirst[]=['fac_code','=',$fac_code];}
            if($cus_code)$mapFirst[]=['cus_code','=',$cus_code];

            $first_area = $this->where($mapFirst)->order('id','asc')->limit(1)->column('id');
            // dump($first_area);
            if(count($first_area)==1){
                $map[] = ['id','>=',$first_area[0]];
                $map2 =  $map1 = $map;
                $map[] = ['last_pro_code','=',$last_pro_code];
                $map[]=['fac_code','=',$fac_code];
                $map[]=['cus_code','=',$cus_code];
    
                $map1[] = ['last_pro_code','=',null];
                $map2[] = ['last_pro_code','=',''];
                $mapAll = [$map,$map1,$map2];
                $orderMap = [
                    'last_pro_code'=>'desc',
                    'packamount'=>'desc',
                    'id'=>'asc'
                ];
                if($excel){
                    $data['data'] = $this->whereOr($mapAll)->order($orderMap)->select();
                }else{
                    $data['data'] = $this->whereOr($mapAll)->page($current,$pageSize)->order($orderMap)->select();
                }
            }else{
                $map2 =  $map1 = $map;
                $map1[] = ['last_pro_code','=',null];
                $map2[] = ['last_pro_code','=',''];
                $mapAll = [$map1,$map2];
                $orderMap = [
                    // 'last_pro_code'=>'desc',
                    // 'packamount'=>'desc',
                    'id'=>'asc'
                ];
                if($excel){
                    $data['data'] = $this->whereOr($mapAll)->order($orderMap)->select();
                }else{
                    $data['data'] = $this->whereOr($mapAll)->page($current,$pageSize)->order($orderMap)->select();
                }
            }
            

        }else{
            $map2 =  $map1 = $map;
            $map1[] = ['last_pro_code','=',null];
            $map2[] = ['last_pro_code','=',''];
            $mapAll = [$map1,$map2];
            $orderMap = [
                'packamount'=>'desc',
                'id'=>'asc'
            ];
            if($excel){
                $data['data'] = $this->whereOr($mapAll)->order($orderMap)->select();
            }else{
                $data['data'] = $this->whereOr($mapAll)->page($current,$pageSize)->order($orderMap)->select();
            }
            
        }

        $data['total'] =  $this->whereOr($mapAll)->count();
        if($data['total']==0){
            $data['data'] = $this->where('position_id',$position_id)->limit(10)->select();
        }
        $data['current'] = $current;
        $data['pageSize'] = $pageSize;
        $data['success'] = true;

        $postion_model = new Position();
        foreach ($data['data'] as $key => $value) {
            $position = $postion_model-> where('id',$value['position_id'])->find();
            $data['data'][$key]['pos_name']= $position?$position['pos_name']:'注销库区';
            $data['data'][$key]['pos_code']= $position?$position['pos_code']:'注销库区';
        }
        
        return json($data);
    }

    /**
     * 扫码出库推荐
     *
     * @param [type] $current
     * @param [type] $pageSize
     * @param [type] $position_id
     * @param [type] $pro_code
     * @param [type] $fac_code
     * @param [type] $cus_code
     * @param boolean $excel
     * @return void
     */
    public function outlist($current,$pageSize,$position_id,$pro_code,$fac_code,$cus_code,$excel=false){
        $pos_area_model = new PositionArea();
        $area_ids = $pos_area_model->where('position_id',$position_id)->column('id');
        if(count($area_ids)==0){
            $data['total'] = 0;
            $data['current'] = $current;
            $data['pageSize'] = $pageSize;
            $data['data'] = [];
            $data['success'] = true;
            return json($data);
        }

        $scan_model = new Scan();
        $map =[];
        $map[] = ['position_area_id','in',$area_ids];
        $map[] = ['pro_code','=',$pro_code];
        if($fac_code){$map[] = ['fac_code','=',$fac_code];}
        if($cus_code){$map[] = ['cus_code','=',$cus_code];}
        $map[] = ['status','=',2];
        $orderMap = [
             'id'=>'asc'
        ];
        if($excel){
            $data['data'] = $scan_model->where($map)->order($orderMap)->select();
        }else{
            $data['data'] = $scan_model->where($map)->page($current,$pageSize)->order($orderMap)->select();
        }

        //库位
        foreach ($data['data'] as $key => $value) {
            $tmp = $pos_area_model-> where('id',$value['position_area_id'])->find();
            $data['data'][$key]['position_area_code']= $tmp?$tmp['code']:'注销库位';
        }

        $data['total'] =  $scan_model->where($map)->count();
        $data['current'] = $current;
        $data['pageSize'] = $pageSize;
        $data['success'] = true;
        
        return json($data);

    }

    

    /**
     * 修改信息
     *
     * @return void
     */
    public function updata($id,$position_id,$code,$size,$status){
        $res = PositionArea::find($id);
        if (empty($res)) {
            return 2;
        }

        $res1 = PositionArea::where('code', $code)->where('id','<>',$id)->findOrEmpty();
        if (!$res1->isEmpty()) {
            return 3;
        }
        
        $res->position_id = $position_id;
        $res->code = $code;
        $res->size = $size;
        if(!empty($status)){$res->status = $status;}
        
        if($res->save() == true){
            return 1;
        }else{
            return 4;
        }
    }

    /**
     * 删除库位
     * @param  array  $ids [description]
     * @return [boolen]      [description]
     */
    public function del($ids = []){
        addLog('删除库位,库区id:'.implode(',', $ids));
        return PositionArea::destroy($ids);
    }

    /**
     * 返回有权限的列表
     *
     * @return array
     */
    public function get_auth($uid){
        $auth_position_ids = [];
        $pro_cates_model = new Position();
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