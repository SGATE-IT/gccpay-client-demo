package com.GCCPay.demo;

import net.sf.json.JSONObject;
import org.apache.http.HttpEntity;
import org.apache.http.client.config.RequestConfig;
import org.apache.http.client.methods.CloseableHttpResponse;
import org.apache.http.client.methods.HttpGet;
import org.apache.http.client.methods.HttpPost;
import org.apache.http.entity.StringEntity;
import org.apache.http.impl.client.CloseableHttpClient;
import org.apache.http.impl.client.HttpClients;
import org.apache.http.util.EntityUtils;

import org.apache.log4j.Logger;
import sun.misc.BASE64Encoder;

import javax.crypto.Mac;
import javax.crypto.spec.SecretKeySpec;
import java.text.SimpleDateFormat;
import java.util.Calendar;
import java.util.UUID;

public class GCCPayDemo {

    private static final Logger logger = Logger.getLogger(GCCPayDemo.class);
    //client info
    private static final String MERCHANTID = "M123456";	///  Merchantid => start with:M  + 6 digital
    private static final String ID = "CLT1234567";	/// Clientid => start with:CLI + 7 character
    private static final String KEY = "Ax***Bi"; ///  Key => 32 bytes  digital + characters 
    private static final String SECRET = "Dc*****Ie";	///  Secret =>  64  bytes  digital + characters 
    private static final String BASEURI_SANDBOX = "https://sandbox.gcc-pay.com/api_v1";		/// sandbox 
    private static final String BASEURI_PROD = "https://gateway.gcc-pay.com/api_v1";		/// product

    private String environment = "sandbox";
    SimpleDateFormat sdf = new SimpleDateFormat("yyyy-MM-dd HH:mm:ss.SSS");

    public String getEnvironment() {
        return environment;
    }

    public void setEnvironment(String environment) {
        this.environment = environment;
    }

    public void getMerchantDetail() {
        try {
            String uri = "/merchants/" + MERCHANTID;
            String method = "merchant.detail";
            String result = doGet(uri,method, null);
            logger.info("getMerchantDetail result is :" + result);
        } catch (Exception e) {
            e.printStackTrace();
        }
    }

    public void getOrderInfo(String orderId ){
        try {
            String uri = "/orders/" + orderId;
            String method = "order.detail";
            String result = doGet(uri,method, null );
            logger.info("getOrderInfo result is :" + result);
        }catch (Exception e){
            e.printStackTrace();
        }
    }

/*

 */
    public void createNewOrder(String orderId,long amount,String currency,String desc ,String notificationURL,String expiredAt){

        CloseableHttpClient client = null;
        CloseableHttpResponse response = null;

        String method ="merchant.addOrder";
        String timestamp = String.valueOf(System.currentTimeMillis()/1000);
        try{
            client = HttpClients.createDefault();
            JSONObject params = new JSONObject();
            params.put("amount",amount);
            params.put("currency",currency);
            if(expiredAt==null ||expiredAt.length()==0){
                Calendar calendar= Calendar.getInstance();
                calendar.add(Calendar.SECOND,3600*24*15);
                expiredAt = sdf.format(calendar.getTime());
            }
            params.put("expiredAt",expiredAt);
            params.put("merchantOrderId",orderId);
            params.put("name",desc);
            params.put("notificationURL",notificationURL);

            String uri="/merchants/" + MERCHANTID + "/orders";

            String sendUri ;
            if (environment.equals("sandbox")) {
                sendUri = BASEURI_SANDBOX + uri;
            } else {
                sendUri = BASEURI_PROD + uri;
            }
            HttpPost post = new HttpPost(sendUri);
            post.setEntity(new StringEntity(params.toString(),"UTF-8"));

            RequestConfig config = RequestConfig.custom().setConnectTimeout(10000).setConnectionRequestTimeout(3000)
                    .setSocketTimeout(20000).build();
            post.setConfig(config);
            post.addHeader("Content-Type", "application/json");
            post.addHeader("x-auth-signature", generateSignStr(uri, method,timestamp));
            post.addHeader("x-auth-key", KEY);
            post.addHeader("x-auth-timestamp", timestamp);
            post.addHeader("x-auth-sign-method", "HmacSHA256");
            post.addHeader("x-auth-uuid", UUID.randomUUID().toString());
            post.addHeader("x-auth-sign-version", "1");

            response = client.execute(post);

            HttpEntity entity = response.getEntity();
            String result = EntityUtils.toString(entity);

            logger.info("create new order result:"+result);

        }catch (Exception e){
            e.printStackTrace();
        }finally {
            try{
                if(client!=null){
                    client.close();
                }
                if(response!=null){
                    response.close();
                }
            }catch ( Exception e){
                e.printStackTrace();
            }
        }

    }

