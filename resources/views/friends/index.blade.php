<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Friends · Echo-Map</title>
<style>
  :root { --bg:#f4f6f8; --card:#ffffff; --ink:#1f2a37; --muted:#6b7280; --line:#e5e7eb; --accent:#3b6ef6; }
  * { box-sizing: border-box; }
  body { margin:0; font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; background:var(--bg); color:var(--ink); }
  header { background:var(--card); border-bottom:1px solid var(--line); padding:14px 22px; display:flex; align-items:center; justify-content:space-between; }
  header h1 { font-size:18px; margin:0; }
  header a { color:var(--accent); text-decoration:none; margin-left:14px; font-size:14px; }
  .wrap { max-width:820px; margin:26px auto; padding:0 18px; }
  .card { background:var(--card); border:1px solid var(--line); border-radius:10px; padding:18px 20px; margin-bottom:18px; }
  .card h2 { margin:0 0 12px; font-size:15px; text-transform:uppercase; letter-spacing:.06em; color:var(--muted); }
  form.inline { display:flex; gap:8px; }
  input[type=email] { flex:1; padding:9px 11px; border:1px solid var(--line); border-radius:8px; font-size:14px; }
  button { padding:9px 14px; border:0; border-radius:8px; background:var(--accent); color:#fff; font-size:14px; cursor:pointer; }
  button.ghost { background:transparent; color:var(--muted); border:1px solid var(--line); }
  ul { list-style:none; margin:0; padding:0; }
  li { display:flex; align-items:center; justify-content:space-between; padding:9px 0; border-bottom:1px solid var(--line); }
  li:last-child { border-bottom:0; }
  .name { font-weight:500; }
  .muted { color:var(--muted); font-size:13px; }
  .flash { padding:10px 14px; border-radius:8px; margin-bottom:14px; font-size:14px; }
  .flash.ok { background:#e7f5ec; color:#1b6b3a; }
  .flash.err { background:#fdecec; color:#8a1f1f; }
</style>
</head>
<body>
<header>
  <h1>Echo-Map · Friends</h1>
  <nav>
    <a href="/map">Map</a>
    <a href="/dashboard">Dashboard</a>
  </nav>
</header>

<div class="wrap">
  @if (session('status'))
    <div class="flash ok">{{ session('status') }}</div>
  @endif
  @if (session('error'))
    <div class="flash err">{{ session('error') }}</div>
  @endif

  <div class="card">
    <h2>Add a friend</h2>
    <form class="inline" method="POST" action="{{ route('friends.store') }}">
      @csrf
      <input type="email" name="email" placeholder="friend@example.com" required>
      <button type="submit">Send request</button>
    </form>
    @error('email') <p class="muted" style="color:#8a1f1f">{{ $message }}</p> @enderror
  </div>

  <div class="card">
    <h2>Incoming requests</h2>
    @if ($incoming->where('status','pending')->isEmpty())
      <p class="muted">No pending requests.</p>
    @else
      <ul>
        @foreach ($incoming->where('status','pending') as $req)
          <li>
            <span class="name">{{ $req->sender->name }}</span>
            <span>
              <form method="POST" action="{{ route('friends.accept', $req) }}" style="display:inline">
                @csrf <button type="submit">Accept</button>
              </form>
              <form method="POST" action="{{ route('friends.decline', $req) }}" style="display:inline">
                @csrf @method('DELETE') <button type="submit" class="ghost">Decline</button>
              </form>
            </span>
          </li>
        @endforeach
      </ul>
    @endif
  </div>

  <div class="card">
    <h2>Sent requests</h2>
    @if ($outgoing->where('status','pending')->isEmpty())
      <p class="muted">No outgoing requests.</p>
    @else
      <ul>
        @foreach ($outgoing->where('status','pending') as $req)
          <li>
            <span class="name">{{ $req->receiver->name }}</span>
            <span class="muted">Pending…</span>
          </li>
        @endforeach
      </ul>
    @endif
  </div>

  <div class="card">
    <h2>Your friends ({{ $friends->count() }})</h2>
    @if ($friends->isEmpty())
      <p class="muted">No friends yet.</p>
    @else
      <ul>
        @foreach ($friends as $friend)
          <li>
            <span class="name">{{ $friend->name }}</span>
            <form method="POST" action="{{ route('friends.destroy', $friend) }}">
              @csrf @method('DELETE')
              <button type="submit" class="ghost">Unfriend</button>
            </form>
          </li>
        @endforeach
      </ul>
    @endif
  </div>
</div>
</body>
</html>
