# Imager X ImageKit transformer Changelog

## 2.0.3 - Unreleased

### Fixed
- Fixed a division by zero error when transforming an image whose size couldn't be read, like an external URL with `getExternalImageDimensions` off, with only a `width` or `height`.

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
