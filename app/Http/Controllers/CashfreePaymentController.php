<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CashfreePaymentController extends Controller
{
    public function create(Request $request)
    {
        return view('payment-create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|min:3',
            'email' => 'required',
            'mobile' => 'required',
            'amount' => 'required|numeric'
        ]);

        $url = "https://sandbox.cashfree.com/pg/orders";

        $headers = [
            "Content-Type: application/json",
            "x-api-version: 2022-01-01",
            "x-client-id: " . env('CASHFREE_API_KEY'),
            "x-client-secret: " . env('CASHFREE_API_SECRET')
        ];

        $data = [
            'order_id' => 'order_' . rand(1111111111, 9999999999),
            'order_amount' => $validated['amount'],
            "order_currency" => "INR",
            "customer_details" => [
                "customer_id" => 'customer_' . rand(111111111, 999999999),
                "customer_name" => $validated['name'],
                "customer_email" => $validated['email'],
                "customer_phone" => $validated['mobile'],
            ],
            "order_meta" => [
                "return_url" => 'http://127.0.0.1:8000/cashfree/payments/success/?order_id={order_id}&order_token={order_token}'
            ]
        ];

        $curl = curl_init($url);

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));

        $resp = curl_exec($curl);

        return $resp;

        if (curl_errno($curl)) {
            return response()->json(['error' => curl_error($curl)], 500);
        }

        curl_close($curl);

        return redirect()->to(json_decode($resp)->payment_link);
    }

    public function success(Request $request)
    {
        $orderId = $request->query('order_id');

        if (!$orderId) {
            return response()->json(['error' => 'Order ID is required'], 400);
        }

        $url = "https://sandbox.cashfree.com/pg/orders/$orderId";

        $headers = [
            'accept: application/json',
            'x-api-version: 2023-08-01',
            'x-client-id: ' . env('CASHFREE_API_KEY'),
            'x-client-secret: ' . env('CASHFREE_API_SECRET'),
        ];

        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
        ]);

        $response = curl_exec($curl);

        if (curl_errno($curl)) {
            return response()->json(['error' => curl_error($curl)], 500);
        }

        curl_close($curl);

        $data = json_decode($response, true);

        return $data;

        if ($data['order_status'] === 'PAID') {
            return "Succses";
        }
        else{
            return "failed";
        }
    }
}
