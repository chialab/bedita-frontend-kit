<?php
declare(strict_types=1);

namespace Chialab\FrontendKit\View\Helper;

use Cake\View\Helper;
use Cake\Collection\Collection;
use BEdita\Core\Model\Entity\ObjectEntity;

/**
 * Poster helper
 * @property-read \Chialab\FrontendKit\View\Helper\ThumbHelper $Thumb
 */
class PosterHelper extends Helper
{
    /**
     * @inheritdoc
     */
    public $helpers = ['Thumb'];

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
            return ThumbHelper::isValidImage($object);
        }

        $posters = new Collection($object->get('poster') ?: []);
        if ($variant > 0) {
            $posters = $posters->skip($variant);
        }

        $poster = $posters->first();
        if (!ThumbHelper::isValidImage($poster)) {
            return $this->check($object, true);
        }

        return true;
    }

    /**
     * Get URL for poster image.
     * @deprecated
     *
     * @param \BEdita\Core\Model\Entity\ObjectEntity|null $object Object to get poster for.
     * @param bool $forceSelf Only use object itself, do not use its `poster` relations.
     * @param int $variant Use `poster` at given index, if greater than zero.
     * @return string|null
     */
    public function getUrl(?ObjectEntity $object, bool $forceSelf = false, int $variant = 0): ?string
    {
        if ($object === null || $forceSelf) {
            return $this->Thumb->url($object);
        }

        $posters = new Collection($object->get('poster') ?: []);
        if ($variant > 0) {
            $posters = $posters->skip($variant);
        }

        $poster = $posters->first();
        if (!ThumbHelper::isValidImage($poster)) {
            return $this->Thumb->url($object);
        }

        return $this->Thumb->url($poster);
    }

    /**
     * Get URL for poster image.
     *
     * @param \BEdita\Core\Model\Entity\ObjectEntity|null $object Object to get thumb for.
     * @param array $preset Thumb options.
     * @param bool $forceSelf Only use object itself, do not use its `poster` relations.
     * @param int $variant Use `poster` at given index, if greater than zero.
     * @return string|null
     */
    public function url(?ObjectEntity $object, array $preset = [], bool $forceSelf = false, int $variant = 0): ?string
    {
        if ($object === null || $forceSelf) {
            return $this->Thumb->url($object, $preset);
        }

        $posters = new Collection($object->get('poster') ?: []);
        if ($variant > 0) {
            $posters = $posters->skip($variant);
        }

        $poster = $posters->first();
        if (!ThumbHelper::isValidImage($poster)) {
            return $this->Thumb->url($object, $preset);
        }

        return $this->Thumb->url($poster, $preset);
    }

    /**
     * Create image tag for poster.
     *
     * @param \BEdita\Core\Model\Entity\ObjectEntity|null $object Object to get thumb for.
     * @param array $preset Thumb options.
     * @param array $options Html options.
     * @param bool $forceSelf Only use object itself, do not use its `poster` relations.
     * @param int $variant Use `poster` at given index, if greater than zero.
     * @return string|null
     */
    public function image(?ObjectEntity $object, array $preset = [], array $options = [], bool $forceSelf = false, int $variant = 0): string
    {
        $url = $this->url($object, $preset, $forceSelf, $variant);
        if (!$url) {
            return '';
        }

        return $this->Html->image($url, $options);
    }
}
