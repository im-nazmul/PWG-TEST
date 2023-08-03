<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Session;

class PaymentController extends Controller
{
    public function token()
    {
        $requestToken = $this->_bkash_Get_Token();
        $idToken = $requestToken['id_token'];

        Session::put('token', $idToken);

        $config = $this->_get_config_file();
        $config['token'] = $idToken;
        $newJsonString = json_encode($config);

        File::put(storage_path('app/public/config.json'), $newJsonString);

        return response()->json($idToken);
    }

    protected function _bkash_Get_Token()
    {
        $array = $this->_get_config_file();

        $post_token = array(
            'app_key' => $array["app_key"],
            'app_secret' => $array["app_secret"]
        );

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Accept'       => 'application/json',
            'username'     => $array["username"],
            'password'     => $array["password"]
        ])->post($array["tokenURL"], $post_token);

        return $response->json();
    }

    protected function _get_config_file()
    {
        $path = storage_path('app/public/config.json');
        return json_decode(File::get($path), true);
    }

    public function createpayment(Request $request)
    {
        try
        {
            $array = $this->_get_config_file();

            $amount = $request->amount;
            $invoice = $request->merchantInvoiceNumber;
            $intent = "sale";
        
            $response = Http::withHeaders([
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
                'authorization' => $array["token"],
                'x-app-key'     => $array["app_key"]
            ])->post($array["createURL"], [
                'mode'                  => '0011',
                'payerReference'        => '01858470759',
                'callbackURL'           => $request->callbackUrl,
                'amount'                => $amount,
                'currency'              => 'BDT',
                'merchantInvoiceNumber' => $invoice,
                'intent'                => $intent
            ]);

            return response()->json($response->body());
        }
        catch(Exception $ex)
        {
            return response()->json($ex->getMessage());
        }
    }

    public function executepayment(Request $request)
    {
        $array = $this->_get_config_file();

        $paymentID = $request->paymentID;

        $response = Http::withHeaders([
            'Accept'        => 'application/json',
            'authorization' => $array["token"],
            'x-app-key'     => $array["app_key"]
        ])->post($array["executeURL"], [
            "paymentID" => $paymentID
        ]);

        $this->_updateOrderStatus($response->body());

        return response()->json($response->body());
    }

    protected function _updateOrderStatus($resultdatax)
    {
        $resultdatax = json_decode($resultdatax);

        if ($resultdatax && $resultdatax->paymentID != null && $resultdatax->transactionStatus == 'Completed') {
            DB::table('orders')->where([
                'invoice' => $resultdatax->merchantInvoiceNumber
            ])->update([
                'status' => 'Processing', 'trxID' => $resultdatax->trxID
            ]);
        }
    }
}
