<?php
namespace Chialab\Frontend\View\Helper;

use Cake\Core\Plugin;
use Cake\Utility\Hash;
use Cake\Utility\Inflector;
use Cake\View\Helper;
use Cake\View\View;

/**
 * Asset helper
 *
 * @property-read \Cake\View\Helper\HtmlHelper $Html
 */
class AssetHelper extends Helper
{

    public $helpers = ['Html'];

    /**
     * Default configuration.
     *
     * @var array
     */
    protected $_defaultConfig = [
        'buildPath' => 'webroot' . DS . 'build',
        'entrypointFile' => 'entrypoints.json',
    ];

    protected $cache = [];

    protected function loadEntrypoint(string $plugin = null): ?array
    {
        if ($plugin === null) {
            $plugin = '_';
        }

        if (isset($this->cache[$plugin])) {
            return $this->cache[$plugin];
        }

        $path = ROOT . DS;
        if ($plugin !== '_') {
            $path = Plugin::path($plugin);
        }

        $path .= rtrim($this->getConfig('buildPath'), DS) . DS . $this->getConfig('entrypointFile');

        return $this->cache[$plugin] = json_decode(file_get_contents($path), true);
    }

    public function getAsset(string $asset, string $type): array
    {
        list($plugin, $resource) = pluginSplit($asset);
        $map = $this->loadEntrypoint($plugin);

        return Hash::get($map, sprintf('entrypoints.%s.%s', $resource, $type), []);
    }

    public function css(string $asset): string
    {
        return join('', array_map(
            function (string $path): string {
                return $this->Html->css($path);
            },
            $this->getAsset($asset, 'css')
        ));
    }

    public function script(string $asset): string
    {
        return join('', array_map(
            function (string $path): string {
                return $this->Html->script($path);
            },
            $this->getAsset($asset, 'js')
        ));
    }
}
