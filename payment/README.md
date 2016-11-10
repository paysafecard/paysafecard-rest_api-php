# Paysafecard payment api PHP class & examples
## currently developed under: psc.hosting-core.de

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
