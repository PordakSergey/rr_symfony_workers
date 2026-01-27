<?php

namespace Rr\Bundle\Workers\Traits;

use Symfony\Component\ErrorHandler\ErrorRenderer\HtmlErrorRenderer;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;

trait ErrorRenderer
{
    private \Closure $renderError;

    /**
     * @param KernelInterface $kernel
     * @return void
     */
    public function initErrorRenderer(KernelInterface $kernel) : void
    {
        if (class_exists(HtmlErrorRenderer::class)) {
            $htmlRenderer = new HtmlErrorRenderer($kernel->isDebug());
            $this->renderError = static function (\Throwable $e) use ($htmlRenderer): Response {
                $flatten = $htmlRenderer->render($e);

                return new Response($flatten->getAsString(), $flatten->getStatusCode(), $flatten->getHeaders());
            };
        } else {
            $this->renderError = static function (\Throwable $e) use ($kernel) {
                $message = $kernel->isDebug() ? (string)$e : 'Internal error';
                return new Response($message, 500, ['Content-Type' => 'text/plain']);
            };
        }
    }
}