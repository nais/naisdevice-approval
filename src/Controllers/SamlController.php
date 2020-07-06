<?php declare(strict_types=1);
namespace Nais\Device\Approval\Controllers;

use Nais\Device\Approval\SamlResponseValidator;
use Nais\Device\Approval\Session;
use Nais\Device\Approval\Session\User;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use DOMDocument;
use DOMXPath;
use InvalidArgumentException;

class SamlController {
    private Session $session;
    private SamlResponseValidator $validator;
    private string $logoutUrl;

    public function __construct(Session $session, SamlResponseValidator $validator, string $logoutUrl) {
        $this->session   = $session;
        $this->validator = $validator;
        $this->logoutUrl = $logoutUrl;
    }

    public function acs(Request $request, Response $response) : Response {
        if ($this->session->hasUser()) {
            $response->getBody()->write('User has already been authenticated');
            return $response->withStatus(400);
        }

        /** @var array{SAMLResponse: ?string} */
        $params = $request->getParsedBody();

        if (empty($params['SAMLResponse'])) {
            $response->getBody()->write('Missing SAML response');
            return $response->withStatus(400);
        }

        $xml = base64_decode($params['SAMLResponse'], true);

        if (false === $xml) {
            $response->getBody()->write('Value is not properly base64 encoded');
            return $response->withStatus(400);
        }

        if (true !== $this->validator->validate($xml)) {
            $response->getBody()->write('Invalid SAML response');
            return $response->withStatus(400);
        }

        try {
            $user = $this->getUserFromSamlResponse($xml);
        } catch (InvalidArgumentException $e) {
            $response->getBody()->write($e->getMessage());
            return $response->withStatus(400);
        }

        $this->session->setUser($user);

        return $response
            ->withHeader('Location', '/')
            ->withStatus(302);
    }

    /**
     * Get a user instance based on the SAML response
     *
     * @param string $xml
     * @throws InvalidArgumentException
     * @return User
     */
    private function getUserFromSamlResponse(string $xml) : User {
        $document = new DOMDocument();

        if (false === $document->loadXML($xml, LIBXML_NOERROR)) {
            throw new InvalidArgumentException('Unable to load XML');
        }

        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('a', 'urn:oasis:names:tc:SAML:2.0:assertion');

        $objectId = (string) $xpath->evaluate('string(//a:Attribute[@Name="http://schemas.microsoft.com/identity/claims/objectidentifier"]/a:AttributeValue)');

        if (empty($objectId)) {
            throw new InvalidArgumentException('Missing objectidentifier claim');
        }

        $givenName = (string) $xpath->evaluate('string(//a:Attribute[@Name="http://schemas.xmlsoap.org/ws/2005/05/identity/claims/givenname"]/a:AttributeValue)');

        if (empty($givenName)) {
            throw new InvalidArgumentException('Missing givenName claim');
        }

        return new User($objectId, $givenName);
    }

    public function logout(Request $request, Response $response) : Response {
        $this->session->destroy();

        return $response
            ->withHeader('Location', $this->logoutUrl)
            ->withStatus(302);
    }
}