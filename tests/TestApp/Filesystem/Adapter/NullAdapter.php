<?php

namespace Chialab\FrontendKit\Test\TestApp\Filesystem\Adapter;

use BEdita\Core\Filesystem\FilesystemAdapter;
use League\Flysystem\Adapter\NullAdapter as FlysystemNullAdapter;

/**
 * Null adapter, for testing.
 */
class NullAdapter extends FilesystemAdapter
{
    /**
     * @inheritDoc
     */
    protected function buildAdapter(array $config)
    {
        return new FlysystemNullAdapter();
    }
}
