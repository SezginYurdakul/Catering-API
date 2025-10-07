# Logs Directory

This directory contains application logs for monitoring, debugging, and auditing.

## Log Files

### `api.log`
**Main application log file**

- **Format:** JSON (one entry per line)
- **Managed by:** `App\Helpers\Logger`
- **Contains:**
  - All application errors and exceptions
  - Validation failures
  - Authentication failures
  - Request tracking information
  - Database errors (when implemented)

**Example log entry:**
```json
{
  "timestamp": "2025-10-07 12:34:56",
  "level": "ERROR",
  "message": "Exception in LocationController::getAllLocations",
  "request_id": "req_6703abc123def",
  "user_ip": "192.168.1.100",
  "user_agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64)...",
  "context": {
    "exception": "Exception",
    "file": "/path/to/LocationService.php",
    "line": 42,
    "endpoint": "/api/locations",
    "method": "GET"
  }
}
```

### `error_fallback.log`
**Fallback log file**

- **Format:** Plain text
- **Managed by:** `App\Helpers\ErrorHandler`
- **Purpose:** Used when the main Logger service is unavailable
- **Contains:** Critical errors that couldn't be logged to `api.log`

## Log Levels

| Level | Usage |
|-------|-------|
| `ERROR` | Exceptions, critical failures, unrecoverable errors |
| `WARNING` | Validation errors, authentication failures, suspicious activity |
| `INFO` | Normal operations, successful requests (optional) |

## Log Rotation

**Manual rotation:** Logs can grow large in production. Consider implementing log rotation:

```bash
# Example: Rotate logs weekly
mv api.log api.log.$(date +%Y%m%d)
gzip api.log.$(date +%Y%m%d)
touch api.log
chmod 666 api.log
```

**Automated rotation (Linux):**
Create `/etc/logrotate.d/catering-api`:
```
/path/to/Catering-API/logs/*.log {
    weekly
    rotate 4
    compress
    delaycompress
    missingok
    notifempty
    create 0666 www-data www-data
}
```

## Monitoring

### View real-time logs:
```bash
tail -f logs/api.log | jq .
```

### Filter by level:
```bash
grep '"level":"ERROR"' logs/api.log | jq .
```

### Count errors by endpoint:
```bash
grep '"level":"ERROR"' logs/api.log | jq -r '.context.endpoint' | sort | uniq -c
```

### Find errors in last hour:
```bash
awk -v d="$(date -d '1 hour ago' '+%Y-%m-%d %H:%M:%S')" '$0 > d' logs/api.log | jq .
```

## Security

⚠️ **Important:** Log files may contain sensitive information:
- User IP addresses
- Request parameters
- Stack traces with file paths

**Best practices:**
1. ✅ Logs are already in `.gitignore`
2. ✅ Sensitive fields (passwords, tokens) are filtered
3. ⚠️ Restrict log file access: `chmod 600 logs/*.log`
4. ⚠️ Regularly purge old logs

## Git

Log files are **excluded** from version control:
- `*.log` - All log files
- `logs/*.log` - Logs directory
- `storage/logs/*.log` - Alternative logs location

Only this `README.md` is tracked in git.

## Development vs Production

### Development
- Full stack traces in responses
- Verbose logging enabled
- Log file: `logs/api.log`

### Production
- Generic error messages in responses
- Production-safe logging
- Consider centralized logging (ELK, Splunk, CloudWatch)
- Implement alerting for critical errors

## Troubleshooting

### Log file not created?
Check directory permissions:
```bash
chmod 777 logs/
```

### Logger service unavailable?
Check `config/services.php` - Logger should be registered in DI container.

### Logs growing too large?
Implement log rotation (see above) or use log aggregation services.

## Related Files

- `App/Helpers/Logger.php` - Logger implementation
- `App/Helpers/ErrorHandler.php` - Global error handler
- `App/Controllers/BaseController.php` - Controller-level logging
- `config/services.php` - Logger service configuration
