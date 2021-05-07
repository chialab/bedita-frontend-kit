<?php
declare(strict_types=1);

namespace Chialab\FrontendKit\View\Helper;

use BEdita\Core\Filesystem\Thumbnail;
use BEdita\Core\Model\Entity\ObjectEntity;
use Cake\Utility\Hash;
use Cake\View\Helper;

/**
 * Thumbnails helper
 *
 * @property \Cake\View\Helper\HtmlHelper $Html
 * @property \Chialab\FrontendKit\View\Helper\MediaHelper $Media
 */
class ThumbHelper extends Helper
{
    /**
     * @inheritdoc
     */
    public $helpers = [
        'Html',
        'Chialab/FrontendKit.Media',
    ];

    /**
     * @inheritdoc
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
        return $this->getConfig('fallbackImage', $this->Media->getFallbackImage());
    }

    /**
     * Get thumb URL for image.
     *
     * ### Fallback options:
     *
     * - `allowPending`: whether to return URLs thumbnails that are not yet ready, or use a fallback instead. Default: `false`
     * - `fallbackOriginal`: whether to return original media URL if thumbnail cannot be generated. Default: `true`
     * - `fallbackStatic`: whether to return a static fallback image if thumbnail cannot be generated. Default: `true`
     *
     * @param \BEdita\Core\Model\Entity\ObjectEntity|null $object Object to get thumb for.
     * @param array|string $thumbOptions Thumbnail preset name or options.
     * @param array $fallbackOptions Fallback options.
     * @return string|null
     */
    public function url(?ObjectEntity $object, $thumbOptions = 'default', array $fallbackOptions = []): ?string
    {
        $allowPending = filter_var(Hash::get($fallbackOptions, 'allowPending', false), FILTER_VALIDATE_BOOL);
        $fallbackOriginal = filter_var(Hash::get($fallbackOptions, 'fallbackOriginal', true), FILTER_VALIDATE_BOOL);
        $fallbackStatic = filter_var(Hash::get($fallbackOptions, 'fallbackStatic', true), FILTER_VALIDATE_BOOL);

        $fallback = $fallbackStatic ? $this->getFallbackImage() : null;
        if (!$this->Media->isMedia($object)) {
            return $fallback;
        }

        /** @var \BEdita\Core\Model\Entity\Media $media */
        $media = $object;

        $stream = $this->Media->getStream($media);
        if ($stream !== null) {
            $res = Thumbnail::get($stream, $thumbOptions);
            if (!empty($res['url']) && (!empty($res['ready']) || $allowPending)) {
                return $res['url'];
            }
        }

        if ($fallbackOriginal && ($this->Media->isRemote($media) || $this->Media->hasStream($media, true))) {
            return $media->get('media_url');
        }

        return $fallback;
    }

    /**
     * Create `<img>` HTML tag for thumbnail.
     *
     * @param \BEdita\Core\Model\Entity\ObjectEntity|null $object Object entity.
     * @param array|string $thumbOptions Thumbnail preset name or options.
     * @param array $attributes HTML attributes for `<img>` tag.
     * @param array $fallbackOptions Fallback options. {@see \Chialab\FrontendKit\View\Helper\ThumbHelper::url()}
     * @return string
     */
    public function image(?ObjectEntity $object, $thumbOptions = 'default', array $attributes = [], array $fallbackOptions = []): string
    {
        $url = $this->url($object, $thumbOptions, $fallbackOptions);
        if (empty($url)) {
            return '';
        }

        return $this->Html->image($url, $attributes + ['plugin' => false]);
    }
}
