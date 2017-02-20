<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="description" content="">
<meta name="author" content="">

<title>paysafecard payment Interface</title>

<link href="css/bootstrap.min.css" rel="stylesheet">
<link href="css/bootstrap-theme.min.css" rel="stylesheet">

<!-- Custom styles for this template -->
<link href="css/theme.css" rel="stylesheet">
<!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
<!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
</head>

<body role="document">

    <!-- Fixed navbar -->
    <nav class="navbar navbar-inverse navbar-fixed-top">
        <div class="container">
            <div class="navbar-header">
                <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
                    <span class="sr-only">Toggle navigation</span> <span class="icon-bar"></span> <span class="icon-bar"></span> <span class="icon-bar"></span>
                </button>
                <a class="navbar-brand" href="/">paysafecard payment Interface</a>
            </div>
            <div id="navbar" class="navbar-collapse collapse">
                <ul class="nav navbar-nav">
                    <li class="active"><a href="<?php echo getURL(); ?>">Payment</a></li>
                </ul>
            </div>
            <!--/.nav-collapse -->
        </div>
    </nav>

    <div class="container theme-showcase" role="main">

<?php

error_reporting(E_ALL);
include_once 'PaymentClass.php';
include_once "PaysafeLogger.php";

/**
 *
 * Check config.php for configuration
 *
 */

include_once "config.php";

// Set correlation ID for referencing (optional), default = ""
$correlation_id = "testCorrID_" . uniqid();

// create new Payment Controller
$pscpayment = new PaysafecardPaymentController($config['psc_key'], $config['environment']);

if ($config['logging']) {
    $logger = new PaysafeLogger();
}

//checking for actual action
if (count($_POST) > 0) {

    // Amount of this payment, i.e. "10.00"
    $amount = $_POST["amount"];

    // Currency of this payment , i.e. "EUR"
    $currency = $_POST["currency"];

    // the customer ID (merchant client id)
    $customer_id = $_POST["customer_id"];

    // the customers IP address
    $customer_ip = $_SERVER['REMOTE_ADDR'];

    // the redirect url after a successful payment, the customer will be sent to this url on success
    $success_url = getURL() . "/success.php?payment_id={payment_id}";

    // the redirect url after a failed / aborted payment, the customer will be redirected to this url on failure
    $failure_url = getURL() . "/failure.php?payment_id={payment_id}";

    // your scripts notification URL, this url is called to notify your script a payment has been processed
    $notification_url = getURL() . "/notification.php?payment_id={payment_id}";

    /*
     * // This is a sample how to use the optional parameters
     *
     * // only allow customers of a certain country, default: ""
     * $country_restriction = "DE";
     *
     * // set the minimum age of the customer, , default: ""
     * $min_age = 18;
     *
     * // only allow customers with a certain kyc level, default: ""
     * $kyc_restriction = "FULL";
     *
     * // chose the shop id to use for this payment, default: ""
     * $shop_id = 1;
     *
     * // Reporting Criteria, default = ""
     * $submerchant_id = "1";
     *
     * // create a new payment with the optional parameters, use this if you want to use the optional parameters
     * $response = $pscpayment->createPayment($amount, $currency, $customer_id, $customer_ip, $success_url, $failure_url, $notification_url, $correlation_id, $country_restriction, $kyc_restriction, $min_age, $shop_id, $submerchant_id);
     *
     */

    // creating a payment and receive the response
    $response = $pscpayment->createPayment($amount, $currency, $customer_id, $customer_ip, $success_url, $failure_url, $notification_url, $correlation_id);

    // log requests and responses to log file (may be turned off in production mode)
    if ($config['logging']) {
        $logger->log($pscpayment->getRequest(), $pscpayment->getCurl(), $pscpayment->getResponse());
    }

    // response handling
    if ($response == false) {
        $error = $pscpayment->getError();

        if ($config['debug_level'] == 0) {

            if (($error["number"] == 4003) || ($error["number"] == 3001) || ($error["number"] == "HTTP:403")) {
                echo '
                    <div class="alert alert-danger" role="alert">
                        <strong>ERROR: ' . $error["number"] . '</strong> ' . $error["message"] . '
                    </div>';
            } else {
                echo '
                    <div class="alert alert-danger" role="alert">
                        Transaction could not be initiated due to connection problems. If the problem persists, please contact our support.
                    </div>';
            }
        }

        if ($config['debug_level'] >= 1) {
            echo '
                <div class="alert alert-danger" role="alert">
                    <strong>ERROR: ' . $error["number"] . '</strong> ' . $error["message"] . '
                </div>';

        }

        if ($config['debug_level'] == 2) {
            echo '
                <div class="alert alert-warning" role="alert">
                    Request: <pre>';
            print_r($pscpayment->getRequest());
            echo '</pre>
                </div>';
            echo '
                <div class="alert alert-warning" role="alert">
                    CURL: <pre>';
            print_r($pscpayment->getCurl());
            echo '</pre>
                </div>';
            echo '
                <div class="alert alert-warning" role="alert">
                    Response: <pre>';
            print_r($pscpayment->getResponse());
            echo '</pre>
                </div>';

        }

    } else if (isset($response["object"])) {
        if (isset($response["redirect"])) {
            header("Location: " . $response["redirect"]['auth_url']);
            exit;
        }
    }
}

