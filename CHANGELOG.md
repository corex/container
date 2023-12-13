# Changelog

## 2.2.0

### Added
- Option to bind class by name of class.
- Option to bind class by implemented interface.

## 2.1.0

### Added
- Added option to set resolved object on container builder.

## 2.0.0

### Changes
- Removed travis support in favor of github actions.
- Rewritten package from scratch to be more strict.
- Raised php to ^8.1

## 1.1.1

### Fixed
- Throw correct exception message on get() when id not found.

## 1.1.0

### Added
- Added bindIf(), bindSingletonIf(), bindSharedIf() to Container::class.
- Added getAbstract() to Definition::class.

## 1.0.0

### Added
- Initial release.
