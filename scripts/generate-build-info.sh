#!/bin/bash
# Generate build info for version tracking

# Get git information if available
if [ -d ".git" ]; then
    GIT_BRANCH=$(git branch --show-current 2>/dev/null || echo "unknown")
    GIT_COMMIT=$(git rev-parse --short HEAD 2>/dev/null || echo "unknown")
else
    GIT_BRANCH="${GIT_BRANCH:-unknown}"
    GIT_COMMIT="${GIT_COMMIT:-unknown}"
fi

# Determine system branch from git branch
SYSTEM_BRANCH="main"
if [[ "$GIT_BRANCH" == *"dev"* ]] || [[ "$GIT_BRANCH" == *"Dev"* ]]; then
    SYSTEM_BRANCH="Dev"
elif [[ "$GIT_BRANCH" != "main" ]] && [[ "$GIT_BRANCH" != "master" ]]; then
    SYSTEM_BRANCH="Dev"
fi

# Get version from config.php
VERSION=$(grep "ADMIN_PANEL_VERSION" dcs-stats/site-config/config.php | sed -E "s/.*'([^']+)'.*/\1/" || echo "V0.0.04")

# Create build info file
cat > dcs-stats/.build-info.json <<EOF
{
  "version": "$VERSION",
  "branch": "$SYSTEM_BRANCH",
  "git_branch": "$GIT_BRANCH",
  "build_date": "$(date -u +%Y-%m-%dT%H:%M:%SZ)",
  "commit": "$GIT_COMMIT"
}
EOF

echo "Build info generated:"
cat dcs-stats/.build-info.json