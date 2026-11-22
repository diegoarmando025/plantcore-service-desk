<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Support\PortfolioDemoScenario;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class ProvisionPortfolioDemo extends Command {
    protected $signature = 'portfolio:provision-demo {--force : Confirm production database}';
    protected $description = 'Create a tightly scoped interactive portfolio demo account and fictional tickets.';

    public function handle(): int {
        if (app()->environment('production') && ! $this->option('force')) {
            $this->error('Use --force in production.'); return self::FAILURE;
        }
        if (! $this->input->isInteractive()) {
            $this->error('Run in interactive SSH only.'); return self::FAILURE;
        }
        if (User::where('is_owner', true)->count() !== 1) {
            $this->error('Exactly one protected owner is required.'); return self::FAILURE;
        }
        $email = 'portfolio.demo@service-desk.example.invalid';
        if (User::where('is_portfolio_demo', true)->where('email','!=',$email)->exists()) {
            $this->error('Another demo identity already exists.'); return self::FAILURE;
        }
        $existing = User::where('email',$email)->first();
        if ($existing && ! $existing->is_portfolio_demo) {
            $this->error('Reserved email is already taken by a regular account.'); return self::FAILURE;
        }
        $departments = DB::table('departments')->where('active', true)->pluck('id');
        if ($departments->count() !== 5 || DB::table('ticket_categories')->count() < 15) {
            $this->error('Install the expected catalogs first.'); return self::FAILURE;
        }
        $this->warn('This provisions one shared demo identity and writes fictional records to the CURRENT database.');
        $this->line('Database: '.DB::connection()->getDatabaseName());
        if (! $this->confirm('Have you verified a restorable MySQL backup and the target database?', false)) {
            return self::FAILURE;
        }
        try {
            DB::transaction(function () use ($email, $existing, $departments): void {
                $demo = $existing;
                if (! $demo) {
                    $demo = User::create([
                        'name' => 'Evaluador PlantCore', 'email' => $email,
                        'password' => Str::random(64), 'role' => 'agent', 'active' => true,
                    ]);
                    $demo->forceFill(['is_portfolio_demo' => true])->save();
                }
                if ($demo->is_owner || $demo->role !== 'agent' || ! $demo->active) {
                    throw new RuntimeException('Demo account role or state is invalid.');
                }
                DB::table('department_user')->where('user_id', $demo->id)->delete();
                foreach ($departments as $id) {
                    DB::table('department_user')->insert(['department_id'=>$id,'user_id'=>$demo->id,'role'=>'agent']);
                }
                PortfolioDemoScenario::seed($demo);
            }, 3);
        } catch (\Throwable $e) {
            report($e);
            $this->error('Provision failed and transaction rolled back. Inspect application logs.');
            return self::FAILURE;
        }
        $this->info('Demo provisioned. No public password required. Use the demo button on /login.');
        return self::SUCCESS;
    }
}
