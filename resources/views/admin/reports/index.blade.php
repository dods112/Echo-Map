<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Reports - Echo-Map Admin</title>
<style>
  :root { --bg:#f4f6f8; --card:#fff; --ink:#172A33; --muted:#5B6E76; --line:#D5DEE1; --signal:#1F6F78; }
  * { box-sizing: border-box; }
  body { margin:0; font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; background:var(--bg); color:var(--ink); font-size:15px; }
  header { background:var(--card); border-bottom:1px solid var(--line); padding:14px 22px; display:flex; align-items:center; justify-content:space-between; }
  header h1 { font-size:18px; margin:0; }
  header a, header button.link { color:var(--ink); text-decoration:none; margin-left:14px; font-size:14px; text-decoration:underline; text-underline-offset:3px; background:none; border:0; font:inherit; cursor:pointer; }
  .wrap { max-width:1000px; margin:26px auto; padding:0 18px; }
  .tabs { display:flex; gap:6px; margin-bottom:16px; }
  .tabs a { padding:6px 12px; border-radius:999px; border:1px solid var(--line); background:var(--card); color:var(--ink); text-decoration:none; font-size:13px; }
  .tabs a[aria-current="page"] { background:var(--ink); color:#fff; border-color:var(--ink); }
  .card { background:var(--card); border:1px solid var(--line); border-radius:10px; padding:16px 18px; margin-bottom:14px; }
  .row { display:flex; justify-content:space-between; align-items:flex-start; gap:16px; }
  .who { font-weight:600; }
  .meta { color:var(--muted); font-size:13px; margin-top:2px; }
  .reason { margin:10px 0; padding:10px 12px; background:var(--bg); border-radius:8px; font-size:14px; }
  .pill { display:inline-block; padding:2px 9px; border-radius:999px; font-size:12px; background:var(--bg); border:1px solid var(--line); }
  .pill.pending { background:#fff7e6; border-color:#f4d38a; color:#7a5300; }
  .pill.reviewed { background:#e7f5ec; border-color:#b7dfc4; color:#1b6b3a; }
  .pill.dismissed { background:#f0f0f0; border-color:#d5d5d5; color:#555; }
  .actions { display:flex; gap:8px; margin-top:12px; }
  .actions form { display:inline; }
  .actions button { padding:7px 12px; border-radius:8px; border:1px solid var(--line); background:#fff; cursor:pointer; font:inherit; font-size:13px; }
  .actions button.hide { background:#1F6F78; color:#fff; border-color:#1F6F78; }
  .actions button.dismiss { background:#fff; }
  .actions button.restore { background:#fff7e6; border-color:#f4d38a; }
  .flash { padding:10px 14px; border-radius:8px; margin-bottom:14px; font-size:14px; background:#e7f5ec; color:#1b6b3a; }
  .empty { color:var(--muted); padding:40px; text-align:center; background:var(--card); border:1px solid var(--line); border-radius:10px; }
  .pager { margin-top:16px; }
  .pager a, .pager span { padding:4px 8px; }
</style>
</head>
<body>
<header>
  <h1>Echo-Map &middot; Admin</h1>
  <nav>
    <a href="/map">Map</a>
    <a href="/dashboard">Dashboard</a>
    <form method="POST" action="{{ route('logout') }}" style="display:inline">
      @csrf
      <button type="submit" class="link">Log out</button>
    </form>
  </nav>
</header>

<div class="wrap">
  <h2 style="margin:0 0 14px;">Reports</h2>

  @if (session('status'))
    <div class="flash">{{ session('status') }}</div>
  @endif

  <div class="tabs">
    @foreach (['pending','reviewed','dismissed','all'] as $s)
      <a href="{{ route('admin.reports.index', ['status' => $s]) }}" @if($status === $s) aria-current="page" @endif>{{ ucfirst($s) }}</a>
    @endforeach
  </div>

  @forelse ($reports as $report)
    <div class="card">
      <div class="row">
        <div>
          <div class="who">
            Report #{{ $report->id }}
            <span class="pill {{ $report->status }}">{{ $report->status }}</span>
          </div>
          <div class="meta">
            {{ $report->targetLabel() }}
            &middot; reported by {{ optional($report->reporter)->name ?? 'unknown' }}
            ({{ optional($report->reporter)->email }})
            &middot; {{ $report->created_at->diffForHumans() }}
          </div>
        </div>
      </div>

      <div class="reason">
        <strong>Reason:</strong> {{ $report->reason }}
      </div>

      @if ($report->pin)
        <div class="meta">
          <strong>Pin:</strong>
          &ldquo;{{ \Illuminate\Support\Str::limit($report->pin->title, 80) }}&rdquo;
          &middot; owner: {{ optional($report->pin->user)->name ?? '-' }}
          &middot; {{ $report->pin->is_hidden ? 'HIDDEN' : 'visible' }}
        </div>
      @endif

      @if ($report->comment)
        <div class="meta">
          <strong>Comment:</strong>
          &ldquo;{{ \Illuminate\Support\Str::limit($report->comment->body, 120) }}&rdquo;
          &middot; author: {{ optional($report->comment->user)->name ?? '-' }}
        </div>
      @endif

      @if ($report->admin_note || $report->reviewed_at)
        <div class="meta" style="margin-top:6px;">
          Reviewed by {{ optional($report->reviewer)->name ?? '-' }}
          &middot; {{ optional($report->reviewed_at)->diffForHumans() }}
          @if ($report->admin_note) &middot; note: {{ $report->admin_note }} @endif
        </div>
      @endif

      <div class="actions">
        @if ($report->status !== 'dismissed')
          <form method="POST" action="{{ route('admin.reports.dismiss', $report) }}">
            @csrf
            <button type="submit" class="dismiss">Dismiss</button>
          </form>
        @endif

        @if ($report->status !== 'reviewed')
          <form method="POST" action="{{ route('admin.reports.hide', $report) }}">
            @csrf
            <button type="submit" class="hide">Hide content</button>
          </form>
        @endif

        @if ($report->pin_id && $report->pin && $report->pin->is_hidden)
          <form method="POST" action="{{ route('admin.reports.restore', $report) }}">
            @csrf
            <button type="submit" class="restore">Restore pin</button>
          </form>
        @endif
      </div>
    </div>
  @empty
    <div class="empty">No reports in this view.</div>
  @endforelse

  <div class="pager">{{ $reports->links() }}</div>
</div>
</body>
</html>
