<?php
/**
 * @author
 *
 */
class PaysafecardRefundController
{
    private $response;
    private $request = array();
    private $curl;
    private $key         = "";
    private $url         = "";
    private $sandbox = true;

    public function __construct($key = "", $sandbox = true)
    {
        $this->key         = $key;
        $this->sandbox = $sandbox;
        $this->setEnvironment();
    }

    /**
     * send curl request
     * @param assoc array $curlparam
     * @param httpmethod $method
     * @return null
     */
    private function doRequest($curlparam, $method, $headers = array())
    {
        $ch = curl_init();

        $header = array(
            "Authorization: Basic " . base64_encode($this->key),
            "Content-Type: application/json",
        );

        $header = array_merge($header, $headers);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        if ($method == 'POST') {
            curl_setopt($ch, CURLOPT_URL, $this->url);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($curlparam));
            curl_setopt($ch, CURLOPT_POST, true);
        } elseif ($method == 'GET') {
            curl_setopt($ch, CURLOPT_URL, $this->url . $curlparam);
            curl_setopt($ch, CURLOPT_POST, false);
        }
        curl_setopt($ch, CURLOPT_PORT, 443);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        if (is_array($curlparam)) {
            $curlparam['request_url'] = $this->url;

        } else {
            $requestURL               = $this->url . $curlparam;
            $curlparam                = array();
            $curlparam['request_url'] = $requestURL;
        }
        $this->request  = $curlparam;
        $this->response = json_decode(curl_exec($ch), true);

