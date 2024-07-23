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

    protected const MOBILE_DEFAULT_WIDTH = 640;

    protected const MOBILE_MAX_WIDTH = 767;

    /**
     * @inheritDoc
     */
    public $helpers = [
        'Html',
        'Chialab/FrontendKit.Media',
        'Chialab/FrontendKit.Thumb',
    ];

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
    public function getFallbackImage(): string|null
    {
        return $this->getConfig('fallbackImage', $this->Media->getFallbackImage());
    }

    /**
     * Get poster at the requested position.
     *
     * @param \BEdita\Core\Model\Entity\ObjectEntity $object The object.
     * @param int $pos Position.
     * @return \BEdita\Core\Model\Entity\Media|null
     */
    protected function getPosterAt(ObjectEntity $object, int $pos = 0): Media|null
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
     * @param \BEdita\Core\Model\Entity\ObjectEntity|null $object The object.
     * @param array $options Poster options.
     * @return \Iterator|array<\BEdita\Core\Model\Entity\Media>
     */
    protected function candidates(ObjectEntity|null $object, array $options): Iterator
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
     * Check if object has a valid mobile variant.
     *
     * @param \BEdita\Core\Model\Entity\ObjectEntity|null $object Object entity.
     * @return bool
     */
    public function mobileExists(ObjectEntity|null $object): bool
    {
        if (empty($object['has_variant_mobile'])) {
            return false;
        }

        $variant = Hash::get($object, 'has_variant_mobile.0');

        return $this->exists($variant, ['forceSelf' => true]);
    }

    /**
     * Get mobile variant object (first related object).
     *
     * @param \BEdita\Core\Model\Entity\ObjectEntity|null $object Object entity.
     * @return \BEdita\Core\Model\Entity\Media|null
     */
    protected function mobile(ObjectEntity|null $object): Media|null
    {
        if (!$this->mobileExists($object)) {
            return null;
        }

        $variant = Hash::get($object, 'has_variant_mobile.0');

        return $variant;
    }

    /**
     * Get `srcset` attribute for image.
     *
     * @param \BEdita\Core\Model\Entity\ObjectEntity|null $object Object entity.
     * @param array|string|false $thumbOptions
     * @param array|string|false $posterOptions
     * @return string
     */
    public function sourceSet(ObjectEntity|null $object, array|string|false $thumbOptions = false, array $posterOptions = []): string
    {
        $variant = $this->mobile($object);
        $fallback = $this->poster($object);
        $posterOptions['forceSelf'] = true;

        $fallbackUrl = $this->url($object, $thumbOptions, $posterOptions);
        $fallbackWidth = $this->getStreamWidth($fallback);

        if (!$variant) {
            return sprintf('%s %sw', $fallbackUrl, $fallbackWidth);
        }

        $url = $this->url($variant, $thumbOptions, $posterOptions);
        $width = $this->getStreamWidth($variant);

        return sprintf('%s %sw, %s %sw', $url, $width, $fallbackUrl, $fallbackWidth);
    }

    /**
     * Get the width of the first stream of a media object.
     * If the media object has no streams, or the first stream has no width, return a default value.
     *
     * @param \BEdita\Core\Model\Entity\Media $media Media entity.
     * @return int
     */
    protected function getStreamWidth(Media $media): int
    {
        return Hash::get($media, 'streams.0.width', $this->getConfig('PosterMobile.slotWidth', static::MOBILE_DEFAULT_WIDTH));
    }

    /**
     * Get sizes attribute for image.
     *
     * @return string
     */
    public function sizes(): string
    {
        $maxWidth = $this->getConfig('PosterMobile.maxWidth', static::MOBILE_MAX_WIDTH);
        $slotWidth = $this->getConfig('PosterMobile.slotWidth', static::MOBILE_DEFAULT_WIDTH);

        return sprintf('(max-width: %spx) %spx', $maxWidth, $slotWidth);
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
    public function exists(ObjectEntity|null $object, array $options = []): bool
    {
        return $this->poster($object, $options) !== null;
    }

    /**
     * Return a valid poster for the object, or the Image itself.
     *
     * ### Poster options:
     *
     * - `forceSelf`: restrict checks to the object itself, rather than evaluating its `poster` related objects. Default: `false`
     * - `variant`: poster variant, i.e. priority of the desired poster related object. Default: 0
     * - `fallbackSelf`: whether to use object itself as a fallback in case poster related objects cannot be used. Default: `true`
     *
     * @param \BEdita\Core\Model\Entity\ObjectEntity|null $object Object entity.
     * @param array $options Poster options.
     * @return \BEdita\Core\Model\Entity\ObjectEntity|null
     */
    protected function poster(ObjectEntity|null $object, array $options = []): ObjectEntity|null
    {
        if ($object !== null && $object->has('provider_thumbnail')) {
            return $object;
        }

        foreach ($this->candidates($object, $options) as $media) {
            if ($media->has('media_url')) {
                return $media;
            }
        }

        return null;
    }

    /**
     * Get media URL, optionally generating a thumbnail.
     *
     * @param \BEdita\Core\Model\Entity\Media $media Media entity.
     * @param array|string|false $thumbOptions Thumb options.
     * @param array $fallbackOptions Fallback options.
     * @return string|null
     */
    protected function getMediaUrl(Media $media, array|string|false $thumbOptions, array $fallbackOptions): string|null
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
     * ### Poster options:
     *
     * - `forceSelf`: restrict checks to the object itself, rather than evaluating its `poster` related objects. Default: `false`
     * - `variant`: poster variant, i.e. priority of the desired poster related object. Default: 0
     * - `fallbackSelf`: whether to use object itself as a fallback in case poster related objects cannot be used. Default: `true`
     * - `fallbackStatic`: whether to return a static fallback image if no valid poster can be found. Default: `true`
     * - additional values are passed to {@see \Chialab\FrontendKit\View\Helper\ThumbHelper::url()} (unless `$thumbOptions === false`)
     *
     * @param \BEdita\Core\Model\Entity\ObjectEntity|null $object Object to get thumb for.
     * @param array|string|false $thumbOptions Thumbnail preset name or options, or `false` for no thumbnail. {@see \Chialab\FrontendKit\View\Helper\ThumbHelper::url()}
     * @param array $posterOptions Poster options.
     * @return string|null
     */
    public function url(ObjectEntity|null $object, array|string|false $thumbOptions = 'default', array $posterOptions = []): string|null
    {
        if ($object !== null && $object->has('provider_thumbnail')) {
            return $object->get('provider_thumbnail');
        }

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
     * @param array|string|false $thumbOptions Thumbnail preset name or options, or `false` for no thumbnail. {@see \Chialab\FrontendKit\View\Helper\ThumbHelper::url()}
     * @param array $attributes HTML attributes for `<img>` tag.
     * @param array $posterOptions Poster options. {@see \Chialab\FrontendKit\View\Helper\PosterHelper::url()}
     * @return string
     */
    public function image(ObjectEntity|null $object, array|string|false $thumbOptions = 'default', array $attributes = [], array $posterOptions = []): string
    {
        $url = $this->url($object, $thumbOptions, $posterOptions);
        if (!$url) {
            return '';
        }

        return $this->Html->image($url, $attributes + ['plugin' => false]);
    }

    /**
     * Get position string to manage object-fit CSS property to crop the object poster image.
     *
     * @param \BEdita\Core\Model\Entity\ObjectEntity|null $object Object to whom crop the poster image.
     * @param string $prefix Prefix string to add to the position string tokens for the mobile variant image, if available.
     * @return string position string in the following format: '<x-string> <y-string>'
     */
    public function position(ObjectEntity|null $object, string $variantPrefix = 'mobile-'): string
    {
        $props = [];

        if (isset($object->custom_props)) {
            $props = $object->custom_props;
        }

        if (isset($object->poster)) {
            $poster = collection($object->poster)->first();
            if ($poster !== null) {
                $props = $poster->custom_props;
            }
        }

        $getPositionValues = function ($props, $prefix = '') {
            if (empty($props)) {
                return '';
            }

            if (!empty($props['position'])) {
                return $props['position'];
            }

            if (!isset($props['position_x']) || !isset($props['position_y'])) {
                return '';
            }

            $xPos = '';
            $yPos = '';

            switch ($props['position_x']) {
                case 0:
                    $xPos = 'left';
                    break;
                case 100:
                    $xPos = 'right';
                    break;
                default:
                    $xPos = 'center';
                    break;
            }

            switch ($props['position_y']) {
                case 0:
                    $yPos = 'bottom';
                    break;
                case 100:
                    $yPos = 'top';
                    break;
                default:
                    $yPos = 'center';
                    break;
            }

            return $prefix . $xPos . ' ' . $prefix . $yPos;
        };

        $objPosition = $getPositionValues($props);

        $variant = $this->mobile($object);

        if ($variant) {
            $variantPosition = $getPositionValues($variant->custom_props, $variantPrefix) ?? '';
            $objPosition = $variantPosition . ' ' . $objPosition;
        }

        return $objPosition;
    }

    /**
     * Get poster image aspect ratio
     *
     * @param \BEdita\Core\Model\Entity\ObjectEntity|null $object Object to whom get poster ratio .
     * @return float aspect value
     */
    public function aspect(ObjectEntity|null $object): float
    {
        $streams = [];

        if (isset($object->streams)) {
            $streams = $object->streams;
        }

        if (isset($object->poster)) {
            $poster = collection($object->poster)->first();
            if ($poster !== null) {
                $streams = $poster->streams;
            }
        }

        if (empty($streams)) {
            return 0.0;
        }

        if (empty($streams[0]['width']) || empty($streams[0]['height'])) {
            return 0.0;
        }

        $aspect = $streams[0]['width'] / $streams[0]['height'];

        return $aspect;
    }

    /**
     * Get poster image orientation  string
     *
     * @param \BEdita\Core\Model\Entity\ObjectEntity|null $object Object to whom get poster orientation.
     * @return string aspect string in the following format: 'portrait | landscape | square'
     */
    public function orientation(ObjectEntity|null $object): string
    {
        $aspect = $this->aspect($object);

        if ($aspect == 0.0) {
            return '';
        }

        $aspectlabel = '';

        switch (true) {
            case $aspect > 1:
                $aspectlabel = 'landscape';
                break;
            case $aspect < 1:
                $aspectlabel = 'portrait';
                break;
            default:
                $aspectlabel = 'square';
                break;
        }

        return $aspectlabel;
    }
}
