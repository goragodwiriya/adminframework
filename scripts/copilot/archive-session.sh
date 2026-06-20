#!/usr/bin/env bash
set -euo pipefail

script_dir="$(CDPATH= cd -- "$(dirname -- "$0")" && pwd)"
repo_root="$(CDPATH= cd -- "$script_dir/../.." && pwd)"

usage() {
  cat <<'EOF'
Usage: scripts/copilot/archive-session.sh [session-log-dir] [archive-root]

Archive a GitHub Copilot debug-log session directory into the repository-local
.copilot/transcripts/ store. If session-log-dir is omitted, the script uses
$VSCODE_TARGET_SESSION_LOG.
EOF
}

source_dir="${1:-${VSCODE_TARGET_SESSION_LOG:-}}"
archive_root="${2:-$repo_root/.copilot/transcripts}"

if [[ -z "$source_dir" ]]; then
  usage >&2
  exit 1
fi

if [[ ! -d "$source_dir" ]]; then
  echo "Session log directory not found: $source_dir" >&2
  exit 1
fi

if [[ ! -f "$source_dir/main.jsonl" ]]; then
  echo "Missing main.jsonl in: $source_dir" >&2
  exit 1
fi

session_id="$(basename -- "$source_dir")"
archive_date="$(date +%F)"
archive_dir="$archive_root/$archive_date/$session_id"
archived_at="$(date +%Y-%m-%dT%H:%M:%S%z)"
models_file="missing"

mkdir -p "$archive_dir"
cp "$source_dir/main.jsonl" "$archive_dir/main.jsonl"

if [[ -f "$source_dir/models.json" ]]; then
  cp "$source_dir/models.json" "$archive_dir/models.json"
  models_file="models.json"
fi

cat > "$archive_dir/session.txt" <<EOF
repo=$(basename -- "$repo_root")
repo_root=$repo_root
session_id=$session_id
source_dir=$source_dir
archived_at=$archived_at
main_jsonl=main.jsonl
models_json=$models_file
EOF

printf '%s\n' "$archive_dir"