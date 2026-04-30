<?php

namespace Dominic\ExperimentSymfonyComponent\Controller;

use OneLogin\Saml2\Auth as SamlAuth;
use Symfony\Component\HttpFoundation\Response;

class SamlController
{
    private SamlAuth $samlAuth;

    public function __construct(array $samlSettings)
    {
        $this->samlAuth = new SamlAuth($samlSettings);
    }

    /**
     * SP Metadata endpoint — provide this URL to your IdP.
     */
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

    /**
     * Initiate SAML login — redirects the user to the IdP.
     */
    public function login(): Response
    {
        $ssoUrl = $this->samlAuth->login(null, [], false, false, true);

        return new Response('', 302, ['Location' => $ssoUrl]);
    }

    /**
     * ACS (Assertion Consumer Service) — handled by SamlAuthenticator.
     * This action is a fallback if the authenticator doesn't intercept.
     */
    public function acs(): Response
    {
        return new Response('SAML ACS processed', 200);
    }

    /**
     * Single Logout Service.
     */
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
