# Codex MCP Local Setup

This repository tracks a portable template at `.codex/config.example.toml`.
Your real local config file is `.codex/config.toml` and is intentionally ignored by Git.

## Setup

1. Copy the template:
   - `cp .codex/config.example.toml .codex/config.toml`
2. Edit `.codex/config.toml` and replace placeholders with absolute local paths.
3. Preferred mode is Sail:
   - `command = "/ABSOLUTE/PATH/TO/PROJECT/vendor/bin/sail"`
   - `args = ["artisan", "boost:mcp"]`
   - `cwd = "/ABSOLUTE/PATH/TO/PROJECT"`
4. Restart Codex app after config changes.
5. Validate:
   - `vendor/bin/sail artisan boost:mcp --help`
   - direct MCP check: `list_mcp_resources(server="laravel-boost")`
   - direct MCP tool check: `mcp__laravel_boost__application_info`

## Notes

- Relative `cwd = "."` may fail depending on Codex launch context.
- Absolute paths are allowed in local untracked `.codex/config.toml`.
- Do not commit real machine paths to tracked files.
- If you use Sail, Docker must be running.
- If MCP startup fails, test the configured command manually from repo root first.
