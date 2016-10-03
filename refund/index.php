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
                <a class="navbar-brand" href="<?php echo getURL(); ?>">paysafecard payment Interface</a>
            </div>
            <div id="navbar" class="navbar-collapse collapse">
                <ul class="nav navbar-nav">
                    <li class="active"><a href="<?php echo getURL(); ?>">Refund</a></li>
                </ul>
            </div>
            <!--/.nav-collapse -->
        </div>
    </nav>

    <div class="container theme-showcase" role="main">

<?php
error_reporting(E_ALL);
include_once 'RefundClass.php';
include_once "PaysafeLogger.php";

/**
 *
 * Check config.php for configuration
 *
 */

include_once "config.php";

// Set correlation ID for referencing (optional), default = ""
$correlation_id = "testCorrID_" . uniqid();

// create new Refund Controller
$pscrefund = new PaysafecardRefundController($config['psc_key'], $config['environment']);
if ($config['logging']) {
    $logger = new PaysafeLogger();
}

//checking for actual action
if (isset($_POST["action"])) {
    $action = $_POST["action"];
    if ($action == "getDetail") {
        // get payment details
        $paymentDetail = $pscrefund->getPaymentDetail($_POST["payment_id"]);
        $refunded      = $pscrefund->getRefundedAmount();
        // get refund amount
        // payment detail handling

        if ($paymentDetail == false || isset($paymentDetail['number'])) {
            printError($pscrefund, $config['debug_level']);
        } else if (isset($paymentDetail["object"])) {
            if ($paymentDetail["status"] == "SUCCESS") {
                // successful got details
                printSucess($paymentDetail, $pscrefund, $config['debug_level'], "retrieve");
            } elseif ($paymentDetail["status"] == "REDIRECTED") {
                // successful got details, but is in invalid state -> no refund can be processed
                printSucess($paymentDetail, $pscrefund, $config['debug_level'], "redirected");
            } else {
                printError($pscrefund, $config['debug_level']);
            }
        }

    }
    if ($action == "validation") {

        // the payment id to refund
        $payment_id = $_POST["payment_id"];
        // clientid to refund to
        $clientid = $_POST["clientid"];
        // refund amount
        $amount = $_POST["amount"];
        // refund currency
        $currency = $_POST["currency"];
        // customer mail (psc)
        $customer_mail = $_POST["customer_mail"];
        // ip of customer
        $customer_ip = $_SERVER['REMOTE_ADDR'];

        // validate / request refund
        $response = $pscrefund->validateRefund($payment_id, $amount, $currency, $clientid, $customer_mail, $customer_ip, $correlation_id);
        if ($config['logging']) {
            $logger->log($pscrefund->getRequest(), $pscrefund->getCurl(), $pscrefund->getResponse());
        }

        // response handling
        if ($response == false || isset($response['number'])) {
            printError($pscrefund, $config['debug_level']);
        } else if (isset($response["object"])) {
            if ($response["status"] == "VALIDATION_SUCCESSFUL") {
                echo '
                <div class="alert alert-success" role="alert">
                    <strong>VALIDATION_SUCCESSFUL :</strong> ' . $response["id"] . '
                </div>';

                //---------------------------------------//
                /*
                 *                Validation OK
                 *        Here you can save the Validation
                 */
                //---------------------------------------//

            } else {
                printError($pscrefund, $config['debug_level']);
            }
        }
    }
    if ($action == "validationExecute") {

        // payment id for refund
        $payment_id = $_POST["payment_id"];
        // refund id
        $refund_id = $_POST["refund_id"];
        // refund client id
        $clientid = $_POST["clientid"];
        // refund amount
        $amount = $_POST["amount"];
        // refund currency
        $currency = $_POST["currency"];

        // customer mail (psc)
        $customer_mail = $_POST["customer_mail"];
        // ip fo customer
        $customer_ip = $_SERVER['REMOTE_ADDR'];

        // execute Refund
        $response = $pscrefund->executeRefund($payment_id, $refund_id, $amount, $currency, $clientid, $customer_mail, $customer_ip, $correlation_id);
        if ($config['logging']) {
            $logger->log($pscrefund->getRequest(), $pscrefund->getCurl(), $pscrefund->getResponse());
        }

        // response handling
        if ($response == false || isset($response['number'])) {
            printError($pscrefund, $config['debug_level']);
        } else if (isset($response["object"])) {
            if ($response["status"] == "SUCCESS") {
                printSucess($response, $pscrefund, $config['debug_level'], "refund");
                //---------------------------------------//
                /*
                 *                Refund OK
                 *        Here you can save the Refund
                 */
                //---------------------------------------//
            } else {
                printError($pscrefund, $config['debug_level']);
            }
        }
    }
    if ($action == "directRefund") {

        // refund payment id
        $payment_id = $_POST["payment_id"];
        // refund amount
        $amount = $_POST["amount"];
        // refund currency
        $currency = $_POST["currency"];
        // refund client id
        $clientid = $_POST["clientid"];
        // customer mail (psc)
        $customer_mail = $_POST["customer_mail"];
        // ip of customer
        $customer_ip = $_SERVER['REMOTE_ADDR'];

        // direct refund
        $response = $pscrefund->directRefund($payment_id, $amount, $currency, $clientid, $customer_mail, $customer_ip, $correlation_id);
        if ($config['logging']) {
            $logger->log($pscrefund->getRequest(), $pscrefund->getCurl(), $pscrefund->getResponse());
        }

        //response handling
        if ($response == false || isset($response['number'])) {
            printError($pscrefund, $config['debug_level']);
        } else if (isset($response["object"])) {
            if ($response["status"] == "SUCCESS") {
                printSucess($response, $pscrefund, $config['debug_level'], "directrefund");
                //---------------------------------------//
                /*
                 *                Refund OK
                 *        Here you can save the Refund
                 */
                //---------------------------------------//
            } else {
                printError($pscrefund, $config['debug_level']);
            }
        }

    }
}

