<?php
declare(strict_types=1);

namespace Chialab\FrontendKit\Test\TestApp\Filesystem\Adapter;

use BEdita\Core\Filesystem\FilesystemAdapter;
use League\Flysystem\FilesystemAdapter as LeagueFilesystemAdapter;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;

/**
 * Null adapter, for testing.
 */
class NullAdapter extends FilesystemAdapter
{
    /**
     * @inheritDoc
     */
    protected function buildAdapter(array $config): LeagueFilesystemAdapter
    {
        return new InMemoryFilesystemAdapter();
    }
}
