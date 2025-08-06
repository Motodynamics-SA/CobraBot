# Gitleaks Security Integration

This document explains the Gitleaks secret scanning integration for enhanced security of the Laravel application.

## Overview

Gitleaks is a SAST (Static Application Security Testing) tool for detecting and preventing secrets in git repositories. It helps prevent accidental exposure of sensitive information like API keys, passwords, and tokens.

## Features Implemented

### 1. Secret Detection Rules

- Laravel application keys (APP_KEY)
- Database credentials (DB_PASSWORD, DB_USERNAME)
- Mail server credentials (MAIL_PASSWORD)
- Redis passwords (REDIS_PASSWORD)
- AWS access keys and secrets
- Stripe API keys (both test and live)
- JWT secrets
- OAuth client secrets (Facebook, Google, Twitter, GitHub)
- Sentry DSN URLs
- Twilio authentication tokens
- Slack webhook URLs
- Generic API keys and tokens

### 2. Smart Allowlisting

The configuration includes intelligent allowlisting for:

- Example configuration files (`.env.example`, `.env.ddev.example`, etc.)
- Testing environments (`.env.testing`)
- Documentation files (`LOCAL-DEVELOPMENT.md`)
- Template files (`.github/templates/.env.j2`)
- Deployment scripts (`azure/deploy-laravel-to-azure.sh`)
- Common development values (`db`, `test`, `localhost`, etc.)
- Base64 test strings with repeating characters
- Environment variable placeholders (`$VARIABLE_NAME`)
- Template syntax (`{{ VARIABLE }}`)

### 3. Pre-commit Hook Integration

- Automatically scans staged files before each commit
- Blocks commits containing potential secrets
- Provides clear error messages and bypass instructions
- Integrates with existing code formatting hooks

### 4. CI/CD Integration

- GitHub Actions workflow for automated scanning
- Runs on all pushes and pull requests to main/develop branches
- Daily scheduled scans for comprehensive monitoring
- Dependency security auditing
- Code quality checks with PHPStan
- Security headers verification

## Installation & Setup

### Prerequisites

- Git repository initialized
- PHP 8.4+ and Composer
- Node.js 22+ and npm

### Manual Installation

```bash
# Download Gitleaks binary
curl -sSL https://github.com/gitleaks/gitleaks/releases/download/v8.28.0/gitleaks_8.28.0_linux_x64.tar.gz | tar -xz

# Install pre-commit hooks
./tools/git-hooks/install.sh

# Test the configuration
./gitleaks detect --config=.gitleaks.toml
```

### Package Manager Installation

```bash
# Ubuntu/Debian
sudo apt install gitleaks

# macOS
brew install gitleaks

# Windows (Chocolatey)
choco install gitleaks
```

## Usage

### Manual Scanning

```bash
# Scan entire repository
./gitleaks detect --config=.gitleaks.toml

# Scan with verbose output
./gitleaks detect --config=.gitleaks.toml --verbose

# Scan specific files
./gitleaks detect --config=.gitleaks.toml --source=app/

# Scan staged files (used by pre-commit hook)
./gitleaks protect --config=.gitleaks.toml --staged
```

### Pre-commit Hook

The pre-commit hook automatically runs when you commit changes:

```bash
git add .
git commit -m "Your commit message"
# Gitleaks scan runs automatically
```

If secrets are detected:

```bash
# Bypass for false positives (use carefully)
git commit -m "Your commit message" --no-verify
```

### CI/CD Pipeline

The GitHub Actions workflow runs automatically on:

- Pushes to main/develop branches
- Pull requests to main/develop branches
- Daily at 2 AM UTC (scheduled scan)

## Configuration

The configuration is stored in `.gitleaks.toml` and includes:

### Rule Customization

Add new rules for project-specific secrets:

```toml
[[rules]]
id = "custom-api-key"
description = "Custom API Key"
regex = '''CUSTOM_API_KEY\s*=\s*["']?[a-z0-9]{32}["']?'''
keywords = ["CUSTOM_API_KEY"]
```

### Allowlist Updates

Add new patterns to ignore false positives:

```toml
[allowlist]
regexes = [
    '''your-custom-pattern-here''',
]
files = [
    "your-file.example",
]
paths = [
    "your-directory",
]
```

## False Positives

### Common False Positives

- Test credentials in example files
- Documentation examples
- Template variables
- Development environment passwords

### Handling False Positives

1. **Update Allowlist**: Add patterns to `.gitleaks.toml`
2. **File Allowlist**: Add specific files to the allowlist
3. **Bypass Commit**: Use `--no-verify` flag (not recommended)

### Example Allowlist Entry

```toml
[allowlist]
regexes = [
    '''DB_PASSWORD\s*=\s*["']?test123["']?''',  # Allow test password
]
files = [
    ".env.local.example",  # Allow specific file
]
```

## Security Best Practices

### Environment Management

- Never commit actual `.env` files
- Use `.env.example` for templates
- Rotate secrets regularly
- Use different secrets for each environment

### Secret Management

- Use environment variables for secrets
- Consider using secret management services (AWS Secrets Manager, Azure Key Vault)
- Implement proper access controls
- Audit secret access regularly

### Development Workflow

- Run scans before committing
- Review scan results carefully
- Don't bypass warnings without investigation
- Keep Gitleaks updated

## Troubleshooting

### Common Issues

#### Permission Denied

```bash
chmod +x gitleaks
```

#### Pre-commit Hook Not Working

```bash
./tools/git-hooks/install.sh
```

#### High False Positive Rate

Review and update the allowlist patterns in `.gitleaks.toml`.

#### CI/CD Failures

Check the GitHub Actions logs and ensure the configuration file is valid.

### Getting Help

- Check the [Gitleaks documentation](https://gitleaks.io/)
- Review configuration patterns
- Open an issue in the project repository

## Monitoring & Maintenance

### Regular Tasks

- Review and update allowlist patterns
- Update Gitleaks to latest version
- Monitor CI/CD scan results
- Audit detected secrets

### Version Updates

```bash
# Check current version
./gitleaks version

# Download latest release
curl -sSL https://github.com/gitleaks/gitleaks/releases/latest/download/gitleaks_linux_x64.tar.gz | tar -xz
```

### Metrics

- Track scan execution time
- Monitor false positive rates
- Review blocked commits
- Audit bypass usage

## Integration with Laravel Security

### Existing Security Features

- CSRF protection
- XSS protection headers (AddSecurityHeaders middleware)
- Input validation and sanitization
- Authentication and authorization (Spatie permissions)
- Session security configuration

### Complementary Security Tools

- PHPStan for static analysis
- Pest for testing
- Pint for code formatting
- Rector for code refactoring

This Gitleaks integration provides an additional layer of security by preventing secret leakage in your Laravel application's codebase.
