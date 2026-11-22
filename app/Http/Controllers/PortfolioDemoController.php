<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PortfolioDemoController extends Controller {
    public function enter(Request $request): RedirectResponse {
        $demo = User::query()->where('is_portfolio_demo', true)->where('is_owner', false)
            ->where('active', true)->where('role', 'agent')->get();
        abort_unless($demo->count() === 1, 503, 'Acceso de evaluación no configurado.');
        $user = $demo->first();
        $expected = DB::table('departments')->where('active', true)->pluck('id')->sort()->values()->all();
        $actual = DB::table('department_user')->where('user_id', $user->id)
            ->where('role', 'agent')->pluck('department_id')->sort()->values()->all();
        abort_unless(count($expected) > 0 && $expected === $actual
            && DB::table('department_user')->where('user_id', $user->id)->count() === count($expected),
            503, 'Permisos de evaluación incompletos.');
        Auth::login($user);
        $request->session()->regenerate();
        return redirect()->route('portfolio.guide');
    }

    public function guide(Request $request): View {
        $user = $request->user();
        $query = Ticket::query();
        if ($user->is_portfolio_demo) $query->where('is_portfolio_demo', true);
        elseif (! $user->is_owner) {
            $query->where('is_portfolio_demo', false);
            if (! $user->isAdmin()) {
                $ids = $user->departments()->pluck('departments.id');
                $query->where(function ($q) use ($user, $ids) {
                    $q->where('requester_id', $user->id);
                    if ($user->role === 'agent') $q->orWhereIn('department_id', $ids);
                });
            }
        }
        $total = (clone $query)->count();
        $recent = $query->latest('id')->limit(6)->get(['id','code','title','status']);
        return view('portfolio.guide', compact('total', 'recent'));
    }
}
