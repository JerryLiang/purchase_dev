<?php
/**
 * 拉卡拉接口配置
 * Created by PhpStorm.
 * User: totoro
 * Date: 2020-05-29
 * Time: 9:49
 */

/*********************** 拉卡拉支付 配置 ***********************/
if(CG_ENV == 'dev')  //开发环境
{
    $config['lakala']['private_key'] = '-----BEGIN RSA PRIVATE KEY-----
MIICXQIBAAKBgQCZipfORL6NbzX5mHBIkGC7k8F8cM9jVy2AAuFZUnC9+kPaHdRupC7J0WuaOvFZtP126kkfnZ3dqkZ8Ochcv7dhz5l2wXTQAJsqlh9r/rmBYrDiUqJ3Vrc7ImFrY/NN1sDc7jlhbeyE2eEDH0dgxz6kVtfH07+8tOSI2W9My5X/LwIDAQABAoGAKRtr7TOGeMNPhhWD6kmNPGsgoDprq8MJUX5z6sAhoxs/00OtPjoCtNG2p0Ikn8nPGmk7TpWaUGBoIpYtyHcjl0Xr7djmMPLQXG3Zf2+JZQpz/RTZ/UT+PkpPFkaC5y1ZnnrKKgcg6Y/WIdxB3gzIZEKerMNdLMLcLDn+So7Mb6ECQQDy6JfjfCbZTNZU1iFJD0CXIbv/nGYTDARaR8umsLvwQN2mrTNz5AyzxQq0HgSAHOv4I0wbBj1UhNg77xDCWjeZAkEAodEAaZljiyRv4sWcZlcq/+TUjGfv1qDseu1KVYGiJnOvfoOeKBhkS5TXFr8cKLCo8qpWZjQGP3lLYshF4haKBwJAF/RmHjH9JsrUDDO9vpW5ee4CuzdyPYie2URhSgP91Lig4zILc+9WbVgOMSsQqI2xm5vngnbAD5i2NlriHTiGaQJBAImsd6xgvAezXZpURQfxm/0R5SD8gVtbmTfRUhBD9gC/Jo3+T36Pmi2QGhwZR0z7WRL1iAL2umYgvdnyyTpdsNsCQQCyoE3U89IQfqMbB3PhuZJYqNo/k/uZl/cGlUNFxYWM4EhQEMIx3Fta6ECXsVZzCG23zGTFxyoleC49ODkWAjxj
-----END RSA PRIVATE KEY-----';//秘钥
    $config['lakala']['public_key']  = '-----BEGIN PUBLIC KEY-----
MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCrTApQnol9FvaAla0sBW6Kz8UYiP6BUj2trPXIhraynK/Rur51Umo9FJzNZzx5P6a5wUj/wDxfzxPCrtOGS4g0K4w9QkNnYQAao2PL2A0XTSvCsbVdC8+pRF2sFo0Hs6dyshKn4oQAt/M9KIqjLtco5/zIUCS6HpOrP4t9ogTR/wIDAQAB
-----END PUBLIC KEY-----';//公钥

    $config['lakala']['api_host']               = 'https://test.wsmsd.cn/sit/crosspay';//测试环境; https://ntl.lakala.com 生产环境
    $config['lakala']['cust_no']                = 'GSPCHN190800007';//商户号  测试:GSPCHN190800007; 线上:888075572990002
    $config['lakala']['registry']               = '/batchPay/registry';//批量代付注册接口
    $config['lakala']['queryBatchFileStatus']   = '/batchPay/queryBatchFileStatus';//批量代付文件批次查询接口
    $config['lakala']['querySingleTransStatus'] = '/batchPay/querySingleTransStatus';//批量代付单笔交易查询接口
    $config['lakala']['downloadBackFile']       = '/batchPay/downloadBackFile';//批量代付回盘文件下载接口
    $config['lakala']['downloadErrorBackFile']  = '/batchPay/downloadErrorBackFile';//批量代付解析回盘文件下载接口


}elseif(CG_ENV == 'prod'){  //生产环境
    $config['lakala']['private_key'] = '-----BEGIN RSA PRIVATE KEY-----
-----END RSA PRIVATE KEY-----';//秘钥
    $config['lakala']['public_key']  = '-----BEGIN PUBLIC KEY-----
-----END PUBLIC KEY-----';//公钥

    $config['lakala']['api_host']               = 'https://intl.lakala.com/sit/crosspay';//测试环境; https://intl.lakala.com
    $config['lakala']['cust_no']                = 'GSPCHN190800006';//商户号  测试:GSPCHN190800007; 线上:GSPCHN190800006
    $config['lakala']['registry']               = '/batchPay/registry';//批量代付注册接口
    $config['lakala']['queryBatchFileStatus']   = '/batchPay/queryBatchFileStatus';//批量代付文件批次查询接口
    $config['lakala']['querySingleTransStatus'] = '/batchPay/querySingleTransStatus';//批量代付单笔交易查询接口
    $config['lakala']['downloadBackFile']       = '/batchPay/downloadBackFile';//批量代付回盘文件下载接口
    $config['lakala']['downloadErrorBackFile']  = '/batchPay/downloadErrorBackFile';//批量代付解析回盘文件下载接口

}else{  //测试环境
    $config['lakala']['private_key'] = '-----BEGIN RSA PRIVATE KEY-----
MIICXQIBAAKBgQCZipfORL6NbzX5mHBIkGC7k8F8cM9jVy2AAuFZUnC9+kPaHdRupC7J0WuaOvFZtP126kkfnZ3dqkZ8Ochcv7dhz5l2wXTQAJsqlh9r/rmBYrDiUqJ3Vrc7ImFrY/NN1sDc7jlhbeyE2eEDH0dgxz6kVtfH07+8tOSI2W9My5X/LwIDAQABAoGAKRtr7TOGeMNPhhWD6kmNPGsgoDprq8MJUX5z6sAhoxs/00OtPjoCtNG2p0Ikn8nPGmk7TpWaUGBoIpYtyHcjl0Xr7djmMPLQXG3Zf2+JZQpz/RTZ/UT+PkpPFkaC5y1ZnnrKKgcg6Y/WIdxB3gzIZEKerMNdLMLcLDn+So7Mb6ECQQDy6JfjfCbZTNZU1iFJD0CXIbv/nGYTDARaR8umsLvwQN2mrTNz5AyzxQq0HgSAHOv4I0wbBj1UhNg77xDCWjeZAkEAodEAaZljiyRv4sWcZlcq/+TUjGfv1qDseu1KVYGiJnOvfoOeKBhkS5TXFr8cKLCo8qpWZjQGP3lLYshF4haKBwJAF/RmHjH9JsrUDDO9vpW5ee4CuzdyPYie2URhSgP91Lig4zILc+9WbVgOMSsQqI2xm5vngnbAD5i2NlriHTiGaQJBAImsd6xgvAezXZpURQfxm/0R5SD8gVtbmTfRUhBD9gC/Jo3+T36Pmi2QGhwZR0z7WRL1iAL2umYgvdnyyTpdsNsCQQCyoE3U89IQfqMbB3PhuZJYqNo/k/uZl/cGlUNFxYWM4EhQEMIx3Fta6ECXsVZzCG23zGTFxyoleC49ODkWAjxj
-----END RSA PRIVATE KEY-----';//秘钥
    $config['lakala']['public_key']  = '-----BEGIN PUBLIC KEY-----
MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCrTApQnol9FvaAla0sBW6Kz8UYiP6BUj2trPXIhraynK/Rur51Umo9FJzNZzx5P6a5wUj/wDxfzxPCrtOGS4g0K4w9QkNnYQAao2PL2A0XTSvCsbVdC8+pRF2sFo0Hs6dyshKn4oQAt/M9KIqjLtco5/zIUCS6HpOrP4t9ogTR/wIDAQAB
-----END PUBLIC KEY-----';//公钥

    $config['lakala']['api_host']               = 'https://test.wsmsd.cn/sit/crosspay';//测试环境; https://ntl.lakala.com 生产环境
    $config['lakala']['cust_no']                = 'GSPCHN190800007';//商户号  测试:GSPCHN190800007; 线上:888075572990002
    $config['lakala']['registry']               = '/batchPay/registry';//批量代付注册接口
    $config['lakala']['queryBatchFileStatus']   = '/batchPay/queryBatchFileStatus';//批量代付文件批次查询接口
    $config['lakala']['querySingleTransStatus'] = '/batchPay/querySingleTransStatus';//批量代付单笔交易查询接口
    $config['lakala']['downloadBackFile']       = '/batchPay/downloadBackFile';//批量代付回盘文件下载接口
    $config['lakala']['downloadErrorBackFile']  = '/batchPay/downloadErrorBackFile';//批量代付解析回盘文件下载接口

}