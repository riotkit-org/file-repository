<?php declare(strict_types=1);

namespace App\Domain\Storage\Repository;

use App\Domain\Storage\Entity\StagedFile;
use App\Domain\Storage\ValueObject\Filename;
use App\Domain\Storage\ValueObject\Path;
use App\Domain\Storage\ValueObject\Stream;

class StagingAreaRepository
{
    private array       $files = [];
    private string      $tempPath;

    public function __construct(string $tempPath)
    {
        $this->tempPath    = $tempPath;
        $this->files       = [];
    }

    public function keepStreamAsTemporaryFile(Stream $stream): StagedFile
    {
        if (!is_dir($this->tempPath)) {
            mkdir($this->tempPath);
        }

        $filePath = tempnam($this->tempPath, 'wolnosciowiec-file-repository-hash');

        // perform a copy to local temporary file
        $tempHandle = fopen($filePath, 'wb');
        stream_copy_to_stream($stream->attachTo(), $tempHandle);
        fclose($tempHandle);

        $path = new Path(
            \dirname($filePath),
            new Filename(\basename($filePath))
        );

        $stagedFile = new StagedFile($path);
        $this->files[] = $stagedFile;

        return $stagedFile;
    }

    public function deleteAllTemporaryFiles(): void
    {
        foreach ($this->files as $file) {
            if (!\is_file($file->getFilePath()->getValue())) {
                continue;
            }

            unlink($file->getFilePath()->getValue());
        }
    }
}