    private String generateSignStr(String uri,String method,String timestamp) throws Exception {
        JSONObject params = new JSONObject();
        params.put("key",KEY);
        params.put("method",method);
        params.put("signMethod","HmacSHA256");
        params.put("signVersion",1);
        params.put("timestamp",timestamp);
        params.put("uri",uri);

        String signStr ="";
        for(Object key:params.keySet()){
            if(signStr.length()>0){
                signStr = signStr + "&";
            }
            String value = String.valueOf(key).equals("uri")? java.net.URLEncoder.encode(String.valueOf(params.get(key)),"UTF-8"):String.valueOf(params.get(key));
            signStr = signStr + String.valueOf(key)+"="+value;
        }
        return  encode(signStr);
    }

    public String encode(String signStr) throws Exception {
        SecretKeySpec keySpec = new SecretKeySpec(SECRET.getBytes(), "HmacSHA256");
        Mac mac = Mac.getInstance("HmacSHA256");
        mac.init(keySpec);
        byte[] result = mac.doFinal(signStr.getBytes());
        BASE64Encoder encoder = new BASE64Encoder();
        return encoder.encode(result);
    }

    public String doGet(String uri, String method,JSONObject params) {

        CloseableHttpClient client = null;
        CloseableHttpResponse response = null;

        try {
            String sendUri;
            if (environment.equals("sandbox")) {
                sendUri = BASEURI_SANDBOX + uri;
            } else {
                sendUri = BASEURI_PROD + uri;
            }
            String timestamp = String.valueOf(System.currentTimeMillis()/1000);

            client = HttpClients.createDefault();
            HttpGet get = new HttpGet(sendUri);
            RequestConfig config = RequestConfig.custom().setConnectTimeout(10000).setConnectionRequestTimeout(3000)
                    .setSocketTimeout(20000).build();
            get.setConfig(config);
            get.addHeader("Content-Type", "application/json");
            get.addHeader("x-auth-signature", generateSignStr(uri,method,timestamp));
            get.addHeader("x-auth-key", KEY);
            get.addHeader("x-auth-timestamp", timestamp);
            get.addHeader("x-auth-sign-method", "HmacSHA256");
            get.addHeader("x-auth-uuid", UUID.randomUUID().toString());
            get.addHeader("x-auth-sign-version", "1");

            response = client.execute(get);

            logger.info("get response code:" + response.getStatusLine().getStatusCode());

            HttpEntity entity = response.getEntity();
            String result = EntityUtils.toString(entity);
            logger.info("get result:" + result);
            return result;

        } catch (Exception e) {
            e.printStackTrace();
        } finally {
            try {
                if (client != null) {
                    client.close();
                }
                if (response != null) {
                    response.close();
                }
            } catch (Exception e) {
                e.printStackTrace();
            }
        }
        return "error";
    }

    public static void main(String[] args) throws Exception{

        GCCPayDemo gccPayDemo = new GCCPayDemo();
        gccPayDemo.getMerchantDetail();
        gccPayDemo.createNewOrder("11223312300b",200,"SAR","nodesc","","");
        gccPayDemo.getOrderInfo("M448726T2023010103324820757561");

    }

}


/*
* mvn dependency
<dependencies>
        <dependency>
            <groupId>commons-logging</groupId>
            <artifactId>commons-logging</artifactId>
            <version>1.2</version>
        </dependency>
        <dependency>
            <groupId>org.apache.logging.log4j</groupId>
            <artifactId>log4j-api</artifactId>
            <version>2.17.1</version>
        </dependency>
        <dependency>
            <groupId>org.apache.logging.log4j</groupId>
            <artifactId>log4j-core</artifactId>
            <version>2.17.1</version>
        </dependency>
        <dependency>
            <groupId>org.slf4j</groupId>
            <artifactId>slf4j-log4j12</artifactId>
            <version>1.7.26</version>
        </dependency>
        <dependency>
            <groupId>org.apache.httpcomponents</groupId>
            <artifactId>httpclient</artifactId>
            <version>4.5.13</version>
        </dependency>
        <dependency>
            <groupId>net.sf.json-lib</groupId>
            <artifactId>json-lib</artifactId>
            <version>2.4</version>
            <classifier>jdk15</classifier>
        </dependency>
    </dependencies>
* */