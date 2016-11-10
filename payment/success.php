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
                <button type="button" class="navbar-toggle collapsed"
                    data-toggle="collapse" data-target="#navbar" aria-expanded="false"
                    aria-controls="navbar">
                    <span class="sr-only">Toggle navigation</span> <span
                        class="icon-bar"></span> <span class="icon-bar"></span> <span
                        class="icon-bar"></span>
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

/*
 * This page is shown to the customer after a successful payment
 */

error_reporting(E_ALL);
include_once 'PaymentClass.php';
include_once "PaysafeLogger.php";

/**
 *
 * Check config.php for configuration
 *
 */

include_once "config.php";

// create new Payment Controller
$pscpayment = new PaysafecardPaymentController($config['psc_key'], $config['environment']);
if ($config['logging']) {
    $logger = new PaysafeLogger();
}

// checking for actual action
if (isset($_GET["payment_id"])) {
    $id = $_GET["payment_id"];
    // get the current payment informations
    $response = $pscpayment->retrievePayment($id);
    if ($config['logging']) {
        $logger->log($pscpayment->getRequest(), $pscpayment->getCurl(), $pscpayment->getResponse());
    }

    if ($response == false) {
        // retrieving the payment failed
        printError($pscpayment, $config['debug_level'], $id);
    } else if (isset($response["object"])) {
        if ($response["status"] == "SUCCESS") {
            // transaction was successful, show customer a positive feedback. Do NOT process any actions here.
            printSucess($response, $pscpayment, $config['debug_level']);
        } else if ($response["status"] == "AUTHORIZED") {
            // capture payment
            $response = $pscpayment->capturePayment($id);
            if ($config['logging']) {
                $logger->log($pscpayment->getRequest(), $pscpayment->getCurl(), $pscpayment->getResponse());
            }

            if ($response == false) {
                printError($pscpayment, $config['debug_level'], $id);
            } else if (isset($response["object"])) {
                if ($response["status"] == "SUCCESS") {
                    //---------------------------------------//
                    /*
                     *                Payment OK
                     *        Here you can save the Payment
                     * process your actions here (i.e. send confirmation email etc.)
                     *  This is a fallback to notification.php
                     */
                    //---------------------------------------//
                    printSucess($response, $pscpayment, $config['debug_level']);
                } else {
                    if ($error["number"] == 2017) {
                        // check with retrieve Payment details if the payment is successful or not
                        $response = $pscpayment->retrievePayment($id);
                        if ($config['logging']) {
                            $logger->log($pscpayment->getRequest(), $pscpayment->getCurl(), $pscpayment->getResponse());
                        }

                        if (isset($response["status"])) {

                            if ($response["status"] == "SUCCESS") {
                                // transaction was successful, show customer a positive feedback. Do NOT process any actions here.
                                printSucess($response, $pscpayment, $config['debug_level']);

                            } else {
                                printError($pscpayment, $config['debug_level'], $id);
                            }
                        } else {
                            printError($pscpayment, $config['debug_level'], $id);
                        }
                    }
                }
            }
        }
    }
}
?>

    </div>
</body>
</html>


<?php
// helper functions

function getURL()
{
    $s        = empty($_SERVER["HTTPS"]) ? '' : ($_SERVER["HTTPS"] == "on") ? "s" : "";
    $sp       = strtolower($_SERVER["SERVER_PROTOCOL"]);
    $protocol = substr($sp, 0, strpos($sp, "/")) . $s;
    return $protocol . "://" . $_SERVER['SERVER_NAME'] . dirname($_SERVER['PHP_SELF']);
}

// Print errors

function printError($pscClass, $debugLevel, $id)
{
    $error = $pscClass->getError();
    if ($debugLevel == 0) {
        echo '
                <div class="alert alert-danger" role="alert">
                    <strong>ERROR
                </div>';

    }

    if ($debugLevel >= 1) {
        echo '
                <div class="alert alert-danger" role="alert">
                    <p><strong>ERROR: ' . $error["number"] . '</strong> ' . $error["message"] . '</p>
                    <hr/>
                    <p>The transaction could not be completed.</p>
                    <p>This may have happened due to a temporary connection problem.</p>
                    <p>Please press the "reload" button in your browser or the link below to reload this page to retry completing your transaction.</p>
                    <p> <a href="#" onClick="window.location.reload()">Click here to reload</a> - If the problem persists, the money reserved money will be returned after a certain time to your PIN or account. </p>
                    <p>Payment ID:<b> ' . $id . ' </b></p>
                </div>';
    }
    if ($debugLevel == 2) {
        echo '
                <div class="alert alert-warning" role="alert">
                    Request: <pre>';
        print_r($pscClass->getRequest());
        echo '</pre>
                </div>';
        echo '
                <div class="alert alert-warning" role="alert">
                    CURL: <pre>';
        print_r($pscClass->getCurl());
        echo '</pre>
                </div>';
        echo '
                <div class="alert alert-warning" role="alert">
                    Response: <pre>';
        print_r($pscClass->getResponse());
        echo '</pre>
                </div>';
    }
}

function printSucess($response, $pscClass, $debugLevel)
{
    if ($debugLevel == 0) {
        echo '
                        <div class="alert alert-success" role="alert">
                            The transaction with the amount of <b>' . $response['amount'] . ' ' . $response['currency'] . '</b> was successful. Payment ID: <b>' . $response['id'] . '</b>
                        </div>';
    }

    if ($debugLevel >= 1) {
        echo '
                        <div class="alert alert-success" role="alert">
                            The transaction with the amount of <b>' . $response['amount'] . ' ' . $response['currency'] . '</b> was successful. Payment ID: <b>' . $response['id'] . '</b>
                        </div>';
    }
    if ($debugLevel == 2) {
        echo '
                <div class="alert alert-warning" role="alert">
                    Request: <pre>';
        print_r($pscClass->getRequest());
        echo '</pre>
                </div>';
        echo '
                <div class="alert alert-warning" role="alert">
                    CURL: <pre>';
        print_r($pscClass->getCurl());
        echo '</pre>
                </div>';
        echo '
                <div class="alert alert-warning" role="alert">
                    Response: <pre>';
        print_r($pscClass->getResponse());
        echo '</pre>
                </div>';
    }

}

?>
