#!/usr/bin/env bash

# Dieses Skript sichert YAML-Dateien aus einem lokalen Git-Repository auf GitHub.
# Dieser Job sollte regelmäßig über Cron oder einen anderen Scheduler ausgeführt werden.
# Am besten wird im Ordner /recipes ein git repo initialisiert, das .git-Verzeichnis sowie dieses Skript mit Unix-Rechten 750 gesetzt und dann über Cron ausgeführt.
# Die Zugangsdaten werden als Parameter übergeben, um sie nicht im Skript zu speichern.

REPO_DIR="PFAD_ZUM_REPOSITORY"  # Pfad zum lokalen Git-Repository
BRANCH="main"
LOG_TAG="recipes-github-push"

# Zugangsdaten als Parameter
if [[ $# -ne 2 ]]; then
    echo "Verwendung: $0 GITHUB_USERNAME GITHUB_PAT" >&2
    exit 1
fi

GITHUB_USER="$1"
GITHUB_PAT="$2"

log() {
    logger -t "$LOG_TAG" -- "$*"
    printf '%s %s\n' "$(date '+%Y-%m-%d %H:%M:%S')" "$*"
}

if [[ "$EUID" -ne 0 ]]; then
    echo "Dieses Skript muss als root ausgeführt werden." >&2
    exit 1
fi

if [[ -z "$GITHUB_USER" || -z "$GITHUB_PAT" ]]; then
    echo "FEHLER: Benutzername und PAT dürfen nicht leer sein." >&2
    exit 1
fi

if [[ ! -d "$REPO_DIR/.git" ]]; then
    log "FEHLER: Kein Git-Repository unter $REPO_DIR gefunden."
    exit 1
fi

cd "$REPO_DIR"
export GIT_TERMINAL_PROMPT=0

# Remote-URL ohne Token sicherstellen.
REMOTE_URL="$(git remote get-url origin)"

if [[ "$REMOTE_URL" != https://github.com/* ]]; then
    log "FEHLER: origin muss eine HTTPS-GitHub-URL sein."
    exit 1
fi

# Git-Zugangsdaten nur für diesen Skriptlauf bereitstellen.
ASKPASS_SCRIPT="$(mktemp /tmp/recipes-askpass.XXXXXX)"
chmod 700 "$ASKPASS_SCRIPT"

cleanup() {
    rm -f "$ASKPASS_SCRIPT"
    unset GITHUB_PAT GITHUB_USER
}
trap cleanup EXIT

cat > "$ASKPASS_SCRIPT" <<'ASKPASS'
#!/bin/sh
case "$1" in
    *Username*)
        printf '%s\n' "$GITHUB_USER"
        ;;
    *Password*)
        printf '%s\n' "$GITHUB_PAT"
        ;;
    *)
        exit 1
        ;;
esac
ASKPASS

chmod 700 "$ASKPASS_SCRIPT"
export GIT_ASKPASS="$ASKPASS_SCRIPT"
export GITHUB_USER GITHUB_PAT

# Remote-Repository aktualisieren.
if ! git fetch origin "$BRANCH"; then
    log "FEHLER: Git-Fetch fehlgeschlagen."
    exit 1
fi

if ! git switch "$BRANCH"; then
    log "FEHLER: Branch '$BRANCH' konnte nicht ausgewählt werden."
    exit 1
fi

# Nur YAML-Dateien zum Index hinzufügen.
if ! git add -A -- '*.yaml'; then
    log "FEHLER: git add fehlgeschlagen."
    exit 1
fi

# Nur bei vorgemerkten Änderungen committen.
if ! git diff --cached --quiet; then
    if ! git commit -m "Automatisches YAML-Backup: $(date '+%Y-%m-%d %H:%M:%S')"; then
        log "FEHLER: Commit fehlgeschlagen."
        exit 1
    fi
else
    log "Keine YAML-Änderungen zum Committen."
fi

# Änderungen pushen.
if ! git push origin "$BRANCH"; then
    log "FEHLER: Git-Push fehlgeschlagen."
    exit 1
fi

log "YAML-Push erfolgreich abgeschlossen."