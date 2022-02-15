<?php
namespace app\controller;
use app\common\ApiMsg;
use app\middleware\Auth;
class SalePro
{
    protected $middleware = [
        Auth::class 	=> ['except' 	=> ['list','findPro'] ],
    ];
    use \app\common\ResponseMsg;

    /**
     * 添加产品
     */
    public function add(){
        $product_id = input('product_id');
        $shop_id = input('shop_id');
        $title = input('title');
        $in_price = input('in_price')?input('in_price'):0;
        $market_price  = input('market_price')?input('market_price'):0;
        $origin_price = input('origin_price')?input('origin_price'):0;
        $price = input('price');
        $pic = input('pic');
        $content = input('content');
        $is_sale=input('is_sale')?1:2;
        $is_cuxiao=input('is_cuxiao')?1:2;
        $is_tuan=input('is_tuan')?1:2;
        $excel = input('excel');
        //表格批量录入
        $pro_code = input('pro_code');
        $shop_code = input('shop_code');
        if($pro_code){
            $product_model = new \app\model\Product();
            $product = $product_model ->where('pro_code',$pro_code)->findOrEmpty();
            if($product->isEmpty()){
                return $this->JsonDataArr(ApiMsg::ERR_SEARCH_PRODUCT);
            }else{
                $product_id = $product['id'];
            }
        }

        if($shop_code){
            $shop_model = new \app\model\Shop();
            $shop = $shop_model ->where('shop_code',$shop_code)->findOrEmpty();
            if($shop->isEmpty()){
                return $this->JsonDataArr(ApiMsg::ERR_SHOP_CODE);
            }else{
                $uid = getUid();
                $uids = json_decode($shop['employs'],true);
                if(in_array($uid,$uids)){
                    $shop_id = $shop['id'];
                }else{
                    return $this->JsonDataArr(ApiMsg::ERR_SHOP_AUTH);
                }
            }
        }
        
        if(empty($product_id)||empty($shop_id)||empty($title)||empty($price)){
            return $this->JsonDataArr(ApiMsg::ERR_PARAMS_EMPTY);
        }

        $sale_pro_model = new \app\model\SalePro();
        $res = $sale_pro_model->add($product_id,$shop_id,$title,$in_price,$market_price,$origin_price,$price,$pic,$content,$is_sale,$is_cuxiao,$is_tuan,$excel);
        return $this->JsonCommon($res);
    }

    /**
     * 修改产品
     */
    public function updata(){
        $id = input('id');
        $product_id = input('product_id');
        $shop_id = input('shop_id');
        $title = input('title');
        $in_price = input('in_price')?input('in_price'):0;
        $market_price  = input('market_price')?input('market_price'):0;
        $origin_price = input('origin_price')?input('origin_price'):0;
        $price = input('price');
        $pic = input('pic');
        $content = input('content');
        $is_tuan=input('is_tuan')?2:1;
        $excel = input('excel')?true:false;
        if(input('is_sale')===true){
            $is_sale =1;
        }elseif(input('is_sale')===false){
            $is_sale =2;
        }else{
            $is_sale = 3;
        }

        if(input('is_cuxiao')===true){
            $is_cuxiao =1;
        }elseif(input('is_cuxiao')===false){
            $is_cuxiao =2;
        }else{
            $is_cuxiao = 3;
        }

        if(input('is_tuan')===true){
            $is_tuan =1;
        }elseif(input('is_tuan')===false){
            $is_tuan =2;
        }else{
            $is_tuan = 3;
        }

        if(empty($product_id)||empty($shop_id)||empty($title)||empty($price)){
            return $this->JsonDataArr(ApiMsg::ERR_PARAMS_EMPTY);
        }

        $sale_pro_model = new \app\model\SalePro();
        $res = $sale_pro_model->updata($id,$product_id,$shop_id,$title,$in_price,$market_price,$origin_price,$price,$pic,$content,$is_sale,$is_cuxiao,$is_tuan,$excel);
        return $this->JsonCommon($res);
    }

    /**
     * 查找产品
     * @return [type] [description]
     */
    public function findpro(){
        $id=input('id');
        if(empty($id)){
            return $this->JsonDataArr(ApiMsg::ERR_PARAMS_EMPTY);
        }
        $sale_pro_model = new \app\model\SalePro();
        $product = $sale_pro_model ->where('id',$id)->findOrEmpty();
        if($product->isEmpty()){
            return $this->JsonDataArr(ApiMsg::ERR_SEARCH_PRODUCT);
        }else{
            $prodcut_model = new \app\model\Product();
            $shop_model = new \app\model\Shop();
            $tmp = $prodcut_model-> where('id',$product['product_id'])->find();
            $product['pro_name']= $tmp?$tmp['pro_name']:'注销产品';
            $tmp_shop = $shop_model-> where('id',$product['shop_id'])->find();
            $product['shop_name']= $tmp_shop?$tmp_shop['shop_name']:'注销店铺';
            return $this->JsonSuccess($product);
        }
    }


    /**
     * 产品列表
     * @return [type] [description]
     */
    public function list(){
        $current=input('current')?input('current'):1;
        $pageSize=input('pageSize')?input('pageSize'):10;
        $product_id = input('product_id');
        $shop_id = input('shop_id');
        $title = input('title');
        $is_sale=input('is_sale')?input('is_sale'):0;
        $is_cuxiao=input('is_cuxiao')?input('is_cuxiao'):0;
        $is_tuan=input('is_tuan')?input('is_tuan'):0;
        $excel = input('excel')?input('excel'):false;
        $sale_pro_model = new \app\model\SalePro();
        return $sale_pro_model->list($current,$pageSize,$product_id,$shop_id,$title,$is_sale,$is_cuxiao,$is_tuan,$excel);
    }


    /**
     * 删除产品
     * @return [type] [description]
     */
    public function del(){
        $id = input('id');
        if($id==NULL){
            return $this->JsonDataArr(ApiMsg::ERR_PARAMS_EMPTY);
        }
        $sale_pro_model = new \app\model\SalePro();
        $res = $sale_pro_model ->del($id);
        return $this->JsonCommon($res);
    }

    /**
     * 上传文章缩略图
     *
     * @return void
     */
    public function upload(){
        $file = request()->file('file');
        // 上传到本地服务器
        $savename = \think\facade\Filesystem::disk('public')->putFile('salepro', $file);
        return $this->JsonResponse(ApiMsg::SUCCESS[0],ApiMsg::SUCCESS[1],$savename);
    }
    

    
    // /**
    //  * 查找产品
    //  * @return [type] [description]
    //  */
    // public function findpro(){
    //     $pro_code=input('pro_code');
    //     if(empty($pro_code)){
    //         return $this->JsonDataArr(ApiMsg::ERR_PARAMS_EMPTY);
    //     }
    //     $pro_model = new \app\model\Product();
    //     $product = $pro_model ->where('code',$pro_code)->findOrEmpty();
    //     if($product->isEmpty()){
    //         return $this->JsonDataArr(ApiMsg::ERR_SEARCH_PRODUCT);
    //     }else{
    //         return $this->JsonSuccess($product);
    //     }
    // }


}