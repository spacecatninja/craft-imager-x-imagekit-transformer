<?php

/**
 * ImageKit transformer for Imager X
 *
 * @link      https://www.spacecat.ninja
 * @copyright Copyright (c) 2023 André Elvan
 */

namespace SpaceCatNinja\ImagekitTransformer;

use craft\base\Model;
use craft\base\Plugin;

use craft\events\ReplaceAssetEvent;
use craft\services\Assets;
use SpaceCatNinja\ImagekitTransformer\Support\ImagekitHelpers;
use SpaceCatNinja\ImagekitTransformer\Settings;
use SpaceCatNinja\ImagekitTransformer\Transformers\Imagekit;
use yii\base\Event;

class ImagekitTransformer extends Plugin
{
    // Static Properties
    // =========================================================================

    /**
     * @var ImagekitTransformer
     */
    public static $plugin;

    // Public Methods
    // =========================================================================

    public function init(): void
    {
        parent::init();

        self::$plugin = $this;

        // Register services
        $this->setComponents([

        ]);

        // Register transformer with Imager
        Event::on(\spacecatninja\imagerx\ImagerX::class,
            \spacecatninja\imagerx\ImagerX::EVENT_REGISTER_TRANSFORMERS,
            static function(\spacecatninja\imagerx\events\RegisterTransformersEvent $event) {
                $event->transformers['imagekit'] = Imagekit::class;
            }
        );

        // Event listener for clearing caches when an asset is replaced
        if (self::getInstance()->getSettings()->purgeEnabled === true) {
            Event::on(Assets::class, Assets::EVENT_AFTER_REPLACE_ASSET,
                static function(ReplaceAssetEvent $event) {
                    ImagekitHelpers::purgeAsset($event->asset);
                }
            );
        }
    }

    /**
     * @inheritdoc
     */
    protected function createSettingsModel(): ?Model
    {
        return new Settings();
    }

}
