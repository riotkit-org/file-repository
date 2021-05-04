<?php declare(strict_types=1);

namespace App\Domain\Storage\Provider;

use App\Domain\Storage\Exception\FileRetrievalError;
use App\Domain\Storage\ValueObject\Stream;
use Psr\Log\LoggerInterface;

class UserUploadProvider
{
    /**
     * @var array
     */
    private array $requestHeaders;

    public function __construct(private LoggerInterface $logger, private string $webserverTemporaryStoragePath)
    {
        $this->requestHeaders = $_SERVER;
    }

    /**
     * Read user input and create a stream from it
     *
     * @return Stream
     *
     * @throws FileRetrievalError
     */
    public function getStreamFromHttp(): Stream
    {
        if ($this->hasPostedUsingLargeFileMechanism()) {
            $this->logger->debug('Handling large file upload processed by reverse proxy');

            $path = $this->webserverTemporaryStoragePath . '/' . basename($this->requestHeaders['X_INTERNAL_FILENAME']);

            if (!is_file($path)) {
                throw FileRetrievalError::fromInvalidReverseProxyUploadDirectory();
            }

            return new Stream(fopen($path, 'rb'));
        }

        if ($this->hasPostedViaPHPUploadMechanism()) {
            $this->logger->debug('Handling Multipart upload via PHP mechanism');

            $filesIndexes = \array_keys($_FILES);
            $file = $_FILES[$filesIndexes[0]];

            if ($file['error'] === 1) {
                throw FileRetrievalError::fromUploadMaxFileSizeReachedCause();
            }

            return new Stream(fopen($file['tmp_name'], 'rb'));
        }

        if ($this->hasPostedRaw()) {
            $this->logger->debug('Handling RAW POST data');

            return new Stream(fopen('php://input', 'rb'));
        }

        if ($this->isChunkedTransfer()) {
            throw FileRetrievalError::fromChunkedTransferNotSupported();
        }

        if ($this->isMultipart() && !$this->hasPostedViaPHPUploadMechanism()) {
            throw FileRetrievalError::fromPostMaxSizeReachedCause();
        }

        $this->logger->warning('The request not contained any source of file (Raw POST or Multipart)');
        $this->logger->debug(json_encode($this->requestHeaders));

        throw FileRetrievalError::fromEmptyRequestCause();
    }

    /**
     * HTTP - Large File Upload using Reverse Proxy
     * --------------------------------------------
     *
     * Reverse proxy like NGINX has to store RAW REQUEST BODY into a file, then put that file name in header "X-Internal-Filename"
     * so the Backup Repository will not have to take a big request - instead Backup Repository gets a path and can process a locally saved file.
     * After the request NGINX removes that temporary file
     *
     * https://stackoverflow.com/questions/44371643/nginx-php-failing-with-large-file-uploads-over-6-gb/44751210#44751210
     *
     * @return bool
     */
    private function hasPostedUsingLargeFileMechanism(): bool
    {
        return isset($this->requestHeaders['X_INTERNAL_FILENAME']) && $this->requestHeaders['X_INTERNAL_FILENAME'];
    }

    private function hasPostedViaPHPUploadMechanism(): bool
    {
        // could check $this->hasUserSentUrlEncodedContentType(), but will not, we can be more fault-tolerant
        return \count($_FILES) > 0;
    }

    private function hasPostedRaw(): bool
    {
        if ($this->hasUserSentUrlEncodedContentType() || $this->isMultipart()) {
            return false;
        }

        $input = fopen('php://input', 'rb');
        $part = fread($input, 512);
        fclose($input);

        return \strlen($part) > 8;
    }

    /**
     * HTTP Multipart upload: https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Content-Disposition
     *
     * @return bool
     */
    private function isMultipart(): bool
    {
        $contentType = strtolower($this->requestHeaders['CONTENT_TYPE'] ?? '');

        return str_contains($contentType, 'multipart/form-data');
    }

    private function hasUserSentUrlEncodedContentType(): bool
    {
        $headerValue = ($this->requestHeaders['CONTENT_TYPE'] ?? '');

        return \strtolower(\trim($headerValue)) === 'application/x-www-form-urlencoded';
    }

    /**
     * HTTP Chunked Transfer: https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Transfer-Encoding
     *
     * Currently not supported.
     *
     * @return bool
     */
    private function isChunkedTransfer(): bool
    {
        return $this->requestHeaders['HTTP_TRANSFER_ENCODING'] ?? '' === 'chunked' &&
            (int) ($this->requestHeaders['CONTENT_LENGTH'] ?? 0) === 0;
    }
}
