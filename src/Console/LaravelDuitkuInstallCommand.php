<?php

namespace AdityaDarma\LaravelDuitku\Console;

use AdityaDarma\LaravelDuitku\LaravelDuitkuServiceProvider;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class LaravelDuitkuInstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'duitku:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'It will copy config file to your project.';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
    {
        //config
        if (File::exists(config_path('duitku.php'))) {
            $confirm = $this->confirm("duitku.php config file already exist. Do you want to overwrite?");
            if ($confirm) {
                $this->publishConfig();
                $this->info("config overwrite finished");
            }
            else {
                $this->info("skipped config publish");
            }
        }
        else {
            $this->publishConfig();
            $this->info("config published");
        }
    }

    /**
     * Publish config
     *
     * @return void
     */
    private function publishConfig(): void
    {
        $this->call('vendor:publish', [
            '--provider' => LaravelDuitkuServiceProvider::class,
            '--tag'      => 'config',
            '--force'    => true
        ]);
    }
}
