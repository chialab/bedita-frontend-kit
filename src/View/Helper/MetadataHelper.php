<?php
declare(strict_types=1);

namespace Chialab\FrontendKit\View\Helper;

use BEdita\Core\Model\Entity\Folder;
use BEdita\Core\Model\Entity\ObjectEntity;
use Cake\I18n\DateTime;
use Cake\I18n\I18n;
use Cake\Routing\Router;
use Cake\View\Helper;
use DateTimeInterface;
use function Cake\Collection\collection;

/**
 * Metadata helper.
 *
 * @property \Cake\View\Helper\HtmlHelper $Html
 */
class MetadataHelper extends Helper
{
    /**
     * @inheritDoc
     */
    protected array $helpers = [
        'Html',
        'Chialab/FrontendKit.Poster',
    ];

    /**
     * @inheritDoc
     */
    protected array $_defaultConfig = [
        'publicationVar' => 'publication',
        'objectVar' => 'object',
        'creatorAssoc' => 'has_author',
        'publisherAssoc' => 'has_publisher',
    ];

    /**
     * Get root folder entity from view.
     *
     * @return \BEdita\Core\Model\Entity\Folder|null
     */
    protected function getPublication(): Folder|null
    {
        return $this->getView()->get($this->getConfig('publicationVar')) ?? null;
    }

    /**
     * Get main object entity from view.
     *
     * @return \BEdita\Core\Model\Entity\ObjectEntity|null
     */
    protected function getObject(): ObjectEntity|null
    {
        return $this->getView()->get($this->getConfig('objectVar')) ?? null;
    }

    /**
     * Get page title.
     *
     * @param \BEdita\Core\Model\Entity\ObjectEntity|null The main object.
     * @param \BEdita\Core\Model\Entity\Folder|null The publication folder.
     * @param string|null $separator The separator between object title and publication title.
     * @return string
     */
    public function getTitle(ObjectEntity|null $object = null, Folder|null $publication = null, string $separator = ' | '): string
    {
        $chunks = array_filter([
            $object?->title,
            $publication?->title ?? $publication?->uname,
        ]);

        return strip_tags(join($separator, $chunks));
    }

    /**
     * Get short page title.
     *
     * @param \BEdita\Core\Model\Entity\ObjectEntity|null The main object.
     * @param \BEdita\Core\Model\Entity\Folder|null The publication folder.
     * @return string
     */
    public function getShortTitle(ObjectEntity|null $object = null, Folder|null $publication = null): string
    {
        if ($object !== null && !empty($object->title)) {
            return strip_tags($object->title ?? '');
        }
        if ($publication !== null) {
            return strip_tags($publication->title ?? $publication->uname);
        }

        return '';
    }

    /**
     * Get page description.
     * Use main object description, fallback to publication description.
     *
     * @param \BEdita\Core\Model\Entity\ObjectEntity|null The main object.
     * @param \BEdita\Core\Model\Entity\Folder|null The publication folder.
     * @return string
     */
    public function getDescription(ObjectEntity|null $object = null, Folder|null $publication = null): string
    {
        if ($object !== null) {
            return strip_tags($object->description ?? '');
        }
        if ($publication !== null) {
            return strip_tags($publication->description ?? '');
        }

        return '';
    }

    /**
     * Get creator name.
     * Use main object `has_author` relation, fallback to publication `has_publisher`.
     *
     * @param \BEdita\Core\Model\Entity\ObjectEntity|null The main object.
     * @param \BEdita\Core\Model\Entity\Folder|null The publication folder.
     * @return string|null
     */
    public function getCreator(ObjectEntity|null $object = null, Folder|null $publication = null): string|null
    {
        if ($object !== null) {
            if ($object->hasValue($this->getConfig('creatorAssoc'))) {
                $author = collection($object->get($this->getConfig('creatorAssoc')))->first();
                if ($author !== null) {
                    return strip_tags($author->title ?? '');
                }
            }
        }

        return $this->getPublisher($object, $publication);
    }

    /**
     * Get publisher name.
     * Use main object `has_publisher` relation, fallback to publication `has_publisher`.
     *
     * @param \BEdita\Core\Model\Entity\ObjectEntity|null The main object.
     * @param \BEdita\Core\Model\Entity\Folder|null The publication folder.
     * @return string|null
     */
    public function getPublisher(ObjectEntity|null $object = null, Folder|null $publication = null): string|null
    {
        if ($object !== null) {
            if ($object->hasValue($this->getConfig('publisherAssoc'))) {
                $publisher = collection($object->get($this->getConfig('publisherAssoc')))->first();
                if ($publisher !== null) {
                    return strip_tags($publisher->title ?? '');
                }
            }
        }
        if ($publication !== null) {
            if ($publication->hasValue($this->getConfig('publisherAssoc'))) {
                $publisher = collection($publication->get($this->getConfig('publisherAssoc')))->first();
                if ($publisher !== null) {
                    return strip_tags($publisher->title ?? '');
                }
            }
        }

        return null;
    }

    /**
     * Get poster url.
     * Use main object `poster` relation, fallback to publication `poster`.
     *
     * @param \BEdita\Core\Model\Entity\ObjectEntity|null The main object.
     * @param \BEdita\Core\Model\Entity\Folder|null The publication folder.
     * @return string|null
     */
    public function getPoster(ObjectEntity|null $object = null, Folder|null $publication = null): string|null
    {
        if ($object !== null && $this->Poster->exists($object)) {
            return $this->Poster->url($object, 'default');
        }
        if ($publication !== null && $this->Poster->exists($publication)) {
            return $this->Poster->url($publication, 'default');
        }

        return null;
    }

