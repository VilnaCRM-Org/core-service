#!/bin/bash

# ==============================================================================
# Configuration Validation Script
# ==============================================================================
#
# This script validates that the required directory structure is maintained
# and that locked configuration files have not been modified.
#
# IMPORTANT: Locked Configuration Files
# ======================================
# The configuration files listed below are considered LOCKED and should NOT
# be modified by automated tools or developers. These files provide core
# quality assurance and code standards functionality.
#
# If you need to customize configuration:
# 1. Discuss changes with the team first
# 2. Ensure changes align with project standards
# 3. Update this script if configuration files are added/removed
#
# AUTHOR:     VilnaCRM Team
# VERSION:    1.0.0
# DATE:       2025-10-23
# LICENSE:    MIT
#
# ==============================================================================

set -euo pipefail

# Global variables
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
ERROR_COUNT=0
WARNING_COUNT=0
ERRORS=""
WARNINGS=""

# ANSI color codes
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# ==============================================================================
# LOCKED CONFIGURATION FILES
# ==============================================================================
# These configuration files should NOT be modified. Any changes will cause
# validation to fail.
LOCKED_CONFIG_FILES=(
    "phpinsights.php"
    "phpinsights-tests.php"
    "psalm.xml"
    "deptrac.yaml"
    "infection.json5"
    "phpmd.xml"
    "phpmd.tests.xml"
    ".php-cs-fixer.dist.php"
)

# Required directory structure
REQUIRED_DIRS=(
    "src"
    "src/Shared"
    "src/Shared/Application"
    "src/Shared/Domain"
    "src/Shared/Infrastructure"
    "config"
    "config/packages"
    "config/doctrine"
    "tests"
    "tests/Unit"
    "tests/Integration"
    "tests/Behat"
)

# ==============================================================================
# HELPER FUNCTIONS
# ==============================================================================

# Print colored output
print_error() {
    echo -e "${RED}$1${NC}" >&2
}

print_success() {
    echo -e "${GREEN}$1${NC}"
}

print_warning() {
    echo -e "${YELLOW}$1${NC}"
}

print_info() {
    echo -e "${BLUE}$1${NC}"
}

# Add error message
add_error() {
    ERROR_COUNT=$((ERROR_COUNT + 1))
    ERRORS="${ERRORS}\n  - $1"
}

# Add warning message
add_warning() {
    WARNING_COUNT=$((WARNING_COUNT + 1))
    WARNINGS="${WARNINGS}\n  - $1"
}

# ==============================================================================
# VALIDATION FUNCTIONS
# ==============================================================================

# Validate required directory structure
validate_directory_structure() {
    local validation_passed=true

    print_info "→ Validating required directory structure..."

    for dir in "${REQUIRED_DIRS[@]}"; do
        local dir_path="$PROJECT_ROOT/$dir"

        if [ ! -e "$dir_path" ]; then
            add_error "Missing required directory: $dir"
            validation_passed=false
        elif [ ! -d "$dir_path" ]; then
            add_error "Expected directory but found file: $dir"
            validation_passed=false
        fi
    done

    if [ "$validation_passed" = true ]; then
        print_success "  ✓ Directory structure validation passed"
    else
        print_error "  ✗ Directory structure validation failed"
    fi

    return $([ "$validation_passed" = true ] && echo 0 || echo 1)
}

# Get all locked configuration files that should not be modified
get_all_config_files() {
    local files=()

    # Add locked config files
    for file in "${LOCKED_CONFIG_FILES[@]}"; do
        files+=("$PROJECT_ROOT/$file")
    done

    printf '%s\n' "${files[@]}"
}

