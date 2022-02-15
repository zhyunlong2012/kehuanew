<?php
namespace app\common;

class ApiMsg{
    /**
     * api 接口消息
     * code 000-100系统消息
     */

    const SUCCESS     = [1,'操作成功'];
    const ERR     = [2,'操作失败'];
    const ERR_UNKNOWN = [3,'未知错误'];
    const ERR_URL     = [4,'访问接口不存在'];
    const ERR_PARAMS  = [5,'参数错误'];
    const ERR_PARAMS_EMPTY  = [6,'缺少参数'];

     /**
      * 操作相关
      * code 101-200
      */

    const SUCCESS_BILL_CHECK = [30,'随车单已校验完成'];
    const ERR_INIT_POSITION_STO = [30,'初始化库存出错'];
    const ERR_ACCESS_TOP_ADMIN = [30,'唯一最高管理员才有权限进行此操作'];
    const ERR_PASSWORD  = [101,'密码错误'];
    const ERR_TIME  =     [102,'登录过期'];
    const ERR_EMPTY = [103,'密码或者用户名为空'];
    const ERR_UID = [104,'用户不存在'];
    const ERR_TOKEN = [105,'token鉴权错误'];
    const ERR_SEARCH_PRODUCT     = [106,'查询产品不存在'];
    const ERR_SEARCH_POSITION     = [107,'查询库区不存在'];
    const ERR_SEARCH_FACTORY     = [108,'查询供应商不存在'];
    const ERR_SEARCH_FACTORY_CATES     = [108,'查询供应商分类不存在'];
    const ERR_SEARCH_AREA     = [109,'库位不存在'];
    const ERR_SAVE     = [110,'数据存储出错'];
    const ERR_SCAN_CODE     = [111,'包裹二维码已存在'];
    const ERR_SCAN_PACKAGE     = [111,'包裹二维码不存在'];
    const ERR_SCAN_PACKAGE_IN     = [112,'包裹已入库定位，请不要重复入库'];
    const ERR_SCAN_PACKAGE_OUT     = [113,'包裹已出库或未入库，请不要重复出库'];
    const ERR_AREA_CODE     = [114,'库位代码已存在'];
    const ERR_OUT_STO     = [115,'库区或库位中产品不足，或保存出错'];
    const ERR_SCAN_PACKAGE_UNPACK    = [116,'在库区产品不准拆包,请在入库前或者出库后进行拆包'];
    const ERR_PACKAGE_OUT_AMOUNT    = [116,'出库数量大于包裹内数量'];
    const ERR_QRCODE    = [117,'码文已存在'];
    const ERR_BILL_NOTEXIST    = [118,'随车单不存在或已校验'];
    const ERR_BILL_STATE    = [119,'随车单已校验，不能录入或修改'];
    const ERR_CUSTOMER_NOTEXIST    = [120,'客户不存在'];
    const ERR_CUSTOMER_CATES_NOTEXIST    = [120,'客户分类不存在'];
    const ERR_PACKAGE_AMOUNT     = [121,'包裹产品数量超过随车单剩余需求数量'];
    const ERR_CHECK_BILL_PRO_AMOUNT     = [121,'出库产品数量超过随车单剩余需求数量'];
    const ERR_PACKAGE_BILL_STATE     = [122,'包裹不是在出库状态'];
    const ERR_PACKAGE_BILL_STATE_NOT_INCAR     = [122,'包裹未经过随车校验'];
    const ERR_SCAN_PRO     = [123,'已经扫码,请不要重复扫码'];
    const ERR_SCAN_PRO_BACK     = [124,'已经撤回,不要重复撤回'];
    const ERR_BILL_DETAIL_NOTEXIST     = [125,'随车单产品数据不存在'];
    const ERR_BILL_DETAIL_EXIST     = [126,'随车单该产品已存在，不要重复录入'];
    const ERR_BILL_DETAIL_AMOUNT     = [126,'随车单该产品数量错误'];
    const ERR_BILL_BACK_CHECKED     = [127,'随车单校验，不能撤回'];
    const ERR_PACKAGE_BILL     = [128,'包裹产品不在随车单内'];
    const ERR_PACKAGE_STATE_MOVE     = [128,'包裹不存在或不在库区内'];
    const ERR_PACKAGE_MOVE_OLD_AREA     = [128,'包裹所在库位不存在'];
    const ERR_PACKAGE_MOVE_NEW_AREA     = [128,'包裹要移动目标库位不存在'];
    const ERR_PRO_CATES_CODE     = [128,'产品分类代码错误'];
    const ERR_PACKAGE_OUT_ALREADY = [128,'包裹已出库，请不要重复出库'];

    const ERR_SHOP_CODE     = [129,'门店代码错误'];
    const ERR_SHOP_AUTH     = [130,'没有操作该门店权限'];
    const ERR_SHOP_CAR   = [131,'未查询到购物车'];
    const ERR_SHOP_CAR_STATE   = [132,'购物车状态错误'];
    const ERR_SHOP_CAR_NUMBER   = [133,'购物车订单号错误'];
    const ERR_SHOP  = [134,'门店错误'];

    const ERR_SALE_PRO  = [135,'销售产品错误'];
    const ERR_SHOP_CAR_DETAIL  = [136,'销售产品错误'];

    const ERR_USER   = [150,'用户不存在'];

    const ERR_STO_CHECK_EXIST   = [150,'盘点数据已存在'];
    const ERR_STO_CHECKING    = [150,'库区正常盘点中'];
    const ERR_STO_CHECK_FINISH   = [150,'库区盘点已完成'];
    const ERR_STO_CHECK_NOTEXIST   = [150,'库区盘点不存在'];
    const ERR_PACKAGE_PAN_EXIST   = [150,'包裹已经盘点过'];
    const ERR_PACKAGE_PAN_NOT_EXIST   = [150,'包裹未在盘点列表'];
    const ERR_AREA_AMOUNT   = [150,'该库位产品库存不足'];

}