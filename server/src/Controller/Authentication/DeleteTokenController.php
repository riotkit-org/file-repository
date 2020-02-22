<?php declare(strict_types=1);

namespace App\Controller\Authentication;

use App\Controller\BaseController;
use App\Domain\Authentication\ActionHandler\TokenDeleteHandler;
use App\Domain\Authentication\Factory\Context\SecurityContextFactory;
use App\Infrastructure\Common\Http\JsonFormattedResponse;
use Exception;
use Symfony\Component\HttpFoundation\Response;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;

class DeleteTokenController extends BaseController
{
    private TokenDeleteHandler $handler;
    private SecurityContextFactory $authFactory;

    public function __construct(TokenDeleteHandler $handler, SecurityContextFactory $authFactory)
    {
        $this->handler = $handler;
        $this->authFactory = $authFactory;
    }

    /**
     * @SWG\Parameter(
     *     type="string",
     *     in="path",
     *     name="token",
     *     description="Token that needs to be deleted"
     * )
     *
     * @SWG\Response(
     *     response="201",
     *     description="Token was deleted",
     *     @SWG\Schema(
     *         type="object",
     *         @SWG\Property(
     *             property="status",
     *             type="boolean",
     *             example="true"
     *         ),
     *         @SWG\Property(
     *             property="http_code",
     *             type="integer",
     *             example="201"
     *         ),
     *         @SWG\Property(
     *             property="errors",
     *             type="array",
     *             @SWG\Items(
     *                 type="string"
     *             )
     *         ),
     *         @SWG\Property(
     *             property="message",
     *             type="string",
     *             example="Token was deleted"
     *         ),
     *         @SWG\Property(
     *             property="token",
     *             ref=@Model(type=\App\Domain\Authentication\Entity\Docs\TokenDoc::class)
     *         ),
     *          @SWG\Property(
     *             property="context",
     *             type="array",
     *             @SWG\Items(type="string")
     *         )
     *     )
     * )
     *
     * @param string $token
     *
     * @return JsonFormattedResponse
     *
     * @throws Exception
     */
    public function handle(string $token): Response
    {
        return $this->wrap(
            function () use ($token) {
                $response = $this->handler->handle(
                    $token,
                    $this->authFactory->createFromToken($this->getLoggedUserToken())
                );

                if ($response === null) {
                    return $this->createNotFoundResponse();
                }

                return new JsonFormattedResponse(
                    $response,
                    JsonFormattedResponse::HTTP_OK
                );
            }
        );
    }
}
