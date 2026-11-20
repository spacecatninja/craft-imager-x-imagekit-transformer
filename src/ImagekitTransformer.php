<?php

/**
 * ImageKit transformer for Imager X
 *
 * @link      https://www.spacecat.ninja
 * @copyright Copyright (c) 2026 André Elvan
 */

namespace SpaceCatNinja\ImagekitTransformer;

use CraftCms\Cms\Asset\Events\AssetReplaced;
use CraftCms\Cms\Plugin\Plugin;
use CraftCms\Cms\Plugin\PluginSettings;
use Illuminate\Support\Facades\Event;
use SpaceCatNinja\ImagekitTransformer\Listeners\PurgeFromImagekit;
use SpaceCatNinja\ImagekitTransformer\Transformers\Imagekit;
use SpaceCatNinja\ImagerX\Transformers\Transformers;

class ImagekitTransformer extends Plugin
{
    // Public Methods
    // =========================================================================

    public function register(): void
    {
        parent::register();

        $this->app->singleton(Settings::class, fn() => $this->getSettings());
    }

    public function boot(Transformers $transformers): void
    {
        // Register transformer with Imager X
        $transformers->register('imagekit', Imagekit::class);

        // Event listener for clearing caches when an asset is replaced
        if (app(Settings::class)->purgeEnabled === true) {
            Event::listen(AssetReplaced::class, [PurgeFromImagekit::class, 'handleAssetReplaced']);
        }
    }

    // Protected Methods
    // =========================================================================

    protected static function createSettings(): ?PluginSettings
    {
        return new Settings();
    }
}
