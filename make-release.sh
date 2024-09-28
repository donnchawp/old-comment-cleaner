#!/bin/bash

# Name of the zip file
ZIP_FILE="old-comment-cleaner.zip"

# Create a zip file excluding .gitignore and this script
cd ../ && zip -r "$ZIP_FILE" old-comment-cleaner -x "old-comment-cleaner/.gitignore" -x "old-comment-cleaner/make-release.sh" -x "old-comment-cleaner/.git/*"

echo "Plugin files have been zipped into $ZIP_FILE"