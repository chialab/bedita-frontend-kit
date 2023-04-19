<?php
declare(strict_types=1);

namespace Chialab\FrontendKit\View\Helper;

use BEdita\Core\Model\Entity\Media;
use Cake\View\Helper;

/**
 * Download helper
 *
 * @property \Cake\View\Helper\UrlHelper $Url
 */
class DownloadHelper extends Helper
{
    /**
     * @inheritDoc
     */
    protected $helpers = ['Url'];

    /**
     * @inheritDoc
     */
    protected $_defaultConfig = [
        'route' => ['_name' => 'pages:download:filename'],
    ];

    /**
     * Get download URL for a media.
     *
     * @param \BEdita\Core\Model\Entity\Media $media The media.
     * @return string
     */
    public function url(Media $media): string
    {
        return $this->Url->build((array)$this->getConfigOrFail('route') + [
            'uname' => $media->uname,
            'filename' => $media->streams[0]->file_name,
        ]);
    }
}
