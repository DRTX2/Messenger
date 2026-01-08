<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class ServeAll extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'serve:all 
                            {--host=127.0.0.1 : The host address to serve the application on}
                            {--port=8000 : The port to serve the application on}
                            {--reverb-port=8080 : The port for the Reverb WebSocket server}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start both the Laravel development server and Reverb WebSocket server';

    /**
     * @var Process[]
     */
    protected array $processes = [];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $host = $this->option('host');
        $port = $this->option('port');
        $reverbPort = $this->option('reverb-port');

        $this->info('🚀 Starting Nexus Messenger Development Servers...');
        $this->newLine();

        // Register shutdown handler for cleanup
        pcntl_async_signals(true);
        pcntl_signal(SIGINT, fn() => $this->shutdown());
        pcntl_signal(SIGTERM, fn() => $this->shutdown());

        // Start Laravel API Server
        $this->info("📡 Starting API Server on http://{$host}:{$port}");
        $serveProcess = new Process(['php', 'artisan', 'serve', "--host={$host}", "--port={$port}"]);
        $serveProcess->setTty(Process::isTtySupported());
        $serveProcess->setTimeout(null);
        $serveProcess->start(function ($type, $buffer) {
            $this->output->write($buffer);
        });
        $this->processes[] = $serveProcess;

        sleep(1);

        // Start Reverb WebSocket Server
        $this->info("🔌 Starting WebSocket Server on ws://{$host}:{$reverbPort}");
        $reverbProcess = new Process(['php', 'artisan', 'reverb:start', "--host={$host}", "--port={$reverbPort}"]);
        $reverbProcess->setTty(Process::isTtySupported());
        $reverbProcess->setTimeout(null);
        $reverbProcess->start(function ($type, $buffer) {
            $this->output->write($buffer);
        });
        $this->processes[] = $reverbProcess;

        $this->newLine();
        $this->info('✅ Both servers are running!');
        $this->table(
            ['Service', 'URL'],
            [
                ['API Server', "http://{$host}:{$port}"],
                ['WebSocket', "ws://{$host}:{$reverbPort}"],
            ]
        );
        $this->warn('Press Ctrl+C to stop all servers');
        $this->newLine();

        // Wait for processes
        while ($this->allProcessesRunning()) {
            usleep(100000); // 100ms
        }

        $this->shutdown();
        return 0;
    }

    /**
     * Check if all processes are still running.
     */
    protected function allProcessesRunning(): bool
    {
        foreach ($this->processes as $process) {
            if (!$process->isRunning()) {
                return false;
            }
        }
        return true;
    }

    /**
     * Shutdown all processes gracefully.
     */
    protected function shutdown(): void
    {
        $this->newLine();
        $this->warn('🛑 Shutting down servers...');

        foreach ($this->processes as $process) {
            if ($process->isRunning()) {
                $process->stop(3, SIGTERM);
            }
        }

        $this->info('✅ All servers stopped');
        exit(0);
    }
}
