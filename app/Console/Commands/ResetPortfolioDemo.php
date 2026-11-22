<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Ticket;
use App\Support\PortfolioDemoScenario;
use Illuminate\Console\Command;

class ResetPortfolioDemo extends Command {
    protected $signature = 'portfolio:reset-demo {--dry-run : Show counts without changes}';
    protected $description = 'Reset ONLY tickets explicitly marked as portfolio demo.';

    public function handle(): int {
        $demo=User::where('email','portfolio.demo@service-desk.example.invalid')->where('is_portfolio_demo', true)->where('is_owner',false)->get();
        if ($demo->count() !== 1) {
            $this->error('Exactly one demo account required.'); return self::FAILURE;
        }
        $count=Ticket::where('is_portfolio_demo',true)->count();
        $this->line("Marked demo tickets: $count");
        if ($this->option('dry-run')) return self::SUCCESS;
        if (! $this->input->isInteractive()) {
            $this->error('Only interactive SSH may reset records.'); return self::FAILURE;
        }
        $this->warn('Only flagged fictional tickets and their messages/activities are removed.');
        if (! $this->confirm('Do you have a verified restorable backup?',false) ||
            $this->ask('Type RESET SERVICE DESK DEMO') !== 'RESET SERVICE DESK DEMO') {
            return self::FAILURE;
        }
        try { $deleted=PortfolioDemoScenario::reset($demo->first()); }
        catch (\Throwable $e) { report($e);$this->error('Reset failed; see logs.');return self::FAILURE; }
        $this->info("Reset $deleted demo tickets. Non-demo tickets untouched.");
        return self::SUCCESS;
    }
}
