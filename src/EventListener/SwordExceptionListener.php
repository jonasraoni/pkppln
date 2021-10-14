<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\EventListener;

use App\Controller\SwordController;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

/**
 * Description of SwordExceptionListener.
 */
class SwordExceptionListener {
    /**
     * Controller that threw the exception.
     *
     * @var AbstractController
     */
    private $controller;

    /**
     * Twig instance.
     *
     * @var Environment
     */
    private $templating;

    /**
     * Symfony environment.
     *
     * @var string
     */
    private $env;

    /**
     * Construct the listener.
     *
     * @param string $env
     * @param Environment $templating
     */
    public function __construct($env, Environment $templating) {
        $this->templating = $templating;
        $this->env = $env;
    }

    /**
     * Once the controller has been initialized, this event is fired.
     *
     * Grab a reference to the active controller.
     *
     * @param ControllerEvent $event
     */
    public function onKernelController(ControllerEvent $event) : void {
        $this->controller = $event->getController();
    }

    /**
     * Exception handler for all controller events.
     *
     * @param ExceptionEvent $event
     *
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function onKernelException(ExceptionEvent $event) : void {
        if ( ! $this->controller[0] instanceof SwordController) {
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
