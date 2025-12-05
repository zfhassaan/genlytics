# How to Release Genlytics

This guide explains the process for releasing a new version of Genlytics.

## Prerequisites

1. Ensure you have write access to the repository
2. All tests are passing
3. Documentation is up to date
4. CHANGELOG.md is updated

## Release Process

### Step 1: Prepare the Release

#### 1.1 Update Version Numbers

Update the version in `composer.json`:

```json
{
    "version": "2.0.0"
}
```

#### 1.2 Update CHANGELOG.md

Add a new section at the top of `CHANGELOG.md`:

```markdown
## v2.0.0 - YYYY-MM-DD

### Added
- New feature 1
- New feature 2

### Changed
- Changed behavior 1

### Fixed
- Bug fix 1
- Bug fix 2

### Removed
- Deprecated feature removed
```

#### 1.3 Update README.md

Ensure README.md reflects the latest features and usage examples.

### Step 2: Create Release Branch

```bash
# Ensure you're on dev branch and it's up to date
git checkout dev
git pull origin dev

# Create release branch
git checkout -b release/v2.0.0
```

### Step 3: Final Checks

#### 3.1 Run Tests

```bash
# Run PHPUnit tests (if available)
composer test

# Or run Pest tests
vendor/bin/pest
```

#### 3.2 Check Code Quality

```bash
# Run PHP CS Fixer
vendor/bin/php-cs-fixer fix --dry-run --diff

# Run static analysis (if using PHPStan)
vendor/bin/phpstan analyse
```

#### 3.3 Verify Installation

```bash
# Test installation in a fresh Laravel project
composer require zfhassaan/genlytics:dev-release/v2.0.0
```

### Step 4: Commit Release Changes

```bash
# Stage all changes
git add .

# Commit release preparation
git commit -m "chore: prepare release v2.0.0"
```

### Step 5: Merge to Main

```bash
# Switch to main branch
git checkout main

# Pull latest changes
git pull origin main

# Merge release branch
git merge release/v2.0.0 --no-ff -m "chore: release v2.0.0"

# Push to remote
git push origin main
```

### Step 6: Create Git Tag

```bash
# Create annotated tag
git tag -a v2.0.0 -m "Release version 2.0.0"

# Push tag to remote
git push origin v2.0.0
```

### Step 7: Publish to Packagist

#### 7.1 Update Packagist

If using GitHub webhook:
- Packagist will automatically update when you push the tag
- Verify at: https://packagist.org/packages/zfhassaan/genlytics

#### 7.2 Manual Update (if needed)

1. Go to https://packagist.org/packages/zfhassaan/genlytics
2. Click "Update" button
3. Wait for the update to complete

### Step 8: Create GitHub Release

1. Go to: https://github.com/zfhassaan/genlytics/releases/new
2. Select the tag: `v2.0.0`
3. Title: `v2.0.0 - Release Title`
4. Description: Copy from CHANGELOG.md
5. Attach any release assets if needed
6. Click "Publish release"

### Step 9: Post-Release Tasks

#### 9.1 Update Dev Branch

```bash
# Switch back to dev
git checkout dev

# Merge main into dev (to sync version numbers)
git merge main --no-ff -m "chore: sync dev with v2.0.0 release"

# Push to remote
git push origin dev
```

#### 9.2 Announce Release

- Update project documentation
- Announce on social media (if applicable)
- Update any related blog posts or tutorials

## Version Numbering

Follow [Semantic Versioning](https://semver.org/):

- **MAJOR** (1.0.0): Breaking changes
- **MINOR** (0.1.0): New features, backward compatible
- **PATCH** (0.0.1): Bug fixes, backward compatible

### Examples

- `1.0.0` → `2.0.0`: Major release (breaking changes)
- `1.0.0` → `1.1.0`: Minor release (new features)
- `1.0.0` → `1.0.1`: Patch release (bug fixes)

## Release Checklist

- [ ] Update version in `composer.json`
- [ ] Update `CHANGELOG.md`
- [ ] Update `README.md` if needed
- [ ] Run all tests
- [ ] Check code quality
- [ ] Create release branch
- [ ] Merge to main
- [ ] Create and push git tag
- [ ] Verify Packagist update
- [ ] Create GitHub release
- [ ] Update dev branch
- [ ] Announce release

## Troubleshooting

### Tag Already Exists

If tag already exists:

```bash
# Delete local tag
git tag -d v2.0.0

# Delete remote tag
git push origin :refs/tags/v2.0.0

# Recreate tag
git tag -a v2.0.0 -m "Release version 2.0.0"
git push origin v2.0.0
```

### Packagist Not Updating

1. Check webhook configuration in GitHub
2. Manually update via Packagist dashboard
3. Verify composer.json is valid

### Merge Conflicts

Resolve conflicts in release branch before merging to main:

```bash
git checkout release/v2.0.0
# Resolve conflicts
git add .
git commit -m "chore: resolve merge conflicts"
```

## Hotfix Release

For urgent bug fixes:

```bash
# Create hotfix branch from main
git checkout main
git checkout -b hotfix/v2.0.1

# Make fixes
# ... fix code ...

# Commit and tag
git commit -m "fix: urgent bug fix"
git tag -a v2.0.1 -m "Hotfix v2.0.1"
git push origin v2.0.1

# Merge to both main and dev
git checkout main
git merge hotfix/v2.0.1
git push origin main

git checkout dev
git merge hotfix/v2.0.1
git push origin dev
```

## Automated Release (Future)

Consider setting up automated releases using:

- GitHub Actions
- Semantic Release
- Release Drafter

This can automate version bumping, changelog generation, and release creation.

