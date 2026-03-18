.PHONY: version-bump release

# Bump version number
version-bump:
	@bash scripts/version-bump.sh

# Create a GitHub release from the current plugin version
release:
	@bash scripts/release.sh