# Check if any configuration files have been modified via git
check_git_changes_to_config() {
    print_info "→ Checking for modifications to locked configuration files..."

    # Check if we're in a git repository
    if [ ! -d "$PROJECT_ROOT/.git" ]; then
        add_warning "Not a git repository - skipping git modification checks"
        return 0
    fi

    # Change to project root for git commands
    cd "$PROJECT_ROOT"

    local modified_files=()
    local comparison_successful=false

    # Try to compare against reference branches (origin/main, origin/master, main, master)
    for ref in origin/main origin/master main master; do
        if git rev-parse --verify "$ref" >/dev/null 2>&1; then
            # Get files modified between ref and HEAD
            while IFS= read -r line; do
                [ -n "$line" ] && modified_files+=("$PROJECT_ROOT/$line")
            done < <(git diff --name-only "$ref" HEAD 2>/dev/null || true)

            comparison_successful=true
            print_info "  → Comparing against reference branch: $ref"
            break
        fi
    done

    # Also check for uncommitted changes (working directory)
    while IFS= read -r line; do
        [ -n "$line" ] && modified_files+=("$PROJECT_ROOT/$line")
    done < <(git diff --name-only HEAD 2>/dev/null || true)

    # Also check for staged changes
    while IFS= read -r line; do
        [ -n "$line" ] && modified_files+=("$PROJECT_ROOT/$line")
    done < <(git diff --cached --name-only 2>/dev/null || true)

    if [ "$comparison_successful" = false ] && [ ${#modified_files[@]} -eq 0 ]; then
        add_warning "Could not find reference branch and no local changes detected"
    fi

    # Get all locked configuration files
    local config_files=()
    while IFS= read -r line; do
        config_files+=("$line")
    done < <(get_all_config_files)

    # Check if any modified files are locked configuration files
    local modified_config=()
    for modified_file in "${modified_files[@]}"; do
        for config_file in "${config_files[@]}"; do
            if [ "$modified_file" = "$config_file" ]; then
                modified_config+=("$modified_file")
                break
            fi
        done
    done

    # Remove duplicates
    if [ ${#modified_config[@]} -gt 0 ]; then
        modified_config=($(printf '%s\n' "${modified_config[@]}" | sort -u))
    fi

    # Report modified configuration files
    if [ ${#modified_config[@]} -gt 0 ]; then
        for file in "${modified_config[@]}"; do
            local rel_path="${file#$PROJECT_ROOT/}"
            add_error "Modification of locked configuration file is not allowed: $rel_path
      Please discuss any configuration changes with the team first."
        done
        print_error "  ✗ Found ${#modified_config[@]} modified configuration file(s)"
        return 1
    else
        print_success "  ✓ No modifications to locked configuration files detected"
        return 0
    fi
}

# ==============================================================================
# REPORTING FUNCTIONS
# ==============================================================================

# Report validation results
report_results() {
    echo ""
    echo "========================================"
    echo "Validation Results"
    echo "========================================"

    if [ $ERROR_COUNT -eq 0 ] && [ $WARNING_COUNT -eq 0 ]; then
        print_success "✅ Configuration validation passed!"
        return 0
    fi

    if [ $ERROR_COUNT -gt 0 ]; then
        echo ""
        print_error "❌ Configuration validation failed!"
        echo ""
        echo "Errors:"
        echo -e "$ERRORS"
    fi

    if [ $WARNING_COUNT -gt 0 ]; then
        echo ""
        print_warning "Warnings:"
        echo -e "$WARNINGS"
    fi

    echo ""
    echo "========================================"
    if [ $ERROR_COUNT -gt 0 ]; then
        print_error "Found $ERROR_COUNT error(s)"
        if [ $WARNING_COUNT -gt 0 ]; then
            print_warning "Found $WARNING_COUNT warning(s)"
        fi
        echo ""
        print_error "💡 Please ensure all required directories are present"
        print_error "   and that no locked configuration files have been modified."
        return 1
    else
        return 0
    fi
}

# ==============================================================================
# MAIN FUNCTION
# ==============================================================================

main() {
    print_info "========================================"
    print_info "Configuration Validation"
    print_info "========================================"
    print_info "Project root: $PROJECT_ROOT"
    echo ""

    local validation_passed=true

    # Run all validations
    if ! validate_directory_structure; then
        validation_passed=false
    fi

    if ! check_git_changes_to_config; then
        validation_passed=false
    fi

    echo ""

    # Report results
    if ! report_results; then
        exit 1
    fi

    exit 0
}

# Execute main function
main "$@"
