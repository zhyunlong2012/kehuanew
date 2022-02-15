<?php
namespace app\model;
use think\Model;
use think\model\concern\SoftDelete;
/**
 * 销售产品表格
 */
class SalePro extends Model{
    use SoftDelete;
    protected $deleteTime = 'delete_time';
    // 设置字段信息
    protected $schema = [
        'id'          => 'int',
        'shop_id'   => 'int', //多用户
        'product_id'  => 'int',
        'title' => 'string', //展示页面产品标题
        'in_price' => 'string',  //市场价
        'market_price' => 'string',  //市场价
        'origin_price'  => 'string', //原价
        'price'     => 'string', //销售价
        'pic'  => 'string',  //略图
        'content'  => 'string',
        'is_sale'      => 'int',  //1上架 
        'is_cuxiao'    => 'int',  //促销
        'is_tuan'    => 'int',  //团购
        'create_time' => 'int',
        'update_time' => 'int',
        'delete_time' => 'int',
    ];


    /**
     * 添加产品
     */
    public function add($product_id,$shop_id,$title,$in_price,$market_price,$origin_price,$price,$pic,$content,$is_sale=1,$is_cuxiao=1,$is_tuan=1,$excel=false){
        $res = SalePro::where('product_id', $product_id)->where('shop_id',$shop_id)->findOrEmpty();
        if (!$res->isEmpty()) {
            if($excel){
                return $this->updata($res->id,$product_id,$shop_id,$title,$in_price,$market_price,$origin_price,$price,$pic,$content,$is_sale,$is_cuxiao,$is_tuan,$excel);
            }else{
                return false;
            }  
        }
        $sale_pro_model = new SalePro();
        $sale_pro_model ->product_id = $product_id;
        $sale_pro_model ->shop_id = $shop_id;
        $sale_pro_model ->title = $title;
        $sale_pro_model ->in_price = $in_price;
        $sale_pro_model ->market_price = $market_price;
        $sale_pro_model ->origin_price = $origin_price;
        $sale_pro_model ->price = $price;
        $sale_pro_model ->pic = $pic;
        $sale_pro_model ->content = $content;
        $sale_pro_model ->is_sale = $is_sale;
        $sale_pro_model ->is_cuxiao = $is_cuxiao;
        $sale_pro_model ->is_tuan = $is_tuan;
        addLog('添加销售产品,产品id:'.$product_id);
        if($sale_pro_model ->save() == true){
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
    public function list($current=1,$pageSize=10,$product_id,$shop_id,$title,$is_sale=1,$is_cuxiao=1,$is_tuan=1,$excel=false){
        $map = [];
        if(!empty($product_id)){$map[] = ['product_id','=',$product_id];}
        if(!empty($shop_id)){$map[] = ['shop_id','=',$shop_id];}
        if(!empty($title)){$map[]=['title','like','%'.$title.'%'];}
        if(!empty($is_sale)){$map[] = ['is_sale','=',$is_sale];}
        if(!empty($is_cuxiao)){$map[] = ['is_cuxiao','=',$is_cuxiao];}
        if(!empty($is_tuan)){$map[] = ['is_tuan','=',$is_tuan];}
        if($excel){
            $data['data'] = $this->where($map)->order('id','desc')->select();
        }else{
            $data['data'] = $this->where($map)->page($current,$pageSize)->order('id','desc')->select();
        }
        $data['total'] =  $this->where($map)->count();
        $data['current'] = $current;
        $data['pageSize'] = $pageSize;
        $data['success'] = true;

        $prodcut_model = new Product();
        $shop_model = new Shop();
        foreach ($data['data'] as $key => $value) {
            $tmp = $prodcut_model-> where('id',$value['product_id'])->find();
            $data['data'][$key]['pro_name']= $tmp?$tmp['pro_name']:'注销产品';
            $tmp_shop = $shop_model-> where('id',$value['shop_id'])->find();
            $data['data'][$key]['shop_name']= $tmp_shop?$tmp_shop['shop_name']:'注销店铺';
        }
        return json($data);
    }

    /**
     * 修改信息
     *
     * @return void
     */
    public function updata($id,$product_id,$shop_id,$title,$in_price,$market_price,$origin_price,$price,$pic,$content,$is_sale=1,$is_cuxiao=1,$is_tuan=1,$excel=false){
        $res = SalePro::find($id);
        if (empty($res)) {
            return false;
        }
        addLog('修改销售产品,产品id:'.$product_id);

        $res ->product_id = $product_id;
        $res ->shop_id = $shop_id;
        $res ->title = $title;
        $res ->in_price = $in_price;
        $res ->market_price = $market_price;
        $res ->origin_price = $origin_price;
        $res ->price = $price;
        if(!$excel){
            $res ->pic = $pic;
            $res ->content = $content;
        }
        $res ->is_sale = $is_sale;
        $res ->is_cuxiao = $is_cuxiao;
        $res ->is_tuan = $is_tuan;
        if($is_sale!==3){$res->is_sale = $is_sale;}
        if($is_cuxiao!==3){$res->is_cuxiao = $is_cuxiao;}
        if($is_tuan!==3){$res->is_tuan = $is_tuan;}
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
        addLog('删除销售产品,销售产品id:'.implode(',', $ids));
        return SalePro::destroy($ids);
    }

}