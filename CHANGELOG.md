# Imager X ImageKit transformer Changelog

## 2.0.3 - 2026-11-20

### Fixed
- Fixed a division by zero error when transforming an image whose size couldn't be read, like an external URL with `getExternalImageDimensions` off, with only a `width` or `height`.
- Fixed `allowUpscale` being ignored when it was set in a transform instead of in the config, and for modes `crop` and `stretch`, which ImageKit always enlarges. The size of assets is now clamped to the source, like Imager does.
- Fixed the size of images transformed with mode `fit` when they're enlarged, and with only a `width` or `height` when they're not, and with mode `croponly` when they're larger than the source.
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
