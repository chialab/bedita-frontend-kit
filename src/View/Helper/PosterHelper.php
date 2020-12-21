<?php
namespace Chialab\FrontendKit\View\Helper;

use Cake\View\Helper;
use Cake\View\View;
use Cake\Collection\Collection;
use BEdita\Core\Filesystem\FilesystemRegistry;
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

    public function check(ObjectEntity $object, bool $forceSelf = false): bool
    {
        if (!$forceSelf) {
            $poster = (new Collection($object->get('poster') ?: []))->first();

            if ($poster !== null) {
                return $this->check($poster, true);
            }
        }

        if ($object->get('type') !== 'images' || $object->isEmpty('mediaUrl')) {
            return false;
        }

        return true;
    }

    public function getUrl(ObjectEntity $object, bool $forceSelf = false): ?string
    {
        if (!$forceSelf) {
            $poster = (new Collection($object->get('poster') ?: []))->first();

            if ($poster !== null) {
                return $this->getUrl($poster, true);
            }
        }

        $fallback = $this->getConfig('fallbackImage');

        if ($object->get('type') !== 'images' || $object->isEmpty('mediaUrl')) {
            return $fallback;
        }

        if (FilesystemRegistry::getMountManager()->has(Hash::get($object, 'streams.0.uri'))) {
            return $object->get('mediaUrl');
        }

        return $fallback;
    }
}
