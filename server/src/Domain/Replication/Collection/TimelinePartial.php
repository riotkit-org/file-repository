<?php declare(strict_types=1);

namespace App\Domain\Replication\Collection;

use App\Domain\Replication\Contract\CsvSerializableToStream;
use App\Domain\Replication\DTO\File;

class TimelinePartial implements CsvSerializableToStream
{
    /**
     * @var callable[] $lazyLoaders
     */
    private $lazyLoaders;

    /**
     * @var int $count
     */
    private $count;

    public function __construct(array $lazyLoaders, int $count)
    {
        $this->lazyLoaders = $lazyLoaders;
        $this->count       = $count;
    }

    public function count(): int
    {
        return \count($this->lazyLoaders);
    }

    public function outputAsJsonOnStream($stream, ?callable $onEachChunkWrite = null): callable
    {
        return function () use ($stream, $onEachChunkWrite) {
            foreach ($this->lazyLoaders as $loader) {
                /**
                 * @var File[] $files
                 */
                $files = $loader();

                foreach ($files as $file) {
                    fwrite($stream, \json_encode($file->jsonSerialize()) . "\n");
                }
            }

            if ($onEachChunkWrite) {
                $onEachChunkWrite();
            }
        };
    }
}