        $this->curl["info"]        = curl_getinfo($ch);
        $this->curl["error_nr"]    = curl_errno($ch);
        $this->curl["error_text"]  = curl_error($ch);
        $this->curl["http_status"] = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $this->setEnvironment();
    }

    /**
     * check request status
     * @return bool
     */
    public function requestIsOk()
    {
        if (($this->curl["error_nr"] == 0) && ($this->curl["http_status"] < 300)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * get the request
     * @return mixed request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * get curl
     * @return mixed curl
     */
    public function getCurl()
    {
        return $this->curl;
    }

    /**
     * get details of a payment
     * @param string $payment
     * @return response
     */

    public function getPaymentDetail($payment = "")
    {
        $this->doRequest($payment, "GET");
        return $this->response;
    }

    /**
     * validate a refund
     * @param string $payment_id
     * @param double $amount
     * @param string|currencycode $currency
     * @param string $merchantclientid
     * @param string $customer_mail
     * @param string $customer_ip
     * @param string $correlation_id
     * @return reponse|false
     */

    public function validateRefund($payment_id, $amount, $currency, $merchantclientid, $customer_mail, $customer_ip, $correlation_id = "", $submerchant_id = "")
    {
        $amount    = str_replace(',', '.', $amount);
        $jsonarray = array(
            "amount"   => $amount,
            "currency" => $currency,
            "type"     => "PAYSAFECARD",
            "customer" => array(
                "id"         => $merchantclientid,
                "email"      => $customer_mail,
                "first_name" => "Test",
                "last_name"  => "Test",
                "ip"         => $customer_ip,
            ),
            "capture"  => "false",
        );

        if ($submerchant_id != "") {
            array_push($jsonarray, [
                "submerchant_id" => $submerchant_id,
            ]);
        }

        if ($correlation_id != "") {
            $headers = ["Correlation-ID: " . $correlation_id];
        } else {
            $headers = [];
        }

        $this->url = $this->url . $payment_id . "/refunds";
        $this->doRequest($jsonarray, "POST", $headers);
        if ($this->requestIsOk() == true) {
            return $this->response;
        } else {
            return false;
        }
    }

    /**
     * execute a refund
     * @param string $payment_id
     * @param string $refund_id
     * @param double $amount
     * @param string|currencycode $currency
     * @param string $merchantclientid
     * @param string $customer_mail
     * @param string $customer_ip
     * @param string $correlation_id
     * @return reponse|false
     */
    public function executeRefund($payment_id, $refund_id, $amount, $currency, $merchantclientid, $customer_mail, $customer_ip, $correlation_id = "", $submerchant_id = "")
    {
        $amount    = str_replace(',', '.', $amount);
        $jsonarray = array(
            "amount"   => $amount,
            "currency" => $currency,
            "type"     => "PAYSAFECARD",
            "customer" => array(
                "id"            => $merchantclientid,
                "email"         => $customer_mail,
                "first_name"    => "Test",
                "last_name"     => "Test",
                "date_of_birth" => "1990-01-09",
                "ip"            => $customer_ip,
            ),
            "capture"  => "true",
        );

        if ($submerchant_id != "") {
            array_push($jsonarray, [
                "submerchant_id" => $submerchant_id,
            ]);
        }

        if ($correlation_id != "") {
            $headers = ["Correlation-ID: " . $correlation_id];
        } else {
            $headers = [];
        }
        $this->url = $this->url . $payment_id . "/refunds/" . $refund_id . "/capture";
        $this->doRequest($jsonarray, "POST", $headers);
        return $this->response;
    }

    /**
     * refund a payment directly
     * @param string $payment_id
     * @param double $amount
     * @param string|currencycode $currency
     * @param string $merchantclientid
     * @param string $customer_mail
     * @param string $customer_ip
     * @param string $correlation_id
     * @return reponse|false
     */
    public function directRefund($payment_id, $amount, $currency, $merchantclientid, $customer_mail, $customer_ip, $correlation_id = "", $submerchant_id = "")
    {
        $amount    = str_replace(',', '.', $amount);
        $jsonarray = array(
            "amount"   => $amount,
            "currency" => $currency,
            "type"     => "PAYSAFECARD",
            "customer" => array(
                "id"            => $merchantclientid,
                "email"         => $customer_mail,
                "first_name"    => "Test",
                "last_name"     => "Test",
                "date_of_birth" => "1990-01-09",
                "ip"            => $customer_ip,
            ),
            "capture"  => "true",
        );

        if ($submerchant_id != "") {
            array_push($jsonarray, [
                "submerchant_id" => $submerchant_id,
            ]);
        }

        if ($correlation_id != "") {
            $headers = ["Correlation-ID: " . $correlation_id];
        } else {
            $headers = [];
        }
        $this->url = $this->url . $payment_id . "/refunds";
        $this->doRequest($jsonarray, "POST", $headers);
        return $this->response;
    }

    /**
     * get the response
     * @return mixed
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * set environment
     * @return mixed
     */
    private function setEnvironment()
    {
        if ($this->sandbox) {
            $this->url = "https://apitest.paysafecard.com/v1/payments/";
        } else if (!$this->sandbox) {
            $this->url = "https://api.paysafecard.com/v1/payments/";
        } else {
            echo "Environment '$this->sandbox' not supported. Please set it to 'true' or 'false'.";
            return false;
        }
        return true;
    }

    /**
     * get error
     * @return response
     */
    public function getError()
    {
        if (!isset($this->response["number"])) {
            switch ($this->curl["info"]['http_code']) {
                case 400:
                    $this->response["number"]  = "HTTP:400";
                    $this->response["message"] = 'Logical error. Please check logs.';
                    break;
                case 403:
                    $this->response["number"]  = "HTTP:403";
                    $this->response["message"] = 'IP not whitelisted! Your IP:' . $_SERVER["SERVER_ADDR"];
                    break;
                case 500:
                    $this->response["number"]  = "HTTP:500";
                    $this->response["message"] = 'Server error. Please check logs.';
                    break;
            }
        }
        switch ($this->response["number"]) {
            case 3160:
                $this->response["message"] = 'Invalid customer details. Please forward the customer to contact our support';
                break;
            case 3162:
                $this->response["message"] = 'E-mail address is not registered with mypaysafecard';
                break;
            case 3165:
                $this->response["message"] = 'The amount is invalid. Maximum refund amount cannot exceed the original payment amount';
                break;
            case 3167:
                $this->response["message"] = 'Customer limit exceeded. Please forward the customer to contact our support';
                break;
            case 3179:
                $this->response["message"] = 'The amount is invalid. Maximum refund amount cannot exceed the original payment amount';
                break;
            case 3180:
                $this->response["message"] = 'Original Transaction is in an invalid state';
                break;
            case 3181:
                $this->response["message"] = 'Merchantclient-ID is not matching with original transaction';
                break;
            case 3182:
                $this->response["message"] = 'Merchantclient-ID is a mandatory parameter';
                break;
            case 3184:
                $this->response["message"] = 'Original payment transaction does not exist';
                break;
            case 10028:
                $this->response["message"] = 'One or more necessary parameters are empty';
                break;
        }
        return $this->response;
    }

    /**
     * get refunded Amount
     * @return double
     */

    public function getRefundedAmount()
    {
        if (isset($this->response["refunds"])) {
            $refunds  = $this->response["refunds"];
            $refunded = 0;
            foreach ($refunds as $refund) {
                if ($refund["status"] == "SUCCESS") {
                    $refunded = $refunded + $refund["amount"];
                }
            }
            return $refunded;
        } else {
            return 0;
        }
    }
}
