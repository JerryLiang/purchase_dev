<?php
/**
 * 第三方系统对接 API地址
 * @author:Jolon
 * @since : 20190220
 */
// CG_ENV 在 config/conf.php中定义
if(1 or CG_ENV == 'dev'){// 开发环境
    /******************* 采购系统服务器配置 *************/
    $config['cg_system']['webfornt']['ip']                    = CG_SYSTEM_WEB_FRONT_IP;
    $config['cg_system']['appdal']['ip']                      = CG_SYSTEM_APP_DAL_IP;
    $config['cg_system']['webfornt']['print_billingcontract'] = CG_SYSTEM_WEB_FRONT_IP.'front/print_template/billingcontract.php';
    $config['cg_system']['webfornt']['print_compact']         = CG_SYSTEM_WEB_FRONT_IP.'front/print_template/contract.php';
    $config['cg_system']['webfornt']['print_menu']            = CG_SYSTEM_WEB_FRONT_IP.'front/print_template/printPurchaseOrder.php';
    $config['cg_system']['webfornt']['print_order']           = CG_SYSTEM_WEB_FRONT_IP.'front/print_template/printPurchaseOrderPDF.php';
    $config['cg_system']['webfornt']['print_payment_apply']   = CG_SYSTEM_WEB_FRONT_IP.'front/print_template/paymentApply.php';
    $config['cg_system']['webfornt']['tax_print_compact']     = CG_SYSTEM_WEB_FRONT_IP.'front/print_template/taxRefundTemplate.php';//合同模板(退税)
    $config['cg_system']['webfornt']['no_tax_print_compact']  = CG_SYSTEM_WEB_FRONT_IP.'front/print_template/nonRefundableTemplate.php';//合同模板(不退税)
    $config['cg_system']['webfornt']['print_statement']       = CG_SYSTEM_WEB_FRONT_IP.'front/print_template/StatementTemplate.php';//打印对账单
    $config['cg_system']['webfornt']['print_statement_new']   = CG_SYSTEM_WEB_FRONT_IP.'front/print_template/printStatementTemplate.php';//打印对账单
    $config['cg_system']['webfornt']['print_statement_esign'] = CG_SYSTEM_WEB_FRONT_IP.'front/print_template/printStatementTemplateEsign.php';//对账单模板（e签宝电子合同）
    $config['cg_system']['webfornt']['print_statement_excel'] = CG_SYSTEM_WEB_FRONT_IP.'front/print_template/printStatementTemplateExcel.php';//下载对账单EXCEL    $config['cg_system']['webfornt']['print_box']            = CG_SYSTEM_WEB_FRONT_IP.'front/print_template/printBoxDetail.php';
    $config['cg_system']['webfornt']['print_box']            = CG_SYSTEM_WEB_FRONT_IP.'front/print_template/printBoxDetail.php';
    $config['cg_system']['webfornt']['purchase_delivery_note']= CG_SYSTEM_WEB_FRONT_IP.'front/print_template/printDownloadPurchaseOrder.php'; // 打印送货单模板
    $config['cg_system']['webfornt']['visit_info']= CG_SYSTEM_WEB_FRONT_IP.'front/print_template/printDownloadVisitReport.php'; // 打印拜访报告模板



    $config['cg_system']['webfornt']['tax_print_compact_excel']     = CG_SYSTEM_WEB_FRONT_IP.'front/print_template/printCompactExcel.php';//合同模板(退税)
    $config['cg_system']['webfornt']['no_tax_print_compact_excel']  = CG_SYSTEM_WEB_FRONT_IP.'front/print_template/nonRefundableTemplateExcel.php';//合同模板(不退税)

    $config['cg_system']['webfornt']['print_billing_contract_excel'] = CG_SYSTEM_WEB_FRONT_IP.'front/print_template/billingContractExcel.php';//开票合同excel模板

    $config['cg_system']['webfornt']['download_statement_pdf'] = CG_SYSTEM_WEB_FRONT_IP.'api/statement/purchase_statement/download_statement_pdf';//下载对账单接口
    $config['cg_system']['webfornt']['upload_file'] = CG_SYSTEM_WEB_FRONT_IP.'api/finance/upload_receipt/upload_file';//上传单个文件接口

    /******************* OA 系统 ****************/
    $config['oa_system']['ip']                             = OA_SYSTEM_IP;
    $config['oa_system']['access_token']                   = OA_ACCESS_TOKEN_IP.'oauth/token?grant_type=client_credentials'; //OA获取access_token
    $config['oa_system']['getOaUserVo']                    = OA_SYSTEM_IP.'oaUser/getOaUserVo';// 通过部门名称获取人员列表
    $config['oa_system']['getProcurementUserList']         = OA_SYSTEM_IP.'oaUser/getProcurementUserList';// 采购系统用户列表
    $config['oa_system']['getUserListByUserIdAndUserName'] = OA_SYSTEM_IP.'oaUser/getUserListByUserIdAndUserName';// 获取指定用户信息
    $config['oa_system']['getUserListByUserNo']            = OA_SYSTEM_IP.'getUserListByUserNo';// 根据用户编号查询用户信息
    $config['oa_system']['getUserListByUserId']            = OA_SYSTEM_IP.'getUserListByUserId';// 根据用户id查询用户信息
    $config['oa_system']['getUserListByUserName']          = OA_SYSTEM_IP.'getUserListByUserName';// 根据用户名称查询用户信息
    $config['oa_system']['selectAllUserByDeptId']          = OA_SYSTEM_IP.'oaUser/selectAllUserByDeptId';// 根据部门id获取部门下所有人员(包括所有子级部门)
    $config['oa_system']['getUserByJobName']               = OA_SYSTEM_IP.'oaUser/getUserByJobName';// 通过岗位名称获取用户信息
    $config['oa_system']['getDirectlyDept']               = OA_SYSTEM_IP.'oaDepartment/getDirectlyDept';// 获取部门ID

    /******************* 产品系统 ******************/
    $config['product_system']['getProdCompanyInfoBySupplierCode']     = PRODUCT_SYSTEM_IP.'product/prodEyecheckCompanyInfo/getProdCompanyInfoBySupplierCode';// 根据供应商查询天眼查信息
    $config['product_system']['yibaiProdSupplier-selectSupplierInfoList'] = PRODUCT_SYSTEM_IP.'product/yibaiProdSupplier/selectSupplierInfoList';// 获取供应商列表
    $config['product_system']['yibaiProdSupplier-updateBystatus']     = PRODUCT_SYSTEM_IP.'product/yibaiProdSupplier/updateBystatus';// 根据供应商编码更新供应商审核状态
    // $config['product_system']['yibaiProdSupplier-updateSupplierInfo'] = PRODUCT_SYSTEM_IP.'product/yibaiProdSupplier/updateSupplierInfo';// 推送审核更新之后供应商信息到产品系统
    $config['product_system']['yibaiProdSupplier-updateSupplierInfo'] = PRODUCT_SYSTEM_IP.'product/yibaiProdSupplier/updateSupplierInfoToProcurement';// 推送审核更新之后供应商信息到产品系统
    $config['product_system']['yibaiProdSku-updateSkuPrice']          = PRODUCT_SYSTEM_IP.'product/yibaiProdSku/updateSkuPrice';// 推送最新采购价，平均运费成本， 加权平均价到产品系统
    $config['product_system']['prodSamplePurchaseOrder-updateStatus'] = PRODUCT_SYSTEM_IP.'product/prodSamplePurchaseOrder/updateStatus';// （采购单请款单付款成功）推送采购单号更新付款状态
    $config['product_system']['prodSamplePurchaseOrder-getByPayStatus'] = PRODUCT_SYSTEM_IP.'product/prodSamplePurchaseOrder/getByPayStatus';// （采购单请款单驳回）推送采购单号查询是否有财务驳回状态
    $config['product_system']['updateSupplierStatus']                   = PRODUCT_SYSTEM_IP.'product/yibaiProdSupplier/updateBystatus';// 更新供应商禁用状态
    $config['product_system']['sku_scree_to_product']                   = PRODUCT_SYSTEM_IP.'product/yibaiProdSkuShielding/pushSkuShield';// 推送SKU 屏蔽信息到产品系统
    $config['product_system']['sku_affirm_to_product']                  =    PRODUCT_SYSTEM_IP.'product/yibaiProdSkuShielding/rebutSkuShield'; // 推送采购确认信息到产品系统
    $config['product_system']['yibaiProdSupplier-getSupplierInfoByCode'] = PRODUCT_SYSTEM_IP.'product/yibaiProdSupplier/getSupplierInfoByCode';// 根据供应商code查询供应商信息
    $config['product_system']['yibaiProdSupplier-getSupplierInfoByCode'] = PRODUCT_SYSTEM_IP.'product/yibaiProdSupplier/getSupplierInfoByCode';// 根据供应商code查询供应商信息
    $config['product_system']['yibaiProdSamplePurchaseDetail-pushWriteOff'] = PRODUCT_SYSTEM_IP.'product/yibaiProdSamplePurchaseDetail/pushWriteOff';// （样品采购单）推送样品采购单收款后更新
    $config['product_system']['updateIsPurchasingBySku'] =  JAVA_API_URL.'/product/yibaiProdSku/updateIsPurchasingBySku'; //代采的历史数据需推送到新产品系统
    $config['product_system']['updatePurSupplierCode'] = JAVA_API_URL.'/product/yibaiProdSku/updatePurSupplierCode';// 推送SKU 供应商到产品系统
    $config['java_system_erp']['new_yibaiProduct-getSkuInfoBySku'] = JAVA_API_URL.'/product/yibaiProdSku/getSkuInfo';// SKU 详情信息接口

    $config['product_system']['getSkuInfoList'] = JAVA_API_URL.'/product/yibaiProdSku/getSkuInfoList';// 批量获取产品系统sku
    $config['product_system']['getProdSkuInfo'] = PRODUCT_SYSTEM_IP.'/product/yibaiProdSku/getProdSkuInfo';// 根据供应商code查询对应绑定sku


    /******************** 工作台 查询ENC变更信息 ************************/
    $config['product_system']['yibaiProdDataChangeApp'] = PRODUCT_SYSTEM_IP.'product/yibaiProdDataChangeApp/listDataChangeAppCount';//查询ENC变更信息

    /******************  JAVA接口ERP ****************/
    $config['java_system_erp']['yibaiProduct-getSkuInfoBySku'] = JAVA_SYSTEM_ERP.'yibaiProduct/getSkuInfoBySku';// 1.1.根据sku获取相关信息（采购系统端）
    $config['java_system_erp']['yibaiProduct-changskus'] = JAVA_SYSTEM_CHANG_SKU.'purchaseProduct/pushPriceToOldPurchse';//修改产品信息同步到老系统
    $config['java_system_erp']['pushPurSupplierToErp']                   = JAVA_SYSTEM_ERP.'providerErpController/pushPurSupplierToErp';// 更新供应商审核状态

    /************************ ERP 系统 ****************/
    $config['erp_system']['ip']                            = ERP_DOMAIN;// IP
    $config['erp_system']['product_scree']['push_to_erp']  = ERP_DOMAIN.'/services/products/product/screenapplyfromnewprovider';// SKU屏蔽列表-推送到ERP
    $config['erp_system']['product_scree']['get_supplier_name']  = ERP_DOMAIN.'/services/products/product/getsupplierinfo';// 获取默认供应商
    $config['erp_system']['product_scree']['push_audit_to_erp']  = ERP_DOMAIN.'/services/products/product/getprovidercheckinfo';// 推送SKU屏蔽替换供应商审核结果到ERP
    $config['erp_system']['product_scree']['push_avg_price_erp']  = ERP_DOMAIN.'/services/products/productstock/getloadlastprice';// 平均运费推送到ERP
    $config['erp_system']['product_scree']['get_product_line']   = ERP_DOMAIN.'/services/products/product/productlinelist';//获取ERP产品线数据
    $config['erp_system']['product_scree']['send_supplier_audit']   = ERP_DOMAIN.'/services/products/product/getprovidercheckinfofromnewprovider';//获取ERP产品线数据
    $config['erp_system']['product_scree']['id'] = 102529;   // 采购系统推送ERP SKU 屏蔽ERP
    $config['erp_system']['purchase_price_to_erp'] = JAVA_SYSTEM_ERP.'yibaiProduct/updatePurchaseCost';
    $config['erp_system']['purchase_price_to_old_data'] = JAVA_SYSTEM_ERP.'yibaiProduct/updatePurchaseCost'; // 老数据中心数据推送到ERP

    /************************  蓝凌接口 ***************/
    $config['lanling']['ip']                                = LAN_LING_IP;// 蓝凌接口api
    $config['lanling']['lanling_url']                       = LAN_LING_IP.'sys/webservice/kmReviewWebserviceService?wsdl';// 蓝凌接口api
    $config['lan_api']['ip']                                =LAN_WEB_IP; //蓝凌WEB 端IP
    $config['lan_api']['web_ip']                            =LAN_WEB_IP.'km/review/km_review_main/kmReviewMain.do?method=view&'; //蓝凌WEB URL
    /************************  数据中心 ***************/
    $config['service_data']['ip']                                = SERVICE_DATA_IP;// 数据中心api
    $config['service_data']['push_purchase_suggest']             = SERVICE_DATA_IP.'index.php/PurchasesNew/platformSummaryToMysql';// 需求单推送到数据中心
    $config['service_data']['push_purchase_order']               = SERVICE_DATA_IP.'index.php/PurchasesNew/insertPurchaseToMysql';// 采购单推送到数据中心
//    $config['service_data']['push_report_loss']                  = SERVICE_DATA_IP.'index.php/purchases/cancelPurchaseByOrderSku';// 报损推送到数据中心(作废)
    $config['service_data']['push_report_loss_up']               = SERVICE_DATA_IP.'PurchasesNew/reducePruchaseOrderOnWayByDamaged';// 报损推送到数据中心(启用)
    $config['service_data']['push_reject_report_loss']           = SERVICE_DATA_IP.'index.php/purchasesNew/rejectCancelPurchase';// 报损驳回数据推送到数据中心
    $config['service_data']['push_product_repackage']            = WAREHOUSE_IP.'/index.php/purchases/purchaseSkuMarkToMysql';// 二次包装列表数据到数据中心
    /******************* 推送仓库 ************************/
    $config['warehouse']['ip']                                   =WAREHOUSE_IP; //仓库api
    $config['warehouse']['warehouse_route']                      =WAREHOUSE_IP.'/index.php/Purchases/cancelPurchaseByOrderSku'; //推送仓库api
    $config['warehouse']['warehouse_reject']                     =WAREHOUSE_IP.'/index.php/purchasesNew/rejectCancelPurchase'; //驳回推送仓库api

    $config['logistics']['ip']                                   =LOGISTICS_IP; //推送到包裹加急IP
    $config['logistics']['pust_logistics']                       =LOGISTICS_IP.'/Api/Purchase/Purchase/setDeliveryPurchase'; //包裹加急推送到仓库

    /************************ WMS 系统 ****************/
    $config['wms_system']['push_handler_res_to_warehouse']        =WMS_DOMAIN.'/Api/Purchase/QualityAbnormal/getQualityAbnormalResult'; //推送异常处理结果至仓库系统
    $config['wms_system']['get_defective_data']                   =WMS_DOMAIN.'/Api/Purchase/QualityAbnormal/getDefectiveData'; //定时拉取仓库异常处理结果
    $config['wms_system']['relate_express']                       =WMS_DOMAIN.'/Api/Purchase/Purchase/relateExpress'; //推送物流信息到仓库
    $config['wms_system']['report_loss_success_list']             =WMS_DOMAIN.'/Api/Purchase/Purchase/purchaseSkuFrmLoss'; //推送报损成功单到仓库
    $config['wms_system']['push_report_list_to_lock']             =WMS_DOMAIN.'/Api/Purchase/Purchase/cancelPurchaseOrderSku'; //推送报损单到仓库锁仓
    $config['wms_system']['pushPurchaseStatus']                   =JAVA_API_URL.'/wms/uebPurchase/pushPurchaseStatus';// 推送采购状态到仓库
    $config['wms_system']['updateWmsSuggestOrderStatus']          =JAVA_API_URL.'/wms/uebPurchase/updateWmsSuggestOrderStatus';// 推送备货单采购状态到仓库
    $config['wms_system']['push_express_info_to_wms']             =JAVA_API_URL.'/wms/uebDeliveryPurchase/pur2WmsCargoCompanyName';// 推送快递单号+快递公司到仓库
    $config['wms_system']['push_express_info_to_wms_new']         = JAVA_API_URL.'/newwms/yibaiInstockExpressPurchase/receiveExpressInfo';// 推送物流信息到新wms
    $config['wms_system']['push_return_detail_to_wms']             =LOGISTICS_IP.'/procurement/httpCallController/createDemands';// 入库后退货-采购经理审核通过推送申请详情
    $config['wms_system']['supplier_return_express_info']             =LOGISTICS_IP.'/procurement/httpCallController/getSupplierReturnExpressInfo';// 入库后退货-采购经理审核通过推送申请详情
    $config['wms_system']['get_combine_demand_order']             =LOGISTICS_IP.'/procurement/httpCallController/combineDemandOrder';// 入库后退货-采购-退货需求单合单配库
    $config['wms_system']['get_delivery_data_by_orderId']             =LOGISTICS_IP.'/procurement/httpCallController/getDeliveryDataByOrderId';//采购-获取采购退货单出库信息
    $config['wms_system']['push_receive_bind_express']             =JAVA_API_URL.'/newwms/yibaiInstockExpressPurchase/receiveBindExpress';// 推送采购单快递单号到新仓库系统
    $config['wms_system']['purchase_processingResult']           = JAVA_API_URL.'/newwms/instock/yibaiInstockAbnormal/purchaseProcessingResult'; // 推送异常列表采购处理结果
    $config['wms_system']['receivePurchaseNumber'] = JAVA_API_URL.'/wms/uebPurchase/receivePurchaseNumber'; // 备货单在发运前重跑规则审核通过之后，sku的“工厂直发发运”和“中转仓发运”采购数量发生变更的情况下，再次推送新的数量到仓库；

    /*********************** 新 wms 入库后退货 ************************/
    $config['wms_system']['receiveDemand']                        = NEW_JAVA_WMS_API_URL.'/newwms/outorder/purchaseReturnDemand/receiveDemand';// 推送入库后退货信息到新wms
    $config['wms_system']['newWmsGetStock']                        = JAVA_API_URL.'/stockcenter/ybStock/main/assign';// 云仓配库接口


    /******************* 1688java接口 ************************/
    $config['alibaba']['ip']                                   = ALIBABA_ACCOUNT_IP; //1688java接口api
    $config['alibaba']['list_buyer_view']                      = ALIBABA_ACCOUNT_IP.'/order/buyerView/listbuyerView';// 查看供应商账期授信信息
    $config['alibaba']['get_order_detail']                     = ALIBABA_ACCOUNT_IP.'/order/orderDetail/getOrderDetail';// 通过交易ID获取账期详情
    $config['alibaba']['crossBorder-getPayUrl']                = ALIBABA_ACCOUNT_IP.'/order/crossBorder/getPayUrl';// 获取使用跨境宝支付的支付链接
    $config['alibaba']['purchasingSolution-getLogisticsInfo']  = ALIBABA_ACCOUNT_IP.'/order/purchasingSolution/getLogisticsInfo';// 获取交易订单的物流信息
    $config['alibaba']['purchasingSolution-listLogisticsInfo'] = ALIBABA_ACCOUNT_IP.'/order/purchasingSolution/listLogisticsInfo';// 批量获取交易订单的物流信息
    $config['alibaba']['purchasingSolution-listOrderDetail']   = ALIBABA_ACCOUNT_IP.'/order/purchasingSolution/listOrderDetail';// 获取交易订单详细信息
    $config['alibaba']['purchasingSolution-getOrderPrice']     = ALIBABA_ACCOUNT_IP.'/order/purchasingSolution/getOrderPrice';// 获取订单最新总额
    $config['alibaba']['order-createCrossOrder']               = ALIBABA_ACCOUNT_IP.'/order/order/createCrossOrder';// 创建订单接口
    $config['alibaba']['order-createOrderPreview']             = ALIBABA_ACCOUNT_IP.'/order/crossBorder/createOrderPreview';// 创建订单前预览数据
    $config['alibaba']['product-getSupplierByProductId']       = ALIBABA_ACCOUNT_IP.'/order/product/getSupplierByProductId';// 根据商品id查询供应商信息
    $config['alibaba']['crossBorder-commodityDistribution']    = ALIBABA_ACCOUNT_IP.'/order/crossBorder/commodityDistribution';// 将商品加入铺货列表
    $config['alibaba']['crossBorder-getProductInfo']           = ALIBABA_ACCOUNT_IP.'/order/crossBorder/getProductInfo';// 获取商品详情
    $config['alibaba']['tracde-getTradeByReceiveAddress']      = ALIBABA_ACCOUNT_IP.'/order/tracde/getTradeByReceiveAddress';// 买家获取保存的收货地址信息列表
    $config['alibaba']['purchasingSolution-getLogisticsTraceInfo'] = ALIBABA_ACCOUNT_IP.'/order/purchasingSolution/getLogisticsTraceInfo';// 获取交易订单的物流跟踪信息
    $config['alibaba']['purchasingSolution-listLogisticsTraceInfo'] = ALIBABA_ACCOUNT_IP.'/order/purchasingSolution/listLogisticsTraceInfo';// 获取交易订单的物流跟踪信息(批量請求)
    $config['alibaba']['auth-listAccount']                     = ALIBABA_ACCOUNT_IP.'/order/auth/listAccount';// 获取子账号列表
    $config['alibaba']['auth-authAdd']                         = ALIBABA_ACCOUNT_IP.'/order/auth/authAdd';// 批量添加子账号授权
    $config['alibaba']['get_supplier_shop_id']                 = ALIBABA_ACCOUNT_IP.'/order/crossBorder/getSupplierInfo';// 根据供应商链接获取店铺id
    $config['alibaba']['get_trade_cancel']                 = ALIBABA_ACCOUNT_IP.'/order/tracdeCancel/getTradeCancel';//  根据交易ID取消订单
    $config['alibaba']['get_query_order_refund']                 = ALIBABA_ACCOUNT_IP.'/order/crossBorder/queryOrderRefund';//  根据交易ID获取退款成功的详情信息
    $config['alibaba']['list_order_refund']                 = ALIBABA_ACCOUNT_IP.'/order/crossBorder/listOrderRefund';//  根据交易ID获取退款单详情信息
    $config['alibaba']['product_pdt_product_gen']              = ALIBABA_ACCOUNT_IP.'/order/crossBorder/productGen';//  跨境产品开发工具同款开发
    $config['alibaba']['get_logistics_status']              = ALIBABA_ACCOUNT_IP.'/order/purchasingSolution/listLogisticsStatus';//  根据1688订单号,拍单号获取物流状态
    $config['alibaba']['get_logistics_trace_info']              = ALIBABA_ACCOUNT_IP.'/order/purchasingSolution/getLogisticsTraceInfo';//  根据拍单号和物流单号,获取交易订单的物流跟踪信息
    $config['alibaba']['getOrderReasonReturn']              = ALIBABA_ACCOUNT_IP.'/order/order/getOrderReasonReturn';//  获取退款原因接口
    $config['alibaba']['getUploadAttachment']                  = ALIBABA_ACCOUNT_IP.'/order/order/getUploadAttachment';// 上传退款凭证接口
    $config['alibaba']['sendOrderRefund']                       = ALIBABA_ACCOUNT_IP.'/order/order/createRefundRequest';// 提交退款退货申请
    $config['alibaba']['getAliCashier']                         = ALIBABA_ACCOUNT_IP.'/order/order/getOrderPay';// 获取1688收银台

    /******************* 计划系统 ************************/
    $config['plan_system']['push_suggest_order']              = PLAN_SYSTEM_APP_DAL_IP.'/plan/purchase/track_check_update';//推送生成采购单的需求单
    $config['plan_system']['push_expiration_suggest']         = PLAN_SYSTEM_APP_DAL_IP.'/plan/purchase/purchase_state_update';//推送过期需求单
    $config['plan_system']['push_purchase_order']             = PLAN_SYSTEM_APP_DAL_IP.'/plan/purchase/track_state_update';//推送采购单变更状态
    $config['plan_system']['push_audit_suggest']              = PLAN_SYSTEM_APP_DAL_IP.'/plan/purchase/purchase_approve_state_update';//推送审核需求单
    $config['plan_system']['push_data_accesstoken']           = JAVA_API_URL.'/mrp/api/getToken'; // 调用计划系统API TOKEN
//    $config['plan_system']['push_data_url']                   =PLAN_TO_DATA.'/api/Purchase/receive_pms_expect_arrived_date?org_code=org_00001';// 推送数据到货时间到计划系统
//    $config['plan_system']['get_oversea_shipment_status']     = PLAN_TO_DATA.'/api/Oversea_shipment/get_oversea_shipment_status';


    /******************* 老采购系统 ************************/
//    $config['old_purchase']['supplier-get-change-supplier-list']   = OLD_PURCHASE.'/v1/supplier/get-change-supplier-list';// 获取老系统供应商状态
//    $config['old_purchase']['supplier-get-supplier-one']      = OLD_PURCHASE.'/v1/supplier/get-supplier-one';// 获取老系统供应商
//    $config['old_purchase']['supplier-get-supplier-status']   = OLD_PURCHASE.'/v1/supplier/get-supplier-status';// 获取老系统供应商状态
//    $config['old_purchase']['supplier-push-new-info']         = OLD_PURCHASE.'/v1/supplier/get-new-purchase-system-change-info';// 新系统更新供应商同步数据到老系统
//    $config['old_purchase']['supplier-push-new-buyer']        = OLD_PURCHASE.'/v1/supplier/get-new-purchase-system-change-buyer';// 新系统更新供应商同步数据到老系统-采购员
//    $config['old_purchase']['supplier-push-new-status']       = OLD_PURCHASE.'/v1/supplier/get-new-purchase-system-change-status';// 新系统更新供应商同步数据到老系统-状态
    $config['old_purchase']['supplier-get-avg_price']         = OLD_PURCHASE.'/v1/erp-sync/get-avg-price-by-sku';// 获取老系统平均运费相关数据
    $config['old_purchase']['sku-get-delivery_time']         = OLD_PURCHASE.'/v1/statistics/get-delivery-time';// 获取老系统权均交期
    $config['old_purchase']['sku-get-delivery_time']         = OLD_PURCHASE.'/v1/statistics/get-delivery-time';// 获取老系统权均交期
    $config['old_purchase']['sku-purchase-order']            =  GET_JAVA_DATA.'/procurementold/purPurchaseOrder/listHistoryOrder'; // 获取老采购系统的SKU 下单记录
    $config['old_purchase']['supplier-get-cooperation-amount'] = OLD_PURCHASE.'/v1/customer-service/get-instock-amount-by-date';// 获取老系统供应商近三个月入库金额
    $config['old_purchase']['tongtu']                        = GET_JAVA_DATA.'/procurementold/purPurchaseHistory/listHistory';// 获取通途系统数据
    $config['old_purchase']['update-product-supply-status']   = OLD_PURCHASE.'/v1/supplier/update-product-source-status';// 更新老系统产品货源状态
    /********************匹配仓库规则******************/
    $config['yibaiLogistics']['batch_get_warehouse_info']     = WAREHOUSE_INFO_IP.'/logistics/yibaiLogisticsTransitRule/batchGetWarehouseInfo';// 批量匹配中转仓规则
    $config['yibaiLogistics']['access_token']                 = WAREHOUSE_ACCESS_TOKEN_IP.'/oauth/token?grant_type=client_credentials'; //warehouse_code获取access_token

    /********************物流系统******************/
    $config['logistics_rule']['push_suggest']                 = LOGISTICS_RULE_IP.'/ordersys/api/OrderStockingPlan/add';// 推送物流系统匹配规则
    $config['logistics_rule']['java_warehouse_url']           = WAREHOUSE_URL.'/logistics/logisticsAttr/getWarehouseData'; // 物流系统获取仓库信息

    /****************财务系统********************/
    $config['declare_customs']['purchaseSysInsertData']      = FINANCE_IP.'/drawback/declare_customs/purchaseSysInsertData';// 推送财务系统
    $config['purchase_api']['purchaseSysInsertData']          = SETTLEMENT_IP.'/purchase/purchase_api/purchaseSysInsertData';// 推送结汇系统
    $config['java_summary']['sendSysInsertData']      = JAVA_API_URL.'/procurement/yibaiMethod/sendSysInsertData';// 推送开票信息至财务汇总系统
    $config['finance_system']['pushWareResults']      = FINANCE_IP.'/instock/purchase/insertData';// 采购入库单接口
    //

    $config['java_system_exchange']['purPushSupplierBank']          = JAVA_API_URL.'/erp/midBankAccountExc/purPushSupplierBank';// 推送采购供应商所绑定银行卡信息到结汇系统


    /****************计划系统java接口********************/
    $config['java_system_plan']['push_expiration_suggest']         = JAVA_SYSTEM_PLAN.'prNumber/updatePrByDemandStatus';//推送过期需求单
    $config['java_system_plan']['push_purchase_order']             = JAVA_SYSTEM_PLAN.'yibaiPurchaseTrack/updatePoStatus';//推送采购单变更状态
    $config['java_system_plan']['push_audit_suggest']              = JAVA_SYSTEM_PLAN.'prNumber/updatePrByAudit';//推送审核需求单
    $config['java_system_plan']['push_act_cancel_qty']              = JAVA_API_URL.'/busmrp/yibaiOverseaPurchaseTrack/refuseTrack';//回传实际取消数量给计划系统
    $config['java_system_plan']['push_cancel_qty']              = JAVA_API_URL.'/busmrp/yibaiOverseaPurchaseTrack/cancelTrack';//回传取消数量给计划系统

    /*********JAVA OA 获取公司人员信息************************/

    $config['java_system_oa']['get_company_all'] =  OA_URL.'/oaUser/listOaUser';
    $config['java_system_oa']['get_company_all_dep'] = OA_URL.'/oaDepartment/getOaDepartmentAll'; // 获取OA 部门信息
    $config['java_system_oa']['get_user_job']        = OA_URL.'/oaUser/getUserListByUserIdAndUserName'; // 获取用户职位信息
    $config['java_system_product_url']['java_url'] =  PRODUCT_OA.'/yibaiProduct/getProductBySku';

    $config['java_system_oa']['java_system_development_person'] = OA_URL.'/oa/oaUser/selectAllUserByDeptId'; // 获取开发部门人员信息

    /*******1688 子账号**********/

    $config['java_system_alibabasub']['url'] = PRODUCT_ALIBABASUB.'/yibaiProdAlibabaSub/insertOrUpdateAlibabaSub';
    $config['java_system_alibabasub']['del'] = PRODUCT_ALIBABASUB.'/yibaiProdAlibabaSub/deleteAlibabaSub';

    /******************* JAVA ES查询 ******************/
    $config['java_es_api']['get_stock'] = JAVA_API_URL.'/elatic/es/product/findProductStork';

    /********采购系统商品属性推送到ERP*******/
    $config['java_system_product_message']['supply_status_url'] = PRODUCT_ALIBABASUB.'/yibaiProdSku/updateResourceTypeBySku'; // 推送货源状态到ERP

    /*******服务总线**********/
    $config['java_system_service']['report_loss']              = GET_JAVA_DATA .'/procurement/purPurchaseOrder/reportLoss';//java服务总线报损
    $config['java_system_service']['report_loss_success_list'] = GET_JAVA_DATA .'/procurement/purPurchaseOrder/reportLossNotice';//java服务总线报损成功通知
    $config['java_system_service']['cancel']                   = GET_JAVA_DATA .'/procurement/purPurchaseOrder/cancel';//java服务总线取消未到货

    /*******物流轨迹******************/
    $config['logistics_locus']['express_info'] = GET_JAVA_DATA . '/logistics/yibaiOrderCnKuaidi/saveCnKuaidi';//推送快递单号和快递公司编码到Java
    $config['logistics_locus']['query_tracks'] = GET_JAVA_DATA . '/logistics/yibaiLogisticsTracks/selectLogisticsTracks';//手动刷新，获取物流轨迹数据
    /******新版SKU 屏蔽JAVA中心******/
    //
    $config['java_system_service']['supply_status'] =  GET_JAVA_DATA."/product/yibaiProdSku/updateProduct"; // 货源状态推送到JAVA数据中心
    $config['java_system_service']['get_selectbysku'] =  GET_JAVA_DATA."/product/prodSkuEditBasic/selectBySku"; // sku详情页，增加商品参数，需从新产品系统获取

    /********** 智库系统 **********/
    $config['think_tank_system']['owe_list'] =  BI_THINK_TANK_URL."/bi/domestic/warehouse/owe/list/"; //缺货列表

    /**************在途数量**************/
    $config['on_way_abnormal']['get_history'] = JAVA_API_URL . '/wms/uebPurchase/pullHistoryStorage';//获取仓库在途数量（拉取历史待入库数据）
    /**************快递鸟接口**************/
    $config['kd_bird']['get_order_traces'] = KD_BIRD_API_URL . '/Ebusiness/EbusinessOrderHandle.aspx';//快递鸟即时查询(增值版)接口
    $config['kd_bird']['traces_subscribe'] = KD_BIRD_API_URL . '/api/dist';//快递鸟订阅(增值版)接口
    /***************验货验厂******************/
    $config['check_product']['push_inspect_order'] = JAVA_API_URL . '/product/yibaiProdPo/pushInspectOrder';//采购确认后(或重验)推送验货数据到产品系统
    $config['check_product']['qualifies_apply'] = JAVA_API_URL . '/product/yibaiProdPo/qualifiesApply';//转合格申请数据到产品系统
    $config['check_product']['inspect_order_changed'] = JAVA_API_URL . '/product/yibaiProdPo/cancelLoss';//生成取消、报损数量推送数据到产品系统
    /***************发运管理推送JAVA************/
    $config['shipping_management']['procurement_audit'] = JAVA_API_URL.'/mrp/yibaiHttpShipment/receiveChangeShipmentTypeResult';//发运类型从中转仓变为直发,需要采购进行审核,审核结果推送到计划系统
    $config['shipping_management']['push_inspect_result'] = JAVA_API_URL . '/mrp/yibaiHttpShipment/receiveInspectionResult';    //推送发运跟踪表验货结果到计划系统

    /***************发票对接门户************/
    $config['invoice']['list'] = JAVA_API_URL."/provider/yibaiSupplierInvoiceList/pushInvoiceList"; // 待审核状态推送到门户
    $config['invoice']['reject'] = JAVA_API_URL."/provider/yibaiSupplierInvoiceList/pushAuditResult"; // 审核状态推送到门户系统

    $config['invoice']['adoptreject'] = JAVA_API_URL."/provider/yibaiSupplierInvoiceList/pushInvoiceCode"; // 审核状态推送到门户系统

    $config['invoice']['pushInvoiceImage']= JAVA_API_URL.'/provider/yibaiSupplierInvoiceList/pushInvoiceImage';
    $config['charge_against']['push_statement_items'] = JAVA_API_URL."/provider/yibaiSupplierStatement/pushStatementItem"; // 对账单明细推送到门户系统
    $config['charge_against']['push_statement_result'] = JAVA_API_URL."/provider/yibaiSupplierStatement/pushStatementResult"; // 对账单附件或者审核结果推送到门户系统
    $config['charge_against']['push_statement_pay_status'] = JAVA_API_URL."/provider/yibaiSupplierStatement/pushPayStatus"; // 对账单付款状态推送到门户系统
    $config['charge_against']['pushInvoiceItem'] = JAVA_API_URL.'/provider/yibaiSupplierInvoiceList/pushInvoiceItem';//采购系统推送填写发票信息

    $config['product_system_image']['get_image'] = PRODUCT_SYSTEM_IMAGE.'/api/image_api/get_image';//获取图片接口
    $config['charge_against']['pushCompactUrl'] = JAVA_API_URL.'/provider/yibaiSupplierInvoiceList/pushCompactUrl';// 发票清单推送合同地址到门户系统
    $config['purchase']['shipmentsqty'] = JAVA_API_URL.'/provider/purPush/getShipmentsQty'; // 采购单获取门户系统回货数
    $config['purchase']['pushActualStatus'] = JAVA_API_URL.'/provider/purPush/pushActualStatus'; // 推送订单实际状态全部到货部分到货不等待剩余和订单完结时间 到门户系统

    $config['CoPurRejectOrder_api']['CoPurRejectOrder']             = COPURREJECTORDER.'/api/Reject_purchase_order/CoPurRejectOrder'; //采购单作废功能

    $config['charge_against']['officialAccountNotifica'] = JAVA_API_URL."/provider/purPush/officialAccountNotification"; // 催发货催改价
    $config['charge_against']['selectSupplierList'] = JAVA_API_URL."/provider/purPush/getSupplierList"; // 供应商后台获取供应商列表

    //java多货调拨接口
    $config['java_system_service']['transfer'] = GET_JAVA_DATA .'/procurement/purMultipleGoods/transfer';//java调拨接口


}elseif(CG_ENV == 'prod'){  //生产环境
    /******************* 采购系统服务器配置 *************/
    $config['cg_system']['webfornt']['ip']                    = CG_SYSTEM_WEB_FRONT_IP;
    $config['cg_system']['appdal']['ip']                      = CG_SYSTEM_APP_DAL_IP;
    $config['cg_system']['webfornt']['print_billingcontract'] = CG_SYSTEM_WEB_FRONT_IP.'front/print_template/billingcontract.php';
    $config['cg_system']['webfornt']['print_compact']         = CG_SYSTEM_WEB_FRONT_IP.'front/print_template/contract.php';
    $config['cg_system']['webfornt']['print_menu']            = CG_SYSTEM_WEB_FRONT_IP.'front/print_template/printPurchaseOrder.php';
    $config['cg_system']['webfornt']['print_payment_apply']   = CG_SYSTEM_WEB_FRONT_IP.'front/print_template/paymentApply.php';
    $config['cg_system']['webfornt']['tax_print_compact']     = CG_SYSTEM_WEB_FRONT_IP.'front/print_template/taxRefundTemplate.php';//合同模板(退税)
    $config['cg_system']['webfornt']['no_tax_print_compact']  = CG_SYSTEM_WEB_FRONT_IP.'front/print_template/nonRefundableTemplate.php';//合同模板(不退税)
    $config['cg_system']['webfornt']['print_box']            = CG_SYSTEM_WEB_FRONT_IP.'front/print_template/printBoxDetail.php';


    /******************* OA 系统 ****************/
    $config['oa_system']['ip']                             = OA_SYSTEM_IP;
    $config['oa_system']['getOaUserVo']                    = OA_SYSTEM_IP.'oa/oaUser/getOaUserVo';
    $config['oa_system']['getProcurementUserList']         = OA_SYSTEM_IP.'oa/oaUser/getProcurementUserList';
    $config['oa_system']['getUserListByUserIdAndUserName'] = OA_SYSTEM_IP.'oa/oaUser/getUserListByUserIdAndUserName';
    $config['oa_system']['getUserListByUserNo']            = OA_SYSTEM_IP.'oa/getUserListByUserNo';// 根据用户编号查询用户信息
    $config['oa_system']['getUserListByUserId']            = OA_SYSTEM_IP.'oa/getUserListByUserId';// 根据用户id查询用户信息
    $config['oa_system']['getUserListByUserName']          = OA_SYSTEM_IP.'oa/getUserListByUserName';// 根据用户名称查询用户信息
    $config['oa_system']['selectAllUserByDeptId']          = OA_SYSTEM_IP.'oa/oaUser/selectAllUserByDeptId';// 根据部门id获取部门下所有人员(包括所有子级部门)
    $config['oa_system']['getDirectlyDept']               = OA_SYSTEM_IP.'oaDepartment/getDirectlyDept';// 获取部门ID


    /******************* 产品系统 ******************/
    $config['product_system']['getProdCompanyInfoBySupplierCode']     = PRODUCT_SYSTEM_IP.'product/prodEyecheckCompanyInfo/getProdCompanyInfoBySupplierCode';// 根据供应商查询天眼查信息
    $config['product_system']['yibaiProdSupplier-selectSupplierInfoList'] = PRODUCT_SYSTEM_IP.'product/yibaiProdSupplier/selectSupplierInfoList';// 获取供应商列表
    $config['product_system']['yibaiProdSupplier-updateBystatus']     = PRODUCT_SYSTEM_IP.'product/yibaiProdSupplier/updateBystatus';// 根据供应商编码更新供应商审核状态
    $config['product_system']['yibaiProdSupplier-updateSupplierInfo'] = PRODUCT_SYSTEM_IP.'product/yibaiProdSupplier/updateSupplierInfo';// 推送审核更新之后供应商信息到产品系统
    $config['product_system']['yibaiProdSku-updateSkuPrice']          = PRODUCT_SYSTEM_IP.'product/yibaiProdSku/updateSkuPrice';// 推送最新采购价，平均运费成本， 加权平均价到产品系统
    $config['product_system']['prodSamplePurchaseOrder-updateStatus'] = PRODUCT_SYSTEM_IP.'product/prodSamplePurchaseOrder/updateStatus';// （采购单请款单付款成功）推送采购单号更新付款状态
    $config['product_system']['prodSamplePurchaseOrder-getByPayStatus'] = PRODUCT_SYSTEM_IP.'product/prodSamplePurchaseOrder/getByPayStatus';// （采购单请款单驳回）推送采购单号查询是否有财务驳回状态
    $config['product_system']['prodSamplePurchaseOrder-updateRemarkByPo'] = PRODUCT_SYSTEM_IP.'product/prodSamplePurchaseOrder/updateRemarkByPo';// （样品采购单）根据po推送备注信息
    $config['product_system']['getProdSkuInfo'] = PRODUCT_SYSTEM_IP.'/product/yibaiProdSku/getProdSkuInfo';// 根据供应商code查询对应绑定sku





    /************************ ERP 系统 ****************/
    $config['erp_system']['ip']                            = ERP_DOMAIN;// IP
    $config['erp_system']['product_scree']['push_to_erp']  = ERP_DOMAIN.'/services/products/product/screenapplyfromnewprovider';// SKU屏蔽列表-推送到ERP
    $config['erp_system']['product_scree']['get_supplier_name']  = ERP_DOMAIN.'/services/products/product/getsupplierinfo';// 获取默认供应商
    $config['erp_system']['product_scree']['push_audit_to_erp']  = ERP_DOMAIN.'/services/products/product/getprovidercheckinfo';// 推送SKU屏蔽替换供应商审核结果到ERP
    $config['erp_system']['product_scree']['push_avg_price_erp']  = ERP_DOMAIN.'/services/products/productstock/getloadlastprice';// 平均运费推送到ERP
    $config['erp_system']['product_scree']['get_product_line']   = ERP_DOMAIN.'/services/products/product/productlinelist';//获取ERP产品线数据
    $config['erp_system']['product_scree']['send_supplier_audit']   = ERP_DOMAIN.'/services/products/product/getprovidercheckinfofromnewprovider';//获取ERP产品线数据
    $config['erp_system']['product_scree']['id'] = 102529;   // 采购系统推送ERP SKU 屏蔽ERP

    /************************  蓝凌接口 ***************/
    $config['lanling']['ip']                                = LAN_LING_IP;// 蓝凌接口api
    $config['lanling']['lanling_url']                       = LAN_LING_IP.'sys/webservice/kmReviewWebserviceService?wsdl';// 蓝凌接口api


    /************************  数据中心 ***************/
    $config['service_data']['ip']                           = SERVICE_DATA_IP;// 数据中心api
    $config['service_data']['push_purchase_suggest']        = SERVICE_DATA_IP.'index.php/purchasesNew/platformSummaryToMysql';// 采购数据推送到仓库系统
    $config['service_data']['push_purchase_void_order']     = SERVICE_DATA_IP.'index.php/purchasesNew/cancelPurchaseByOrderSku';// 推送作废订单到数据中心


    /******************* 推送仓库 ************************/
    $config['warehouse']['ip']                                   =WAREHOUSE_IP; //仓库api
    $config['warehouse']['warehouse_route']                      =WAREHOUSE_IP.'/index.php/Purchases/cancelPurchaseByOrderSku'; //推送仓库api
    $config['warehouse']['pust_logistics']                      =WAREHOUSE_IP.'/Api/Purchase/Purchase/setDeliveryPurchase'; //包裹加急推送到仓库

    //OA
    $config['oa_system']['access_token']                      = OA_ACCESS_TOKEN_IP.'oauth/token?grant_type=client_credentials'; //OA获取access_token
    //plan
    $config['plan_system']['push_suggest_order']              = PLAN_SYSTEM_APP_DAL_IP.'/plan/purchase/track_check_update';
    $config['plan_system']['push_expiration_suggest']         = PLAN_SYSTEM_APP_DAL_IP.'/plan/purchase/purchase_state_update';
    $config['plan_system']['push_audit_suggest']              = PLAN_SYSTEM_APP_DAL_IP.'/plan/purchase/purchase_approve_state_update';//推送审核需求单


    /************************ WMS 系统 ****************/
    $config['wms_system']['push_handler_res_to_warehouse']        =WMS_DOMAIN.'/Api/Purchase/QualityAbnormal/getQualityAbnormalResult'; //推送异常处理结果至仓库系统
    $config['wms_system']['get_defective_data']                   =WMS_DOMAIN.'/Api/Purchase/QualityAbnormal/getDefectiveData'; //定时拉取仓库异常处理结果
    $config['wms_system']['pushPurchaseStatus']                   =JAVA_API_URL.'/wms/uebPurchase/pushPurchaseStatus';// 推送采购状态到仓库
    $config['wms_system']['supplier_return_express_info']             =JAVA_API_URL.'/procurement/httpCallController/getSupplierReturnExpressInfo';// 入库后退货-采购经理审核通过推送申请详情


    /******************* 1688java接口 ************************/
    $config['alibaba']['ip']                                   = ALIBABA_ACCOUNT_IP; //1688java接口api
    $config['alibaba']['list_buyer_view']                      = ALIBABA_ACCOUNT_IP.'/order/buyerView/listBuyerView';// 查看供应商账期授信信息
    $config['alibaba']['get_order_detail']                     = ALIBABA_ACCOUNT_IP.'/order/orderDetail/getOrderDetail';// 通过交易ID获取账期详>情
    $config['alibaba']['crossBorder-getPayUrl']                = ALIBABA_ACCOUNT_IP.'/order/crossBorder/getPayUrl';// 获取使用跨境宝支付的支付链
    $config['alibaba']['purchasingSolution-getLogisticsInfo']  = ALIBABA_ACCOUNT_IP.'/order/purchasingSolution/getLogisticsInfo';// 获取交易订单
    $config['alibaba']['purchasingSolution-listOrderDetail']   = ALIBABA_ACCOUNT_IP.'/order/purchasingSolution/listOrderDetail';// 获取交易订单>详细信息
    $config['alibaba']['purchasingSolution-getOrderPrice']     = ALIBABA_ACCOUNT_IP.'/order/purchasingSolution/getOrderPrice';// 获取订单最新总>额
    $config['alibaba']['order-createCrossOrder']               = ALIBABA_ACCOUNT_IP.'/order/order/createCrossOrder';// 创建订单接口
    $config['alibaba']['order-createOrderPreview']             = ALIBABA_ACCOUNT_IP.'/order/crossBorder/createOrderPreview';// 创建订单前预览数>据
    $config['alibaba']['product-getSupplierByProductId']       = ALIBABA_ACCOUNT_IP.'/order/product/getSupplierByProductId';// 根据商品id查询供>应商信息
    $config['alibaba']['crossBorder-commodityDistribution']    = ALIBABA_ACCOUNT_IP.'/order/crossBorder/commodityDistribution';// 将商品加入铺货
    $config['alibaba']['crossBorder-getProductInfo']           = ALIBABA_ACCOUNT_IP.'/order/crossBorder/getProductInfo';// 获取商品详情
    $config['alibaba']['tracde-getTradeByReceiveAddress']      = ALIBABA_ACCOUNT_IP.'/order/tracde/getTradeByReceiveAddress';// 买家获取保>存的收货地址信息列表

    /******************* 老采购系统 ************************/
    $config['old_purchase']['supplier-get-supplier-one']      = OLD_PURCHASE.'/v1/supplier/get-supplier-one';// 获取老系统供应商
    $config['old_purchase']['supplier-get-supplier-status']   = OLD_PURCHASE.'/v1/supplier/get-supplier-status';// 获取老系统供应商状态
    /********************匹配仓库规则******************/
    $config['yibaiLogistics']['batch_get_warehouse_info']     = WAREHOUSE_INFO_IP.'/logistics/yibaiLogisticsTransitRule/batchGetWarehouseInfo';// 批量匹配中转仓规则
    $config['yibaiLogistics']['access_token']                 = WAREHOUSE_ACCESS_TOKEN_IP.'/oauth/token?grant_type=client_credentials'; //warehouse_code获取access_token
    /********************物流系统******************/
    $config['logistics_rule']['push_suggest']                 = LOGISTICS_RULE_IP.'/api/OrderStockingPlan/addOne';// 推送物流系统匹配规则

}else{  //测试环境
    /******************* 采购系统服务器配置 *************/
    $config['cg_system']['webfornt']['ip']                    = CG_SYSTEM_WEB_FRONT_IP;
    $config['cg_system']['appdal']['ip']                      = CG_SYSTEM_APP_DAL_IP;
    $config['cg_system']['webfornt']['print_billingcontract'] = CG_SYSTEM_WEB_FRONT_IP.'front/print_template/billingcontract.php';
    $config['cg_system']['webfornt']['print_compact']         = CG_SYSTEM_WEB_FRONT_IP.'front/print_template/contract.php';
    $config['cg_system']['webfornt']['print_menu']            = CG_SYSTEM_WEB_FRONT_IP.'front/print_template/printPurchaseOrder.php';
    $config['cg_system']['webfornt']['print_payment_apply']   = CG_SYSTEM_WEB_FRONT_IP.'front/print_template/paymentApply.php';
    $config['cg_system']['webfornt']['tax_print_compact']     = CG_SYSTEM_WEB_FRONT_IP.'front/print_template/taxRefundTemplate.php';//合同模板(退税)
    $config['cg_system']['webfornt']['no_tax_print_compact']  = CG_SYSTEM_WEB_FRONT_IP.'front/print_template/nonRefundableTemplate.php';//合同模板(不退税)
    $config['cg_system']['webfornt']['print_statement']       = CG_SYSTEM_WEB_FRONT_IP.'front/print_template/StatementTemplate.php';//打印对账单

    $config['cg_system']['webfornt']['tax_print_compact_excel']     = CG_SYSTEM_WEB_FRONT_IP.'front/print_template/printCompactExcel.php';//合同模板(退税)
    $config['cg_system']['webfornt']['no_tax_print_compact_excel']  = CG_SYSTEM_WEB_FRONT_IP.'front/print_template/nonRefundableTemplateExcel.php';//合同模板(不退税)
    $config['cg_system']['webfornt']['print_box']            = CG_SYSTEM_WEB_FRONT_IP.'front/print_template/printBoxDetail.php';

    /******************* OA 系统 ****************/
    $config['oa_system']['ip']                             = OA_SYSTEM_IP;
    $config['oa_system']['access_token']                   = OA_ACCESS_TOKEN_IP.'oauth/token?grant_type=client_credentials'; //OA获取access_token
    $config['oa_system']['getOaUserVo']                    = OA_SYSTEM_IP.'oaUser/getOaUserVo';// 通过部门名称获取人员列表
    $config['oa_system']['getProcurementUserList']         = OA_SYSTEM_IP.'oaUser/getProcurementUserList';// 采购系统用户列表
    $config['oa_system']['getUserListByUserIdAndUserName'] = OA_SYSTEM_IP.'oaUser/getUserListByUserIdAndUserName';// 获取指定用户信息
    $config['oa_system']['getUserListByUserNo']            = OA_SYSTEM_IP.'getUserListByUserNo';// 根据用户编号查询用户信息
    $config['oa_system']['getUserListByUserId']            = OA_SYSTEM_IP.'getUserListByUserId';// 根据用户id查询用户信息
    $config['oa_system']['getUserListByUserName']          = OA_SYSTEM_IP.'getUserListByUserName';// 根据用户名称查询用户信息
    $config['oa_system']['selectAllUserByDeptId']          = OA_SYSTEM_IP.'oaUser/selectAllUserByDeptId';// 根据部门id获取部门下所有人员(包括所有子级部门)
    $config['oa_system']['getUserByJobName']               = OA_SYSTEM_IP.'oaUser/getUserByJobName';// 通过岗位名称获取用户信息
    $config['oa_system']['getDirectlyDept']               = OA_SYSTEM_IP.'oaDepartment/getDirectlyDept';// 获取部门ID

    /******************* 产品系统 ******************/
    $config['product_system']['getProdCompanyInfoBySupplierCode']     = PRODUCT_SYSTEM_IP.'product/prodEyecheckCompanyInfo/getProdCompanyInfoBySupplierCode';// 根据供应商查询天眼查信息
    $config['product_system']['yibaiProdSupplier-selectSupplierInfoList'] = PRODUCT_SYSTEM_IP.'product/yibaiProdSupplier/selectSupplierInfoList';// 获取供应商列表
    $config['product_system']['yibaiProdSupplier-updateBystatus']     = PRODUCT_SYSTEM_IP.'product/yibaiProdSupplier/updateBystatus';// 根据供应商编码更新供应商审核状态
    // $config['product_system']['yibaiProdSupplier-updateSupplierInfo'] = PRODUCT_SYSTEM_IP.'product/yibaiProdSupplier/updateSupplierInfo';// 推送审核更新之后供应商信息到产品系统
    $config['product_system']['yibaiProdSupplier-updateSupplierInfo'] = PRODUCT_SYSTEM_IP.'product/yibaiProdSupplier/updateSupplierInfoToProcurement';// 推送审核更新之后供应商信息到产品系统
    $config['product_system']['yibaiProdSku-updateSkuPrice']          = PRODUCT_SYSTEM_IP.'product/yibaiProdSku/updateSkuPrice';// 推送最新采购价，平均运费成本， 加权平均价到产品系统
    $config['product_system']['prodSamplePurchaseOrder-updateStatus'] = PRODUCT_SYSTEM_IP.'product/prodSamplePurchaseOrder/updateStatus';// （采购单请款单付款成功）推送采购单号更新付款状态
    $config['product_system']['prodSamplePurchaseOrder-getByPayStatus'] = PRODUCT_SYSTEM_IP.'product/prodSamplePurchaseOrder/getByPayStatus';// （采购单请款单驳回）推送采购单号查询是否有财务驳回状态
    $config['product_system']['updateSupplierStatus']                   = PRODUCT_SYSTEM_IP.'product/yibaiProdSupplier/updateBystatus';// 更新供应商禁用状态
    $config['product_system']['sku_scree_to_product']                   = PRODUCT_SYSTEM_IP.'product/yibaiProdSkuShielding/pushSkuShield';// 推送SKU 屏蔽信息到产品系统
    $config['product_system']['sku_affirm_to_product']                  =    PRODUCT_SYSTEM_IP.'product/yibaiProdSkuShielding/rebutSkuShield'; // 推送采购确认信息到产品系统
    $config['product_system']['yibaiProdSupplier-getSupplierInfoByCode'] = PRODUCT_SYSTEM_IP.'product/yibaiProdSupplier/getSupplierInfoByCode';// 根据供应商code查询供应商信息
    $config['product_system']['getProdSkuInfo'] = PRODUCT_SYSTEM_IP.'/product/yibaiProdSku/getProdSkuInfo';// 根据供应商code查询对应绑定sku


    /******************  JAVA接口ERP ****************/
    $config['java_system_erp']['yibaiProduct-getSkuInfoBySku'] = JAVA_SYSTEM_ERP.'yibaiProduct/getSkuInfoBySku';// 1.1.根据sku获取相关信息（采购系统端）
    $config['java_system_erp']['yibaiProduct-changskus'] = JAVA_SYSTEM_CHANG_SKU.'purchaseProduct/pushPriceToOldPurchse';//修改产品信息同步到老系统
    $config['java_system_erp']['pushPurSupplierToErp']                   = JAVA_SYSTEM_ERP.'providerErpController/pushPurSupplierToErp';// 更新供应商审核状态


    /************************ ERP 系统 ****************/
    $config['erp_system']['ip']                            = ERP_DOMAIN;// IP
    $config['erp_system']['product_scree']['push_to_erp']  = ERP_DOMAIN.'/services/products/product/screenapplyfromnewprovider';// SKU屏蔽列表-推送到ERP
    $config['erp_system']['product_scree']['get_supplier_name']  = ERP_DOMAIN.'/services/products/product/getsupplierinfo';// 获取默认供应商
    $config['erp_system']['product_scree']['push_audit_to_erp']  = ERP_DOMAIN.'/services/products/product/getprovidercheckinfo';// 推送SKU屏蔽替换供应商审核结果到ERP
    $config['erp_system']['product_scree']['push_avg_price_erp']  = ERP_DOMAIN.'/services/products/productstock/getloadlastprice';// 平均运费推送到ERP
    $config['erp_system']['product_scree']['get_product_line']   = ERP_DOMAIN.'/services/products/product/productlinelist';//获取ERP产品线数据
    $config['erp_system']['product_scree']['send_supplier_audit']   = ERP_DOMAIN.'/services/products/product/getprovidercheckinfofromnewprovider';//获取ERP产品线数据
    $config['erp_system']['product_scree']['id'] = 102529;   // 采购系统推送ERP SKU 屏蔽ERP
    $config['erp_system']['purchase_price_to_erp'] = JAVA_SYSTEM_ERP.'yibaiProduct/updatePurchaseCost';

    /************************  蓝凌接口 ***************/
    $config['lanling']['ip']                                = LAN_LING_IP;// 蓝凌接口api
    $config['lanling']['lanling_url']                       = LAN_LING_IP.'sys/webservice/kmReviewWebserviceService?wsdl';// 蓝凌接口api
    $config['lan_api']['ip']                                =LAN_WEB_IP; //蓝凌WEB 端IP
    $config['lan_api']['web_ip']                            =LAN_WEB_IP.'km/review/km_review_main/kmReviewMain.do?method=view&'; //蓝凌WEB URL
    /************************  数据中心 ***************/
    $config['service_data']['ip']                                = SERVICE_DATA_IP;// 数据中心api
    $config['service_data']['push_purchase_suggest']             = SERVICE_DATA_IP.'index.php/PurchasesNew/platformSummaryToMysql';// 需求单推送到数据中心
    $config['service_data']['push_purchase_order']               = SERVICE_DATA_IP.'index.php/PurchasesNew/insertPurchaseToMysql';// 采购单推送到数据中心
//    $config['service_data']['push_report_loss']                  = SERVICE_DATA_IP.'index.php/purchases/cancelPurchaseByOrderSku';// 报损推送到数据中心(作废)
    $config['service_data']['push_report_loss_up']               = SERVICE_DATA_IP.'PurchasesNew/reducePruchaseOrderOnWayByDamaged';// 报损推送到数据中心(启用)
    $config['service_data']['push_reject_report_loss']           = SERVICE_DATA_IP.'index.php/purchasesNew/rejectCancelPurchase';// 报损驳回数据推送到数据中心
    $config['service_data']['push_product_repackage']            = WAREHOUSE_IP.'/index.php/purchases/purchaseSkuMarkToMysql';// 二次包装列表数据到数据中心
    /******************* 推送仓库 ************************/
    $config['warehouse']['ip']                                   =WAREHOUSE_IP; //仓库api
    $config['warehouse']['warehouse_route']                      =WAREHOUSE_IP.'/index.php/Purchases/cancelPurchaseByOrderSku'; //推送仓库api
    $config['warehouse']['warehouse_reject']                     =WAREHOUSE_IP.'/index.php/purchasesNew/rejectCancelPurchase'; //驳回推送仓库api

    $config['logistics']['ip']                                   =LOGISTICS_IP; //推送到包裹加急IP
    $config['logistics']['pust_logistics']                       =LOGISTICS_IP.'/Api/Purchase/Purchase/setDeliveryPurchase'; //包裹加急推送到仓库

    /************************ WMS 系统 ****************/
    $config['wms_system']['push_handler_res_to_warehouse']        =WMS_DOMAIN.'/Api/Purchase/QualityAbnormal/getQualityAbnormalResult'; //推送异常处理结果至仓库系统
    $config['wms_system']['get_defective_data']                   =WMS_DOMAIN.'/Api/Purchase/QualityAbnormal/getDefectiveData'; //定时拉取仓库异常处理结果
    $config['wms_system']['relate_express']                       =WMS_DOMAIN.'/Api/Purchase/Purchase/relateExpress'; //推送物流信息到仓库
    $config['wms_system']['report_loss_success_list']             =WMS_DOMAIN.'/Api/Purchase/Purchase/purchaseSkuFrmLoss'; //推送报损成功单到仓库
    $config['wms_system']['push_report_list_to_lock']             =WMS_DOMAIN.'/Api/Purchase/Purchase/cancelPurchaseOrderSku'; //推送报损单到仓库锁仓
    $config['wms_system']['pushPurchaseStatus']                   =JAVA_API_URL.'/wms/uebPurchase/pushPurchaseStatus';// 推送采购状态到仓库
    $config['wms_system']['updateWmsSuggestOrderStatus']          =JAVA_API_URL.'/wms/uebPurchase/updateWmsSuggestOrderStatus';// 推送备货单采购状态到仓库
    $config['wms_system']['push_express_info_to_wms']             =JAVA_API_URL.'/wms/uebDeliveryPurchase/pur2WmsCargoCompanyName';// 推送快递单号+快递公司到仓库


    $config['wms_system']['supplier_return_express_info']         =JAVA_API_URL.'/procurement/httpCallController/getSupplierReturnExpressInfo';// 采购-接收采购退货供应商拒收重发快递信息
    $config['wms_system']['supplier_reject_info']         =JAVA_API_URL.'/procurement/httpCallController/getSupplierRejectInfo';// 入库后退货-接收采购退货供应商拒收信息

    /******************* 1688java接口 ************************/
    $config['alibaba']['ip']                                   = ALIBABA_ACCOUNT_IP; //1688java接口api
    $config['alibaba']['list_buyer_view']                      = ALIBABA_ACCOUNT_IP.'/order/buyerView/listbuyerView';// 查看供应商账期授信信息
    $config['alibaba']['get_order_detail']                     = ALIBABA_ACCOUNT_IP.'/order/orderDetail/getOrderDetail';// 通过交易ID获取账期详情
    $config['alibaba']['crossBorder-getPayUrl']                = ALIBABA_ACCOUNT_IP.'/order/crossBorder/getPayUrl';// 获取使用跨境宝支付的支付链接
    $config['alibaba']['purchasingSolution-getLogisticsInfo']  = ALIBABA_ACCOUNT_IP.'/order/purchasingSolution/getLogisticsInfo';// 获取交易订单的物流信息
    $config['alibaba']['purchasingSolution-listLogisticsInfo'] = ALIBABA_ACCOUNT_IP.'/order/purchasingSolution/listLogisticsInfo';// 批量获取交易订单的物流信息
    $config['alibaba']['purchasingSolution-listOrderDetail']   = ALIBABA_ACCOUNT_IP.'/order/purchasingSolution/listOrderDetail';// 获取交易订单详细信息
    $config['alibaba']['purchasingSolution-getOrderPrice']     = ALIBABA_ACCOUNT_IP.'/order/purchasingSolution/getOrderPrice';// 获取订单最新总额
    $config['alibaba']['order-createCrossOrder']               = ALIBABA_ACCOUNT_IP.'/order/order/createCrossOrder';// 创建订单接口
    $config['alibaba']['order-createOrderPreview']             = ALIBABA_ACCOUNT_IP.'/order/crossBorder/createOrderPreview';// 创建订单前预览数据
    $config['alibaba']['product-getSupplierByProductId']       = ALIBABA_ACCOUNT_IP.'/order/product/getSupplierByProductId';// 根据商品id查询供应商信息
    $config['alibaba']['crossBorder-commodityDistribution']    = ALIBABA_ACCOUNT_IP.'/order/crossBorder/commodityDistribution';// 将商品加入铺货列表
    $config['alibaba']['crossBorder-getProductInfo']           = ALIBABA_ACCOUNT_IP.'/order/crossBorder/getProductInfo';// 获取商品详情
    $config['alibaba']['tracde-getTradeByReceiveAddress']      = ALIBABA_ACCOUNT_IP.'/order/tracde/getTradeByReceiveAddress';// 买家获取保存的收货地址信息列表
    $config['alibaba']['purchasingSolution-getLogisticsTraceInfo'] = ALIBABA_ACCOUNT_IP.'/order/purchasingSolution/getLogisticsTraceInfo';// 获取交易订单的物流跟踪信息
    $config['alibaba']['purchasingSolution-listLogisticsTraceInfo'] = ALIBABA_ACCOUNT_IP.'/order/purchasingSolution/listLogisticsTraceInfo';// 获取交易订单的物流跟踪信息(批量請求)
    $config['alibaba']['auth-listAccount']                     = ALIBABA_ACCOUNT_IP.'/order/auth/listAccount';// 获取子账号列表
    $config['alibaba']['auth-authAdd']                         = ALIBABA_ACCOUNT_IP.'/order/auth/authAdd';// 批量添加子账号授权
    $config['alibaba']['get_supplier_shop_id']                 = ALIBABA_ACCOUNT_IP.'/order/crossBorder/getSupplierInfo';// 根据供应商链接获取店铺id
    $config['alibaba']['get_trade_cancel']                 = ALIBABA_ACCOUNT_IP.'/order/tracdeCancel/getTradeCancel';//  根据交易ID取消订单
    $config['alibaba']['get_query_order_refund']                 = ALIBABA_ACCOUNT_IP.'/order/crossBorder/queryOrderRefund';//  根据交易ID获取退款单详情信息
    $config['alibaba']['product_pdt_product_gen']              = ALIBABA_ACCOUNT_IP.'/order/crossBorder/productGen';//  跨境产品开发工具同款开发
    $config['alibaba']['get_logistics_status']              = ALIBABA_ACCOUNT_IP.'/order/purchasingSolution/listLogisticsStatus';//  根据1688订单号,拍单号获取物流状态
    $config['alibaba']['get_logistics_trace_info']              = ALIBABA_ACCOUNT_IP.'/order/purchasingSolution/getLogisticsTraceInfo';//  根据拍单号和物流单号,获取交易订单的物流跟踪信息

    /******************* 计划系统 ************************/
    $config['plan_system']['push_suggest_order']              = PLAN_SYSTEM_APP_DAL_IP.'/plan/purchase/track_check_update';//推送生成采购单的需求单
    $config['plan_system']['push_expiration_suggest']         = PLAN_SYSTEM_APP_DAL_IP.'/plan/purchase/purchase_state_update';//推送过期需求单
    $config['plan_system']['push_purchase_order']             = PLAN_SYSTEM_APP_DAL_IP.'/plan/purchase/track_state_update';//推送采购单变更状态
    $config['plan_system']['push_audit_suggest']              = PLAN_SYSTEM_APP_DAL_IP.'/plan/purchase/purchase_approve_state_update';//推送审核需求单

    /******************* 老采购系统 ************************/
    $config['old_purchase']['supplier-get-change-supplier-list']   = OLD_PURCHASE.'/v1/supplier/get-change-supplier-list';// 获取老系统供应商状态
    $config['old_purchase']['supplier-get-supplier-one']      = OLD_PURCHASE.'/v1/supplier/get-supplier-one';// 获取老系统供应商
    $config['old_purchase']['supplier-get-supplier-status']   = OLD_PURCHASE.'/v1/supplier/get-supplier-status';// 获取老系统供应商状态
    $config['old_purchase']['supplier-get-avg_price']         = OLD_PURCHASE.'/v1/erp-sync/get-avg-price-by-sku';// 获取老系统平均运费相关数据
    $config['old_purchase']['sku-get-delivery_time']         = OLD_PURCHASE.'/v1/statistics/get-delivery-time';// 获取老系统权均交期
    $config['old_purchase']['supplier-push-new-info']         = OLD_PURCHASE.'/v1/supplier/get-new-purchase-system-change-info';// 新系统更新供应商同步数据到老系统
    $config['old_purchase']['supplier-push-new-buyer']        = OLD_PURCHASE.'/v1/supplier/get-new-purchase-system-change-buyer';// 新系统更新供应商同步数据到老系统-采购员
    $config['old_purchase']['supplier-push-new-status']       = OLD_PURCHASE.'/v1/supplier/get-new-purchase-system-change-status';// 新系统更新供应商同步数据到老系统-状态
    $config['old_purchase']['sku-get-delivery_time']         = OLD_PURCHASE.'/v1/statistics/get-delivery-time';// 获取老系统权均交期
    $config['old_purchase']['sku-purchase-order']            =  GET_JAVA_DATA.'/procurementold/purPurchaseOrder/listHistoryOrder'; // 获取老采购系统的SKU 下单记录
    $config['old_purchase']['supplier-get-cooperation-amount'] = OLD_PURCHASE.'/v1/customer-service/get-instock-amount-by-date';// 获取老系统供应商近三个月入库金额
    $config['old_purchase']['tongtu']                        = GET_JAVA_DATA.'/procurementold/purPurchaseHistory/listHistory';// 获取通途系统数据
    $config['old_purchase']['update-product-supply-status']   = OLD_PURCHASE.'/v1/supplier/update-product-source-status';// 更新老系统产品货源状态
    /********************匹配仓库规则******************/
    $config['yibaiLogistics']['batch_get_warehouse_info']     = WAREHOUSE_INFO_IP.'/logistics/yibaiLogisticsTransitRule/batchGetWarehouseInfo';// 批量匹配中转仓规则
    $config['yibaiLogistics']['access_token']                 = WAREHOUSE_ACCESS_TOKEN_IP.'/oauth/token?grant_type=client_credentials'; //warehouse_code获取access_token

    /********************物流系统******************/
    $config['logistics_rule']['push_suggest']                 = LOGISTICS_RULE_IP.'/ordersys/api/OrderStockingPlan/add';// 推送物流系统匹配规则

    /****************财务系统********************/
    $config['declare_customs']['purchaseSysInsertData']      = FINANCE_IP.'/drawback/declare_customs/purchaseSysInsertData';// 推送财务系统
    $config['purchase_api']['purchaseSysInsertData']          = SETTLEMENT_IP.'/purchase/purchase_api/purchaseSysInsertData';// 推送结汇系统


    /****************计划系统java接口********************/
    $config['java_system_plan']['push_expiration_suggest']         = JAVA_SYSTEM_PLAN.'prNumber/updatePrByDemandStatus';//推送过期需求单
    $config['java_system_plan']['push_purchase_order']             = JAVA_SYSTEM_PLAN.'yibaiPurchaseTrack/updatePoStatus';//推送采购单变更状态
    $config['java_system_plan']['push_audit_suggest']              = JAVA_SYSTEM_PLAN.'prNumber/updatePrByAudit';//推送审核需求单

    /*********JAVA OA 获取公司人员信息************************/

    $config['java_system_oa']['get_company_all'] =  OA_URL.'/oaUser/listOaUser';
    $config['java_system_oa']['get_company_all_dep'] = OA_URL.'/oaDepartment/getOaDepartmentAll'; // 获取OA 部门信息
    $config['java_system_oa']['get_user_job']        = OA_URL.'/oaUser/getUserListByUserIdAndUserName'; // 获取用户职位信息
    $config['java_system_product_url']['java_url'] =  PRODUCT_OA.'/yibaiProduct/getProductBySku';

    /*******1688 子账号**********/

    $config['java_system_alibabasub']['url'] = PRODUCT_ALIBABASUB.'/yibaiProdAlibabaSub/insertOrUpdateAlibabaSub';
    $config['java_system_alibabasub']['del'] = PRODUCT_ALIBABASUB.'/yibaiProdAlibabaSub/deleteAlibabaSub';

    /******************* JAVA ES查询 ******************/
    $config['java_es_api']['get_stock'] = JAVA_API_URL.'/elatic/es/product/findProductStork';

    /********采购系统商品属性推送到ERP*******/
    $config['java_system_product_message']['supply_status_url'] = PRODUCT_OA.'/yibaiProduct/updateProviderStatusBySku'; // 推送货源状态到ERP

    /*******服务总线**********/
    $config['java_system_service']['report_loss']              = GET_JAVA_DATA .'/procurement/purPurchaseOrder/reportLoss';//java服务总线报损
    $config['java_system_service']['report_loss_success_list'] = GET_JAVA_DATA .'/procurement/purPurchaseOrder/reportLossNotice';//java服务总线报损成功通知
    $config['java_system_service']['cancel']                   = GET_JAVA_DATA .'/procurement/purPurchaseOrder/cancel';//java服务总线取消未到货

    /*******物流轨迹******************/
    $config['logistics_locus']['express_info'] = GET_JAVA_DATA . '/logistics/yibaiOrderCnKuaidi/saveCnKuaidi';//推送快递单号和快递公司编码到Java
    $config['logistics_locus']['query_tracks'] = GET_JAVA_DATA . '/logistics/yibaiLogisticsTracks/selectLogisticsTracks';//手动刷新，获取物流轨迹数据
    $config['alibaba']['purchasingSolution-listLogisticsTraceInfo'] = ALIBABA_ACCOUNT_IP.'/order/purchasingSolution/listLogisticsTraceInfo';// 获取交易订单的物流跟踪信息(批量請求)

    $config['java_system_service']['transfer'] = GET_JAVA_DATA .'/procurement/purMultipleGoods/transfer';//java调拨接口

}