?>
        <div class="page-header">
            <h1>Refund</h1>
        </div>

        <div class="row">
            <div class="col-sm-4">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title">Zahlung abrufen</h3>
                    </div>
                    <div class="panel-body" >
                        <form class="navbar-form navbar-right" method="POST">
                            <div class="form-group">
<?php if (isset($payment_id)) {
    $payment_id_value = $payment_id;
} else {
    $payment_id_value = ""; // empty if not set
}?>


                                <input type="hidden" name="action" value="getDetail" class="form-control"> <input type="text" name="payment_id" class="form-control" value="<?php echo $payment_id_value; ?>">
                            </div>
                            <button type="submit" class="btn btn-success">get Details</button>
                        </form>
                    </div>
                </div>

            </div>
            <?php

//Show Refund Dialog only if no payment is shown or there is some amount to refund.

if (isset($paymentDetail['object'])) {
    if ($paymentDetail["status"] == "SUCCESS" && ($paymentDetail["amount"] - $refunded) > 0) {

        ?>
            <div class="col-sm-4">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title">make Refund</h3>
                    </div>
                    <div class="panel-body">
                        <form class="navbar-form navbar-right" method="POST">

                            <div class="form-group" style="padding: 10px">
                                <input type="hidden" name="payment_id" class="form-control" value="<?php if (isset($paymentDetail) == true) {echo $paymentDetail["id"];}?>">
                                <table>
                                    <tr>
                                        <td>Amount:</td>
                                        <td><input type="text" name="amount" class="form-control" value="<?php if (isset($paymentDetail) == true) {echo $paymentDetail["amount"] - $refunded;}?>"></td>
                                    </tr>
                                    <tr>
                                        <td>Currency:</td>
                                        <td><input type="text" name="currency" class="form-control" value="<?php if (isset($paymentDetail) == true) {echo $paymentDetail["currency"];}?>"></td>
                                    </tr>
                                    <tr>
                                        <td>MyPaysafe Account:</td>
                                        <td><input type="text" name="customer_mail" class="form-control" value="psc.mypins+matwal_blFxgFUJfbNS@gmail.com"></td>
                                    </tr>
                                    <tr>
                                        <td>Client ID:</td>
                                        <td><input type="text" name="clientid" class="form-control" value="<?php if (isset($paymentDetail) == true) {echo $paymentDetail["customer"]["id"];}?>"></td>
                                    </tr>
                                </table>
                            </div>
                            <button type="submit" name="action" value="validation" class="btn btn-success">validation</button>
                            <button type="submit" name="action" value="directRefund" class="btn btn-success">directRefund</button>
                        </form>
                    </div>
                </div>

            </div>
            <?php }

}?>
        </div>

        <?php
