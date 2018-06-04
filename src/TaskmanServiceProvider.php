<?php namespace Dimbo\Taskman;

use Illuminate\Support\ServiceProvider;

class TaskmanServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bindShared('\Dimbo\Taskman\ScheduledTasksManagerInterface', function()
        {
            if(true) //PHP_OS === 'Linux')
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
        return ['\Dimbo\Taskman\ScheduledTasksManagerInterface'];
    }
}
