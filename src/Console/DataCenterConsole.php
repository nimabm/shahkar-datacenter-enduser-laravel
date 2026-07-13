<?php

namespace Shahkar\DataCenter\Console;

use Illuminate\Console\Command;
use Shahkar\DataCenter\Contracts\DataCenterApiInterface;
use Shahkar\DataCenter\Http\Responses\ApiResponse;
use Shahkar\DataCenter\Support\ServiceTemplates;
use Throwable;

/**
 * Interactive console for working with the NSCRA data-center service by hand,
 * mirroring the menu-driven sample_python/main.py. Useful for admins testing
 * the web service from the CLI.
 */
class DataCenterConsole extends Command
{
    protected $signature = 'shahkar:datacenter';

    protected $description = 'Interactive console for the Shahkar/NSCRA data-center web service';

    private DataCenterApiInterface $api;

    public function handle(DataCenterApiInterface $api): int
    {
        $this->api = $api;

        $options = [
            'Generate client key pair',
            'Register public key (2-step OTP)',
            'Register a service (put)',
            'Update a service',
            'Delete a service',
            'Check status by tracking id',
            'Exit',
        ];

        while (true) {
            $this->newLine();
            $choice = $this->choice('Select an action', $options, array_key_last($options));

            try {
                match ($choice) {
                    'Generate client key pair'          => $this->call('shahkar:keygen'),
                    'Register public key (2-step OTP)'  => $this->registerKeyFlow(),
                    'Register a service (put)'          => $this->sendFlow('put'),
                    'Update a service'                  => $this->sendFlow('update'),
                    'Delete a service'                  => $this->sendFlow('delete'),
                    'Check status by tracking id'       => $this->statusFlow(),
                    'Exit'                              => null,
                };
            } catch (Throwable $e) {
                $this->error(class_basename($e) . ': ' . $e->getMessage());
            }

            if ($choice === 'Exit') {
                return self::SUCCESS;
            }
        }
    }

    private function registerKeyFlow(): void
    {
        $this->info('Step 1: requesting an OTP for key registration...');
        $this->render($this->api->registerKey());

        if ($this->confirm('Enter the OTP to confirm key registration now?', true)) {
            $otp = (string) $this->ask('OTP');
            $this->info('Step 2: confirming key registration...');
            $this->render($this->api->registerKey($otp));
        }
    }

    private function sendFlow(string $action): void
    {
        $customer = null;
        $service  = null;

        if ($action !== 'delete') {
            $customer = $this->choice('Customer type', ServiceTemplates::CUSTOMERS, 'real');
            $service  = $this->choice('Service type', ServiceTemplates::SERVICES, 'shared');
        }

        $payload = $this->buildPayload($action, $customer, $service);

        if ($action === 'delete') {
            $payload['id'] = (string) $this->ask('Service id (id)', $payload['id']);
        }

        $this->line('Payload to send:');
        $this->line($this->pretty($payload));

        if (! $this->confirm('Send this request (step 1)?', true)) {
            return;
        }

        $response  = $this->api->sendRaw($action, $payload);
        $this->render($response);

        $payload['requestId'] = $response->requestId;
        $trackingId           = $response->getTrackingId();

        // Two-step OTP applies to put/update, not delete.
        if ($action !== 'delete' && $this->confirm('Enter OTP and send step 2 now?', true)) {
            $payload['otp'] = (int) $this->ask('OTP');

            if ($customer === 'legal') {
                $payload['agentOtp'] = (int) $this->ask('Agent OTP');
            }

            $response2  = $this->api->sendRaw($action, $payload);
            $this->render($response2);
            $trackingId = $response2->getTrackingId() ?? $trackingId;
        }

        if ($trackingId && $this->confirm("Poll status for tracking id {$trackingId} now?", true)) {
            $this->render($this->api->checkStatus($trackingId));
        }
    }

    private function statusFlow(): void
    {
        $trackingId = (string) $this->ask('Tracking id');

        if ($trackingId !== '') {
            $this->render($this->api->checkStatus($trackingId));
        }
    }

    /**
     * @return array<string,mixed>
     */
    private function buildPayload(string $action, ?string $customer, ?string $service): array
    {
        $template = ServiceTemplates::for($action, $customer, $service);

        $source = $this->choice(
            'Payload source',
            ['Use sample template', 'Load from JSON file'],
            'Use sample template'
        );

        if ($source === 'Load from JSON file') {
            $path = (string) $this->ask('Path to JSON file');
            $json = is_file($path) ? file_get_contents($path) : false;

            if ($json === false) {
                $this->error("Cannot read file: {$path}. Falling back to the sample template.");

                return $template;
            }

            $decoded = json_decode($json, true);

            if (! is_array($decoded)) {
                $this->error('Invalid JSON. Falling back to the sample template.');

                return $template;
            }

            return $decoded;
        }

        return $template;
    }

    private function render(ApiResponse $response): void
    {
        $this->line(sprintf(
            'HTTP %d  success=%s',
            $response->statusCode,
            $response->success ? 'true' : 'false'
        ));

        if ($response->requestId) {
            $this->line("requestId:  {$response->requestId}");
        }

        if ($response->getTrackingId()) {
            $this->line("trackingId: {$response->getTrackingId()}");
        }

        $this->line('body:');
        $this->line($this->pretty($response->body));

        if ($response->getDecrypted() !== null) {
            $this->line('decrypted:');
            $this->line($this->pretty($response->getDecrypted()));
        }
    }

    /**
     * @param  array<mixed> $data
     */
    private function pretty(array $data): string
    {
        return (string) json_encode(
            $data,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );
    }
}
