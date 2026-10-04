# VRC Italia Network Core

WordPress backend for VRC Italia Network.

## Main features

- dedicated community owner role
- frontend-only owner dashboard
- email verification for applications
- 75% community approval workflow + final administrator decision
- private vote comments for administrators
- community data management
- moderated community gallery
- dynamic event calendar REST feed
- event moderation
- weekly time-slot rotation and exception voting
- automatic Yes for missing exception votes after 48 hours
- application form builder and popup design settings
- manual GitHub design synchronization with staging and Git blob integrity verification

## Data ownership

All operational data stays in WordPress. The GitHub design synchronizer never deletes or replaces database content, accounts, media, votes or events.

## Required companion theme

Use with `wordpress/theme/vrchat-italia`.
