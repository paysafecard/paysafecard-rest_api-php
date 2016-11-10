error_reporting(E_ALL);
include_once 'PayoutClass.php';
include_once "JsonDB.php";
include_once "PaysafeLogger.php";

/**
 *
 * Check config.php for configuration
 *
 */

include_once "config.php";

// Set correlation ID for referencing (optional), default = ""
$correlation_id = "testCorrID_" . uniqid();

// create new Payout Controller
$pscpayout = new PaysafecardPayoutController($config['psc_key'], $config['environment']);

if ($config['logging']) {
    $logger = new PaysafeLogger();
}

//checking for actual action
if (isset($_POST["action"])) {
    $action = $_POST["action"];
    if ($action == "makePayout") {

        // payout amount
        $amount = $_POST["amount"];
        // payout currency
        $currency = $_POST["currency"];
        // merchant client id
        $merchantclientid = $_POST["merchantclientid"];
        // customer mail (psc)
        $customer_mail = $_POST["customer_mail"];
        // first name of customer
        $first_name = $_POST["first_name"];
        // last name of customer
        $last_name = $_POST["last_name"];
        // birthday of customer
        $birthday = $_POST["birthday"];
        // ip of customer
        $customer_ip = $_SERVER['REMOTE_ADDR'];

        // validate / request the payout
        $response = $pscpayout->validatePayout($amount, $currency, $merchantclientid, $customer_mail, $customer_ip, $first_name, $last_name, $birthday, $correlation_id);
        if ($config['logging']) {
            $logger->log($pscpayout->getRequest(), $pscpayout->getCurl(), $pscpayout->getResponse());
        }

        // reponse handling
        if ($response == false) {
            $error = $pscpayout->getError();
            printError($pscpayout, $config['debug_level']);
        } else if (isset($response["object"])) {
            if ($response["status"] == "VALIDATION_SUCCESSFUL") {
                printSucess($response, $pscpayout, $config['debug_level']);
                $db     = new JsonDB();
                $result = $db->insert(
                    "payouts",
                    [
                        'id'                => $response["id"],
                        'amount'            => $amount,
                        'currency'          => $currency,
                        'merchantclientid'  => $merchantclientid,
                        'customer_mail'     => $customer_mail,
                        'first_name'        => $first_name,
                        'last_name'         => $last_name,
                        'birthday'          => $birthday,
                        'requested_at'      => date("d.m.Y H:i:s"),
                        'customer_amount'   => $response['customer_amount'],
                        'customer_currency' => $response['customer_currency'],
                    ]
                );
            } else {
                $error = $pscpayout->getError();
                printError($pscpayout, $config['debug_level']);
            }
        }
    }

}