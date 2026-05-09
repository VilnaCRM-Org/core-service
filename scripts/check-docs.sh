#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
DOCS_DIR="${ROOT_DIR}/docs"

required_docs=(
  "README.md"
  "main.md"
  "getting-started.md"
  "design-and-architecture.md"
  "developer-guide.md"
  "api-endpoints.md"
  "testing.md"
  "deployment.md"
  "database.md"
  "troubleshooting.md"
  "security.md"
  "operational.md"
  "onboarding.md"
  "advanced-configuration.md"
  "performance.md"
  "release-notes.md"
  "versioning.md"
  "community-and-support.md"
  "legal-and-licensing.md"
  "glossary.md"
  "user-guide.md"
)

failures=0

fail() {
  printf 'docs-check: %s\n' "$1" >&2
  failures=$((failures + 1))
}

for doc in "${required_docs[@]}"; do
  path="${DOCS_DIR}/${doc}"

  if [[ ! -s "${path}" ]]; then
    fail "required document is missing or empty: docs/${doc}"
    continue
  fi

  if ! head -n 1 "${path}" | grep -Eq '^# '; then
    fail "document must start with an H1 heading: docs/${doc}"
  fi
done

if ! grep -Eq '\]\((\./)?docs/README\.md\)' "${ROOT_DIR}/README.md"; then
  fail "root README.md must link to docs/README.md"
fi

if ! grep -Eq '^docs:.*## ' "${ROOT_DIR}/Makefile"; then
  fail "Makefile must expose a documented docs target"
fi

tmp_ws_file="$(mktemp)"
trap 'rm -f "${tmp_ws_file}"' EXIT
if grep -RIn '[[:blank:]]$' "${DOCS_DIR}" "${ROOT_DIR}/README.md" "${ROOT_DIR}/Makefile" >"${tmp_ws_file}"; then
  cat "${tmp_ws_file}" >&2
  fail "documentation files must not contain trailing whitespace"
fi

link_sources=(
  "${ROOT_DIR}/README.md"
  "${DOCS_DIR}"/*.md
)

while IFS=$'\t' read -r file target; do
  [[ -n "${target}" ]] || continue

  case "${target}" in
    http://*|https://*|mailto:*|tel:*|'#'*)
      continue
      ;;
  esac

  target="${target%%#*}"
  target="${target%%\?*}"
  [[ -n "${target}" ]] || continue

  if [[ "${target}" = /* ]]; then
    resolved="$(realpath -m "${ROOT_DIR}${target}")"
  else
    resolved="$(realpath -m "$(dirname "${file}")/${target}")"
  fi

  case "${resolved}" in
    "${ROOT_DIR}"/*) ;;
    *)
      fail "local link points outside repository: ${file} -> ${target}"
      continue
      ;;
  esac

  if [[ ! -e "${resolved}" ]]; then
    fail "broken local Markdown link: ${file} -> ${target}"
  fi
done < <(
  perl -0ne 'while (/\[[^\]]+\]\(([^)\s]+)\)/g) { print "$ARGV\t$1\n" }' "${link_sources[@]}"
)

if (( failures > 0 )); then
  exit 1
fi

printf 'docs-check: validated %d required documents and local Markdown links\n' "${#required_docs[@]}"
