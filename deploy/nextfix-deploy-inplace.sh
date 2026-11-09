#!/usr/bin/env bash
set -Eeuo pipefail

COMMIT="${1:-}"
VERSION="${2:-}"
RUN_ID="${3:-}"
REPOSITORY="${NEXTFIX_REPOSITORY:-}"
REMOTE="${NEXTFIX_GIT_REMOTE:-origin}"
BRANCH="${NEXTFIX_BRANCH:-main}"
STATE_FILE="${NEXTFIX_RELEASE_STATE:-$REPOSITORY/storage/app/releases/current.json}"

[[ "$COMMIT" =~ ^[a-f0-9]{40}$ ]] || { echo "Invalid immutable commit"; exit 20; }
[[ "$VERSION" =~ ^[0-9A-Za-z._-]+$ ]] || { echo "Invalid version"; exit 21; }
[[ "$RUN_ID" =~ ^[0-9]+$ ]] || { echo "Invalid deployment run"; exit 22; }
[[ -n "$REPOSITORY" && -d "$REPOSITORY/.git" ]] || { echo "Git repository is unavailable"; exit 23; }

# cPanel web workers often omit HOME even though the interactive Terminal has
# it. Composer requires a writable home directory for cache and configuration.
export HOME="${HOME:-$(dirname "$REPOSITORY")}"
export COMPOSER_HOME="${COMPOSER_HOME:-$REPOSITORY/storage/app/composer}"
mkdir -p "$COMPOSER_HOME"

for command in git php; do
    command -v "$command" >/dev/null || { echo "Missing command: $command"; exit 24; }
done

if command -v composer >/dev/null 2>&1; then
    COMPOSER=("$(command -v composer)")
elif [[ -x /opt/cpanel/composer/bin/composer ]]; then
    COMPOSER=(/opt/cpanel/composer/bin/composer)
elif [[ -f /opt/cpanel/composer/bin/composer ]]; then
    COMPOSER=("$(command -v php)" /opt/cpanel/composer/bin/composer)
elif [[ -x /usr/local/bin/composer ]]; then
    COMPOSER=(/usr/local/bin/composer)
else
    echo "Composer executable was not found in the web process environment."
    echo "Configure COMPOSER_HOME or ask the host for the cPanel Composer path."
    exit 24
fi

cd "$REPOSITORY"
echo "stage=preflight version=$VERSION run=$RUN_ID"

# Shared hosting permission commands can change executable bits on tracked
# placeholder files. Ignore mode-only differences but continue to block any
# actual tracked content changes.
git config core.fileMode false
git diff --quiet && git diff --cached --quiet || {
    echo "Tracked server files contain local changes; deployment stopped to protect them."
    exit 25
}

echo "stage=fetch"
git fetch --quiet "$REMOTE" "$BRANCH"
REMOTE_COMMIT="$(git rev-parse "$REMOTE/$BRANCH")"
[[ "$REMOTE_COMMIT" == "$COMMIT" ]] || { echo "Remote commit changed; request the update again"; exit 26; }
git merge-base --is-ancestor HEAD "$COMMIT" || { echo "Update is not a fast-forward"; exit 27; }

echo "stage=activate"
git merge --ff-only "$COMMIT"

echo "stage=dependencies"
"${COMPOSER[@]}" install --no-dev --prefer-dist --no-interaction --optimize-autoloader

echo "stage=migrate"
php artisan migrate --force

echo "stage=storage"
if [[ ! -L public/storage && ! -e public/storage ]]; then
    php artisan storage:link
fi

echo "stage=optimize"
php artisan optimize:clear
php artisan optimize

mkdir -p "$(dirname "$STATE_FILE")"
STATE_TMP="${STATE_FILE}.tmp.$$"
printf '{"version":"%s","commit":"%s","deployed_at":"%s"}\n' \
    "$VERSION" "$COMMIT" "$(date -u +%Y-%m-%dT%H:%M:%SZ)" > "$STATE_TMP"
mv -f "$STATE_TMP" "$STATE_FILE"

echo "stage=complete version=$VERSION commit=$COMMIT"
