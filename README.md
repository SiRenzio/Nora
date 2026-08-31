# Laravel + React Starter Kit

## Introduction

Our React starter kit provides a robust, modern starting point for building Laravel applications with a React frontend using [Inertia](https://inertiajs.com).

Inertia allows you to build modern, single-page React applications using classic server-side routing and controllers. This lets you enjoy the frontend power of React combined with the incredible backend productivity of Laravel and lightning-fast Vite compilation.

This React starter kit utilizes React 19, TypeScript, Tailwind, and the [shadcn/ui](https://ui.shadcn.com) and [radix-ui](https://www.radix-ui.com) component libraries.

## Official Documentation

Documentation for all Laravel starter kits can be found on the [Laravel website](https://laravel.com/docs/starter-kits).

## Contributing

Thank you for considering contributing to our starter kit! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

All contributions to the Starter Kits from now on should be made through [Maestro](https://github.com/laravel/maestro).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## License

The Laravel + React starter kit is open-sourced software licensed under the MIT license.


Nora — System Requirements Draft
1. Purpose
Nora is a personal reading-tracker application for manga, manhwa, manhua, webcomics, and novels published across different websites.
The system will let users maintain one centralized library, track their reading progress, detect newly released chapters, and notify them when updates become available. Nora will direct users to the original publishing or reading website and will not host copyrighted chapters.
2. Objectives
Nora should:
- Centralize titles from multiple websites in one library.
- Remember the user’s latest completed chapter.
- Detect newly published chapters automatically where possible.
- Notify users without repeatedly notifying them about the same chapter.
- Provide direct links to chapters on their original websites.
- Support manual tracking when automatic monitoring is unavailable.
- Remain extensible so additional websites can be supported later.
3. User Roles
Guest
A guest can:
- View the landing page.
- Register an account.
- Sign in.
- Reset a forgotten password.
Registered User
A registered user can:
- Manage their personal library.
- Add and remove titles.
- update reading progress.
- Configure monitoring and notifications.
- View detected updates and notification history.
- Manage their account and preferences.
Administrator
An administrator can:
- Manage supported source websites.
- Enable or disable source integrations.
- Review failed chapter checks.
- Monitor scheduled jobs and source health.
- Manage users when necessary.
- Configure global checking limits and system settings.
An administrative interface can be postponed until after the MVP.
4. Core Functional Requirements
4.1 Authentication
The system shall:
- Allow account registration and sign-in.
- Support password recovery.
- Allow users to update their profile and password.
- Prevent users from accessing another user’s library.
- Support email verification if notifications are sent through email.
4.2 Personal Library
Users shall be able to:
- Add a title using a supported website URL.
- Add a title manually when the website is unsupported.
- Edit title information.
- Remove or archive a title.
- Search and filter their library.
- Assign one of the following reading statuses:
  - Plan to Read
  - Reading
  - On Hold
  - Completed
  - Dropped
Each library entry should contain:
- Title
- Alternative title, if available
- Content type: manga, manhwa, manhua, comic, or novel
- Cover image
- Description
- Original source URL
- Source website
- Current reading status
- Latest available chapter
- Last completed chapter
- Date last read
- Date last checked
- Monitoring enabled or disabled
- Personal notes
- Optional rating
- Optional tags or genres
4.3 Reading Progress
The system shall allow users to:
- Set the last completed chapter manually.
- Mark the next chapter as completed.
- Mark a specific chapter as completed.
- View unread chapter count.
- Continue reading from the next unread chapter.
- Open a chapter on its original website.
- Preserve chapter labels such as 12, 12.5, Side Story 3, or Volume 2 Chapter 8.
Chapter identifiers should not be restricted to integers because websites use different numbering formats.
4.4 Source Monitoring
For supported websites, Nora shall:
- Retrieve title metadata from a submitted URL.
- Detect the latest available chapter.
- Store the original chapter title, identifier, URL, and publication date when available.
- Periodically check monitored titles for updates.
- Record newly detected chapters.
- Avoid creating duplicate chapters.
- Record successful and failed checks.
- Apply reasonable request delays and retry limits.
- Stop or reduce checks for repeatedly failing sources.
- Allow a source-specific parser or adapter to be added without changing the core tracking system.
For unsupported or blocked websites, Nora shall:
- Preserve the user’s library entry.
- Allow manual progress and latest-chapter updates.
- Explain that automatic monitoring is unavailable.
- Avoid bypassing authentication, paywalls, CAPTCHAs, or anti-bot protections.
Official APIs and feeds should be preferred over page scraping whenever available.
4.5 Update Feed
The system shall provide an update feed containing:
- Titles with newly detected chapters
- Number of unread chapters
- Latest chapter label
- Detection or publication time
- Link to the latest chapter
- Option to mark an update as seen
- Option to mark a chapter as completed
Users should be able to filter updates by content type, source, and date.
4.6 Notifications
The MVP shall support in-app notifications.
Email notifications should be the first external notification channel. Browser push, mobile push, Telegram, or Discord can be added later.
Users shall be able to configure:
- Whether notifications are enabled
- Notification channel
- Immediate or digest delivery
- Digest frequency
- Titles for which notifications are muted
- Quiet hours and timezone
The system shall:
- Notify only when a new chapter is detected.
- Avoid duplicate notifications.
- Record delivery status.
- Retry temporary delivery failures.
- Allow notifications to link to Nora or directly to the source chapter.
4.7 Dashboard
The dashboard should summarize:
- Recently updated titles
- Titles with unread chapters
- Currently reading titles
- Recently read titles
- Failed or paused monitors
- Total titles by reading status
- Recent notifications
5. Monitoring Workflow
Scheduled check
      ↓
Select titles due for checking
      ↓
Use the adapter for each source website
      ↓
Retrieve and normalize chapter information
      ↓
Compare results with stored chapters
      ↓
Save newly detected chapters
      ↓
Update the title's latest-chapter information
      ↓
Create notifications for affected users
      ↓
Send immediate notifications or queue them for a digest
6. Suggested Data Entities
The initial system will likely need:
- users
- titles
- sources
- title_sources
- chapters
- library_entries
- reading_progress
- notifications
- notification_preferences
- monitoring_checks
- tags
- title_tag
A title and its source URL should be separate concepts. This allows one work to have multiple source websites without duplicating the underlying title.
7. Non-Functional Requirements
Security
- Users must only access their own library and preferences.
- Credentials and notification secrets must be encrypted or stored outside source control.
- Submitted URLs must be validated to reduce server-side request forgery risks.
- Monitoring requests must only access approved hosts.
- Rate limiting must be applied to authentication and URL-import endpoints.
- Imported website content must be sanitized before display.
Reliability
- Monitoring and notifications must run through background queues.
- Failed jobs must be retryable.
- A failure from one website must not stop checks for other websites.
- Chapter detection must be idempotent.
- Every monitoring attempt should produce a useful status record.
Performance
- Normal library and dashboard pages should load within approximately two seconds under expected personal-use traffic.
- Source checks should execute asynchronously.
- Large libraries should use pagination or incremental loading.
- Repeated source requests should be minimized through sensible check intervals.
Maintainability
- Each supported website should have its own adapter.
- Adapters should follow a shared interface and return normalized data.
- Parser behavior should have automated tests using saved, sanitized fixtures.
- Source-specific changes should not require changes to library or notification logic.
Accessibility and Responsiveness
- The interface should work on desktop and mobile browsers.
- All primary actions must be keyboard accessible.
- Forms and controls must have accessible labels.
- Status and update indicators must not rely on color alone.
- The interface should support light and dark modes.
Time Handling
- Dates must be stored in UTC.
- Dates and notification schedules must be displayed using the user’s timezone.
- The initial default timezone may be Asia/Manila.
8. Legal and Ethical Constraints
Nora shall:
- Store metadata and links rather than copyrighted chapter content.
- Direct users to the original source.
- Respect website terms, robots directives where applicable, and request limits.
- Prefer official APIs, RSS feeds, or publicly exposed metadata.
- Not bypass paywalls, authentication, CAPTCHA, or access controls.
- Allow a source integration to be disabled if monitoring is prohibited or unreliable.
- Avoid permanently copying externally hosted cover images unless permitted.
9. MVP Scope
The first usable release should include:
- User registration and authentication
- Personal library
- Manual title creation
- URL-based title import for one or two approved websites
- Reading-status and chapter-progress tracking
- Automatic scheduled chapter checks
- In-app update feed
- In-app notifications
- Email notifications
- Notification preferences
- Monitoring history and failure messages
- Responsive interface
- Automated tests for core workflows and source adapters
10. Features Deferred Until Later
The following are valuable but should not block the MVP:
- Mobile application
- Browser extension
- AniList, MyAnimeList, or MangaUpdates synchronization
- Importing existing browser bookmarks
- Telegram, Discord, and mobile push notifications
- Reading statistics and yearly summaries
- Recommendations
- Social profiles and shared reading lists
- Comments, reviews, and community features
- Automatic title matching across multiple source websites
- Public API
- Offline reading
- Hosting chapter images or novel text
11. MVP Acceptance Criteria
The MVP will be considered usable when a registered user can:
1. Add a supported title using its URL.
2. Add an unsupported title manually.
3. Set their current reading status and last completed chapter.
4. See the latest known chapter and unread chapter count.
5. Receive one notification when a newer chapter is detected.
6. Open the new chapter on its original website.
7. Avoid receiving duplicate notifications for the same chapter.
8. Disable monitoring or notifications for an individual title.
9. Review a clear error when a source cannot be checked.
10. Use the primary workflow comfortably on both desktop and mobile.
12. Decisions Needed Before Implementation
Before defining the database schema and first development milestone, we should decide:
- Which two reading websites Nora will support first
- Whether Nora is initially single-user or open to multiple accounts
- Whether email notifications are required in the first release
- How frequently websites should be checked
- Whether different translations of the same work are separate library entries
- Whether the MVP tracks individual chapter history or only the latest completed chapter
- Whether Nora will be local-only initially or deployed to a server