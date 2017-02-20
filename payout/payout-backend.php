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
                    <li class=""><a href="<?php echo getURL(); ?>/payout-frontend.php">Payout Front</a></li>
                    <li class="active"><a href="<?php echo getURL(); ?>/payout-backend.php">Payout Backend</a></li>
                </ul>
            </div>
            <!--/.nav-collapse -->
        </div>
    </nav>

    <div class="container theme-showcase" role="main">

<?php

/*
 * This files handles the currently open payout requests
 */

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

//checking for actual action, handling action and errors
if (isset($_POST["action"])) {
    $action = $_POST["action"];
    if ($action == "getLimit") {
        $response = $pscpayout->getLimits("EUR");
        if ($config['logging']) {
            $logger->log($pscpayout->getRequest(), $pscpayout->getCurl(), $pscpayout->getResponse());
        }
        if ($response == false) {
            printError($pscpayout, $config['debug_level']);
        } else if (isset($response["object"])) {
            if ($response["status"] == "SUCCESS") {
                echo '
                    <div class="alert alert-success" role="alert">
                        <strong>SUCCESS Requesting Limits:</strong> ' . $response["id"] . '
                    </div>';
            } else {
                printError($pscpayout, $config['debug_level']);
            }
        }
    }
    if ($action == "decline") {
        $payout_id = $_POST["payout_id"];
        $db        = new JsonDB();
        $db->delete("payouts", 'id', $payout_id);
    }

    if ($action == "executePayout") {
        // execute a payout

        // current payout_ID
        $payout_id = $_POST["payout_id"];
        // payout amount
        $amount = $_POST["amount"];
        // payout currency
        $currency = $_POST["currency"];
        // merchant client id
        $merchantclientid = $_POST["merchantclientid"];
        // custumers mail (psc)
        $customer_mail = $_POST["customer_mail"];
        // first name of customer
        $first_name = $_POST["first_name"];
        // last name of customer
        $last_name = $_POST["last_name"];
        // birthday of customer
        $birthday = $_POST["birthday"];
        // ip address of customer
        $customer_ip = $_SERVER['REMOTE_ADDR'];

        // execute payout
        $response = $pscpayout->executePayout($payout_id, $amount, $currency, $merchantclientid, $customer_mail, $customer_ip, $first_name, $last_name, $birthday, $correlation_id);
        if ($config['logging']) {
            $logger->log($pscpayout->getRequest(), $pscpayout->getCurl(), $pscpayout->getResponse());
        }

        // handling response
        if ($response == false) {
            $error = $pscpayout->getError();
            printError($pscpayout, $config['debug_level']);
        } else if (isset($response["object"])) {
            if ($response["status"] == "SUCCESS") {
                printSucess($response, $pscpayout, $config['debug_level'], "payout");
                $db = new JsonDB();
                $db->delete("payouts", 'id', $response["id"]);
            } else {
                printError($pscpayout, $config['debug_level']);
            }
        }
    }

    if ($action == "makePayout") {
        // make/validate a payout

        // payout amount
        $amount = $_POST["amount"];
        // payout currency
        $currency = $_POST["currency"];
        // merchant client id
        $merchantclientid = $_POST["merchantclientid"];
        // customers mail (psc)
        $customer_mail = $_POST["customer_mail"];
        // first name of customer
        $first_name = $_POST["first_name"];
        // last name of customer
        $last_name = $_POST["last_name"];
        // birthday name of customer
        $birthday = $_POST["birthday"];
        // ip name of customer
        $customer_ip = $_SERVER['REMOTE_ADDR'];

        // validating payout
        $response = $pscpayout->validatePayout($amount, $currency, $merchantclientid, $customer_mail, $customer_ip, $first_name, $last_name, $birthday, $correlation_id);
        if ($config['logging']) {
            $logger->log($pscpayout->getRequest(), $pscpayout->getCurl(), $pscpayout->getResponse());
        }

        // handling response
        if ($response == false) {
            $error = $pscpayout->getError();
            printError($pscpayout, $config['debug_level']);
        } else if (isset($response["object"])) {
            if ($response["status"] == "VALIDATION_SUCCESSFUL") {
                printSucess($response, $pscpayout, $config['debug_level'], "validate");
                $db     = new JsonDB();
                $result = $db->insert("payouts",
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
            <h1>Payout backoffice tool</h1>
        </div>

        <div class="row">
            <div class="col-sm-6">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title">Create a new payout with my paysafecard</h3>
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
                            <button type="submit" name="action" value="makePayout" class="btn btn-success">Request a new payout</button>
                        </form>
                    </div>
                </div>

            </div>

        </div>
        <hr/>
</div>

    <div class="container-fluid" role="main">
             <div class="col-md-12">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Payout ID</th>
                            <th>Requested</th>
                            <th>PSC mail</th>
                            <th>Name</th>
                            <th>Currency</th>
                            <th>Amount</th>
                            <th>Customer Currency</th>
                            <th>Customer Amount</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <h3>Validated payout requests</h3> <hr>
<?php
//Drawing all Refunds for this payment.
$i       = 1;
$db      = new JsonDB();
$payouts = $db->selectAll("payouts");
if (count($payouts) > 0) {
    foreach ($payouts as $payout) {
        echo "<tr>";
        echo "<td>" . $i . "</td>";
        echo "<td>" . $payout["id"] . "</td>";
        echo "<td>" . $payout["requested_at"] . "</td>";
        echo "<td>" . $payout['customer_mail'] . "</td>";
        echo "<td>" . $payout['first_name'] . " " . $payout['last_name'] . "</td>";
        echo "<td>" . $payout['currency'] . "</td>";
        echo "<td>" . number_format($payout["amount"], $decimals = 2, $dec_point = ",", $thousands_sep = ".") . "</td>";
        echo "<td>" . $payout['customer_currency'] . "</td>";
        echo "<td>" . number_format($payout["customer_amount"], $decimals = 2, $dec_point = ",", $thousands_sep = ".") . "</td>";
        echo '<form method="POST">
                <input type="hidden" name="payout_id" value="' . $payout["id"] . '">
                <input type="hidden" name="amount" value="' . $payout["amount"] . '">
                <input type="hidden" name="currency" value="' . $payout["currency"] . '">
                <input type="hidden" name="merchantclientid" value="' . $payout["merchantclientid"] . '">
                <input type="hidden" name="customer_mail" value="' . $payout["customer_mail"] . '">
                <input type="hidden" name="first_name" value="' . $payout["first_name"] . '">
                <input type="hidden" name="last_name" value="' . $payout["last_name"] . '">
                <input type="hidden" name="birthday" value="' . $payout["birthday"] . '">';
        echo '<td><button type="submit" name="action" value="executePayout" class="btn btn-success">make Payout</button>';
        echo '</form>';
        echo '<form method="POST">
                                                                              <input type="hidden" name="payout_id" value="' . $payout["id"] . '">';
        echo '<br><button type="submit" name="action" value="decline" class="btn btn-danger">decline</button></td>';
        echo '</form>';
        echo "</tr>";
        $i++;
    }
}

?>
                    </tbody>
                </table>
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

function printSucess($response, $pscClass, $debugLevel, $type)
{
    if ($debugLevel == 0) {
        if ($type == "validate") {
            echo '
                <div class="alert alert-success" role="alert">
                    Payout validation successful! Payout ID: ' . $response["id"] . '
                </div>';
        } elseif ($type == "payout") {
            echo '
                <div class="alert alert-success" role="alert">
                    <p>The payout request was successful!</p>
                    <p>PayoutID: ' . $response["id"] . '</p>
                    <p>The amount of ' . $response["amount"] . ' ' . $response["currency"] . ' was transferred to the my paysafecard account ' . $response["customer"]["email"] . '
                </div>';
        }

    }

    if ($debugLevel >= 1) {
        if ($type == "validate") {
            echo '
                <div class="alert alert-success" role="alert">
                    Payout validation successful! Payout ID: ' . $response["id"] . '
                </div>';
        } elseif ($type == "payout") {
            echo '
                <div class="alert alert-success" role="alert">
                    <p>The payout request was successful!</p>
                    <p>PayoutID: ' . $response["id"] . '</p>
                    <p>The amount of ' . $response["amount"] . ' ' . $response["currency"] . ' was transferred to the my paysafecard account ' . $response["customer"]["email"] . '
                </div>';
        }
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
