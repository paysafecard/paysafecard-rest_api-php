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