//print all information about the Payment
if (isset($paymentDetail["object"]) == "PAYMENT") {
    ?>
        <div class="row">

        <div class="page-header">
                <h1>Payment Details</h1>
                <table class="table table-striped">
                    <tr>
                        <th>ID</th>
                        <td><?php echo $paymentDetail["id"]; ?></td>
                    </tr>
                    <tr>
                        <th>created</th>
                        <td><?php echo date("d.m.y - H:i", $paymentDetail["created"]); ?></td>
                    </tr>
                    <tr>
                        <th>updated</th>
                        <td><?php echo date("d.m.y - H:i", $paymentDetail["updated"]); ?></td>
                    </tr>
                    <tr>
                        <th>amount</th>
                        <td><?php echo number_format($paymentDetail["amount"], $decimals = 2, $dec_point = ",", $thousands_sep = ".") . " " . $paymentDetail["currency"] ?></td>
                    </tr>
                    <tr>
                        <th>status</th>
                        <td><?php echo $paymentDetail["status"]; ?></td>
                    </tr>
                    <tr>
                        <th>type</th>
                        <td> <?php echo $paymentDetail["type"]; ?></td>
                    </tr>
                    <tr>
                        <th>refunded:</th>
                        <td> <?php echo number_format($refunded, $decimals = 2, $dec_point = ",", $thousands_sep = ".") . " " . $paymentDetail["currency"] ?></td>
                    </tr>
                </table>

            </div>
            <?php
}
//print all information about the Payment
if (isset($paymentDetail["refunds"]) == true) {
    ?>


            <div class="page-header">
                <h1>Refunds</h1>
            </div>

            <div class="col-md-6">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Refund ID</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                            <?php
//print all refunds for this payment.
    $i = 1;
    if (isset($paymentDetail["refunds"])) {
        $refunds = $paymentDetail["refunds"];
        foreach ($refunds as $refund) {
            echo "<tr>";
            echo "<td>" . $i . "</td>";
            echo "<td>" . $refund["id"] . "</td>";
            echo "<td>" . number_format($refund["amount"], $decimals = 2, $dec_point = ",", $thousands_sep = ".") . "</td>";
            echo "<td>" . $refund["status"] . "</td>";
            if ($refund["status"] == "VALIDATION_SUCCESSFUL") {
                //Prepare Form for validation execution.
                echo '<form method="POST">
                                            <input type="hidden" name="payment_id" value="' . $paymentDetail["id"] . '">
                                            <input type="hidden" name="refund_id" value="' . $refund["id"] . '">
                                            <input type="hidden" name="amount" value="' . $refund["amount"] . '">
                                            <input type="hidden" name="currency" value="' . $paymentDetail["currency"] . '">
                                            <input type="hidden" name="clientid" value="' . $refund["customer"]["id"] . '">
                                            <input type="hidden" name="customer_mail" value="' . $paymentDetail["id"] . '">';
                echo '<td><button type="submit" name="action" value="validationExecute" class="btn btn-success">make Refund</button></td>';
                echo '</form>';
            } else {
                echo "<td></td>";
            }

            echo "</tr>";
            $i++;
        }
    }

    ?>
                    </tbody>
                </table>
            </div>

        <?php }?>

      </div>

    </div>
    <!-- /container -->
