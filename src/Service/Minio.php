<?php

namespace App\Service;

use Aws\S3\S3Client;

use League\Flysystem\AwsS3v3\AwsS3Adapter;
use League\Flysystem\Filesystem;

class Minio
{
    /**
     * @var \Aws\S3\S3Client
     */
    protected $client;

    /**
     * @var \League\Flysystem\AwsS3v3\AwsS3Adapter
     */
    protected $adapter;

    /**
     * @var \League\Flysystem\Filesystem
     */
    protected $filesystem;

    /**
     * Temporary storage location for downloads
     * @var string
     */
    private $path = '/var/www/var/tmp/';

    public function __construct($endpoint, $key, $bucket, $secret)
    {
        $this->client = new S3Client([
            'version' => 'latest',
            'region'  => 'us-east-1',
            'endpoint' => $endpoint,
            'use_path_style_endpoint' => true,
            'credentials' => [
                'key'    => $key,
                'secret' => $secret,
            ],
            'http'    => [
                'connect_timeout' => 5
            ]
        ]);

        $this->adapter = new AwsS3Adapter($this->client, $bucket, '');
        $this->filesystem = new Filesystem($this->adapter);
    }

    public function has($path)
    {
        return $this->filesystem->has($path);
    }

    public function listContents($path, $recursive = false)
    {
        return $this->filesystem->listContents($path, $recursive);
    }

    public function upload($file, $dest)
    {
        $file = trim(preg_replace('/\s+/', ' ', $file));
        $stream = fopen("{$this->path}{$file}", 'r+');
        $this->filesystem->writeStream($dest, $stream);
        fclose($stream);
        unlink("{$this->path}{$file}");

        return true;
    }

    public function stream($path)
    {
        return $this->filesystem->readStream($path);
    }
}