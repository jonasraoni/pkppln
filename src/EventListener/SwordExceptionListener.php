<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\EventListener;

use App\Controller\SwordController;
use Closure;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Controller\ErrorController;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

/**
 * Description of SwordExceptionListener.
 */
class SwordExceptionListener
{
    /**
     * Controller that threw the exception.
     *
     * @var (callable():mixed)|ErrorController $controller
     */
    private mixed $controller;

    /**
     * Twig instance.
     */
    private Environment $templating;

    /**
     * Symfony environment.
     */
    private string $env;

    /**
     * Construct the listener.
     */
    public function __construct(string $env, Environment $templating)
    {
        $this->templating = $templating;
        $this->env = $env;
    }

    /**
     * Once the controller has been initialized, this event is fired.
     *
     * Grab a reference to the active controller.
     */
    public function onKernelController(ControllerEvent $event): void
    {
        $this->controller = $event->getController();
    }

    /**
     * Exception handler for all controller events.
     *
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function onKernelException(ExceptionEvent $event): void
    {
        if (!is_array($this->controller) || ! ($this->controller[0] ?? null) instanceof SwordController) {
            return;
        }

        $exception = $event->getThrowable();
        $response = new Response();
        if ($exception instanceof HttpExceptionInterface) {
            $response->setStatusCode($exception->getStatusCode());
            $response->headers->replace($exception->getHeaders());
        } else {
            $response->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
        }
        $response->headers->set('Content-Type', 'text/xml');
        $response->setContent($this->templating->render('sword/exception_document.xml.twig', [
            'exception' => $exception,
            'env' => $this->env,
        ]));
        $event->setResponse($response);
    }
}
