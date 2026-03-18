#!/bin/bash
set -e

PLUGIN_FILE="woocommerce-payforpayment.php"
README_FILE="readme.txt"

# --- 1. Check for uncommitted changes ---
if [ -n "$(git status --porcelain)" ]; then
    echo "Error: You have uncommitted changes. Commit or stash them first."
    exit 1
fi

# --- 2. Push all changes ---
echo "Pushing changes to remote..."
git push

# --- 3. Get current plugin version ---
plugin_version=$(grep -E "^[[:space:]]*\*?[[:space:]]*Version:" "$PLUGIN_FILE" | grep -oE '[0-9]+\.[0-9]+\.[0-9]+')
if [ -z "$plugin_version" ]; then
    echo "Error: Could not detect version from $PLUGIN_FILE"
    exit 1
fi
echo "Plugin version: $plugin_version"

# --- 4. Get latest release tag ---
latest_tag=$(gh release list --limit 1 --json tagName --jq '.[0].tagName' 2>/dev/null || echo "")
if [ -z "$latest_tag" ]; then
    echo "No existing releases found. Will create first release."
else
    echo "Latest release:  $latest_tag"
fi

# --- 5. Compare versions ---
if [ "$plugin_version" = "$latest_tag" ]; then
    echo ""
    echo "Error: Plugin version ($plugin_version) matches the latest release ($latest_tag)."
    echo "Run 'make version-bump' first."
    exit 1
fi

echo ""
echo "New release: $plugin_version (previous: ${latest_tag:-none})"
echo ""

# --- 6. Extract changelog entry using Claude CLI ---
echo "Extracting changelog entry with Claude..."
echo ""

changelog=$(claude --print --model claude-haiku-4-5-20251001 -p "Read the file $README_FILE. Extract ONLY the changelog entry for version $plugin_version. Output the raw text content of that single version entry (the bullet points only, no heading, no version number). Do not add any commentary.")

if [ -z "$changelog" ]; then
    echo "Error: Could not extract changelog entry for version $plugin_version"
    exit 1
fi

echo "--- Release notes for $plugin_version ---"
echo ""
echo "$changelog"
echo ""
echo "-------------------------------------------"
echo ""

# --- 7. Let user review and confirm ---
read -p "Create GitHub release $plugin_version with these notes? (y/n): " confirm

if [ "$confirm" != "y" ] && [ "$confirm" != "Y" ]; then
    echo "Release aborted."
    exit 0
fi

# --- 8. Create the tag and GitHub release ---
echo ""
echo "Creating GitHub release $plugin_version..."

gh release create "$plugin_version" \
    --title "$plugin_version" \
    --notes "$changelog" \
    --target "$(git rev-parse HEAD)"

echo ""
echo "Release $plugin_version created successfully!"
echo "View at: $(gh release view "$plugin_version" --json url --jq '.url')"
