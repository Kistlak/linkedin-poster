# 📢 LinkedIn Poster for Laravel

A simple Laravel package to authenticate users with LinkedIn and allow them to share event posts with a custom merged image (e.g., event banner + profile photo).

---

## 🚀 Features

- LinkedIn OAuth login with access token/session management
- Image merge support using event image + user's LinkedIn profile picture
- Optional preview before posting
- Text + Image post support to LinkedIn feed
- Configurable via `config/linkedin-share.php`

---

## 🔧 Installation

1. Require the package:

```bash
composer require kistlak/linkedin-poster
```

```
php artisan vendor:publish --tag=linkedin-config
```

2. Add the following to your .env

LINKEDIN_CLIENT_ID=your_client_id
LINKEDIN_CLIENT_SECRET=your_client_secret
LINKEDIN_REDIRECT_URI=https://yourapp.com/linkedin/callback

## Usage
In your blade file, add:

```
@if (!Session::has('linkedin_token'))
    <a href="{{ route('linkedin.redirect', [$event->getTable(), $event->id]) }}">Connect LinkedIn</a>
@else
    <a href="{{ route('linkedin.share.index.view', [$event->getTable(), $event->id]) }}">Share on LinkedIn</a>
@endif
```
