# VRChat Italia - WordPress

This directory contains the WordPress implementation of VRChat Italia.

## Architecture

- `theme/vrchat-italia`: presentation layer only. It renders the public site, the events page and the owner dashboard shell.
- `plugins/vrchat-italia-core`: data, permissions, moderation, applications, voting, events, owner dashboard and GitHub design synchronization.
- `design`: the manually synchronized design source. The active WordPress site can fetch these assets from GitHub from **VRChat Italia > Design Sync**.

The public prototype in the repository root can remain useful for visual development, but WordPress runtime data never comes from static HTML.

## Security model

The GitHub synchronizer intentionally downloads only allow-listed design assets (CSS, JavaScript and images) from `wordpress/design`. It never downloads or executes remote PHP. A synchronization is manual, administrator-only, staged in a new snapshot and published only after every file passes size and Git blob hash verification.

Community data, accounts, votes, events and uploaded media remain in the WordPress database/uploads and are never overwritten by design synchronization.

## Initial installation

1. Back up the WordPress database and `wp-content`.
2. Copy `theme/vrchat-italia` to `wp-content/themes/vrchat-italia`.
3. Copy `plugins/vrchat-italia-core` to `wp-content/plugins/vrchat-italia-core`.
4. Activate **VRChat Italia Core**.
5. Activate **VRChat Italia** as the current theme.
6. Open **Settings > Permalinks** once if the routes are not refreshed by activation.
7. Test:
   - `/it/`
   - `/en/`
   - `/it/eventi/`
   - `/en/events/`
   - `/it/dashboard/`
   - `/en/dashboard/`
8. Open **VRChat Italia > Design Sync**, verify the repository and manually synchronize the design.

Test on staging before production.

## Application workflow

1. A public applicant submits the popup form.
2. The email must be verified before voting starts.
3. The eligible voter list is snapshotted from currently approved community owners.
4. At least `ceil(75% * eligible owners)` Yes votes are required.
5. Every Yes/No vote requires a 10-250 character comment.
6. Vote comments are visible only to WordPress administrators.
7. If the 75% threshold becomes mathematically impossible, the application is rejected automatically.
8. If the threshold is reached, a WordPress administrator makes the final decision.
9. On approval, the verified email becomes the community owner's WordPress account and receives the dedicated role.
10. The applicant receives only the configured final result message, plus the normal WordPress account email when an account is created.

## Community owner permissions

Owners are normal WordPress users with the `vri_community_owner` role, but they are redirected away from `/wp-admin/`. Their frontend dashboard lets them manage:

- account email, display name and password
- community name
- Discord
- VRChat group
- VRChat world/map
- Instagram
- website
- descriptions in Italian and English (250 characters per language)
- banner
- logo/icon
- events
- up to 10 gallery images

Gallery images require administrator approval before appearing on the homepage.

## Event rotation

The system stores a rotation size when a normal event is submitted.

Example with three approved communities:

- A uses Sunday 1, 22:00-00:00 normally.
- A is blocked from every partially overlapping slot on Sunday 2 and Sunday 3.
- B and C are free to use those slots.
- A becomes free again on Sunday 4.

A blocked owner can click **Request date**.

- all other current community owners are snapshotted as voters
- 100% Yes is required
- one No rejects the request immediately
- after 48 hours, missing votes become automatic Yes
- voters are anonymous to other owners
- administrators retain the audit trail
- an approved exception does not reset the original rotation
- the event still requires administrator content approval

Text-only edits of an already approved event keep the event's existing slot mode/rotation.

## Design Sync

Default source:

- Repository: `haxurus/www.vrchatitalia.it`
- Branch: `main`
- Path: `wordpress/design`

Required files:

- `site.css`
- `site.js`

The theme always has bundled fallback copies, so a failed synchronization does not take the public site offline.
