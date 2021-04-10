<?php
declare(strict_types=1);

namespace Chialab\FrontendKit\View\Helper;

use BEdita\Core\Model\Entity\Media;
use BEdita\Core\Model\Entity\ObjectEntity;
use Cake\Utility\Hash;
use Cake\View\Helper;
use Iterator;

/**
 * Poster helper.
 *
 * @property \Cake\View\Helper\HtmlHelper $Html
 * @property \Chialab\FrontendKit\View\Helper\MediaHelper $Media
 * @property \Chialab\FrontendKit\View\Helper\ThumbHelper $Thumb
 */
class PosterHelper extends Helper
{
    protected const OBJECT_TYPE = 'images';

    /**
     * @inheritdoc
     */
    public $helpers = [
        'Html',
        'Chialab/FrontendKit.Media',
        'Chialab/FrontendKit.Thumb',
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
     * Get poster at the requested position.
     *
     * @param \BEdita\Core\Model\Entity\ObjectEntity $object
     * @param int $pos Position.
     * @return \BEdita\Core\Model\Entity\Media|null
     */
    protected function getPosterAt(ObjectEntity $object, int $pos = 0): ?Media
    {
        $posters = $object->get('poster');
        if (empty($posters) || !is_iterable($posters)) {
            return null;
        }

        $i = 0;
        foreach ($posters as $media) {
            // Dirty workaround to account for both arrays and iterators, and always get the n-th element.
            if ($i === $pos) {
                return $media;
            }
            $i++;
        }

        // If we reach this point, object has less posters than requested.
        return null;
    }

    /**
     * Yield candidates to be the poster.
     *
     * @param \BEdita\Core\Model\Entity\ObjectEntity|null $object
     * @param array $options Poster options.
     * @return \Iterator|\BEdita\Core\Model\Entity\Media[]
     */
    protected function candidates(?ObjectEntity $object, array $options): Iterator
    {
        $forceSelf = filter_var(Hash::get($options, 'forceSelf', false), FILTER_VALIDATE_BOOL);
        $variant = Hash::get($options, 'variant', 0);
        $fallbackSelf = filter_var(Hash::get($options, 'fallbackSelf', true), FILTER_VALIDATE_BOOL);

        if ($object === null) {
            return;
        }

        if (!$forceSelf) {
            $poster = $this->getPosterAt($object, $variant);
            if ($poster !== null && $this->Media->isMedia($poster, static::OBJECT_TYPE)) {
                yield $poster;
            }
            if (!$fallbackSelf) {
                return;
            }
        }

        if ($this->Media->isMedia($object, static::OBJECT_TYPE)) {
            yield $object;
        }
    }

    /**
     * Check if object has a valid poster, or is an Image itself, and the referenced file does actually exist.
     *
     * @param \BEdita\Core\Model\Entity\ObjectEntity|null $object Object entity.
     * @param bool $forceSelf Restrict checks to the object itself, rather than evaluating its `poster` related objects.
     * @param int $variant Poster variant, i.e. priority of the desired poster related object.
     * @return bool
     * @deprecated Use {@see \Chialab\FrontendKit\View\Helper\PosterHelper::exists()} instead.
     */
    public function check(?ObjectEntity $object, bool $forceSelf = false, int $variant = 0): bool
    {
        deprecationWarning('PosterHelper::check() is deprecated, use PosterHelper::exists() instead.');

        return $this->exists($object, compact('forceSelf', 'variant'));
    }

    /**
     * Check if object has a valid poster, or is an Image itself, and the referenced file does actually exist.
     *
     * ### Poster options:
     *
     * - `forceSelf`: restrict checks to the object itself, rather than evaluating its `poster` related objects. Default: `false`
     * - `variant`: poster variant, i.e. priority of the desired poster related object. Default: 0
     * - `fallbackSelf`: whether to use object itself as a fallback in case poster related objects cannot be used. Default: `true`
     *
     * @param \BEdita\Core\Model\Entity\ObjectEntity|null $object Object entity.
     * @param array $options Poster options.
     * @return bool
     */
    public function exists(?ObjectEntity $object, array $options = []): bool
    {
        foreach ($this->candidates($object, $options) as $media) {
            if ($media->has('media_url')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get media URL, optionally generating a thumbnail.
     *
     * @param \BEdita\Core\Model\Entity\Media $media Media entity.
     * @param $thumbOptions
     * @param array $fallbackOptions
     * @return string|null
     */
    protected function getMediaUrl(Media $media, $thumbOptions, array $fallbackOptions): ?string
    {
        if ($thumbOptions !== false) {
            return $this->Thumb->url($media, $thumbOptions, $fallbackOptions);
        }
        if ($this->Media->isRemote($media) || $this->Media->hasStream($media, true)) {
            return $media->get('media_url');
        }

        return null;
    }

    /**
     * Get URL for poster image.
     *
     * @param \BEdita\Core\Model\Entity\ObjectEntity|null $object Object entity.
     * @param bool $forceSelf Restrict checks to the object itself, rather than evaluating its `poster` related objects.
     * @param int $variant Poster variant, i.e. priority of the desired poster related object.
     * @return string|null
     * @deprecated Use {@see \Chialab\FrontendKit\View\Helper\PosterHelper::url()} instead.
     */
    public function getUrl(?ObjectEntity $object, bool $forceSelf = false, int $variant = 0): ?string
    {
        deprecationWarning('PosterHelper::getUrl() is deprecated, use PosterHelper::url() instead.');

        return $this->url($object, false, compact('forceSelf', 'variant'));
    }

    /**
     * Get URL for poster image.
     *
     * ### Poster options:
     *
     * - `forceSelf`: restrict checks to the object itself, rather than evaluating its `poster` related objects. Default: `false`
     * - `variant`: poster variant, i.e. priority of the desired poster related object. Default: 0
     * - `fallbackSelf`: whether to use object itself as a fallback in case poster related objects cannot be used. Default: `true`
     * - additional values are passed to {@see \Chialab\FrontendKit\View\Helper\ThumbHelper::url()} (unless `$thumbOptions === false`)
     *
     * @param \BEdita\Core\Model\Entity\ObjectEntity|null $object Object to get thumb for.
     * @param string|array|false $thumbOptions Thumbnail preset name or options, or `false` for no thumbnail. {@see \Chialab\FrontendKit\View\Helper\ThumbHelper::url()}
     * @param array $posterOptions Poster options.
     * @return string|null
     */
    public function url(?ObjectEntity $object, $thumbOptions = 'default', array $posterOptions = []): ?string
    {
        $fallbackStatic = filter_var(Hash::get($posterOptions, 'fallbackStatic', true), FILTER_VALIDATE_BOOL);
        $thumbFallbackOptions = $posterOptions;
        $thumbFallbackOptions['fallbackStatic'] = false;
        foreach ($this->candidates($object, $posterOptions) as $media) {
            $url = $this->getMediaUrl($media, $thumbOptions, $thumbFallbackOptions);
            if ($url !== null) {
                return $url;
            }
        }

        if ($fallbackStatic) {
            return $this->getFallbackImage();
        }

        return null;
    }

    /**
     * Create `<img>` HTML tag for poster.
     *
     * @param \BEdita\Core\Model\Entity\ObjectEntity|null $object Object entity.
     * @param string|array|false $thumbOptions Thumbnail preset name or options, or `false` for no thumbnail. {@see \Chialab\FrontendKit\View\Helper\ThumbHelper::url()}
     * @param array $attributes HTML attributes for `<img>` tag.
     * @param array $posterOptions Poster options. {@see \Chialab\FrontendKit\View\Helper\PosterHelper::url()}
     * @return string
     */
    public function image(?ObjectEntity $object, $thumbOptions = 'default', array $attributes = [], array $posterOptions = []): string
    {
        $url = $this->url($object, $thumbOptions, $posterOptions);
        if (!$url) {
            return '';
        }

        return $this->Html->image($url, $attributes + ['plugin' => false]);
    }
}
