# -*- coding: utf-8 -*-
# GCCPay Class
# afa@GCCPay.com 2022/12/05

import requests
import json
import uuid
import urllib.parse
import logging
import time
import base64
import hmac
import hashlib

class GCCPay:
    merchant_key = ""
    merchant_secret = ""
    merchant_id = ""
    merchant_clientid=""
    environment = "sandbox"
    logger = None
    ###  Init GCCPay
    def __init__(self,merchant_key="",merchant_secret="",merchant_id="",merchant_clientid = "", environment = "sandbox"):
        logging.basicConfig(level = logging.INFO,format = '%(asctime)s - %(name)s - %(levelname)s - %(message)s')
        self.logger = logging.getLogger("GCCPay")
        self.merchant_key = merchant_key
        self.merchant_secret = merchant_secret
        self.merchant_id = merchant_id
        self.merchant_clientid = merchant_clientid
        if(environment.lower() != "product"):
            self.environment = "sandbox"
        else:
            self.environment = "product"
        self.logger.info("init param:merchant_key=>%s ; merchant_secret=>%s ; merchant_id=>%s ; merchant_clientid=>%s ; merchant_environment=>%s "%(self.merchant_key,self.merchant_secret,self.merchant_id,self.merchant_clientid ,self.environment))
    
    ### Create New Order
    def createNewOrder(self,merchantOrderId="",amount=0,currency="SAR",name="order desc is empty",notificationURL="",expiredAt=""):
        params = {}
        params["merchantOrderId"] = merchantOrderId
        params["amount"] = amount
        params["currency"] = currency
        params["name"] = name
        params["notificationURL"] = notificationURL
        if(expiredAt):
            params["expiredAt"] = expiredAt
        else:
            params["expiredAt"] = time.strftime('%Y-%m-%dT%H:%M:%S.000Z',time.localtime(time.time()+3600*24*15))
        
        uri = "/merchants/" + self.merchant_id + "/orders" 

        return self.submitToGCCPay(uri=uri,post="post",method="merchant.addOrder",params=params)
    
    ### getOrderInfo
    def getOrderInfo(self,orderId= ""):
        uri = "/orders/" + orderId
        return self.submitToGCCPay(uri,post="get",method="order.detail")

    ### getMerchantDetail
    def getMerchantDetail(self):
        uri = "/merchants/"+self.merchant_id
        return self.submitToGCCPay(uri,method="merchant.detail")
    ### submit data to GCCPay
    def submitToGCCPay(self,uri="",post="get",params={},method=""):

        headers= {}
        ### sign
        timestamp = str(int(time.time()))
        signArr = {}
        signArr["key"] = self.merchant_key
        signArr["method"] = method
        signArr["signMethod"] = "HmacSHA256"
        signArr["signVersion"] = 1
        signArr["timestamp"] = timestamp
        signArr["uri"] = uri
        signStr = urllib.parse.urlencode(sorted(signArr.items(),key=lambda d:d[0]))
        self.logger.info("sign str:%s"%signStr)
        sign = base64.b64encode(hmac.new(bytes(self.merchant_secret,encoding = "utf-8"),bytes(signStr,encoding = "utf-8"),hashlib.sha256).digest()).decode()
        self.logger.info("sign:%s"%sign)

        ### set headers
        headers["Content-Type"] = "application/json"
        headers["x-auth-signature"] = sign
        headers["x-auth-key"] = self.merchant_key
        headers["x-auth-timestamp"] = timestamp
        headers["x-auth-sign-method"] = "HmacSHA256"
        headers["x-auth-uuid"] = str(uuid.uuid1())
        headers["x-auth-sign-version"] = "1"

        if(self.environment == "sandbox"):
            url = "https://sandbox.gcc-pay.com/api_v1" + uri
        else:
            url = "https://gateway.gcc-pay.com/api_v1" + uri
        
        ret = ""
        self.logger.info("submit to GCCPay:type=>%s ;url=>%s ; headers=>%s ;data=>%s"%(post,url,json.dumps(headers),json.dumps(params)))
        if(post.lower() == "get"):
            ret = requests.get(url,headers=headers,params=params).json()
        else:
            ret = requests.post(url,headers=headers,data=json.dumps(params)).json()
        
        self.logger.info("GCCPay response:%s"%json.dumps(ret))
        return ret

