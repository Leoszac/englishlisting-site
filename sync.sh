#!/bin/sh
# Runs on the cPanel server via cron (every 10 min). Pulls the public repo
# Leoszac/englishlisting-site (built output only) and syncs site/ into the
# englishlisting.com document root. No credentials involved.
# IMPORTANT: DEST must stay OUTSIDE ~/public_html, because FertilityList's sync
# runs rsync --delete on public_html and would wipe anything inside it.
set -e
REPO="$HOME/el-deploy"
DEST="$HOME/englishlisting.com"
cd "$REPO"
git fetch -q origin main
REMOTE=$(git rev-parse origin/main)
if [ "$(git rev-parse HEAD)" = "$REMOTE" ] && [ "$(cat "$DEST/.el-deployed" 2>/dev/null)" = "$REMOTE" ]; then
  exit 0
fi
git reset -q --hard origin/main
mkdir -p "$DEST"
if command -v rsync >/dev/null 2>&1; then
  rsync -a --delete --exclude '.well-known/' --exclude 'cgi-bin/' --exclude '.el-deployed' site/ "$DEST/"
else
  cp -a site/. "$DEST/"
fi
echo "$REMOTE" > "$DEST/.el-deployed"
echo "$(date -u +%Y-%m-%dT%H:%M:%SZ) deployed $REMOTE"
