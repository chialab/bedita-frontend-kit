<?php
namespace Chialab\Frontend\View\Helper;

use Cake\Core\Plugin;
use Cake\Utility\Hash;
use Cake\View\Helper;

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

    /**
     * Cache entrypoints.
     *
     * @var array
     */
    protected $cache = [];

    /**
     * Load entrypoints for a frontend plugin.
     *
     * @param string|null $plugin The frontend plugin name.
     * @return array A list of entrypoints.
     */
    protected function loadEntrypoints(string $plugin = null): ?array
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

    /**
     * Get assets by type.
     *
     * @param string $asset The assets name.
     * @param string $type The asset type.
     * @return array A list of resources.
     */
    public function getAssets(string $asset, string $type): array
    {
        list($plugin, $resource) = pluginSplit($asset);
        $map = $this->loadEntrypoints($plugin);

        return Hash::get($map, sprintf('entrypoints.%s.%s', $resource, $type), []);
    }

    /**
     * Get css assets.
     *
     * @param string $asset The assets name.
     * @return array A list of css resources.
     */
    public function css(string $asset): string
    {
        return join('', array_map(
            function (string $path): string {
                return $this->Html->css($path);
            },
            $this->getAssets($asset, 'css')
        ));
    }

    /**
     * Get js assets.
     *
     * @param string $asset The assets name.
     * @return array A list of js resources.
     */
    public function script(string $asset): string
    {
        return join('', array_map(
            function (string $path): string {
                return $this->Html->script($path);
            },
            $this->getAssets($asset, 'js')
        ));
    }
}
