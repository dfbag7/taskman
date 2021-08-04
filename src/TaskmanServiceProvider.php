<?php namespace Dimbo\Taskman;

use Illuminate\Support\ServiceProvider;

class TaskmanServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(ScheduledTasksManagerInterface::class, function()
        {
            if(PHP_OS === 'Linux')
            {
                return new LinuxTaskScheduler();
            }
            else
            {
                return new WindowsTaskScheduler();
            }
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [ScheduledTasksManagerInterface::class];
    }
}