</body>
</html>
<?php
// Helper functions

// get the current URL
function getURL()
{
    $s        = empty($_SERVER["HTTPS"]) ? '' : ($_SERVER["HTTPS"] == "on") ? "s" : "";
    $sp       = strtolower($_SERVER["SERVER_PROTOCOL"]);
    $protocol = substr($sp, 0, strpos($sp, "/")) . $s;
    return $protocol . "://" . $_SERVER['SERVER_NAME'] . dirname($_SERVER['PHP_SELF']);
}

// Print errors

function printError($pscrefund, $debugLevel)
{
    $error = $pscrefund->getError();
    if ($debugLevel == 0) {

        echo '
                <div class="alert alert-danger" role="alert">
                    <strong>ERROR
                </div>';

    }

    if ($debugLevel >= 1) {

        echo '
                <div class="alert alert-danger" role="alert">
                    <strong>ERROR: ' . $error["number"] . '</strong> ' . $error["message"] . '
                </div>';

    }
    if ($debugLevel == 2) {
        echo '
                <div class="alert alert-warning" role="alert">
                    Request: <pre>';
        print_r($pscrefund->getRequest());
        echo '</pre>
                </div>';
        echo '
                <div class="alert alert-warning" role="alert">
                    CURL: <pre>';
        print_r($pscrefund->getCurl());
        echo '</pre>
                </div>';
        echo '
                <div class="alert alert-warning" role="alert">
                    Response: <pre>';
        print_r($pscrefund->getResponse());
        echo '</pre>
                </div>';
    }
}

function printSucess($response, $pscrefund, $debugLevel, $type)
{
    if ($debugLevel == 0) {
        if ($type == "retrieve") {
            echo '
                <div class="alert alert-success" role="alert">
                    <strong>Retrieve payment details successful.
                </div>';
        } elseif ($type == "redirected") {
            // this is a successful API Request, yet the refund can NOT be processed due to a invalid state
            echo '
                <div class="alert alert-warning" role="alert">
                    <p>Payment is in an invalid state.</p>
                    <p>No refund possible.</p>
                </div>';
        } else {
            echo '
                <div class="alert alert-success" role="alert">
                    <strong>The refund with the amount of ' . $response['amount'] . ' ' . $response['currency'] . ' was successful.
                </div>';
        }
    }

    if ($debugLevel >= 1) {
        if ($type == "retrieve") {
            echo '
                <div class="alert alert-success" role="alert">
                    <strong>Retrieve payment details successful.
                </div>';
        } elseif ($type == "redirected") {
            // this is a successful API Request, yet the refund can NOT be processed due to a invalid state
            echo '
                <div class="alert alert-danger" role="alert">
                    <p>Payment is in an invalid state.</p>
                    <p>No refund possible.</p>
                </div>';
        } else {
            echo '
                <div class="alert alert-success" role="alert">
                    <strong>The refund with the amount of ' . $response['amount'] . ' ' . $response['currency'] . ' was successful.
                </div>';
        }
    }
    if ($debugLevel == 2) {
        echo '
                <div class="alert alert-warning" role="alert">
                    Request: <pre>';
        print_r($pscrefund->getRequest());
        echo '</pre>
                </div>';
        echo '
                <div class="alert alert-warning" role="alert">
                    CURL: <pre>';
        print_r($pscrefund->getCurl());
        echo '</pre>
                </div>';
        echo '
                <div class="alert alert-warning" role="alert">
                    Response: <pre>';
        print_r($pscrefund->getResponse());
        echo '</pre>
                </div>';
    }

}

?>