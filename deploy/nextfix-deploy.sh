#!/usr/bin/env bash
set -Eeuo pipefail

# This script is called by the queue worker with fixed arguments from the
# exact Git commit selected by the server. Keep it outside the web document root.
COMMIT="${1:-}"
VERSION="${2:-}"
RUN_ID="${3:-}"

[[ "$COMMIT" =~ ^[a-f0-9]{40}$ ]] || { echo "Invalid immutable commit"; exit 20; }
[[ "$VERSION" =~ ^[0-9A-Za-z._-]+$ ]] || { echo "Invalid version"; exit 21; }
[[ "$RUN_ID" =~ ^[0-9]+$ ]] || { echo "Invalid deployment run"; exit 22; }

: "${NEXTFIX_REPOSITORY:?Set NEXTFIX_REPOSITORY to the private bare/deploy checkout}"
: "${NEXTFIX_RELEASES:?Set NEXTFIX_RELEASES outside public_html}"
: "${NEXTFIX_SHARED:?Set NEXTFIX_SHARED outside public_html}"
: "${NEXTFIX_CURRENT:?Set NEXTFIX_CURRENT symlink path}"
: "${NEXTFIX_BACKUP_SCRIPT:?Set NEXTFIX_BACKUP_SCRIPT to a reviewed executable backup command}"
: "${NEXTFIX_HEALTH_URL:?Set NEXTFIX_HEALTH_URL to the production health URL}"

for command in git php composer curl flock; do command -v "$command" >/dev/null || { echo "Missing command: $command"; exit 23; }; done
[[ -x "$NEXTFIX_BACKUP_SCRIPT" ]] || { echo "Backup script is not executable"; exit 24; }
[[ -f "$NEXTFIX_SHARED/.env" ]] || { echo "Shared .env missing"; exit 25; }

mkdir -p "$NEXTFIX_RELEASES" "$NEXTFIX_SHARED/storage" "$NEXTFIX_SHARED/backups"
exec 9>"$NEXTFIX_SHARED/deploy.lock"
flock -n 9 || { echo "Another deployment is running"; exit 26; }

echo "stage=preflight version=$VERSION run=$RUN_ID"
git -C "$NEXTFIX_REPOSITORY" fetch --quiet origin "$COMMIT"
ACTUAL_COMMIT="$(git -C "$NEXTFIX_REPOSITORY" rev-parse FETCH_HEAD)"
[[ "$ACTUAL_COMMIT" == "$COMMIT" ]] || { echo "Fetched commit mismatch"; exit 27; }

echo "stage=backup"
"$NEXTFIX_BACKUP_SCRIPT" "$NEXTFIX_SHARED/backups" "$RUN_ID"

RELEASE="$NEXTFIX_RELEASES/${VERSION}-${COMMIT:0:12}"
[[ ! -e "$RELEASE" ]] || { echo "Release directory already exists"; exit 28; }
mkdir -p "$RELEASE"
git -C "$NEXTFIX_REPOSITORY" archive "$COMMIT" | tar -x -C "$RELEASE"
ln -s "$NEXTFIX_SHARED/.env" "$RELEASE/.env"
rm -rf "$RELEASE/storage"
ln -s "$NEXTFIX_SHARED/storage" "$RELEASE/storage"

echo "stage=dependencies"
composer install --working-dir="$RELEASE" --no-dev --prefer-dist --no-interaction --optimize-autoloader

echo "stage=migrate"
php "$RELEASE/artisan" down --retry=60
php "$RELEASE/artisan" migrate --force

echo "stage=activate"
ln -sfn "$RELEASE" "$NEXTFIX_CURRENT.next"
mv -Tf "$NEXTFIX_CURRENT.next" "$NEXTFIX_CURRENT"
php "$RELEASE/artisan" optimize
php "$RELEASE/artisan" queue:restart
php "$RELEASE/artisan" up

echo "stage=health"
curl --fail --silent --show-error --max-time 20 "$NEXTFIX_HEALTH_URL" >/dev/null
printf '{"version":"%s","commit":"%s","deployed_at":"%s"}\n' "$VERSION" "$COMMIT" "$(date -u +%Y-%m-%dT%H:%M:%SZ)" > "$NEXTFIX_SHARED/current.json"
echo "stage=complete version=$VERSION commit=$COMMIT"
