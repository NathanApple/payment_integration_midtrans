<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Transaction;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Ramsey\Uuid\Uuid;

class PaymentController extends Controller
{
    //
    public function create(Request $request){
        try{
            $uuid = Uuid::uuid4()->toString();
            $product_id = $request->product_id;
            $quantity = $request->quantity;
            $user = Auth::user();

            $transaction = new Transaction();

            $transaction->user_id= $user->id;
            $transaction->product_id= $product_id;
            $transaction->quantity = $quantity;
            $transaction->payment_id= $uuid;
            $transaction->status =  "Process";
            
            
            $product = Product::findOrFail($product_id);
            $total_price = $product->price * $quantity;

            $transaction->total =  $total_price;

            $transaction->save();
        

            $params = array(
                'transaction_details' => array(
                    'order_id' => $uuid,
                    'gross_amount' => $total_price,
                )
            );
        } catch (Exception $e){
            return response()->json([
                "status_message" => "Error, Parameter is incorrect or does not exist",
                "status_code" => "404"], 404);
        }
        
        try {
            // Get Snap Payment Page URL
            $paymentUrl = \Midtrans\Snap::createTransaction($params)->redirect_url;
          
            // Redirect to Snap Payment Page
            return response()->json([
                                    'id'=>$uuid,
                                    'url' => $paymentUrl,
                                    'price' => $total_price,
                                    'product' => $product->name,
                                    'username' => $user->name]);
        //   header('Location: ' . $paymentUrl);
        }
        catch (Exception $e) {
          echo $e->getMessage();
        }
    }

    public function check_status(Request $request){
        try{
            $status = \Midtrans\Transaction::status($request->uuid);
        } catch (Exception $e){
            return response()->json(["status" => [
                    "status_message" => "Error, Trasanction is not finished",
                    "status_code" => "404"]]);
        }

        // $status = $request->uuid;
        return response()->json(compact('status'));
    }
    
    public function notification(){
        $notif = new \Midtrans\Notification();

        $transaction = $notif->transaction_status;
        $fraud = $notif->fraud_status;
        $order_id = $notif->order_id;
        error_log("Order ID $order_id: "."transaction status = $transaction, fraud staus = $fraud");
        Log::debug("Order ID $order_id: "."transaction status = $transaction, fraud staus = $fraud");
        if ($transaction == 'capture') {
            if ($fraud == 'challenge') {
            // TODO Set payment status in merchant's database to 'challenge'
            Transaction::where('payment_id', $order_id)
                    ->update(['status' => 'challenge']);
            }
            else if ($fraud == 'accept') {
            // TODO Set payment status in merchant's database to 'success'
            Transaction::where('payment_id', $order_id)
            ->update(['status' => 'success']);
            }
        }
        else if ($transaction == 'cancel') {
            if ($fraud == 'challenge') {
            // TODO Set payment status in merchant's database to 'failure'
            Transaction::where('payment_id', $order_id)
            ->update(['status' => 'failure']);
            }
            else if ($fraud == 'accept') {
            // TODO Set payment status in merchant's database to 'failure'
            Transaction::where('payment_id', $order_id)
            ->update(['status' => 'failure']);
            }
        }
        else if ($transaction == 'deny') {
            // TODO Set payment status in merchant's database to 'failure'
            Transaction::where('payment_id', $order_id)
            ->update(['status' => 'failure']);
        } 
        else if ($transaction == 'pending') {
            Transaction::where('payment_id', $order_id)
            ->update(['status' => 'pending']);
        }
        else if ($transaction == 'settlement') {
            Transaction::where('payment_id', $order_id)
            ->update(['status' => 'settlement']);
        }  
    }

    public function test(){
        $uuid = Uuid::uuid4();
        $product = Product::findOrFail(1);
        $currentUser = Auth::user();
        
        Transaction::where('payment_id', '123')
            ->update(['status' => 'success']);

        return response()->json(["uuid" => $uuid->toString(),
                                "product" => $product->price,
                                "user" => $currentUser]);
    }
}
