# Echo-Map

> Collaborative AI-Summarized Memory Pinning Platform for Shared Spaces.

Echo-Map is a web platform where users pin personal memories — text, photos, or audio — to real-world locations. When public pins cluster in the same place, an AI language model summarizes them into a short "location story" that captures the collective meaning of that spot.

---

## What it does

Echo-Map works in three layers:

1. **Individual pins** — a user drops a note, photo, or audio clip at a specific coordinate. Each pin has its own visibility: `private`, `friends-only`, or `public`.
2. **Collective memory** — when several public pins land near each other (default ~300 m radius), they are automatically grouped into a cluster that represents the community's shared memory of that place.
3. **AI location stories** — the cluster's public pins are sent to a large language model, which writes a short headline and a 2–3 sentence summary. The result is rendered on the map as a clickable halo.

The result is a platform where many small, individual memories become one durable, AI-authored description of a place — a shared memory layer over the physical world.

---

## Features

- 📍 **Location-based pins** with text, photo (up to 5), and audio attachments
- 🔒 **Three-tier privacy** per pin: `private`, `friends-only`, `public`
- 👥 **Friends system** — send, accept, decline, and remove connections
- 🤖 **AI Location Stories** — public pins cluster and are summarized by an LLM
- 🗺️ **Interactive map** filtered by **theme**, **location**, and **time period**
- 💬 **Comments and Echo reactions** on pins
- 🚩 **Report system** for pins and comments
- 🛡️ **Admin moderation dashboard** — review reports, hide content, restore pins

---

## Tech stack

| Layer | Choice |
|---|---|
| Backend | Laravel 13 (PHP 8.5) |
| Database | MySQL 8 |
| Frontend | Blade + vanilla JS + Leaflet |
| Map tiles | OpenStreetMap (CSS-filtered for a calm look) |
| Auth | Laravel Breeze (Blade stack) |
| LLM | Provider-agnostic: Groq (free) / Ollama (local) / Anthropic Claude |
| Queue | Database driver (jobs run synchronously via artisan commands) |

---

## Privacy design

The hard rule: **private and friends-only pins never reach the AI.**

This is enforced at three levels:

1. `ClusterPinsJob` filters `visibility = 'public' AND is_hidden = false` at the SQL level — private/friends pins never enter memory.
2. `SummarizeStoryJob` re-fetches pins via `LocationStory::publicPins()`, which repeats the same filter as a belt-and-suspenders check.
3. `StoryController@list` and the `/stories` endpoint only ever count public pins when deciding whether a cluster qualifies for display.

The AI prompt contains **only** public pin titles and short body snippets — no coordinates, no usernames, no media URLs. The 7 automated tests in `tests/Feature/EchoMap/PrivacyTest.php` prove this behavior end to end.

---

## Setup (local development)

Prerequisites: PHP 8.5+, Composer 2.10+, Node 24+, MySQL 8+.

```bash
# 1. Clone the repo
git clone https://github.com/dods112/Echo-Map.git
cd Echo-Map

# 2. Install PHP dependencies
composer install

# 3. Install JS dependencies (Vite + Tailwind for Breeze pages)
npm install

# 4. Create your .env from the template
cp .env.example .env
php artisan key:generate

# 5. Configure the database in .env (DB_DATABASE=echomap by default)
#    then run migrations:
php artisan migrate

# 6. Link storage for user-uploaded media
php artisan storage:link

# 7. Build frontend assets
npm run build

# 8. Serve
php artisan serve
```

Open http://127.0.0.1:8000. The map lives at `/map`.

---

## Configuring the AI provider

The summarization layer supports four providers, selected by a single `.env` value:

```env
LLM_PROVIDER=stub        # no API key needed — writes placeholder summaries
LLM_PROVIDER=groq        # free tier — needs GROQ_API_KEY
LLM_PROVIDER=ollama      # fully offline — needs Ollama running locally
LLM_PROVIDER=anthropic   # Claude API — needs ANTHROPIC_API_KEY
```

Relevant env keys:

```env
GROQ_API_KEY=gsk_...
GROQ_MODEL=openai/gpt-oss-120b
OLLAMA_MODEL=llama3.2:1b
ANTHROPIC_API_KEY=sk-ant-...
ANTHROPIC_MODEL=claude-haiku-4-5-20251001
```

After changing any of these, run:

```bash
php artisan config:clear
```

---

## Generating location stories

After adding public pins, run:

```bash
php artisan stories:cluster      # groups nearby public pins into stories
php artisan stories:summarize    # asks the LLM to write a title + summary for each
```

Story halos appear on `/map` automatically for any cluster with 3+ visible public pins.

To seed demo pins near a coordinate:

```bash
php artisan stories:demo-seed --user=1 --lat=14.5533 --lng=121.0450
```

---

## Testing

```bash
php artisan test
```

The current suite includes `tests/Feature/EchoMap/PrivacyTest.php`, which verifies:

- Private pins do not leak through `/pins`
- Friends-only pins only reach accepted friends
- Pending friend requests do NOT unlock friends-only content
- Owners always see their own pins
- Hidden pins are invisible to everyone
- `/stories` only returns ready stories
- `/pins` and `/stories` require authentication

---

## Admin

Admins log in through the same `/login` page as regular users. The `Admin` link and dashboard only appear when `is_admin = 1` on the user row.

To create an admin locally:

```bash
php artisan tinker

$u = App\Models\User::where('email', 'you@example.com')->first();
$u->is_admin = true;   // must be set directly — is_admin is not mass-assignable
$u->save();
```

The moderation dashboard is at `/admin/reports`.

---

## Directory highlights

```
app/
  Http/Controllers/         Pin, Comment, Reaction, Friend, Report controllers
  Http/Controllers/Admin/   Report moderation
  Http/Middleware/          EnsureUserIsAdmin
  Jobs/                     ClusterPinsJob, SummarizeStoryJob
  Models/                   User, Pin, Media, Comment, Reaction, Report, Friendship, LocationStory
  Console/Commands/         stories:cluster, stories:summarize, stories:demo-seed

database/migrations/        Full schema including location_stories and reports

resources/views/
  map.blade.php             Interactive Leaflet map (custom design)
  layouts/echo.blade.php    Shared layout for auth + dashboard
  auth/*.blade.php          Restyled login/register/forgot/reset
  admin/reports/            Moderation dashboard

tests/Feature/EchoMap/      PrivacyTest (7 assertions for the hard privacy rule)
```

---

## Author

**Dodis Dolohan** — Capstone project

---

## License

This project is built on Laravel, which is open-sourced software licensed under the MIT license.
