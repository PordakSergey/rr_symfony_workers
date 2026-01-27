<?php

namespace Rr\Bundle\Workers\RoadRunnerBridge;

use Rr\Bundle\Workers\Contracts\RoadRunnerBridge\HttpFoundationWorkerInterface;
use Rr\Bundle\Workers\Helpers\ServerParser;
use Spiral\RoadRunner\Http\HttpWorkerInterface;
use Spiral\RoadRunner\Http\Request as RoadRunnerRequest;
use Spiral\RoadRunner\WorkerInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

final readonly class HttpFoundationWorker implements HttpFoundationWorkerInterface
{
    /**
     * @param HttpWorkerInterface $httpWorker
     * @param ServerParser $serverParser
     */
    public function __construct(
        private HttpWorkerInterface $httpWorker,
        private ServerParser $serverParser,
    )
    {
    }

    /**
     * @return WorkerInterface
     */
    public function getWorker(): WorkerInterface
    {
        return $this->httpWorker->getWorker();
    }

    /**
     * @return SymfonyRequest|null
     * @throws \JsonException
     */
    public function waitRequest(): ?SymfonyRequest
    {
        $rrRequest = $this->httpWorker->waitRequest();
        if ($rrRequest === null) {
            return null;
        }
        return $this->toSymfonyRequest($rrRequest);
    }

    /**
     * @param SymfonyResponse $symfonyResponse
     * @return void
     */
    public function respond(SymfonyResponse $symfonyResponse): void
    {
        if ($symfonyResponse instanceof BinaryFileResponse && !$symfonyResponse->headers->has('Content-Range')) {
            $content = file_get_contents($symfonyResponse->getFile()->getPathname());
            if ($content === false) {
                throw new \RuntimeException(\sprintf("Cannot read file '%s'", $symfonyResponse->getFile()->getPathname()));
            }
        } else {
            if ($symfonyResponse instanceof StreamedResponse || $symfonyResponse instanceof BinaryFileResponse) {
                $content = '';
                ob_start(function ($buffer) use (&$content) {
                    $content .= $buffer;

                    return '';
                });

                $symfonyResponse->sendContent();
                ob_end_clean();
            } else {
                $content = (string) $symfonyResponse->getContent();
            }
        }

        $headers = $this->serverParser->stringifyHeaders($symfonyResponse->headers->all());

        $this->httpWorker->respond($symfonyResponse->getStatusCode(), $content, $headers);
    }

    /**
     * @param RoadRunnerRequest $rrRequest
     * @return SymfonyRequest
     * @throws \JsonException
     * @throws \Exception
     */
    private function toSymfonyRequest(RoadRunnerRequest $rrRequest): SymfonyRequest
    {
        $_SERVER = $this->serverParser->configureServer($rrRequest);

        $files = $this->serverParser->wrapUploads($rrRequest->uploads);

        $request = new SymfonyRequest(
            $rrRequest->query,
            $rrRequest->getParsedBody() ?? [],
            $rrRequest->attributes,
            $rrRequest->cookies,
            $files,
            $_SERVER,
            $rrRequest->body
        );

        $request->headers->add($rrRequest->headers);
        return $request;
    }
}