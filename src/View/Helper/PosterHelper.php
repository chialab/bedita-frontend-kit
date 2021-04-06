<?php
declare(strict_types=1);

namespace Chialab\FrontendKit\View\Helper;

use Cake\View\Helper;
use Cake\Collection\Collection;
use BEdita\Core\Filesystem\FilesystemRegistry;
use BEdita\Core\Model\Entity\Media;
use BEdita\Core\Model\Entity\ObjectEntity;
use Cake\Utility\Hash;

/**
 * Poster helper
 */
class PosterHelper extends Helper
{
    /**
     * Default configuration.
     *
     * @var array
     */
    protected $_defaultConfig = [
        'fallbackImage' => null,
    ];

    /**
     * Check if an Object is an Image with property `mediaUrl` set and non-empty.
     *
     * @param \BEdita\Core\Model\Entity\ObjectEntity|null $object Object to check.
     * @return bool
     */
    protected static function isValidImage(?ObjectEntity $object): bool
    {
        return $object !== null && ($object instanceof Media) && $object->type === 'images' && !$object->isEmpty('mediaUrl');
    }

    /**
     * Check if a Media's Stream actually exists.
     *
     * @param \BEdita\Core\Model\Entity\ObjectEntity|null $object
     * @return bool
     */
    protected static function hasValidStream(?ObjectEntity $object): bool
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
     * Check if object has a valid poster, or is an Image itself.
     *
     * @param \BEdita\Core\Model\Entity\ObjectEntity|null $object Object being checked.
     * @param bool $forceSelf Only check object itself, do not check for its `poster` relations.
     * @param int $variant Use `poster` at given index, if greater than zero.
     * @return bool
     */
    public function check(?ObjectEntity $object, bool $forceSelf = false, int $variant = 0): bool
    {
        if ($object === null || $forceSelf) {
            return static::isValidImage($object);
        }

        $posters = new Collection($object->get('poster') ?: []);
        if ($variant > 0) {
            $posters = $posters->skip($variant);
        }

        $poster = $posters->first();
        if (!static::isValidImage($poster)) {
            return $this->check($object, true);
        }

        return true;
    }

    /**
     * Get URL for poster image.
     *
     * @param \BEdita\Core\Model\Entity\ObjectEntity|null $object Object to get poster for.
     * @param bool $forceSelf Only use object itself, do not use its `poster` relations.
     * @param int $variant Use `poster` at given index, if greater than zero.
     * @return string|null
     */
    public function getUrl(?ObjectEntity $object, bool $forceSelf = false, int $variant = 0): ?string
    {
        if ($object === null || $forceSelf) {
            if (static::hasValidStream($object)) {
                return $object->get('mediaUrl');
            }

            return $this->getConfig('fallbackImage');
        }

        $posters = new Collection($object->get('poster') ?: []);
        if ($variant > 0) {
            $posters = $posters->skip($variant);
        }

        $poster = $posters->first();
        if (!static::isValidImage($poster)) {
            return $this->getUrl($object, true);
        }

        return $this->getUrl($poster, true);
    }
}
