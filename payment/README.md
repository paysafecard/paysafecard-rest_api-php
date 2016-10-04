# paysafecard payment api PHP class & examples

## Basic procedure information
1. First create a payment with the needed information (amount, currency, success_url, ...) you will receive an auth_url to redirect your customer to. Here the customer enters his PSC Card Information and proceeds with the payment.
   On successful payment, the customer is redirect automatically to your provided success_url ( {payment_id} will be replaced with the actual payment id)

2. Meanwhile the PSC API calls your notification_url. Here you should retrieve the payment information and if the payment is AUTHORIZED capture the payment and proceed with your actions (change payment status in your system and/or send a successful payment mail to the customer)

3. If, for any reason, the notification request fails, put a fallback into your success_url script. Retrieve the payment and if the status is AUTHORIZED capture the payment and process your actions on success. As mentioned before, give positive feedback to the customer on success.

4. If the customer cancels his payment he is automatically redirected to your failure_url. You do not have to call any mehtods in your failure script, since it is only a information site for a failed/canceled payment for your customer.

## minimal basic usage

```php
// include the payment class
include_once 'class-payment.php';

// set necessary parameters
$debug = true;
$key = "psc_abcde-fg1234-5678h"; // use your own PSC key

// create a new payment controller object
$pscpayment = new PaysafecardPaymentController($key, true);

// define needed payment parameters

        // Amount of this paymen, i.e. "10.00"
        $amount = "10.00";

        // Currency of this payment , i.e. "EUR", a comprehensive list can be found here (Link to allowed currencies?)
        $currency = "EUR";

        // the customer ID
        $customer_id = md5('customer123');

        // the customers IP address
        $customer_ip = $_SERVER['REMOTE_ADDR'];

        // the redirect url after a successful payment, the customer will be sent to this url on success
        $okurl = "http://yourdomain.com/success.php?action=ok&payment={payment_id}";

        // the redirect url after a failed / aborted payment, the customer will be redirected to this url on failure
        $errorurl = "http://yourdomain.com/failure.php?payment={payment_id}";

        // your scripts notification URL, this url is called to notify your script a payment has been processed
        $notifyurl = "http://yourdomain.com/notification.php?action=notify&payment={payment_id}";

// creating a payment and receive the response
$response = $pscpayment->createPayment($amount, $currency, $customer_id, $customer_ip, $okurl, $errorurl, $notifyurl, $correlation_id);

// handle the response
if ($response == false) {
            $error = $pscpayment->getError();
            if ($debug == true) {
                echo 'ERROR: ' . $error["number"] . '</strong> ' . $error["message"];
            } else {
                if (($error["number"] == 4003) || ($error["number"] == 4003)) {
                    echo '<strong>ERROR: ' . $error["number"] . '</strong> ' . $error["message"];
                } else {
                    echo 'Transaction could not be initiated due to connection problems. If the problem persists, please contact our support.';
                }
            }
        } else if (isset($response["object"])) {
            if (isset($response["redirect"])) {
                header("Location: " . $response["redirect"]['auth_url']);
            }
        }
```

## examples and extended usage can be found within the script.
