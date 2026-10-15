<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Report;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', 'pending');
        $allowed = ['pending', 'reviewed', 'dismissed', 'all'];
        if (! in_array($status, $allowed, true)) $status = 'pending';

        $reports = Report::with(['reporter:id,name,email', 'reviewer:id,name', 'pin.user:id,name', 'pin:id,title,is_hidden,user_id', 'comment.user:id,name', 'comment:id,body,user_id'])
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        return view('admin.reports.index', [
            'reports' => $reports,
            'status'  => $status,
        ]);
    }

    public function dismiss(Request $request, Report $report)
    {
        $report->update([
            'status'      => 'dismissed',
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'admin_note'  => $request->input('admin_note'),
        ]);

        return back()->with('status', 'Report dismissed.');
    }

    public function hide(Request $request, Report $report)
    {
        // Hide the underlying target (pin takes precedence, then comment).
        if ($report->pin_id && $report->pin) {
            $report->pin->update(['is_hidden' => true]);
        }
        if ($report->comment_id && $report->comment) {
            // Comments have no is_hidden column in your schema. Delete instead.
            $report->comment->delete();
        }

        $report->update([
            'status'      => 'reviewed',
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'admin_note'  => $request->input('admin_note'),
        ]);

        return back()->with('status', 'Content hidden and report marked reviewed.');
    }

    public function restore(Request $request, Report $report)
    {
        if ($report->pin_id && $report->pin) {
            $report->pin->update(['is_hidden' => false]);
        }

        $report->update([
            'status'      => 'pending',
            'reviewed_by' => null,
            'reviewed_at' => null,
        ]);

        return back()->with('status', 'Pin restored.');
    }
}