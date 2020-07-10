<?php

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace AppBundle\EventListener;

use Pimcore\Event\AssetEvents;
use Pimcore\Event\FrontendEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Aws\S3\S3Client;

class S3Listener implements EventSubscriberInterface
{
    private $s3BaseUrl;
    private $s3TmpUrlPrefix;
    private $s3AssetUrlPrefix;

    public function __construct()
    {
        $this->s3BaseUrl = getenv('s3CloudfrontURL');
        $this->s3TmpUrlPrefix = $this->s3BaseUrl . $this->replaceCloudfront(PIMCORE_TEMPORARY_DIRECTORY);
        $this->s3AssetUrlPrefix = $this->s3BaseUrl . $this->replaceCloudfront(PIMCORE_ASSET_DIRECTORY);

    }

    private function replaceCloudfront(string $path) : string  {
        return str_replace("s3://".getenv('s3BucketName'), "", $path);
    }

    public static function getSubscribedEvents()
    {
        return [
            //KernelEvents::REQUEST => 'initS3Wrappers',
            FrontendEvents::ASSET_IMAGE_THUMBNAIL => 'onFrontendPathThumbnail',
            FrontendEvents::ASSET_DOCUMENT_IMAGE_THUMBNAIL => 'onFrontendPathThumbnail',
            FrontendEvents::ASSET_VIDEO_IMAGE_THUMBNAIL => 'onFrontendPathThumbnail',
            FrontendEvents::ASSET_VIDEO_THUMBNAIL => 'onFrontendPathThumbnail',
            FrontendEvents::ASSET_PATH => 'onFrontEndPathAsset',
            AssetEvents::IMAGE_THUMBNAIL => 'onAssetThumbnailCreated',
            AssetEvents::VIDEO_IMAGE_THUMBNAIL => 'onAssetThumbnailCreated',
            AssetEvents::DOCUMENT_IMAGE_THUMBNAIL => 'onAssetThumbnailCreated',
        ];
    }

    public function onFrontendPathThumbnail(GenericEvent $event) {
        // rewrite the path for the frontend

        $fileSystemPath = $event->getSubject()->getFileSystemPath();

        $cacheKey = "thumb_s3_" . md5($fileSystemPath);
        $path = \Pimcore\Cache::load($cacheKey);

        if(!$path) {
            if(!file_exists($fileSystemPath)) {
                // the thumbnail doesn't exist yet, so we need to create it on request -> Thumbnail controller plugin
                // the first time the path is displayed without the CLOUD FRONT URL, because otherwise the thumbnail
                // cannot be created --> Pimcore Issue!
                $path = str_replace(PIMCORE_TEMPORARY_DIRECTORY."/image-thumbnails", "", $fileSystemPath);
            } else {

                //without CDN:
                //$path = str_replace(PIMCORE_TEMPORARY_DIRECTORY . "/image-thumbnails/", $this->s3TmpUrlPrefix . "/", $fileSystemPath);

                //with CDN:
                $path = str_replace(PIMCORE_TEMPORARY_DIRECTORY."/", $this->s3TmpUrlPrefix . "/", $fileSystemPath);
            }
        }

        $event->setArgument('frontendPath',$path);
    }

    public function onAssetThumbnailCreated(GenericEvent $event)
    {
        $thumbnail = $event->getSubject();

        $fsPath = $thumbnail->getFileSystemPath();

        if ($fsPath && $event->getArgument("generated")) {
            $cacheKey = "thumb_s3_" . md5($fsPath);

            \Pimcore\Cache::remove($cacheKey);
        }
    }

    public function onFrontEndPathAsset(GenericEvent $event) {

        $asset = $event->getSubject();
        $path = str_replace(PIMCORE_ASSET_DIRECTORY . "/", $this->s3AssetUrlPrefix . "/", $asset->getFileSystemPath());

        $event->setArgument('frontendPath',$path);
    }
}