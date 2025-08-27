# OpenSID Agents

This directory contains specialized agents for OpenSID development and testing tasks.

## Fresh Install Agent

The **fresh-install** agent resets the OpenSID installation environment while preserving all bug fixes, making it perfect for testing installation flows.

### What it does:

**REMOVES (installation results):**
- ✅ Removes `desa/` folder completely
- ✅ Drops and recreates `opensid` database (empty)
- ✅ Clears installation logs from `storage/logs/`
- ✅ Clears framework cache
- ✅ Restarts PHP-FPM to clear sessions

**PRESERVES (bug fixes):**
- ✅ All migration file fixes (like `Migrasi_2024060171.php`)
- ✅ PHP settings (`memory_limit = 512M`, `max_execution_time = 300s`)
- ✅ Storage directories and permissions
- ✅ Open_basedir configuration
- ✅ All other bug fixes for smooth installation

**VERIFICATION:**
- ✅ Verifies `desa` folder is removed
- ✅ Verifies database is empty (0 tables)
- ✅ Verifies installer is accessible at `http://opensid.local/install`

### Usage:

There are three ways to run the fresh-install agent:

#### Method 1: Direct execution
```bash
./fresh-install.sh
```

#### Method 2: Using the agent runner
```bash
./run-agent.sh fresh-install
```

#### Method 3: Using the task command (recommended)
```bash
./task fresh-install
```

### Example workflow:

1. Run the fresh-install agent:
   ```bash
   ./task fresh-install
   ```

2. Open your browser and navigate to:
   ```
   http://opensid.local/install
   ```

3. Follow the installation wizard to test your installation flow

4. Repeat as needed for testing different installation scenarios

### Requirements:

- MySQL/MariaDB with `opensid` database access
- PHP-FPM service running
- Sudo privileges for restarting PHP-FPM
- Web server configured for `http://opensid.local`

### Configuration:

The agent uses these default settings (editable in `fresh-install.sh`):
- Database: `opensid`
- DB User: `root` 
- DB Password: (empty)
- DB Host: `localhost`
- Web URL: `http://opensid.local`

### Safety Features:

- Confirmation prompt before executing destructive operations
- Comprehensive error checking and logging
- Colored output for clear status reporting
- Verification steps to confirm successful reset

### Adding More Agents:

To create additional agents:

1. Create your agent script (e.g., `my-agent.sh`)
2. Make it executable: `chmod +x my-agent.sh`
3. Add it to the `run-agent.sh` case statement
4. Document it in this README

## Status Check Agent

The **status-check** agent provides a comprehensive overview of the current OpenSID installation state, helping you understand whether the environment is ready for fresh installation or if cleanup is needed.

### What it checks:

**Installation State:**
- ✅ Desa folder existence (should be absent for fresh install)
- ✅ Database state (should be empty for fresh install)
- ✅ Installer accessibility at `http://opensid.local/install`
- ✅ Recent log files count

**Bug Fixes Preservation:**
- ✅ Migration file fixes presence
- ✅ Storage directories integrity
- ✅ Core framework files presence

### Usage:

```bash
./task status-check
```

### Example output:

```
OpenSID Status Check
════════════════════════════════════════════════

Desa folder status: ✓ NOT FOUND (ready for fresh install)
Database status: ✓ EMPTY (0 tables - ready for install)  
Installer accessibility: ✓ ACCESSIBLE (http://opensid.local/install)
Recent logs: ℹ 1 log files exist

Bug fixes preservation status:
✓ Migration fixes preserved (Migrasi_2024060171.php found)
✓ Storage directories preserved
✓ Core framework files preserved

Overall Status:
✓ READY FOR FRESH INSTALLATION

To start fresh installation:
  ./task fresh-install

Then navigate to: http://opensid.local/install
```

## Files:

- `fresh-install.sh` - The main fresh-install agent script
- `status-check.sh` - The status check agent script
- `run-agent.sh` - Agent runner with support for multiple agents
- `task` - Simplified task runner interface
- `AGENTS.md` - This documentation file

## Support:

For issues or feature requests related to these agents, please refer to the main OpenSID project documentation or contact the development team.