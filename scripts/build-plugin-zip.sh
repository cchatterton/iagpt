#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
BUILD_DIR="${ROOT_DIR}/dist"
PLUGIN_DIR="${ROOT_DIR}/analytics-chat-for-wordpress"
ZIP_PATH="${BUILD_DIR}/analytics-chat-for-wordpress.zip"

rm -rf "${BUILD_DIR}"
mkdir -p "${BUILD_DIR}/analytics-chat-for-wordpress"

rsync -a \
  --exclude=".DS_Store" \
  --exclude="*.zip" \
  "${PLUGIN_DIR}/" \
  "${BUILD_DIR}/analytics-chat-for-wordpress/"

(
  cd "${BUILD_DIR}"
  zip -qr "${ZIP_PATH}" "analytics-chat-for-wordpress"
)

echo "${ZIP_PATH}"
