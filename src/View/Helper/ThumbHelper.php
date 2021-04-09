<?php
declare(strict_types=1);

namespace Chialab\FrontendKit\View\Helper;

use Cake\View\Helper;
use BEdita\Core\Filesystem\FilesystemRegistry;
use BEdita\Core\Filesystem\ThumbnailRegistry;
use BEdita\Core\Model\Entity\Media;
use BEdita\Core\Model\Entity\ObjectEntity;
use Cake\Utility\Hash;

/**
 * Thumb helper
 *
 * @property-read \Cake\View\Helper\HtmlHelper $Html
 */
class ThumbHelper extends Helper
{
    /**
     * @inheritdoc
     */
    public $helpers = ['Html'];

    /**
     * Thumbnail registry.
     *
     * @var \BEdita\Core\Filesystem\ThumbnailRegistry
     */
    protected static $_registry;

    /**
     * @inheritdoc
     */
    protected $_defaultConfig = [
        'fallbackImage' => null,
        'preset' => [
            'generator' => 'default',
        ],
    ];

    /**
     * Getter for thumbnails registry.
     *
     * @return \BEdita\Core\Filesystem\ThumbnailRegistry
     */
    public static function getRegistry()
    {
        if (!isset(static::$_registry)) {
            static::$_registry = new ThumbnailRegistry();
        }

        return static::$_registry;
    }

    /**
     * Check if an Object is an Image with property `mediaUrl` set and non-empty.
     *
     * @param \BEdita\Core\Model\Entity\ObjectEntity|null $object Object to check.
     * @return bool
     */
    public static function isValidImage(?ObjectEntity $object): bool
    {
        return $object !== null && ($object instanceof Media) && $object->type === 'images' && !$object->isEmpty('mediaUrl');
    }

    /**
     * Check if a Media's Stream actually exists.
     *
     * @param \BEdita\Core\Model\Entity\ObjectEntity|null $object
     * @return bool
     */
    public static function hasValidStream(?ObjectEntity $object): bool
    {
        if (!static::isValidImage($object)) {
            return false;
        }

        $uri = Hash::get($object, 'streams.0.uri');
        if (empty($uri)) {
            return false;
        }

        return FilesystemRegistry::getMountManager()->has($uri);
    }

    /**
     * Get thumb URL for image.
     *
     * @param \BEdita\Core\Model\Entity\ObjectEntity|null $object Object to get thumb for.
     * @param array $preset Thumb options.
     * @return string|null
     */
    public function url(?ObjectEntity $object, array $preset = []): ?string
    {
        if (!static::hasValidStream($object)) {
            return $this->getConfig('fallbackImage');
        }

        $options = $preset + ($this->getConfig('preset') ?: []);
        $generatorName = Hash::get($options, 'generator', 'default');
        $stream = Hash::get($object, 'streams.0');
        
        $registry = static::getRegistry();
        $generator = $registry->has($generatorName) ? $registry->get($generatorName) : $registry->load($generatorName);

        return $generator->getUrl($stream, $options);
    }

    /**
     * Create image tag for thumb.
     *
     * @param \BEdita\Core\Model\Entity\ObjectEntity|null $object Object to get thumb for.
     * @param array $preset Thumb options.
     * @param array $options Html options.
     * @return string|null
     */
    public function image(?ObjectEntity $object, array $preset = [], array $options = []): string
    {
        $url = $this->url($object, $preset);
        if (!$url) {
            return '';
        }

        return $this->Html->image($url, $options);
    }
}
