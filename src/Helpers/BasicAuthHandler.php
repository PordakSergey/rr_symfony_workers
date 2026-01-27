<?php

namespace Rr\Bundle\Workers\Helpers;

use Symfony\Component\HttpFoundation\Request;

final class BasicAuthHandler
{
    /**
     * @param Request $request
     * @return void
     */
    public function handle(Request $request)
    {
        $authorizationHeader = $request->headers->get('Authorization');

        if (!$authorizationHeader) {
            return;
        }

        if (preg_match("/Basic\s+(.*)$/i", $authorizationHeader, $matches)) {
            $decoded = base64_decode($matches[1], true);

            if (!$decoded) {
                return;
            }

            $userPass = explode(':', $decoded, 2);

            $userInfo = [
                'PHP_AUTH_USER' => $userPass[0],
                'PHP_AUTH_PW' => $userPass[1] ?? '',
            ];

            $request->headers->add($userInfo);
            $request->server->add($userInfo);
        }
    }
}