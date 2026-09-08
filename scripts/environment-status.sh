#!/bin/sh
# Report the live URL, health, and deployed commit of every HTSMS environment.
#
# The commit is read back from each running environment's /health/ready
# endpoint rather than from a deploy log, so it reflects what is actually
# serving traffic right now.
#
#   ./scripts/environment-status.sh              # human-readable table
#   ./scripts/environment-status.sh --markdown   # markdown table
#
# Depends only on curl and sed so it runs the same in CI and in Git Bash.
set -eu

PRODUCTION_URL="${HTSMS_PRODUCTION_URL:-https://htsms.cm-ea.com}"
STAGING_URL="${HTSMS_STAGING_URL:-https://dev.htsms.cm-ea.com}"

FORMAT=plain
[ "${1:-}" = "--markdown" ] && FORMAT=markdown

# Pull one field out of the small flat health JSON without needing jq.
json_field() {
    printf '%s' "$1" | sed -n 's/.*"'"$2"'"[[:space:]]*:[[:space:]]*"\([^"]*\)".*/\1/p'
}

short_sha() {
    printf '%s' "$1" | cut -c1-7
}

# Sets: R_STATUS, R_COMMIT, R_SHORT, R_CODE
probe() {
    url="$1"
    # Assign on failure rather than substituting inside the expansion, or
    # curl's own "000" and the fallback would concatenate.
    R_CODE=$(curl -sS -m 20 -o /dev/null -w '%{http_code}' "$url/health/ready" 2>/dev/null) || R_CODE='000'
    [ -n "$R_CODE" ] || R_CODE='000'

    if [ "$R_CODE" = "000" ]; then
        R_STATUS='unreachable'; R_COMMIT='-'; R_SHORT='-'
        return 0
    fi

    body=$(curl -sS -m 20 "$url/health/ready" 2>/dev/null || printf '')
    R_STATUS=$(json_field "$body" status)
    R_COMMIT=$(json_field "$body" commit)
    [ -n "$R_STATUS" ] || R_STATUS="http $R_CODE"
    [ -n "$R_COMMIT" ] || R_COMMIT='unknown'
    R_SHORT=$(short_sha "$R_COMMIT")
}

if [ "$FORMAT" = markdown ]; then
    printf '| Environment | URL | Status | Commit |\n'
    printf '| --- | --- | --- | --- |\n'
else
    printf '%-12s %-32s %-12s %s\n' ENVIRONMENT URL STATUS COMMIT
fi

exit_code=0
for entry in "production|$PRODUCTION_URL" "staging|$STAGING_URL"; do
    name=${entry%%|*}
    url=${entry#*|}
    probe "$url"

    if [ "$FORMAT" = markdown ]; then
        printf '| %s | %s | %s | `%s` |\n' "$name" "$url" "$R_STATUS" "$R_SHORT"
    else
        printf '%-12s %-32s %-12s %s\n' "$name" "$url" "$R_STATUS" "$R_SHORT"
    fi

    [ "$R_STATUS" = 'ready' ] || exit_code=1
done

exit "$exit_code"
