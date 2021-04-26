<?php
declare(strict_types=1);

namespace Chialab\FrontendKit\View\Helper;

use Cake\Core\Plugin;
use Cake\Utility\Hash;
use Cake\View\Helper;

/**
 * Encore helper
 *
 * @property-read \Cake\View\Helper\HtmlHelper $Html
 */
class EncoreHelper extends Helper
{
    /**
     * @inheritDoc
     */
    public $helpers = ['Html'];

    /**
     * @inheritDoc
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
    protected function loadEntrypoints(?string $plugin = null): ?array
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
        if (!file_exists($path) || !is_readable($path)) {
            return null;
        }

        return $this->cache[$plugin] = json_decode(file_get_contents($path), true);
    }

    /**
     * Get assets by type.
     *
     * @param string $asset The assets name.
     * @param string $type The asset type.
     * @return array A list of resources with their format.
     */
    public function getAssets(string $asset, string $type): array
    {
        [$plugin, $resource] = pluginSplit($asset);
        $map = $this->loadEntrypoints($plugin);
        if ($map === null) {
            return [];
        }

        $format = Hash::get($map, sprintf('entrypoints.%s.format', $resource), 'umd');
        $entries = Hash::get($map, sprintf('entrypoints.%s.%s', $resource, $type), []);

        return [$format, $entries];
    }

    /**
     * Get css assets.
     *
     * @param string $asset The assets name.
     * @return string HTML to load CSS resources.
     */
    public function css(string $asset): string
    {
        $assets = $this->getAssets($asset, 'css');

        return join('', array_filter(
            array_map(
                function (string $path): ?string {
                    return $this->Html->css($path);
                },
                $assets[1]
            )
        ));
    }

    /**
     * Get js assets.
     *
     * @param string $asset The assets name.
     * @param array $options Array of options and HTML arguments.
     * @return string HTML to load JS resources.
     */
    public function script(string $asset, array $options = []): string
    {
        $assets = $this->getAssets($asset, 'js');
        if ($assets[0] === 'esm') {
            $options['type'] = 'module';
        }

        return join('', array_filter(
            array_map(
                function (string $path) use ($options): ?string {
                    return $this->Html->script($path, $options);
                },
                $assets[1]
            )
        ));
    }
}