if(__name__ == "__main__"):

    merchant_key = "zS****zQI" ### Key => 32 bytes  digital + characters 
    merchant_secret = "Ro****B0Z"   ### Secret =>  64  bytes  digital + characters 
    merchant_id = "M123456" ### Merchantid => start with:M  + 6 digital
    merchant_clientid="CLT1234567" ### Clientid => start with:CLI + 7 character
    environment = "sandbox" ### sandbox or product


    GCCPayobj = GCCPay(merchant_id=merchant_id , merchant_clientid=merchant_clientid ,merchant_key=merchant_key,merchant_secret=merchant_secret,environment=environment)

    MerchantDetail = GCCPayobj.getMerchantDetail()
    print(MerchantDetail)
    # # output:
    # {
    #     "id": "M123456T2022120606162806147062",
    #     "clientId": "CLT1234567",
    #     "merchantId": "M123456",
    #     "status": "pending",
    #     "ticket": "X3SxHrKN1sun22s3jRfTJF2Cq7xvs5uLUUkq6pqZJoUvKj2eynQv12jXAP4MlRZE",
    #     "name": "Order test for desc",
    #     "merchantOrderId": "TEST1670307394",
    #     "notificationURL": "",
    #     "amount": 12.02,
    #     "currency": "SAR",
    #     "expiredAt": "2022-12-21T14:16:34.000Z",
    #     "createdAt": "2022-12-06T06:16:28.060Z",
    #     "refundAmount": 0
    # }
    NewOrderDetail = GCCPayobj.createNewOrder(merchantOrderId="TEST"+str(int(time.time())),amount=12.02,name="Order test for desc")
    print(NewOrderDetail)
    # # output:
    # {
    #     "id": "M123456T2022120606162806147062",
    #     "clientId": "CLT1234567",
    #     "merchantId": "M123456",
    #     "status": "pending",
    #     "ticket": "X3SxHrKN1sun22s3jRfTJF2Cq7xvs5uLUUkq6pqZJoUvKj2eynQv12jXAP4MlRZE",
    #     "name": "Order test for desc",
    #     "merchantOrderId": "TEST1670307394",
    #     "notificationURL": "",
    #     "amount": 12.02,
    #     "currency": "SAR",
    #     "expiredAt": "2022-12-21T14:16:34.000Z",
    #     "createdAt": "2022-12-06T06:16:28.060Z",
    #     "refundAmount": 0
    # }
    CheckOrderDetail = GCCPayobj.getOrderInfo(orderId=NewOrderDetail["id"])
    print(CheckOrderDetail)
    # # output:
    # {
    #     "amount": 12.02,
    #     "refundAmount": 0,
    #     "id": "M123456T2022120606162806147062",
    #     "clientId": "CLT1234567",
    #     "merchantId": "M123456",
    #     "agencyId": "A936252",
    #     "status": "pending",
    #     "ticket": "X3SxHrKN1sun22s3jRfTJF2Cq7xvs5uLUUkq6pqZJoUvKj2eynQv12jXAP4MlRZE",
    #     "name": "Order test for desc",
    #     "merchantOrderId": "TEST1670307394",
    #     "channelOrderId": "None",
    #     "notificationURL": "",
    #     "currency": "SAR",
    #     "paidAt": "None",
    #     "expiredAt": "2022-12-21T14:16:34.000Z",
    #     "message": "None",
    #     "refundTimes": "None",
    #     "createdAt": "2022-12-06T06:16:28.000Z",
    #     "updatedAt": "2022-12-06T06:16:28.000Z"
    # }    