?>
        <div class="page-header">
            <h1>Payment</h1>
            <p>Make a payment with paysafecard <img src="https://www.paysafecard.com/fileadmin/Website/Dokumente/B2B/logo_paysafecard.jpg" height="48px" /></p>
        </div>

        <div class="row">
            <div class="col-sm-6">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title">Test Article</h3>
                    </div>
                    <div class="panel-body" >
                    Awesome Article Description
                    <hr/>
                        <form method="POST">
                            <div class="form-group">
                                <label for="amount">Amount:</label>
                                <input type="number" step="0.01" name="amount" class="form-control" value="0.10" id="amount">
                            </div>
                            <div class="form-group">
                                <label for="currency">Currency:</label>
                                <select name="currency" id="currency" class="form-control">
                                    <option selected value="">Select currency</option>
                                    <option value="EUR" selected>Euro – EUR</option>
                                    <option value="GBP">United Kingdom Pounds – GBP</option>
                                    <option value="NOK">Norway Kroner – NOK</option>
                                    <option value="RON">Romania New Lei – RON</option>
                                    <option value="SKK">Slovakia Koruny – SKK</option>
                                    <option value="TRY">Turkey New Lira – TRY</option>
                                    <option value="USD">United States Dollars – USD</option>
                                </select>
                            </div>

                            <p>The maximum payment amount is 1000 € or equivalent in other currencies</p>
                            <input type="hidden" name="customer_id" class="form-control" value="<?php echo md5('test123') ?>">
                            <br/>
                            <button type="submit" name="action" value="payment" class="btn btn-success">pay with paysafecard</button>
                        </form>
                    </div>
                </div>

            </div>
            <div class="col-sm-6">
                <p>
                    Pay prepaid online. Buy paysafecard and pay cash online.
                </p>
                <p>
                    Pay by simply entering the 16-digit paysafecard PIN or sign up for your personal my paysafecard payments account, top it up with your PINs and pay with just your username and password.
                </p>
                <p>
                    More information is available at <a href="https://www.paysafecard.com" target="_blank"> www.paysafecard.com </a>
                </p>
            </div>

        </div>

    </div>
</body>
</html>


<?php
// Helper functions

//
function getURL()
{
    $s        = empty($_SERVER["HTTPS"]) ? '' : ($_SERVER["HTTPS"] == "on") ? "s" : "";
    $sp       = strtolower($_SERVER["SERVER_PROTOCOL"]);
    $protocol = substr($sp, 0, strpos($sp, "/")) . $s;
    return $protocol . "://" . $_SERVER['SERVER_NAME'] . dirname($_SERVER['PHP_SELF']);
}
