<?php

/*
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace spec\Dunglas\AngularCsrfBundle\EventListener;

use Dunglas\AngularCsrfBundle\Csrf\AngularCsrfTokenManager;
use Dunglas\AngularCsrfBundle\Routing\RouteMatcherInterface;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Csrf\CsrfToken;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class AngularCsrfCookieListenerSpec extends ObjectBehavior
{
    const COOKIE_NAME = 'cookie';
    const COOKIE_EXPIRE = 0;
    const COOKIE_PATH = '/';
    const COOKIE_DOMAIN = 'example.com';
    const COOKIE_SECURE = true;
    const TOKEN_VALUE = 'token';

    private $routes = array('^/punk', '^/rock$');
    private $secureRequest;
    private $unsecureRequest;

    public function let(
        AngularCsrfTokenManager $tokenManager,
        RouteMatcherInterface $routeMatcher,
        Request $secureRequest,
        Request $unsecureRequest,
        CsrfToken $token
    ) {
        $token->getValue()->willReturn(self::TOKEN_VALUE);
        $tokenManager->getToken()->willReturn($token);

        $this->secureRequest = $secureRequest;
        $this->unsecureRequest = $unsecureRequest;

        $routeMatcher->match($this->secureRequest, $this->routes)->willReturn(true);
        $routeMatcher->match($this->unsecureRequest, $this->routes)->willReturn(false);

        $this->beConstructedWith(
            $tokenManager,
            $routeMatcher,
            $this->routes,
            self::COOKIE_NAME,
            self::COOKIE_EXPIRE,
            self::COOKIE_PATH,
            self::COOKIE_DOMAIN,
            self::COOKIE_SECURE
        );
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType('Dunglas\AngularCsrfBundle\EventListener\AngularCsrfCookieListener');
    }

    public function it_sets_cookie_when_it_does(
        FilterResponseEvent $event,
        Response $response,
        ResponseHeaderBag $headers
    ) {
        $headers->setCookie(Argument::type('Symfony\Component\HttpFoundation\Cookie'));
        $response->headers = $headers;

        $event->getRequestType()->willReturn(HttpKernelInterface::MASTER_REQUEST)->shouldBeCalled();
        $event->getRequest()->willReturn($this->secureRequest)->shouldBeCalled();
        $event->getResponse()->willReturn($response)->shouldBeCalled();

        $this->onKernelResponse($event);
    }

    public function it_does_not_set_cookie_on_sub_request(FilterResponseEvent $event)
    {
        $event->getRequestType()->willReturn(HttpKernelInterface::SUB_REQUEST)->shouldBeCalled();
        $event->getRequest()->shouldNotBeCalled();

        $this->onKernelResponse($event);
    }

    public function it_does_not_set_cookie_when_it_does_not(FilterResponseEvent $event)
    {
        $event->getRequestType()->willReturn(HttpKernelInterface::MASTER_REQUEST)->shouldBeCalled();
        $event->getRequest()->willReturn($this->unsecureRequest)->shouldBeCalled();
        $event->getResponse()->shouldNotBeCalled();

        $this->onKernelResponse($event);
    }
}
