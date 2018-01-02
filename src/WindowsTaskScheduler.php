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
     * @param \DateInterval $interval
     *
     * @return string
     */
    private function dateIntervalToString(\DateInterval $interval)
    {
        // Reading all non-zero date parts.
        $date = array_filter(array(
            'Y' => $interval->y,
            'M' => $interval->m,
            'D' => $interval->d
        ));

        // Reading all non-zero time parts.
        $time = array_filter(array(
            'H' => $interval->h,
            'M' => $interval->i,
            'S' => $interval->s
        ));

        $specString = 'P';

        // Adding each part to the spec-string.
        foreach ($date as $key => $value) {
            $specString .= $value . $key;
        }
        if (count($time) > 0) {
            $specString .= 'T';
            foreach ($time as $key => $value) {
                $specString .= $value . $key;
            }
        }

        return $specString;
    }

    /**
     * @param string $name      Name of the task
     * @param string $program   Full path to a program to execute.
     * @param string $arguments Command line arguments for the program.
     * @param \DateInterval $interval the interval should be less than one day
     *
     * @return bool True on success.
     *
     * @throws \Exception
     */
    public function createTask($name, $program, $arguments, $interval)
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
        // Create a daily trigger. Note that the start boundary
        // specifies the time of day when the task starts and the
        // interval specifies on which days the task is run.
        $triggers = $taskDefinition->Triggers;

        $trigger  = $triggers->Create(TASK_TRIGGER_DAILY);   // defined in the type library
        $trigger->StartBoundary = date('Y-m-d\T\0\0\:\0\0\:\0\0');
        $trigger->DaysInterval = 1;
        $trigger->RandomDelay = 'PT30S'; // 30 seconds
        $trigger->Id = 'DailyTriggerId';
        $trigger->Enabled = true;

        if($interval !== null)
        {
            //
            // Set the task repetition pattern for the task.
            $repetitionPattern = $trigger->Repetition;
            $repetitionPattern->Duration = 'P1D';   // 1 day
            $repetitionPattern->Interval = $this->dateIntervalToString($interval);
        }

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
}
