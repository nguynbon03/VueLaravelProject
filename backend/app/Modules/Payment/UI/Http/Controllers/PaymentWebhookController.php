<?php

declare(strict_types=1);

namespace App\Modules\Payment\UI\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Payment\Application\Actions\ProcessPaymentWebhookAction;
use App\Modules\Payment\Application\Actions\SimulateDevWebhookAction;
use App\Modules\Payment\Application\DTOs\PaymentWebhookCommand;
use App\Modules\Payment\Domain\Exceptions\InvalidWebhookSignatureException;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PaymentWebhookController extends Controller
{
    public function __construct(
        private readonly ProcessPaymentWebhookAction $processWebhookAction,
        private readonly SimulateDevWebhookAction $simulateWebhookAction,
    ) {}

    public function handle(Request $request, string $provider): JsonResponse
    {
        $normalizedProvider = strtolower(trim($provider));
        if (! in_array($normalizedProvider, ['sepay', 'stripe'], true)) {
            return new JsonResponse(['message' => 'Nhà cung cấp thanh toán không được hỗ trợ.'], 404);
        }

        $signature = (string) $request->header('X-Webhook-Signature', '');
        $rawPayload = (string) $request->getContent();

        try {
            $secret = (string) config('services.webhooks.' . $normalizedProvider, '');
            if ($signature === '' || $secret === '' || ! hash_equals(hash_hmac('sha256', $rawPayload, $secret), strtolower(trim(str_starts_with(strtolower(trim($signature)), 'sha256=') ? substr(trim($signature), 7) : trim($signature))))) {
                throw new InvalidWebhookSignatureException();
            }

            $validated = $request->validate([
                'event_id' => ['required', 'string', 'max:191'],
                'order_number' => ['required', 'string', 'max:64'],
                'amount_minor' => ['required', 'integer', 'min:0'],
                'transaction_ref' => ['required', 'string', 'max:191'],
            ]);

            $result = $this->processWebhookAction->execute(new PaymentWebhookCommand(
                provider: $normalizedProvider,
                rawPayload: $rawPayload,
                signature: $signature,
                eventId: (string) $validated['event_id'],
                orderNumber: (string) $validated['order_number'],
                amountMinor: (int) $validated['amount_minor'],
                transactionRef: (string) $validated['transaction_ref'],
                decodedPayload: $validated,
            ));
        } catch (InvalidWebhookSignatureException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 401);
        } catch (DomainException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 422);
        }

        return new JsonResponse(['data' => $result], 200);
    }

    public function simulateDev(Request $request): JsonResponse
    {
        if (app()->environment('production')) {
            return new JsonResponse(['message' => 'Endpoint giả lập chỉ khả dụng ở môi trường phát triển/kiểm thử.'], 403);
        }

        $validated = $request->validate([
            'order_number' => ['required', 'string', 'max:64'],
        ]);

        try {
            $result = $this->simulateWebhookAction->execute((string) $validated['order_number']);
        } catch (DomainException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 404);
        }

        return new JsonResponse(['data' => $result], 200);
    }
}
