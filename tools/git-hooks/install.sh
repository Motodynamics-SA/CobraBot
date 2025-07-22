#!/bin/bash

# Make the pre-commit hook executable
chmod +x tools/git-hooks/pre-commit

# Create symlink to .git/hooks
ln -sf ../../tools/git-hooks/pre-commit .git/hooks/pre-commit

echo "Git hooks installed successfully!"