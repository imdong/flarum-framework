<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Admin\Content;

use Flarum\Extension\ExtensionManager;
use Flarum\Foundation\ApplicationInfoProvider;
use Flarum\Foundation\Config;
use Flarum\Frontend\Document;
use Flarum\Group\Permission;
use Flarum\Settings\Event\Deserializing;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\User;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface as Request;

class AdminPayload
{
    public function __construct(
        protected Container $container,
        protected SettingsRepositoryInterface $settings,
        protected ExtensionManager $extensions,
        protected ConnectionInterface $db,
        protected Dispatcher $events,
        protected Config $config,
        protected ApplicationInfoProvider $appInfo
    ) {
    }

    public function __invoke(Document $document, Request $request): void
    {
        $settings = $this->settings->all();

        $this->events->dispatch(
            new Deserializing($settings)
        );

        $document->payload['settings'] = $settings;
        $document->payload['permissions'] = Permission::map();
        $document->payload['extensions'] = $this->extensions->getExtensions()->toArray();

        $document->payload['displayNameDrivers'] = array_keys($this->container->make('flarum.user.display_name.supported_drivers'));
        $document->payload['slugDrivers'] = array_map(function ($resourceDrivers) {
            return array_keys($resourceDrivers);
        }, $this->container->make('flarum.http.slugDrivers'));

        $document->payload['phpVersion'] = $this->appInfo->identifyPHPVersion();
        $document->payload['mysqlVersion'] = $this->appInfo->identifyDatabaseVersion();
        $document->payload['debugEnabled'] = Arr::get($this->config, 'debug');

        if ($this->appInfo->scheduledTasksRegistered()) {
            $document->payload['schedulerStatus'] = $this->appInfo->getSchedulerStatus();
        }

        $document->payload['queueDriver'] = $this->appInfo->identifyQueueDriver();
        $document->payload['sessionDriver'] = $this->appInfo->identifySessionDriver(true);

        /**
         * Used in the admin user list. Implemented as this as it matches the API in flarum/statistics.
         * If flarum/statistics ext is enabled, it will override this data with its own stats.
         *
         * This allows the front-end code to be simpler and use one single source of truth to pull the
         * total user count from.
         */
        $document->payload['modelStatistics'] = [
            'users' => [
                'total' => User::query()->count()
            ]
        ];
    }
}