    /**
     * Get publication status of the main object.
     *
     * @param \BEdita\Core\Model\Entity\ObjectEntity The object.
     * @return string
     */
    public function getPublicationStatus(ObjectEntity $object): string
    {
        if ($object->hasValue('publish_start')) {
            if ((new DateTime($object->get('publish_start')))->greaterThan(DateTime::now())) {
                return 'future';
            }
        }

        if ($object->hasValue('publish_end')) {
            if ((new DateTime($object->get('publish_end')))->greaterThan(DateTime::now())) {
                return 'expired';
            }
        }

        return 'published';
    }

    /**
     * Format a date as ISO 8601, as expected by Dublin Core and Open Graph meta tags.
     *
     * @param \DateTimeInterface|null $date The date.
     * @return string|null
     */
    protected static function formatDate(DateTimeInterface|null $date): string|null
    {
        return $date?->format(DateTimeInterface::ATOM);
    }

    /**
     * Dublin Core meta tags generator.
     *
     * @param array $data Data to set.
     * @param \BEdita\Core\Model\Entity\ObjectEntity|null The main object.
     * @param \BEdita\Core\Model\Entity\Folder|null The publication folder.
     * @return string
     */
    public function metaDc(array $data = [], ObjectEntity|null $object = null, Folder|null $publication = null): string
    {
        $object = $object ?? $this->getObject();
        $publication = $publication ?? $this->getPublication();

        $output = '<link rel="schema.DC" href="http://purl.org/dc/elements/1.1/" />';
        $defaults = [
            'DC.format' => 'text/html',
            'DC.title' => htmlspecialchars($this->getShortTitle($object, $publication)),
            'DC.description' => htmlspecialchars($this->getDescription($object, $publication)),
            'DC.creator' => htmlspecialchars($this->getCreator($object, $publication) ?? ''),
            'DC.publisher' => htmlspecialchars($this->getPublisher($object, $publication) ?? ''),
            'DC.rights' => htmlspecialchars($this->getPublisher($object, $publication) ?? ''),
            'DC.license' => $object->license ?? $publication?->license ?? null,
        ];
        if ($object !== null) {
            $defaults = [
                'DC.date' => static::formatDate($object->publish_start ?? $object->created),
                'DC.created' => static::formatDate($object->created),
                'DC.modified' => static::formatDate($object->modified),
                'DC.type' => $object->type,
                'DC.identifier' => $object->uname,
                'DC.language' => $object->lang ?? I18n::getLocale(),
            ] + $defaults;
        }

        $data = $data + $defaults;
        foreach ($data as $key => $value) {
            if ($value !== null) {
                $output .= $this->Html->meta($key, $value);
            }
        }

        return $output;
    }

    /**
     * Open graph meta tags generator.
     *
     * @param array $data Data to set.
     * @param \BEdita\Core\Model\Entity\ObjectEntity|null The main object.
     * @param \BEdita\Core\Model\Entity\Folder|null The publication folder.
     * @return string
     */
    public function metaOg(array $data = [], ObjectEntity|null $object = null, Folder|null $publication = null): string
    {
        $object = $object ?? $this->getObject();
        $publication = $publication ?? $this->getPublication();

        $output = '';
        // Keyed by property, so that `+` merges by property instead of overwriting by list position.
        $defaults = [
            'og:url' => ['property' => 'og:url', 'content' => Router::url(null, true)],
            'og:title' => ['property' => 'og:title', 'content' => htmlspecialchars($this->getShortTitle($object, $publication))],
            'og:description' => ['property' => 'og:description', 'content' => htmlspecialchars($this->getDescription($object, $publication))],
            'og:image' => ['property' => 'og:image', 'content' => $this->getPoster($object, $publication)],
            'og:site_name' => ['property' => 'og:site_name', 'content' => htmlspecialchars($this->getPublisher(null, $publication) ?? '')],
        ];
        if ($object !== null) {
            $defaults = [
                'og:type' => ['property' => 'og:type', 'content' => $object->type],
                'og:updated_time' => ['property' => 'og:updated_time', 'content' => static::formatDate($object->modified)],
            ] + $defaults;
        }

        $data = $data + $defaults;
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                if (!empty($value['content'])) {
                    $output .= $this->Html->meta($value);
                }
            } elseif ($value !== null) {
                $output .= $this->Html->meta($key, $value);
            }
        }

        return $output;
    }

    /**
     * Twitter meta tags generator.
     *
     * @param array $data Data to set.
     * @param \BEdita\Core\Model\Entity\ObjectEntity|null The main object.
     * @param \BEdita\Core\Model\Entity\Folder|null The publication folder.
     * @return string
     */
    public function metaTwitter(array $data = [], ObjectEntity|null $object = null, Folder|null $publication = null): string
    {
        $object = $object ?? $this->getObject();
        $publication = $publication ?? $this->getPublication();
        $poster = $this->getPoster($object, $publication);

        $output = '';
        $defaults = [
            'twitter:title' => htmlspecialchars($this->getShortTitle($object, $publication)),
            'twitter:description' => htmlspecialchars($this->getDescription($object, $publication)),
            'twitter:card' => $poster ? 'summary_large_image' : null,
            'twitter:image' => $poster,
        ];

        $data = $data + $defaults;
        foreach ($data as $key => $value) {
            if ($value !== null) {
                $output .= $this->Html->meta($key, $value);
            }
        }

        return $output;
    }
}
