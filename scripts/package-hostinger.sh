#!/usr/bin/env bash
# Build a Hostinger archive from tracked files only, for one verified Git commit.
set -euo pipefail
cd "$(git rev-parse --show-toplevel)"
revision="${1:-$(git rev-parse HEAD)}"
if [[ ! "$revision" =~ ^[0-9a-f]{40}$ ]]; then
  echo "ERROR: expected a complete 40-character Git commit SHA" >&2
  exit 1
fi
if [[ "$(git rev-parse HEAD)" != "$revision" ]]; then
  echo "ERROR: checked-out commit is not the requested release revision" >&2
  exit 1
fi
short="${revision:0:12}"
filename="PlantCore_Service_Desk_${short}.zip"
build_dir="$(mktemp -d)"
trap 'rm -rf "$build_dir"' EXIT
mkdir -p "$build_dir/apps/service-desk" "$build_dir/public_html/service-desk"
while IFS= read -r -d '' file; do
  case "$file" in
    .github/*|tests/*|deploy/*|scripts/*|node_modules/*|package.json|package-lock.json|phpunit.xml|.env|.env.*|*.sqlite|*.log) continue ;;
  esac
  mkdir -p "$build_dir/apps/service-desk/$(dirname "$file")"
  cp -- "$file" "$build_dir/apps/service-desk/$file"
done < <(git ls-files -z)
mkdir -p "$build_dir/apps/service-desk/deploy"
cp deploy/deploy-hostinger.sh "$build_dir/apps/service-desk/deploy/"
cp -a deploy/hostinger-public/. "$build_dir/public_html/service-desk/"
printf '%s\n' "$revision" > "$build_dir/apps/service-desk/DEPLOYED_COMMIT"
test -s "$build_dir/apps/service-desk/composer.lock"
test -s "$build_dir/apps/service-desk/artisan"
test -s "$build_dir/public_html/service-desk/index.php"
test -s "$build_dir/public_html/service-desk/assets/vendor/adminlte.min.css"
test ! -e "$build_dir/apps/service-desk/.env"
test ! -d "$build_dir/apps/service-desk/vendor"
test ! -e "$build_dir/apps/service-desk/database/database.sqlite"
(
  cd "$build_dir"
  zip -qr "$OLDPWD/$filename" apps public_html
)
unzip -t "$filename" > /dev/null
if unzip -Z -1 "$filename" | grep -Eq '(^|/)\\.env($|/)|^apps/service-desk/vendor/|^apps/service-desk/database/.*\\.sqlite$'; then
  echo "ERROR: secret or runtime data found in package" >&2
  exit 1
fi
sha256sum "$filename" > "$filename.sha256"
echo "Release archive: $filename"
echo "Commit: $revision"
cat "$filename.sha256"
