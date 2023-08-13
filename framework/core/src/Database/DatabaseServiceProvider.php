<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Database;

use Flarum\Foundation\AbstractServiceProvider;
use Illuminate\Container\Container as ContainerImplementation;
use Illuminate\Contracts\Container\Container;
use Illuminate\Database\Capsule\Manager;
use Illuminate\Database\ConnectionResolverInterface;

class DatabaseServiceProvider extends AbstractServiceProvider
{
    public function register(): void
    {
        $this->container->singleton(Manager::class, function (ContainerImplementation $container) {
            $manager = new Manager($container);

            $config = $container['flarum']->config('database');
            $config['engine'] = 'InnoDB';
            $config['prefix_indexes'] = true;

            $manager->addConnection($config, 'flarum');

            return $manager;
        });

        $this->container->singleton('db', function (Container $container) {
            /** @var Manager $manager */
            $manager = $container->make(Manager::class);
            $manager->setAsGlobal();
            $manager->bootEloquent();

            $dbManager = $manager->getDatabaseManager();
            $dbManager->setDefaultConnection('flarum');

            return $dbManager;
        });

        $this->container->singleton('db.connection', function (Container $container) {
            /** @var ConnectionResolverInterface $resolver */
            $resolver = $container->make('db');

            return $resolver->connection();
        });

        $this->container->singleton(MigrationRepositoryInterface::class, function (Container $container) {
            return new DatabaseMigrationRepository($container['db.connection'], 'migrations');
        });

        $this->container->singleton('flarum.database.model_private_checkers', function () {
            return [];
        });
    }

    public function boot(Container $container): void
    {
        AbstractModel::setConnectionResolver($container->make(ConnectionResolverInterface::class));
        AbstractModel::setEventDispatcher($container->make('events'));

        foreach ($container->make('flarum.database.model_private_checkers') as $modelClass => $checkers) {
            $modelClass::saving(function ($instance) use ($checkers) {
                foreach ($checkers as $checker) {
                    if ($checker($instance) === true) {
                        $instance->is_private = true;

                        return;
                    }
                }

                $instance->is_private = false;
            });
        }
    }
}
