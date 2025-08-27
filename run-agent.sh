#!/bin/bash

# OpenSID Agent Runner
# Usage: ./run-agent.sh <agent-name>
# Example: ./run-agent.sh fresh-install

set -e

OPENSID_ROOT="/home/mulyawansentosa/Documents/Production/Project/OpenSID/Flaz/OpenSID-Flaz"
AGENT_NAME="$1"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Show usage
show_usage() {
    echo ""
    echo -e "${BLUE}OpenSID Agent Runner${NC}"
    echo ""
    echo "Usage: ./run-agent.sh <agent-name>"
    echo ""
    echo "Available agents:"
    echo "  fresh-install    - Reset OpenSID installation environment while preserving bug fixes"
    echo "  status-check     - Check current OpenSID installation state"
    echo ""
    echo "Examples:"
    echo "  ./run-agent.sh fresh-install"
    echo "  ./run-agent.sh status-check"
    echo ""
}

# Run the requested agent
run_agent() {
    case "$AGENT_NAME" in
        "fresh-install")
            echo -e "${GREEN}Running fresh-install agent...${NC}"
            "$OPENSID_ROOT/fresh-install.sh"
            ;;
        "status-check")
            echo -e "${GREEN}Running status-check agent...${NC}"
            "$OPENSID_ROOT/status-check.sh"
            ;;
        *)
            echo -e "${RED}Error: Unknown agent '$AGENT_NAME'${NC}"
            show_usage
            exit 1
            ;;
    esac
}

# Main execution
main() {
    if [ -z "$AGENT_NAME" ]; then
        echo -e "${RED}Error: No agent specified${NC}"
        show_usage
        exit 1
    fi
    
    cd "$OPENSID_ROOT" || {
        echo -e "${RED}Error: Cannot change to OpenSID directory: $OPENSID_ROOT${NC}"
        exit 1
    }
    
    run_agent
}

# Run main function
main "$@"