<?php namespace Dimbo\Taskman;

class WindowsTaskScheduler implements ScheduledTasksManagerInterface
{
    protected $parentFolderName = 'Tethra';

    /** @var resource $service */
    protected $service;

    protected function getService()
    {
        if($this->service === null)
        {
            $this->service = new \COM('Schedule.Service');
            $this->service->Connect();
        }

        return $this->service;
    }

    protected function getFolder($folderName)
    {
        $folder = null;

        try
        {
            $folder = $this->getService()->GetFolder($folderName);
        }
        catch(\com_exception $ex)
        {
            //
        }

        return $folder;
    }

    /**
     * @param string $folderName
     *
     * @return resource TaskFolder object instance
     */
    protected function createFolderIfNotExists($folderName)
    {
        $folder = $this->getFolder('\\' . $folderName);

        if($folder === null)
        {
            $rootFolder = $this->getService()->GetFolder('\\');
            $folder = $rootFolder->CreateFolder($folderName, null);
        }

        return $folder;
    }

    /**
     * @param string $name      Name of the task
     * @param string $program   Full path to a program to execute.
     * @param string $arguments Command line arguments for the program.
     *
     * @return bool True on success.
     *
     * @throws \Exception
     */
    public function createDailyTask($name, $program, $arguments)
    {
        return $this->createTaskWithCallback($name, $program, $arguments, function($triggers)
        {
            //
            // Create a daily trigger. Note that the start boundary
            // specifies the time of day when the task starts and the
            // interval specifies on which days the task is run.
            $trigger  = $triggers->Create(TASK_TRIGGER_DAILY);   // defined in the type library
            $trigger->StartBoundary = date('Y-m-d\T\0\0\:\0\0\:\0\0');
            $trigger->DaysInterval = 1;
            $trigger->RandomDelay = 'PT30S'; // 30 seconds
            $trigger->Id = 'DailyTriggerId';
            $trigger->Enabled = true;
        });
    }

    /**
     * @param string $name      Name of the task
     * @param string $program   Full path to a program to execute.
     * @param string $arguments Command line arguments for the program.
     *
     * @return bool True on success.
     */
    public function createMinuteTask($name, $program, $arguments)
    {
        return $this->createTaskWithCallback($name, $program, $arguments, function($triggers)
        {
            //
            // Create a daily trigger. Note that the start boundary
            // specifies the time of day when the task starts and the
            // interval specifies on which days the task is run.
            $trigger  = $triggers->Create(TASK_TRIGGER_DAILY);   // defined in the type library
            $trigger->StartBoundary = date('Y-m-d\T\0\0\:\0\0\:\0\0');
            $trigger->DaysInterval = 1;
            $trigger->RandomDelay = 'PT30S'; // 30 seconds
            $trigger->Id = 'DailyTriggerId';
            $trigger->Enabled = true;

            // Set the task repetition pattern.
            $repetitionPattern = $trigger->Repetition;
            $repetitionPattern->Duration = 'P1D'; // 1 day
            $repetitionPattern->Interval = 'PT1M'; // 1 minute
        });
    }

    /**
     * @param string $name      Name of the task
     * @param string $program   Full path to a program to execute.
     * @param string $arguments Command line arguments for the program.
     *
     * @return bool True on success.
     *
     * @throws \Exception
     */
    public function createWeeklyTask($name, $program, $arguments)
    {
        return $this->createTaskWithCallback($name, $program, $arguments, function($triggers)
        {
            $trigger = $triggers->Create(TASK_TRIGGER_WEEKLY);   // defined in the type library
            $trigger->StartBoundary = date('Y-m-d\T\0\0\:\0\0\:\0\0');
            $trigger->DaysOfWeek = 0x40; // Saturday
            $trigger->WeeksInterval = 1; // every week
            $trigger->RandomDelay = 'PT30S'; // 30 seconds
            $trigger->Id = 'WeeklyTriggerId';
            $trigger->Enabled = true;
        });
    }

    /**
     * @param string $name
     * @param string $program
     * @param string $arguments
     * @param callable $setupTrigger
     *
     * @return bool|void
     */
    protected function createTaskWithCallback($name, $program, $arguments, callable $setupTrigger)
    {
        $service = $this->getService();

        // Get a folder to create a task definition in.
        $folder = $this->createFolderIfNotExists($this->parentFolderName);

        // The flags parameter is 0 because it is not supported.
        $taskDefinition = $service->NewTask(0);

        //
        // Define information about the task.
        //
        // Set the registration info for the task by
        // creating the RegistrationInfo object.
        $regInfo = $taskDefinition->RegistrationInfo;
        $regInfo->Description = 'Tethra Automatically maintained scheduled task';
        $regInfo->Author = 'Administrator';

        //
        // Set the principal for the task.
        $principal = $taskDefinition->Principal;
        $principal->Id = 'Author';
        $principal->LogonType = TASK_LOGON_SERVICE_ACCOUNT;   // defined in the type library
        $principal->UserId = 'S-1-5-18'; // NT_AUTHORITY\SYSTEM
        $principal->RunLevel = TASK_RUNLEVEL_HIGHEST;         // defined in the type library

        //
        // Set the task setting info for the Task Scheduler by
        // creating a TaskSettings object.
        $settings = $taskDefinition->Settings;
        $settings->Enabled = true;
        $settings->StartWhenAvailable = true;
        $settings->Hidden = false;
        $settings->MultipleInstances = TASK_INSTANCES_IGNORE_NEW;    // defined in the type library
        $settings->RunOnlyIfIdle = false;
        $settings->ExecutionTimeLimit = 'P1D'; // 1 day
        $settings->Priority = 7;

        //
        // Set up trigger to run
        //
        $triggers = $taskDefinition->Triggers;
        $setupTrigger($triggers);

        $action = $taskDefinition->Actions->Create(TASK_ACTION_EXEC);   // defined in the type library
        $action->Path = $program;
        $action->Arguments = $arguments;

        $emptyVariant = new \VARIANT();

        $folder->RegisterTaskDefinition($name, $taskDefinition, TASK_CREATE_OR_UPDATE,    // defined in the type library
            $emptyVariant, $emptyVariant, TASK_LOGON_SERVICE_ACCOUNT);                    // defined in the type library

        return true;
    }

    /**
     * @return array Array of task names
     */
    public function getTasks()
    {
        $result = [];

        $folder = $this->getFolder('\\' . $this->parentFolderName);
        if($folder !== null)
        {
            $taskCollection = $folder->GetTasks(0);

            $count = $taskCollection->Count;

            if($count != 0)
            {
                foreach($taskCollection as $item)
                {
                    $result[] = $item->Name;
                }
            }
        }

        return $result;
    }

    /**
     * @param string $name Name of the task to delete.
     *
     * @return bool True if the task was existing.
     */
    public function deleteTask($name)
    {
        $result = false;

        $folder = $this->getFolder('\\' . $this->parentFolderName);
        if($folder !== null)
        {
            $folder->DeleteTask($name, 0);
            $result = true;
        }

        return $result;
    }

    /**
     * @param string $name
     * @param bool $enable
     *
     * @return bool True if the task exists and was disabled.
     */
    public function enableTask($name, $enable)
    {
        $result = false;

        $folder = $this->getFolder('\\' . $this->parentFolderName);
        if($folder !== null)
        {
            $task = $folder->GetTask($name);
            if($task !== null)
            {
                $task->Enabled = $enable;
                $result = true;
            }
        }

        return $result;
    }
}
