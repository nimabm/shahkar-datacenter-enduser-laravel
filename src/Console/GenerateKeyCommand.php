<?php

namespace Shahkar\DataCenter\Console;

use Illuminate\Console\Command;

class GenerateKeyCommand extends Command
{
    protected $signature = 'shahkar:keygen
        {--path= : Directory to write client_private.pem and client_public.pem into}
        {--force : Overwrite existing key files}';

    protected $description = 'Generate an EC P-256 (prime256v1) key pair for the Shahkar/NSCRA client';

    public function handle(): int
    {
        $key = openssl_pkey_new([
            'private_key_type' => OPENSSL_KEYTYPE_EC,
            'curve_name'       => 'prime256v1',
        ]);

        if ($key === false) {
            $this->error('Failed to generate EC key pair: ' . openssl_error_string());

            return self::FAILURE;
        }

        openssl_pkey_export($key, $privatePem);
        $publicPem = openssl_pkey_get_details($key)['key'];

        $path = $this->option('path');

        if (! $path) {
            $this->line('<info>Private key</info> (keep this secret):');
            $this->line($privatePem);
            $this->line('<info>Public key</info> (register it with NSCRA):');
            $this->line($publicPem);
            $this->newLine();
            $this->comment('Tip: run with --path=/secure/dir to write the files instead of printing.');

            return self::SUCCESS;
        }

        $path = rtrim($path, '/');

        if (! is_dir($path) && ! mkdir($path, 0700, true) && ! is_dir($path)) {
            $this->error("Unable to create directory: {$path}");

            return self::FAILURE;
        }

        $privateFile = "{$path}/client_private.pem";
        $publicFile  = "{$path}/client_public.pem";

        foreach ([$privateFile, $publicFile] as $file) {
            if (file_exists($file) && ! $this->option('force')) {
                $this->error("File already exists: {$file} (use --force to overwrite)");

                return self::FAILURE;
            }
        }

        file_put_contents($privateFile, $privatePem);
        chmod($privateFile, 0600);
        file_put_contents($publicFile, $publicPem);

        $this->info('Key pair written:');
        $this->line("  private: {$privateFile}");
        $this->line("  public:  {$publicFile}");
        $this->newLine();
        $this->comment('Set these in your .env:');
        $this->line("  SHAHKAR_CLIENT_PRIVATE_KEY={$privateFile}");
        $this->line("  SHAHKAR_CLIENT_PUBLIC_KEY={$publicFile}");

        return self::SUCCESS;
    }
}
