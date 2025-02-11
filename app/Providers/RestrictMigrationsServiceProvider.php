<?php
//
//namespace App\Providers;
//
//use Illuminate\Support\ServiceProvider;
//use Illuminate\Support\Facades\App;
//use Illuminate\Console\Events\CommandStarting;
//use Illuminate\Support\Facades\Event;
//
//class RestrictMigrationsServiceProvider extends ServiceProvider
//{
//    public function register()
//    {
//        // No bindings needed
//    }
//
//    public function boot()
//    {
//        Event::listen(CommandStarting::class, function ($event) {
//            $restrictedCommands = [
//                'migrate:fresh',
//                'migrate:fresh --seed',
//                'db:seed'
//            ];
//
//            if (App::environment('local') && in_array($event->command, $restrictedCommands)) {
//                echo "⚠️  Command '{$event->command}' is restricted in the local environment!\n";
//                exit(1);
//            }
//        });
//    }
//}
