<?php declare(strict_types=1);

namespace Nais\Device\Approval\Controllers;

use Nais\Device\Approval\Session;
use Nais\Device\Approval\Session\User;
use NAVIT\AzureAd\ApiClient;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use RuntimeException;
use Slim\Views\Twig;

#[CoversClass(IndexController::class)]
class IndexControllerTest extends TestCase
{
    public function testRedirectsOnMissingUser(): void
    {
        $controller = new IndexController(
            $this->createMock(ApiClient::class),
            $this->createMock(Twig::class),
            $this->createConfiguredMock(Session::class, [
                'getUser' => null,
            ]),
            'loginurl',
            'entityid',
            'access-group'
        );

        $locationResponse = $this->createMock(Response::class);
        $locationResponse
            ->expects($this->once())
            ->method('withStatus')
            ->with(302)
            ->willReturn($this->createMock(Response::class));

        $response = $this->createMock(Response::class);
        $response
            ->expects($this->once())
            ->method('withHeader')
            ->with('Location', $this->callback(fn (string $url): bool => 0 === strpos($url, 'loginurl?SAMLRequest=')))
            ->willReturn($locationResponse);

        $controller->index(
            $this->createMock(Request::class),
            $response
        );
    }

    public function testThrowsExceptionWhenUnableToGetUserGroups(): void
    {
        $apiClient = $this->createMock(ApiClient::class);
        $apiClient
            ->expects($this->once())
            ->method('getUserGroups')
            ->with('user-id')
            ->willThrowException(new RuntimeException('Some error occurred', 400));

        $controller = new IndexController(
            $apiClient,
            $this->createMock(Twig::class),
            $this->createConfiguredMock(Session::class, [
                'getUser' => $this->createConfiguredMock(User::class, [
                    'getObjectId' => 'user-id',
                ]),
            ]),
            'loginurl',
            'entityid',
            'access-group'
        );

        $this->expectExceptionObject(new RuntimeException('Unable to fetch user groups', 400));
        $controller->index(
            $this->createMock(Request::class),
            $this->createMock(Response::class)
        );
    }

    /**
     * @return array<string,array{0:array<array{id:string}>,1:string,2:bool}>
     */
    public static function getUserGroups(): array
    {
        return [
            'no groups' => [
                [],
                'access-group',
                false,
            ],
            'has approval group' => [
                [
                    ['id' => 'group-1'],
                    ['id' => 'access-group'],
                    ['id' => 'group-2'],
                ],
                'access-group',
                true,
            ],
            'does not have approval group' => [
                [
                    ['id' => 'group-1'],
                    ['id' => 'group-2'],
                ],
                'access-group',
                false,
            ],
        ];
    }

    /**
     * @dataProvider getUserGroups
     * @param array<array{id:string}> $groups
     * @param string $accessGroup
     * @param bool $hasAccepted
     */
    #[DataProvider('getUserGroups')]
    public function testCanRenderViewWithCorrectVariables(array $groups, string $accessGroup, bool $hasAccepted): void
    {
        $apiClient = $this->createMock(ApiClient::class);
        $apiClient
            ->expects($this->once())
            ->method('getUserGroups')
            ->with('user-id')
            ->willReturn($groups);

        $user = $this->createConfiguredMock(User::class, ['getObjectId' => 'user-id']);

        $token = '';

        $session = $this->createConfiguredMock(Session::class, ['getUser' => $user]);
        $session
            ->expects($this->once())
            ->method('setPostToken')
            ->with($this->callback(function (string $value) use (&$token): bool {
                $token = $value;

                // Always return true as the type hint above does the actual expectation for us,
                // and we simply want to capture the value for the incoming token
                return true;
            }));

        $view = $this->createMock(Twig::class);
        $view
            ->expects($this->once())
            ->method('render')
            ->with(
                $this->isInstanceOf(Response::class),
                'index.html',
                [
                    'user'        => $user,
                    'hasAccepted' => $hasAccepted,
                    'token'       => &$token,
                ]
            );

        $controller = new IndexController(
            $apiClient,
            $view,
            $session,
            'loginurl',
            'entityid',
            $accessGroup
        );

        $controller->index(
            $this->createMock(Request::class),
            $this->createMock(Response::class)
        );
    }
}
