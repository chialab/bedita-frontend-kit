<?php
declare(strict_types=1);

namespace Chialab\FrontendKit\View\Helper;

use BEdita\Core\Filesystem\FilesystemRegistry;
use BEdita\Core\Model\Entity\Media;
use BEdita\Core\Model\Entity\ObjectEntity;
use BEdita\Core\Model\Entity\Stream;
use Cake\View\Helper;

/**
 * Media helper.
 *
 * @package Chialab\FrontendKit\View\Helper
 */
class MediaHelper extends Helper
{
    /**
     * @inheritDoc
     */
    protected $_defaultConfig = [
        'fallbackImage' => null,
    ];

    /**
     * Get fallback image URL.
     *
     * @return string|null
     */
    public function getFallbackImage(): ?string
    {
        return $this->getConfig('fallbackImage');
    }

    /**
     * Check if an object is a media entity, optionally performing a strict object type check.
     *
     * @param \BEdita\Core\Model\Entity\ObjectEntity|null $object Object entity.
     * @param string|null $type Object type name to check, or `null` to accept any Media entity regardless of concrete type.
     * @return bool
     */
    public function isMedia(?ObjectEntity $object, ?string $type = null): bool
    {
        if ($object === null || !$object instanceof Media) {
            return false;
        }

        if ($type !== null && $object->get('type') !== $type) {
            return false;
        }

        return true;
    }

    /**
     * Get Media Stream.
     *
     * @param \BEdita\Core\Model\Entity\Media $media Media entity.
     * @return \BEdita\Core\Model\Entity\Stream|null
     */
    public function getStream(Media $media): ?Stream
    {
        if (empty($media->media_url)) {
            // Dirty workaround to load streams that are fetched only on media_url property access.
            return null;
        }

        if (!$media->has('streams') || !is_iterable($media->streams)) {
            return null;
        }

        foreach ($media->streams as $stream) {
            // Dirty workaround to account for both arrays and iterators, and always get the first element.
            return $stream;
        }

        // If we reach this point, media has zero streams.
        return null;
    }

    /**
     * Check if media has a valid stream, with optional filesystem check.
     *
     * @param \BEdita\Core\Model\Entity\Media $media Media entity.
     * @param bool $filesystemCheck Check if stream actually exists on filesystem.
     * @return bool
     */
    public function hasStream(Media $media, bool $filesystemCheck = false): bool
    {
        $stream = $this->getStream($media);
        if ($stream === null) {
            return false;
        }

        return !$filesystemCheck || FilesystemRegistry::getMountManager()->has($stream->uri);
    }

    /**
     * Check if media references a local stream.
     *
     * @param \BEdita\Core\Model\Entity\Media $media Media entity.
     * @return bool
     */
    public function isLocal(Media $media): bool
    {
        return empty($media->provider_url) && $this->hasStream($media);
    }

    /**
     * Check if media references a remote resource.
     *
     * @param \BEdita\Core\Model\Entity\Media $media Media entity.
     * @return bool
     */
    public function isRemote(Media $media): bool
    {
        return !empty($media->provider_url);
    }
}
