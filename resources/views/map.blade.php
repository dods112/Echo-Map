<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Echo-Map</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@600;700&family=Figtree:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js">

        const CURRENT_USER_ID = {{ (int) auth()->id() }};
</script>
    <style>
        :root {
            --ink: #172A33;
            --mist: #EDF1F2;
            --surface: #FFFFFF;
            --line: #D5DEE1;
            --muted: #5B6E76;
            --signal: #1F6F78;
            --signal-dark: #17565D;
            --display: 'Bricolage Grotesque', 'Figtree', system-ui, sans-serif;
        }
        * { box-sizing: border-box; }
        html, body { height: 100%; min-height: 100vh; margin: 0; }
        body {
            display: flex; flex-direction: column;
            font-family: 'Figtree', system-ui, sans-serif;
            color: var(--ink); background: var(--mist);
            font-size: 15px; line-height: 1.45;
        }
        :focus-visible { outline: 2px solid var(--signal); outline-offset: 2px; }

        /* Top bar */
        .bar {
            display: flex; align-items: center; gap: 18px; flex-wrap: wrap;
            padding: 10px 16px; background: var(--surface);
            border-bottom: 1px solid var(--line); z-index: 1000;
        }
        .brand { display: flex; align-items: center; gap: 8px; font-family: var(--display); font-weight: 700; font-size: 20px; color: var(--ink); text-decoration: none; }
        .brand svg { color: var(--signal); }
        .chips { display: flex; gap: 6px; overflow-x: auto; flex: 1; min-width: 220px; padding: 2px; }
        .chip {
            display: inline-flex; align-items: center; gap: 7px; white-space: nowrap;
            border: 1px solid var(--line); background: var(--surface); color: var(--ink);
            border-radius: 999px; padding: 5px 12px; font: inherit; font-size: 14px; cursor: pointer;
        }
        .chip i { width: 9px; height: 9px; border-radius: 50%; background: var(--c, var(--ink)); }
        .chip[aria-pressed="true"] { background: var(--ink); color: #fff; border-color: var(--ink); }
        .chip:hover { border-color: var(--ink); }
        .who { display: flex; align-items: center; gap: 14px; font-size: 14px; color: var(--muted); }
        .who a, .who button { color: var(--ink); background: none; border: 0; font: inherit; cursor: pointer; text-decoration: underline; text-underline-offset: 3px; padding: 0; }

        /* Stage */
        .stage { position: relative; flex: 1; min-height: 0; }
        #map { position: absolute; inset: 0; background: var(--mist); }
        .leaflet-tile-pane { filter: saturate(.3) contrast(.95) brightness(1.05); }

        /* Add-a-memory panel */
        .panel {
            position: absolute; left: 16px; bottom: 24px; width: 380px;
            max-height: calc(100% - 40px); overflow-y: auto;
            background: var(--surface); border: 1px solid var(--line); border-radius: 14px;
            box-shadow: 0 10px 34px rgba(23, 42, 51, .18); z-index: 900;
        }
        .panel-head {
            display: flex; align-items: center; gap: 12px; width: 100%;
            padding: 14px 16px; background: none; border: 0; text-align: left; cursor: pointer; font: inherit; color: inherit;
        }
        .panel-head .plus {
            display: grid; place-items: center; flex: none; width: 34px; height: 34px; border-radius: 50%;
            background: var(--signal); color: #fff; font-size: 22px; line-height: 1;
        }
        .panel-head strong { display: block; font-family: var(--display); font-size: 17px; }
        .panel-head small { color: var(--muted); font-size: 13px; }
        .panel.collapsed form { display: none; }
        form#pinForm { padding: 0 16px 16px; display: grid; gap: 12px; border-top: 1px solid var(--line); padding-top: 14px; }

        .field { display: grid; gap: 4px; border: 0; padding: 0; margin: 0; min-width: 0; }
        .field label, .field legend { font-size: 13px; font-weight: 500; color: var(--muted); padding: 0; }
        input[type=text], input[type=date], select, textarea {
            width: 100%; font: inherit; color: var(--ink); background: #fff;
            border: 1px solid var(--line); border-radius: 8px; padding: 9px 10px;
        }
        textarea { resize: vertical; }
        .row2 { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .segs { display: flex; border: 1px solid var(--line); border-radius: 8px; overflow: hidden; }
        .segs label { flex: 1; text-align: center; cursor: pointer; margin: 0; }
        .segs input { position: absolute; opacity: 0; pointer-events: none; }
        .segs span { display: block; padding: 8px 4px; font-size: 14px; color: var(--ink); border-right: 1px solid var(--line); }
        .segs label:last-child span { border-right: 0; }
        .segs input:checked + span { background: var(--ink); color: #fff; }
        .segs input:focus-visible + span { outline: 2px solid var(--signal); outline-offset: -2px; }
        .note { margin: 0; font-size: 13px; color: var(--muted); }
        .filebtn {
            display: block; text-align: center; padding: 8px 10px; border: 1px dashed #9DB0B7; border-radius: 8px;
            font-size: 14px; color: var(--ink); cursor: pointer;
        }
        .filebtn:hover { border-color: var(--signal); color: var(--signal-dark); }
        .filebtn input { position: absolute; opacity: 0; width: 1px; height: 1px; }
        .filebtn:focus-within { outline: 2px solid var(--signal); outline-offset: 2px; }
        .save {
            font: inherit; font-weight: 600; color: #fff; background: var(--signal);
            border: 0; border-radius: 8px; padding: 11px; cursor: pointer;
        }
        .save:hover { background: var(--signal-dark); }
        .save:disabled { opacity: .6; cursor: wait; }
        .msg { margin: 0; font-size: 14px; min-height: 1.2em; }
        .msg.ok { color: #1E7A4D; }
        .msg.err { color: #B3261E; }

        /* Pins and the echo marker */
        .pin-icon svg { display: block; filter: drop-shadow(0 2px 3px rgba(0,0,0,.3)); }
        .echo { position: relative; display: block; width: 24px; height: 24px; }
        .echo b { position: absolute; inset: 6px; border-radius: 50%; background: var(--signal); border: 2px solid #fff; box-shadow: 0 1px 4px rgba(0,0,0,.35); }
        .echo i {
            position: absolute; inset: 0; border-radius: 50%; border: 2px solid var(--signal);
            opacity: 0; animation: echo 2.4s ease-out infinite;
        }
        .echo i:nth-child(2) { animation-delay: .8s; }
        .echo i:nth-child(3) { animation-delay: 1.6s; }
        @keyframes echo { from { transform: scale(.4); opacity: .7; } to { transform: scale(3); opacity: 0; } }
        @media (prefers-reduced-motion: reduce) {
            .echo i { animation: none; opacity: .3; transform: scale(1.8); }
            .echo i:nth-child(n+2) { display: none; }
        }

        /* Popup card */
        .leaflet-popup-content-wrapper { border-radius: 12px; padding: 0; box-shadow: 0 10px 30px rgba(23,42,51,.25); }
        .leaflet-popup-content { margin: 14px; font-family: 'Figtree', system-ui, sans-serif; color: var(--ink); }
        .card h3 { margin: 0 0 4px; font-family: var(--display); font-size: 18px; line-height: 1.2; }
        .card p { margin: 0 0 8px; font-size: 14px; white-space: pre-wrap; }
        .shots { display: grid; grid-template-columns: repeat(2, 1fr); gap: 4px; margin-bottom: 10px; }
        .shots.n1 { grid-template-columns: 1fr; }
        .shots img { display: block; width: 100%; height: 100px; object-fit: cover; border-radius: 6px; }
        .shots.n1 img { height: 150px; }
        .card audio { width: 100%; margin: 2px 0 8px; }
        .tags { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 8px; }
        .tag { display: inline-flex; align-items: center; gap: 6px; font-size: 12px; border: 1px solid var(--line); border-radius: 999px; padding: 2px 9px; }
        .tag i { width: 8px; height: 8px; border-radius: 50%; }
        .by { font-size: 12px; color: var(--muted); }

        .actions { display: flex; gap: 8px; margin: 8px 0 2px; }
        .actions button { font: inherit; font-size: 13px; color: var(--ink); background: #fff; border: 1px solid var(--line); border-radius: 999px; padding: 4px 12px; cursor: pointer; }
        .actions .echo-btn[aria-pressed="true"] { background: var(--signal); border-color: var(--signal); color: #fff; }
        .comments { margin-top: 8px; border-top: 1px solid var(--line); padding-top: 8px; }
        .comments[hidden] { display: none; }
        .cm-list { max-height: 140px; overflow-y: auto; margin-bottom: 6px; }
        .cm { font-size: 13px; margin-bottom: 6px; overflow-wrap: anywhere; }
        .cm strong { font-weight: 600; }
        .cm-del { margin-left: 8px; font-size: 12px; color: var(--muted); background: none; border: 0; text-decoration: underline; cursor: pointer; padding: 0; }
        .cm-form { display: flex; gap: 6px; }
        .cm-form input { flex: 1; width: auto; min-width: 0; font: inherit; font-size: 13px; border: 1px solid var(--line); border-radius: 8px; padding: 6px 8px; }
        .cm-form button { font: inherit; font-size: 13px; color: #fff; background: var(--ink); border: 0; border-radius: 8px; padding: 6px 12px; cursor: pointer; }

        @media (max-width: 700px) {
            .panel { left: 8px; right: 8px; bottom: 8px; width: auto; max-height: 75%; }
            .who { margin-left: auto; }
            .brand { font-size: 18px; }
        }
        /* --- Pin marker polish (v2) --- */
.pin-icon { background: transparent !important; border: 0 !important; }
.pin-icon svg {
    display: block;
    filter: drop-shadow(0 4px 5px rgba(17,42,51,.28));
    transition: transform .18s cubic-bezier(.2,.8,.2,1), filter .18s ease;
    transform-origin: 50% 100%;
}
.leaflet-marker-icon.pin-icon:hover svg {
    transform: translateY(-2px) scale(1.08);
    filter: drop-shadow(0 6px 9px rgba(17,42,51,.34));
}
.leaflet-marker-icon.pin-icon:focus-visible svg {
    outline: 2px solid var(--signal);
    outline-offset: 3px;
    border-radius: 6px;
}
.leaflet-div-icon { background: transparent; border: 0; }

@keyframes pin-breathe {
    0%   { filter: drop-shadow(0 4px 5px rgba(17,42,51,.28)); }
    50%  { filter: drop-shadow(0 4px 10px rgba(31,111,120,.55)); }
    100% { filter: drop-shadow(0 4px 5px rgba(17,42,51,.28)); }
}
.leaflet-marker-icon.pin-icon.is-mine svg {
    animation: pin-breathe 3.5s ease-in-out infinite;
}

/* Drop a soft shadow under the tip so the pin looks anchored */
.pin-icon::after {
    content: '';
    position: absolute;
    left: 50%;
    bottom: -2px;
    width: 14px;
    height: 4px;
    transform: translateX(-50%);
    background: radial-gradient(ellipse at center, rgba(17,42,51,.28), rgba(17,42,51,0) 70%);
    border-radius: 50%;
    pointer-events: none;
}
/* --- Popup card styling (v2) --- */
.leaflet-popup-content-wrapper {
    border-radius: 14px;
    box-shadow: 0 12px 32px rgba(17,42,51,.18), 0 2px 6px rgba(17,42,51,.08);
    padding: 0;
    overflow: hidden;
    border: 1px solid var(--line);
}
.leaflet-popup-content {
    margin: 0;
    min-width: 240px;
    max-width: 300px;
    font-family: 'Figtree', system-ui, sans-serif;
}
.leaflet-popup-tip {
    box-shadow: 0 4px 12px rgba(17,42,51,.12);
}

/* theme accent bar at the top of the card */
.leaflet-popup-content .card {
    padding: 16px 16px 14px;
    border-top: 4px solid var(--pin, var(--signal));
    background: var(--surface);
}
.leaflet-popup-content .card h3 {
    margin: 0 0 4px;
    font-family: var(--display);
    font-weight: 700;
    font-size: 17px;
    line-height: 1.2;
    color: var(--ink);
    letter-spacing: -.01em;
}
.leaflet-popup-content .card p {
    margin: 0 0 10px;
    color: var(--muted);
    font-size: 14px;
    line-height: 1.4;
}

/* chips row (theme, visibility, date) */
.leaflet-popup-content .chips,
.leaflet-popup-content .meta {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    margin-bottom: 10px;
}
.leaflet-popup-content .chip,
.leaflet-popup-content .pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 10px;
    border-radius: 999px;
    background: var(--mist);
    color: var(--ink);
    border: 1px solid var(--line);
    font-size: 12.5px;
    line-height: 1;
    white-space: nowrap;
}
.leaflet-popup-content .chip i,
.leaflet-popup-content .pill i {
    width: 7px; height: 7px; border-radius: 50%; background: var(--pin, var(--signal));
}

/* "Pinned by" line */
.leaflet-popup-content .by {
    color: var(--muted);
    font-size: 13px;
    margin-bottom: 12px;
}

/* footer: Echo + Comments as real buttons */
.leaflet-popup-content .actions {
    display: flex;
    gap: 8px;
    border-top: 1px solid var(--line);
    padding-top: 12px;
}
.leaflet-popup-content .actions button {
    flex: 1;
    padding: 8px 10px;
    border-radius: 9px;
    border: 1px solid var(--line);
    background: #fff;
    color: var(--ink);
    font: inherit;
    font-size: 13px;
    cursor: pointer;
    transition: background .15s ease, border-color .15s ease, transform .12s ease;
}
.leaflet-popup-content .actions button:hover {
    background: var(--mist);
    border-color: var(--ink);
}
.leaflet-popup-content .actions button:active { transform: translateY(1px); }
.leaflet-popup-content .actions button[aria-pressed="true"] {
    background: var(--signal);
    border-color: var(--signal);
    color: #fff;
}
.leaflet-popup-content .actions button[aria-pressed="true"] [data-count] { color: #fff; }

/* Close button top-right, subtle */
.leaflet-container a.leaflet-popup-close-button {
    top: 8px; right: 10px;
    width: 24px; height: 24px;
    color: var(--muted);
    font-size: 18px; line-height: 22px;
    border-radius: 50%;
    background: transparent;
}
.leaflet-container a.leaflet-popup-close-button:hover {
    color: var(--ink);
    background: var(--mist);
}

/* Photos grid (only styling the container, not the images) */
.leaflet-popup-content .shots {
    display: grid;
    gap: 4px;
    grid-template-columns: 1fr;
    margin: -16px -16px 12px;
}
.leaflet-popup-content .shots.n2 { grid-template-columns: 1fr 1fr; }
.leaflet-popup-content .shots a { display: block; }
.leaflet-popup-content .shots img {
    width: 100%; height: 120px; object-fit: cover; display: block;
    border-radius: 0;
}
/* --- Time-period filter (range slider) --- */
.range-wrap {
    display: flex; align-items: center; gap: 10px;
    padding: 6px 10px; margin-left: 6px;
    border: 1px solid var(--line); border-radius: 999px;
    background: var(--surface); font-size: 13px; color: var(--muted);
    white-space: nowrap;
}
.range-wrap .dates { font-variant-numeric: tabular-nums; color: var(--ink); }
.range-wrap input[type="range"] {
    -webkit-appearance: none; appearance: none;
    height: 4px; border-radius: 999px; background: var(--line); outline: none;
}
.range-wrap input[type="range"]::-webkit-slider-thumb {
    -webkit-appearance: none; appearance: none;
    width: 14px; height: 14px; border-radius: 50%;
    background: var(--signal); border: 2px solid #fff;
    box-shadow: 0 1px 3px rgba(17,42,51,.25); cursor: pointer;
}
.range-wrap input[type="range"]::-moz-range-thumb {
    width: 14px; height: 14px; border-radius: 50%;
    background: var(--signal); border: 2px solid #fff; cursor: pointer;
}
.range-wrap .reset {
    border: 0; background: transparent; color: var(--muted);
    font: inherit; cursor: pointer; text-decoration: underline;
    text-underline-offset: 3px; padding: 0 2px;
}
/* --- Story halo + popup --- */
.story-halo {
    stroke-dasharray: 6 6;
    stroke-width: 1.5;
    fill-opacity: .08;
    transition: fill-opacity .15s ease, stroke-width .15s ease;
    cursor: pointer;
}
.story-halo:hover { fill-opacity: .16; stroke-width: 2; }

.leaflet-popup-content .story-card {
    padding: 16px 16px 14px;
    background: var(--surface);
    border-top: 4px solid var(--signal);
}
.leaflet-popup-content .story-card h3 {
    margin: 0 0 6px;
    font-family: var(--display);
    font-weight: 700;
    font-size: 17px;
    line-height: 1.2;
    color: var(--ink);
    letter-spacing: -.01em;
}
.leaflet-popup-content .story-card p {
    margin: 0 0 12px;
    color: var(--muted);
    font-size: 14px;
    line-height: 1.5;
}
.leaflet-popup-content .story-card .meta {
    display: flex; align-items: center; gap: 8px;
    padding-top: 10px;
    border-top: 1px solid var(--line);
}
.leaflet-popup-content .story-card .meta button {
    padding: 7px 12px;
    border-radius: 9px;
    border: 1px solid var(--signal);
    background: var(--signal);
    color: #fff;
    font: inherit;
    font-size: 13px;
    cursor: pointer;
    transition: filter .15s ease, transform .12s ease;
}
.leaflet-popup-content .story-card .meta button:hover { filter: brightness(1.05); }
.leaflet-popup-content .story-card .meta button:active { transform: translateY(1px); }
.leaflet-popup-content .story-card .meta .count {
    color: var(--muted);
    font-size: 13px;
    margin-left: auto;
}
/* --- Report link styling (specific enough to beat Leaflet defaults) --- */
.leaflet-popup-content .card .report-row {
    margin-top: 10px;
    padding-top: 10px;
    border-top: 1px solid var(--line);
    text-align: right;
}
.leaflet-popup-content .card .report-row .report-btn {
    appearance: none;
    -webkit-appearance: none;
    background: transparent !important;
    border: 0 !important;
    padding: 0;
    margin: 0;
    color: var(--muted) !important;
    font: inherit;
    font-size: 12.5px;
    line-height: 1;
    cursor: pointer;
    text-decoration: underline;
    text-underline-offset: 3px;
}
.leaflet-popup-content .card .report-row .report-btn:hover {
    color: #8a1f1f !important;
}
.leaflet-popup-content .card .report-row .report-btn:focus-visible {
    outline: 2px solid var(--signal);
    outline-offset: 3px;
    border-radius: 4px;
}

/* Comment report link */
.leaflet-popup-content .cm .cm-report {
    appearance: none;
    -webkit-appearance: none;
    background: transparent !important;
    border: 0 !important;
    padding: 0 4px;
    margin-left: 6px;
    color: var(--muted) !important;
    font: inherit;
    font-size: 12px;
    cursor: pointer;
    text-decoration: underline;
    text-underline-offset: 3px;
}
.leaflet-popup-content .cm .cm-report:hover { color: #8a1f1f !important; }
    </style>
</head>
<body>
    <header class="bar">
        <a class="brand" href="{{ route('map') }}">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                <circle cx="12" cy="12" r="2.6" fill="currentColor" stroke="none"/>
                <circle cx="12" cy="12" r="6.2" stroke-width="1.6"/>
                <circle cx="12" cy="12" r="10" stroke-width="1.2" opacity=".5"/>
            </svg>
            Echo-Map
        </a>
        <div class="chips" id="chips" role="group" aria-label="Filter pins by theme"></div>
<div class="range-wrap" id="rangeWrap" hidden>
    <span>From</span>
    <input type="range" id="rangeFrom" min="0" max="0" step="1" value="0">
    <span class="dates" id="rangeFromLabel">—</span>
    <span>to</span>
    <input type="range" id="rangeTo" min="0" max="0" step="1" value="0">
    <span class="dates" id="rangeToLabel">—</span>
    <button type="button" class="reset" id="rangeReset">All time</button>
</div>

        <div class="who">
            <span>{{ auth()->user()->name }}</span>
            @unless (auth()->user()->is_admin)<a href="/friends" style="text-decoration:underline;text-underline-offset:3px;color:inherit;">Friends</a>@endunless
            <a href="{{ route('dashboard') }}">Dashboard</a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit">Log out</button>
            </form>
        </div>
    </header>

    <main class="stage">
        <div id="map"></div>

        <section class="panel collapsed" id="panel" aria-label="Add a memory">
            <button type="button" class="panel-head" id="panelToggle" aria-expanded="false" aria-controls="pinForm">
                <span class="plus" aria-hidden="true">+</span>
                <span>
                    <strong>Add a memory</strong>
                    <small id="hint">Click the map to choose a spot</small>
                </span>
            </button>

            <form id="pinForm">
                <input type="hidden" id="latitude">
                <input type="hidden" id="longitude">

                <div class="field">
                    <label for="title">Title</label>
                    <input type="text" id="title" maxlength="150" required placeholder="What happened here?">
                </div>
                <div class="field">
                    <label for="body">Your memory</label>
                    <textarea id="body" rows="3" maxlength="2000" placeholder="Tell the story of this spot"></textarea>
                </div>
                <div class="row2">
                    <div class="field">
                        <label for="theme">Theme</label>
                        <select id="theme">
                            <option value="">No theme</option>
                            <option>Memory</option>
                            <option>History</option>
                            <option>Event</option>
                            <option>Hangout</option>
                            <option>Other</option>
                        </select>
                    </div>
                    <div class="field">
                        <label for="event_date">When it happened</label>
                        <input type="date" id="event_date">
                    </div>
                </div>

                <fieldset class="field">
                    <legend>Who can see it</legend>
                    <div class="segs">
                        <label><input type="radio" name="visibility" value="public" checked><span>Public</span></label>
                        <label><input type="radio" name="visibility" value="friends"><span>Friends</span></label>
                        <label><input type="radio" name="visibility" value="private"><span>Only me</span></label>
                    </div>
                    <p class="note" id="visHint">Everyone on Echo-Map can see this pin.</p>
                </fieldset>

                <div class="row2">
                    <label class="filebtn">Add photos
                        <input type="file" id="photos" accept="image/png,image/jpeg,image/webp" multiple>
                    </label>
                    <label class="filebtn">Add audio
                        <input type="file" id="audio" accept="audio/*">
                    </label>
                </div>
                <p class="note" id="fileNote">Up to 5 photos and 1 audio clip.</p>

                <button type="submit" class="save" id="saveBtn">Save memory</button>
                <p class="msg" id="msg" role="status" aria-live="polite"></p>
            </form>
        </section>
    </main>

    <script>
        
        const CURRENT_USER_ID = {{ (int) auth()->id() }};
const THEMES = {
            Memory: '#D6336C',
            History: '#B5701B',
            Event: '#2F6FDE',
            Hangout: '#2F9E6B',
            Other: '#6B7A82'
        };
        const INK = '#172A33';
        const VIS_HINTS = {
            public: 'Everyone on Echo-Map can see this pin.',
            friends: 'Only your accepted friends can see this pin.',
            private: 'Only you can see this pin.'
        };

        const map = L.map('map', {
                        minZoom: 2,
            // Restrict to one copy of the world so dragging can't loop.
            maxBounds: [[-85, -180], [85, 180]],
            maxBoundsViscosity: 1.0,
            zoomControl: false,
        }).setView([12.88, 121.77], 6);

        // ---- Ensure Leaflet sizes the map after layout settles ----
        function _echomapResize() {
            try { map.invalidateSize(); } catch (e) {}
        }
        window.addEventListener('load', () => setTimeout(_echomapResize, 0));

        // ---- Keep the map from zooming out past where tiles cover the viewport ----
        function enforceMinZoom() {
            // World is 256 * 2^z px on each side. Pick the smallest z where both
            // dimensions cover the container.
            const size  = map.getSize();
            const needX = Math.ceil(Math.log2(size.x / 256));
            const needY = Math.ceil(Math.log2(size.y / 256));
            const need  = Math.max(needX, needY, 1); // never below 1
            map.setMinZoom(need);
            if (map.getZoom() < need) { map.setZoom(need); }
        }
        window.addEventListener('load',   enforceMinZoom);
        window.addEventListener('resize', enforceMinZoom);
        setTimeout(enforceMinZoom, 50);
        window.addEventListener('resize', _echomapResize);
        // Also run once after a beat, in case the flex layout finalises late
        setTimeout(_echomapResize, 250);
        L.control.zoom({ position: 'topright' }).addTo(map);
        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            noWrap: true,
            
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);
        map.locate({ setView: true, maxZoom: 16 });

        const pinLayer = L.layerGroup().addTo(map);
        let picker = null;
        let currentTheme = '';
        const pinsById = {};

        const $ = (id) => document.getElementById(id);
        const panel = $('panel');

        function esc(s) {
            const d = document.createElement('div');
            d.textContent = s ?? '';
            return d.innerHTML;
        }

        function pinIcon(color) {
            // Stable gradient id per color so multiple pins reuse the same <defs>
            const gid = 'pg-' + String(color).replace(/[^a-z0-9]/gi, '');
            return L.divIcon({
                className: 'pin-icon',
                html: `<svg width="34" height="46" viewBox="0 0 34 46" aria-hidden="true">
                    <defs>
                        <linearGradient id="${gid}" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%"  stop-color="${color}" stop-opacity="1"/>
                            <stop offset="100%" stop-color="${color}" stop-opacity=".72"/>
                        </linearGradient>
                        <radialGradient id="${gid}-gloss" cx="50%" cy="35%" r="60%">
                            <stop offset="0%"  stop-color="#ffffff" stop-opacity=".55"/>
                            <stop offset="60%" stop-color="#ffffff" stop-opacity="0"/>
                        </radialGradient>
                    </defs>
                    <!-- outer ring -->
                    <path d="M17 45C17 45 3 28 3 16.5A14 14 0 0 1 31 16.5C31 28 17 45 17 45Z"
                          fill="#ffffff" stroke="rgba(23,42,51,.12)" stroke-width="1"/>
                    <!-- body with gradient -->
                    <path d="M17 42C17 42 5.5 27 5.5 16.5A11.5 11.5 0 0 1 28.5 16.5C28.5 27 17 42 17 42Z"
                          fill="url(#${gid})"/>
                    <!-- gloss on the head -->
                    <path d="M17 42C17 42 5.5 27 5.5 16.5A11.5 11.5 0 0 1 28.5 16.5C28.5 27 17 42 17 42Z"
                          fill="url(#${gid}-gloss)"/>
                    <!-- inner white ring -->
                    <circle cx="17" cy="16.5" r="6.4" fill="#ffffff" opacity=".95"/>
                    <!-- theme dot -->
                    <circle cx="17" cy="16.5" r="3.4" fill="${color}"/>
                    <!-- inner highlight -->
                    <circle cx="17" cy="15.2" r="1.1" fill="#ffffff" opacity=".8"/>
                </svg>`,
                iconSize: [34, 46],
                iconAnchor: [17, 45],
                popupAnchor: [0, -42]
            });
        }

        const echoIcon = L.divIcon({
            className: '',
            html: '<span class="echo"><i></i><i></i><i></i><b></b></span>',
            iconSize: [24, 24],
            iconAnchor: [12, 12]
        });

        function popupHtml(p) {
            const color = THEMES[p.theme] || INK;
            const photos = (p.media || []).filter((m) => m.type === 'photo');
            const audio = (p.media || []).filter((m) => m.type === 'audio');
            const shots = photos.length
                ? `<div class="shots n${Math.min(photos.length, 2) === 1 ? 1 : 2}">` +
                  photos.map((m) => `<a href="/storage/${esc(m.path)}" target="_blank" rel="noopener"><img loading="lazy" src="/storage/${esc(m.path)}" alt=""></a>`).join('') +
                  '</div>'
                : '';
            const clips = audio.map((m) => `<audio controls preload="none" src="/storage/${esc(m.path)}"></audio>`).join('');
            const when = p.event_date
                ? new Date(p.event_date.slice(0, 10) + 'T00:00:00').toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' })
                : '';
            const visLabel = { public: 'Public', friends: 'Friends', private: 'Only me' }[p.visibility] || '';
            return `<div class="card" data-pin="${p.id}" style="--pin: ${color};">
                ${shots}
                <h3>${esc(p.title)}</h3>
                ${p.body ? `<p>${esc(p.body)}</p>` : ''}
                ${clips}
                <div class="tags">
                    <span class="tag"><i style="background:${color}"></i>${esc(p.theme || 'No theme')}</span>
                    <span class="tag">${esc(visLabel)}</span>
                    ${when ? `<span class="tag">${esc(when)}</span>` : ''}
                </div>
                <div class="by">Pinned by ${esc(p.user?.name)}</div>
                <div class="actions">
                    <button type="button" class="echo-btn" data-echo aria-pressed="${p.echoed ? 'true' : 'false'}">Echo <span data-count>${p.reactions_count || 0}</span></button>
                    <button type="button" data-toggle-comments aria-expanded="false">Comments <span data-ccount>${p.comments_count || 0}</span></button>
                </div>
                <div class="report-row">
                    <button type="button" class="report-btn" onclick="window.__echoReport('pin', ${p.id}, 'pin')">Report</button>
                </div>
                <div class="comments" hidden>
                    <div class="cm-list" data-list></div>
                    <form class="cm-form" data-form>
                        <input type="text" maxlength="500" placeholder="Add a comment" aria-label="Add a comment" required>
                        <button type="submit">Post</button>
                    </form>
                </div>
            </div>`;
        }

        function addPinMarker(p) {
            const color = THEMES[p.theme] || INK;
            const marker = L.marker([p.latitude, p.longitude], { icon: pinIcon(color), title: p.title });
            // Tag own pins so the .is-mine breathe animation runs.
            if (typeof CURRENT_USER_ID !== 'undefined' && Number(p.user_id) === Number(CURRENT_USER_ID)) {
                marker.on('add', () => {
                    const el = marker.getElement();
                    if (el) el.classList.add('is-mine');
                });
            }
            marker
                .addTo(pinLayer)
                .bindPopup(() => { pinsById[p.id] = p; return popupHtml(p); }, { minWidth: 240, maxWidth: 280 });
        }

        const currentRange = { from: null, to: null };
        /* ---------- AI Location Stories ---------- */
        const storyLayer = L.layerGroup().addTo(map);

        function storyHalo(story) {
            return L.circle([story.center_lat, story.center_lng], {
                radius: story.radius_m,
                color: THEMES.Memory || '#1F6F78',
                weight: 1.5,
                fillColor: THEMES.Memory || '#1F6F78',
                fillOpacity: 0.08,
                className: 'story-halo',
            });
        }

        function storyPopupHtml(story) {
            const safeTitle   = esc(story.title || 'Location story');
            const safeSummary = esc(story.summary || '');
            return `<div class="story-card" data-story="${story.id}">
                <h3>${safeTitle}</h3>
                <p>${safeSummary}</p>
                <div class="meta">
                    <button type="button" data-zoom="${story.id}">Zoom in</button>
                    <span class="count">${story.pin_count} notes nearby</span>
                </div>
            </div>`;
        }

        function bindStoryZoom(halo, story) {
            halo.on('popupopen', (e) => {
                const node = e.popup.getElement();
                if (!node) return;
                const btn = node.querySelector('button[data-zoom]');
                if (!btn) return;
                btn.addEventListener('click', () => {
                    map.closePopup();
                    const pts = story.pin_ids
                        .map((id) => pinsById[id])
                        .filter(Boolean)
                        .map((p) => [Number(p.latitude), Number(p.longitude)]);
                    if (pts.length >= 2) {
                        map.fitBounds(L.latLngBounds(pts).pad(0.25));
                    } else if (pts.length === 1) {
                        map.setView(pts[0], 17);
                    } else {
                        map.setView([story.center_lat, story.center_lng], 16);
                    }
                });
            });
        }

        async function loadStories() {
            const params = new URLSearchParams();
            if (currentTheme)      params.set('theme', currentTheme);
            if (currentRange.from) params.set('from',  currentRange.from);
            if (currentRange.to)   params.set('to',    currentRange.to);
            const qs  = params.toString();
            const url = `/stories${qs ? '?' + qs : ''}`;

            let stories = [];
            try {
                const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
                if (res.ok) stories = await res.json();
            } catch (_) { /* silent */ }

            storyLayer.clearLayers();
            stories.forEach((story) => {
                const halo = storyHalo(story);
                halo.bindPopup(() => storyPopupHtml(story), { minWidth: 240, maxWidth: 300 });
                bindStoryZoom(halo, story);
                halo.addTo(storyLayer);
            });
        }

        async function loadPins() {
            const params = new URLSearchParams();
            if (currentTheme)        params.set('theme', currentTheme);
            if (currentRange.from)   params.set('from',  currentRange.from);
            if (currentRange.to)     params.set('to',    currentRange.to);
            const qs  = params.toString();
            const url = `{{ route('pins.list') }}${qs ? '?' + qs : ''}`;

            const res  = await fetch(url, { headers: { 'Accept': 'application/json' } });
            const pins = await res.json();
            pinLayer.clearLayers();
            pins.forEach(addPinMarker);
        
            loadStories();
        }

        // Expose a small hook for the time-filter slider
        let _rangeDebounce = null;
        window.__echoMap = {
            setDateRange({ from, to, active }) {
                currentRange.from = active ? from : null;
                currentRange.to   = active ? to   : null;
                clearTimeout(_rangeDebounce);
                _rangeDebounce = setTimeout(loadPins, 250);
            }
        };

        /* Theme filter chips */
        const chipsEl = $('chips');
        ['All', ...Object.keys(THEMES)].forEach((name) => {
            const b = document.createElement('button');
            b.type = 'button';
            b.className = 'chip';
            b.setAttribute('aria-pressed', name === 'All' ? 'true' : 'false');
            b.innerHTML = name === 'All' ? 'All themes' : `<i style="--c:${THEMES[name]}"></i>${name}`;
            b.addEventListener('click', () => {
                currentTheme = name === 'All' ? '' : name;
                chipsEl.querySelectorAll('.chip').forEach((c) => c.setAttribute('aria-pressed', c === b ? 'true' : 'false'));
                loadPins();
            });
            chipsEl.appendChild(b);
        });

        /* Panel open/close */
        function setPanel(open) {
            panel.classList.toggle('collapsed', !open);
            $('panelToggle').setAttribute('aria-expanded', open ? 'true' : 'false');
        }
        $('panelToggle').addEventListener('click', () => setPanel(panel.classList.contains('collapsed')));

        /* Choose a spot */
        map.on('click', (e) => {
            if (picker) { picker.setLatLng(e.latlng); } else { picker = L.marker(e.latlng, { icon: echoIcon, keyboard: false }).addTo(map); }
            $('latitude').value = e.latlng.lat.toFixed(7);
            $('longitude').value = e.latlng.lng.toFixed(7);
            $('hint').textContent = 'Spot chosen. Add the details below.';
            setPanel(true);
        });

        /* Small form helpers */
        document.querySelectorAll('input[name=visibility]').forEach((r) => {
            r.addEventListener('change', () => { $('visHint').textContent = VIS_HINTS[r.value]; });
        });
        function updateFileNote() {
            const n = $('photos').files.length;
            const a = $('audio').files[0];
            const parts = [];
            if (n) parts.push(n + (n === 1 ? ' photo' : ' photos'));
            if (a) parts.push('audio: ' + a.name);
            $('fileNote').textContent = parts.length ? parts.join(', ') + ' selected.' : 'Up to 5 photos and 1 audio clip.';
        }
        $('photos').addEventListener('change', updateFileNote);
        $('audio').addEventListener('change', updateFileNote);

        function say(text, kind) {
            const m = $('msg');
            m.textContent = text;
            m.className = 'msg' + (kind ? ' ' + kind : '');
        }

        /* Save */
        $('pinForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            if (!$('latitude').value) {
                say('Click the map first to choose a spot.', 'err');
                return;
            }

            const fd = new FormData();
            fd.append('title', $('title').value);
            fd.append('body', $('body').value);
            fd.append('latitude', $('latitude').value);
            fd.append('longitude', $('longitude').value);
            fd.append('visibility', document.querySelector('input[name=visibility]:checked').value);
            if ($('theme').value) fd.append('theme', $('theme').value);
            if ($('event_date').value) fd.append('event_date', $('event_date').value);
            for (const f of $('photos').files) fd.append('photos[]', f);
            if ($('audio').files[0]) fd.append('audio', $('audio').files[0]);

            const btn = $('saveBtn');
            btn.disabled = true;
            say('Saving...', '');
            try {
                const res = await fetch(`{{ route('pins.store') }}`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: fd
                });
                if (res.ok) {
                    e.target.reset();
                    $('latitude').value = '';
                    $('longitude').value = '';
                    $('visHint').textContent = VIS_HINTS.public;
                    updateFileNote();
                    if (picker) { map.removeLayer(picker); picker = null; }
                    $('hint').textContent = 'Click the map to choose a spot';
                    say('Memory saved.', 'ok');
                    loadPins();
                } else if (res.status === 422) {
                    const j = await res.json();
                    say(Object.values(j.errors).flat()[0], 'err');
                } else {
                    say('Could not save (error ' + res.status + '). Try again.', 'err');
                }
            } catch (err) {
                say('Could not reach the server. Check your connection and try again.', 'err');
            } finally {
                btn.disabled = false;
            }
        });

        loadPins();
    map.on('popupopen', (ev) => {
            const root = ev.popup.getElement();
            const card = root && root.querySelector('.card');
            if (!card) return;
            const id = card.dataset.pin;
            const pin = pinsById[id];
            const headers = {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            };
            const echoBtn = card.querySelector('[data-echo]');
            const toggle = card.querySelector('[data-toggle-comments]');
            const box = card.querySelector('.comments');
            const list = card.querySelector('[data-list]');
            const form = card.querySelector('[data-form]');
            const ccount = card.querySelector('[data-ccount]');
            let loaded = false;

            function setCount(n) {
                ccount.textContent = n;
                if (pin) pin.comments_count = n;
            }

            function renderComment(c) {
                const row = document.createElement('div');
                row.className = 'cm';
                const who = document.createElement('strong');
                who.textContent = c.author;
                const text = document.createElement('span');
                text.textContent = ' ' + c.body;
                row.append(who, text);
                if (c.can_delete) {
                    const del = document.createElement('button');
                    del.type = 'button';
                    del.className = 'cm-del';
                    del.textContent = 'Delete';
                    del.addEventListener('click', async () => {
                        const r = await fetch('/comments/' + c.id, { method: 'DELETE', headers });
                        if (r.ok) {
                            row.remove();
                            setCount(Math.max(0, Number(ccount.textContent) - 1));
                            ev.popup.update();
                        }
                    });
                    row.appendChild(del);
                }
                // Report link on every comment (not only your own).
                const rep = document.createElement('button');
                rep.type = 'button';
                rep.className = 'cm-report';
                rep.textContent = 'Report';
                rep.addEventListener('click', () => window.__echoReport && window.__echoReport('comment', c.id, 'comment'));
                row.appendChild(rep);
                return row;
            }

            async function loadComments() {
                const r = await fetch('/pins/' + id + '/comments', { headers });
                if (!r.ok) return;
                const items = await r.json();
                list.replaceChildren(...items.map(renderComment));
                setCount(items.length);
                loaded = true;
                ev.popup.update();
            }

            toggle.addEventListener('click', () => {
                const open = box.hidden;
                box.hidden = !open;
                toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
                if (open && !loaded) loadComments();
                ev.popup.update();
            });

            echoBtn.addEventListener('click', async () => {
                const r = await fetch('/pins/' + id + '/echo', { method: 'POST', headers });
                if (!r.ok) return;
                const j = await r.json();
                echoBtn.setAttribute('aria-pressed', j.echoed ? 'true' : 'false');
                echoBtn.querySelector('[data-count]').textContent = j.count;
                if (pin) { pin.echoed = j.echoed; pin.reactions_count = j.count; }
            });

            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                const input = form.querySelector('input');
                const body = input.value.trim();
                if (!body) return;
                const r = await fetch('/pins/' + id + '/comments', {
                    method: 'POST',
                    headers: { ...headers, 'Content-Type': 'application/json' },
                    body: JSON.stringify({ body })
                });
                if (r.ok) {
                    const c = await r.json();
                    list.appendChild(renderComment(c));
                    setCount(Number(ccount.textContent) + 1);
                    input.value = '';
                    ev.popup.update();
                }
            });
        });
    
    // ---------- Time-period filter ----------
    (function initTimeFilter() {
        const wrap    = document.getElementById('rangeWrap');
        const fromEl  = document.getElementById('rangeFrom');
        const toEl    = document.getElementById('rangeTo');
        const fromLbl = document.getElementById('rangeFromLabel');
        const toLbl   = document.getElementById('rangeToLabel');
        const reset   = document.getElementById('rangeReset');
        if (!wrap) return;

        let bounds = { min: null, max: null }; // ISO date strings

        const fmt = (iso) => iso ? new Date(iso + 'T00:00:00')
            .toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' }) : '—';

        const dayMs  = 86400000;
        const toIdx  = (iso) => Math.round((new Date(iso + 'T00:00:00') - new Date(bounds.min + 'T00:00:00')) / dayMs);
        const toIso  = (i)   => new Date(new Date(bounds.min + 'T00:00:00').getTime() + i * dayMs)
            .toISOString().slice(0, 10);

        function sync() {
            if (Number(fromEl.value) > Number(toEl.value)) {
                // keep handles from crossing
                if (document.activeElement === fromEl) toEl.value = fromEl.value;
                else fromEl.value = toEl.value;
            }
            fromLbl.textContent = fmt(toIso(Number(fromEl.value)));
            toLbl.textContent   = fmt(toIso(Number(toEl.value)));
        }

        function applyFilter() {
            if (!window.__echoMap) return; // set by your existing map script
            window.__echoMap.setDateRange({
                from: toIso(Number(fromEl.value)),
                to:   toIso(Number(toEl.value)),
                active: Number(fromEl.value) > 0 || Number(toEl.value) < Number(toEl.max)
            });
        }

        fromEl.addEventListener('input', () => { sync(); applyFilter(); });
        toEl  .addEventListener('input', () => { sync(); applyFilter(); });
        reset.addEventListener('click', () => {
            fromEl.value = 0;
            toEl.value   = toEl.max;
            sync();
            applyFilter();
        });

        // Fetch bounds once
        fetch('/pins/date-range', { headers: { 'Accept': 'application/json' } })
            .then((r) => r.ok ? r.json() : null)
            .then((data) => {
                if (!data || !data.min || !data.max) return;
                bounds = data;
                const span = toIdx(data.max);
                fromEl.min = 0; fromEl.max = span; fromEl.value = 0;
                toEl  .min = 0; toEl  .max = span; toEl  .value = span;
                sync();
                wrap.hidden = false;
            })
            .catch(() => { /* silent - no range UI on failure */ });

        // expose for the map script
        window.__echoTimeFilter = { sync, applyFilter };
    })();
    </script>
</body>
</html>
