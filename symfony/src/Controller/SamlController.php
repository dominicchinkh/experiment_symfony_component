<?php

namespace Dominic\ExperimentSymfonyComponent\Controller;

use OneLogin\Saml2\Auth as SamlAuth;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SamlController
{
    private SamlAuth $samlAuth;

    public function __construct(array $samlSettings)
    {
        $this->samlAuth = new SamlAuth($samlSettings);
    }

    #[Route('/saml/metadata', name: 'saml_metadata')]
    public function metadata(): Response
    {
        $settings = $this->samlAuth->getSettings();
        $metadata = $settings->getSPMetadata();
        $errors = $settings->validateMetadata($metadata);

        if (!empty($errors)) {
            return new Response(
                'Metadata validation errors: ' . implode(', ', $errors),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        return new Response($metadata, 200, [
            'Content-Type' => 'text/xml',
        ]);
    }

    #[Route('/saml/login', name: 'saml_login')]
    public function login(): Response
    {
        $ssoUrl = $this->samlAuth->login(null, [], false, false, true);

        return new Response('', 302, ['Location' => $ssoUrl]);
    }

    #[Route('/saml/acs', name: 'saml_acs', methods: ['POST'])]
    public function acs(): Response
    {
        return new Response('SAML ACS processed', 200);
    }

    #[Route('/saml/sls', name: 'saml_sls')]
    public function sls(): Response
    {
        $this->samlAuth->processSLO();

        $errors = $this->samlAuth->getErrors();
        if (!empty($errors)) {
            return new Response(
                'SLO Error: ' . implode(', ', $errors),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        return new Response('', 302, ['Location' => '/']);
    }
}
