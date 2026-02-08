# Emails

All emails sent from this application must be **plain text only** — no HTML, no CSS, no markdown components.

## Rules
- Mailables must use `text:` in their `Content` definition, never `markdown:` or `view:`
- Notification overrides must use `->text('view.name', $data)` on `MailMessage`, never `->line()`, `->action()`, or `->markdown()`
- Email Blade templates in `resources/views/mail/` must contain only plain text with Blade variables — no `<x-mail::message>`, `<x-mail::button>`, or any HTML tags
- URLs must be included as raw text links, not wrapped in buttons or anchor tags

## Where emails are configured
- **Custom mailables**: `app/Mail/` — each uses a plain text view from `resources/views/mail/`
- **Built-in notifications** (password reset, email verification): Overridden in `AppServiceProvider::configureMail()` to use plain text views
