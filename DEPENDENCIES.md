Dependencies Notes

## Locked Versions

### behat/gherkin: 4.10

- **Reason**: Compatibility issues with newer versions
- **Status**: Temporary lock
- **Related Issue**: https://github.com/Behat/Gherkin/issues/xxx
- **Action**: Remove version lock when upstream issue is resolved

## Version Management Policy

All dependencies should use semantic versioning with the `^` prefix to allow compatible updates, except for the locked versions listed above.

## Updating Dependencies

When updating dependencies:

1. Check if any locked versions can be unlocked
2. Verify that all new versions use the `^` prefix
3. Test thoroughly after updates
4. Delete this document if locking new versions
