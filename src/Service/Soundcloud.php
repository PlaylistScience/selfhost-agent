<?php

namespace App\Service;

use Symfony\Component\Process\Process;

class Soundcloud
{
    protected $folder = 'soundcloud';

    protected $url;

    protected $minio;

    public function __construct(Minio $minio)
    {
        $this->minio = $minio;
    }

    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    public function getUrl()
    {
        return $this->url;
    }

    public function import()
    {
        $path = $this->check();
        if (false !== $path) {
            return $path;
        }

        $process = new Process("/var/www/bin/youtube-download {$this->getUrl()}");
        $process->run();
        if (!$process->isSuccessful()) {
            return false;
        }

        $file = $process->getOutput();
        $this->minio->upload($file, "{$this->folder}/{$file}");

        return str_replace('.mp3', '', $file);

        if (false === $this->check()) {
            return false;
        }
    }

    public function stream($filename)
    {
        $path = $this->check($filename);
        if (false === $path) {
            return false;
        }

        return $this->minio->stream($path);
    }

    public function fetchAll()
    {
        return $this->minio->listContents($this->folder, true);
    }

    private function check($filename = '')
    {
        $extensions = ['mp3'];
        $path = "{$this->folder}/{$filename}";
        foreach ($extensions as $extension) {
            $check = "${path}.{$extension}";
            if ($this->minio->has($check)) {
                return $check;
            }
        }

        return false;
    }
}