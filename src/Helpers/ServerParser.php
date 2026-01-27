<?php

namespace Rr\Bundle\Workers\Helpers;

use Exception;
use Spiral\RoadRunner\Http\Request as RoadRunnerRequest;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class ServerParser
{
    /**
     * @var array
     */
    private array $originalServer;

    public function __construct()
    {
        $this->originalServer = $_SERVER;
    }

    /**
     * @param RoadRunnerRequest $request
     * @return array
     * @throws Exception
     */
    public function configureServer(RoadRunnerRequest $request): array
    {
        $server = $this->originalServer;

        $components = parse_url($request->uri);

        if ($components === false) {
            throw new Exception('Failed to parse RoadRunner request URI: ' . $request->uri);
        }

        if (isset($components['host'])) {
            $server['SERVER_NAME'] = $components['host'];
        }

        if (isset($components['port'])) {
            $server['SERVER_PORT'] = $components['port'];
        } elseif (isset($components['scheme'])) {
            $server['SERVER_PORT'] = $components['scheme'] === 'https' ? 443 : 80;
        }

        $server['REQUEST_URI'] = $components['path'] ?? '';
        if (isset($components['query']) && $components['query'] !== '') {
            $server['QUERY_STRING'] = $components['query'];
            $server['REQUEST_URI'] .= '?' . $components['query'];
        }

        if (isset($components['scheme']) && $components['scheme'] === 'https') {
            $server['HTTPS'] = 'on';
        }

        $server['REQUEST_TIME'] = time();
        $server['REQUEST_TIME_FLOAT'] = microtime(true);
        $server['REMOTE_ADDR'] = $request->getRemoteAddr();
        $server['REQUEST_METHOD'] = $request->method;
        $server['SERVER_PROTOCOL'] = $request->protocol;

        $server['HTTP_USER_AGENT'] = '';
        foreach ($request->headers as $key => $value) {
            $key = strtoupper(str_replace('-', '_', $key));
            if (\in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH'])) {
                $server[$key] = implode(', ', $value);
            } else {
                $server['HTTP_' . $key] = implode(', ', $value);
            }
        }

        $authorizationHeader = $request->headers['Authorization'][0] ?? null;

        if ($authorizationHeader !== null && preg_match("/Basic\s+(.*)$/i", $authorizationHeader, $matches)) {
            $decoded = base64_decode($matches[1], true);

            if ($decoded) {
                $userPass = explode(':', $decoded, 2);

                $server['PHP_AUTH_USER'] = $userPass[0];
                $server['PHP_AUTH_PW'] = $userPass[1] ?? '';
            }
        }

        return $server;
    }

    /**
     * @param array $files
     * @return array
     */
    public function wrapUploads(array $files): array
    {
        $result = [];

        foreach ($files as $index => $file) {
            if (!isset($file['name'])) {
                $result[$index] = $this->wrapUploads($file);
                continue;
            }

            $result[$index] = new UploadedFile($file['tmpName'] ?? '', $file['name'], $file['mime'], $file['error'], true);
        }

        return $result;
    }

    /**
     * @param array $headers
     * @return array
     */
    public function stringifyHeaders(array $headers): array
    {
        return array_map(static function ($headerValues) {
            return array_map(static fn ($val) => (string) $val, (array) $headerValues);
        }, $headers);
    }
}