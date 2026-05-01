<?php

namespace Dominic\ExperimentSymfonyComponent\Security;

use OneLogin\Saml2\Auth as SamlAuth;
use OneLogin\Saml2\Error as SamlError;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class SamlAuthenticator extends AbstractAuthenticator
{
    private array $samlSettings;

    public function __construct(array $samlSettings)
    {
        $this->samlSettings = $samlSettings;
    }

    public function supports(Request $request): ?bool
    {
        // Only authenticate on the ACS (Assertion Consumer Service) endpoint
        return $request->getPathInfo() === '/saml/acs' && $request->isMethod('POST');
    }

    public function authenticate(Request $request): Passport
    {
        $samlAuth = new SamlAuth($this->samlSettings);
        $samlAuth->processResponse();

        $errors = $samlAuth->getErrors();
        if (!empty($errors)) {
            $reason = $samlAuth->getLastErrorReason() ?: implode(', ', $errors);
            throw new CustomUserMessageAuthenticationException('SAML Error: ' . $reason);
        }

        if (!$samlAuth->isAuthenticated()) {
            throw new CustomUserMessageAuthenticationException('SAML authentication failed.');
        }

        $nameId = $samlAuth->getNameId();
        $attributes = $samlAuth->getAttributes();

        // Store SAML attributes on the request for downstream use
        $request->attributes->set('_saml_attributes', $attributes);
        $request->attributes->set('_saml_name_id', $nameId);

        return new SelfValidatingPassport(new UserBadge($nameId));
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // Redirect to dashboard after successful SAML login
        return new RedirectResponse('/admin/dashboard');
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new JsonResponse([
            'error' => strtr($exception->getMessageKey(), $exception->getMessageData()),
        ], Response::HTTP_UNAUTHORIZED);
    }

    public function getSamlAuth(): SamlAuth
    {
        return new SamlAuth($this->samlSettings);
    }
}
