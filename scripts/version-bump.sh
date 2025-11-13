#!/bin/bash

# Function to increment version number
increment_version() {
    local version=$1
    local major=$(echo $version | cut -d. -f1)
    local minor=$(echo $version | cut -d. -f2)
    local patch=$(echo $version | cut -d. -f3)

    # Increment patch version
    patch=$((patch + 1))

    echo "$major.$minor.$patch"
}

# Get current version from phone-validator-and-formatter.php
current_version=$(grep -o "Version: [0-9]\+\.[0-9]\+\.[0-9]\+" woocommerce-payforpayment.php | cut -d' ' -f2)
echo "Current version: $current_version"

# Ask for new version
read -p "Enter new version (press enter to auto-increment patch): " new_version

if [ -z "$new_version" ]; then
    new_version=$(increment_version $current_version)
fi

# Update version in phone-validator-and-formatter.php
sed -i '' "s/Version: $current_version/Version: $new_version/" woocommerce-payforpayment.php
# Update the define() statement for PAY4PAYMENT_VERSION in woocommerce-payforpayment.php
sed -i '' "s/define( 'PAY4PAYMENT_VERSION', '$current_version' );/define( 'PAY4PAYMENT_VERSION', '$new_version' );/" woocommerce-payforpayment.php

echo "Updated version to $new_version in woocommerce-payforpayment.php"

# Ask about updating readme.txt
read -p "Do you want to update the stable tag in readme.txt? (y/n): " update_readme

if [ "$update_readme" = "y" ] || [ "$update_readme" = "Y" ]; then
    if [ -f "readme.txt" ]; then
        # Update the stable tag line
        sed -i '' "s/^Stable tag: .*/Stable tag: $new_version/" readme.txt
        echo "Updated stable tag in readme.txt"
    else
        echo "readme.txt not found"
    fi
fi

echo "Version bump completed!"
