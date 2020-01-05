<?php declare(strict_types=1);

namespace App\Controller\Replication;

use App\Controller\BaseController;
use App\Domain\Replication\ActionHandler\ServeFileHandler;
use App\Domain\Replication\Entity\Authentication\Token;
use App\Domain\Replication\Exception\AuthenticationException;
use App\Domain\Replication\Factory\SecurityContextFactory;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Swagger\Annotations as SWG;

class ServeFileController extends BaseController
{
    /**
     * @var ServeFileHandler
     */
    private $handler;

    /**
     * @var SecurityContextFactory
     */
    private $contextFactory;

    public function __construct(ServeFileHandler $handler, SecurityContextFactory $contextFactory)
    {
        $this->handler        = $handler;
        $this->contextFactory = $contextFactory;
    }

    /**
     * @SWG\Response(
     *     response="200",
     *     description="Binary file content.
     * - Raw file content, when the CURRENT TOKEN does not have set encryption.
     * - Encrypted content, when the CURRENT TOKEN have set encryption for zero-knowledge replication (replication client is not aware of replicated file contents)"
     * )
     *
     * @SWG\Response(
     *     response="403",
     *     description="When token has no replication role assigned"
     * )
     *
     * @param string $fileId
     *
     * @return Response
     * @throws AuthenticationException
     */
    public function fetchAction(string $fileId): Response
    {
        $output   = fopen('php://output', 'wb');

        /**
         * @var Token $token
         */
        $token    = $this->getLoggedUserToken(Token::class);
        $context  = $this->contextFactory->create($token);

        // act, and get response
        $response = $this->handler->handle($fileId, $output, $context);

        return new StreamedResponse(
            $response->getFlushingCallback(),
            $response->getStatusCode(),
            $response->getHeaders()
        );
    }
}