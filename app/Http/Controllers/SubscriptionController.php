<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Razorpay\Api\Api;
use Razorpay\Api\Errors\SignatureVerificationError;

class SubscriptionController extends Controller
{
    private function api(): Api
    {
        return new Api(config('services.razorpay.key'), config('services.razorpay.secret'));
    }

    /** Step 2: create a Razorpay order for the selected plan so the JS checkout popup can open. */
    public function create(Request $request)
    {
        $user = $request->user();

        $planType = $request->input('plan_type') === 'annual' ? 'annual' : 'monthly';
        $amountRupees = $planType === 'annual' ? User::ANNUAL_PRICE_RUPEES : User::PRO_PRICE_RUPEES;

        $order = $this->api()->order->create([
            'receipt' => 'user_'.$user->id.'_'.now()->timestamp,
            'amount' => $amountRupees * 100,
            'currency' => 'INR',
            'payment_capture' => 1,
            'notes' => ['user_id' => $user->id, 'plan_type' => $planType],
        ]);

        return response()->json([
            'order_id' => $order['id'],
            'amount' => $amountRupees * 100,
            'currency' => 'INR',
            'key' => config('services.razorpay.key'),
            'name' => 'VyaparHub',
            'description' => $planType === 'annual' ? 'Pro plan — 365 days' : 'Pro plan — 30 days',
            'theme_color' => '#E91E8C',
            'prefill' => [
                'name' => $user->business_name ?: $user->name,
                'email' => $user->email,
                'contact' => $user->mobile,
            ],
        ]);
    }

    /** Step 4: verify the checkout's payment signature, then activate Pro. */
    public function verify(Request $request)
    {
        $validated = $request->validate([
            'razorpay_payment_id' => ['required', 'string'],
            'razorpay_order_id' => ['required', 'string'],
            'razorpay_signature' => ['required', 'string'],
        ]);

        try {
            $this->api()->utility->verifyPaymentSignature($validated);
        } catch (SignatureVerificationError $e) {
            return redirect()->route('upgrade')->with('upgrade_reason', 'Payment verification failed. If money was deducted, it will be auto-refunded — please try again or contact support.');
        }

        $order = $this->api()->order->fetch($validated['razorpay_order_id']);
        $planType = ($order['notes']['plan_type'] ?? 'monthly') === 'annual' ? 'annual' : 'monthly';
        $days = $planType === 'annual' ? 365 : 30;

        $user = $request->user();
        $user->activateProPlan($days, $planType);

        // Best-effort: link a Razorpay customer record; never block the upgrade on this.
        try {
            $payment = $this->api()->payment->fetch($validated['razorpay_payment_id']);
            if (! empty($payment['customer_id'])) {
                $user->forceFill(['razorpay_customer_id' => $payment['customer_id']])->save();
            }
        } catch (\Throwable $e) {
            // ignore — plan is already active regardless of customer-id linkage
        }

        return redirect()->route('dashboard')
            ->with('status', 'You are now on Pro plan! Valid until '.$user->plan_expires_at->format('d M Y').'.');
    }

    /**
     * Step 5: server-side backup for the popup callback — Razorpay calls this directly.
     * Route is CSRF-exempt (see bootstrap/app.php) since Razorpay can't send a CSRF token.
     */
    public function webhook(Request $request)
    {
        $signature = $request->header('X-Razorpay-Signature', '');

        try {
            $this->api()->utility->verifyWebhookSignature(
                $request->getContent(),
                $signature,
                config('services.razorpay.webhook_secret')
            );
        } catch (SignatureVerificationError $e) {
            return response()->json(['status' => 'invalid signature'], 400);
        }

        $payload = $request->json()->all();

        if (($payload['event'] ?? null) === 'payment.captured') {
            $entity = $payload['payload']['payment']['entity'] ?? [];
            $notes = $entity['notes'] ?? [];
            $userId = $notes['user_id'] ?? null;
            $planType = ($notes['plan_type'] ?? 'monthly') === 'annual' ? 'annual' : 'monthly';
            $days = $planType === 'annual' ? 365 : 30;

            if ($userId && ($user = User::find($userId))) {
                // Idempotent: safe to call again even if the popup callback already activated the plan.
                $user->activateProPlan($days, $planType);
            }
        }

        return response()->json(['status' => 'ok']);
    }
}
