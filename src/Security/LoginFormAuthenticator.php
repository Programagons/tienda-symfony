<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class LoginFormAuthenticator extends AbstractLoginFormAuthenticator
{
    // Permite recordar la URL a la que el usuario quería acceder antes del login
    use TargetPathTrait;

    // Nombre de la ruta del formulario de login
    public const LOGIN_ROUTE = 'app_login';

    // Inyección del generador de URLs
    public function __construct(private UrlGeneratorInterface $urlGenerator)
    {
    }

    // Se ejecuta cuando el usuario envía el formulario de login
    public function authenticate(Request $request): Passport
    {
        // Obtiene el valor del campo "login" (username o email)
        $login = $request->getPayload()->getString('login');

        // Guarda el último usuario introducido para reutilizarlo en el formulario
        $request->getSession()->set(
            SecurityRequestAttributes::LAST_USERNAME,
            $login
        );

        // Devuelve un Passport con:
        // - El usuario a autenticar
        // - Las credenciales (contraseña)
        // - Protección CSRF
        return new Passport(
            new UserBadge($login),
            new PasswordCredentials(
                $request->getPayload()->getString('password')
            ),
            [
                new CsrfTokenBadge(
                    'authenticate',
                    $request->getPayload()->getString('_csrf_token')
                ),
            ]
        );
    }

    // Se ejecuta cuando la autenticación es correcta
    public function onAuthenticationSuccess(
        Request $request,
        TokenInterface $token,
        string $firewallName
    ): ?Response {
        // Si el usuario intentó acceder a una página protegida antes del login,
        // se le redirige a esa URL
        if ($targetPath = $this->getTargetPath(
            $request->getSession(),
            $firewallName
        )) {
            return new RedirectResponse($targetPath);
        }

        // Si no hay una URL previa, se redirige a una ruta por defecto
        return new RedirectResponse(
            $this->urlGenerator->generate('mostrar_categorias')
        );
    }

    // Devuelve la URL del formulario de login
    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}