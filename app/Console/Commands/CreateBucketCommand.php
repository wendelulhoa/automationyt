<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Aws\S3\S3Client;

class CreateBucketCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'create-bucket-minio';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cria um novo bucket no MinIO';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->createBucket();
        return 0;
    }

    /**
     * Cria um novo bucket no MinIO
     *
     * @return void
     */
    public function createBucket()
    {
         $s3Client = new S3Client([
              'version' => 'latest',
              'region'  => env('AWS_DEFAULT_REGION'),
              'endpoint' => env('AWS_ENDPOINT'),
              'use_path_style_endpoint' => true,
              'credentials' => [
                   'key'    => env('AWS_ACCESS_KEY_ID'),
                   'secret' => env('AWS_SECRET_ACCESS_KEY'),
              ],
         ]);

         $bucket = env('AWS_BUCKET');
         if (!$s3Client->doesBucketExist($bucket)) {
              $s3Client->createBucket(['Bucket' => $bucket]);
              $s3Client->waitUntil('BucketExists', ['Bucket' => $bucket]);
         }
    }
}
