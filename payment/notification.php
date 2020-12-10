<?php

/*
 * this script handles the notification requests made by the paysafecard api
 * handling the requests after a payment was successful / failed
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
$logger     = new PaysafeLogger();

// checking for actual action
if (isset($_GET["payment_id"])) {
    $id = $_GET["payment_id"];
    // get payment status with retrieve Payment details
    $response = $pscpayment->retrievePayment($id);
    $logger->log($pscpayment->getRequest(), $pscpayment->getCurl(), $pscpayment->getResponse());
    if ($response == true) {
        if (isset($response["object"])) {
            if ($response["status"] == "AUTHORIZED") {
                // capture payment
                $response = $pscpayment->capturePayment($id);
                $error    = $pscpayment->getError();
                $logger->log($pscpayment->getRequest(), $pscpayment->getCurl(), $pscpayment->getResponse());
                if ($response == true) {
                    if (isset($response["object"])) {
                        if ($response["status"] == "SUCCESS") {

                            //---------------------------------------//
                            /*
                             *                Payment OK
                             *        Here you can save the Payment
                             * process your actions here (i.e. send confirmation email etc.)
                             */
                            //---------------------------------------//
                        }
                    }

                    if ($error["number"] == 2017) {
                        // Transaction already succeeded, logging response only
                        $response = $pscpayment->retrievePayment($id);
                        $logger->log($pscpayment->getRequest(), $pscpayment->getCurl(), $pscpayment->getResponse());

                    }
                }
            }
        }
    }

}
