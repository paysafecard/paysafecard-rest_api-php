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
                    <li class="active"><a href="<?php echo getURL(); ?>/payout-frontend.php">Payout Front</a></li>
                    <li class=""><a href="<?php echo getURL(); ?>/payout-backend.php">Payout Backend</a></li>
                </ul>
            </div>
            <!--/.nav-collapse -->
        </div>
    </nav>

    <div class="container theme-showcase" role="main">

<?php
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

        // response handling
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

?>
        <div class="page-header">
            <h1>Payout customer frontend</h1>
            <p>Payout your winnings quickly and simply with my paysafecard now!</p>
        </div>

        <div class="row">
            <div class="col-sm-6">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title">Payout with my paysafecard</h3>

                    </div>
                    <div class="panel-body" >
                        <form method="POST">
                        <input type="hidden" name="currency" class="form-control" value="EUR">
                        <input type="hidden" name="merchantclientid" class="form-control" value="434186408713">
                                <div class="form-group">
                                    <label for="customer_mail">my paysafecard account mail address:</label><br/>
                                    <input type="text" name="customer_mail" class="form-control" value="VrAtTRLRyS@avUVWdRVeH.NYE" id="customer_mail">
                                </div>
                                <div class="form-group">
                                    <label for="amount">Amount:</label><br/>
                                    <input type="text" name="amount" class="form-control" value="10.00" id="amount">
                                </div>
                                <div class="form-group">
                                    <label for="first_name">First Name:</label><br/>
                                    <input type="text" name="first_name" class="form-control" id="first_name" value="Test">
                                </div>
                                <div class="form-group">
                                    <label for="last_name">Last Name:</label><br/>
                                    <input type="text" name="last_name" class="form-control" id="last_name" value="BubxNFGHwdGCElzbmjxsycWdYX">
                                </div>
                                <div class="form-group">
                                    <label for="birthday">Date of Birth:</label><br/>
                                    <input type="date" name="birthday" class="form-control" id="birthday" value="1986-06-16">
                                </div>
                            <button type="submit" name="action" value="makePayout" class="btn btn-success">Request a payout</button>

                        </form>
                    </div>
                </div>

            </div>
            <div class="col-sm-6">
            <h3>Payouts made easy:</h3>
            <p>my paysafecard lets you complete your top-ups and payouts quickly and simply!</p>
            <p><a href="http://www.paysafecard.com/payout" target="_blank" >Sign up now</a> for your free my paysafecard account, which includes a complete overview of your transactions.</p>
            <p>No bank account or credit card needed. Enjoy the benefit of quick and simple payouts!</p>
            </div>

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

function printError($pscClass, $debugLevel)
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
                    <strong>ERROR: ' . $error["number"] . '</strong> ' . $error["message"] . '
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
                    <p>Thank you for your request!</p>
                    <p>Payout ID: ' . $response["id"] . '</p>
                    <p>We are processing the payout of ' . $response["amount"] . ' ' . $response["currency"] . ' EUR to your my paysafecard account.</p>
                    <p>This can take up to XX working days.</p>
                    <p>If you have any questions in the meantime please contact our support: <a href="mailto:support@company.com">support@company.com </a></p>
                </div>';

    }

    if ($debugLevel >= 1) {
        echo '
                <div class="alert alert-success" role="alert">
                    <p>Thank you for your request!</p>
                    <p>Payout ID: ' . $response["id"] . '</p>
                    <p>We are processing the payout of ' . $response["amount"] . ' ' . $response["currency"] . ' EUR to your my paysafecard account.</p>
                    <p>This can take up to XX working days.</p>
                    <p>If you have any questions in the meantime please contact our support: <a href="mailto:support@company.com">support@company.com </a></p>
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
