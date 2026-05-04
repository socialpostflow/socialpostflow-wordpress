# Remove vendor directory
cd ..
rm -rf vendor

# Run composer to only install non-dev dependencies
composer install --no-dev

# Build ZIP file, excluding non-Plugin files
rm social-post-flow.zip
zip -r social-post-flow.zip . \
-x "*.git*" \
-x ".devcontainer/*" \
-x ".scripts/*" \
-x ".wordpress-org/*" \
-x "node_modules/*" \
-x "tests/*" \
-x "vendor/*" \
-x "*.distignore" \
-x "*.env.*" \
-x ".gitignore" \
-x "*.md" \
-x "*.yml" \
-x "composer.json" \
-x "composer.lock" \
-x "*.xml" \
-x "*.neon" \
-x "*.dist" \
-x "*.example" \
-x "*.DS_Store" \
-x "*codeception.*" \
-x "config.codekit3" \

# Run composer to install dev dependencies, returning enviornment back to original state
composer update