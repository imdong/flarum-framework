<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\PackageManager\Api\Controller;

use Flarum\Http\RequestUtil;
use Flarum\PackageManager\Command\WhyNot;
use Flarum\PackageManager\Job\Dispatcher;
use Illuminate\Support\Arr;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class WhyNotController implements RequestHandlerInterface
{
    public function __construct(
        protected Dispatcher $bus
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $actor = RequestUtil::getActor($request);
        $package = Arr::get($request->getParsedBody(), 'data.package', '');
        $version = Arr::get($request->getParsedBody(), 'data.version', '*');

        $whyNot = $this->bus->sync()->dispatch(
            new WhyNot($actor, $package, $version)
        );

        return new JsonResponse(['data' => ['reason' => $whyNot->data['reason']]]);
    }
}
