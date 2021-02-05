<?php

namespace App\Security;

use App\Controller\OAuthController;
use App\Entity\User\User;
use App\Service\OAuthService;
use League\OAuth2\Server\ResourceServer;
use Symfony\Bridge\PsrHttpMessage\Factory\DiactorosFactory;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;
use Symfony\Contracts\Translation\TranslatorInterface;

class OAuthAuthenticator extends AbstractGuardAuthenticator
{
    /**
     * @var ResourceServer
     */
    private $server;

    private $translator;

    public function __construct(OAuthService $service)
    {
        $this->server = $service->getValidator();
    }

    public function supports(Request $request)
    {
        return (($request->headers->has("Authorization") && strpos($request->headers->get("Authorization"), "Bearer") === 0) || $request->query->has("access_token"));
    }

    public function getCredentials(Request $request)
    {
        $factory = new DiactorosFactory();
        return $factory->createRequest($request);
    }

    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        try {
            $auth = $this->server->validateAuthenticatedRequest($credentials);
            $id = $auth->getAttribute("oauth_user_id");
            /** @var User $user */
            $user = $userProvider->loadUserByUsername($id);
            $user->isOAuth = true;
            return $user;
        } catch (\Exception $e) {
            return null;
        }

    }

    public function checkCredentials($credentials, UserInterface $user)
    {
        /** @var User $user */
        if(!$user->isEnabled()) {
            throw new AuthenticationException("账户被禁，原因：在公开场合发表令人不适的言论或图片。请不要尝试使用他人账户，否则会被连带处理。");
        }
        return true;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        return new JsonResponse(array("code" => 400, "message" => $exception->getMessage()), 400);
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        return;
    }

    public function start(Request $request, AuthenticationException $authException = null)
    {
        // todo
    }

    public function supportsRememberMe()
    {
        return false;
    }
}
