<?php

namespace App\Service;

use Symfony\Component\Process\Process;

class Youtube
{
    protected $folder = 'youtube';

    protected $id;

    protected $minio;

    public function __construct(Minio $minio)
    {
        $this->minio = $minio;
    }

    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    public function getId()
    {
        return $this->id;
    }

    public function import()
    {
        $path = $this->check();
        if (false !== $path) {
            return $path;
        }

        $process = new Process(
            "/app/bin/youtube-download {$this->getId()}"
        );
        $process->run();

        if (!$process->isSuccessful()) {
            return false;
        }

        $file = $process->getOutput();
        $this->minio->upload($file, "{$this->folder}/{$file}");

        return $this->check();
    }

    private function check()
    {
        $extensions = ['opus', 'ogg'];
        $path = "{$this->folder}/{$this->getId()}";
        foreach ($extensions as $extension) {
            if ($this->minio->has("${path}.{$extension}")) {
                return true;
            }
        }

        return false;
    }
}