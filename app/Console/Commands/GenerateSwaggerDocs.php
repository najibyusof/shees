<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use OpenApi\Generator;

class GenerateSwaggerDocs extends Command
{
    protected $signature = 'swagger:generate';
    protected $description = 'Generate Swagger/OpenAPI documentation';

    public function handle()
    {
        try {
            $this->info('Generating OpenAPI documentation...');

            $openApiSpec = Generator::scan([
                base_path('app'),
            ]);

            $storagePath = storage_path('api-docs');

            if (!is_dir($storagePath)) {
                mkdir($storagePath, 0755, true);
            }

            // Write JSON
            file_put_contents(
                $storagePath . '/api-docs.json',
                json_encode($openApiSpec, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
            );

            // Write YAML
            file_put_contents(
                $storagePath . '/api-docs.yaml',
                $openApiSpec->toYaml()
            );

            $this->info('✓ OpenAPI documentation generated successfully!');
            $this->info('JSON: storage/api-docs/api-docs.json');
            $this->info('YAML: storage/api-docs/api-docs.yaml');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Error generating documentation: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
