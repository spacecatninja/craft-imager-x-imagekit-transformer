# Imager X ImageKit transformer Changelog

## 3.0.0-beta.1 - 2026-11-20

> [!IMPORTANT]
> Version 3 requires Craft CMS 6, Imager X 7 and PHP 8.5, the config file moves to `config/craft/`, and there are breaking changes for code that uses the plugin's classes. See [UPGRADE.md](UPGRADE.md).

### Added
- Added `ImagekitHelpers::purgeUrl()` and `ImagekitHelpers::PURGE_ENDPOINT`.

### Changed
- The plugin now requires Craft CMS 6, Imager X 7.0.0-beta.2 or later, and PHP 8.5, and uses Craft 6's native plugin APIs. It doesn't need the Yii 2 adapter.
- The config file is now `config/craft/imager-x-imagekit-transformer.php`, and is a plain Laravel config file.
- Classes moved to the `SpaceCatNinja\ImagekitTransformer` namespace, and to Laravel-style folders.
- The transformer is now registered with Imager X's `Transformers` registry.
- The purge event handler is now the `PurgeFromImagekit` listener, for Craft 6's `AssetReplaced` event.
- Purging now posts to ImageKit's purge API with Laravel's HTTP client. The ImageKit SDK still builds and signs the URLs.
- With `useCloudSourcePath`, the cloud source path is now the `root` of the volume's filesystem disk followed by the volume's subpath. Version 2 used the filesystem's subfolder and the volume's subpath.

### Removed
- Removed the `ImagekitTransformer::$plugin` property.

### Fixed
- Fixed a division by zero error when transforming an image whose size couldn't be read, like an external URL with `getExternalImageDimensions` off, with only a `width` or `height`.
- Fixed `allowUpscale` being ignored when it was set in a transform instead of in the config.
- Fixed the size of images transformed with mode `fit` when they're enlarged, and with only a `width` or `height` when they're not.
- Fixed the size of images transformed with mode `croponly` that are larger than the source.
- Fixed `allowUpscale` being ignored for modes `crop` and `stretch`, which ImageKit always enlarges. The size of assets is now clamped to the source, like Imager does.
- Fixed the README example for overriding the profile, which passed `profile` in `transformerParams`. That sent a `profile` transformation to ImageKit instead of changing profile. Use `transformerConfig`.

## 2.0.2 - 2026-11-20

### Fixed
- Fixed a fatal error that occured when transforming with a profile that had `isWebProxy` enabled.
- Fixed an error that could occur when purging an asset that a file path could not be resolved for.

## 2.0.1

### Fixed
- Fixed an issue where profile was not passed to the `ImagekitTransformedImageModel` (fixes #5)

## 2.0.0

### Added
- Added support for Craft 5
