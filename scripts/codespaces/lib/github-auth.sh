#!/usr/bin/env bash
# shellcheck shell=bash

cs_require_command() {
    local command_name="$1"
    if ! command -v "${command_name}" >/dev/null 2>&1; then
        echo "Error: required command '${command_name}' is not installed." >&2
        return 1
    fi
}

cs_has_github_app_credentials() {
    [ -n "${GH_APP_ID:-}" ] \
        && [ -n "${GH_APP_INSTALLATION_ID:-}" ] \
        && [ -n "${GH_APP_PRIVATE_KEY:-}" ]
}

cs_base64url_encode() {
    openssl base64 -A | tr '+/' '-_' | tr -d '='
}

cs_load_gh_token_from_aliases() {
    local default_token_var="GH_AUTOMATION_TOKEN"
    local token_var="${GH_TOKEN_VAR:-$default_token_var}"

    if [ -n "${GH_TOKEN:-}" ]; then
        return 0
    fi

    if [ -n "${!token_var:-}" ]; then
        export GH_TOKEN="${!token_var}"
    elif [ -n "${GITHUB_TOKEN:-}" ]; then
        export GH_TOKEN="${GITHUB_TOKEN}"
    elif [ -n "${GH_APP_INSTALLATION_TOKEN:-}" ]; then
        export GH_TOKEN="${GH_APP_INSTALLATION_TOKEN}"
    fi
}

cs_mint_github_app_installation_token() {
    local now iat exp
    local header payload header_b64 payload_b64 signature jwt
    local token_response token key_material

    if ! cs_has_github_app_credentials; then
        echo "Error: missing GitHub App credentials." >&2
        return 1
    fi

    cs_require_command curl
    cs_require_command jq
    cs_require_command openssl

    now="$(date +%s)"
    iat="$((now - 60))"
    exp="$((now + 540))"

    header='{"alg":"RS256","typ":"JWT"}'
    payload="$(printf '{"iat":%s,"exp":%s,"iss":"%s"}' "${iat}" "${exp}" "${GH_APP_ID}")"

    header_b64="$(printf '%s' "${header}" | cs_base64url_encode)"
    payload_b64="$(printf '%s' "${payload}" | cs_base64url_encode)"

    key_material="${GH_APP_PRIVATE_KEY}"
    if printf '%s' "${key_material}" | grep -q '\\n'; then
        key_material="$(printf '%b' "${key_material//\\n/\\n}")"
    fi

    signature="$(
        printf '%s.%s' "${header_b64}" "${payload_b64}" \
            | openssl dgst -binary -sha256 -sign <(printf '%s\n' "${key_material}") \
            | cs_base64url_encode
    )"
    jwt="${header_b64}.${payload_b64}.${signature}"

    token_response="$(
        curl -fsSL -X POST \
            -H "Authorization: Bearer ${jwt}" \
            -H "Accept: application/vnd.github+json" \
            -H "X-GitHub-Api-Version: 2022-11-28" \
            "https://api.github.com/app/installations/${GH_APP_INSTALLATION_ID}/access_tokens"
    )"

    token="$(printf '%s' "${token_response}" | jq -r '.token // empty')"
    if [ -z "${token}" ]; then
        echo "Error: failed to mint GitHub App installation token." >&2
        return 1
    fi

    printf '%s' "${token}"
}

cs_detect_gh_auth_mode() {
    if gh api user >/dev/null 2>&1; then
        printf 'user'
        return 0
    fi

    if gh api /installation/repositories >/dev/null 2>&1; then
        printf 'app'
        return 0
    fi

    return 1
}

cs_ensure_gh_auth() {
    local auth_mode

    cs_require_command gh

    if auth_mode="$(cs_detect_gh_auth_mode)"; then
        export CS_GH_AUTH_MODE="${auth_mode}"
        gh auth setup-git >/dev/null 2>&1 || true
        return 0
    fi

    cs_load_gh_token_from_aliases

    if [ -z "${GH_TOKEN:-}" ] && cs_has_github_app_credentials; then
        GH_TOKEN="$(cs_mint_github_app_installation_token)"
        export GH_TOKEN
    fi

    if auth_mode="$(cs_detect_gh_auth_mode)"; then
        export CS_GH_AUTH_MODE="${auth_mode}"
        gh auth setup-git >/dev/null 2>&1 || true
        return 0
    fi

    cat >&2 <<'EOM'
Error: GitHub authentication is not available.
Provide one of:
  - GH_TOKEN
  - GH_AUTOMATION_TOKEN
  - GITHUB_TOKEN
  - GH_APP_INSTALLATION_TOKEN
or GitHub App credentials to mint a token automatically:
  - GH_APP_ID
  - GH_APP_INSTALLATION_ID
  - GH_APP_PRIVATE_KEY
EOM
    return 1
}
