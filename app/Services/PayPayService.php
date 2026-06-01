<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use PayPay\OpenPaymentAPI\Client;
use PayPay\OpenPaymentAPI\Models\CreateQrCodePayload;
use PayPay\OpenPaymentAPI\Models\OrderItem;

class PayPayService
{
    public function createQrCodePayment(int $orderId, int $amount, string $description, string $redirectUrl): array
    {
        $client = $this->createClient();

        $payload = new CreateQrCodePayload();
        $payload->setMerchantPaymentId("order_{$orderId}");
        $payload->setRequestedAt();
        $payload->setOrderDescription($description);
        $payload->setCodeType('ORDER_QR');
        $payload->setAmount(['amount' => $amount, 'currency' => 'JPY']);
        $payload->setRedirectType('WEB_LINK');
        $payload->setRedirectUrl($redirectUrl);

        $orderItem = new OrderItem();
        $orderItem->setName($description)
            ->setQuantity(1)
            ->setUnitPrice(['amount' => $amount, 'currency' => 'JPY']);

        $payload->setOrderItems([$orderItem]);

        $response = $client->code->createQRCode($payload);
        $data = $response['data'] ?? [];

        return [
            'payment_id' => $data['merchantPaymentId'] ?? "order_{$orderId}",
            'payment_url' => $data['url'] ?? $data['paymentUrl'] ?? null,
            'raw_response' => $data,
        ];
    }

    protected function createClient(): Client
    {
        $config = config('services.paypay');

        if (empty($config['api_key']) || empty($config['api_secret']) || empty($config['merchant_id'])) {
            throw new \RuntimeException('PayPay APIキー、シークレット、またはマーチャントIDが設定されていません。');
        }

        return new Client([
            'API_KEY' => $config['api_key'],
            'API_SECRET' => $config['api_secret'],
            'MERCHANT_ID' => $config['merchant_id'],
            'API_BASE_URL' => $config['api_base_url'],
        ]);
    }
}
