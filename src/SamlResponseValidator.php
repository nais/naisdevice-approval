<?php declare(strict_types=1);

namespace Nais\Device\Approval;

use OneLogin\Saml2\Utils as SamlUtils;

/**
 * @codeCoverageIgnore
 */
class SamlResponseValidator
{
    private string $certificate;

    /**
     * Class constructor
     *
     * @param string $certificate
     */
    public function __construct(string $certificate)
    {
        $this->certificate = $certificate;
    }

    /**
     * Validate the response
     *
     * @param string $responseXml The complete SAML XML response
     * @return bool
     */
    public function validate(string $responseXml): bool
    {
        return SamlUtils::validateSign($responseXml, $this->certificate);
    }
}
